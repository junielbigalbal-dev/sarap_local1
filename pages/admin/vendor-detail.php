<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$vendorId = $_GET['id'] ?? null;

if (!$vendorId) {
    header('Location: vendors.php');
    exit;
}

// Get vendor details
$stmt = $pdo->prepare("
    SELECT u.*, 
           COALESCE(up.business_name, up.name, u.email) as vendor_name,
           up.phone, up.address, up.business_hours
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ? AND u.role = 'vendor'
");
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    header('Location: vendors.php');
    exit;
}

// Get vendor products
$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC");
$stmt->execute([$vendorId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vendor orders
$stmt = $pdo->prepare("
    SELECT o.*, u.email as customer_email
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.id
    WHERE o.vendor_id = ?
    ORDER BY o.created_at DESC
    LIMIT 20
");
$stmt->execute([$vendorId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total) as revenue FROM orders WHERE vendor_id = ? AND status = 'completed'");
$stmt->execute([$vendorId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Details - <?php echo SITE_NAME; ?></title>
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
                        <h1><?php echo htmlspecialchars($vendor['name'] ?? $vendor['email']); ?></h1>
                        <p class="header-subtitle">
                            <a href="vendors.php" style="color: var(--admin-primary);">← Back to Vendors</a>
                        </p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Vendor Info Card -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Vendor Information</h3>
                        <span class="badge badge-<?php echo $vendor['status'] === 'active' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($vendor['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Email</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($vendor['email']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Phone</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($vendor['phone'] ?? 'N/A'); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Registered</div>
                                <div style="font-weight: 600;"><?php echo formatDateTime($vendor['created_at']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 4px;">Last Login</div>
                                <div style="font-weight: 600;"><?php echo formatDateTime($vendor['updated_at']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo count($products); ?></div>
                            <div class="stat-label">Total Products</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $stats['count']; ?></div>
                            <div class="stat-label">Completed Orders</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['revenue'] ?? 0); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value">⭐ 4.8</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Products (<?php echo count($products); ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?php echo $product['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                    <td><?php echo formatCurrency($product['price']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($product['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Orders</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td><strong><?php echo formatCurrency($order['total']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
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
