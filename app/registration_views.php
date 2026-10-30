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
    ?><!doctype html><html lang="en"><head><?php ui_head('Create account'); ?></head><body class="auth-page">
    <?php auth_header(); ?>
    <main class="registration-shell"><section class="registration-intro"><p class="eyebrow">TEAM ACCESS</p><h1>Create your staff account</h1><p class="muted">Join <?=e($config['store_name'])?> using the email address your owner invited.</p><div class="steps" aria-label="Account setup steps"><span>1. Owner invitation</span><span>2. Email verification</span><span>3. Ready to sign in</span></div></section>
    <section class="panel registration-card">
    <?php registration_notice(); if($error) echo '<div class="alert error" role="alert">'.e($error).'</div>'; ?>
    <form method="post" action="<?=e(url('register'))?>"><?=csrf()?><input type="hidden" name="action" value="register"><div class="form-grid">
    <div><?php input_field('full_name','Full name',$_POST['full_name']??'','text',true,'maxlength="100" autocomplete="name" placeholder="Your full name"'); ?></div>
    <div><?php input_field('email','Invited email address',$_POST['email']??'','email',true,'maxlength="254" autocomplete="email" placeholder="you@example.com"'); ?></div>
    <div><?php input_field('username','Username',$_POST['username']??'','text',true,'minlength="3" maxlength="60" autocomplete="username" placeholder="Choose a username"'); ?></div>
    <div><?php input_field('otp','Email verification code','','text',true,'inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code"'); ?></div>
    <div><?php password_field('password','Password','register-password','new-password','minlength="12" maxlength="72"'); ?></div>
    <div><?php password_field('password_confirm','Confirm password','register-password-confirm','new-password','minlength="12" maxlength="72"'); ?></div>
    </div><p class="form-help">Use 12–72 characters for your password. Verification codes expire in 10 minutes.</p><button class="button wide">Verify email &amp; create account <?=ui_icon('arrow')?></button></form>
    <details class="resend-box"><summary>Need another verification code?</summary><p class="muted">Enter your invited email address. You can request a new code once per minute.</p><form method="post" action="<?=e(url('register'))?>"><?=csrf()?><input type="hidden" name="action" value="request_code"><?php input_field('email','Invited email',$_POST['email']??'','email',true,'maxlength="254" autocomplete="email"'); ?><button class="button secondary">Email another code</button></form></details><p class="auth-switch">Already have an account? <a href="<?=e(url('login'))?>">Sign in</a></p></section></main><footer class="auth-bottom">Grocery <span>Inventory &amp; sales</span></footer></body></html><?php
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
