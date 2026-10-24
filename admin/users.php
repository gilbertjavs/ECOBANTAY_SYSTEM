<?php
require __DIR__ . '/../includes/init.php';
$me = require_role('Admin');

$users = q('SELECT u.*, (SELECT COUNT(*) FROM reports r WHERE r.user_id = u.id) AS report_count FROM users u ORDER BY u.id')->fetchAll();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

admin_header('users');
?>
    <div class="admin-topline"><h1>Manage Users</h1></div>
    <div class="auth-notice" style="margin-bottom:16px;">Compare each resident's ID with your barangay records before selecting <b>Verify ID</b>. An ID number entered during registration is not proof of identity by itself.</div>
    <details class="details-toggle">
      <summary>&#10010; Add User</summary>
      <div class="card">
        <form method="post" action="<?= esc(url('actions/user-add.php')) ?>">
          <?= csrf_field() ?>
          <div class="field"><label>Full Name</label><input type="text" name="name" required></div>
          <div class="field"><label>Username</label><input type="text" name="username" required></div>
          <div class="field"><label>Email</label><input type="email" name="email" required></div>
          <div class="field"><label>Password</label><input type="password" name="password" required autocomplete="new-password"></div>
          <div class="field"><label>Barangay Resident ID (required for residents)</label><input type="text" name="resident_id" maxlength="50"></div>
          <div class="field"><label>Role</label>
            <select name="role"><option value="Resident">Resident</option><option value="Admin">Admin</option></select>
          </div>
          <div class="field"><label>Contact</label><input type="text" name="contact" placeholder=""></div>
          <p class="muted" style="font-size:12px;margin-top:-6px;">Users added here by staff are verified automatically.</p>
          <button class="btn small" type="submit">Add User</button>
        </form>
      </div>
    </details>
    <div class="card" style="padding:0;">
      <table>
        <thead><tr><th>Name</th><th>Username</th><th>Resident ID</th><th>Email</th><th>Role</th><th>Contact</th><th>Reports Filed</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): $uid = (int)$u['id']; $verified = (bool)$u['verified']; $idPhotoPath = resident_id_photo_file($u['resident_id_photo'] ?? null); $hasIdPhoto = $u['role'] === 'Resident' && $idPhotoPath !== null && is_file($idPhotoPath); ?>
            <tr>
              <td><b><?= esc($u['name']) ?></b></td>
              <td><?= esc($u['username']) ?></td>
              <td><?= $u['role'] === 'Resident' && trim((string)$u['resident_id']) !== '' ? '<b>' . esc($u['resident_id']) . '</b>' : '&mdash;' ?><br>
                <?php if ($hasIdPhoto): ?>
                  <a href="<?= esc(url('actions/resident-id-view.php?id=' . $uid)) ?>" target="_blank" rel="noopener">View ID photo</a>
                <?php else: ?><span class="muted">No ID photo</span><?php endif; ?>
              </td>
              <td><?= $u['email'] ? esc($u['email']) : '&mdash;' ?></td>
              <td><span class="<?= pill_class($u['role'] === 'Admin' ? 'Investigating' : 'Good') ?>"><?= esc($u['role']) ?></span></td>
              <td><?= $u['contact'] ? esc($u['contact']) : '&mdash;' ?></td>
              <td><?= (int)$u['report_count'] ?></td>
              <td><span class="<?= pill_class($verified ? 'Verified' : 'Pending') ?>"><?= $verified ? 'Verified' : 'Pending' ?></span></td>
              <td>
                <div class="row-actions">
                  <?php if (!$verified && trim((string)$u['resident_id']) !== '' && $hasIdPhoto): ?>
                    <form method="post" action="<?= esc(url('actions/user-verify.php')) ?>" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $uid ?>"><button class="btn secondary small" type="submit">Verify ID</button></form>
                  <?php elseif (!$verified && $u['role'] === 'Resident'): ?>
                    <span class="muted">ID photo required</span>
                  <?php endif; ?>
                  <a class="edit-toggle" href="users.php<?= $editId === $uid ? '' : '?edit=' . $uid ?>" style="text-decoration:none;">Edit</a>
                  <form method="post" action="<?= esc(url('actions/user-delete.php')) ?>" style="margin:0;" onsubmit="return confirm('Delete user &quot;<?= esc(addslashes($u['name'])) ?>&quot;? This cannot be undone.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $uid ?>"><button class="edit-toggle" style="color:var(--rust);" type="submit">Delete</button></form>
                </div>
              </td>
            </tr>
            <?php if ($editId === $uid): ?>
            <tr>
              <td colspan="9">
                <form method="post" action="<?= esc(url('actions/user-update.php')) ?>" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:8px 0;">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $uid ?>">
                  <div class="field" style="margin:0;"><label>Name</label><input type="text" name="name" value="<?= esc($u['name']) ?>" required></div>
                  <div class="field" style="margin:0;"><label>Username</label><input type="text" name="username" value="<?= esc($u['username']) ?>" required></div>
                  <div class="field" style="margin:0;"><label>Email</label><input type="email" name="email" value="<?= esc($u['email']) ?>" required></div>
                  <div class="field" style="margin:0;"><label>Contact</label><input type="text" name="contact" value="<?= esc($u['contact']) ?>"></div>
                  <div class="field" style="margin:0;"><label>Barangay Resident ID</label><input type="text" name="resident_id" value="<?= esc($u['resident_id']) ?>" maxlength="50"></div>
                  <div class="field" style="margin:0;"><label>Replace ID photo (JPG/PNG/WEBP, max 5 MB)</label><input type="file" name="resident_id_photo" accept="image/jpeg,image/png,image/webp"></div>
                  <?php if ($hasIdPhoto): ?><a href="<?= esc(url('actions/resident-id-view.php?id=' . $uid)) ?>" target="_blank" rel="noopener">View current ID photo</a><?php endif; ?>
                  <div class="field" style="margin:0;"><label>Role</label>
                    <select name="role"><option value="Resident" <?= $u['role'] === 'Resident' ? 'selected' : '' ?>>Resident</option><option value="Admin" <?= $u['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option></select>
                  </div>
                  <button class="btn secondary small" type="submit">Save</button>
                </form>
              </td>
            </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php admin_footer();
