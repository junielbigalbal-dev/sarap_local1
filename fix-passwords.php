<?php
/**
 * Fix Demo Account Passwords
 * Run this script once to update demo account passwords
 */

require_once __DIR__ . '/config/config.php';
$pdo = require __DIR__ . '/config/database.php';

// Hash the passwords properly
$customerPassword = password_hash('customer123', PASSWORD_BCRYPT, ['cost' => 12]);
$vendorPassword = password_hash('vendor123', PASSWORD_BCRYPT, ['cost' => 12]);
$adminPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

try {
    // Update customer password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$customerPassword, 'customer1@example.com']);
    echo "✅ Customer password updated\n";
    
    // Update vendor password
    $stmt->execute([$vendorPassword, 'vendor1@example.com']);
    echo "✅ Vendor password updated\n";
    
    // Update admin password
    $stmt->execute([$adminPassword, 'admin@saraplocal.com']);
    echo "✅ Admin password updated\n";
    
    // Verify accounts are active and verified
    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, status = 'active' WHERE email IN (?, ?, ?)");
    $stmt->execute(['customer1@example.com', 'vendor1@example.com', 'admin@saraplocal.com']);
    echo "✅ All accounts activated and verified\n";
    
    echo "\n🎉 Demo accounts are ready!\n\n";
    echo "Login credentials:\n";
    echo "Customer: customer1@example.com / customer123\n";
    echo "Vendor: vendor1@example.com / vendor123\n";
    echo "Admin: admin@saraplocal.com / admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

