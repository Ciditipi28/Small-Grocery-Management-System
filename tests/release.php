<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/scripts/owner-bootstrap.php';
$config['db_name']='grocery_test_'.bin2hex(random_bytes(6));
$server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
function release_check(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
function import_release_sql(PDO $pdo,string $sql): void {
    // These project-owned imports contain no procedures or semicolons in literals.
    foreach(explode(';',$sql) as $statement) {
        if(trim($statement)!=='') { $s=$pdo->query($statement); if($s) $s->closeCursor(); }
    }
}
try {
    $fresh=file_get_contents(dirname(__DIR__).'/releases/mini_grocery_fresh.sql');
    $fresh=str_replace('`mini_grocery`','`'.$config['db_name'].'`',$fresh);
    if(str_contains($fresh,'`mini_grocery`')||!preg_match('/^grocery_test_[a-f0-9]{12}$/D',$config['db_name'])) throw new RuntimeException('Unsafe test database name.');
    import_release_sql($server,$fresh);
    release_check((int)query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?',[$config['db_name']])->fetchColumn()===16,'Fresh phpMyAdmin SQL creates 16 tables');
    release_check((int)query('SELECT COUNT(*) FROM users')->fetchColumn()===0,'Fresh SQL contains no accounts or password hashes');
    $id=create_initial_owner('release_owner','Release Test Owner','ReleaseTestPassword!2026');
    $owner=one('SELECT * FROM users WHERE id=?',[$id]);
    release_check($owner['username']==='release_owner'&&password_verify('ReleaseTestPassword!2026',$owner['password_hash']),'First-owner setup works after SQL import');
    $blocked=false;
    try { create_initial_owner('extra_owner','Extra Owner','ReleaseTestPassword!2026'); } catch(DomainException $e) { $blocked=true; }
    release_check($blocked&&(int)query('SELECT COUNT(*) FROM users')->fetchColumn()===1,'First-owner setup cannot add accounts to an existing installation');
    // Model an earlier 15-table installation while retaining its existing owner.
    db()->exec('DROP TABLE account_invitations');
    db()->exec('ALTER TABLE users DROP COLUMN email_verified_at, DROP COLUMN email');
    $update=file_get_contents(dirname(__DIR__).'/releases/mini_grocery_update_002_email_otp.sql');
    import_release_sql(db(),$update);
    import_release_sql(db(),$update);
    release_check((int)query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?',[$config['db_name']])->fetchColumn()===16,'Update SQL adds email invitations and is safe to rerun');
    release_check(query('SELECT password_hash FROM users WHERE id=?',[$id])->fetchColumn()===$owner['password_hash'],'Update SQL preserves the existing owner and password');
    echo "Release database checks passed. Only an isolated temporary database was used.\n";
} finally {
    $server->exec('DROP DATABASE IF EXISTS `'.$config['db_name'].'`');
}
