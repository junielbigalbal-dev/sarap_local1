<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get vendor payouts
$payouts = [];
try {
    $stmt = $pdo->prepare("
        SELECT vp.*, u.email as vendor_email, 
               COALESCE(up.business_name, up.name, u.email) as vendor_name
        FROM vendor_payouts vp
        LEFT JOIN users u ON vp.vendor_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE vp.period_start >= ? AND vp.period_end <= ?
        ORDER BY vp.created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet - migrations not run
}

// Get summary stats
$stats = ['total_payouts' => 0, 'total_amount' => 0, 'total_commission' => 0, 'total_net' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payouts,
            SUM(amount) as total_amount,
            SUM(commission) as total_commission,
            SUM(net_amount) as total_net
        FROM vendor_payouts
        WHERE period_start >= ? AND period_end <= ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = $result;
    }
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments & Transactions - <?php echo SITE_NAME; ?></title>
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
                        <h1>Payments & Transactions</h1>
                        <p class="header-subtitle">Vendor payouts and commission tracking</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Date Filter -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <form method="GET" style="display: flex; gap: 12px; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-input" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-input" value="<?php echo $endDate; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('payoutsTable', 'payouts.csv')">
                                📥 Export
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($stats['total_payouts']); ?></div>
                            <div class="stat-label">Total Payouts</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['total_amount'] ?? 0); ?></div>
                            <div class="stat-label">Total Amount</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['total_commission'] ?? 0); ?></div>
                            <div class="stat-label">Total Commission</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['total_net'] ?? 0); ?></div>
                            <div class="stat-label">Net Payouts</div>
                        </div>
                    </div>
                </div>

                <!-- Payouts Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">Vendor Payouts</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="payoutsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vendor</th>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Net Amount</th>
                                    <th>Status</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payouts as $payout): ?>
                                <tr>
                                    <td><strong>#<?php echo $payout['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($payout['vendor_name'] ?? $payout['vendor_email']); ?></td>
                                    <td>
                                        <?php echo date('M d', strtotime($payout['period_start'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($payout['period_end'])); ?>
                                    </td>
                                    <td><?php echo formatCurrency($payout['amount']); ?></td>
                                    <td><?php echo formatCurrency($payout['commission']); ?></td>
                                    <td><strong><?php echo formatCurrency($payout['net_amount']); ?></strong></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'completed' => 'success',
                                            'failed' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge badge-<?php echo $statusColors[$payout['status']]; ?>">
                                            <?php echo ucfirst($payout['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $payout['processed_at'] ? formatDateTime($payout['processed_at']) : 'Pending'; ?></td>
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
