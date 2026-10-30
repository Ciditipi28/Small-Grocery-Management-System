<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/masters.php';
$config['db_name']='grocery_test_'.bin2hex(random_bytes(6));
$server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
function expect(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
function rejects(callable $fn,string $message): void { try { $fn(); } catch(DomainException $e) { expect(true,$message); return; } throw new RuntimeException('Expected rejection: '.$message); }
function doc(array $extra=[]): array { return $extra+['request_key'=>bin2hex(random_bytes(16)),'product_id'=>[1],'quantity'=>[1]]; }
try {
 $server->exec('CREATE DATABASE `'.$config['db_name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 db()->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
 query('INSERT INTO users(role_id,full_name,username,password_hash) VALUES(1,?,?,?),(2,?,?,?)',['Test Owner','owner',password_hash('integration-password',PASSWORD_DEFAULT),'Test Cashier','cashier',password_hash('integration-password',PASSWORD_DEFAULT)]);
 $owner=one('SELECT *,"Owner" role_name FROM users WHERE id=1'); $cashier=one('SELECT *,"Cashier" role_name FROM users WHERE id=2');
 save_master($owner,'suppliers',['supplier_name'=>'Test supplier','is_active'=>1]);
 save_master($owner,'products',['category_id'=>1,'unit_id'=>2,'sku'=>'BOTTLE','product_name'=>'Test bottle','selling_price'=>'25.00','reorder_level'=>'5','is_active'=>1]);
 expect((int)query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?',[$config['db_name']])->fetchColumn()===16,'16 relational tables including email invitations');
 post_document($owner,'adjustment',doc(['quantity'=>[10],'reason'=>'Opening stock']));
 post_document($owner,'purchase',doc(['quantity'=>[8],'supplier_id'=>1,'unit_cost'=>['18.50']]));
 $data=doc(['quantity'=>[3],'cash_tendered'=>'100.00']); $sale=post_document($cashier,'sale',$data);
 expect(post_document($cashier,'sale',$data)===$sale,'Repeated post returns the same document');
 $s=one('SELECT * FROM sales WHERE id=?',[$sale]); expect($s['total_amount']==='75.00'&&decimal(cents($s['cash_tendered'])-cents($s['total_amount']))==='25.00','Sale total and change');
 $line=(int)query('SELECT id FROM sale_items WHERE sale_id=?',[$sale])->fetchColumn();
 post_document($owner,'return',doc(['sale_id'=>$sale,'product_id'=>[$line],'quantity'=>[1],'restock_quantity'=>[1],'reason'=>'Customer return']));
 post_document($owner,'adjustment',doc(['quantity'=>[-2],'reason'=>'Damaged goods']));
 expect((int)query('SELECT stock_qty FROM products WHERE id=1')->fetchColumn()===14,'Inventory example equals 14');
 expect(decimal(cents(query('SELECT SUM(total_amount) FROM sales')->fetchColumn())-cents(query('SELECT SUM(total_amount) FROM sales_returns')->fetchColumn()))==='50.00','Net cash takings equals 50.00');
 $before=(int)query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
 rejects(fn()=>post_document($cashier,'sale',doc(['quantity'=>[15],'cash_tendered'=>'500'])),'Overselling blocked');
 rejects(fn()=>post_document($cashier,'sale',doc(['quantity'=>[1],'cash_tendered'=>'1'])),'Insufficient tender blocked');
 expect((int)query('SELECT COUNT(*) FROM activity_logs')->fetchColumn()===$before&&(int)query('SELECT stock_qty FROM products WHERE id=1')->fetchColumn()===14,'Failed posting rolls back stock and audit');
 db()->exec("CREATE TRIGGER test_audit_failure BEFORE INSERT ON activity_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected audit failure'");
 try { post_document($cashier,'sale',doc(['quantity'=>[1],'cash_tendered'=>'100'])); throw new RuntimeException('Audit failure not raised'); } catch(PDOException $e) { /* deliberate database failure after header, lines and stock writes */ }
 db()->exec('DROP TRIGGER test_audit_failure');
 expect((int)query('SELECT stock_qty FROM products WHERE id=1')->fetchColumn()===14&&(int)query('SELECT COUNT(*) FROM sales')->fetchColumn()===1,'Late database failure rolls back header, lines and stock');
 rejects(fn()=>post_document($owner,'return',doc(['sale_id'=>$sale,'product_id'=>[$line],'quantity'=>[3],'restock_quantity'=>[0],'reason'=>'Too many'])),'Excess cumulative return blocked');
 rejects(fn()=>post_document($owner,'return',doc(['sale_id'=>$sale,'product_id'=>[$line],'quantity'=>[1],'restock_quantity'=>[2],'reason'=>'Invalid'])),'Excess restock blocked');
 rejects(fn()=>post_document($cashier,'adjustment',doc(['reason'=>'Unauthorized'])),'Cashier cannot adjust stock');
 rejects(fn()=>save_master($cashier,'suppliers',['supplier_name'=>'Unauthorized']),'Cashier cannot maintain master records');
 rejects(fn()=>post_document($cashier,'sale',doc(['product_id'=>[1,1],'quantity'=>[1,1],'cash_tendered'=>'100'])),'Duplicate product lines blocked');
 query('UPDATE products SET selling_price=30 WHERE id=1');
 expect(query('SELECT unit_price FROM sale_items WHERE id=?',[$line])->fetchColumn()==='25.00','Historical price preserved');
 post_document($owner,'return',doc(['sale_id'=>$sale,'product_id'=>[$line],'quantity'=>[1],'restock_quantity'=>[0],'reason'=>'Damaged return']));
 expect((int)query('SELECT stock_qty FROM products WHERE id=1')->fetchColumn()===14,'Damaged return does not restore stock');
 query('UPDATE products SET is_active=0 WHERE id=1');
 rejects(fn()=>post_document($cashier,'sale',doc(['cash_tendered'=>'100'])),'Inactive products cannot be sold');
 expect(cents('0.01')===1&&cents('25.10')===2510,'Money parsed in integer cents');
 rejects(fn()=>cents('1.001'),'Fractional cents rejected');
 rejects(fn()=>quantity('1.5'),'Fractional quantities rejected');
 echo "All integration checks passed.\n";
} finally { $server->exec('DROP DATABASE IF EXISTS `'.$config['db_name'].'`'); }
