<?php
require __DIR__ . '/../includes/init.php';
require_post();

$name     = trim((string)($_POST['name'] ?? ''));
$username = trim((string)($_POST['username'] ?? ''));
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$contact  = trim((string)($_POST['contact'] ?? ''));
$residentId = trim((string)($_POST['resident_id'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$privacy  = !empty($_POST['privacy_accept']);

function reg_fail(string $msg, array $old)
{
    set_old($old);
    flash_set('auth_error', $msg);
    redirect('register.php');
}
$old = ['name' => $name, 'username' => $username, 'email' => $email, 'contact' => $contact, 'resident_id' => $residentId];

if ($password === '') reg_fail('Please complete all required fields.', $old);
if (!$privacy) reg_fail('You must accept the privacy notice before creating an account.', $old);
if ($err = user_field_error($name, $username, $email, $contact)) reg_fail($err, $old);
if ($err = resident_id_error($residentId)) reg_fail($err, $old);
if (strlen($password) < 6 || strlen($password) > 72) reg_fail('Password must be between 6 and 72 characters.', $old);
if ($err = user_duplicate_error($username, $email, null, 'registered')) reg_fail($err, $old);

$upload = $_FILES['resident_id_photo'] ?? null;
[$residentIdPhoto, $uploadError] = save_resident_id_photo(is_array($upload) ? $upload : null);
if ($uploadError !== null) reg_fail($uploadError, $old);

try {
    q('INSERT INTO users (name, username, email, password, role, contact, resident_id, resident_id_photo, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)',
      [$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), 'Resident', $contact, $residentId, $residentIdPhoto]);
} catch (PDOException $e) {
    if (!delete_resident_id_photo($residentIdPhoto)) {
        error_log('[EcoBantay] Could not remove an unlinked resident ID upload.');
    }
    if ($e->getCode() === '23000') reg_fail('That username or email is already registered.', $old);
    throw $e;
}

$userId = (int)db()->lastInsertId();
session_regenerate_id(true);
$_SESSION['csrf'] = bin2hex(random_bytes(32));
$_SESSION['pending_verification_user_id'] = $userId;
redirect('verification-pending.php');
