<?php
require __DIR__ . '/../includes/init.php';
require_role('Resident');
resident_header('about');
about_content(false);
resident_footer();
