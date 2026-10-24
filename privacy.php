<?php
require __DIR__ . '/includes/init.php';

$siteName = 'EcoBantay';

render_head('Privacy Notice');
?>
<div class="shell-body" style="max-width:820px;">
  <div class="section-head"><h2>Privacy Notice</h2></div>
  <div class="card">
    <p class="muted" style="margin-top:0;">This privacy notice should be reviewed by the barangay or responsible privacy adviser before production use.</p>

    <h3 style="font-size:14.5px;margin:18px 0 10px;">What information is collected</h3>
    <p>The system collects resident names, usernames, email addresses, mobile numbers, barangay resident ID numbers, and a photo of the resident ID for account verification. It also collects report locations, report descriptions, and optional report photos for waste reporting and barangay response. Separately, signed-in users may explicitly opt in to share their current device location with authorized barangay administrators; live sharing is off by default. A user may also choose to send an SOS alert with their selected location to EcoBantay administrators and, if configured, the emergency email recipient.</p>

    <h3 style="font-size:14.5px;margin:18px 0 10px;">Why the information is collected</h3>
    <p>EcoBantay uses resident ID numbers and ID photos only to help authorized barangay administrators verify that a registration belongs to a legitimate resident. Other information is used to receive and process waste reports, monitor waste conditions, assign and track actions, and communicate status updates to residents and barangay staff. Live device location is used only when a user turns on the optional live-sharing control, and can be stopped at any time. An SOS location is sent only when the user submits an SOS alert and is included in the resulting admin notification and configured emergency email.</p>

    <h3 style="font-size:14.5px;margin:18px 0 10px;">Who can access it</h3>
    <p>Only authorized barangay administrators can view uploaded resident ID photos and see current locations of users who opted in to live sharing. ID photos are stored in a folder that blocks direct public web access and are served only through an administrator-only page. Live location is not collected when sharing is off, is removed from the live map after three minutes without an update, and is not kept as a movement history.</p>

    <h3 style="font-size:14.5px;margin:18px 0 10px;">How it is protected</h3>
    <p>Access is controlled by role-based authorization, secure session handling, password hashing, and limited administrative access. ID uploads are restricted to JPG, PNG, or WEBP images up to 5 MB and stored in a folder configured to deny direct web requests.</p>

    <h3 style="font-size:14.5px;margin:18px 0 10px;">Resident rights and requests</h3>
    <p>Residents may ask for clarification about the information collected, correction of inaccurate personal data, and information regarding the purpose of processing. The barangay should maintain a documented procedure for receiving and handling such requests in line with the Philippine Data Privacy Act of 2012.</p>

    <h3 style="font-size:14.5px;margin:18px 0 10px;">Retention</h3>
    <p>Resident ID photos are retained for account verification and related barangay administration. Live-location data is stored as one current point for an opted-in session, deleted when the user logs out, and expired after three minutes without a refresh. The barangay should delete other records securely when no longer needed and maintain documented retention periods.</p>

    <div style="margin-top:18px;">
      <a class="btn secondary" href="<?= esc(url('register.php')) ?>">Back to Registration</a>
    </div>
  </div>
</div>
<?php
render_foot();
