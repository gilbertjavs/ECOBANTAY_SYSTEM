<?php
require __DIR__ . '/../includes/init.php';
$user = require_role('Resident');

$rows = q('SELECT * FROM reports WHERE user_id = ? ORDER BY date_reported DESC, id DESC', [$user['id']])->fetchAll();

// A resident can only open their own reports.
$detail = null;
$history = [];
$openId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($openId > 0) {
    $detail = q('SELECT * FROM reports WHERE id = ? AND user_id = ?', [$openId, $user['id']])->fetch() ?: null;
    if ($detail) {
        $history = q('SELECT * FROM report_history WHERE report_id = ? ORDER BY created_at, id', [$openId])->fetchAll();
    }
}

resident_header('myReports');
?>
    <div class="section-head"><h2>My Reports</h2></div>
    <?php if ($rows): ?>
    <div class="card" style="padding:0;overflow:auto;">
      <table>
        <thead><tr><th>Report</th><th>Problem</th><th>Waste Type</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): $href = 'my-reports.php?id=' . (int)$r['id']; ?>
            <tr>
              <td><?= row_link($href, '<b>#' . rid($r['id']) . '</b>') ?></td>
              <td><?= row_link($href, esc($r['problem_type'])) ?></td>
              <td><?= row_link($href, esc($r['waste_type'])) ?></td>
              <td><?= row_link($href, '<span class="' . pill_class($r['status']) . '">' . esc($r['status']) . '</span>') ?></td>
              <td><?= row_link($href, fmt_date($r['date_reported'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <div class="empty">No reports yet. Submit one from the dashboard.</div>
    <?php endif; ?>

    <?php if ($detail): $r = $detail; ?>
    <div style="margin-top:24px;" class="card">
      <div class="section-head"><h2>Report #<?= rid($r['id']) ?></h2><span class="<?= pill_class($r['status']) ?>"><?= esc($r['status']) ?></span></div>
      <div class="detail-grid">
        <div><div class="dl">Location</div><div class="dv"><?= esc($r['location']) ?></div></div>
        <div><div class="dl">Date Reported</div><div class="dv"><?= fmt_date($r['date_reported']) ?></div></div>
        <div><div class="dl">Assigned Staff</div><div class="dv"><?= !empty($r['assigned_to']) ? esc(q("SELECT name FROM users WHERE id = ? AND role = 'Admin'", [(int)$r['assigned_to']])->fetchColumn() ?: 'Unassigned') : 'Unassigned' ?></div></div>
        <div><div class="dl">Target Resolution Date</div><div class="dv"><?= !empty($r['due_date']) ? fmt_date($r['due_date']) : 'Not set' ?><?php if (!empty($r['due_date']) && $r['due_date'] < date('Y-m-d') && !in_array($r['status'], ['Resolved', 'Rejected'], true)): ?> <span class="pill Rejected">Overdue</span><?php endif; ?></div></div>
        <div><div class="dl">Waste Type</div><div class="dv"><?= esc($r['waste_type']) ?></div></div>
        <div><div class="dl">Problem Type</div><div class="dv"><?= esc($r['problem_type']) ?></div></div>
      </div>
      <?php if ($r['latitude'] !== null && $r['longitude'] !== null): ?>
        <div class="row-actions" style="margin:10px 0 16px;"><a class="btn secondary small" href="<?= esc(build_map_link($r['location'], $r['latitude'], $r['longitude'])) ?>" target="_blank" rel="noopener">Open map</a></div>
      <?php endif; ?>
      <div class="dl">Description</div>
      <p style="margin-top:4px;"><?= esc($r['description']) ?></p>
      <?php if ($r['photo']): ?><div class="photo-frame"><img src="<?= esc(url($r['photo'])) ?>" alt="Report photo"></div><?php endif; ?>
      <div class="dl" style="margin-top:16px;">Status History</div>
      <div class="status-flow"><?php foreach ($history as $h): ?><span class="reached"><?= esc($h['status']) ?> &middot; <?= fmt_date($h['created_at']) ?></span><?php endforeach; ?></div>
      <?php if ($r['action_taken'] !== '' && $r['action_taken'] !== null): ?><div class="info-box"><b>Action taken:</b> <?= esc($r['action_taken']) ?></div><?php endif; ?>
      <a class="btn ghost small" style="margin-top:16px;" href="my-reports.php">Close</a>
    </div>
    <?php endif; ?>
<?php resident_footer();
