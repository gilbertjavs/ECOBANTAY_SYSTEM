<?php
require __DIR__ . '/../includes/init.php';
require_post();
$user = require_login();
$mode = (string)($_POST['mode'] ?? '');
$wantsJson = strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;

if ($mode === 'stop') {
    stop_live_location_sharing((int)$user['id']);
    flash_set('toast', 'Live location sharing has been stopped.');
    redirect('location-sharing.php');
}

if ($mode !== 'enable' && $mode !== 'update') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Choose a valid location-sharing action.']);
    exit;
}

if ($mode === 'enable' && ($_POST['consent'] ?? '') !== 'yes') {
    if ($wantsJson) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Confirm consent before sharing your location.']);
        exit;
    }
    flash_set('toast', 'Please confirm consent before sharing your location.');
    redirect('location-sharing.php');
}
if ($mode === 'update' && empty($_SESSION['location_share_enabled'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Live location sharing is not enabled for this session.']);
    exit;
}

$latitude = parse_coord($_POST['latitude'] ?? '', -90, 90);
$longitude = parse_coord($_POST['longitude'] ?? '', -180, 180);
$accuracyInput = trim((string)($_POST['accuracy'] ?? ''));
$accuracy = $accuracyInput === '' ? null : (is_numeric($accuracyInput) ? (float)$accuracyInput : false);
if ($latitude === false || $longitude === false || $latitude === null || $longitude === null
    || $accuracy === false || ($accuracy !== null && ($accuracy < 0 || $accuracy > 100000))) {
    if ($mode === 'enable') {
        if ($wantsJson) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'The browser did not provide valid location coordinates. Sharing was not started.']);
            exit;
        }
        flash_set('toast', 'The browser did not provide valid location coordinates. Sharing was not started.');
        redirect('location-sharing.php');
    }
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'The browser did not provide valid location coordinates.']);
    exit;
}

save_live_location((int)$user['id'], $latitude, $longitude, $accuracy);
if ($mode === 'enable') {
    $_SESSION['location_share_enabled'] = true;
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode(['ok' => true]);
        exit;
    }
    flash_set('toast', 'Live location sharing is on. You can stop it at any time.');
    redirect('location-sharing.php');
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
echo json_encode(['ok' => true]);
