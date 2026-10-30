<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/scripts/hosting-helpers.php';
function hosting_check(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
function hosting_import(PDO $pdo,string $sql): void {
    $sql=preg_replace('/^--.*$/m','',$sql);
    foreach(explode(';',$sql) as $statement) if(trim($statement)!=='') { $s=$pdo->query($statement); if($s) $s->closeCursor(); }
}
$settings=['require_https'=>true,'registration_url'=>'https://grocery.example.test/public/index.php?page=register'];
hosting_check(https_redirect_target(['require_https'=>false],['REQUEST_URI'=>'/public/'])===null,'Local HTTP preview remains supported');
hosting_check(https_redirect_target($settings,['HTTPS'=>'on'])===null,'HTTPS requests are served without a redirect');
hosting_check(https_redirect_target($settings,['HTTPS'=>'off','HTTP_HOST'=>'attacker.example','REQUEST_URI'=>'/public/index.php?page=register'])==='https://grocery.example.test/public/index.php?page=register','HTTP redirects use the configured site, not the Host header');
hosting_check(https_redirect_target($settings,['HTTP_X_FORWARDED_PROTO'=>'https','REQUEST_URI'=>'/public/'])==='https://grocery.example.test/public/','Untrusted forwarded headers cannot bypass HTTPS enforcement');
hosting_check(https_redirect_target($settings,['REQUEST_URI'=>"/\r\nInjected: value"])==='https://grocery.example.test/','Unsafe redirect request paths are discarded');
$rejected=false; try { https_redirect_target(['require_https'=>true,'registration_url'=>'http://localhost/'],[]); } catch(DomainException $e) { $rejected=true; }
hosting_check($rejected,'HTTPS mode rejects an HTTP registration URL');
$config['db_name']='grocery_test_'.bin2hex(random_bytes(6));
$server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try {
    $server->exec('CREATE DATABASE `'.$config['db_name'].'` CHARACTER SET utf8mb4');
    $sql=file_get_contents(dirname(__DIR__).'/releases/mini_grocery_hosting_schema.sql');
    $body=preg_replace('/^--.*$/m','',$sql);
    hosting_check(!preg_match('/\bCREATE\s+DATABASE\b|\bUSE\s+`?/i',$body),'Hosting schema does not create or select a fixed database');
    hosting_import(db(),$sql);
    hosting_check((int)query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?',[$config['db_name']])->fetchColumn()===16,'Hosting import creates the complete 16-table schema');
    $password='LiveTestPassword!2026'; $name="Owner's \\ Store";
    $ownerSql=first_owner_import_sql('live_owner',$name,$password);
    hosting_check(!str_contains($ownerSql,$password),'Private owner import contains a password hash, not the password');
    hosting_import(db(),$ownerSql);
    $owner=one('SELECT * FROM users WHERE username=?',['live_owner']);
    hosting_check($owner&&$owner['full_name']===$name&&password_verify($password,$owner['password_hash']),'Private SQL initializes the chosen owner without remote CLI access');
    hosting_import(db(),first_owner_import_sql('another_owner','Other','DifferentPassword!2026'));
    hosting_check((int)query('SELECT COUNT(*) FROM users')->fetchColumn()===1,'Reimport cannot add an owner when accounts already exist');
    hosting_check((int)query('SELECT COUNT(*) FROM activity_logs')->fetchColumn()===1,'First owner import records exactly one audit event');
} finally { $server->exec('DROP DATABASE IF EXISTS `'.$config['db_name'].'`'); }
echo "Hosting preparation checks passed. No live server or real email was used.\n";
