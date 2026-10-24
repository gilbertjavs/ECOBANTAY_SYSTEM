<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$day   = (string)($_POST['day'] ?? '');
$area  = (string)($_POST['area'] ?? '');
$waste = (string)($_POST['wasteType'] ?? '');
$time  = trim((string)($_POST['time'] ?? ''));

if (!in_array($day, DAYS_OF_WEEK, true))   { flash_set('toast', 'Please choose a valid day.'); redirect('admin/settings.php'); }
if (!q('SELECT 1 FROM areas WHERE area = ?', [$area])->fetch()) { flash_set('toast', 'Please choose a valid area.'); redirect('admin/settings.php'); }
if (!in_array($waste, WASTE_TYPES, true))  { flash_set('toast', 'Please choose a valid waste type.'); redirect('admin/settings.php'); }
if ($time === '' || strlen($time) > 20)    { flash_set('toast', 'Please enter a pickup time.'); redirect('admin/settings.php'); }

q('INSERT INTO collection_schedule (day, area, waste_type, time) VALUES (?, ?, ?, ?)', [$day, $area, $waste, $time]);
flash_set('toast', 'Collection schedule updated.');
redirect('admin/settings.php');
