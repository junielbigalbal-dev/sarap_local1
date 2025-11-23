<?php
/**
 * Database Configuration
 * PDO connection with error handling
 */

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Database credentials
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'sarap_local');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

<?php
/**
 * Database Configuration
 * PDO connection with error handling
 */

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Database credentials
// These defines are now mostly superseded by the new connection logic,
// but kept for compatibility or if other parts of the app still rely on them.
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'sarap_local');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Database configuration - supports both MySQL (local) and PostgreSQL (Render)

// Check if running on Render (DATABASE_URL environment variable exists)
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // RENDER ENVIRONMENT - Use PostgreSQL
    $db = parse_url($database_url);
    
    $host = $db['host'];
    $port = isset($db['port']) ? $db['port'] : 5432;
    $dbname = ltrim($db['path'], '/');
    $username = $db['user'];
    $password = $db['pass'];
    
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Render Database Connection failed: " . $e->getMessage());
    }
} else {
    // LOCAL ENVIRONMENT - Use MySQL (XAMPP)
    // Use defined constants for local environment if available, otherwise fallback
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USER;
    $password = DB_PASS;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Local Database Connection failed: " . $e->getMessage());
    }
}

// Return connection for use in other files
return $pdo;
