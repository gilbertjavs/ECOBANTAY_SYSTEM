<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$area   = trim((string)($_POST['area'] ?? ''));
$status = (string)($_POST['status'] ?? '');
$notes  = trim((string)($_POST['notes'] ?? ''));

if ($area === '')                          { flash_set('toast', 'Please enter an area name.'); redirect('admin/waste-status.php'); }
if (strlen($area) > 100)                   { flash_set('toast', 'The area name is too long.'); redirect('admin/waste-status.php'); }
if (!in_array($status, AREA_STATUSES, true)) { flash_set('toast', 'Please choose a valid status.'); redirect('admin/waste-status.php'); }
if (strlen($notes) > 255)                  { flash_set('toast', 'Notes are too long (max 255 characters).'); redirect('admin/waste-status.php'); }
if (q('SELECT 1 FROM areas WHERE LOWER(area) = LOWER(?)', [$area])->fetch()) { flash_set('toast', 'That area already exists.'); redirect('admin/waste-status.php'); }

q('INSERT INTO areas (area, status, admin_notes, last_updated) VALUES (?, ?, ?, ?)', [$area, $status, $notes, today_iso()]);
flash_set('toast', "$area added.");
redirect('admin/waste-status.php');
