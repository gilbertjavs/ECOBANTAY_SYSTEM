<?php
require __DIR__ . '/../includes/init.php';
require_post();
require_role('Admin');

$id = (int)($_POST['id'] ?? 0);
$r = q('SELECT id, photo FROM reports WHERE id = ?', [$id])->fetch();
if ($r) {
    q('DELETE FROM reports WHERE id = ?', [$id]);          // report_history rows are removed by ON DELETE CASCADE
    delete_report_photo($r['photo']);
    flash_set('toast', 'Report #' . rid($id) . ' deleted.');
} else {
    flash_set('toast', 'That report no longer exists.');
}
redirect('admin/reports.php');
