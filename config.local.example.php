<?php
// Copy to config.local.php. Keep credentials outside public/.
return [
    'db_host' => '127.0.0.1', 'db_port' => 3306,
    'db_name' => 'mini_grocery', 'db_user' => 'root', 'db_password' => '',
    'store_name' => 'Neighborhood Mini Grocery', 'currency' => '',
    // Gmail App Password, not the normal Google account password.
    'mail_smtp_username' => '',
    'mail_smtp_password' => '',
    'mail_from' => '', // Same Gmail address as mail_smtp_username.
    'registration_url' => 'http://localhost/SmallGrocery/public/index.php?page=register',
];
