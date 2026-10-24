<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');
$areas = q('SELECT * FROM areas ORDER BY id')->fetchAll();

admin_header('wasteStatus');
?>
    <div class="admin-topline"><h1>Waste Status Management</h1></div>
    <details class="details-toggle">
      <summary>&#10010; Add Area</summary>
      <div class="card">
        <form method="post" action="<?= esc(url('actions/area-add.php')) ?>">
          <?= csrf_field() ?>
          <div class="field"><label>Area Name</label><input type="text" name="area" required placeholder="e.g. Purok 4"></div>
          <div class="field"><label>Status</label>
            <select name="status"><?php foreach (AREA_STATUSES as $s): ?><option value="<?= esc($s) ?>"><?= esc($s) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label>Notes</label><input type="text" name="notes" placeholder="Optional notes"></div>
          <button class="btn small" type="submit">Add Area</button>
        </form>
      </div>
    </details>
    <div class="card">
      <div class="area-edit-row" style="font-weight:700;color:var(--ink-soft);font-size:11.5px;text-transform:uppercase;border-bottom:2px solid var(--line);">
        <div>Area</div><div>Status</div><div>Notes</div><div></div>
      </div>
      <?php foreach ($areas as $a): ?>
        <form class="area-edit-row" method="post" action="<?= esc(url('actions/area-update.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <div><b><?= esc($a['area']) ?></b><div class="rmeta">Updated <?= fmt_date($a['last_updated']) ?></div></div>
          <select name="status"><?php foreach (AREA_STATUSES as $s): ?><option value="<?= esc($s) ?>" <?= $s === $a['status'] ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach; ?></select>
          <input type="text" name="notes" value="<?= esc($a['admin_notes']) ?>" placeholder="Notes" maxlength="255">
          <div class="row-actions">
            <button class="btn secondary small" type="submit">Save</button>
            <button class="edit-toggle" style="color:var(--rust);" type="submit" form="delArea<?= (int)$a['id'] ?>" onclick="return confirm('Delete area &quot;<?= esc(addslashes($a['area'])) ?>&quot;? This cannot be undone.');">Delete</button>
          </div>
        </form>
        <form id="delArea<?= (int)$a['id'] ?>" method="post" action="<?= esc(url('actions/area-delete.php')) ?>" style="display:none;"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"></form>
      <?php endforeach; ?>
    </div>
<?php admin_footer();
