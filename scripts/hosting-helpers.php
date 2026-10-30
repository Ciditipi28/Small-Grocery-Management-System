<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
function first_owner_import_sql(string $username,string $name,string $password): string {
    if(!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D',$username)||trim($name)===''||mb_strlen($name)>100||strlen($password)<12||strlen($password)>72) throw new DomainException('Use a valid username, name, and password of 12-72 characters.');
    $literal=fn(string $v)=>'CONVERT(0x'.bin2hex($v).' USING utf8mb4)';
    $values=$literal(trim($name)).','.$literal($username).','.$literal(password_hash($password,PASSWORD_DEFAULT));
    return "-- PRIVATE FIRST-OWNER IMPORT. Select the LIVE grocery database in phpMyAdmin.\n"
        ."-- Import once after the hosting schema. Contains a password hash; keep private.\n"
        ."-- Does nothing when any account already exists. No existing records are changed.\n"
        ."START TRANSACTION;\nSELECT id INTO @grocery_owner_role_id FROM roles WHERE role_name='Owner' LIMIT 1 FOR UPDATE;\n"
        ."INSERT INTO users (role_id,full_name,username,password_hash) SELECT r.id,$values FROM roles r WHERE r.role_name='Owner' AND NOT EXISTS (SELECT 1 FROM users) LIMIT 1;\n"
        ."SET @grocery_owner_created=ROW_COUNT();\n"
        ."INSERT INTO activity_logs (user_id,action,record_type,record_id,description) SELECT id,'BOOTSTRAP','users',id,'First owner created through private hosting import' FROM users WHERE id=LAST_INSERT_ID() AND @grocery_owner_created=1;\nCOMMIT;\n";
}
