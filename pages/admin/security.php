<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get login logs
$stmt = $pdo->query("
    SELECT * FROM login_logs
    ORDER BY created_at DESC
    LIMIT 100
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM login_logs WHERE status = 'failed' AND DATE(created_at) = CURDATE()");
$todayFailed = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM login_logs WHERE status = 'success' AND DATE(created_at) = CURDATE()");
$todaySuccess = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Login Logs - <?php echo SITE_NAME; ?></title>
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
                        <h1>Security & Login Logs</h1>
                        <p class="header-subtitle">Monitor login activity and security</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $todaySuccess; ?></div>
                            <div class="stat-label">Successful Logins Today</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $todayFailed; ?></div>
                            <div class="stat-label">Failed Logins Today</div>
                        </div>
                    </div>
                </div>

                <!-- Login Logs -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">Recent Login Activity</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['email']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $log['status'] === 'success' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($log['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['failure_reason'] ?? '-'); ?></td>
                                    <td><?php echo formatDateTime($log['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
