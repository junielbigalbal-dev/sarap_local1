<?php
// Fix column names in user_profiles table
require_once 'config/database.php';

echo "Fixing column names...\n\n";

try {
    // Rename latitude to lat
    $pdo->exec("ALTER TABLE user_profiles RENAME COLUMN latitude TO lat");
    echo "✅ Renamed latitude to lat\n";
    
    // Rename longitude to lng
    $pdo->exec("ALTER TABLE user_profiles RENAME COLUMN longitude TO lng");
    echo "✅ Renamed longitude to lng\n";
    
    echo "\n🎉 Database columns fixed!\n";
    echo "You can now try signing up again.\n";
} catch(PDOException $e) {
    // Check if error is because columns already exist or don't exist
    if (strpos($e->getMessage(), 'does not exist') !== false) {
        echo "⚠️ Columns might already be renamed or don't exist.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>
