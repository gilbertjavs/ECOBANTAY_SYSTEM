<?php
require __DIR__ . '/includes/init.php';
$user = require_login();
$sharingEnabled = !empty($_SESSION['location_share_enabled']);

if ($user['role'] === 'Admin') {
    admin_header('locationSharing');
} else {
    resident_header('locationSharing');
}
?>
    <div class="section-head"><h2>Live Location Sharing</h2></div>
    <section class="card location-sharing-card">
      <?php if ($sharingEnabled): ?>
        <div class="location-sharing-state is-active">
          <span class="location-share-dot" aria-hidden="true"></span>
          <div>
            <h3>Location sharing is on</h3>
            <p>Your current location is being shared with authorized barangay administrators while you remain signed in and this browser can access your location.</p>
          </div>
        </div>
        <div id="locationSharingStatus" class="muted location-sharing-status" role="status">Updating your shared location while this session is active.</div>
        <form method="post" action="<?= esc(url('actions/location-share.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="mode" value="stop">
          <button class="btn danger" type="submit">Stop sharing my location</button>
        </form>
      <?php else: ?>
        <div class="location-sharing-state">
          <span class="location-share-dot" aria-hidden="true"></span>
          <div>
            <h3>Location sharing is off</h3>
            <p>Your location is not being collected for live sharing.</p>
          </div>
        </div>
        <div class="location-sharing-privacy">
          <h3>Before you turn it on</h3>
          <ul>
            <li>Only authorized barangay administrators can see your current location on the live map.</li>
            <li>Sharing is optional. Your browser will ask permission to access location.</li>
            <li>Only your latest location is stored; EcoBantay does not keep a movement history.</li>
            <li>You can stop sharing at any time. Sharing also ends when you log out; if the browser closes unexpectedly, your marker disappears after three minutes without an update.</li>
          </ul>
        </div>
        <form id="locationSharingForm" method="post" action="<?= esc(url('actions/location-share.php')) ?>" data-location-endpoint="<?= esc(url('actions/location-share.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="mode" value="enable">
          <input type="hidden" name="latitude" value="">
          <input type="hidden" name="longitude" value="">
          <input type="hidden" name="accuracy" value="">
          <label class="location-sharing-consent">
            <input id="locationSharingConsent" type="checkbox" name="consent" value="yes" required>
            <span>I understand and agree to share my live location with authorized barangay administrators until I stop sharing or log out.</span>
          </label>
          <button id="startLocationSharing" class="btn" type="button">Share my live location</button>
          <p id="locationSharingStatus" class="muted location-sharing-status" role="status">Your location is not shared unless you check the box and press the button.</p>
          <noscript><p class="auth-error">JavaScript is required to request your browser location and start live sharing.</p></noscript>
        </form>
      <?php endif; ?>
    </section>
    <script defer src="<?= esc(url('assets/location-sharing.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/assets/location-sharing.js') ?>"></script>
<?php
if ($user['role'] === 'Admin') admin_footer();
else resident_footer();
