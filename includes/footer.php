<?php
/* Closes the page shells and prints the one-time "toast" message (pure CSS animation, no JavaScript). */

function render_foot(): void
{
    echo "</div>\n";
    $t = flash_get('toast');
    if ($t !== null && $t !== '') {
        echo '<div id="toast" class="toast flash">' . esc($t) . "</div>\n";
    }
    $passwordToggleVersion = filemtime(__DIR__ . '/../assets/password-visibility.js');
    echo '<script defer src="' . esc(url('assets/password-visibility.js')) . '?v=' . (int)$passwordToggleVersion . '"></script>' . "\n";
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['location_share_enabled'])) {
        $trackerVersion = filemtime(__DIR__ . '/../assets/location-tracker.js');
        echo '<script defer src="' . esc(url('assets/location-tracker.js')) . '?v=' . (int)$trackerVersion
           . '" data-endpoint="' . esc(url('actions/location-share.php')) . '" data-token="' . esc(csrf_token())
           . '" data-indicator="locationShareIndicator" data-status="locationSharingStatus"></script>' . "\n";
    }
    echo "</body>\n</html>\n";
}

function resident_footer(): void
{
    echo "</div>\n"; // .shell-body
    render_foot();
}

function admin_footer(): void
{
    echo "</div></div>\n"; // .admin-main + .admin-shell
    render_foot();
}

/* Shared "About EcoBantay" page (used by both Resident and Admin, as in the original). */
function about_content(bool $isAdmin): void
{
    $areas = (int)q('SELECT COUNT(*) FROM areas')->fetchColumn();
    $total = (int)q('SELECT COUNT(*) FROM reports')->fetchColumn();
    $resolved = (int)q("SELECT COUNT(*) FROM reports WHERE status = 'Resolved'")->fetchColumn();
    ?>
    <div class="section-head"><h2>About EcoBantay</h2></div>
    <div class="card">
      <p class="muted" style="margin:0 0 4px;">Community-Based Waste Segregation Tracker</p>
      <div class="about-goal">
        <b>System Goal:</b> EcoBantay exists to help barangays improve waste segregation and cleanliness by giving
        residents a direct channel to report problems &mdash; improper segregation, overflowing bins, illegal dumping,
        uncollected waste &mdash; and giving barangay staff a clear workflow to verify each report, act on it, and
        resolve it, while keeping everyone informed of which areas are clean and which need attention.
      </div>

      <h3 style="font-size:14.5px;margin-bottom:10px;">What residents can do</h3>
      <ul class="about-list">
        <li>Submit a waste or segregation report with location, waste type, description, and an optional photo.</li>
        <li>Track each report's status from <b>Pending</b> through <b>Investigating &rarr; Verified &rarr; Action Taken &rarr; Resolved</b> (or Rejected).</li>
        <li>View the current cleanliness status &mdash; <b>Good</b>, <b>Monitor</b>, or <b>Needs Attention</b> &mdash; for every monitored area.</li>
        <li>Get notified whenever a submitted report is updated by an admin.</li>
        <li>Refer to the segregation guide for how to sort biodegradable, recyclable, residual, and hazardous waste.</li>
      </ul>

      <h3 style="font-size:14.5px;margin:18px 0 10px;">What barangay admins can do</h3>
      <ul class="about-list">
        <li>Full report management: add, edit, verify, and delete reports, and record admin notes and action taken.</li>
        <li>Full area management: add, edit, and delete monitored areas and their waste status.</li>
        <li>Full user management: add, edit, and delete accounts, and verify newly registered residents before they can log in.</li>
        <li>See system-wide analytics: reports by problem type, by status, and by location.</li>
      </ul>

      <h3 style="font-size:14.5px;margin:18px 0 10px;">Why it matters</h3>
      <p style="margin:0;font-size:13.5px;line-height:1.7;">
        Waste segregation problems are often invisible to barangay staff until they become serious &mdash; illegal
        dumping, overflowing bins, or repeated uncollected waste. EcoBantay closes that gap by turning residents into
        the system's eyes on the ground, and giving staff an auditable trail from report to resolution.
      </p>

      <div class="system-review" aria-label="EcoBantay system review">
        <div class="system-review__header">
          <span class="system-review__brand">ECOBANTAY</span>
          <span class="system-review__meta">SYSTEM</span>
        </div>

        <div class="system-review__bubble">
          <p>EcoBantay is a barangay waste reporting system that helps residents report local waste problems and helps administrators track and resolve them quickly.</p>
          <div class="system-review__status">just now</div>
        </div>

        <div class="system-review__panel">
          <p><strong>Good news</strong> &mdash; EcoBantay already covers the core workflow: residents can submit waste reports, admins can monitor areas, verify reports, and update statuses, while the system keeps a record of what has been reported and what action was taken.</p>

          <p>The platform is designed to improve barangay cleanliness by connecting residents to local waste management actions. It records issue type, location, waste category, report status, and administrative notes so cleanup and follow-up remain visible and traceable.</p>

          <p><strong>System highlights:</strong></p>
          <ul>
            <li>Residents can submit reports for improper segregation, overflowing bins, illegal dumping, and uncollected waste.</li>
            <li>Barangay admins can verify reports, update status, and record action taken.</li>
            <li>Areas can be monitored using status labels such as Good, Monitor, and Needs Attention.</li>
            <li>Notifications keep residents informed when their reports are reviewed or resolved.</li>
            <li>Collection schedules and waste-management data support organized barangay response planning.</li>
          </ul>

          <p><strong>Why this matters:</strong> local waste problems often worsen before barangay staff can act. EcoBantay closes that gap by turning residents into the system's eyes on the ground and giving officials a structured trail from report to resolution.</p>

          <p class="system-review__closing">This is a practical barangay tool for reporting, monitoring, and improving waste segregation and cleanliness.</p>
        </div>
      </div>

      <p class="muted" style="font-size:12px;margin-top:18px;border-top:1px solid var(--line);padding-top:14px;">
        This is a prototype system built for a barangay setting; feature scope may expand as EcoBantay grows.
      </p>

    </div>
<?php
}
