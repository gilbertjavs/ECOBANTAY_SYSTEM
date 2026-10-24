<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');

$s = q("SELECT COUNT(*) AS total,
               SUM(status = 'Pending')  AS pending,
               SUM(status = 'Verified') AS verified,
               SUM(status = 'Resolved') AS resolved
        FROM reports")->fetch();
$activeAreas = (int)q("SELECT COUNT(*) FROM areas WHERE status <> 'Good'")->fetchColumn();
$recent = q('SELECT * FROM reports ORDER BY date_reported DESC, id DESC LIMIT 6')->fetchAll();

admin_header('dashboard', true);
?>
    <div class="admin-topline"><h1>Overview</h1></div>
    <div class="stat-grid">
      <div class="stat-card"><b><?= (int)$s['total'] ?></b><span>Total Reports</span></div>
      <div class="stat-card"><b><?= (int)$s['pending'] ?></b><span>Pending</span></div>
      <div class="stat-card"><b><?= (int)$s['verified'] ?></b><span>Verified</span></div>
      <div class="stat-card"><b><?= (int)$s['resolved'] ?></b><span>Resolved</span></div>
      <div class="stat-card"><b><?= $activeAreas ?></b><span>Active Areas</span></div>
    </div>
    <div class="section-head"><h2>Recent Reports</h2><a href="reports.php" style="font-size:13px;font-weight:600;color:var(--forest);">Open Reports &rarr;</a></div>
    <div class="card" style="padding:0;">
      <table>
        <thead><tr><th>Report</th><th>Problem</th><th>Location</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): $href = 'reports.php?id=' . (int)$r['id']; ?>
          <tr>
            <td><?= row_link($href, '<b>#' . rid($r['id']) . '</b>') ?></td>
            <td><?= row_link($href, esc($r['problem_type'])) ?></td>
            <td><?= row_link($href, esc($r['location'])) ?></td>
            <td><?= row_link($href, '<span class="' . pill_class($r['status']) . '">' . esc($r['status']) . '</span>') ?></td>
            <td><?= row_link($href, fmt_date($r['date_reported'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php admin_footer();
