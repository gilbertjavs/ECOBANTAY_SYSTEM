<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');
$list = q('SELECT n.*, u.name AS recipient FROM notifications n LEFT JOIN users u ON u.id = n.user_id ORDER BY n.`date` DESC, n.id DESC')->fetchAll();

admin_header('notifications');
?>
    <div class="admin-topline"><h1>Notifications</h1></div>
    <div class="card" style="padding:0;">
      <table>
        <thead><tr><th>Recipient</th><th>Message</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($list as $n): ?>
            <tr><td><b><?= esc($n['recipient'] ?? 'Unknown') ?></b></td><td><?= esc($n['message']) ?></td><td><?= fmt_date($n['date']) ?></td><td><span class="<?= $n['is_read'] ? 'pill Good' : 'pill Pending' ?>"><?= $n['is_read'] ? 'Read' : 'Unread' ?></span></td></tr>
          <?php endforeach; ?>
          <?php if (!$list): ?><tr><td colspan="4" class="empty">No notifications sent yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
<?php admin_footer();
