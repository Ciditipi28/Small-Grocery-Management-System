<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/masters.php';
if((int)query('SELECT COUNT(*) FROM products')->fetchColumn()>0) { fwrite(STDERR,"Demo seeding requires an empty product catalogue. Nothing changed.\n"); exit(1); }
$owner=one('SELECT u.*,r.role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_name="Owner" AND u.is_active=1 LIMIT 1');
if(!$owner) throw new RuntimeException('Install an owner account first.');
save_master($owner,'suppliers',['supplier_name'=>'DEMO · Example supplier','is_active'=>1]); $supplier=(int)db()->lastInsertId();
// Retrieve by name because the audit insert also has its own auto-increment ID.
$supplier=(int)query('SELECT id FROM suppliers WHERE supplier_name=? ORDER BY id DESC LIMIT 1',['DEMO · Example supplier'])->fetchColumn();
save_master($owner,'products',['category_id'=>2,'unit_id'=>2,'sku'=>'DEMO-WATER','product_name'=>'DEMO · Bottled water','selling_price'=>'25.00','reorder_level'=>'5','is_active'=>1]);
$product=(int)query('SELECT id FROM products WHERE sku="DEMO-WATER"')->fetchColumn();
$make=fn($extra=[])=>$extra+['request_key'=>bin2hex(random_bytes(16)),'product_id'=>[$product],'quantity'=>[1]];
post_document($owner,'adjustment',$make(['quantity'=>[10],'reason'=>'DEMO opening stock']));
post_document($owner,'purchase',$make(['quantity'=>[8],'unit_cost'=>['18.50'],'supplier_id'=>$supplier,'notes'=>'DEMO delivery']));
$sale=post_document($owner,'sale',$make(['quantity'=>[3],'cash_tendered'=>'100.00']));
$line=(int)query('SELECT id FROM sale_items WHERE sale_id=?',[$sale])->fetchColumn();
post_document($owner,'return',$make(['sale_id'=>$sale,'product_id'=>[$line],'quantity'=>[1],'restock_quantity'=>[1],'reason'=>'DEMO saleable return']));
post_document($owner,'adjustment',$make(['quantity'=>[-2],'reason'=>'DEMO damaged stock']));
echo "Clearly labeled demo records created. Expected stock: 14; net cash takings: 50.00.\n";
