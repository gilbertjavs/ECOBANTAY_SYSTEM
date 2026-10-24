<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');

$locations = live_location_shares();
admin_header('locations');
?>
    <div class="admin-topline"><h1>Live Locations</h1></div>
    <section class="card live-location-info">
      <p><strong>Consent-based sharing only.</strong> This map shows the most recent location of people who explicitly turned on sharing for their current session. It refreshes every 30 seconds and removes a marker after three minutes without an update.</p>
      <p class="muted">Location sharing is optional, stops when the person logs out, and stores only the latest point (not a movement history). GPS accuracy varies by device and permission.</p>
    </section>
    <section class="card live-location-map-card" aria-labelledby="live-location-map-title">
      <div class="section-head">
        <div>
          <h2 id="live-location-map-title">People sharing now</h2>
          <p id="liveLocationCount" class="muted"><?= count($locations) ?> people sharing a recent location.</p>
        </div>
      </div>
      <div id="liveLocationMap" class="live-location-map" data-locations="<?= esc(json_encode($locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]') ?>" data-endpoint="<?= esc(url('actions/live-locations.php')) ?>"></div>
      <p id="liveLocationStatus" class="muted live-location-status" role="status">Map tiles are provided by OpenStreetMap.</p>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script defer src="<?= esc(url('assets/live-locations-map.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/live-locations-map.js') ?>"></script>
<?php admin_footer();
