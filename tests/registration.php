<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/masters.php';
$config['db_name']='grocery_test_'.bin2hex(random_bytes(6));
$server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$checks=0;
function check(bool $ok,string $label): void { global $checks; if(!$ok) throw new RuntimeException($label); $checks++; echo "PASS $label\n"; }
function deny(callable $fn,string $label): void { try { $fn(); } catch(DomainException $e) { check(true,$label); return; } throw new RuntimeException('Expected rejection: '.$label); }
function age_code(string $email): void { query('UPDATE account_invitations SET last_sent_at=DATE_SUB(NOW(),INTERVAL 61 SECOND) WHERE email=?',[$email]); }
$codes=[];
$capture=function(string $email,string $code) use(&$codes) { $codes[$email]=$code; };
$profile=fn(string $email,string $code,string $username='new_cashier')=>['email'=>$email,'otp'=>$code,'full_name'=>'Test Staff','username'=>$username,'password'=>'RegistrationTest!2026','password_confirm'=>'RegistrationTest!2026','role_id'=>1];
try {
    $server->exec('CREATE DATABASE `'.$config['db_name'].'` CHARACTER SET utf8mb4');
    db()->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
    query('INSERT INTO users(role_id,full_name,username,password_hash) VALUES(1,"Owner","owner",?),(2,"Cashier","cashier",?)',[password_hash('OwnerTest!2026',PASSWORD_DEFAULT),password_hash('CashierTest!2026',PASSWORD_DEFAULT)]);
    $owner=one('SELECT *,"Owner" role_name FROM users WHERE id=1'); $cashier=one('SELECT *,"Cashier" role_name FROM users WHERE id=2');
    deny(fn()=>save_master($owner,'users',['full_name'=>'Bypass','username'=>'bypass','role_id'=>2,'password'=>'RegistrationTest!2026','is_active'=>1]),'Direct staff creation cannot bypass email verification');
    deny(fn()=>invite_staff($cashier,['email'=>'staff@example.test','role_id'=>2],$capture),'Cashier cannot invite accounts');
    invite_staff($owner,['email'=>'STAFF@example.test','role_id'=>2],$capture);
    check((int)query('SELECT COUNT(*) FROM users')->fetchColumn()===2,'Invitation does not create a login account');
    $i=one('SELECT * FROM account_invitations WHERE email=?',['staff@example.test']);
    check(preg_match('/^[0-9]{6}$/D',$codes['staff@example.test'])===1&&$i['otp_hash']!==$codes['staff@example.test']&&password_verify($codes['staff@example.test'],$i['otp_hash']),'Only the OTP hash is stored');
    check(strtotime($i['expires_at'])-strtotime($i['last_sent_at'])===600,'Code expiry is ten minutes');
    deny(fn()=>resend_invitation($owner,(int)$i['id'],$capture),'Resend cooldown enforced');
    $wrong=$codes['staff@example.test']==='000000'?'111111':'000000';
    deny(fn()=>register_staff($profile('staff@example.test',$wrong)),'Wrong code rejected');
    check((int)query('SELECT attempts FROM account_invitations WHERE id=?',[$i['id']])->fetchColumn()===1,'Wrong attempt commits to the database');
    for($n=0;$n<4;$n++) { try { register_staff($profile('staff@example.test',$wrong)); } catch(DomainException $e) {} }
    deny(fn()=>register_staff($profile('staff@example.test',$codes['staff@example.test'])),'Correct code rejected after five failures');
    age_code('staff@example.test');
    $old=$codes['staff@example.test']; resend_invitation($owner,(int)$i['id'],$capture);
    check((int)query('SELECT attempts FROM account_invitations WHERE id=?',[$i['id']])->fetchColumn()===0,'New code resets attempt count');
    // Reissue once in the extremely unlikely event that random generation repeated the old value.
    if($old===$codes['staff@example.test']) { age_code('staff@example.test'); resend_invitation($owner,(int)$i['id'],$capture); }
    if($old!==$codes['staff@example.test']) deny(fn()=>register_staff($profile('staff@example.test',$old)),'Previous code invalid after resend');
    $id=register_staff($profile('staff@example.test',$codes['staff@example.test']));
    $user=one('SELECT * FROM users WHERE id=?',[$id]);
    check((int)$user['role_id']===2&&$user['email_verified_at']!==null&&password_verify('RegistrationTest!2026',$user['password_hash']),'Verified account created with owner-selected role, ignoring posted role');
    deny(fn()=>register_staff($profile('staff@example.test',$codes['staff@example.test'],'replay_user')),'Used code cannot create another account');
    check(one('SELECT otp_hash FROM account_invitations WHERE id=?',[$i['id']])['otp_hash']===null,'Used code hash removed');
    invite_staff($owner,['email'=>'expired@example.test','role_id'=>2],$capture);
    query('UPDATE account_invitations SET expires_at=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE email=?',['expired@example.test']);
    deny(fn()=>register_staff($profile('expired@example.test',$codes['expired@example.test'],'expired_user')),'Expired code rejected');
    deny(fn()=>register_staff($profile('unknown@example.test','123456','unknown_user')),'Uninvited email rejected');
    invite_staff($owner,['email'=>'revoked@example.test','role_id'=>2],$capture);
    $revoke=(int)query('SELECT id FROM account_invitations WHERE email=?',['revoked@example.test'])->fetchColumn();
    revoke_invitation($owner,$revoke);
    deny(fn()=>register_staff($profile('revoked@example.test',$codes['revoked@example.test'],'revoked_user')),'Revoked invitation rejected');
    age_code('revoked@example.test'); invite_staff($owner,['email'=>'revoked@example.test','role_id'=>2],$capture);
    check(one('SELECT revoked_at FROM account_invitations WHERE id=?',[$revoke])['revoked_at']===null,'Owner can re-invite a revoked email');
    invite_staff($owner,['email'=>'limited@example.test','role_id'=>2],$capture);
    $limited=(int)query('SELECT id FROM account_invitations WHERE email=?',['limited@example.test'])->fetchColumn();
    for($n=0;$n<4;$n++) { age_code('limited@example.test'); resend_invitation($owner,$limited,$capture); }
    age_code('limited@example.test'); deny(fn()=>resend_invitation($owner,$limited,$capture),'Hourly email cap enforced');
    $before=(int)query('SELECT COUNT(*) FROM account_invitations')->fetchColumn();
    deny(fn()=>invite_staff($owner,['email'=>'failed@example.test','role_id'=>2],fn()=>throw new DomainException('Delivery failed')),'Delivery failure reported');
    check((int)query('SELECT COUNT(*) FROM account_invitations')->fetchColumn()===$before,'Failed delivery rolls back invitation');
    request_registration_code('uninvited@example.test',$capture);
    check(!isset($codes['uninvited@example.test']),'Public resend does not send to an uninvited address');
    $previousHash=query('SELECT otp_hash FROM account_invitations WHERE id=?',[$revoke])->fetchColumn();
    age_code('revoked@example.test');
    deny(fn()=>resend_invitation($owner,$revoke,fn()=>throw new DomainException('Delivery failed')),'Resend delivery failure reported');
    check(query('SELECT otp_hash FROM account_invitations WHERE id=?',[$revoke])->fetchColumn()===$previousHash,'Failed resend preserves the previous code');
    $config['mail_smtp_username']=''; $config['mail_smtp_password']=''; $config['mail_from']='';
    deny(fn()=>invite_staff($owner,['email'=>'notconfigured@example.test','role_id'=>2]),'Missing Gmail configuration fails closed without creating an account');
    query('UPDATE users SET is_active=0 WHERE id=1');
    deny(fn()=>register_staff($profile('revoked@example.test',$codes['revoked@example.test'],'owner_inactive')),'Inactive inviter cannot authorize a new account');
    query('UPDATE users SET is_active=1 WHERE id=1');
    if(isset($argv[1])) {
        $config=array_replace($config,['mail_smtp_host'=>'127.0.0.1','mail_smtp_port'=>(int)$argv[1],'mail_smtp_security'=>'','mail_smtp_username'=>'fixture@example.test','mail_smtp_password'=>'test-only','mail_from'=>'fixture@example.test']);
        send_otp_email('recipient@example.test','123456');
        check(true,'PHPMailer SMTP transport sent to the local fixture only');
    }
    echo "$checks registration checks passed. No real emails sent.\n";
} finally { $server->exec('DROP DATABASE IF EXISTS `'.$config['db_name'].'`'); }
