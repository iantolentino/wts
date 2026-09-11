<?php
declare(strict_types=1);
const ROOT_PATH = __DIR__ . '/..';
$config = require is_file(ROOT_PATH . '/config/config.local.php') ? ROOT_PATH . '/config/config.local.php' : ROOT_PATH . '/config/config.example.php';
date_default_timezone_set('Asia/Manila');
if (!is_dir(ROOT_PATH . '/.local/sessions')) mkdir(ROOT_PATH . '/.local/sessions', 0700, true);
session_save_path(ROOT_PATH . '/.local/sessions');
session_name($config['app']['session_name']);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params(['path' => '/', 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tickets.php';
require_once __DIR__ . '/wheettle.php';
require_once __DIR__ . '/layout.php';
set_exception_handler(function (Throwable $error): void {
    error_log('Wheettle: ' . $error->getMessage());
    http_response_code(500);
    echo '<h1>Unable to complete this request</h1><p>Please try again or contact your administrator. Your changes may not have been saved.</p>';
});
