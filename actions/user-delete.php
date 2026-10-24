<?php
require __DIR__ . '/../includes/init.php';
require_post();
$me = require_role('Admin');

$id = (int)($_POST['id'] ?? 0);
$u = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
if (!$u) { flash_set('toast', 'That user no longer exists.'); redirect('admin/users.php'); }

if ((int)$u['id'] === (int)$me['id']) { flash_set('toast', "You can't delete your own account."); redirect('admin/users.php'); }
if ($u['role'] === 'Admin' && (int)q("SELECT COUNT(*) FROM users WHERE role = 'Admin'")->fetchColumn() <= 1) {
    flash_set('toast', "Can't delete the last remaining admin.");
    redirect('admin/users.php');
}

q('DELETE FROM live_location_shares WHERE user_id = ?', [$id]);
q('DELETE FROM users WHERE id = ?', [$id]);      // their reports stay (user_id -> NULL); notifications are removed
if (!delete_resident_id_photo($u['resident_id_photo'] ?? null)) {
    error_log('[EcoBantay] Could not remove a resident ID upload after account deletion.');
}
flash_set('toast', $u['name'] . ' deleted.');
redirect('admin/users.php');
