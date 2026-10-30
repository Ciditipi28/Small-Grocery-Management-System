<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['timezone']);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/transactions.php';
require_once __DIR__ . '/registration.php';
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_name('grocery_session');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
