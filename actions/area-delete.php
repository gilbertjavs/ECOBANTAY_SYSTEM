<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$id = (int)($_POST['id'] ?? 0);
$a = q('SELECT area FROM areas WHERE id = ?', [$id])->fetch();
if ($a) {
    q('DELETE FROM areas WHERE id = ?', [$id]);
    flash_set('toast', $a['area'] . ' removed.');
} else {
    flash_set('toast', 'That area no longer exists.');
}
redirect('admin/waste-status.php');
