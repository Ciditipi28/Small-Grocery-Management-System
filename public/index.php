<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/masters.php';
require dirname(__DIR__).'/app/views.php';
require dirname(__DIR__).'/app/registration_views.php';
$page=(string)($_GET['page']??'dashboard'); $error=null; $user=null;
try {
 $user=current_user();
 if($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf(); $action=(string)($_POST['action']??'');
  if($action==='register') {
   if($user) throw new DomainException('Sign out before creating another account.');
   register_staff($_POST);
   $_SESSION['registration_notice']='Account created and email verified. You can now sign in.';
   go('login');
  }
  if($action==='request_code') {
   if(($_SESSION['code_request_after']??0)>time()) throw new DomainException('Wait 60 seconds before requesting another code.');
   $_SESSION['code_request_after']=time()+60;
   request_registration_code($_POST['email']??'');
   $_SESSION['registration_notice']='If this email has a pending invitation and can receive another code, an email will be sent. Check your inbox and spam folder.';
   go('register');
  }
  if($action==='login') {
   if(($_SESSION['login_after']??0)>time()) throw new DomainException('Too many attempts. Please wait a minute before trying again.');
   $account=one('SELECT * FROM users WHERE username=? AND is_active=1',[text_value($_POST['username']??'',60)]);
   if(!$account||!password_verify((string)($_POST['password']??''),$account['password_hash'])) {
    $_SESSION['login_failures']=($_SESSION['login_failures']??0)+1;
    if($_SESSION['login_failures']>=5) { $_SESSION['login_after']=time()+60; $_SESSION['login_failures']=0; }
    throw new DomainException('Username or password is incorrect.');
   }
   session_regenerate_id(true); $_SESSION=['user_id'=>$account['id'],'csrf'=>bin2hex(random_bytes(32))]; go('dashboard');
  }
  if(!$user) go('login');
  if($action==='logout') { $_SESSION=[]; session_destroy(); go('login'); }
  if($action==='invite_staff') { invite_staff($user,$_POST); $_SESSION['flash']='Invitation email sent. The recipient must verify their code to create an account.'; go('users'); }
  if($action==='resend_invitation') { resend_invitation($user,(int)($_POST['invitation_id']??0)); $_SESSION['flash']='A new verification code was emailed. The previous code is no longer valid.'; go('users'); }
  if($action==='revoke_invitation') { revoke_invitation($user,(int)($_POST['invitation_id']??0)); $_SESSION['flash']='Invitation revoked.'; go('users'); }
  if($action==='save_master') { save_master($user,$page,$_POST); $_SESSION['flash']='Record saved.'; go($page); }
  if($action==='post_document') { $type=(string)($_GET['type']??'sale'); $id=post_document($user,$type,$_POST); $_SESSION['flash']='Transaction posted successfully.'; go('detail',['type'=>$type,'id'=>$id]); }
  throw new DomainException('Unknown action.');
 }
} catch(DomainException $ex) { $error=$ex->getMessage(); }
catch(PDOException $ex) { error_log($ex->getMessage()); $error=$ex->getCode()==='23000'?'A code or username already exists, or a related record is invalid.':'Database unavailable. Start MySQL and follow the installation guide in docs/03-RUNNING-THE-SYSTEM.md.'; }
catch(Throwable $ex) { error_log($ex->getMessage()); $error='The request could not be completed. Check the application error log.'; }
if(!$user) { if($page==='register') registration_view($error); else login_view($error); exit; }
$title=page_title($page); layout_start($title,$user);
if($error) echo '<div class="alert error" role="alert">'.e($error).'</div>';
if(isset($_SESSION['flash'])) { echo '<div class="alert success" role="status">'.e($_SESSION['flash']).'</div>'; unset($_SESSION['flash']); }
try { render_page($page,$user); }
catch(DomainException $ex) { http_response_code(403); echo '<div class="alert error">'.e($ex->getMessage()).'</div>'; }
catch(Throwable $ex) { error_log($ex->getMessage()); http_response_code(500); echo '<div class="alert error">Unable to load this page. Check that the database is installed and running.</div>'; }
layout_end();
