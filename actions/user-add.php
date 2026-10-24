<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$name     = trim((string)($_POST['name'] ?? ''));
$username = trim((string)($_POST['username'] ?? ''));
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$role     = (string)($_POST['role'] ?? '');
$contact  = trim((string)($_POST['contact'] ?? ''));
$residentId = trim((string)($_POST['resident_id'] ?? ''));

function uadd_fail(string $m) { flash_set('toast', $m); redirect('admin/users.php'); }

if ($password === '') uadd_fail('Please complete all required fields.');
if ($err = user_field_error($name, $username, $email, $contact)) uadd_fail($err);
if (!in_array($role, USER_ROLES, true)) uadd_fail('Please choose a valid role.');
if ($role === 'Resident' && ($err = resident_id_error($residentId))) uadd_fail($err);
if ($role !== 'Resident' && strlen($residentId) > 50) uadd_fail('Resident ID is too long (max 50 characters).');
if (strlen($password) < 6 || strlen($password) > 72) uadd_fail('Password must be between 6 and 72 characters.');
if ($err = user_duplicate_error($username, $email, null, 'registered')) uadd_fail($err);

try {
    q('INSERT INTO users (name, username, email, password, role, contact, resident_id, verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
      [$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $contact, $residentId]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') uadd_fail('That username or email is already registered.');
    throw $e;
}
flash_set('toast', "$name added.");
redirect('admin/users.php');
