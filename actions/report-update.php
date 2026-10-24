<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$id          = (int)($_POST['id'] ?? 0);
$location    = trim((string)($_POST['location'] ?? ''));
$wasteType   = (string)($_POST['wasteType'] ?? '');
$problemType = (string)($_POST['problemType'] ?? '');
$description = trim((string)($_POST['description'] ?? ''));
$latitude = parse_coord($_POST['latitude'] ?? '', -90, 90);
$longitude = parse_coord($_POST['longitude'] ?? '', -180, 180);
$newStatus   = (string)($_POST['status'] ?? '');
$adminNotes  = trim((string)($_POST['adminNotes'] ?? ''));
$actionTaken = trim((string)($_POST['actionTaken'] ?? ''));
$assignedToInput = (string)($_POST['assignedTo'] ?? '');
$dueDateInput = trim((string)($_POST['dueDate'] ?? ''));

$rs = (string)($_POST['return_status'] ?? 'all');
$qs = ['id' => $id];
if (($_POST['return_q'] ?? '') !== '') $qs['q'] = (string)$_POST['return_q'];
if ($rs !== 'all' && in_array($rs, STATUSES, true)) $qs['status'] = $rs;
$back = 'admin/reports.php?' . http_build_query($qs);

$r = q('SELECT * FROM reports WHERE id = ?', [$id])->fetch();
if (!$r) { flash_set('toast', 'That report no longer exists.'); redirect('admin/reports.php'); }

function upd_fail(string $msg, string $back) { flash_set('toast', $msg); redirect($back); }

$locationOk = ($location === $r['location']) || q('SELECT 1 FROM areas WHERE area = ?', [$location])->fetch();
if ($location === '' || !$locationOk)               upd_fail('Please select a valid location.', $back);
if (!in_array($wasteType, WASTE_TYPES, true))       upd_fail('Please choose a valid waste type.', $back);
if (!in_array($problemType, PROBLEM_TYPES, true))   upd_fail('Please choose a valid problem type.', $back);
if (!in_array($newStatus, STATUSES, true))          upd_fail('Please choose a valid status.', $back);
if ($description === '' || strlen($description) > 2000) upd_fail('Please enter a description (max 2000 characters).', $back);
if ($latitude === false || $longitude === false) upd_fail('Please enter valid coordinates.', $back);
if (($latitude === null) !== ($longitude === null)) upd_fail('Please enter both coordinates, or leave both blank.', $back);
if (strlen($adminNotes) > 2000 || strlen($actionTaken) > 2000) upd_fail('Notes are too long (max 2000 characters).', $back);

$assignedTo = null;
if ($assignedToInput !== '') {
    if (!ctype_digit($assignedToInput) || !q("SELECT 1 FROM users WHERE id = ? AND role = 'Admin'", [(int)$assignedToInput])->fetchColumn()) {
        upd_fail('Please choose a valid staff member.', $back);
    }
    $assignedTo = (int)$assignedToInput;
}

$dueDate = null;
if ($dueDateInput !== '') {
    $parsedDueDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDateInput);
    if (!$parsedDueDate || $parsedDueDate->format('Y-m-d') !== $dueDateInput) {
        upd_fail('Please enter a valid target date.', $back);
    }
    $dueDate = $dueDateInput;
}

$statusChanged = $newStatus !== $r['status'];
$assignmentChanged = (int)($r['assigned_to'] ?? 0) !== (int)($assignedTo ?? 0);
$dueDateChanged = ($r['due_date'] ?? null) !== $dueDate;

$pdo = db();
try {
    $pdo->beginTransaction();
    q('UPDATE reports SET location=?, latitude=?, longitude=?, waste_type=?, problem_type=?, description=?, status=?, assigned_to=?, due_date=?, admin_notes=?, action_taken=? WHERE id=?',
      [$location, $latitude, $longitude, $wasteType, $problemType, $description, $newStatus, $assignedTo, $dueDate, $adminNotes, $actionTaken, $id]);
    if ($statusChanged) {
        q('INSERT INTO report_history (report_id, status, note) VALUES (?, ?, ?)',
          [$id, $newStatus, $adminNotes !== '' ? $adminNotes : $newStatus . ' recorded.']);
        if ($r['user_id']) {
            add_notification((int)$r['user_id'], 'Your report #' . rid($id) . " ($problemType) has been marked $newStatus.");
        }
    }
    if ($assignmentChanged && $assignedTo !== null) {
        add_notification($assignedTo, 'Report #' . rid($id) . " ($problemType) has been assigned to you.");
    }
    if ($r['user_id'] && ($assignmentChanged || $dueDateChanged)) {
        $staffName = $assignedTo === null ? 'Unassigned' : (string)q('SELECT name FROM users WHERE id = ?', [$assignedTo])->fetchColumn();
        $targetDate = $dueDate === null ? 'not set' : fmt_date($dueDate);
        add_notification((int)$r['user_id'], 'Report #' . rid($id) . " assignment updated: $staffName; target date $targetDate.");
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}

flash_set('toast', 'Report #' . rid($id) . ' updated.');
redirect($back);
