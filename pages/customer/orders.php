<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

// Redirect to the persistent dashboard orders view
redirect(SITE_URL . '/pages/customer/dashboard.php?view=orders');
exit;
?>
