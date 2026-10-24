<?php
require __DIR__ . '/../includes/init.php';
require_post();

$identifier = trim((string)($_POST['identifier'] ?? ''));
$password   = (string)($_POST['password'] ?? '');
$ip         = client_ip();

if ($identifier === '' || $password === '') {
    flash_set('auth_error', 'Please complete all required fields.');
    redirect('login.php');
}

if (login_is_locked($identifier, $ip)) {
    flash_set('auth_error', 'Too many failed login attempts. Please wait 15 minutes and try again.');
    redirect('login.php');
}

// Login with username OR email (was DB.users.find(...) in JavaScript)
$u = q('SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1', [$identifier, $identifier])->fetch();

if (!$u || !password_verify($password, $u['password'])) {
    record_failed_login($identifier, $ip);
    flash_set('auth_error', 'Incorrect email/username or password. Please try again.');
    redirect('login.php');
}
if (!(int)$u['verified']) {
    clear_login_attempts($identifier, $ip);
    flash_set('auth_error', 'Your account is still waiting for verification by the barangay admin.');
    redirect('login.php');
}

clear_login_attempts($identifier, $ip);
if (!empty($_SESSION['user_id'])) {
    stop_live_location_sharing((int)$_SESSION['user_id']);
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$u['id'];
$_SESSION['role']    = $u['role'];
$_SESSION['name']    = $u['name'];

flash_set('toast', 'Welcome back, ' . first_name($u['name']) . '.');
redirect(home_for($u['role']));
