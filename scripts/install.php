<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
try {
    if(!preg_match('/^[a-zA-Z0-9_]+$/D',$config['db_name'])) throw new RuntimeException('Invalid database name.');
    $pdo=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $exists=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?'); $exists->execute([$config['db_name']]);
    if($exists->fetchColumn()>0) throw new RuntimeException('Database already contains tables. Installer will not overwrite them.');
    $username=trim(readline('Owner username: ')); $name=trim(readline('Owner full name: '));
    $password=getenv('GROCERY_INSTALL_PASSWORD') ?: readline('Owner password (12+ characters; visible while typing): ');
    if(!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D',$username)||$name===''||mb_strlen($name)>100||strlen($password)<12||strlen($password)>72) throw new RuntimeException('Use a valid username, name, and password of 12–72 characters.');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$config['db_name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `'.$config['db_name'].'`');
    $pdo->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
    $s=$pdo->prepare('INSERT INTO users (role_id,full_name,username,password_hash) VALUES (1,?,?,?)'); $s->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Installation complete. Sign in with the owner account you just created.\n";
} catch(Throwable $e) { fwrite(STDERR,'Installation failed: '.$e->getMessage()."\n"); exit(1); }
