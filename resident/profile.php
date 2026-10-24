<?php
require __DIR__ . '/../includes/init.php';
$user = require_role('Resident');
$old = take_old();
$account = q('SELECT resident_id, verified FROM users WHERE id = ?', [(int)$user['id']])->fetch();

resident_header('profile');
?>
    <div class="section-head"><h2>My Profile</h2></div>
    <div class="profile-grid">
      <section class="card">
        <div class="profile-section-heading">
          <h3>Personal information</h3>
          <p class="muted">Update the information connected to your EcoBantay account.</p>
        </div>
        <form method="post" action="<?= esc(url('actions/profile-update.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="section" value="profile">
          <div class="field">
            <label for="profile-name">Full name</label>
            <input id="profile-name" type="text" name="name" maxlength="100" value="<?= esc($old['name'] ?? $user['name']) ?>" autocomplete="name" required>
          </div>
          <div class="field">
            <label for="profile-username">Username</label>
            <input id="profile-username" type="text" name="username" maxlength="50" value="<?= esc($old['username'] ?? $user['username']) ?>" autocomplete="username" required>
          </div>
          <div class="field">
            <label for="profile-email">Email</label>
            <input id="profile-email" type="email" name="email" maxlength="120" value="<?= esc($old['email'] ?? $user['email']) ?>" autocomplete="email" required>
          </div>
          <div class="field">
            <label for="profile-contact">Contact number</label>
            <input id="profile-contact" type="tel" name="contact" maxlength="20" value="<?= esc($old['contact'] ?? $user['contact']) ?>" autocomplete="tel">
          </div>
          <div class="profile-account-note">
            <div><strong>Barangay Resident ID</strong><br><?= esc($account['resident_id'] ?? '') ?></div>
            <span class="pill <?= (int)($account['verified'] ?? 0) ? 'Resolved' : 'Pending' ?>">
              <?= (int)($account['verified'] ?? 0) ? 'Verified' : 'Pending verification' ?>
            </span>
            <p class="muted">To keep your identity verification accurate, contact your barangay admin if this resident ID needs correction.</p>
          </div>
          <button class="btn" type="submit">Save profile</button>
        </form>
      </section>

      <section class="card">
        <div class="profile-section-heading">
          <h3>Login credentials</h3>
          <p class="muted">Change your password. You will need your current password to confirm the change.</p>
        </div>
        <form method="post" action="<?= esc(url('actions/profile-update.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="section" value="password">
          <div class="field">
            <label for="current-password">Current password</label>
            <input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
          </div>
          <div class="field">
            <label for="new-password">New password</label>
            <input id="new-password" type="password" name="new_password" minlength="6" maxlength="72" autocomplete="new-password" required>
            <div class="muted" style="font-size:12px;margin-top:6px;">Use 6 to 72 characters.</div>
          </div>
          <div class="field">
            <label for="confirm-password">Confirm new password</label>
            <input id="confirm-password" type="password" name="confirm_password" minlength="6" maxlength="72" autocomplete="new-password" required>
          </div>
          <button class="btn" type="submit">Update password</button>
        </form>
      </section>
    </div>
<?php resident_footer();
