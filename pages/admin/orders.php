<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'update_status') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['order_id']]);
            echo json_encode(['success' => true, 'message' => 'Order status updated']);
        } elseif ($_POST['action'] === 'assign_rider') {
            $stmt = $pdo->prepare("UPDATE orders SET rider_id = ? WHERE id = ?");
            $stmt->execute([$_POST['rider_id'], $_POST['order_id']]);
            echo json_encode(['success' => true, 'message' => 'Rider assigned successfully']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
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
    WHERE 1=1
";

if ($statusFilter !== 'all') {
    $sql .= " AND o.status = :status";
}

if ($searchQuery) {
    $sql .= " AND (o.id LIKE :search1 OR u.email LIKE :search2 OR v.email LIKE :search3)";
}

$sql .= " ORDER BY o.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);

// Build parameters array
$params = [];
if ($statusFilter !== 'all') {
    $params[':status'] = $statusFilter;
}
if ($searchQuery) {
    $searchTerm = "%$searchQuery%";
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
}

$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$statusCounts = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}

// Get delivery riders
$riders = [];
try {
    $stmt = $pdo->query("SELECT * FROM delivery_riders WHERE status = 'available' ORDER BY name");
    $riders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet - migrations not run
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - <?php echo SITE_NAME; ?></title>
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
                        <h1>Order Management</h1>
                        <p class="header-subtitle">Monitor and manage all orders</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['accepted'] ?? 0; ?></div>
                            <div class="stat-label">Accepted</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo ($statusCounts['preparing'] ?? 0) + ($statusCounts['in_transit'] ?? 0); ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['completed'] ?? 0; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['cancelled'] ?? 0; ?></div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Orders</h3>
                        <div class="table-filters">
                            <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <input type="text" name="search" class="form-input" placeholder="Search orders..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" style="width: 250px;">
                                <select name="status" class="form-select" onchange="this.form.submit()" style="width: 150px;">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="preparing" <?php echo $statusFilter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="in_transit" <?php echo $statusFilter === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('ordersTable', 'orders.csv')">
                                    📥 Export
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Rider</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? $order['customer_email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($order['vendor_name'] ?? $order['vendor_email'] ?? 'N/A'); ?></td>
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
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($order['rider_id']) && $order['rider_id']): ?>
                                        <span class="badge badge-success">Assigned</span>
                                        <?php else: ?>
                                        <span class="badge badge-gray">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDateTime($order['created_at']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="View Details">👁️</a>
                                            
                                            <?php if (!isset($order['rider_id']) || (!$order['rider_id'] && in_array($order['status'], ['accepted', 'preparing']))): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="assignRider(<?php echo $order['id']; ?>)" 
                                                    title="Assign Rider">🚴</button>
                                            <?php endif; ?>
                                        </div>
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

    <!-- Assign Rider Modal -->
    <div class="modal-overlay" id="assignRiderModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Assign Delivery Rider</h3>
                <button class="modal-close" onclick="closeModal('assignRiderModal')">×</button>
            </div>
            <div class="modal-body">
                <form id="assignRiderForm">
                    <input type="hidden" id="assignOrderId" name="order_id">
                    <input type="hidden" name="action" value="assign_rider">
                    
                    <div class="form-group">
                        <label class="form-label">Select Rider</label>
                        <select name="rider_id" class="form-select" required>
                            <option value="">Choose a rider...</option>
                            <?php foreach ($riders as $rider): ?>
                            <option value="<?php echo $rider['id']; ?>">
                                <?php echo htmlspecialchars($rider['name']); ?> 
                                (<?php echo ucfirst($rider['vehicle_type']); ?>) 
                                - ⭐ <?php echo number_format($rider['rating'], 1); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('assignRiderModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitAssignRider()">Assign Rider</button>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }

        function assignRider(orderId) {
            document.getElementById('assignOrderId').value = orderId;
            openModal('assignRiderModal');
        }

        async function submitAssignRider() {
            const form = document.getElementById('assignRiderForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch('orders.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    closeModal('assignRiderModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred');
            }
        }
    </script>
</body>
</html>
