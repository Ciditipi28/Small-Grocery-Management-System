<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
require __DIR__.'/hosting-helpers.php';
try {
    echo "Create a PRIVATE first-owner SQL file for a new hosting database.\n";
    $username=trim(readline('Live owner username: '));
    $name=trim(readline('Live owner full name: '));
    $password=getenv('GROCERY_INSTALL_PASSWORD') ?: readline('Live owner password (12-72 characters; visible while typing): ');
    $sql=first_owner_import_sql($username,$name,$password);
    $folder=dirname(__DIR__).'/var/deployment';
    if(!is_dir($folder)&&!mkdir($folder,0700,true)) throw new RuntimeException('Cannot create private export directory.');
    $path=$folder.'/first-owner.sql';
    if(is_file($path)) throw new RuntimeException('A private first-owner export already exists. Rename it locally before creating another.');
    if(file_put_contents($path,$sql,LOCK_EX)===false) throw new RuntimeException('Cannot write private SQL file.');
    echo "Saved: $path\nImport through the hosting phpMyAdmin, not the public web folder. This file is excluded from Git and release ZIPs.\n";
} catch(Throwable $e) { fwrite(STDERR,'Export failed: '.$e->getMessage()."\n"); exit(1); }
