<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Order.php';

requireRole('admin');

$userModel = new User($pdo);
$productModel = new Product($pdo);
$orderModel = new Order($pdo);

// Get current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get comprehensive statistics
$stats = [];

// Total Orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $stmt->fetch()['count'];

// Total Revenue
$stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Active Vendors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor' AND status = 'active'");
$stats['active_vendors'] = $stmt->fetch()['count'];

// Total Customers
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['total_customers'] = $stmt->fetch()['count'];

// Pending Approvals (vendors + products)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor' AND status = 'pending'");
$pendingVendors = $stmt->fetch()['count'];
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'pending'");
$pendingProducts = $stmt->fetch()['count'];
$stats['pending_approvals'] = $pendingVendors + $pendingProducts;

// Today's Orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $stmt->fetch()['count'];

// Today's Revenue
$stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
$stats['today_revenue'] = $stmt->fetch()['total'] ?? 0;

// Average Order Value
$stats['avg_order_value'] = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;

// Order Status Distribution
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
");
$orderStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Orders
$stmt = $pdo->query("
    SELECT o.*, u.email as customer_email, v.email as vendor_email
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.id
    LEFT JOIN users v ON o.vendor_id = v.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Live Activity Log
$stmt = $pdo->query("
    SELECT 'order' as type, id, created_at, status FROM orders
    UNION ALL
    SELECT 'user' as type, id, created_at, role as status FROM users
    UNION ALL
    SELECT 'product' as type, id, created_at, status FROM products
    ORDER BY created_at DESC
    LIMIT 15
");
$activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly Revenue Data (for chart)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(total) as revenue
    FROM orders
    WHERE status = 'completed'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Vendors
$stmt = $pdo->query("
    SELECT 
        u.id, u.email, 
        COALESCE(up.business_name, up.name, u.email) as name,
        COUNT(o.id) as order_count,
        SUM(o.total) as total_sales
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN orders o ON u.id = o.vendor_id AND o.status = 'completed'
    WHERE u.role = 'vendor'
    GROUP BY u.id
    ORDER BY total_sales DESC
    LIMIT 5
");
$topVendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-layout.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .activity-item {
            padding: 12px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.15s;
        }
        .activity-item:hover {
            background: var(--gray-50);
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .activity-icon.order { background: rgba(198, 125, 59, 0.1); color: var(--admin-primary); }
        .activity-icon.user { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .activity-icon.product { background: rgba(16, 185, 129, 0.1); color: var(--success); }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="btn btn-icon btn-secondary" style="display: none;">
                        ☰
                    </button>
                    <div class="header-title">
                        <h1>Dashboard Overview</h1>
                        <p class="header-subtitle">Welcome back, <?php echo htmlspecialchars($currentUser['email'] ?? 'Admin'); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <span class="header-search-icon">🔍</span>
                        <input type="text" placeholder="Search anything...">
                    </div>
                    <div class="header-notifications">
                        <span class="notification-icon">🔔</span>
                        <?php if ($stats['pending_approvals'] > 0): ?>
                        <span class="notification-badge"><?php echo $stats['pending_approvals']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="header-user">
                        <div class="user-avatar">A</div>
                        <div class="user-info">
                            <div class="user-name">Admin</div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
                <!-- Pending Approvals Alert -->
                <?php if ($stats['pending_approvals'] > 0): ?>
                <div class="alert alert-warning">
                    <span style="font-size: 1.5rem;">⚠️</span>
                    <div>
                        <strong>Pending Approvals</strong><br>
                        You have <?php echo $stats['pending_approvals']; ?> items pending approval 
                        (<?php echo $pendingVendors; ?> vendors, <?php echo $pendingProducts; ?> products).
                        <a href="vendors.php?status=pending" style="color: inherit; text-decoration: underline; font-weight: 600;">Review now</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <!-- Total Orders -->
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon primary">📦</div>
                            <div class="stat-trend up">
                                <span>↑</span>
                                <span>12%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-footer">
                            <?php echo $stats['today_orders']; ?> orders today
                        </div>
                    </div>

                    <!-- Total Revenue -->
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon success">💰</div>
                            <div class="stat-trend up">
                                <span>↑</span>
                                <span>8%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-footer">
                            <?php echo formatCurrency($stats['today_revenue']); ?> today
                        </div>
                    </div>

                    <!-- Active Vendors -->
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon warning">🏪</div>
                            <div class="stat-trend up">
                                <span>↑</span>
                                <span>5%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($stats['active_vendors']); ?></div>
                            <div class="stat-label">Active Vendors</div>
                        </div>
                        <div class="stat-footer">
                            <?php echo $pendingVendors; ?> pending approval
                        </div>
                    </div>

                    <!-- Total Customers -->
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon info">👥</div>
                            <div class="stat-trend up">
                                <span>↑</span>
                                <span>15%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                        <div class="stat-footer">
                            Growing user base
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <!-- Revenue Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Revenue Tracking</h3>
                            <div class="card-actions">
                                <select class="form-select" style="width: auto; padding: 8px 12px;">
                                    <option>Last 12 Months</option>
                                    <option>Last 6 Months</option>
                                    <option>Last 3 Months</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Distribution -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Status</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="orderStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders & Activity -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <!-- Recent Orders -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Orders</h3>
                            <a href="orders.php" class="btn btn-sm btn-outline">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Vendor</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($order['vendor_email'] ?? 'N/A'); ?></td>
                                        <td><strong><?php echo formatCurrency($order['total']); ?></strong></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'accepted' => 'info',
                                                'preparing' => 'info',
                                                'in_transit' => 'primary',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $color = $statusColors[$order['status']] ?? 'gray';
                                            ?>
                                            <span class="badge badge-<?php echo $color; ?>">
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

                    <!-- Live Activity Log -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Live Activity</h3>
                        </div>
                        <div class="card-body" style="padding: 0; max-height: 400px; overflow-y: auto;">
                            <?php foreach ($activityLog as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['type']; ?>">
                                    <?php
                                    $icons = ['order' => '📦', 'user' => '👤', 'product' => '🍽️'];
                                    echo $icons[$activity['type']];
                                    ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 0.875rem;">
                                        <?php
                                        $labels = [
                                            'order' => 'New Order',
                                            'user' => 'New User',
                                            'product' => 'New Product'
                                        ];
                                        echo $labels[$activity['type']];
                                        ?>
                                        #<?php echo $activity['id']; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                                        <?php echo formatDateTime($activity['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Vendors -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top Performing Vendors</h3>
                        <a href="vendors.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Email</th>
                                    <th>Total Orders</th>
                                    <th>Total Sales</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topVendors as $vendor): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vendor['name'] ?? $vendor['email']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                                    <td><?php echo number_format($vendor['order_count']); ?></td>
                                    <td><strong><?php echo formatCurrency($vendor['total_sales'] ?? 0); ?></strong></td>
                                    <td>
                                        <a href="vendor-detail.php?id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
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
        // Show mobile menu button on small screens
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }
    </script>
</body>
</html>
