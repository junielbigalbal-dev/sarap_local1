<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sales data for chart
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders
    FROM orders
    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$startDate, $endDate]);
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling items
$stmt = $pdo->query("
    SELECT p.name, p.price, COUNT(oi.id) as sales_count, SUM(oi.quantity * oi.price) as total_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY sales_count DESC
    LIMIT 10
");
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top vendors
$stmt = $pdo->query("
    SELECT 
        COALESCE(up.business_name, up.name, u.email) as name, 
        u.email, 
        COUNT(o.id) as order_count, 
        SUM(o.total_amount) as revenue
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN orders o ON u.id = o.vendor_id AND o.status = 'completed'
    WHERE u.role = 'vendor'
    GROUP BY u.id, up.business_name, up.name, u.email
    ORDER BY revenue DESC
    LIMIT 10
");
$topVendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Peak hours data
$stmt = $pdo->query("
    SELECT EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as order_count
    FROM orders
    GROUP BY EXTRACT(HOUR FROM created_at)
    ORDER BY hour ASC
");
$peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-layout.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="btn btn-icon btn-secondary" style="display: none;">☰</button>
                    <div class="header-title">
                        <h1>Analytics & Reports</h1>
                        <p class="header-subtitle">Comprehensive business insights</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Date Range Filter -->
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
                            <button type="button" class="btn btn-secondary" onclick="window.print()">📄 Print Report</button>
                        </form>
                    </div>
                </div>

                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <!-- Sales Trend -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sales Trend</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Peak Hours -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Peak Hours</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="peakHoursChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products & Vendors -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <!-- Top Selling Items -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Top Selling Items</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Product</th>
                                        <th>Sales</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><strong>#<?php echo $rank++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo $product['sales_count']; ?> orders</td>
                                        <td><strong><?php echo formatCurrency($product['total_revenue'] ?? 0); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Vendors -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Best Performing Vendors</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Vendor</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; foreach ($topVendors as $vendor): ?>
                                    <tr>
                                        <td><strong>#<?php echo $rank++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($vendor['name'] ?? $vendor['email']); ?></td>
                                        <td><?php echo $vendor['order_count']; ?></td>
                                        <td><strong><?php echo formatCurrency($vendor['revenue'] ?? 0); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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

        // Sales Trend Chart
        const salesTrendCtx = document.getElementById('salesTrendChart');
        new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($salesData, 'date')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($salesData, 'revenue')); ?>,
                    borderColor: '#C67D3B',
                    backgroundColor: 'rgba(198, 125, 59, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Peak Hours Chart
        const peakHoursCtx = document.getElementById('peakHoursChart');
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($h) { return $h['hour'] . ':00'; }, $peakHours)); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode(array_column($peakHours, 'order_count')); ?>,
                    backgroundColor: '#C67D3B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
