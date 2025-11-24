<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

// Redirect to the persistent dashboard view
$queryString = $_SERVER['QUERY_STRING'] ?? '';
redirect(SITE_URL . '/pages/customer/dashboard.php' . ($queryString ? '?' . $queryString . '&view=products' : '?view=products'));
exit;
?>
