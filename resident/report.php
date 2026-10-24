<?php
require __DIR__ . '/../includes/init.php';
require_role('Resident');

$areas = q('SELECT area FROM areas ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
$old = take_old();
$selLoc   = $old['location'] ?? '';
$selWaste = $old['wasteType'] ?? WASTE_TYPES[0];
$selProb  = $old['problemType'] ?? PROBLEM_TYPES[0];

resident_header('reportForm');
?>
    <div class="report-page">
    <div class="section-head report-heading"><h2>Report Waste / Segregation Problem</h2></div>
    <div class="card report-card">
      <form method="post" action="<?= esc(url('actions/report-submit.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field">
          <label>Location / Area</label>
          <select name="location" required>
            <option value="">Select Barangay / Street / Purok</option>
            <?php foreach ($areas as $a): ?>
              <option value="<?= esc($a) ?>" <?= $a === $selLoc ? 'selected' : '' ?>><?= esc($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="report-form-columns">
          <div class="report-choice-column">
            <div class="field">
              <label>Waste Type</label>
              <div class="radio-grid">
                <?php foreach (WASTE_TYPES as $w): ?>
                  <label class="radio-pill"><input type="radio" name="wasteType" value="<?= esc($w) ?>" <?= $w === $selWaste ? 'checked' : '' ?> required><?= esc($w) ?></label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="report-choice-column">
            <div class="field">
              <label>Problem Type</label>
              <div class="radio-grid">
                <?php foreach (PROBLEM_TYPES as $p): ?>
                  <label class="radio-pill"><input type="radio" name="problemType" value="<?= esc($p) ?>" <?= $p === $selProb ? 'checked' : '' ?> required><?= esc($p) ?></label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="report-form-details">
            <div class="field">
              <label>Description</label>
              <textarea name="description" placeholder="Describe the situation..." required><?= esc($old['description'] ?? '') ?></textarea>
            </div>
            <div class="field">
              <label>Upload Photo (optional)</label>
              <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
              <div class="muted" style="font-size:12px;margin-top:6px;">JPG, PNG, GIF or WEBP &middot; max 5 MB</div>
            </div>
            <div class="field report-coordinates">
              <label>Location Coordinates (optional)</label>
              <div class="row-actions" style="margin-bottom:8px;">
                <button type="button" class="btn secondary small" id="useCurrentLocationBtn">Use my location</button>
                <button type="button" class="btn ghost small" id="pickLocationBtn" aria-expanded="false" aria-controls="reportLocationPicker">Pick on map</button>
              </div>
              <div class="report-coordinate-inputs">
                <input type="number" step="any" min="-90" max="90" name="latitude" placeholder="Latitude" id="latitudeInput" value="<?= esc($old['latitude'] ?? '') ?>">
                <input type="number" step="any" min="-180" max="180" name="longitude" placeholder="Longitude" id="longitudeInput" value="<?= esc($old['longitude'] ?? '') ?>">
              </div>
              <div id="reportLocationPicker" class="location-picker" hidden></div>
              <div id="reportLocationStatus" class="muted location-picker-status" role="status">Tap “Use my location,” choose “Pick on map,” or enter coordinates manually. Sharing a location is optional.</div>
            </div>
          </div>
        </div>
        <div class="report-form-footer">
          <p class="muted" style="font-size:12.5px;margin:0;">Date &amp; time will be recorded automatically on submit.</p>
          <button type="submit" class="btn">Submit Report</button>
        </div>
      </form>
    </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= esc(url('assets/report-location-picker.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/report-location-picker.js') ?>"></script>
<?php resident_footer();
