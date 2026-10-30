<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['timezone']);
require_once __DIR__ . '/https.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/transactions.php';
require_once __DIR__ . '/registration.php';
if (PHP_SAPI !== 'cli') {
    if(!empty($config['require_https'])) {
        ini_set('display_errors','0');
        try { $redirect=https_redirect_target($config,$_SERVER); }
        catch(DomainException $e) { http_response_code(503); exit('The secure site address needs to be configured by the owner.'); }
        if($redirect!==null) { header('Location: '.$redirect,true,303); exit; }
    }
    ini_set('session.use_strict_mode', '1');
    session_name('grocery_session');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($config['require_https']) || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
