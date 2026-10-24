<?php
/* Shared constants and helper functions (PHP versions of the old JS helpers). */

const WASTE_TYPES   = ["Biodegradable", "Recyclable", "Residual", "Hazardous", "Mixed Waste", "Other"];
const PROBLEM_TYPES = ["Improper Segregation", "Overflowing Bin", "Illegal Dumping", "Uncollected Waste", "Wrong Bin Used", "Other"];
const STATUSES      = ["Pending", "Investigating", "Verified", "Action Taken", "Resolved", "Rejected"];
const STATUS_FLOW   = ["Pending", "Investigating", "Verified", "Action Taken", "Resolved"];
const AREA_STATUSES = ["Good", "Monitor", "Needs Attention"];
const DAYS_OF_WEEK  = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const USER_ROLES    = ['Resident', 'Admin'];

/* ---------------- output / urls ---------------- */

function esc($s): string
{
    return htmlspecialchars((string)($s === null ? '' : $s), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path)
{
    header('Location: ' . url($path));
    exit;
}

/** Redirect to the page the form was submitted from (same host only), else to $fallback. */
function redirect_back(string $fallback)
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $p = parse_url($ref);
    if ($p && isset($p['host'], $_SERVER['HTTP_HOST'])) {
        $host = $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
        if (strcasecmp($host, $_SERVER['HTTP_HOST']) === 0) {
            $target = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
            header('Location: ' . str_replace(["\r", "\n"], '', $target));
            exit;
        }
    }
    redirect($fallback);
}

/* ---------------- database helper ---------------- */

/** Run a prepared statement and return it. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function ensure_login_attempts_table(): void
{
    q("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        identifier VARCHAR(120) NOT NULL,
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
        first_attempt_at DATETIME NULL,
        last_attempt_at DATETIME NULL,
        locked_until DATETIME NULL,
        PRIMARY KEY (id),
        KEY idx_login_attempts_lookup (identifier, ip_address, locked_until),
        KEY idx_login_attempts_last (last_attempt_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensure_live_location_shares_table(): void
{
    q("CREATE TABLE IF NOT EXISTS live_location_shares (
        session_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        latitude DECIMAL(10,7) NOT NULL,
        longitude DECIMAL(10,7) NOT NULL,
        accuracy_m DECIMAL(10,2) NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (session_key),
        KEY idx_live_location_user (user_id),
        KEY idx_live_location_updated (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    q('DELETE FROM live_location_shares WHERE updated_at < DATE_SUB(NOW(), INTERVAL 3 MINUTE)');
}

function live_location_session_key(?string $sessionId = null): string
{
    return hash('sha256', $sessionId ?? session_id());
}

function save_live_location(int $userId, $latitude, $longitude, $accuracy): void
{
    q('INSERT INTO live_location_shares (session_key, user_id, latitude, longitude, accuracy_m, updated_at)
       VALUES (?, ?, ?, ?, ?, NOW())
       ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), latitude = VALUES(latitude),
         longitude = VALUES(longitude), accuracy_m = VALUES(accuracy_m), updated_at = NOW()',
      [live_location_session_key(), $userId, $latitude, $longitude, $accuracy]);
}

function stop_live_location_sharing(int $userId): void
{
    q('DELETE FROM live_location_shares WHERE session_key = ? AND user_id = ?',
      [live_location_session_key(), $userId]);
    unset($_SESSION['location_share_enabled']);
}

function live_location_shares(): array
{
    $rows = q("SELECT s.user_id, u.name, u.role, s.latitude, s.longitude, s.accuracy_m, s.updated_at
               FROM live_location_shares s
               JOIN users u ON u.id = s.user_id
               WHERE s.updated_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
               ORDER BY s.updated_at DESC")->fetchAll();
    $locations = [];
    $seenUsers = [];
    foreach ($rows as $row) {
        $userId = (int)$row['user_id'];
        if (isset($seenUsers[$userId])) continue;
        $latitude = parse_coord($row['latitude'], -90, 90);
        $longitude = parse_coord($row['longitude'], -180, 180);
        if ($latitude === false || $longitude === false || $latitude === null || $longitude === null) continue;
        $seenUsers[$userId] = true;
        $locations[] = [
            'id' => $userId,
            'name' => (string)$row['name'],
            'role' => (string)$row['role'],
            'lat' => $latitude,
            'lng' => $longitude,
            'accuracy' => $row['accuracy_m'] === null ? null : (float)$row['accuracy_m'],
            'updatedAt' => (string)$row['updated_at'],
        ];
    }
    return $locations;
}

function normalized_login_key(string $value): string
{
    return strtolower(trim((string)$value));
}

function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (is_array($ip)) { $ip = $ip[0] ?? ''; }
            $ip = trim((string)$ip);
            if ($ip !== '') return substr($ip, 0, 45);
        }
    }
    return 'unknown';
}

function login_attempt_state(string $identifier, string $ip): ?array
{
    $row = q('SELECT * FROM login_attempts WHERE identifier = ? AND ip_address = ? LIMIT 1', [normalized_login_key($identifier), $ip])->fetch();
    return $row === false ? null : $row;
}

function login_is_locked(string $identifier, string $ip): bool
{
    $row = login_attempt_state($identifier, $ip);
    if (!$row) return false;
    if (!empty($row['locked_until']) && strtotime((string)$row['locked_until']) > time()) return true;
    if ((int)$row['attempt_count'] >= 5 && strtotime((string)$row['last_attempt_at']) < time() - 900) {
        q('DELETE FROM login_attempts WHERE id = ?', [(int)$row['id']]);
        return false;
    }
    return false;
}

function record_failed_login(string $identifier, string $ip): void
{
    $key = normalized_login_key($identifier);
    $row = login_attempt_state($key, $ip);
    $now = date('Y-m-d H:i:s');
    if (!$row) {
        q('INSERT INTO login_attempts (identifier, ip_address, attempt_count, first_attempt_at, last_attempt_at, locked_until) VALUES (?, ?, 1, ?, ?, NULL)', [$key, $ip, $now, $now]);
        return;
    }
    $count = (int)$row['attempt_count'] + 1;
    $locked = $count >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
    q('UPDATE login_attempts SET attempt_count = ?, last_attempt_at = ?, locked_until = ? WHERE id = ?', [$count, $now, $locked, (int)$row['id']]);
}

function clear_login_attempts(string $identifier, string $ip): void
{
    q('DELETE FROM login_attempts WHERE identifier = ? AND ip_address = ?', [normalized_login_key($identifier), $ip]);
}

/* ---------------- flash messages / old input ---------------- */

function flash_set(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string
{
    $m = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $m;
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function take_old(): array
{
    $o = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return is_array($o) ? $o : [];
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

/** Every state-changing action calls this first: POST only + valid CSRF token. */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        flash_set('toast', 'Your session expired. Please try again.');
        redirect_back('index.php');
    }
}

/* ---------------- formatting ---------------- */

function today_iso(): string
{
    return date('Y-m-d');
}

function fmt_date($v): string
{
    $v = (string)$v;
    if ($v === '') return '';
    $t = strtotime(substr($v, 0, 10));
    return $t ? date('M j, Y', $t) : $v;
}

function pill_class(string $status): string
{
    return 'pill ' . preg_replace('/\s+/', '', $status);
}

function rid($id): string   { return 'R' . str_pad((string)$id, 3, '0', STR_PAD_LEFT); }

function first_name(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    return $parts[0] ?? $name;
}

function initials(string $name): string
{
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $w) {
        $out .= function_exists('mb_substr') ? mb_substr($w, 0, 1) : substr($w, 0, 1);
    }
    return $out;
}

function build_map_link($location, $lat, $lng): string
{
    if ($lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
        return 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng) . '&z=15';
    }
    if ($location) {
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($location);
    }
    return '#';
}

function row_link(string $href, string $inner): string
{
    return '<a class="rowlink" href="' . esc($href) . '">' . $inner . '</a>';
}

function like_escape(string $s): string
{
    return addcslashes($s, '%_\\');
}

function current_theme(): string
{
    return (($_COOKIE['ecobantay_theme'] ?? '') === 'dark') ? 'dark' : 'light';
}

/* ---------------- collection schedule ---------------- */

function get_collection_schedule(): array
{
    return q("SELECT id, day, area, waste_type, time FROM collection_schedule ORDER BY id")->fetchAll();
}

function next_pickup(array $schedule): ?array
{
    if (!$schedule) return null;
    $order = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $today = (int)date('w');
    $best = null;
    foreach ($schedule as $item) {
        $idx = array_search($item['day'], $order, true);
        if ($idx === false) continue;
        $diff = ($idx - $today + 7) % 7;
        if ($diff === 0) $diff = 7;
        if ($best === null || $diff < $best['diff']) {
            $best = $item;
            $best['diff'] = $diff;
        }
    }
    return $best ?? $schedule[0];
}

/* ---------------- validation helpers ---------------- */

/** Returns a coordinate as float, null when blank, or false when invalid. */
function parse_coord($v, float $min, float $max)
{
    $v = trim((string)$v);
    if ($v === '') return null;
    if (!is_numeric($v)) return false;
    $f = (float)$v;
    return ($f < $min || $f > $max) ? false : $f;
}

function user_field_error(string $name, string $username, string $email, string $contact): ?string
{
    if ($name === '' || $username === '' || $email === '') return 'Please complete all required fields.';
    if (strlen($name) > 100 || strlen($username) > 50) return 'Name or username is too long.';
    if (strpos($username, '@') !== false) return 'Username cannot contain @ when using username/email login.';
    if (strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Please enter a valid email address.';
    if ($contact !== '' && !preg_match('/^[0-9+\-\s()]{5,20}$/', $contact)) return 'Please enter a valid contact number.';
    return null;
}

function user_duplicate_error(string $username, string $email, ?int $excludeId, string $word = 'registered'): ?string
{
    $ex = $excludeId ?? 0;
    if (q('SELECT id FROM users WHERE LOWER(username)=LOWER(?) AND id<>?', [$username, $ex])->fetch()) {
        return "That username is already $word.";
    }
    if (q('SELECT id FROM users WHERE LOWER(email)=LOWER(?) AND id<>?', [$email, $ex])->fetch()) {
        return "That email is already $word.";
    }
    return null;
}

function resident_id_error(string $residentId, ?int $excludeId = null): ?string
{
    if ($residentId === '') return 'Please enter your barangay resident ID.';
    if (strlen($residentId) > 50 || preg_match('/[\x00-\x1F\x7F]/', $residentId)) {
        return 'Please enter a valid barangay resident ID (max 50 characters).';
    }
    $exclude = $excludeId ?? 0;
    if (q('SELECT id FROM users WHERE LOWER(TRIM(resident_id)) = LOWER(?) AND id <> ? LIMIT 1', [$residentId, $exclude])->fetch()) {
        return 'That barangay resident ID is already linked to another account.';
    }
    return null;
}

function private_resident_id_dir(): ?string
{
    $documentRootValue = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $documentRoot = $documentRootValue !== '' ? realpath($documentRootValue) : false;
    $configured = getenv('ECOBANTAY_PRIVATE_UPLOAD_DIR');
    if ($configured !== false && trim($configured) !== '') {
        $directory = rtrim($configured, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . 'resident_ids';
        if ($documentRoot !== false) {
            $publicRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
            $uploadPath = rtrim(str_replace('\\', '/', $directory), '/');
            if (strcasecmp($uploadPath, $publicRoot) === 0
                || strncasecmp($uploadPath, $publicRoot . '/', strlen($publicRoot) + 1) === 0) {
                return null;
            }
        }
        return $directory;
    }

    $projectRoot = realpath(dirname(__DIR__));
    if ($documentRoot === false || $projectRoot === false) return null;

    return $projectRoot . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'resident_ids';
}

function resident_id_photo_file(?string $filename): ?string
{
    if (!$filename || !preg_match('/\A[a-f0-9]{48}\.(jpg|png|webp)\z/', $filename)) return null;
    $directory = private_resident_id_dir();
    return $directory === null ? null : $directory . DIRECTORY_SEPARATOR . $filename;
}

/** Validate and save a private resident ID image. Returns [filename|null, error|null]. */
function save_resident_id_photo(?array $file): array
{
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, 'Please upload a clear photo of your barangay resident ID.'];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return [null, 'The ID photo is too large (maximum 5 MB).'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        return [null, 'The ID photo could not be uploaded. Please try again.'];
    }

    $image = @getimagesize($file['tmp_name']);
    $mime = $image['mime'] ?? '';
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$image || !isset($extensions[$mime])) {
        return [null, 'Upload a valid JPG, PNG, or WEBP photo of your ID.'];
    }

    $directory = private_resident_id_dir();
    if ($directory === null) {
        return [null, 'Private ID storage is not configured. Please contact the administrator.'];
    }
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        return [null, 'Private ID storage is unavailable. Please contact the administrator.'];
    }
    if (is_link($directory)) {
        return [null, 'Private ID storage could not be secured. Please contact the administrator.'];
    }
    $denyFile = $directory . DIRECTORY_SEPARATOR . '.htaccess';
    $denyRule = "Require all denied\n";
    if (is_link($denyFile)) {
        return [null, 'Private ID storage could not be secured. Please contact the administrator.'];
    }
    if (!is_file($denyFile) && file_put_contents($denyFile, $denyRule, LOCK_EX) === false) {
        return [null, 'Private ID storage could not be secured. Please contact the administrator.'];
    }
    if (trim((string)file_get_contents($denyFile)) !== trim($denyRule)) {
        return [null, 'Private ID storage could not be secured. Please contact the administrator.'];
    }
    if (DIRECTORY_SEPARATOR !== '\\' && !chmod($directory, 0700)) {
        return [null, 'Private ID storage permissions could not be secured. Please contact the administrator.'];
    }

    $filename = bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
    $path = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return [null, 'The ID photo could not be saved securely. Please try again.'];
    }
    if (DIRECTORY_SEPARATOR !== '\\' && !chmod($path, 0600)) {
        unlink($path);
        return [null, 'The ID photo could not be stored with secure permissions. Please contact the administrator.'];
    }
    return [$filename, null];
}

function delete_resident_id_photo(?string $filename): bool
{
    $path = resident_id_photo_file($filename);
    if ($path === null || !is_file($path)) return true;
    return unlink($path);
}

/* ---------------- photo upload ---------------- */

/** Validates and stores an uploaded report photo. Returns [relative path|null, error|null]. */
function save_report_photo(?array $file): array
{
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) return [null, 'The photo is too large (max 5 MB).'];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'The photo could not be uploaded. Please try again.'];
    if ($file['size'] > MAX_UPLOAD_BYTES) return [null, 'The photo is too large (max 5 MB).'];
    if (!is_uploaded_file($file['tmp_name'])) return [null, 'Invalid upload.'];

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) return [null, 'Only JPG, PNG, GIF or WEBP images are allowed.'];

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) return [null, 'The photo could not be saved.'];
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) return [null, 'The photo could not be saved.'];
    return [UPLOAD_URL . $name, null];
}

function delete_report_photo(?string $path): void
{
    if (!$path || strpos($path, UPLOAD_URL) !== 0) return;
    $file = UPLOAD_DIR . basename($path);
    if (is_file($file)) @unlink($file);
}
