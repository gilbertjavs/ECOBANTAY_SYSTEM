<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$id       = (int)($_POST['id'] ?? 0);
$name     = trim((string)($_POST['name'] ?? ''));
$username = trim((string)($_POST['username'] ?? ''));
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$contact  = trim((string)($_POST['contact'] ?? ''));
$residentId = trim((string)($_POST['resident_id'] ?? ''));
$role     = (string)($_POST['role'] ?? '');

function uupd_fail(string $m, int $id) { flash_set('toast', $m); redirect('admin/users.php?edit=' . $id); }

$u = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
if (!$u) { flash_set('toast', 'That user no longer exists.'); redirect('admin/users.php'); }

if ($err = user_field_error($name, $username, $email, $contact)) uupd_fail($err, $id);
if (!in_array($role, USER_ROLES, true)) uupd_fail('Please choose a valid role.', $id);
if ($role === 'Resident' && ($err = resident_id_error($residentId, $id))) uupd_fail($err, $id);
if ($role !== 'Resident' && strlen($residentId) > 50) uupd_fail('Resident ID is too long (max 50 characters).', $id);
if ($err = user_duplicate_error($username, $email, $id, 'taken')) uupd_fail($err, $id);

// Protect the admin account: the last remaining admin cannot be demoted.
if ($u['role'] === 'Admin' && $role !== 'Admin') {
    $admins = (int)q("SELECT COUNT(*) FROM users WHERE role = 'Admin'")->fetchColumn();
    if ($admins <= 1) uupd_fail("Can't remove the last remaining admin.", $id);
}

$photoUpload = $_FILES['resident_id_photo'] ?? null;
$newPhoto = null;
if (is_array($photoUpload) && ($photoUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    [$newPhoto, $uploadError] = save_resident_id_photo($photoUpload);
    if ($uploadError !== null) uupd_fail($uploadError, $id);
}
$photoToSave = $role === 'Resident' ? ($newPhoto ?? $u['resident_id_photo']) : null;
$verifiedToSave = $role === 'Admin'
    ? (int)($u['verified'] ?? 0)
    : ($u['role'] === 'Resident' ? (int)$u['verified'] : 0);

try {
    q('UPDATE users SET name=?, username=?, email=?, contact=?, resident_id=?, resident_id_photo=?, role=?, verified=? WHERE id=?',
      [$name, $username, $email, $contact, $residentId, $photoToSave, $role, $verifiedToSave, $id]);
} catch (PDOException $e) {
    if (!delete_resident_id_photo($newPhoto)) {
        error_log('[EcoBantay] Could not remove an unlinked resident ID upload.');
    }
    if ($e->getCode() === '23000') uupd_fail('That username or email is already taken.', $id);
    throw $e;
}
if ($newPhoto !== null && !delete_resident_id_photo($u['resident_id_photo'])) {
    error_log('[EcoBantay] Could not remove a replaced resident ID upload.');
}
if ($role !== 'Resident' && !delete_resident_id_photo($u['resident_id_photo'])) {
    error_log('[EcoBantay] Could not remove a resident ID upload after role change.');
}
flash_set('toast', "$name updated.");
redirect('admin/users.php');
