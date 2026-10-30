<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo;
    global $config;
    return $pdo ??= new PDO("mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false, PDO::MYSQL_ATTR_INIT_COMMAND=>"SET time_zone = '".date('P')."'"]);
}
function query(string $sql, array $params=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($params); return $s; }
function rows(string $sql,array $params=[]): array { return query($sql,$params)->fetchAll(); }
function one(string $sql,array $params=[]): ?array { return query($sql,$params)->fetch() ?: null; }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $page, array $params=[]): string { return 'index.php?' . http_build_query(['page'=>$page]+$params); }
function go(string $page,array $params=[]): never { header('Location: '.url($page,$params)); exit; }
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return one('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1', [$_SESSION['user_id']]);
}
function owner(array $u): bool { return $u['role_name']==='Owner'; }
function require_owner(array $u): void { if (!owner($u)) throw new DomainException('This action is available to the owner only.'); }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf']??'',(string)($_POST['csrf']??''))) throw new DomainException('Your form expired. Refresh the page and try again.'); }
function text_value(mixed $v,int $max=150,bool $required=true): string {
    if (!is_scalar($v)) throw new DomainException('Invalid text input.');
    $s=trim((string)$v); if (($required && $s==='') || mb_strlen($s)>$max) throw new DomainException("Required text is missing or longer than $max characters."); return $s;
}
function quantity(mixed $v,bool $signed=false): int {
    if (!is_scalar($v) || !preg_match($signed?'/^-?[0-9]{1,7}$/D':'/^[0-9]{1,7}$/D',(string)$v)) throw new DomainException('Enter a whole-number quantity.');
    $n=(int)$v; if (($signed ? $n===0 : $n<1) || abs($n)>1000000) throw new DomainException('Quantity must be between 1 and 1,000,000; adjustments can also be negative.'); return $n;
}
function cents(mixed $v): int {
    if (!is_scalar($v) || !preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D',(string)$v)) throw new DomainException('Enter a nonnegative amount with at most two decimal places.');
    $p=explode('.',(string)$v); $n=(int)$p[0]*100+(int)str_pad($p[1]??'',2,'0');
    if ($n>999999999999) throw new DomainException('Amount exceeds the supported limit.'); return $n;
}
function decimal(int $n): string { return ($n<0?'-':'').intdiv(abs($n),100).'.'.str_pad((string)(abs($n)%100),2,'0',STR_PAD_LEFT); }
function money(mixed $v): string { global $config; return e(($config['currency']!==''?$config['currency'].' ':'').number_format((float)$v,2)); }
function audit(int $user,string $action,string $type,int $id,string $description): void { query('INSERT INTO activity_logs (user_id,action,record_type,record_id,description) VALUES (?,?,?,?,?)',[$user,$action,$type,$id,$description]); }
function atomic(callable $work): mixed { db()->beginTransaction(); try { $v=$work(); db()->commit(); return $v; } catch (Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; } }
function date_range(): array {
    $start=(string)($_GET['from']??date('Y-m-d',strtotime('-29 days'))); $end=(string)($_GET['to']??date('Y-m-d'));
    foreach ([$start,$end] as $d) { $dt=DateTimeImmutable::createFromFormat('!Y-m-d',$d); if(!$dt || $dt->format('Y-m-d')!==$d) throw new DomainException('Choose valid report dates.'); }
    if($start>$end) throw new DomainException('Start date must be before the end date.');
    return [$start,$end,(new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d')];
}
