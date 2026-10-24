<?php
require __DIR__ . '/../includes/init.php';
require_post();
$admin = require_role('Admin');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || $id === (int)$admin['id']) {
    flash_set('toast', 'You cannot change your own verification status.');
    redirect('admin/users.php');
}

$u = q('SELECT id, name, role, resident_id, resident_id_photo FROM users WHERE id = ?', [$id])->fetch();
if ($u && $u['role'] === 'Resident') {
    if (trim((string)$u['resident_id']) === '') {
        flash_set('toast', 'Add a barangay resident ID before verifying this account.');
        redirect('admin/users.php?edit=' . $id);
    }
    $photoPath = resident_id_photo_file($u['resident_id_photo']);
    if ($photoPath === null || !is_file($photoPath)) {
        flash_set('toast', 'Upload a clear resident ID photo before verifying this account.');
        redirect('admin/users.php?edit=' . $id);
    }
    q('UPDATE users SET verified = 1 WHERE id = ?', [$id]);
    add_notification((int)$u['id'], 'Your EcoBantay account has been verified. If your registration waiting page is still open, you will be signed in automatically. Otherwise, you can now log in.');
    flash_set('toast', $u['name'] . ' verified. They will be signed in automatically if their registration page is still open.');
} else {
    flash_set('toast', 'Only resident accounts can be verified from this screen.');
}
redirect('admin/users.php');
