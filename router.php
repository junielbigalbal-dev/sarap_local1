<?php
/**
 * Router for PHP Built-in Server
 * Ensures static files (CSS, JS, images) and PHP files are served correctly
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$filepath = __DIR__ . $uri;

// If it's a directory, look for index.php
if (is_dir($filepath)) {
    if (file_exists($filepath . '/index.php')) {
        require $filepath . '/index.php';
        return true;
    }
}

// If it's a PHP file, execute it
if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'php') {
    require $filepath;
    return true;
}

// Serve static files directly
if (file_exists($filepath)) {
    // Get the file extension
    $ext = pathinfo($filepath, PATHINFO_EXTENSION);
    
    // Set appropriate content type
    $contentTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    if (isset($contentTypes[$ext])) {
        header('Content-Type: ' . $contentTypes[$ext]);
    }
    
    return false; // Serve the file
}

// If file doesn't exist, return 404
http_response_code(404);
echo "404 - File not found";
return true;

