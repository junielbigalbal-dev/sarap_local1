<?php
// Database setup script for Render
// Run this once to create all tables

require_once 'config/database.php';

echo "Starting database setup...\n\n";

$sql = file_get_contents('sql/schema-postgresql.sql');

try {
    $pdo->exec($sql);
    echo "✅ Database setup completed successfully!\n";
    echo "Tables created:\n";
    echo "- users\n";
    echo "- user_profiles\n";
    echo "- categories\n";
    echo "- products\n";
    echo "- orders\n";
    echo "- order_items\n";
    echo "- carts\n";
    echo "- messages\n";
    echo "- email_verifications\n\n";
    echo "Default admin user created:\n";
    echo "Email: admin@saraplocal.com\n";
    echo "Password: password\n\n";
    echo "🎉 You can now use your application!\n";
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
