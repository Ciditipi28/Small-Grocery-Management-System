<?php
return array_replace([
    'db_host' => '127.0.0.1', 'db_port' => 3306,
    'db_name' => 'mini_grocery', 'db_user' => 'root', 'db_password' => '',
    'store_name' => 'Neighborhood Mini Grocery',
    'timezone' => 'Asia/Manila', 'currency' => '',
    'mail_smtp_host' => 'smtp.gmail.com', 'mail_smtp_port' => 587,
    'mail_smtp_security' => 'tls',
    'mail_smtp_username' => getenv('GROCERY_SMTP_USERNAME') ?: '',
    'mail_smtp_password' => getenv('GROCERY_SMTP_PASSWORD') ?: '',
    'mail_from' => getenv('GROCERY_SMTP_USERNAME') ?: '',
    'registration_url' => 'http://127.0.0.1:8085/index.php?page=register',
], is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : []);
