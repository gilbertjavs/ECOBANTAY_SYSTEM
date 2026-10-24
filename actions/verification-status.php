<?php
require __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Your session expired. Refresh this page to continue.']);
    exit;
}

$pendingUserId = (int)($_SESSION['pending_verification_user_id'] ?? 0);
if ($pendingUserId <= 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No pending registration is linked to this session.']);
    exit;
}

$user = q("SELECT id, name, role, verified FROM users WHERE id = ? AND role = 'Resident'", [$pendingUserId])->fetch();
if (!$user) {
    unset($_SESSION['pending_verification_user_id']);
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'The pending account could not be found. Contact the barangay administrator.']);
    exit;
}

if (!(int)$user['verified']) {
    echo json_encode(['ok' => true, 'state' => 'pending']);
    exit;
}

session_regenerate_id(true);
$_SESSION['csrf'] = bin2hex(random_bytes(32));
unset($_SESSION['pending_verification_user_id']);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['name'];
flash_set('toast', 'Your account is verified. You are now signed in.');

echo json_encode(['ok' => true, 'state' => 'signed_in', 'redirect' => url(home_for($user['role']))]);
