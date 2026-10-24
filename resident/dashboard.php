<?php
require __DIR__ . '/../includes/init.php';
$user = require_role('Resident');

$recent = q('SELECT * FROM reports WHERE user_id = ? ORDER BY date_reported DESC, id DESC LIMIT 4', [$user['id']])->fetchAll();
$schedule = get_collection_schedule();
$next = next_pickup($schedule);
$sharedLocation = null;
if (!empty($_SESSION['location_share_enabled'])) {
    $sharedLocation = q('SELECT latitude, longitude FROM live_location_shares
                         WHERE session_key = ? AND user_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)',
        [live_location_session_key(), (int)$user['id']])->fetch() ?: null;
}

resident_header('dashboard', true);
?>
    <div class="welcome-banner">
      <h1>Welcome, <?= esc(first_name($user['name'])) ?>!</h1>
      <p>Help keep our community clean through proper waste segregation.</p>
    </div>
    <div class="quick-grid">
      <a class="quick-card" href="report.php"><span class="qi">&#128221;</span><b>Report Waste</b><span>Flag a segregation or dumping problem</span></a>
      <a class="quick-card" href="my-reports.php"><span class="qi">&#128203;</span><b>My Reports</b><span>Track reports you've submitted</span></a>
      <a class="quick-card" href="waste-status.php"><span class="qi">&#9851;</span><b>Waste Status</b><span>See the condition of your area</span></a>
      <a class="quick-card" href="guide.php"><span class="qi">&#8505;</span><b>Information</b><span>Segregation guidelines &amp; reminders</span></a>
      <a class="quick-card" href="about.php"><span class="qi">&#127760;</span><b>About EcoBantay</b><span>What this system is for</span></a>
    </div>
    <section class="card sos-location-card" aria-labelledby="sos-location-title">
      <div class="section-head sos-location-heading">
        <div>
          <h2 id="sos-location-title">Your Location Preview</h2>
          <p class="muted">Your location is only sent when you choose to share it or send an SOS alert.</p>
        </div>
        <button id="useMyGps" class="btn secondary small" type="button">Use my GPS</button>
      </div>
      <div class="sos-coordinates">
        <div><span>Latitude</span><strong id="sosLatitude"><?= $sharedLocation ? esc($sharedLocation['latitude']) : 'Not set' ?></strong></div>
        <div><span>Longitude</span><strong id="sosLongitude"><?= $sharedLocation ? esc($sharedLocation['longitude']) : 'Not set' ?></strong></div>
      </div>
      <div id="sosLocationMap" class="report-map-canvas sos-location-map"
           data-location="<?= esc(json_encode($sharedLocation ? ['lat' => (float)$sharedLocation['latitude'], 'lng' => (float)$sharedLocation['longitude']] : null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: 'null') ?>"></div>
      <p id="sosLocationStatus" class="muted report-map-note" role="status"><?= $sharedLocation ? 'Showing your latest shared location. Click the map to choose a different point for an SOS.' : 'Use my GPS to show your current position, or click the map to choose a point for an SOS.' ?></p>
      <form method="post" action="<?= esc(url('actions/sos-alert.php')) ?>" id="sosAlertForm" class="sos-alert-form">
        <?= csrf_field() ?>
        <input id="sosLatitudeInput" type="hidden" name="latitude" value="<?= $sharedLocation ? esc($sharedLocation['latitude']) : '' ?>">
        <input id="sosLongitudeInput" type="hidden" name="longitude" value="<?= $sharedLocation ? esc($sharedLocation['longitude']) : '' ?>">
        <button id="sendSosAlert" class="btn danger block" type="submit" disabled>Send SOS alert</button>
      </form>
      <div class="sos-sharing-note">
        <?php if (!empty($_SESSION['location_share_enabled'])): ?>
          <p class="muted">Live location sharing is on for this session. It stops when you log out or turn it off.</p>
          <a class="btn ghost small" href="<?= esc(url('location-sharing.php')) ?>">Manage live sharing</a>
        <?php else: ?>
          <p class="muted">Want to share your live location while signed in? It stays off unless you explicitly opt in.</p>
          <form id="dashboardLiveShareForm" class="dashboard-live-share-form" data-endpoint="<?= esc(url('actions/location-share.php')) ?>">
            <?= csrf_field() ?>
            <label class="location-sharing-consent">
              <input id="dashboardLiveShareConsent" type="checkbox">
              <span>I agree to share my live location with authorized barangay administrators while I am signed in. I can stop sharing at any time.</span>
            </label>
            <button id="enableDashboardLiveShare" class="btn secondary small" type="button">Enable live sharing while signed in</button>
          </form>
          <a class="btn ghost small" href="<?= esc(url('location-sharing.php')) ?>">More about location sharing</a>
        <?php endif; ?>
      </div>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script defer src="<?= esc(url('assets/sos-location.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/sos-location.js') ?>"></script>
    <div class="card schedule-card">
      <div class="section-head" style="margin-bottom:10px;"><h2>Collection Schedule</h2></div>
      <?php if ($next): ?>
        <div class="schedule-highlight">Next pickup: <b><?= esc($next['day']) ?></b> at <b><?= esc($next['time']) ?></b> for <b><?= esc($next['area']) ?></b> (<?= esc($next['waste_type']) ?>)</div>
      <?php else: ?>
        <div class="schedule-highlight">No pickups have been scheduled yet.</div>
      <?php endif; ?>
      <div class="schedule-list">
        <?php foreach ($schedule as $s): ?>
          <div class="schedule-item"><span><?= esc($s['day']) ?></span><strong><?= esc($s['area']) ?></strong><small><?= esc($s['waste_type']) ?> &bull; <?= esc($s['time']) ?></small></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="section-head"><h2>Recent Reports</h2><a href="my-reports.php" style="font-size:13px;font-weight:600;color:var(--forest);">View all &rarr;</a></div>
    <div class="card">
      <?php if ($recent): foreach ($recent as $r): ?>
        <div class="report-row">
          <div>
            <b>#<?= rid($r['id']) ?></b> &nbsp; <?= esc($r['problem_type']) ?>
            <div class="rmeta"><?= esc($r['location']) ?> &middot; <?= esc($r['waste_type']) ?> &middot; <?= fmt_date($r['date_reported']) ?></div>
          </div>
          <span class="<?= pill_class($r['status']) ?>"><?= esc($r['status']) ?></span>
        </div>
      <?php endforeach; else: ?>
        <div class="empty">You haven't submitted any reports yet. Use <b>Report Waste</b> to flag your first issue.</div>
      <?php endif; ?>
    </div>
<?php resident_footer();
