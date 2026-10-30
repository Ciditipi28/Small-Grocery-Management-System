<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
function create_initial_owner(string $username,string $name,string $password): int {
    if(!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D',$username)||trim($name)===''||mb_strlen($name)>100||strlen($password)<12||strlen($password)>72) throw new DomainException('Use a valid username, name, and password of 12-72 characters.');
    return atomic(function() use($username,$name,$password) {
        $role=one('SELECT id FROM roles WHERE role_name=? FOR UPDATE',['Owner']);
        if(!$role) throw new DomainException('Import the fresh database schema first.');
        if((int)query('SELECT COUNT(*) FROM users')->fetchColumn()>0) throw new DomainException('Accounts already exist. First-owner setup cannot create additional accounts.');
        query('INSERT INTO users (role_id,full_name,username,password_hash) VALUES (?,?,?,?)',[$role['id'],trim($name),$username,password_hash($password,PASSWORD_DEFAULT)]);
        $id=(int)db()->lastInsertId();
        audit($id,'BOOTSTRAP','users',$id,'First owner created through local CLI setup');
        return $id;
    });
}
