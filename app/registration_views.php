<?php
declare(strict_types=1);
function registration_notice(): void {
    if(isset($_SESSION['registration_notice'])) {
        echo '<div class="alert success" role="status">'.e($_SESSION['registration_notice']).'</div>';
        unset($_SESSION['registration_notice']);
    }
}
function registration_view(?string $error): void {
    global $config;
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create account · Grocery</title><link rel="stylesheet" href="assets/app.css"></head><body class="login"><main class="registration-shell"><section class="registration-intro"><a class="brand" href="<?=e(url('login'))?>">▦ GROCERY</a><p class="eyebrow">JOIN YOUR STORE</p><h1>Create your staff account</h1><p class="muted">Use the email address invited by the owner of <?=e($config['store_name'])?>. Enter the six-digit code from your email to finish.</p><div class="steps"><span>1 · Owner invitation</span><span>2 · Email code</span><span>3 · Account ready</span></div></section><section class="panel registration-card"><?php
    registration_notice();
    if($error) echo '<div class="alert error" role="alert">'.e($error).'</div>';
    echo '<form method="post" action="'.e(url('register')).'">'.csrf().'<input type="hidden" name="action" value="register"><div class="form-grid">';
    input_field('full_name','Full name',$_POST['full_name']??'','text',true,'maxlength="100" autocomplete="name"');
    input_field('email','Invited email address',$_POST['email']??'','email',true,'maxlength="254" autocomplete="email"');
    input_field('username','Username',$_POST['username']??'','text',true,'minlength="3" maxlength="60" autocomplete="username"');
    input_field('otp','Email verification code','','text',true,'inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code"');
    input_field('password','Password','','password',true,'minlength="12" maxlength="72" autocomplete="new-password"');
    input_field('password_confirm','Confirm password','','password',true,'minlength="12" maxlength="72" autocomplete="new-password"');
    echo '</div><p class="muted">Codes expire in 10 minutes. Your role is assigned by the owner.</p><button class="button wide">Verify code &amp; create account</button></form><details class="resend-box"><summary>Need another code?</summary><p class="muted">Only email addresses invited by the owner can register. You can request a new code once per minute.</p><form method="post" action="'.e(url('register')).'">'.csrf().'<input type="hidden" name="action" value="request_code">';
    input_field('email','Invited email',$_POST['email']??'','email',true,'maxlength="254" autocomplete="email"');
    echo '<button class="button secondary">Email another code</button></form></details><p class="auth-switch">Already have an account? <a href="'.e(url('login')).'">Sign in</a></p></section></main></body></html>';
}
function invitations_panel(): void {
    echo '<section class="panel"><h2>Invite a staff member</h2><p class="muted">Choose their role and email them a code. Their account is created only after they verify their email.</p>';
    if(!mail_ready()) echo '<div class="alert error">Gmail sending needs setup before invitations can be emailed. See the project’s Gmail setup guide.</div>';
    echo '<form method="post" action="'.e(url('users')).'" class="form-grid">'.csrf().'<input type="hidden" name="action" value="invite_staff">';
    input_field('email','Staff email address',($_POST['action']??'')==='invite_staff'?($_POST['email']??''):'','email',true,'maxlength="254" autocomplete="off"');
    select_field('role_id','Assigned role',rows('SELECT id,role_name label FROM roles ORDER BY id DESC'),($_POST['action']??'')==='invite_staff'?($_POST['role_id']??2):2);
    echo '<div class="invite-submit"><button class="button">Email invitation code</button></div></form><details><summary>Invitation history</summary>';
    $invitations=rows('SELECT i.*,r.role_name FROM account_invitations i JOIN roles r ON r.id=i.role_id ORDER BY i.id DESC LIMIT 50');
    $data=[];
    foreach($invitations as $i) {
        $status=$i['used_at']?'Account created':($i['revoked_at']?'Revoked':(!$i['expires_at']||strtotime($i['expires_at'])<=time()?'Code expired':((int)$i['attempts']>=5?'Code locked':'Pending')));
        $actions='';
        if(!$i['used_at']&&!$i['revoked_at']) {
            foreach(['resend_invitation'=>'Resend code','revoke_invitation'=>'Revoke'] as $action=>$label) $actions.='<form class="inline-form" method="post" action="'.e(url('users')).'">'.csrf().'<input type="hidden" name="action" value="'.$action.'"><input type="hidden" name="invitation_id" value="'.e($i['id']).'"><button class="text-button" '.($action==='revoke_invitation'?'data-confirm="Revoke this invitation? Its verification code will stop working."':'').'>'.$label.'</button></form> ';
        }
        $data[]=[e($i['email']),e($i['role_name']),e($status),e($i['last_sent_at']??'Not sent'),$actions];
    }
    table_view(['Email','Role','Status','Last email','Actions'],$data);
    echo '</details></section>';
}
