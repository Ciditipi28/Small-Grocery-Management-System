<?php
function masters(): array { return [
 'products'=>['Products',['sku'=>['Item code',50],'product_name'=>['Product name',150],'category_id'=>['Category','product_categories','category_name'],'unit_id'=>['Unit','units','unit_name'],'selling_price'=>['Selling price','money'],'reorder_level'=>['Reorder level','integer']]],
 'product_categories'=>['Categories',['category_name'=>['Category name',100]]],
 'units'=>['Units',['unit_name'=>['Unit name',60],'abbreviation'=>['Abbreviation',15]]],
 'suppliers'=>['Suppliers',['supplier_name'=>['Supplier name',150],'contact_person'=>['Contact person',100,false],'phone'=>['Phone',40,false],'address'=>['Address',250,false]]],
 'users'=>['Staff Accounts',['full_name'=>['Full name',100],'username'=>['Username',60],'role_id'=>['Role','roles','role_name'],'password'=>['Password (12+ characters; blank keeps current)','password']]],
]; }
function save_master(array $u,string $table,array $data): void {
 require_owner($u); $meta=masters()[$table]??null; if(!$meta) throw new DomainException('Unknown module.');
 atomic(function() use($u,$table,$data,$meta) {
  $id=(int)($data['id']??0); $existing=$id?one("SELECT * FROM $table WHERE id=? FOR UPDATE",[$id]):null;
  if($id&&!$existing) throw new DomainException('Record no longer exists.');
  if($table==='users'&&!$id) throw new DomainException('New staff must be invited and verify their email before creating an account.');
  $values=[];
  foreach($meta[1] as $field=>$spec) {
   $raw=$data[$field]??'';
   if($spec[1]==='password') {
    if($raw===''&&$id) continue;
    if(!is_string($raw)||strlen($raw)<12||strlen($raw)>72) throw new DomainException('Passwords must have 12–72 characters.');
    $values['password_hash']=password_hash($raw,PASSWORD_DEFAULT);
   } elseif($spec[1]==='money') $values[$field]=decimal(cents($raw));
   elseif($spec[1]==='integer') { $values[$field]=(string)$raw==='0'?0:quantity($raw); }
   elseif(is_string($spec[1])) {
    $ref=quantity($raw); $target=$spec[1];
    if(!one("SELECT id FROM $target WHERE id=?".($target!=='roles'?' AND is_active=1':''),[$ref])) throw new DomainException('Choose an active '.$spec[0].'.');
    $values[$field]=$ref;
   } else $values[$field]=text_value($raw,$spec[1],$spec[2]??true);
  }
  if($table==='users'&&!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/D',$values['username'])) throw new DomainException('Use 3–60 letters, digits, dots, underscores or hyphens for usernames.');
  $values['is_active']=isset($data['is_active'])?1:0;
  if($table==='users'&&$id===(int)$u['id']&&(!$values['is_active']||(int)$values['role_id']!==(int)$u['role_id'])) throw new DomainException('You cannot deactivate or change the role of your own account.');
  if($id) { $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($values))); query("UPDATE $table SET $sets WHERE id=?",[...array_values($values),$id]); }
  else { $keys=implode(',',array_keys($values)); query("INSERT INTO $table ($keys) VALUES (".implode(',',array_fill(0,count($values),'?')).')',array_values($values)); $id=(int)db()->lastInsertId(); }
  audit((int)$u['id'],$existing?'UPDATE':'CREATE',$table,$id,$meta[0].' record saved');
 });
}
