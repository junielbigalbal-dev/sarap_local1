<?php
/**
 * Database Connection Test
 * Visit this page to check if your database is connected
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Test</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>Database Connection Test</h1>";

try {
    // Test connection
    $result = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Database connection: SUCCESS</p>";
    
    // Check if tables exist
    $tables = ['users', 'user_profiles', 'email_verifications', 'products', 'orders'];
    echo "<h2>Table Check:</h2><ul>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<li class='success'>✅ Table '$table' exists ($count rows)</li>";
        } catch (PDOException $e) {
            echo "<li class='error'>❌ Table '$table' does NOT exist</li>";
        }
    }
    echo "</ul>";
    
    // If tables don't exist, show setup link
    echo "<hr>";
    echo "<p class='info'>If tables are missing, run: <a href='/setup-database.php'>Setup Database</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection FAILED</p>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<p class='info'>Make sure you've:</p>";
    echo "<ol>";
    echo "<li>Created a PostgreSQL database on Render</li>";
    echo "<li>Linked it to your Web Service (this sets DATABASE_URL)</li>";
    echo "<li>Redeployed your service</li>";
    echo "</ol>";
}

echo "</body></html>";
