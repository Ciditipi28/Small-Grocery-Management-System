<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/vendor/phpmailer/src/Exception.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/SMTP.php';

function mail_ready(): bool {
    global $config;
    return $config['mail_smtp_username']!=='' && $config['mail_smtp_password']!==''
        && filter_var($config['mail_from'],FILTER_VALIDATE_EMAIL)!==false;
}
function send_otp_email(string $email,string $code): void {
    global $config;
    if(!mail_ready()) throw new DomainException('Gmail sending is not configured yet. Ask the owner to complete the email setup.');
    $mail=new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host=$config['mail_smtp_host'];
        $mail->Port=(int)$config['mail_smtp_port'];
        $mail->SMTPAuth=true;
        $mail->Username=$config['mail_smtp_username'];
        $mail->Password=$config['mail_smtp_password'];
        $mail->SMTPSecure=$config['mail_smtp_security'];
        $mail->Timeout=15;
        $mail->Timelimit=20;
        $mail->SMTPDebug=0;
        $mail->CharSet='UTF-8';
        $mail->setFrom($config['mail_from'],$config['store_name']);
        $mail->addAddress($email);
        $mail->Subject='Your grocery account verification code';
        $mail->Body="Your verification code is: $code\n\nThis code expires in 10 minutes and can be used once.\nCreate your staff account at:\n".$config['registration_url']."\n\nThe store owner selected your role. Never share this code. If you did not expect an invitation, ignore this email.";
        $mail->send();
    } catch(\PHPMailer\PHPMailer\Exception $e) {
        error_log('Grocery verification email delivery failed; check SMTP configuration and provider access.');
        throw new DomainException('Email could not be sent. Check the Gmail sender settings and try again.');
    }
}
