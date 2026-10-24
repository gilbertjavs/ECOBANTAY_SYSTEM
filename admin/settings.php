<?php
require __DIR__ . '/../includes/init.php';
$user = require_role('Admin');

$sysName = q("SELECT setting_value FROM settings WHERE setting_key = 'system_name'")->fetchColumn() ?: 'EcoBantay';
$emergencyEmail = q("SELECT setting_value FROM settings WHERE setting_key = 'sos_emergency_email'")->fetchColumn() ?: '';
$areaCount = (int)q('SELECT COUNT(*) FROM areas')->fetchColumn();
$areas = q('SELECT area FROM areas ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
$schedule = get_collection_schedule();

admin_header('settings');
?>
    <div class="admin-topline"><h1>Settings</h1></div>
    <div class="card">
      <div class="detail-grid">
        <div><div class="dl">System Name</div><div class="dv"><?= esc($sysName) ?></div></div>
        <div><div class="dl">Coverage</div><div class="dv"><?= $areaCount ?> monitored areas</div></div>
        <div><div class="dl">Logged in as</div><div class="dv"><?= esc($user['name']) ?> (<?= esc($user['role']) ?>)</div></div>
        <div><div class="dl">Report Statuses</div><div class="dv"><?= esc(implode(', ', STATUSES)) ?></div></div>
      </div>
      <form method="post" action="<?= esc(url('actions/emergency-settings.php')) ?>" style="margin-top:20px;border-top:1px solid var(--line);padding-top:18px;">
        <?= csrf_field() ?>
        <div class="field">
          <label for="sos_emergency_email">SOS emergency email</label>
          <input id="sos_emergency_email" type="email" name="emergency_email" value="<?= esc($emergencyEmail) ?>" maxlength="254" placeholder="emergency@example.org">
          <p class="muted" style="font-size:12px;margin:6px 0 0;">SOS alerts also appear in EcoBantay admin notifications. Email sending requires PHP mail to be configured on the server. Leave blank to disable email alerts.</p>
        </div>
        <button class="btn small" type="submit">Save emergency email</button>
      </form>
      <div class="schedule-admin-block" style="margin-top:22px;">
        <h3 style="margin:0 0 12px; font-size:15px;">Collection Schedule</h3>
        <div class="schedule-list">
          <?php foreach ($schedule as $s): ?>
            <div class="schedule-item"><span><?= esc($s['day']) ?></span><strong><?= esc($s['area']) ?></strong><small><?= esc($s['waste_type']) ?> &bull; <?= esc($s['time']) ?></small></div>
          <?php endforeach; ?>
        </div>
      </div>
      <form method="post" action="<?= esc(url('actions/schedule-add.php')) ?>" style="margin-top:20px; border-top:1px solid var(--line); padding-top:18px;">
        <?= csrf_field() ?>
        <div class="field"><label>Day</label><select name="day"><?php foreach (DAYS_OF_WEEK as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Area</label><select name="area"><?php foreach ($areas as $a): ?><option value="<?= esc($a) ?>"><?= esc($a) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Waste Type</label><select name="wasteType"><?php foreach (WASTE_TYPES as $w): ?><option value="<?= esc($w) ?>"><?= esc($w) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Pickup Time</label><input type="text" name="time" value="7:00 AM" required maxlength="20"></div>
        <button class="btn small" type="submit">Add schedule</button>
      </form>
      <p class="muted" style="font-size:13px; margin-top:18px;">This keeps residents informed about the next scheduled pickup and helps the barangay plan collection efforts.</p>
    </div>
<?php admin_footer();
