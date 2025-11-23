<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT 
        u.*,
        up.name as customer_name,
        up.phone,
        COUNT(DISTINCT o.id) as order_count,
        SUM(CASE WHEN o.status = 'completed' THEN o.total ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN orders o ON u.id = o.customer_id
    WHERE u.role = 'customer'
";

if ($searchQuery) {
    $sql .= " AND (u.email LIKE :search OR u.name LIKE :search)";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);

if ($searchQuery) {
    $stmt->bindValue(':search', "%$searchQuery%");
}

$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$totalCustomers = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$newCustomers = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - <?php echo SITE_NAME; ?></title>
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
                        <h1>Customer Management</h1>
                        <p class="header-subtitle">View and manage customer accounts</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($totalCustomers); ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo number_format($newCustomers); ?></div>
                            <div class="stat-label">New (Last 30 Days)</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo count($customers); ?></div>
                            <div class="stat-label">Active Customers</div>
                        </div>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Customers</h3>
                        <div class="table-filters">
                            <form method="GET" style="display: flex; gap: 12px;">
                                <input type="text" name="search" class="form-input" placeholder="Search customers..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" style="width: 300px;">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('customersTable', 'customers.csv')">
                                    📥 Export
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="customersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><strong>#<?php echo $customer['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td><strong><?php echo formatCurrency($customer['total_spent'] ?? 0); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'gray'; ?>">
                                            <?php echo ucfirst($customer['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($customer['created_at']); ?></td>
                                    <td>
                                        <a href="customer-detail.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-primary">View Details</a>
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
