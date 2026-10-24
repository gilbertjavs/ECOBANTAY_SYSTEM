<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$id     = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$notes  = trim((string)($_POST['notes'] ?? ''));

$a = q('SELECT * FROM areas WHERE id = ?', [$id])->fetch();
if (!$a)                                   { flash_set('toast', 'That area no longer exists.'); redirect('admin/waste-status.php'); }
if (!in_array($status, AREA_STATUSES, true)) { flash_set('toast', 'Please choose a valid status.'); redirect('admin/waste-status.php'); }
if (strlen($notes) > 255)                  { flash_set('toast', 'Notes are too long (max 255 characters).'); redirect('admin/waste-status.php'); }

q('UPDATE areas SET status = ?, admin_notes = ?, last_updated = ? WHERE id = ?', [$status, $notes, today_iso(), $id]);
flash_set('toast', $a['area'] . ' status updated.');
redirect('admin/waste-status.php');
