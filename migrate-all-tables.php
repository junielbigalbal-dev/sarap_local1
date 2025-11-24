<?php
/**
 * Comprehensive migration script to ensure all required tables exist
 * Safe to run multiple times - only creates missing tables
 */

require_once 'config/database.php';

echo "=== Database Migration Script ===\n";
echo "Checking and creating missing tables...\n\n";

// Check which tables exist
$existingTables = [];
try {
    $result = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
    ");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existingTables[] = $row['table_name'];
    }
    echo "Found " . count($existingTables) . " existing tables.\n\n";
} catch(PDOException $e) {
    echo "Warning: Could not check existing tables: " . $e->getMessage() . "\n\n";
}

// Read and execute the full schema
$sql = file_get_contents('sql/schema-postgresql.sql');

try {
    echo "Executing schema...\n";
    $pdo->exec($sql);
    echo "✅ Schema execution completed!\n\n";
    
    // Verify all required tables now exist
    $requiredTables = [
        'users',
        'user_profiles',
        'categories',
        'products',
        'orders',
        'order_items',
        'carts',
        'messages',
        'notifications',
        'email_verifications'
    ];
    
    echo "Verifying tables:\n";
    $result = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
    ");
    $currentTables = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $currentTables[] = $row['table_name'];
    }
    
    $allPresent = true;
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $currentTables);
        $status = $exists ? "✅" : "❌";
        echo "$status $table\n";
        if (!$exists) {
            $allPresent = false;
        }
    }
    
    echo "\n";
    if ($allPresent) {
        echo "🎉 All required tables are present!\n";
        echo "\nDefault admin credentials:\n";
        echo "Email: admin@saraplocal.com\n";
        echo "Password: password\n";
    } else {
        echo "⚠️  Some tables are still missing. Please check the error messages above.\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>
