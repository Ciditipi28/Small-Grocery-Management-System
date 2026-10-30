<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
try {
    foreach(['email'=>'VARCHAR(254) NULL UNIQUE','email_verified_at'=>'DATETIME NULL'] as $column=>$type) {
        if(!(int)query('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?',[$config['db_name'],'users',$column])->fetchColumn()) db()->exec("ALTER TABLE users ADD COLUMN $column $type");
    }
    db()->exec(file_get_contents(dirname(__DIR__).'/database/migrations/002-email-registration.sql'));
    echo "Email registration migration applied. Existing accounts and transactions preserved.\n";
} catch(Throwable $e) { fwrite(STDERR,'Migration failed: '.$e->getMessage()."\n"); exit(1); }
