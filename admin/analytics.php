<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');

$total = (int)q('SELECT COUNT(*) FROM reports')->fetchColumn();
$resolved = (int)q("SELECT COUNT(*) FROM reports WHERE status = 'Resolved'")->fetchColumn();
$thisMonth = (int)q("SELECT COUNT(*) FROM reports WHERE DATE_FORMAT(date_reported, '%Y-%m') = ?", [date('Y-m')])->fetchColumn();

$byProblem = array_fill_keys(PROBLEM_TYPES, 0);
foreach (q('SELECT problem_type AS k, COUNT(*) AS c FROM reports GROUP BY problem_type')->fetchAll() as $row) $byProblem[$row['k']] = (int)$row['c'];
$byStatus = array_fill_keys(STATUSES, 0);
foreach (q('SELECT status AS k, COUNT(*) AS c FROM reports GROUP BY status')->fetchAll() as $row) $byStatus[$row['k']] = (int)$row['c'];
$byArea = [];
foreach (q('SELECT area FROM areas ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $a) $byArea[$a] = 0;
foreach (q('SELECT location AS k, COUNT(*) AS c FROM reports GROUP BY location')->fetchAll() as $row) $byArea[$row['k']] = ($byArea[$row['k']] ?? 0) + (int)$row['c'];

$maxProblem = max(1, max($byProblem));
$maxStatus  = max(1, max($byStatus));
$maxArea    = max(1, $byArea ? max($byArea) : 1);

admin_header('analytics');
?>
    <div class="admin-topline"><h1>Waste Report Analytics</h1></div>
    <div class="stat-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:24px;">
      <div class="stat-card"><b><?= $total ?></b><span>All-time Reports</span></div>
      <div class="stat-card"><b><?= $thisMonth ?></b><span>Reports This Month</span></div>
    </div>
    <div class="two-col">
      <div class="card">
        <h3 style="margin-bottom:14px;font-size:15px;">Reports by Problem Type</h3>
        <?php foreach ($byProblem as $k => $v): ?>
          <div class="bar-row"><div class="blabel"><?= esc($k) ?></div><div class="bar-track"><div class="bar-fill" style="width:<?= $v / $maxProblem * 100 ?>%"></div></div><div class="bar-num"><?= $v ?></div></div>
        <?php endforeach; ?>
      </div>
      <div class="card">
        <h3 style="margin-bottom:14px;font-size:15px;">Reports by Status</h3>
        <?php foreach ($byStatus as $k => $v): ?>
          <div class="bar-row"><div class="blabel"><?= esc($k) ?></div><div class="bar-track"><div class="bar-fill" style="width:<?= $v / $maxStatus * 100 ?>%;background:var(--teal);"></div></div><div class="bar-num"><?= $v ?></div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card" style="margin-top:20px;">
      <h3 style="margin-bottom:14px;font-size:15px;">Reports by Location</h3>
      <?php foreach ($byArea as $k => $v): ?>
        <div class="bar-row"><div class="blabel"><?= esc($k) ?></div><div class="bar-track"><div class="bar-fill" style="width:<?= $v / $maxArea * 100 ?>%;background:var(--ochre);"></div></div><div class="bar-num"><?= $v ?></div></div>
      <?php endforeach; ?>
    </div>
<?php admin_footer();
