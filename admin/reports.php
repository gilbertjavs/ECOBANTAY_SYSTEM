<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');

/* ---- search + filter (was DB.reports.filter(...) in JavaScript) ---- */
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? 'all');
if ($statusFilter !== 'all' && !in_array($statusFilter, STATUSES, true)) $statusFilter = 'all';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "CONCAT_WS(' ', CONCAT('R', LPAD(id, 3, '0')), problem_type, location, reporter, description) LIKE ?";
    $params[] = '%' . like_escape($search) . '%';
}
if ($statusFilter !== 'all') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM reports' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY date_reported DESC, id DESC';
$filtered = q($sql, $params)->fetchAll();
$mapMarkers = [];
foreach ($filtered as $mapReport) {
    $latitude = parse_coord($mapReport['latitude'] ?? '', -90, 90);
    $longitude = parse_coord($mapReport['longitude'] ?? '', -180, 180);
    if ($latitude === null || $longitude === null || $latitude === false || $longitude === false) continue;
    $mapMarkers[] = [
        'lat' => $latitude,
        'lng' => $longitude,
        'report' => rid((int)$mapReport['id']),
        'problem' => (string)$mapReport['problem_type'],
        'location' => (string)$mapReport['location'],
        'status' => (string)$mapReport['status'],
        'photo' => !empty($mapReport['photo']) ? url((string)$mapReport['photo']) : '',
        'href' => report_link((int)$mapReport['id'], $search, $statusFilter),
    ];
}

$activeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$activeId && $filtered) $activeId = (int)$filtered[0]['id'];
if (!$activeId) $activeId = (int)q('SELECT id FROM reports ORDER BY date_reported DESC, id DESC LIMIT 1')->fetchColumn();

$r = $activeId ? (q('SELECT * FROM reports WHERE id = ?', [$activeId])->fetch() ?: null) : null;
$history = $r ? q('SELECT * FROM report_history WHERE report_id = ? ORDER BY created_at, id', [$r['id']])->fetchAll() : [];

$residents = q("SELECT id, name FROM users WHERE role = 'Resident' ORDER BY name")->fetchAll();
$staff = q("SELECT id, name FROM users WHERE role = 'Admin' ORDER BY name")->fetchAll();
$staffById = array_column($staff, 'name', 'id');
$areas = q('SELECT area FROM areas ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

function report_link(int $id, string $search, string $status): string
{
    $qs = ['id' => $id];
    if ($search !== '') $qs['q'] = $search;
    if ($status !== 'all') $qs['status'] = $status;
    return url('admin/reports.php?' . http_build_query($qs)) . '#reportDetail';
}

admin_header('reports');
?>
    <div class="admin-topline"><h1>Reports</h1></div>
    <div class="card" style="margin-bottom:16px;">
      <form method="get" action="reports.php" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:0;">
        <input name="q" value="<?= esc($search) ?>" type="text" placeholder="Search reports..." style="flex:1; min-width:180px;">
        <select name="status" style="min-width:150px;">
          <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All statuses</option>
          <?php foreach (STATUSES as $s): ?><option value="<?= esc($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn small">Search</button>
        <a class="btn secondary small" href="<?= esc(url('actions/report-export.php')) ?>">Export CSV</a>
      </form>
    </div>
    <section class="card report-map-overview" aria-labelledby="report-map-title">
      <div class="section-head">
        <div>
          <h2 id="report-map-title">Report Map</h2>
          <p class="muted">Showing <?= count($mapMarkers) ?> report<?= count($mapMarkers) === 1 ? '' : 's' ?> with coordinates in the current results.</p>
        </div>
      </div>
      <div id="reportMap" class="report-map-canvas" data-markers="<?= esc(json_encode($mapMarkers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]') ?>"></div>
      <div class="report-map-legend" aria-label="Map marker status legend">
        <span><i class="map-dot pending"></i>Pending</span>
        <span><i class="map-dot investigating"></i>Investigating</span>
        <span><i class="map-dot verified"></i>Verified</span>
        <span><i class="map-dot action-taken"></i>Action Taken</span>
        <span><i class="map-dot resolved"></i>Resolved</span>
        <span><i class="map-dot rejected"></i>Rejected</span>
      </div>
      <p id="reportMapStatus" class="muted report-map-note" role="status">Map data is loading. Map tiles are provided by OpenStreetMap.</p>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= esc(url('assets/report-map.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/report-map.js') ?>"></script>
    <script defer src="<?= esc(url('assets/report-admin-location-picker.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/report-admin-location-picker.js') ?>"></script>
    <details class="details-toggle">
      <summary>&#10010; New Report</summary>
      <div class="card" style="max-width:640px;">
        <form method="post" action="<?= esc(url('actions/report-add.php')) ?>">
          <?= csrf_field() ?>
          <div class="field"><label>Reporter</label>
            <select name="userId">
              <option value="">Filed by staff (no resident)</option>
              <?php foreach ($residents as $u): ?><option value="<?= (int)$u['id'] ?>"><?= esc($u['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Location / Area</label>
            <select name="location" required>
              <option value="">Select Barangay / Street / Purok</option>
              <?php foreach ($areas as $a): ?><option value="<?= esc($a) ?>"><?= esc($a) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Waste Type</label>
            <select name="wasteType"><?php foreach (WASTE_TYPES as $w): ?><option value="<?= esc($w) ?>"><?= esc($w) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label>Problem Type</label>
            <select name="problemType"><?php foreach (PROBLEM_TYPES as $p): ?><option value="<?= esc($p) ?>"><?= esc($p) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label>Description</label><textarea name="description" required placeholder="Describe the situation..."></textarea></div>
          <div class="field">
            <label>Coordinates (optional)</label>
            <div class="report-coordinate-inputs">
              <input id="newReportLatitude" type="number" step="any" name="latitude" min="-90" max="90" placeholder="Latitude">
              <input id="newReportLongitude" type="number" step="any" name="longitude" min="-180" max="180" placeholder="Longitude">
            </div>
            <button type="button" class="btn ghost small location-picker-toggle" id="newReportPickLocation" aria-expanded="false" aria-controls="newReportLocationPicker">Pick on map</button>
            <div id="newReportLocationPicker" class="location-picker" hidden></div>
            <div id="newReportLocationStatus" class="muted location-picker-status" role="status">Click the map to add the report location, or enter coordinates.</div>
          </div>
          <button class="btn small" type="submit">Add Report</button>
        </form>
      </div>
    </details>
    <div class="split-page">
      <div class="split-list">
        <?php foreach ($filtered as $row): ?>
          <a class="split-item <?= (int)$row['id'] === $activeId ? 'active' : '' ?>" href="<?= esc(report_link((int)$row['id'], $search, $statusFilter)) ?>">
            <b>#<?= rid($row['id']) ?> &middot; <?= esc($row['problem_type']) ?></b>
            <div class="rmeta"><?= esc($row['location']) ?> &middot; <?= fmt_date($row['date_reported']) ?></div>
            <div class="rmeta">Assigned: <?= !empty($row['assigned_to']) ? esc($staffById[(int)$row['assigned_to']] ?? 'Unassigned') : 'Unassigned' ?> &middot; Due: <?= !empty($row['due_date']) ? fmt_date($row['due_date']) : 'Not set' ?></div>
            <span class="<?= pill_class($row['status']) ?>" style="margin-top:6px;display:inline-block;"><?= esc($row['status']) ?></span>
            <?php if (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d') && !in_array($row['status'], ['Resolved', 'Rejected'], true)): ?><span class="pill Rejected" style="margin-top:6px;display:inline-block;">Overdue</span><?php endif; ?>
          </a>
        <?php endforeach; ?>
        <?php if (!$filtered): ?><div class="empty" style="margin:12px;">No reports match your filters.</div><?php endif; ?>
      </div>
      <div class="split-detail" id="reportDetail">
      <?php if ($r): ?>
        <div class="section-head"><h2>Report #<?= rid($r['id']) ?></h2><span class="<?= pill_class($r['status']) ?>"><?= esc($r['status']) ?></span></div>
        <div class="status-flow">
          <?php $cur = array_search($r['status'], STATUS_FLOW, true);
          foreach (STATUS_FLOW as $i => $s): ?>
            <span class="<?= ($cur !== false && $i <= $cur) ? 'reached' : '' ?>"><?= esc($s) ?></span>
          <?php endforeach; ?>
          <?php if ($r['status'] === 'Rejected'): ?><span class="reached" style="border-color:var(--rust);color:var(--rust);">Rejected</span><?php endif; ?>
        </div>
        <div class="detail-grid">
          <div><div class="dl">Reported By</div><div class="dv"><?= esc($r['reporter']) ?></div></div>
          <div><div class="dl">Date</div><div class="dv"><?= fmt_date($r['date_reported']) ?></div></div>
          <div><div class="dl">Assigned Staff</div><div class="dv"><?= $r['assigned_to'] ? esc($staffById[(int)$r['assigned_to']] ?? 'Unassigned') : 'Unassigned' ?></div></div>
          <div><div class="dl">Target Date</div><div class="dv"><?= !empty($r['due_date']) ? fmt_date($r['due_date']) : 'Not set' ?><?php if (!empty($r['due_date']) && $r['due_date'] < date('Y-m-d') && !in_array($r['status'], ['Resolved', 'Rejected'], true)): ?> <span class="pill Rejected">Overdue</span><?php endif; ?></div></div>
            <?php if ($r['latitude'] !== null && $r['longitude'] !== null): ?>
              <div><div class="dl">Coordinates</div><div class="dv"><?= esc($r['latitude']) ?>, <?= esc($r['longitude']) ?> · <a href="<?= esc(build_map_link($r['location'], $r['latitude'], $r['longitude'])) ?>" target="_blank" rel="noopener">Open map</a></div></div>
            <?php endif; ?>
          </div>
        <?php if ($r['photo']): ?><div class="photo-frame"><img src="<?= esc(url($r['photo'])) ?>" alt="Report photo"></div><?php else: ?><div class="photo-frame">No photo attached</div><?php endif; ?>

        <div class="dl" style="margin-top:16px;">Status History</div>
        <div class="status-flow"><?php foreach ($history as $h): ?><span class="reached"><?= esc($h['status']) ?> &middot; <?= fmt_date($h['created_at']) ?></span><?php endforeach; ?></div>

        <form method="post" action="<?= esc(url('actions/report-update.php')) ?>" style="margin-top:20px;border-top:1px solid var(--line);padding-top:18px;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="return_q" value="<?= esc($search) ?>">
          <input type="hidden" name="return_status" value="<?= esc($statusFilter) ?>">
          <div class="field"><label>Location / Area</label>
            <select name="location">
              <?php $locs = $areas; if (!in_array($r['location'], $locs, true)) array_unshift($locs, $r['location']);
              foreach ($locs as $a): ?><option value="<?= esc($a) ?>" <?= $a === $r['location'] ? 'selected' : '' ?>><?= esc($a) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Coordinates (optional)</label>
            <div class="report-coordinate-inputs">
              <input id="editReportLatitude" type="number" step="any" name="latitude" min="-90" max="90" placeholder="Latitude" value="<?= esc($r['latitude'] ?? '') ?>">
              <input id="editReportLongitude" type="number" step="any" name="longitude" min="-180" max="180" placeholder="Longitude" value="<?= esc($r['longitude'] ?? '') ?>">
            </div>
            <button type="button" class="btn ghost small location-picker-toggle" id="editReportPickLocation" aria-expanded="false" aria-controls="editReportLocationPicker">Pick on map</button>
            <div id="editReportLocationPicker" class="location-picker" hidden></div>
            <div id="editReportLocationStatus" class="muted location-picker-status" role="status">Click the map to set or correct the exact report location.</div>
          </div>
          <div class="field"><label>Waste Type</label>
            <select name="wasteType"><?php foreach (WASTE_TYPES as $w): ?><option value="<?= esc($w) ?>" <?= $w === $r['waste_type'] ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label>Problem Type</label>
            <select name="problemType"><?php foreach (PROBLEM_TYPES as $p): ?><option value="<?= esc($p) ?>" <?= $p === $r['problem_type'] ? 'selected' : '' ?>><?= esc($p) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label>Description</label><textarea name="description"><?= esc($r['description']) ?></textarea></div>
          <div class="field">
            <label>Status</label>
            <select name="status"><?php foreach (STATUSES as $s): ?><option value="<?= esc($s) ?>" <?= $s === $r['status'] ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field">
            <label>Assigned Staff</label>
            <select name="assignedTo">
              <option value="">Unassigned</option>
              <?php foreach ($staff as $member): ?><option value="<?= (int)$member['id'] ?>" <?= (int)$member['id'] === (int)($r['assigned_to'] ?? 0) ? 'selected' : '' ?>><?= esc($member['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Target Resolution Date</label><input type="date" name="dueDate" value="<?= esc($r['due_date'] ?? '') ?>"></div>
          <div class="field"><label>Admin Notes</label><textarea name="adminNotes" placeholder="Notes from review..."><?= esc($r['admin_notes']) ?></textarea></div>
          <div class="field"><label>Action Taken</label><textarea name="actionTaken" placeholder="Corrective action recorded..."><?= esc($r['action_taken']) ?></textarea></div>
          <div class="row-actions">
            <button class="btn" type="submit">Save Update</button>
            <button class="btn danger" type="submit" form="deleteReportForm" onclick="return confirm('Delete report #<?= rid($r['id']) ?>? This cannot be undone.');">Delete Report</button>
          </div>
        </form>
        <form id="deleteReportForm" method="post" action="<?= esc(url('actions/report-delete.php')) ?>" style="display:none;">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        </form>
      <?php else: ?>
        <div class="empty">No reports yet.</div>
      <?php endif; ?>
      </div>
    </div>
<?php admin_footer();
