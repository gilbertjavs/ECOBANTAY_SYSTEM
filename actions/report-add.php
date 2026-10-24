<?php
require __DIR__ . '/../includes/init.php';
require_post();
$admin = require_role('Admin');

$userId      = (int)($_POST['userId'] ?? 0);
$location    = trim((string)($_POST['location'] ?? ''));
$wasteType   = (string)($_POST['wasteType'] ?? '');
$problemType = (string)($_POST['problemType'] ?? '');
$description = trim((string)($_POST['description'] ?? ''));
$latitude = parse_coord($_POST['latitude'] ?? '', -90, 90);
$longitude = parse_coord($_POST['longitude'] ?? '', -180, 180);

function add_fail(string $msg)
{
    flash_set('toast', $msg);
    redirect('admin/reports.php');
}

if ($location === '' || !q('SELECT 1 FROM areas WHERE area = ?', [$location])->fetch()) add_fail('Please select a location.');
if (!in_array($wasteType, WASTE_TYPES, true))     add_fail('Please choose a valid waste type.');
if (!in_array($problemType, PROBLEM_TYPES, true)) add_fail('Please choose a valid problem type.');
if ($description === '' || strlen($description) > 2000) add_fail('Please enter a description (max 2000 characters).');
if ($latitude === false || $longitude === false) add_fail('Please enter valid coordinates.');
if (($latitude === null) !== ($longitude === null)) add_fail('Please enter both coordinates, or leave both blank.');

$resident = null;
if ($userId > 0) {
    $resident = q("SELECT id, name FROM users WHERE id = ? AND role = 'Resident'", [$userId])->fetch() ?: null;
    if (!$resident) add_fail('Please choose a valid resident.');
}
$reporter = $resident ? $resident['name'] : $admin['name'] . ' (staff)';

$pdo = db();
try {
    $pdo->beginTransaction();
    q('INSERT INTO reports (user_id, reporter, location, waste_type, problem_type, description, photo, latitude, longitude, date_reported, status, admin_notes, action_taken)
       VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)',
      [$resident ? $resident['id'] : null, $reporter, $location, $wasteType, $problemType, $description, $latitude, $longitude, today_iso(), 'Pending', '', '']);
    $id = (int)$pdo->lastInsertId();
    q('INSERT INTO report_history (report_id, status, note) VALUES (?, ?, ?)', [$id, 'Pending', 'Report filed by staff.']);
    if ($resident) add_notification((int)$resident['id'], 'A report #' . rid($id) . " ($problemType) was filed on your behalf at $location.");
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}

flash_set('toast', 'Report #' . rid($id) . ' added.');
redirect('admin/reports.php?id=' . $id);
