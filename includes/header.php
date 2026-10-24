<?php
/* Page shells: auth screen, resident topbar layout, admin sidebar layout.
   Markup and class names are identical to the original EcoBantay render functions. */

function render_head(string $title = 'EcoBantay', bool $withMap = false): void
{
    $theme = current_theme();
    $styleVersion = filemtime(__DIR__ . '/../assets/style.css');
    ?><!DOCTYPE html>
<html lang="en" style="color-scheme:<?= $theme ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php if ($withMap): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><?php endif; ?>
<link rel="stylesheet" href="<?= esc(url('assets/style.css')) ?>?v=<?= (int)$styleVersion ?>">
</head>
<body data-theme="<?= $theme ?>">
<div id="app">
<?php
}

function theme_button(): string
{
    $icon = current_theme() === 'dark' ? '&#9728;' : '&#9790;';
    return '<form method="post" action="' . esc(url('actions/theme.php')) . '" style="margin:0;display:flex;">'
         . csrf_field()
         . '<button class="theme-toggle" type="submit" title="Toggle theme" aria-label="Toggle theme">' . $icon . '</button></form>';
}

function logout_form(string $btnAttrs = ''): string
{
    return '<form method="post" action="' . esc(url('logout.php')) . '" style="margin:0;">' . csrf_field()
         . '<button class="btn ghost small ' . ($btnAttrs ? 'block' : '') . '" type="submit"' . ($btnAttrs ? ' style="' . $btnAttrs . '"' : '') . '>Log out</button></form>';
}

function location_share_indicator(): string
{
    $enabled = !empty($_SESSION['location_share_enabled']);
    $label = $enabled ? 'Location sharing ON' : 'Share location';
    return '<a id="locationShareIndicator" class="location-share-indicator' . ($enabled ? ' is-active' : '') . '" href="' . esc(url('location-sharing.php')) . '"'
         . ' title="Optional live location sharing" aria-label="' . esc($enabled ? 'Live location sharing is on. Manage sharing.' : 'Manage optional live location sharing.') . '">'
         . '<span class="location-share-dot" aria-hidden="true"></span><span class="location-share-label">' . esc($label) . '</span></a>';
}

/* ================= AUTH VIEW ================= */

function auth_page(string $tab): void
{
    $isLogin = $tab === 'login';
    $stats = q("SELECT (SELECT COUNT(*) FROM reports) AS r, (SELECT COUNT(*) FROM areas) AS a,
                       (SELECT COUNT(*) FROM reports WHERE status = 'Resolved') AS s")->fetch();
    $error  = flash_get('auth_error');
    $notice = flash_get('auth_notice');
    $old    = take_old();
    render_head('EcoBantay');
    ?>
  <div class="auth-wrap">
    <div class="auth-side">
      <div class="brand"><span class="leaf">&#9851;</span> ECOBANTAY</div>
      <div class="auth-tag">A community-based tracker for cleaner, better-segregated barangays.</div>
    </div>
    <div class="auth-form-side">
      <div class="auth-box">
        <div class="auth-toolbar">
          <?= theme_button() ?>
        </div>
        <div class="auth-tabs">
          <a href="<?= esc(url('login.php')) ?>" class="<?= $isLogin ? 'active' : '' ?>">Log in</a>
          <a href="<?= esc(url('register.php')) ?>" class="<?= !$isLogin ? 'active' : '' ?>">Register</a>
        </div>
        <?php if ($error): ?><div class="auth-error"><?= esc($error) ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="auth-notice"><?= esc($notice) ?></div><?php endif; ?>
        <?php if ($isLogin): ?>
          <form method="post" action="<?= esc(url('actions/login.php')) ?>">
            <?= csrf_field() ?>
            <div class="field"><label>Email or Username</label><input type="text" name="identifier" required autocomplete="username"></div>
            <div class="field"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
            <button class="btn block" type="submit">Log in</button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= esc(url('actions/register.php')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field"><label for="reg_name">Full name</label><input id="reg_name" type="text" name="name" value="<?= esc($old['name'] ?? '') ?>" required></div>
            <div class="field"><label for="reg_username">Username</label><input id="reg_username" type="text" name="username" value="<?= esc($old['username'] ?? '') ?>" required></div>
            <div class="field"><label for="reg_email">Email</label><input id="reg_email" type="email" name="email" value="<?= esc($old['email'] ?? '') ?>" required autocomplete="email"></div>
            <div class="field"><label for="reg_contact">Contact number</label><input id="reg_contact" type="text" name="contact" value="<?= esc($old['contact'] ?? '') ?>" placeholder=""></div>
            <div class="field"><label for="reg_resident_id">Barangay Resident ID number</label><input id="reg_resident_id" type="text" name="resident_id" value="<?= esc($old['resident_id'] ?? '') ?>" maxlength="50" required autocomplete="off"></div>
            <div class="field"><label for="reg_resident_id_photo">Photo of your barangay ID</label><input id="reg_resident_id_photo" type="file" name="resident_id_photo" accept="image/jpeg,image/png,image/webp" required><div class="muted" style="font-size:12px;margin-top:6px;">Clear JPG, PNG, or WEBP image; maximum 5 MB. Only authorized barangay admins can view it.</div></div>
            <div class="field"><label for="reg_password">Password</label><input id="reg_password" type="password" name="password" required></div>
            <div class="field" style="margin-bottom:18px;">
              <label for="privacy_accept" style="display:block; margin-bottom:8px; font-size:12px; font-weight:600; color:var(--ink-soft);">Privacy notice</label>
              <label for="privacy_accept" style="display:flex; align-items:flex-start; gap:8px; font-size:12.5px; line-height:1.5; color:var(--ink-soft);">
                <input id="privacy_accept" type="checkbox" name="privacy_accept" value="1" required>
                <span>I agree that the barangay may collect, use, and securely store my resident ID number and ID photo to verify my account. Only authorized barangay admins can view the photo for verification. <a href="<?= esc(url('privacy.php')) ?>" target="_blank" rel="noopener noreferrer">Read the full privacy notice</a>.</span>
              </label>
            </div>
            <button class="btn block" type="submit">Create resident account</button>
          </form>
          <p class="demo-note">New accounts are registered as Residents and can log in after barangay staff verify the account.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php
    render_foot();
}

/* ================= RESIDENT SHELL ================= */

function resident_header(string $title = 'EcoBantay', bool $isHome = false): void
{
    $u = current_user();
    $unread = unread_count((int)$u['id']);
    render_head('EcoBantay', $title === 'reportForm' || $title === 'dashboard');
    ?>
    <div class="topbar">
      <div class="brand"><span class="leaf">&#9851;</span> ECOBANTAY</div>
      <div class="topbar-right">
        <?= theme_button() ?>
        <a class="icon-btn" href="<?= esc(url('resident/notifications.php')) ?>" title="Notifications">&#128276;<?php if ($unread): ?><span class="badge"><?= $unread ?></span><?php endif; ?></a>
        <?= location_share_indicator() ?>
        <a class="user-chip profile-link" href="<?= esc(url('resident/profile.php')) ?>" title="Edit your profile" aria-label="Edit your profile">
          <span class="av"><?= esc(initials($u['name'])) ?></span><?= esc($u['name']) ?>
        </a>
        <?= logout_form() ?>
      </div>
    </div>
    <div class="shell-body"><?php if (!$isHome): ?><a class="btn ghost small" style="margin-bottom:16px;" href="<?= esc(url('resident/dashboard.php')) ?>">&larr; Back</a><?php endif; ?>

<?php
}

/* ================= ADMIN SHELL ================= */

function admin_header(string $active, bool $isHome = false): void
{
    $u = current_user();
    $nav = [
        ['dashboard',     'Dashboard',    '&#128202;', 'admin/dashboard.php'],
        ['reports',       'Reports',      '&#128203;', 'admin/reports.php'],
        ['locations',     'Live Locations','&#128205;', 'admin/locations.php'],
        ['wasteStatus',   'Waste Status', '&#9851;',   'admin/waste-status.php'],
        ['users',         'Users',        '&#128101;', 'admin/users.php'],
        ['analytics',     'Analytics',    '&#128200;', 'admin/analytics.php'],
        ['notifications', 'Notifications','&#128276;', 'admin/notifications.php'],
        ['settings',      'Settings',     '&#9881;',   'admin/settings.php'],
        ['about',         'About',        '&#8505;',   'admin/about.php'],
    ];
    render_head('EcoBantay', $active === 'reports' || $active === 'locations');
    ?>
  <div class="admin-shell">
    <div class="sidebar">
      <div class="admin-nav-head">
        <div class="brand"><span class="leaf">&#9851;</span> ECOBANTAY</div>
        <div class="sidebar-foot">
          <?= theme_button() ?>
          <?= location_share_indicator() ?>
          <div class="user-chip" style="color:#fff;margin-bottom:10px;"><span class="av"><?= esc(initials($u['name'])) ?></span><?= esc($u['name']) ?></div>
          <?= logout_form('color:#DCE4D3;border-color:rgba(255,255,255,0.25);') ?>
        </div>
      </div>
      <nav class="sidebar-nav" aria-label="Admin navigation">
        <?php foreach ($nav as [$key, $label, $icon, $href]): ?>
          <a class="nav-item <?= $active === $key ? 'active' : '' ?>" href="<?= esc(url($href)) ?>"><?= $icon ?> <?= $label ?></a>
        <?php endforeach; ?>
        <a class="nav-item <?= $active === 'locationSharing' ? 'active' : '' ?>" href="<?= esc(url('location-sharing.php')) ?>">&#128274; Location Sharing</a>
      </nav>
    </div>
    <div class="admin-main"><?php if (!$isHome): ?><a class="btn ghost small" style="margin-bottom:16px;" href="<?= esc(url('admin/dashboard.php')) ?>">&larr; Back</a><?php endif; ?>

<?php
}
