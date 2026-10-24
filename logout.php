<?php
require __DIR__ . '/includes/init.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') redirect('index.php');   // logout is POST + CSRF protected
require_post();
if (!empty($_SESSION['user_id'])) {
    stop_live_location_sharing((int)$_SESSION['user_id']);
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();                       // fresh session so we can show the login page cleanly
session_regenerate_id(true);
redirect('login.php');
