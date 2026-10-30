<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
require __DIR__.'/owner-bootstrap.php';
try {
    if((int)query('SELECT COUNT(*) FROM users')->fetchColumn()>0) throw new DomainException('Accounts already exist. Sign in with the existing owner; nothing was changed.');
    $username=trim(readline('First owner username: '));
    $name=trim(readline('First owner full name: '));
    $password=getenv('GROCERY_INSTALL_PASSWORD') ?: readline('First owner password (12-72 characters; visible while typing): ');
    create_initial_owner($username,$name,$password);
    echo "First owner created. You can now sign in.\n";
} catch(Throwable $e) { fwrite(STDERR,'Owner setup failed: '.$e->getMessage()."\n"); exit(1); }
