<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');
admin_header('about');
about_content(true);
admin_footer();
