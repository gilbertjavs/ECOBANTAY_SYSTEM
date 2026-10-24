<?php
require __DIR__ . '/../includes/init.php';
require_role('Resident');

$areas = q('SELECT * FROM areas ORDER BY id')->fetchAll();
$overall = 'Good';
$lastUpdated = '0000-00-00';
foreach ($areas as $a) {
    if ($a['status'] === 'Needs Attention') $overall = 'Needs Attention';
    elseif ($a['status'] === 'Monitor' && $overall !== 'Needs Attention') $overall = 'Monitor';
    if ($a['last_updated'] > $lastUpdated) $lastUpdated = $a['last_updated'];
}

resident_header('wasteStatus');
?>
    <div class="section-head"><h2>Community Waste Status</h2></div>
    <div class="card" style="margin-bottom:20px;">
      <div class="dl">Overall Waste Status</div>
      <div style="display:flex;align-items:center;gap:10px;margin:8px 0 4px;">
        <span class="<?= pill_class($overall) ?>" style="font-size:13px;padding:5px 14px;"><?= esc($overall) ?></span>
      </div>
      <div class="muted" style="font-size:12.5px;">Last updated: <?= fmt_date($lastUpdated) ?></div>
    </div>
    <div class="card" style="padding:0;">
      <table>
        <thead><tr><th>Area</th><th>Status</th><th>Notes</th><th>Updated</th></tr></thead>
        <tbody>
          <?php foreach ($areas as $a): ?>
            <tr>
              <td><b><?= esc($a['area']) ?></b></td>
              <td><span class="<?= pill_class($a['status']) ?>"><?= esc($a['status']) ?></span></td>
              <td class="muted"><?= $a['admin_notes'] !== '' && $a['admin_notes'] !== null ? esc($a['admin_notes']) : '&mdash;' ?></td>
              <td><?= fmt_date($a['last_updated']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php resident_footer();
