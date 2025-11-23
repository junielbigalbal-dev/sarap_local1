<?php
// Add missing columns to existing tables
require_once 'config/database.php';

echo "Adding missing columns...\n\n";

try {
    // Add status column to users table if it doesn't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'active'");
    echo "✅ Added status column to users table\n";
    
    // Add status column to products table if it doesn't exist
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'active'");
    echo "✅ Added status column to products table\n";
    
    echo "\n🎉 Database update completed!\n";
    echo "You can now visit your site: https://sarap-local1.onrender.com\n";
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
