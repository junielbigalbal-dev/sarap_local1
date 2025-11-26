<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle vendor actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'vendor'");
            $stmt->execute([$_POST['vendor_id']]);
            echo json_encode(['success' => true, 'message' => 'Vendor approved successfully']);
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'vendor'");
            $stmt->execute([$_POST['vendor_id']]);
            echo json_encode(['success' => true, 'message' => 'Vendor rejected']);
        } elseif ($_POST['action'] === 'suspend') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND role = 'vendor'");
            $stmt->execute([$_POST['vendor_id']]);
            echo json_encode(['success' => true, 'message' => 'Vendor suspended']);
        } elseif ($_POST['action'] === 'reactivate') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'vendor'");
            $stmt->execute([$_POST['vendor_id']]);
            echo json_encode(['success' => true, 'message' => 'Vendor reactivated']);
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
        u.*,
        COALESCE(up.business_name, up.name, u.email) as vendor_name,
        COUNT(DISTINCT p.id) as product_count,
        COUNT(DISTINCT o.id) as order_count,
        SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_sales,
        AVG(CASE WHEN o.status = 'completed' THEN 5 ELSE NULL END) as rating
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN products p ON u.id = p.vendor_id
    LEFT JOIN orders o ON u.id = o.vendor_id
    WHERE u.role = 'vendor'
";

if ($statusFilter !== 'all') {
    $sql .= " AND u.status = :status";
}

if ($searchQuery) {
    $sql .= " AND (u.email LIKE :search OR u.name LIKE :search)";
}

$sql .= " GROUP BY u.id, up.business_name, up.name ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);

if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter);
}
if ($searchQuery) {
    $stmt->bindValue(':search', "%$searchQuery%");
}

$stmt->execute();
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$statusCounts = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM users WHERE role = 'vendor' GROUP BY status");
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-layout.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-mobile-fix.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="btn btn-icon btn-secondary" style="display: none;">☰</button>
                    <div class="header-title">
                        <h1>Vendor Management</h1>
                        <p class="header-subtitle">Manage vendor accounts and approvals</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-user">
                        <div class="user-avatar">A</div>
                        <div class="user-info">
                            <div class="user-name">Admin</div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['active'] ?? 0; ?></div>
                            <div class="stat-label">Active Vendors</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                            <div class="stat-label">Pending Approval</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['suspended'] ?? 0; ?></div>
                            <div class="stat-label">Suspended</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo count($vendors); ?></div>
                            <div class="stat-label">Total Vendors</div>
                        </div>
                    </div>
                </div>

                <!-- Vendors Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Vendors</h3>
                        <div class="table-filters">
                            <form method="GET" style="display: flex; gap: 12px;">
                                <input type="text" name="search" class="form-input" placeholder="Search vendors..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" style="width: 250px;">
                                <select name="status" class="form-select" onchange="this.form.submit()" style="width: 150px;">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="vendorsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vendor Name</th>
                                    <th>Email</th>
                                    <th>Products</th>
                                    <th>Orders</th>
                                    <th>Total Sales</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendors as $vendor): ?>
                                <tr>
                                    <td><strong>#<?php echo $vendor['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($vendor['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                                    <td><?php echo $vendor['product_count']; ?></td>
                                    <td><?php echo $vendor['order_count']; ?></td>
                                    <td><strong><?php echo formatCurrency($vendor['total_sales'] ?? 0); ?></strong></td>
                                    <td>
                                        <?php if ($vendor['rating']): ?>
                                        ⭐ <?php echo number_format($vendor['rating'], 1); ?>
                                        <?php else: ?>
                                        <span style="color: var(--gray-400);">No ratings</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'active' => 'success',
                                            'pending' => 'warning',
                                            'suspended' => 'danger',
                                            'rejected' => 'gray'
                                        ];
                                        $color = $statusColors[$vendor['status']] ?? 'gray';
                                        ?>
                                        <span class="badge badge-<?php echo $color; ?>">
                                            <?php echo ucfirst($vendor['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($vendor['created_at']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="vendor-detail.php?id=<?php echo $vendor['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="View Details">👁️</a>
                                            
                                            <?php if ($vendor['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this vendor?');">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve">✓</button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this vendor?');">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">✗</button>
                                            </form>
                                            <?php elseif ($vendor['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Suspend this vendor?');">
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Suspend">⏸️</button>
                                            </form>
                                            <?php elseif ($vendor['status'] === 'suspended'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reactivate this vendor?');">
                                                <input type="hidden" name="action" value="reactivate">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Reactivate">▶️</button>
                                            </form>
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

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }

        // Handle form submissions with AJAX
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            if (!form.querySelector('input[name="search"]')) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const response = await fetch('vendors.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('success', result.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('danger', result.message);
                    }
                });
            }
        });
    </script>
</body>
</html>
