<?php
require __DIR__ . '/includes/init.php';
if ($u = current_user()) redirect(home_for($u['role']));
auth_page('register');
