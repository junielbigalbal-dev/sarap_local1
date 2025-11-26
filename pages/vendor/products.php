<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

// Redirect to the persistent dashboard products view
redirect(SITE_URL . '/pages/vendor/dashboard.php?view=products');
exit;
?>
