<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if(($argv[1]??'')==='worker') {
 $config['db_name']=$argv[2]; $user=one('SELECT *,"Cashier" role_name FROM users WHERE id=?',[(int)$argv[3]]);
 try { post_document($user,'sale',['request_key'=>bin2hex(random_bytes(16)),'product_id'=>[1],'quantity'=>[1],'cash_tendered'=>'25.00']); echo 'SOLD'; }
 catch(DomainException $e) { echo 'REJECTED'; }
 exit;
}
$config['db_name']='grocery_test_'.bin2hex(random_bytes(6));
$server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try {
 $server->exec('CREATE DATABASE `'.$config['db_name'].'` CHARACTER SET utf8mb4');
 db()->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
 query('INSERT INTO users(role_id,full_name,username,password_hash) VALUES(2,"A","a","test"),(2,"B","b","test")');
 query('INSERT INTO products(category_id,unit_id,sku,product_name,selling_price,stock_qty) VALUES(1,1,"LAST","Last unit",25,1)');
 $processes=[];
 foreach([1,2] as $id) { $pipes=[]; $proc=proc_open([PHP_BINARY,__FILE__,'worker',$config['db_name'],(string)$id],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes); fclose($pipes[0]); $processes[]=[$proc,$pipes]; }
 $results=[]; foreach($processes as [$proc,$pipes]) { $results[]=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); if(proc_close($proc)!==0) throw new RuntimeException($err); }
 sort($results);
 if($results!==['REJECTED','SOLD']||(int)query('SELECT stock_qty FROM products WHERE id=1')->fetchColumn()!==0||(int)query('SELECT COUNT(*) FROM sales')->fetchColumn()!==1) throw new RuntimeException('Concurrent checkout failed.');
 echo "PASS Two different cashiers compete for the last unit: one sale, one rejection, zero remaining stock.\n";
} finally { $server->exec('DROP DATABASE IF EXISTS `'.$config['db_name'].'`'); }
