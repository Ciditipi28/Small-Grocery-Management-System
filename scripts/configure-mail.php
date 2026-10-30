<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
try {
    $data=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
    $email=trim((string)($data['email']??''));
    $password=str_replace(' ','',(string)($data['password']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[a-zA-Z0-9]{16}$/D',$password)) throw new RuntimeException('Enter a valid Gmail sender address and its 16-character App Password.');
    $path=dirname(__DIR__).'/config.local.php';
    $local=is_file($path)?require $path:[];
    $local=array_replace($local,['mail_smtp_host'=>'smtp.gmail.com','mail_smtp_port'=>587,'mail_smtp_security'=>'tls','mail_smtp_username'=>$email,'mail_smtp_password'=>$password,'mail_from'=>$email]);
    if(file_put_contents($path,"<?php\nreturn ".var_export($local,true).";\n",LOCK_EX)===false) throw new RuntimeException('Could not save the private email configuration.');
    echo "Gmail sender configured. No email was sent by this setup command.\n";
} catch(Throwable $e) { fwrite(STDERR,'Setup failed: '.$e->getMessage()."\n"); exit(1); }
