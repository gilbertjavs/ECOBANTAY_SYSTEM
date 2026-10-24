<?php
require __DIR__ . '/../includes/init.php';
$user = require_role('Resident');
$list = my_notifications((int)$user['id']);

resident_header('notifications');
?>
    <div class="section-head"><h2>Notifications</h2>
      <?php if ($list): ?>
        <form method="post" action="<?= esc(url('actions/notification-read.php')) ?>" style="margin:0;"><?= csrf_field() ?><button class="btn ghost small" type="submit">Mark all read</button></form>
      <?php endif; ?>
    </div>
    <div class="card">
      <?php if ($list): foreach ($list as $n): ?>
        <div class="report-row">
          <div>
            <div style="<?= $n['is_read'] ? '' : 'font-weight:600;' ?>"><?= esc($n['message']) ?></div>
            <div class="rmeta"><?= fmt_date($n['date']) ?></div>
          </div>
          <?= $n['is_read'] ? '' : '<span class="pill Pending">New</span>' ?>
        </div>
      <?php endforeach; else: ?>
        <div class="empty">You have no notifications yet.</div>
      <?php endif; ?>
    </div>
<?php resident_footer();
