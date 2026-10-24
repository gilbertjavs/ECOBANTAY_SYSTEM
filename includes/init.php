<?php
/* Bootstrap: loaded at the top of every page and action. */
require_once __DIR__ . '/../config/database.php';

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data: blob: https://unpkg.com https://*.tile.openstreetmap.org; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self';");
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
    session_name('ECOBANTAYSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

set_exception_handler(function ($e) {
    $message = $e->getMessage();
    error_log('[EcoBantay] ' . $message . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);

    $isDbIssue = stripos($message, 'database') !== false
        || stripos($message, 'mysql') !== false
        || stripos($message, 'sqlstate') !== false
        || stripos($message, 'pdo') !== false;

    $title = $isDbIssue ? 'Database setup required' : 'Something went wrong';
    $detail = $isDbIssue
        ? 'EcoBantay could not connect to the MySQL database. Make sure MySQL is running and import the project schema from database/ecobantay.sql before trying again.'
        : 'Please try again in a moment.';

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>EcoBantay</title>'
       . '<link rel="stylesheet" href="' . htmlspecialchars(BASE_URL . '/assets/style.css') . '"></head><body><div class="auth-form-side" style="min-height:100vh;">'
       . '<div class="auth-box">'
       . '<div class="auth-error">' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
       . '<div class="auth-notice" style="margin:0 0 18px; text-align:center;">' . htmlspecialchars($detail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
       . '<a class="btn block" href="' . htmlspecialchars(BASE_URL . '/index.php') . '">Back to EcoBantay</a>'
       . '</div></div></body></html>';
    exit;
});

require_once __DIR__ . '/functions.php';
ensure_login_attempts_table();
ensure_live_location_shares_table();
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/footer.php';
