<?php
/**
 * Application Configuration
 * Global settings and constants
 */

// Site settings
define('SITE_NAME', 'Sarap Local');

// Determine SITE_URL with HTTPS support for Render
$siteUrl = $_ENV['SITE_URL'] ?? 'http://localhost/sarap_local1';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $siteUrl = str_replace('http://', 'https://', $siteUrl);
}
define('SITE_URL', $siteUrl);
define('SITE_EMAIL', $_ENV['SITE_EMAIL'] ?? 'noreply@saraplocal.com');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);

// Email settings (SMTP)
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_SECURE', 'tls');

// Google Maps API
define('GOOGLE_MAPS_API_KEY', $_ENV['GOOGLE_MAPS_API_KEY'] ?? '');

// Google ReCAPTCHA
define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

// Biliran Province bounds (for map restriction)
define('MAP_CENTER_LAT', 11.5833);
define('MAP_CENTER_LNG', 124.4833);
define('MAP_BOUNDS_NORTH', 11.7);
define('MAP_BOUNDS_SOUTH', 11.4);
define('MAP_BOUNDS_EAST', 124.6);
define('MAP_BOUNDS_WEST', 124.3);

// Security
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination
define('ITEMS_PER_PAGE', 20);
define('FEED_ITEMS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (disable in production)
if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
