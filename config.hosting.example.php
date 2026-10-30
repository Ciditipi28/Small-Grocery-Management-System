<?php
// On the HOSTING SERVER ONLY, copy this to config.local.php and fill in values.
// Keep your local XAMPP configuration unchanged.
return [
    'db_host' => 'YOUR_MYSQL_HOSTNAME',
    'db_port' => 3306,
    'db_name' => 'YOUR_PREFIXED_DATABASE_NAME',
    'db_user' => 'YOUR_HOSTING_MYSQL_USERNAME',
    'db_password' => '',
    'store_name' => 'Neighborhood Mini Grocery',
    'timezone' => 'Asia/Manila',
    'currency' => '',
    // Enable only after the host's SSL certificate is active.
    'require_https' => true,
    'mail_smtp_host' => 'smtp.gmail.com',
    'mail_smtp_port' => 587,
    'mail_smtp_security' => 'tls',
    'mail_smtp_username' => '', // Gmail sender address.
    'mail_smtp_password' => '', // Gmail App Password, not normal password.
    'mail_from' => '', // Same address as mail_smtp_username.
    'registration_url' => 'https://YOUR-SUBDOMAIN.infinityfreeapp.com/public/index.php?page=register',
];
