<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = isset($_GET['id']) ? '&id=' . (int)$_GET['id'] : '';

// Redirect to the persistent dashboard messages view
redirect(SITE_URL . '/pages/customer/dashboard.php?view=messages' . $id);
exit;
?>
