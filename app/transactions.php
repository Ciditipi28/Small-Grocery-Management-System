<?php
declare(strict_types=1);
function document_types(): array { return [
    'purchase'=>['purchases','purchase_items','purchase_id','Receiving History','Record Delivery','Delivery Details','PUR'],
    'sale'=>['sales','sale_items','sale_id','Sales History','New Sale / Checkout','Sale Details / Receipt','SAL'],
    'return'=>['sales_returns','sales_return_items','return_id','Return History','Customer Return','Return Details','RET'],
    'adjustment'=>['stock_adjustments','stock_adjustment_items','adjustment_id','Stock Adjustments','New Stock Adjustment','Adjustment Details','ADJ'],
]; }
function post_document(array $u,string $type,array $data): int {
    $types=document_types(); if(!isset($types[$type])) throw new DomainException('Invalid transaction type.');
    if($type!=='sale') require_owner($u);
    $key=text_value($data['request_key']??'',64); if(!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new DomainException('Invalid submission key.');
    [$table,$items,$fk,, , ,$prefix]=$types[$type];
    return atomic(function() use($u,$type,$data,$key,$table,$items,$fk,$prefix) {
        // Serialize identical submissions, even when they contain different product lists.
        query('SELECT id FROM users WHERE id=? FOR UPDATE',[$u['id']]);
        $existing=one("SELECT id,created_by FROM $table WHERE request_key=?",[$key]);
        if($existing) { if((int)$existing['created_by']!==(int)$u['id']) throw new DomainException('Submission key already used.'); return (int)$existing['id']; }
        $ids=$data['product_id']??[]; $qs=$data['quantity']??[];
        if(!is_array($ids)||!is_array($qs)||!count($ids)||count($ids)>100) throw new DomainException('Add between 1 and 100 product lines.');
        $lines=[]; $total=0; $sale=null;
        if($type==='return') {
            $sale=one("SELECT * FROM sales WHERE id=? AND status='POSTED' FOR UPDATE",[(int)($data['sale_id']??0)]);
            if(!$sale) throw new DomainException('Choose an original completed sale.');
        }
        foreach($ids as $i=>$rawId) {
            $id=quantity($rawId); if(isset($lines[$id])) throw new DomainException('Combine duplicate products into one line.');
            $q=quantity($qs[$i]??'', $type==='adjustment');
            if($type==='return') {
                $original=one('SELECT * FROM sale_items WHERE id=? AND sale_id=?',[$id,$sale['id']]);
                if(!$original) throw new DomainException('A return line does not belong to the selected sale.');
                $prior=(int)query("SELECT COALESCE(SUM(i.quantity),0) FROM sales_return_items i JOIN sales_returns h ON h.id=i.return_id WHERE i.sale_item_id=? AND h.status='POSTED'",[$id])->fetchColumn();
                if($q>(int)$original['quantity']-$prior) throw new DomainException('Returned quantity exceeds the remaining returnable quantity.');
                $restockRaw=$data['restock_quantity'][$i]??'0';
                $restock=(string)$restockRaw==='0'?0:quantity($restockRaw);
                if($restock>$q) throw new DomainException('Saleable quantity cannot exceed the returned quantity.');
                $lines[$id]=['product_id'=>(int)$original['product_id'],'quantity'=>$q,'delta'=>$restock,'price'=>cents($original['unit_price']),'original'=>$original];
            } else $lines[$id]=['product_id'=>$id,'quantity'=>$q,'delta'=>$type==='sale'?-$q:$q,'cost'=>$data['unit_cost'][$i]??'0'];
        }
        $productIds=array_unique(array_column($lines,'product_id')); sort($productIds,SORT_NUMERIC); $products=[];
        foreach($productIds as $id) {
            $p=one('SELECT * FROM products WHERE id=? FOR UPDATE',[$id]);
            if(!$p || (!$p['is_active'] && in_array($type,['purchase','sale'],true))) throw new DomainException('A selected product is unavailable or inactive.');
            $products[$id]=$p;
        }
        foreach($lines as &$line) {
            $p=$products[$line['product_id']]; $new=(int)$p['stock_qty']+$line['delta'];
            if($new<0 || $new>1000000000) throw new DomainException('Insufficient stock or stock limit exceeded for '.$p['product_name'].'.');
            if($type==='sale') $line['price']=cents($p['selling_price']);
            if($type==='purchase') $line['price']=cents($line['cost']);
            if($type!=='adjustment') $total+=$line['price']*$line['quantity'];
            if($total>999999999999) throw new DomainException('Transaction total exceeds the supported limit.');
        } unset($line);
        $no=$prefix.'-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(5)));
        $cols=['document_no','request_key','created_by']; $values=[$no,$key,$u['id']];
        if($type==='purchase') {
            $supplier=(int)($data['supplier_id']??0);
            if(!one('SELECT id FROM suppliers WHERE id=? AND is_active=1 FOR UPDATE',[$supplier])) throw new DomainException('Choose an active supplier.');
            $cols=array_merge($cols,['supplier_id','supplier_reference','notes','total_amount']);
            $values=array_merge($values,[$supplier,text_value($data['supplier_reference']??'',100,false),text_value($data['notes']??'',500,false),decimal($total)]);
        } elseif($type==='sale') {
            $cash=cents($data['cash_tendered']??''); if($cash<$total) throw new DomainException('Cash tendered is less than the sale total.');
            $cols=array_merge($cols,['total_amount','cash_tendered']); $values=array_merge($values,[decimal($total),decimal($cash)]);
        } elseif($type==='return') {
            $cols=array_merge($cols,['sale_id','reason','total_amount']); $values=array_merge($values,[$sale['id'],text_value($data['reason']??'',500),decimal($total)]);
        } else {
            $cols=array_merge($cols,['reason','notes']); $values=array_merge($values,[text_value($data['reason']??'',100),text_value($data['notes']??'',500,false)]);
        }
        query("INSERT INTO $table (".implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($values),'?')).')',$values); $header=(int)db()->lastInsertId();
        foreach($lines as $lineId=>$line) {
            $p=$products[$line['product_id']];
            if($type==='return') query('INSERT INTO sales_return_items (return_id,sale_item_id,quantity,restock_quantity) VALUES (?,?,?,?)',[$header,$lineId,$line['quantity'],$line['delta']]);
            elseif($type==='adjustment') query('INSERT INTO stock_adjustment_items (adjustment_id,product_id,quantity_change) VALUES (?,?,?)',[$header,$p['id'],$line['quantity']]);
            elseif($type==='purchase') query('INSERT INTO purchase_items (purchase_id,product_id,quantity,unit_cost) VALUES (?,?,?,?)',[$header,$p['id'],$line['quantity'],decimal($line['price'])]);
            else query('INSERT INTO sale_items (sale_id,product_id,product_name_snapshot,sku_snapshot,quantity,unit_price) VALUES (?,?,?,?,?,?)',[$header,$p['id'],$p['product_name'],$p['sku'],$line['quantity'],decimal($line['price'])]);
            query('UPDATE products SET stock_qty=stock_qty+? WHERE id=?',[$line['delta'],$p['id']]);
        }
        audit((int)$u['id'],'POST',$table,$header,$no); return $header;
    });
}
