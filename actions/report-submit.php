<?php
require __DIR__ . '/../includes/init.php';
require_post();
$user = require_role('Resident');

$location    = trim((string)($_POST['location'] ?? ''));
$wasteType   = (string)($_POST['wasteType'] ?? '');
$problemType = (string)($_POST['problemType'] ?? '');
$description = trim((string)($_POST['description'] ?? ''));
$lat = parse_coord($_POST['latitude'] ?? '', -90, 90);
$lng = parse_coord($_POST['longitude'] ?? '', -180, 180);

$old = ['location' => $location, 'wasteType' => $wasteType, 'problemType' => $problemType, 'description' => $description,
        'latitude' => trim((string)($_POST['latitude'] ?? '')), 'longitude' => trim((string)($_POST['longitude'] ?? ''))];

function submit_fail(string $msg, array $old)
{
    set_old($old);
    flash_set('toast', $msg);
    redirect('resident/report.php');
}

if ($location === '' || !q('SELECT 1 FROM areas WHERE area = ?', [$location])->fetch()) submit_fail('Please select a location.', $old);
if (!in_array($wasteType, WASTE_TYPES, true))     submit_fail('Please choose a valid waste type.', $old);
if (!in_array($problemType, PROBLEM_TYPES, true)) submit_fail('Please choose a valid problem type.', $old);
if ($description === '')                          submit_fail('Please complete all required fields.', $old);
if (strlen($description) > 2000)                  submit_fail('The description is too long (max 2000 characters).', $old);
if ($lat === false || $lng === false)             submit_fail('Please enter valid coordinates.', $old);
if (($lat === null) !== ($lng === null))          submit_fail('Please enter both latitude and longitude, or leave both blank.', $old);

[$photo, $photoErr] = save_report_photo($_FILES['photo'] ?? null);
if ($photoErr) submit_fail($photoErr, $old);

$pdo = db();
try {
    $pdo->beginTransaction();
    q('INSERT INTO reports (user_id, reporter, location, waste_type, problem_type, description, photo, latitude, longitude, date_reported, status, admin_notes, action_taken)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
      [$user['id'], $user['name'], $location, $wasteType, $problemType, $description, $photo, $lat, $lng, today_iso(), 'Pending', '', '']);
    $id = (int)$pdo->lastInsertId();
    q('INSERT INTO report_history (report_id, status, note) VALUES (?, ?, ?)', [$id, 'Pending', 'Report submitted.']);
    notify_admins('New report #' . rid($id) . " ($problemType) submitted at $location.");
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    delete_report_photo($photo);
    throw $e;
}

flash_set('toast', 'Report #' . rid($id) . ' submitted.');
redirect('resident/my-reports.php?id=' . $id);
