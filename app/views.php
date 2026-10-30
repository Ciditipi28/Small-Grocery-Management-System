<?php
require_once __DIR__.'/ui.php';
function page_title(string $p): string { return (masters()[$p][0]??null) ?: (['dashboard'=>'Overview','history'=>document_types()[$_GET['type']??'sale'][3]??'History','new'=>document_types()[$_GET['type']??'sale'][4]??'New Transaction','detail'=>document_types()[$_GET['type']??'sale'][5]??'Details','stock'=>'Stock on Hand','movements'=>'Stock Movement History','reports'=>'Reports','activity'=>'Activity History'][$p]??'Page not found'); }
function login_view(?string $error): void {
 global $config;
 ?><!doctype html><html lang="en"><head><?php ui_head('Sign in'); ?></head><body class="auth-page">
 <?php auth_header(); ?>
 <main class="auth-layout">
  <section class="auth-aside" aria-label="Store information">
   <span class="auth-store-caption">THE STORE WORKSPACE</span>
   <h2 class="auth-store-name"><?=e($config['store_name'])?></h2>
   <?php shelf_illustration(); ?>
   <div class="auth-features"><span><?=ui_icon('receipt')?>Sales</span><span><?=ui_icon('box')?>Inventory</span><span><?=ui_icon('truck')?>Receiving</span></div>
   <p class="auth-aside-note">Your daily records, from the first delivery to the last receipt.</p>
  </section>
  <section class="auth-form-area"><div class="auth-form-card">
   <p class="eyebrow">STAFF ACCESS</p><h1>Sign in to your store</h1><p class="muted">Use your staff account to continue.</p>
   <?php registration_notice(); if($error) echo '<div class="alert error" role="alert">'.e($error).'</div>'; ?>
   <form method="post" action="index.php">
    <?=csrf()?><input type="hidden" name="action" value="login">
    <label for="login-username">Username<input id="login-username" name="username" required maxlength="60" autocomplete="username" placeholder="Enter your username" value="<?=e($_POST['username']??'')?>"></label>
    <?php password_field('password','Password','login-password','current-password'); ?>
    <button class="button wide" type="submit">Sign in <?=ui_icon('arrow')?></button>
   </form>
   <div class="auth-form-footer"><span>Invited to the team?</span><a href="<?=e(url('register'))?>">Create your account <?=ui_icon('arrow')?></a></div>
  </div></section>
 </main><footer class="auth-bottom">Grocery <span>Inventory &amp; sales</span></footer></body></html><?php
}
function page_description(string $page): string {
 return [
  'dashboard'=>'A look at today’s sales and the stock that needs attention.',
  'products'=>'Keep your catalogue, prices, and reorder levels up to date.',
  'product_categories'=>'Organize products into useful groups.', 'units'=>'Set the units used to sell and count your products.',
  'suppliers'=>'Your delivery sources and contact information.',
  'stock'=>'Current quantities, selling prices, and stock status.',
  'movements'=>'Follow every delivery, sale, return, and adjustment.',
  'reports'=>'Review your sales, refunds, and receiving for a selected period.',
  'users'=>'Invite staff and manage access to your store.',
  'activity'=>'A record of account changes and posted transactions.',
  'history'=>'Find and review your store’s posted transactions.',
  'new'=>'Enter the details below and review them before posting.',
  'detail'=>'Your saved transaction and its item details.',
  'register'=>'Manage access to your store.',
 ][$page]??'';
}
function layout_start(string $title,array $u): void {
 global $config;
 $page=(string)($_GET['page']??'dashboard'); $type=(string)($_GET['type']??'sale');
 $groups=[
  'Workspace'=>[['dashboard','Overview','overview',[]],['new','Checkout','cart',['type'=>'sale']],['history','Sales','receipt',['type'=>'sale']]],
  'Inventory'=>[], 'Catalogue'=>[], 'Management'=>[],
 ];
 if(owner($u)) $groups['Inventory'][]=['products','Products','box',[]];
 $groups['Inventory'][]=['stock','Stock on hand','stock',[]];
 $groups['Inventory'][]=['movements','Movements','movement',[]];
 if(owner($u)) {
  $groups['Inventory']=array_merge($groups['Inventory'],[['history','Receiving','truck',['type'=>'purchase']],['history','Returns','return',['type'=>'return']],['history','Adjustments','adjust',['type'=>'adjustment']]]);
  $groups['Catalogue']=[['product_categories','Categories','tag',[]],['units','Units','ruler',[]],['suppliers','Suppliers','supplier',[]]];
  $groups['Management']=[['reports','Reports','chart',[]],['users','Staff accounts','users',[]],['activity','Activity history','activity',[]]];
 }
 $parts=preg_split('/\s+/u',trim($u['full_name'])); $initials=mb_strtoupper(mb_substr($parts[0]??'',0,1).(count($parts)>1?mb_substr(end($parts),0,1):''));
 ?><!doctype html><html lang="en"><head><?php ui_head($title); ?></head><body class="app-shell" data-page="<?=e($page)?>">
 <a class="skip-link" href="#main-content">Skip to content</a>
 <aside class="sidebar" id="app-sidebar" aria-label="Store navigation">
  <a class="sidebar-brand" href="<?=e(url('dashboard'))?>"><span class="brand-mark"><?=ui_icon('store')?></span><span class="brand-copy">Grocery<span>Inventory &amp; sales</span></span></a>
  <button class="sidebar-close icon-button" type="button" data-nav-close aria-label="Close navigation"><?=ui_icon('close')?></button>
  <div class="store-switcher"><span class="store-dot" aria-hidden="true"></span><span><?=e($config['store_name'])?></span></div>
  <nav aria-label="Main navigation">
   <?php foreach($groups as $group=>$links) { if(!$links) continue; echo '<div class="nav-group"><p class="nav-label">'.e($group).'</p>';
    foreach($links as [$target,$label,$icon,$params]) {
     $active=$page===$target&&(!isset($params['type'])||$type===$params['type']);
     if(in_array($page,['detail','new'],true)&&$target==='history'&&isset($params['type'])&&$params['type']===$type&&($type!=='sale'||$page==='detail')) $active=true;
     echo '<a '.($active?'class="active" aria-current="page" ':'').'href="'.e(url($target,$params)).'"><span class="nav-icon">'.ui_icon($icon).'</span><span class="nav-text">'.e($label).'</span></a>';
    } echo '</div>';
   } ?>
  </nav>
  <div class="account"><span class="avatar" aria-hidden="true"><?=e($initials)?></span><div class="account-copy"><strong><?=e($u['full_name'])?></strong><small><?=e($u['role_name'])?></small></div><form class="account-actions" method="post" action="index.php"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="icon-button" aria-label="Sign out" title="Sign out"><?=ui_icon('logout')?></button></form></div>
 </aside>
 <button class="sidebar-backdrop" type="button" data-nav-close aria-label="Close navigation" hidden></button>
 <main class="workspace" id="main-content" tabindex="-1"><header class="topbar"><div class="topbar-left"><button class="menu-toggle icon-button" type="button" data-menu-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Open navigation"><?=ui_icon('menu')?></button><div class="breadcrumb"><span>Workspace</span><?=ui_icon('chevron')?><strong><?=e($title)?></strong></div></div><div class="topbar-right"><time datetime="<?=e(date('Y-m-d'))?>"><?=e(date('D, j M Y'))?></time><?php theme_toggle(); ?></div></header>
 <div class="content"><div class="page-heading"><div><h1><?=e($title)?></h1><p class="page-description"><?=e(page_description($page))?></p></div><div class="heading-actions"><?php if(!($page==='new'&&$type==='sale')) { ?><a class="button" href="<?=e(url('new',['type'=>'sale']))?>"><?=ui_icon('plus')?> New sale</a><?php } ?></div></div><?php
}
function layout_end(): void { echo '</div><footer><span>Grocery</span><span>Inventory &amp; sales</span></footer></main></body></html>'; }
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
 $isOwner=owner($u); $scope=$isOwner?'':' AND created_by='.(int)$u['id']; $today=date('Y-m-d'); $tomorrow=date('Y-m-d',strtotime('+1 day'));
 $sales=one("SELECT COALESCE(SUM(total_amount),0) amount,COUNT(*) count FROM sales WHERE posted_at>=? AND posted_at<? $scope",[$today,$tomorrow]);
 $refund=$isOwner?query('SELECT COALESCE(SUM(total_amount),0) FROM sales_returns WHERE posted_at>=? AND posted_at<?',[$today,$tomorrow])->fetchColumn():'0.00';
 $low=(int)query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_qty<=reorder_level')->fetchColumn();
 $out=(int)query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_qty=0')->fetchColumn();
 $cards=[['Sales today',money($sales['amount']),'Before refunds','receipt',false]];
 if($isOwner) { $cards[]=['Refunds today',money($refund),'Cash refunds posted today','return',false]; $cards[]=['Net cash takings',money(decimal(cents($sales['amount'])-cents($refund))),'Sales less refunds · not profit','chart',true]; }
 $cards[]=['Completed sales',e($sales['count']),$isOwner?'Receipts posted today':'Your receipts today','cart',false];
 $cards[]=['Stock alerts',e($low),$out.' out of stock','box',false];
 echo '<section class="cards" aria-label="Today’s summary">';
 foreach($cards as [$label,$value,$note,$icon,$featured]) echo '<article class="stat'.($featured?' stat-featured':'').'"><div class="stat-label"><span>'.e($label).'</span><span class="stat-icon">'.ui_icon($icon).'</span></div><strong class="stat-value">'.$value.'</strong><small class="stat-note">'.e($note).'</small></article>';
 echo '</section>';
 $start=date('Y-m-d',strtotime('-6 days'));
 $dailySales=[]; foreach(rows("SELECT DATE(posted_at) day,SUM(total_amount) amount FROM sales WHERE posted_at>=? AND posted_at<? $scope GROUP BY DATE(posted_at)",[$start,$tomorrow]) as $r) $dailySales[$r['day']]=(float)$r['amount'];
 $dailyRefunds=[]; if($isOwner) foreach(rows('SELECT DATE(posted_at) day,SUM(total_amount) amount FROM sales_returns WHERE posted_at>=? AND posted_at<? GROUP BY DATE(posted_at)',[$start,$tomorrow]) as $r) $dailyRefunds[$r['day']]=(float)$r['amount'];
 $days=[]; $max=0;
 for($i=6;$i>=0;$i--) { $day=date('Y-m-d',strtotime("-$i days")); $s=$dailySales[$day]??0; $r=$dailyRefunds[$day]??0; $days[]=[$day,$s,$r]; $max=max($max,$s,$r); }
 $scale=max(100,ceil($max/100)*100);
 echo '<div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><h2>Sales overview</h2><p class="section-caption">'.e(date('j M',strtotime($start))).' – '.e(date('j M',strtotime($today))).($isOwner?' · last 7 days':' · your last 7 days').'</p></div><div class="chart-legend"><span><i class="legend-dot" aria-hidden="true"></i>Sales</span>'.($isOwner?'<span><i class="legend-dot refunds" aria-hidden="true"></i>Refunds</span>':'').'</div></div>';
 echo '<div class="trend-chart"><svg viewBox="0 0 760 245" role="img" aria-labelledby="sales-chart-title sales-chart-description"><title id="sales-chart-title">Sales over the last seven days</title><desc id="sales-chart-description">Daily sales'.($isOwner?' and refunds':'').' on a shared scale. Exact figures are available in the table below.</desc>';
 for($i=0;$i<=4;$i++) { $y=25+$i*40; echo '<path class="chart-grid" d="M64 '.$y.'H744"/><text class="chart-label" x="52" y="'.($y+4).'" text-anchor="end">'.e(number_format($scale*(4-$i)/4,0)).'</text>'; }
 foreach($days as $i=>[$day,$s,$r]) {
  $x=86+$i*96; $sh=160*$s/$scale; $rh=160*$r/$scale;
  echo '<g><title>'.e(date('D, j M',strtotime($day)).': sales '.number_format($s,2).($isOwner?', refunds '.number_format($r,2):'')).'</title><rect class="sales-bar" x="'.$x.'" y="'.(185-$sh).'" width="'.($isOwner?22:32).'" height="'.$sh.'" rx="3"/>';
  if($isOwner) echo '<rect class="refund-bar" x="'.($x+28).'" y="'.(185-$rh).'" width="22" height="'.$rh.'" rx="3"/>';
  echo '<text class="chart-label" x="'.($x+($isOwner?25:16)).'" y="211" text-anchor="middle">'.e(date('D',strtotime($day))).'</text></g>';
 }
 echo '</svg></div><details class="chart-data"><summary>View daily figures</summary>';
 table_view($isOwner?['Date','Sales','Refunds']:['Date','Sales'],array_map(fn($d)=>$isOwner?[e(date('D, j M',strtotime($d[0]))),money($d[1]),money($d[2])]:[e(date('D, j M',strtotime($d[0]))),money($d[1])],$days)); echo '</details></section>';
 echo '<section class="panel"><div class="panel-heading"><div><h2>Stock to watch</h2><p class="section-caption">Products at or below their reorder level</p></div><span class="badge'.($low?' badge-warning':' badge-neutral').'">'.$low.'</span></div>';
 $attention=rows('SELECT * FROM products WHERE is_active=1 AND stock_qty<=reorder_level ORDER BY stock_qty,product_name LIMIT 5');
 if($attention) table_view(['Product','On hand'],array_map(fn($p)=>['<a href="'.e(url('movements',['product_id'=>$p['id']])).'">'.e($p['product_name']).'</a>','<span class="badge '.($p['stock_qty']==0?'badge-danger':'badge-warning').'">'.e($p['stock_qty']).' left</span>'],$attention));
 else { $active=(int)query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn(); echo '<div class="state-empty">'.ui_icon('box').'<strong>'.($active?'Nothing running low':'No active products yet').'</strong><p>'.($active?'Your active products are above their reorder levels.':'Products will appear here when stock needs attention.').'</p></div>'; }
 echo '<a class="section-link" href="'.e(url('stock',['low'=>1])).'">View stock on hand '.ui_icon('arrow').'</a></section></div>';
 $recentScope=$isOwner?'':' AND h.created_by='.(int)$u['id'];
 $recent=rows("SELECT h.*,u.full_name FROM sales h JOIN users u ON u.id=h.created_by WHERE 1=1 $recentScope ORDER BY h.id DESC LIMIT 5");
 echo '<div class="'.($isOwner?'dashboard-grid':'').'"><section class="panel panel-table"><div class="panel-heading"><div><h2>Recent sales</h2><p class="section-caption">Your latest posted receipts</p></div><a href="'.e(url('history',['type'=>'sale'])).'">View all '.ui_icon('arrow').'</a></div>';
 table_view(['Receipt','Staff','Amount'],array_map(fn($r)=>['<a href="'.e(url('detail',['type'=>'sale','id'=>$r['id']])).'">'.e($r['document_no']).'</a>',e($r['full_name']),money($r['total_amount'])],$recent)); echo '</section>';
 if($isOwner) {
  echo '<section class="panel panel-table"><div class="panel-heading"><div><h2>Recent deliveries</h2><p class="section-caption">Goods received from suppliers</p></div><a href="'.e(url('history',['type'=>'purchase'])).'">View all '.ui_icon('arrow').'</a></div>';
  $receiving=rows('SELECT h.*,s.supplier_name FROM purchases h JOIN suppliers s ON s.id=h.supplier_id ORDER BY h.id DESC LIMIT 5');
  table_view(['Delivery','Supplier','Cost'],array_map(fn($r)=>['<a href="'.e(url('detail',['type'=>'purchase','id'=>$r['id']])).'">'.e($r['document_no']).'</a>',e($r['supplier_name']),money($r['total_amount'])],$receiving)); echo '</section>';
 }
 echo '</div>';
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
 filters(true); [$from,$to,$end]=date_range(); echo '<div class="toolbar report-toolbar"><button class="button secondary print-button">Print report</button></div><section class="panel"><div class="panel-heading"><div><h2>Operational summary</h2><p class="section-caption">'.e($from).' through '.e($to).'</p></div></div>';
 $s=query('SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE posted_at>=? AND posted_at<?',[$from,$end])->fetchColumn(); $r=query('SELECT COALESCE(SUM(total_amount),0) FROM sales_returns WHERE posted_at>=? AND posted_at<?',[$from,$end])->fetchColumn(); table_view(['Gross sales','Refunds paid','Net cash takings'],[[money($s),money($r),money(decimal(cents($s)-cents($r)))]]); echo '<p class="muted report-note">Net cash takings are not profit. Refunds follow their posting date.</p></section><section class="panel"><div class="panel-heading"><h2>Top-selling products · gross sales</h2></div>';
 $data=rows('SELECT i.sku_snapshot, i.product_name_snapshot,SUM(i.quantity) quantity,SUM(i.quantity*i.unit_price) amount FROM sale_items i JOIN sales h ON h.id=i.sale_id WHERE h.posted_at>=? AND h.posted_at<? GROUP BY i.product_id,i.sku_snapshot,i.product_name_snapshot ORDER BY quantity DESC LIMIT 20',[$from,$end]); table_view(['Code','Product','Units sold','Gross amount'],array_map(fn($r)=>[e($r['sku_snapshot']),e($r['product_name_snapshot']),e($r['quantity']),money($r['amount'])],$data));
 echo '</section><section class="panel"><div class="panel-heading"><h2>Received goods by supplier</h2></div>'; $data=rows('SELECT s.supplier_name,COUNT(*) deliveries,SUM(h.total_amount) amount FROM purchases h JOIN suppliers s ON s.id=h.supplier_id WHERE h.posted_at>=? AND h.posted_at<? GROUP BY s.id,s.supplier_name ORDER BY amount DESC',[$from,$end]); table_view(['Supplier','Deliveries','Received cost'],array_map(fn($r)=>[e($r['supplier_name']),e($r['deliveries']),money($r['amount'])],$data)); echo '<p class="muted report-note">For individual records, open Sales or Returns. Stock on hand and Movements show your inventory details.</p></section>';
}
function activity_page(): void {
 filters(true); [$from,$to,$end]=date_range(); $params=[$from,$end,'%'.($_GET['q']??'').'%']; $where='l.created_at>=? AND l.created_at<? AND l.description LIKE ?'; $count=(int)query("SELECT COUNT(*) FROM activity_logs l WHERE $where",$params)->fetchColumn(); [$limit,$off,$pg]=pager($count); $data=rows("SELECT l.*,u.full_name FROM activity_logs l JOIN users u ON u.id=l.user_id WHERE $where ORDER BY l.id DESC LIMIT $limit OFFSET $off",$params); table_view(['Date','Staff','Action','Record','Description'],array_map(fn($r)=>[e($r['created_at']),e($r['full_name']),e($r['action']),e($r['record_type'].' #'.$r['record_id']),e($r['description'])],$data)); page_links($count,$pg);
}


