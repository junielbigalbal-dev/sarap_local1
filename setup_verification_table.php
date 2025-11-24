<?php
require_once __DIR__ . '/config/config.php';
$pdo = require_once __DIR__ . '/config/database.php';

try {
    echo "Checking database tables...\n";
    
    // Create email_verifications table
    $sql = "CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✅ Table 'email_verifications' created or already exists.\n";
    
    // Check if email_verified column exists in users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER role");
        echo "✅ Column 'email_verified' added to 'users' table.\n";
    } else {
        echo "✅ Column 'email_verified' already exists in 'users' table.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
