<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');

$id = (int)($_GET['id'] ?? 0);
$user = $id > 0
    ? q("SELECT resident_id_photo FROM users WHERE id = ? AND role = 'Resident'", [$id])->fetch()
    : false;
$path = $user ? resident_id_photo_file($user['resident_id_photo']) : null;

if ($path === null || !is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('ID photo not found.');
}

$image = @getimagesize($path);
$mime = $image['mime'] ?? '';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit('ID photo not found.');
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="resident-id.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
readfile($path);
