<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get recent messages
$messages = [];
try {
    $stmt = $pdo->query("
        SELECT tm.*, st.ticket_number, st.subject, u.email as sender_email, 
               COALESCE(up.name, u.email) as sender_name,
               st.status as ticket_status
        FROM ticket_messages tm
        JOIN support_tickets st ON tm.ticket_id = st.id
        JOIN users u ON tm.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        ORDER BY tm.created_at DESC
        LIMIT 50
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-layout.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="btn btn-icon btn-secondary" style="display: none;">☰</button>
                    <div class="header-title">
                        <h1>Messages</h1>
                        <p class="header-subtitle">Recent support communications</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <?php if (empty($messages)): ?>
                <div class="card" style="text-align: center; padding: 48px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">💬</div>
                    <h3>No Messages Yet</h3>
                    <p style="color: var(--gray-600);">Recent messages from support tickets will appear here.</p>
                    <a href="support.php" class="btn btn-primary" style="margin-top: 16px;">View Support Tickets</a>
                </div>
                <?php else: ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Sender</th>
                                    <th>Message</th>
                                    <th>Ticket</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td style="white-space: nowrap; color: var(--gray-600); font-size: 0.875rem;">
                                        <?php echo formatDateTime($msg['created_at']); ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--gray-600);"><?php echo htmlspecialchars($msg['sender_email']); ?></div>
                                    </td>
                                    <td>
                                        <div style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($msg['message']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="support-detail.php?id=<?php echo $msg['ticket_id']; ?>" style="color: var(--admin-primary); font-weight: 500;">
                                            #<?php echo htmlspecialchars($msg['ticket_number']); ?>
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--gray-600);">
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="support-detail.php?id=<?php echo $msg['ticket_id']; ?>" class="btn btn-sm btn-secondary">
                                            Reply
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }
    </script>
</body>
</html>
