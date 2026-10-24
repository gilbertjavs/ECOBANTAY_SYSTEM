<?php
require __DIR__ . '/../includes/init.php';
require_role('Resident');
$items = [
    ["Biodegradable", "Food scraps, garden waste, and other organic material. Compost when possible."],
    ["Recyclable", "Clean paper, cardboard, plastic bottles, glass, and metal cans. Rinse before disposal."],
    ["Residual", "Non-recyclable, non-biodegradable waste such as soiled packaging and diapers."],
    ["Hazardous", "Batteries, chemicals, and electronics. Never mix with regular household waste."],
    ["Collection schedule", "Follow your Purok's assigned collection days to reduce overflow."],
    ["Reporting", "Use Report Waste for any segregation, dumping, or collection problem you notice."],
];
resident_header('guide');
?>
    <div class="section-head"><h2>Segregation Guide &amp; Information</h2></div>
    <div class="card">
      <?php foreach ($items as [$t, $d]): ?>
        <div class="guide-item"><b><?= esc($t) ?></b><span><?= esc($d) ?></span></div>
      <?php endforeach; ?>
    </div>
<?php resident_footer();
