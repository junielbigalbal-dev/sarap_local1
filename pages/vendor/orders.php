<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$status = isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '';

// Redirect to the persistent dashboard orders view
redirect(SITE_URL . '/pages/vendor/dashboard.php?view=orders' . $status);
exit;
?>
