<?php
require __DIR__ . '/../includes/init.php';
require_post();
$next = current_theme() === 'dark' ? 'light' : 'dark';
setcookie('ecobantay_theme', $next, [
    'expires'  => time() + 60 * 60 * 24 * 365,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
redirect_back('index.php');
