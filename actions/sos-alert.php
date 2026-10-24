<?php
require __DIR__ . '/../includes/init.php';
require_post();
$user = require_role('Resident');

$latitude = parse_coord($_POST['latitude'] ?? '', -90, 90);
$longitude = parse_coord($_POST['longitude'] ?? '', -180, 180);
if ($latitude === false || $longitude === false || $latitude === null || $longitude === null) {
    flash_set('toast', 'Choose a valid location on the map or use your GPS before sending an SOS alert.');
    redirect('resident/dashboard.php');
}

$adminCount = (int)q("SELECT COUNT(*) FROM users WHERE role = 'Admin'")->fetchColumn();
$emergencyEmail = trim((string)q("SELECT setting_value FROM settings WHERE setting_key = 'sos_emergency_email'")->fetchColumn());
if ($emergencyEmail !== '' && !filter_var($emergencyEmail, FILTER_VALIDATE_EMAIL)) {
    error_log('[EcoBantay] Invalid SOS emergency email setting; email alert was skipped.');
    $emergencyEmail = '';
}

$coordinates = number_format((float)$latitude, 7, '.', '') . ', ' . number_format((float)$longitude, 7, '.', '');
$mapUrl = 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string)$latitude)
    . '&mlon=' . rawurlencode((string)$longitude) . '#map=17/' . rawurlencode((string)$latitude)
    . '/' . rawurlencode((string)$longitude);
$adminNotified = $adminCount > 0;
if ($adminNotified) {
    notify_admins('SOS alert from ' . (string)$user['name'] . ' at ' . $coordinates . '.');
}

$emailQueued = null;
if ($emergencyEmail !== '') {
    $subject = 'EcoBantay SOS alert';
    $body = "An SOS alert was submitted through EcoBantay.\n\n"
        . 'Name: ' . (string)$user['name'] . "\n"
        . 'Coordinates: ' . $coordinates . "\n"
        . 'Map: ' . $mapUrl . "\n"
        . 'Time: ' . date('Y-m-d H:i:s T') . "\n\n"
        . "This email was generated automatically. Please follow your local emergency response procedures.";
    $emailQueued = mail($emergencyEmail, $subject, $body, 'Content-Type: text/plain; charset=UTF-8');
    if (!$emailQueued) {
        error_log('[EcoBantay] PHP mail() did not accept the SOS emergency email.');
    }
}

if (!$adminNotified && $emailQueued !== true) {
    flash_set('toast', 'SOS alert could not be delivered. No EcoBantay admin account is available and emergency email delivery is not configured or failed.');
} elseif ($adminNotified && $emailQueued === true) {
    flash_set('toast', 'SOS alert sent to EcoBantay admins; the emergency email was accepted for delivery by the server.');
} elseif ($adminNotified && $emailQueued === false) {
    flash_set('toast', 'SOS alert sent to EcoBantay admins, but the emergency email could not be queued. Please contact the barangay directly if this is urgent.');
} elseif ($adminNotified) {
    flash_set('toast', 'SOS alert sent to EcoBantay admins. No emergency email recipient is configured.');
} else {
    flash_set('toast', 'The emergency email was accepted for delivery by the server. No EcoBantay admin account is available.');
}

redirect('resident/dashboard.php');
