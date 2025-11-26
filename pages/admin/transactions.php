<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? 'all';

// Get all completed orders (transactions)
$sql = "
    SELECT 
        o.*,
        u.email as customer_email,
        COALESCE(cup.name, u.email) as customer_name,
        v.email as vendor_email,
        COALESCE(vup.business_name, vup.name, v.email) as vendor_name
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.id
    LEFT JOIN user_profiles cup ON u.id = cup.user_id
    LEFT JOIN users v ON o.vendor_id = v.id
    LEFT JOIN user_profiles vup ON v.id = vup.user_id
    WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
";

if ($statusFilter !== 'all') {
    $sql .= " AND o.status = :status";
}

$sql .= " ORDER BY o.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':start_date', $startDate);
$stmt->bindValue(':end_date', $endDate);

if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter);
}

$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_amount,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_amount,
        SUM(CASE WHEN status = 'cancelled' THEN total_amount ELSE 0 END) as cancelled_amount
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo SITE_NAME; ?></title>
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
                        <h1>Transactions</h1>
                        <p class="header-subtitle">View all financial transactions</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Date Filter -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <form method="GET" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-input" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-input" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('transactionsTable', 'transactions.csv')">
                                📥 Export
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
                            <div class="stat-label">Total Transactions</div>
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
                            <div class="stat-value"><?php echo formatCurrency($stats['completed_amount'] ?? 0); ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['cancelled_amount'] ?? 0); ?></div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Transactions</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="transactionsTable">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Date & Time</th>
                                    <th>Customer</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><strong>#<?php echo $transaction['id']; ?></strong></td>
                                    <td><?php echo formatDateTime($transaction['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['vendor_name']); ?></td>
                                    <td><strong><?php echo formatCurrency($transaction['total_amount']); ?></strong></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'accepted' => 'info',
                                            'preparing' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$transaction['status']] ?? 'gray';
                                        ?>
                                        <span class="badge badge-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $transaction['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">Cash on Delivery</span>
                                    </td>
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
