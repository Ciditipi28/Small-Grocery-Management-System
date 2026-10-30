<?php
declare(strict_types=1);
require_once __DIR__.'/mailer.php';

function registration_email(mixed $value): string {
    $email=strtolower(text_value($value,254));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
    return $email;
}
function invitation_issuer_active(array $invite): bool {
    return (bool)one('SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1 AND r.role_name=?',[$invite['invited_by'],'Owner']);
}
// The optional delivery callback is injected by isolated CLI tests only, never HTTP input.
function issue_invitation_code(int $inviteId,?callable $deliver=null): void {
    $i=one('SELECT * FROM account_invitations WHERE id=? FOR UPDATE',[$inviteId]);
    if(!$i || $i['used_at'] || $i['revoked_at'] || !invitation_issuer_active($i)) throw new DomainException('This invitation is no longer available.');
    $now=time();
    if($i['last_sent_at'] && strtotime($i['last_sent_at'])>$now-60) throw new DomainException('Wait 60 seconds before sending another code.');
    $window=$i['send_window_at']?strtotime($i['send_window_at']):0;
    $count=$window>$now-3600?(int)$i['sends_in_window']:0;
    if($count>=5) throw new DomainException('The hourly email limit was reached. Try again later.');
    do { $code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT); }
    while($i['otp_hash'] && password_verify($code,$i['otp_hash']));
    ($deliver??'send_otp_email')($i['email'],$code);
    query('UPDATE account_invitations SET otp_hash=?,expires_at=?,attempts=0,last_sent_at=?,send_window_at=?,sends_in_window=? WHERE id=?',[
        password_hash($code,PASSWORD_DEFAULT),date('Y-m-d H:i:s',$now+600),date('Y-m-d H:i:s',$now),date('Y-m-d H:i:s',$count?$window:$now),$count+1,$i['id']
    ]);
}
function invite_staff(array $owner,array $data,?callable $deliver=null): void {
    require_owner($owner);
    $email=registration_email($data['email']??''); $role=quantity($data['role_id']??'');
    atomic(function() use($owner,$email,$role,$deliver) {
        if(!one('SELECT id FROM roles WHERE id=?',[$role])) throw new DomainException('Choose a valid role.');
        if(one('SELECT id FROM users WHERE email=?',[$email])) throw new DomainException('That email already belongs to a staff account.');
        $existing=one('SELECT * FROM account_invitations WHERE email=? FOR UPDATE',[$email]);
        if($existing && (!$existing['revoked_at'] || $existing['used_at'])) throw new DomainException('An invitation already exists for this email. Resend its code or review its status below.');
        if($existing) {
            $id=(int)$existing['id'];
            query('UPDATE account_invitations SET role_id=?,invited_by=?,revoked_at=NULL WHERE id=?',[$role,$owner['id'],$id]);
        } else {
            query('INSERT INTO account_invitations (email,role_id,invited_by) VALUES (?,?,?)',[$email,$role,$owner['id']]);
            $id=(int)db()->lastInsertId();
        }
        issue_invitation_code($id,$deliver);
        audit((int)$owner['id'],'INVITE','account_invitations',$id,'Staff email invitation issued');
    });
}
function resend_invitation(array $owner,int $id,?callable $deliver=null): void {
    require_owner($owner);
    atomic(function() use($owner,$id,$deliver) {
        issue_invitation_code($id,$deliver);
        audit((int)$owner['id'],'RESEND','account_invitations',$id,'Verification code resent');
    });
}
function revoke_invitation(array $owner,int $id): void {
    require_owner($owner);
    atomic(function() use($owner,$id) {
        $i=one('SELECT * FROM account_invitations WHERE id=? FOR UPDATE',[$id]);
        if(!$i||$i['used_at']||$i['revoked_at']) throw new DomainException('Invitation is no longer pending.');
        query('UPDATE account_invitations SET revoked_at=NOW(),otp_hash=NULL WHERE id=?',[$id]);
        audit((int)$owner['id'],'REVOKE','account_invitations',$id,'Staff invitation revoked');
    });
}
function request_registration_code(mixed $rawEmail,?callable $deliver=null): void {
    $email=registration_email($rawEmail);
    try {
        atomic(function() use($email,$deliver) {
            $i=one('SELECT id FROM account_invitations WHERE email=?',[$email]);
            if($i) issue_invitation_code((int)$i['id'],$deliver);
        });
    } catch(DomainException $e) { /* Do not expose invitation status or delivery failures to visitors. */ }
}
function register_staff(array $data): int {
    $email=registration_email($data['email']??'');
    $name=text_value($data['full_name']??'',100);
    $username=text_value($data['username']??'',60);
    if(!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D',$username)) throw new DomainException('Use 3–60 letters, digits, dots, underscores or hyphens for usernames.');
    $password=$data['password']??'';
    if(!is_string($password)||strlen($password)<12||strlen($password)>72) throw new DomainException('Passwords must have 12–72 characters.');
    if($password!==($data['password_confirm']??null)) throw new DomainException('The passwords do not match.');
    $code=is_string($data['otp']??null)?$data['otp']:'';
    $result=atomic(function() use($email,$name,$username,$password,$code) {
        $i=one('SELECT * FROM account_invitations WHERE email=? FOR UPDATE',[$email]);
        $bad='The code is invalid, expired, or unavailable. Ask the owner for an invitation or request another code.';
        if(!$i||$i['used_at']||$i['revoked_at']||!$i['otp_hash']||!$i['expires_at']||strtotime($i['expires_at'])<=time()||(int)$i['attempts']>=5||!invitation_issuer_active($i)) return ['error'=>$bad];
        if(!preg_match('/^[0-9]{6}$/D',$code)||!password_verify($code,$i['otp_hash'])) {
            // Return the error so the failed-attempt update commits before throwing.
            query('UPDATE account_invitations SET attempts=attempts+1 WHERE id=?',[$i['id']]);
            return ['error'=>$bad];
        }
        if(one('SELECT id FROM users WHERE username=? OR email=?',[$username,$email])) return ['error'=>'That username or email is already in use.'];
        query('INSERT INTO users (role_id,full_name,username,password_hash,email,email_verified_at) VALUES (?,?,?,?,?,NOW())',[$i['role_id'],$name,$username,password_hash($password,PASSWORD_DEFAULT),$email]);
        $id=(int)db()->lastInsertId();
        query('UPDATE account_invitations SET used_at=NOW(),user_id=?,otp_hash=NULL WHERE id=?',[$id,$i['id']]);
        audit($id,'REGISTER','users',$id,'Account created after email code verification');
        return ['id'=>$id];
    });
    if(isset($result['error'])) throw new DomainException($result['error']);
    return (int)$result['id'];
}
