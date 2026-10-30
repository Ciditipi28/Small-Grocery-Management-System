<?php
function page_title(string $p): string { return (masters()[$p][0]??null) ?: (['dashboard'=>'Overview','history'=>document_types()[$_GET['type']??'sale'][3]??'History','new'=>document_types()[$_GET['type']??'sale'][4]??'New Transaction','detail'=>document_types()[$_GET['type']??'sale'][5]??'Details','stock'=>'Stock on Hand','movements'=>'Stock Movement History','reports'=>'Reports','activity'=>'Activity History'][$p]??'Page not found'); }
function login_view(?string $error): void {
 global $config;
 ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · Grocery</title><link rel="stylesheet" href="assets/app.css"></head><body class="login"><main class="login-grid"><section class="welcome"><div class="brand">▦ GROCERY</div><p class="eyebrow">YOUR STORE, CONNECTED</p><h1>A clearer picture<br>of every shelf.</h1><p>Inventory, sales, and daily operations.<br>One place to keep your shop moving.</p><div class="welcome-note">Built for the everyday neighborhood store.</div></section><section class="login-card"><p class="eyebrow">WELCOME BACK</p><h2>Sign in to your store</h2><p class="muted"><?=e($config['store_name'])?></p><?php registration_notice(); if($error) echo '<div class="alert error" role="alert">'.e($error).'</div>'; ?><form method="post" action="index.php"><?=csrf()?><input type="hidden" name="action" value="login"><label>Username<input name="username" required maxlength="60" autocomplete="username" value="<?=e($_POST['username']??'')?>"></label><label>Password<input name="password" type="password" required autocomplete="current-password"></label><button class="button wide">Sign in →</button></form><small class="muted">New staff member? <a href="<?=e(url('register'))?>">Create an account with your email code</a>.</small></section></main></body></html><?php
}
function layout_start(string $title,array $u): void {
 global $config;
 $nav=[['dashboard','Overview'],['new','Checkout',['type'=>'sale']],['history','Sales',['type'=>'sale']],['stock','Stock on hand'],['movements','Stock movements']];
 if(owner($u)) $nav=array_merge($nav,[['products','Products'],['product_categories','Categories'],['units','Units'],['suppliers','Suppliers'],['history','Receiving',['type'=>'purchase']],['history','Returns',['type'=>'return']],['history','Adjustments',['type'=>'adjustment']],['reports','Reports'],['users','Staff accounts'],['activity','Activity history']]);
 ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · Grocery</title><link rel="stylesheet" href="assets/app.css"><script src="assets/app.js" defer></script></head><body><aside class="sidebar"><a class="brand" href="<?=url('dashboard')?>">▦ <span>GROCERY<small>STORE MANAGEMENT</small></span></a><div class="store-name"><?=e($config['store_name'])?></div><nav><?php foreach($nav as $n) { $active=($_GET['page']??'dashboard')===$n[0] && (!isset($n[2]) || ($_GET['type']??'sale')===$n[2]['type']); echo '<a '.($active?'class="active" ':'').'href="'.e(url($n[0],$n[2]??[])).'">'.e($n[1]).'</a>'; } ?></nav><div class="account"><strong><?=e($u['full_name'])?></strong><small><?=e($u['role_name'])?></small><form method="post" action="index.php"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="text-button">Sign out</button></form></div></aside><main class="workspace"><header class="topbar"><span>Workspace / <?=e($title)?></span><span><?=e(date('D, M j, Y'))?></span></header><div class="content"><div class="page-heading"><div><p class="eyebrow">NEIGHBORHOOD OPERATIONS</p><h1><?=e($title)?></h1></div><a class="button" href="<?=e(url('new',['type'=>'sale']))?>">＋ New sale</a></div><?php
}
function layout_end(): void { echo '</div><footer>Small Grocery Inventory and Sales System · One store, everything in view.</footer></main></body></html>'; }
function table_view(array $headers,array $data): void {
 echo '<div class="table-wrap"><table><thead><tr>'; foreach($headers as $h) echo '<th>'.e($h).'</th>'; echo '</tr></thead><tbody>';
 if(!$data) echo '<tr><td colspan="'.count($headers).'" class="empty">No records yet. Your saved records will appear here.</td></tr>';
 foreach($data as $row) { echo '<tr>'; foreach($row as $cell) echo '<td>'.$cell.'</td>'; echo '</tr>'; } echo '</tbody></table></div>';
}
function input_field(string $name,string $label,mixed $value='',string $type='text',bool $required=true,string $extra=''): void { echo '<label>'.e($label).'<input name="'.e($name).'" type="'.e($type).'" value="'.e($value).'" '.($required?'required ':'').$extra.'></label>'; }
function select_field(string $name,string $label,array $options,mixed $value=''): void { echo '<label>'.e($label).'<select name="'.e($name).'" required><option value="">Choose…</option>'; foreach($options as $o) echo '<option value="'.e($o['id']).'" '.(isset($o['price'])?'data-price="'.e($o['price']).'" ':'').((string)$value===(string)$o['id']?'selected':'').'>'.e($o['label']).'</option>'; echo '</select></label>'; }
function filters(bool $dates=false): void { echo '<form class="filters" method="get"><input type="hidden" name="page" value="'.e($_GET['page']??'dashboard').'">'; if(isset($_GET['type'])) echo '<input type="hidden" name="type" value="'.e($_GET['type']).'">'; input_field('q','Search',$_GET['q']??'','search',false); if($dates) { [$f,$t]=date_range(); input_field('from','From',$f,'date'); input_field('to','To',$t,'date'); } echo '<button class="button secondary">Apply filters</button></form>'; }
function pager(int $count,int $size=20): array { $page=max(1,(int)($_GET['p']??1)); return [$size,($page-1)*$size,$page]; }
function page_links(int $total,int $page): void { if($total<=20) return; echo '<div class="pagination">'; for($i=max(1,$page-2);$i<=min((int)ceil($total/20),$page+2);$i++) echo '<a class="button secondary" href="'.e('index.php?'.http_build_query(array_replace($_GET,['p'=>$i]))).'">'.$i.'</a>'; echo '</div>'; }
function render_page(string $p,array $u): void {
 if(isset(masters()[$p])) { require_owner($u); master_page($p,$u); return; }
 switch($p) {
  case 'register': echo '<section class="panel"><h2>You are already signed in</h2><p>Sign out before using an invitation to create another account. Owners can invite staff from Staff accounts.</p></section>'; break;
  case 'dashboard': dashboard($u); break;
  case 'new': transaction_form($u); break;
  case 'history': history_page($u); break;
  case 'detail': detail_page($u); break;
  case 'stock': stock_page($u); break;
  case 'movements': movements_page(); break;
  case 'reports': require_owner($u); reports_page(); break;
  case 'activity': require_owner($u); activity_page(); break;
  default: http_response_code(404); echo '<p>Page not found.</p>';
 }
}
function master_page(string $table,array $u): void {
 [$title,$fields]=masters()[$table]; $edit=(int)($_GET['edit']??0); $record=$edit?one("SELECT * FROM $table WHERE id=?",[$edit]):[];
 if($edit&&!$record) throw new DomainException('Record not found.');
 if($_SERVER['REQUEST_METHOD']==='POST') $record=$_POST;
 if($table==='users') invitations_panel();
 if($table!=='users'||$edit) {
 echo '<details class="panel" '.($edit||$_SERVER['REQUEST_METHOD']==='POST'?'open':'').'><summary>'.($edit?'Edit record':'＋ Add record').'</summary><form method="post" class="form-grid">'.csrf().'<input type="hidden" name="action" value="save_master"><input type="hidden" name="id" value="'.e($record['id']??0).'">';
 foreach($fields as $name=>$spec) {
  $v=$record[$name]??'';
  if(is_string($spec[1])&&!in_array($spec[1],['money','integer','password'],true)) { $target=$spec[1]; $label=$spec[2]; select_field($name,$spec[0],rows("SELECT id,$label AS label FROM $target".($target!=='roles'?' WHERE is_active=1':'')." ORDER BY $label"),$v); }
  else { $type=match($spec[1]) { 'money','integer'=>'number','password'=>'password', default=>'text' }; $extra=$type==='number'?'min="0" step="'.($spec[1]==='money'?'0.01':'1').'"':(is_int($spec[1])?'maxlength="'.$spec[1].'"':''); input_field($name,$spec[0],$type==='password'?'':$v,$type,$spec[1]==='password'?!$edit:($spec[2]??true),$extra); }
 }
 echo '<label class="checkbox"><input type="checkbox" name="is_active" data-confirm-off="Deactivate this record? Existing history will be preserved." value="1" '.(($record['is_active']??1)?'checked':'').'> Active</label><div><button class="button">Save record</button> <a href="'.e(url($table)).'">Cancel</a></div></form></details>';
 }
 filters(); $search='%'.trim((string)($_GET['q']??'')).'%'; $name=array_key_first($fields); $where="$name LIKE ?"; $params=[$search];
 if($table==='products') { $where='(sku LIKE ? OR product_name LIKE ?)'; $params=[$search,$search]; }
 if($table==='users') { $where='(full_name LIKE ? OR username LIKE ?)'; $params=[$search,$search]; }
 $count=(int)query("SELECT COUNT(*) FROM $table WHERE $where",$params)->fetchColumn(); [$limit,$off,$pg]=pager($count);
 $data=rows("SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT $limit OFFSET $off",$params); $headers=[]; foreach($fields as $f=>$s) if($s[1]!=='password') $headers[]=$s[0]; $headers[]='Status'; $headers[]='Action'; $display=[];
 foreach($data as $r) { $out=[]; foreach($fields as $f=>$s) { if($s[1]==='password') continue; $v=$r[$f]; if(is_string($s[1])&&!in_array($s[1],['money','integer'],true)) $v=query("SELECT {$s[2]} FROM {$s[1]} WHERE id=?",[$v])->fetchColumn(); $out[]=e($v); } $out[]='<span class="badge">'.($r['is_active']?'Active':'Inactive').'</span>'; $out[]='<a href="'.e(url($table,['edit'=>$r['id']])).'">Edit</a>'; $display[]=$out; }
 table_view($headers,$display); page_links($count,$pg);
}
function dashboard(array $u): void {
 $scope=owner($u)?'':' AND created_by='.(int)$u['id']; $today=date('Y-m-d');
 $sales=one("SELECT COALESCE(SUM(total_amount),0) amount, COUNT(*) count FROM sales WHERE posted_at>=? AND posted_at<? $scope",[$today,date('Y-m-d',strtotime('+1 day'))]);
 $refund=owner($u)?query('SELECT COALESCE(SUM(total_amount),0) FROM sales_returns WHERE posted_at>=? AND posted_at<?',[$today,date('Y-m-d',strtotime('+1 day'))])->fetchColumn():null;
 $low=query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_qty<=reorder_level')->fetchColumn();
 echo '<section class="hero"><div><p class="eyebrow">A GOOD DAY STARTS WITH A CLEAR VIEW</p><h2>Hello, '.e(explode(' ',$u['full_name'])[0]).'.</h2><p>Here’s what’s happening in your store today.</p></div><div class="hero-mark">▦</div></section><section class="cards">';
 $cards=[['Sales today',money($sales['amount'])],['Completed sales',e($sales['count'])],['Low / out of stock',e($low)]];
 if(owner($u)) { $cards[]=['Refunds today',money($refund)]; $cards[]=['Net cash takings',money(decimal(cents($sales['amount'])-cents($refund)))]; }
 foreach($cards as [$l,$v]) echo '<article class="stat"><span>'.e($l).'</span><strong>'.$v.'</strong><small>Based on saved records</small></article>'; echo '</section><div class="dashboard-grid"><section class="panel"><h2>Last seven days</h2><p class="muted">Daily gross sales'.(owner($u)?' and refunds':' · your sales').'</p><div class="chart">';
 for($i=6;$i>=0;$i--) { $day=date('Y-m-d',strtotime("-$i days")); $next=date('Y-m-d',strtotime($day.' +1 day')); $s=query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE posted_at>=? AND posted_at<? $scope",[$day,$next])->fetchColumn(); $r=owner($u)?query('SELECT COALESCE(SUM(total_amount),0) FROM sales_returns WHERE posted_at>=? AND posted_at<?',[$day,$next])->fetchColumn():0; echo '<div class="chart-row"><span>'.e(date('D',strtotime($day))).'</span><meter min="0" max="'.e(max(100,(float)$s,(float)$r)).'" value="'.e($s).'">'.e($s).'</meter><strong>'.money($s).'</strong>'.(owner($u)?'<small>Refund '.money($r).'</small>':'').'</div>'; } echo '</div></section><section class="panel"><h2>Stock needs attention</h2>';
 table_view(['Product','Available'],array_map(fn($p)=>[e($p['product_name']),'<span class="badge">'.e($p['stock_qty']).'</span>'],rows('SELECT * FROM products WHERE is_active=1 AND stock_qty<=reorder_level ORDER BY stock_qty LIMIT 6'))); echo '<a href="'.url('stock',['q'=>'','low'=>1]).'">View inventory →</a></section></div><section class="panel"><h2>Recent sales</h2>';
 $recent=rows("SELECT * FROM sales WHERE 1=1 $scope ORDER BY id DESC LIMIT 5"); table_view(['Receipt','Date','Total'],array_map(fn($r)=>['<a href="'.e(url('detail',['type'=>'sale','id'=>$r['id']])).'">'.e($r['document_no']).'</a>',e($r['posted_at']),money($r['total_amount'])],$recent)); echo '</section>';
}
function transaction_form(array $u): void {
 $type=(string)($_GET['type']??'sale'); if(!isset(document_types()[$type])) throw new DomainException('Unknown transaction.'); if($type!=='sale') require_owner($u);
 $data=$_SERVER['REQUEST_METHOD']==='POST'?$_POST:[]; $saleId=(int)($data['sale_id']??$_GET['sale_id']??0);
 if($type==='return'&&!$saleId) { echo '<section class="panel"><p>Open a completed sale from Sales History and choose “Return items”.</p><a class="button" href="'.url('history',['type'=>'sale']).'">Find original sale</a></section>'; return; }
 $options=rows('SELECT id, CONCAT(sku," · ",product_name," (",stock_qty," available)") label, selling_price price FROM products '.(in_array($type,['sale','purchase'])?'WHERE is_active=1':'').' ORDER BY product_name');
 if($type==='return') { $sale=one('SELECT * FROM sales WHERE id=?',[$saleId]); if(!$sale) throw new DomainException('Sale not found.'); echo '<p>Original receipt: '.e($sale['document_no']).'</p>'; $options=rows('SELECT i.id, CONCAT(i.sku_snapshot," · ",i.product_name_snapshot," · sold ",i.quantity) label FROM sale_items i WHERE sale_id=?',[$saleId]); }
 echo '<section class="panel"><p class="muted">Posting saves this transaction and updates inventory. Check every line before posting.</p><form method="post" id="transaction-form">'.csrf().'<input type="hidden" name="action" value="post_document"><input type="hidden" name="request_key" value="'.e($data['request_key']??bin2hex(random_bytes(16))).'"><div class="form-grid">';
 if($type==='purchase') { select_field('supplier_id','Supplier',rows('SELECT id,supplier_name label FROM suppliers WHERE is_active=1 ORDER BY supplier_name'),$data['supplier_id']??''); input_field('supplier_reference','Supplier reference',$data['supplier_reference']??'','text',false,'maxlength="100"'); }
 if($type==='return') echo '<input type="hidden" name="sale_id" value="'.$saleId.'">';
 if(in_array($type,['return','adjustment'])) input_field('reason','Reason',$data['reason']??'','text',true,'maxlength="100" placeholder="Opening stock, count correction, damaged goods…"');
 if(in_array($type,['purchase','adjustment'])) input_field('notes','Notes',$data['notes']??'','text',false,'maxlength="500"');
 echo '</div><div class="table-wrap"><table><thead><tr><th>Product'.($type==='return'?' / original sale line':'').'</th><th>Quantity'.($type==='adjustment'?' (+ / −)':'').'</th>'.($type==='purchase'?'<th>Unit cost</th>':'').($type==='return'?'<th>Saleable quantity</th>':'').'<th></th></tr></thead><tbody id="transaction-lines">';
 $count=max(1,count($data['product_id']??[]));
 for($i=0;$i<$count;$i++) { echo '<tr class="transaction-line"><td>'; select_field('product_id[]','Product',$options,$data['product_id'][$i]??''); echo '</td><td>'; input_field('quantity[]','Quantity',$data['quantity'][$i]??1,'number',true,'step="1" '.($type==='adjustment'?'':'min="1"')); echo '</td>'; if($type==='purchase') { echo '<td>'; input_field('unit_cost[]','Unit cost',$data['unit_cost'][$i]??'0.00','number',true,'min="0" step="0.01"'); echo '</td>'; } if($type==='return') { echo '<td>'; input_field('restock_quantity[]','Saleable',$data['restock_quantity'][$i]??0,'number',true,'min="0" step="1"'); echo '</td>'; } echo '<td><button type="button" class="text-button remove-line">Remove</button></td></tr>'; }
 echo '</tbody></table></div><button type="button" class="button secondary" id="add-line">＋ Add product</button>';
 if($type==='sale') { echo '<div class="checkout-total"><p>Prices are checked again when posting. Estimated total: <strong id="checkout-sum">0.00</strong>. Change: <strong id="checkout-change">0.00</strong>.</p>'; input_field('cash_tendered','Cash tendered',$data['cash_tendered']??'','number',true,'min="0" step="0.01"'); echo '</div>'; }
 echo '<div class="form-actions"><button class="button" data-confirm="Post this transaction and update inventory?">Post '.e($type).' →</button></div></form></section>';
}
function history_page(array $u): void {
 $type=(string)($_GET['type']??'sale'); if(!isset(document_types()[$type])) throw new DomainException('Unknown transaction.'); if($type!=='sale') require_owner($u); [$t]=document_types()[$type];
 echo '<div class="toolbar"><a class="button" href="'.e(url('new',['type'=>$type])).'">＋ '.e(document_types()[$type][4]).'</a></div>'; filters(true); [$from,$to,$end]=date_range(); $params=[$from,$end,'%'.($_GET['q']??'').'%']; $where='h.posted_at>=? AND h.posted_at<? AND h.document_no LIKE ?'; if(!owner($u)) { $where.=' AND h.created_by=?'; $params[]=$u['id']; }
 $count=(int)query("SELECT COUNT(*) FROM $t h WHERE $where",$params)->fetchColumn(); [$limit,$off,$pg]=pager($count);
 $data=rows("SELECT h.*,u.full_name FROM $t h JOIN users u ON u.id=h.created_by WHERE $where ORDER BY h.id DESC LIMIT $limit OFFSET $off",$params);
 table_view(['Document','Posted','Staff',$type==='adjustment'?'Reason':'Amount','Status'],array_map(fn($r)=>['<a href="'.e(url('detail',['type'=>$type,'id'=>$r['id']])).'">'.e($r['document_no']).'</a>',e($r['posted_at']),e($r['full_name']),$type==='adjustment'?e($r['reason']):money($r['total_amount']),'<span class="badge">Posted</span>'],$data)); page_links($count,$pg);
}
function detail_page(array $u): void {
 $type=(string)($_GET['type']??'sale'); if(!isset(document_types()[$type])) throw new DomainException('Unknown transaction.'); if($type!=='sale') require_owner($u); [$t,$items,$fk]=document_types()[$type]; $h=one("SELECT h.*,u.full_name FROM $t h JOIN users u ON u.id=h.created_by WHERE h.id=?",[(int)($_GET['id']??0)]);
 if(!$h||(!owner($u)&&(int)$h['created_by']!==(int)$u['id'])) throw new DomainException('Transaction not available.');
 echo '<section class="panel receipt"><div class="receipt-heading"><div><p class="eyebrow">POSTED TRANSACTION</p><h2>'.e($h['document_no']).'</h2><p>'.e($h['posted_at']).' · '.e($h['full_name']).'</p></div><button type="button" class="button secondary print-button">Print</button></div>';
 if($type==='purchase') { $s=one('SELECT supplier_name FROM suppliers WHERE id=?',[$h['supplier_id']]); echo '<p>Supplier: '.e($s['supplier_name']).' · Reference: '.e($h['supplier_reference']).'</p>'; }
 if($type==='return') echo '<p>Original sale: <a href="'.e(url('detail',['type'=>'sale','id'=>$h['sale_id']])).'">View receipt</a></p>';
 if(isset($h['reason'])) echo '<p>Reason: '.e($h['reason']).'</p>'; if(!empty($h['notes'])) echo '<p>Notes: '.e($h['notes']).'</p>';
 if($type==='return') $data=rows('SELECT i.*,s.product_name_snapshot product_name,s.unit_price price FROM sales_return_items i JOIN sale_items s ON s.id=i.sale_item_id WHERE i.return_id=?',[$h['id']]);
 else $data=rows("SELECT i.*,".($type==='sale'?'i.product_name_snapshot':'p.product_name')." product_name FROM $items i JOIN products p ON p.id=i.product_id WHERE i.$fk=?",[$h['id']]);
 $headers=['Product',$type==='adjustment'?'Quantity change':'Quantity']; if($type!=='adjustment') $headers=array_merge($headers,['Unit amount','Line total']); if($type==='return') $headers[]='Restocked'; $display=[];
 foreach($data as $r) { $row=[e($r['product_name']),e($r[$type==='adjustment'?'quantity_change':'quantity'])]; if($type!=='adjustment') { $price=$r[$type==='purchase'?'unit_cost':($type==='return'?'price':'unit_price')]; $row[]=money($price); $row[]=money(decimal(cents($price)*(int)$r['quantity'])); } if($type==='return') $row[]=e($r['restock_quantity']); $display[]=$row; } table_view($headers,$display);
 if(isset($h['total_amount'])) echo '<h2 class="total">'.($type==='return'?'Refund':'Total').': '.money($h['total_amount']).'</h2>';
 if($type==='sale') { echo '<p class="total">Cash: '.money($h['cash_tendered']).' · Change: '.money(decimal(cents($h['cash_tendered'])-cents($h['total_amount']))).'</p>'; if(owner($u)) echo '<a class="button secondary" href="'.e(url('new',['type'=>'return','sale_id'=>$h['id']])).'">Return items</a>'; }
 echo '</section>';
}
function stock_page(array $u): void {
 filters(); echo '<p><a href="'.url('stock',['low'=>1]).'">Low stock only</a> · <a href="'.url('stock').'">All products</a></p>'; $q='%'.($_GET['q']??'').'%'; $where='(p.product_name LIKE ? OR p.sku LIKE ?)'.(!empty($_GET['low'])?' AND p.is_active=1 AND p.stock_qty<=p.reorder_level':''); $count=(int)query("SELECT COUNT(*) FROM products p WHERE $where",[$q,$q])->fetchColumn(); [$limit,$off,$pg]=pager($count);
 $data=rows("SELECT p.*,u.abbreviation FROM products p JOIN units u ON u.id=p.unit_id WHERE $where ORDER BY p.product_name LIMIT $limit OFFSET $off",[$q,$q]); table_view(['Code','Product','Available','Reorder at','Selling price','Status'],array_map(fn($p)=>[e($p['sku']),'<a href="'.e(url('movements',['product_id'=>$p['id']])).'">'.e($p['product_name']).'</a>',e($p['stock_qty'].' '.$p['abbreviation']),e($p['reorder_level']),money($p['selling_price']),'<span class="badge">'.(!$p['is_active']?'Inactive':($p['stock_qty']==0?'Out of stock':($p['stock_qty']<=$p['reorder_level']?'Low stock':'In stock'))).'</span>'],$data)); page_links($count,$pg);
}
function movement_rows(int $id): array {
 return rows("SELECT h.posted_at,'Receiving' kind,h.document_no,i.quantity delta FROM purchases h JOIN purchase_items i ON i.purchase_id=h.id WHERE i.product_id=? UNION ALL SELECT h.posted_at,'Sale',h.document_no,-i.quantity FROM sales h JOIN sale_items i ON i.sale_id=h.id WHERE i.product_id=? UNION ALL SELECT h.posted_at,'Return',h.document_no,i.restock_quantity FROM sales_returns h JOIN sales_return_items i ON i.return_id=h.id JOIN sale_items s ON s.id=i.sale_item_id WHERE s.product_id=? UNION ALL SELECT h.posted_at,'Adjustment',h.document_no,i.quantity_change FROM stock_adjustments h JOIN stock_adjustment_items i ON i.adjustment_id=h.id WHERE i.product_id=? ORDER BY posted_at,document_no",[$id,$id,$id,$id]);
}
function movements_page(): void {
 $id=(int)($_GET['product_id']??0); echo '<form class="filters"><input type="hidden" name="page" value="movements">'; select_field('product_id','Product',rows('SELECT id,CONCAT(sku," · ",product_name) label FROM products ORDER BY product_name'),$id); echo '<button class="button">View movements</button></form>';
 if(!$id) return; $p=one('SELECT * FROM products WHERE id=?',[$id]); if(!$p) throw new DomainException('Product not found.'); $data=movement_rows($id); $sum=array_sum(array_column($data,'delta')); echo '<div class="alert '.($sum===(int)$p['stock_qty']?'success':'error').'">Recorded movement balance: '.e($sum).' · Stock on hand: '.e($p['stock_qty']).'</div>'; table_view(['Date','Movement','Document','Quantity change'],array_map(fn($r)=>array_map('e',array_values($r)),$data));
}
function reports_page(): void {
 filters(true); [$from,$to,$end]=date_range(); echo '<button class="button secondary print-button">Print report</button><h2>Operational summary</h2><p>'.e($from).' through '.e($to).'</p>';
 $s=query('SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE posted_at>=? AND posted_at<?',[$from,$end])->fetchColumn(); $r=query('SELECT COALESCE(SUM(total_amount),0) FROM sales_returns WHERE posted_at>=? AND posted_at<?',[$from,$end])->fetchColumn(); table_view(['Gross sales','Refunds paid','Net cash takings'],[[money($s),money($r),money(decimal(cents($s)-cents($r)))]]); echo '<p class="muted">Net cash takings are not profit. Refunds follow their posting date.</p><h2>Top-selling products · gross sales</h2>';
 $data=rows('SELECT i.sku_snapshot, i.product_name_snapshot,SUM(i.quantity) quantity,SUM(i.quantity*i.unit_price) amount FROM sale_items i JOIN sales h ON h.id=i.sale_id WHERE h.posted_at>=? AND h.posted_at<? GROUP BY i.product_id,i.sku_snapshot,i.product_name_snapshot ORDER BY quantity DESC LIMIT 20',[$from,$end]); table_view(['Code','Product','Units sold','Gross amount'],array_map(fn($r)=>[e($r['sku_snapshot']),e($r['product_name_snapshot']),e($r['quantity']),money($r['amount'])],$data));
 echo '<h2>Received goods by supplier</h2>'; $data=rows('SELECT s.supplier_name,COUNT(*) deliveries,SUM(h.total_amount) amount FROM purchases h JOIN suppliers s ON s.id=h.supplier_id WHERE h.posted_at>=? AND h.posted_at<? GROUP BY s.id,s.supplier_name ORDER BY amount DESC',[$from,$end]); table_view(['Supplier','Deliveries','Received cost'],array_map(fn($r)=>[e($r['supplier_name']),e($r['deliveries']),money($r['amount'])],$data)); echo '<p>For receipt and refund details, use the date-filtered Sales and Returns histories. Current inventory and product movement reports are available in the sidebar.</p>';
}
function activity_page(): void {
 filters(true); [$from,$to,$end]=date_range(); $params=[$from,$end,'%'.($_GET['q']??'').'%']; $where='l.created_at>=? AND l.created_at<? AND l.description LIKE ?'; $count=(int)query("SELECT COUNT(*) FROM activity_logs l WHERE $where",$params)->fetchColumn(); [$limit,$off,$pg]=pager($count); $data=rows("SELECT l.*,u.full_name FROM activity_logs l JOIN users u ON u.id=l.user_id WHERE $where ORDER BY l.id DESC LIMIT $limit OFFSET $off",$params); table_view(['Date','Staff','Action','Record','Description'],array_map(fn($r)=>[e($r['created_at']),e($r['full_name']),e($r['action']),e($r['record_type'].' #'.$r['record_id']),e($r['description'])],$data)); page_links($count,$pg);
}

