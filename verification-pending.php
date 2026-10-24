<?php
require __DIR__ . '/includes/init.php';

$pendingUserId = (int)($_SESSION['pending_verification_user_id'] ?? 0);
if ($pendingUserId <= 0) redirect('login.php');

$pendingUser = q("SELECT name FROM users WHERE id = ? AND role = 'Resident'", [$pendingUserId])->fetch();
if (!$pendingUser) {
    unset($_SESSION['pending_verification_user_id']);
    flash_set('auth_error', 'The pending account could not be found. Please contact the barangay administrator.');
    redirect('login.php');
}

render_head('Verification Pending');
?>
<div class="auth-wrap">
  <div class="auth-form-side">
    <div class="auth-box">
      <div class="section-head"><h2>Account verification</h2></div>
      <div class="card">
        <h1 style="font-size:21px;margin:0 0 10px;">Thanks, <?= esc($pendingUser['name']) ?>!</h1>
        <p class="muted" style="font-size:14px;line-height:1.65;">Your account is waiting for barangay verification. Keep this page open. When an administrator verifies your account, EcoBantay will automatically sign you in on this device.</p>
        <p id="verificationStatus" class="info-box" role="status" aria-live="polite">Checking for verification…</p>
        <p class="muted" style="font-size:12.5px;line-height:1.6;">If you close this page or use another device, you can log in manually after your account is verified.</p>
        <a class="btn secondary block" href="<?= esc(url('login.php')) ?>">Go to login</a>
      </div>
    </div>
  </div>
</div>
<script defer src="<?= esc(url('assets/verification-pending.js')) ?>?v=<?= (int)filemtime(__DIR__ . '/assets/verification-pending.js') ?>"
  data-endpoint="<?= esc(url('actions/verification-status.php')) ?>"
  data-token="<?= esc(csrf_token()) ?>"></script>
<?php render_foot();
