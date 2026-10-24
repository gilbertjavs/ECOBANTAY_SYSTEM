<?php
require __DIR__ . '/../includes/init.php';
require_post();
$user = require_role('Resident');
$userId = (int)$user['id'];
$section = (string)($_POST['section'] ?? '');

if ($section === 'profile') {
    $name = trim((string)($_POST['name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $contact = trim((string)($_POST['contact'] ?? ''));
    $old = ['name' => $name, 'username' => $username, 'email' => $email, 'contact' => $contact];

    if ($error = user_field_error($name, $username, $email, $contact)) {
        set_old($old);
        flash_set('toast', $error);
        redirect('resident/profile.php');
    }
    if ($error = user_duplicate_error($username, $email, $userId, 'taken')) {
        set_old($old);
        flash_set('toast', $error);
        redirect('resident/profile.php');
    }

    try {
        q('UPDATE users SET name = ?, username = ?, email = ?, contact = ? WHERE id = ?',
            [$name, $username, $email, $contact, $userId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            set_old($old);
            flash_set('toast', 'That username or email is already taken.');
            redirect('resident/profile.php');
        }
        throw $e;
    }

    $_SESSION['name'] = $name;
    if (!empty($_SESSION['location_share_enabled'])) {
        q('DELETE FROM live_location_shares WHERE session_key = ? AND user_id = ?',
            [live_location_session_key(), $userId]);
    }
    session_regenerate_id(true);
    flash_set('toast', 'Your profile has been updated.');
    redirect('resident/profile.php');
}

if ($section === 'password') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $account = q('SELECT password FROM users WHERE id = ?', [$userId])->fetch();

    if (!$account || !password_verify($currentPassword, $account['password'])) {
        flash_set('toast', 'Your current password is incorrect.');
        redirect('resident/profile.php');
    }
    if (strlen($newPassword) < 6 || strlen($newPassword) > 72) {
        flash_set('toast', 'New password must be between 6 and 72 characters.');
        redirect('resident/profile.php');
    }
    if ($newPassword !== $confirmPassword) {
        flash_set('toast', 'The new password and confirmation do not match.');
        redirect('resident/profile.php');
    }
    if (password_verify($newPassword, $account['password'])) {
        flash_set('toast', 'Choose a password different from your current password.');
        redirect('resident/profile.php');
    }

    q('UPDATE users SET password = ? WHERE id = ?', [password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    if (!empty($_SESSION['location_share_enabled'])) {
        q('DELETE FROM live_location_shares WHERE session_key = ? AND user_id = ?',
            [live_location_session_key(), $userId]);
    }
    session_regenerate_id(true);
    flash_set('toast', 'Your password has been updated.');
    redirect('resident/profile.php');
}

flash_set('toast', 'Please choose a valid profile update.');
redirect('resident/profile.php');
