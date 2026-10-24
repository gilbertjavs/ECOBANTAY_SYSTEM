<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$email = trim((string)($_POST['emergency_email'] ?? ''));
if ($email !== '' && (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    flash_set('toast', 'Please enter a valid emergency email address or leave the field blank.');
    redirect('admin/settings.php');
}

q("INSERT INTO settings (setting_key, setting_value) VALUES ('sos_emergency_email', ?)
   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$email]);
flash_set('toast', $email === '' ? 'Emergency email alerts are disabled.' : 'SOS emergency email saved.');
redirect('admin/settings.php');
