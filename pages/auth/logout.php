<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

logoutUser();
setFlashMessage('You have been logged out successfully', 'success');
redirect(SITE_URL . '/pages/auth/login.php');
