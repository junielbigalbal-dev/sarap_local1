<?php
/**
 * Migration script to create notifications table
 * This can be run safely even if the table already exists
 */

require_once 'config/database.php';

echo "Creating notifications table...\n\n";

$sql = "
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
";

try {
    $pdo->exec($sql);
    echo "✅ Notifications table created successfully!\n";
    echo "The table includes:\n";
    echo "- id (primary key)\n";
    echo "- user_id (foreign key to users)\n";
    echo "- type (notification type)\n";
    echo "- title (notification title)\n";
    echo "- message (notification message)\n";
    echo "- link (optional link)\n";
    echo "- is_read (read status)\n";
    echo "- created_at (timestamp)\n\n";
    echo "🎉 You can now use the notifications feature!\n";
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nIf the table already exists, this is normal and you can ignore this error.\n";
}
?>
