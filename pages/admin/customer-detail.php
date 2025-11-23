<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$customerId = $_GET['id'] ?? null;

if (!$customerId) {
    header('Location: customers.php');
    exit;
}

// Get customer details
$stmt = $pdo->prepare("
    SELECT u.*, 
           up.name as customer_name,
           up.phone, up.address
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ? AND u.role = 'customer'
");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Get customer orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           v.email as vendor_email,
           COALESCE(vup.business_name, vup.name, v.email) as vendor_name
    FROM orders o
    LEFT JOIN users v ON o.vendor_id = v.id
    LEFT JOIN user_profiles vup ON v.id = vup.user_id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 50
");
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_spent,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders 
    WHERE customer_id = ?
");
$stmt->execute([$customerId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - <?php echo SITE_NAME; ?></title>
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
                        <h1><?php echo htmlspecialchars($customer['customer_name'] ?? $customer['email']); ?></h1>
                        <p class="header-subtitle">
                            <a href="customers.php" style="color: var(--admin-primary);">← Back to Customers</a>
                        </p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Customer Info Card -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Customer Information</h3>
                        <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'gray'; ?>">
                            <?php echo ucfirst($customer['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Email</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($customer['email']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Phone</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Address</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Registered</div>
                                <div style="font-weight: 600;"><?php echo formatDateTime($customer['created_at']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                            <div class="stat-label">Completed Orders</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $stats['cancelled_orders']; ?></div>
                            <div class="stat-label">Cancelled Orders</div>
                        </div>
                    </div>
                </div>

                <!-- Order History -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Order History (<?php echo count($orders); ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['vendor_name']); ?></td>
                                    <td><strong><?php echo formatCurrency($order['total']); ?></strong></td>
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
                                        $color = $statusColors[$order['status']] ?? 'gray';
                                        ?>
                                        <span class="badge badge-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($order['created_at']); ?></td>
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
