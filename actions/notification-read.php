<?php
require __DIR__ . '/../includes/init.php';
require_post();
$user = require_role('Resident');
q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$user['id']]);
redirect('resident/notifications.php');
