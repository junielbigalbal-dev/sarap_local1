<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE products SET status = 'active' WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            echo json_encode(['success' => true, 'message' => 'Product approved']);
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $pdo->prepare("UPDATE products SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            echo json_encode(['success' => true, 'message' => 'Product rejected']);
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            echo json_encode(['success' => true, 'message' => 'Product deleted']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT 
        p.*,
        u.email as vendor_email,
        COALESCE(up.business_name, up.name, u.email) as vendor_name,
        c.name as category_name
    FROM products p
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";

if ($statusFilter !== 'all') {
    $sql .= " AND p.status = :status";
}

if ($categoryFilter !== 'all') {
    $sql .= " AND p.category_id = :category";
}

if ($searchQuery) {
    $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
}

$sql .= " ORDER BY p.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);

if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter);
}
if ($categoryFilter !== 'all') {
    $stmt->bindValue(':category', $categoryFilter);
}
if ($searchQuery) {
    $stmt->bindValue(':search', "%$searchQuery%");
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM products GROUP BY status");
$statusCounts = [];
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu & Categories - <?php echo SITE_NAME; ?></title>
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
                        <h1>Menu & Categories</h1>
                        <p class="header-subtitle">Manage products and food categories</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['active'] ?? 0; ?></div>
                            <div class="stat-label">Active Products</div>
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
                            <div class="stat-value"><?php echo $statusCounts['inactive'] ?? 0; ?></div>
                            <div class="stat-label">Inactive</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo count($categories); ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Products</h3>
                        <div class="table-filters">
                            <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <input type="text" name="search" class="form-input" placeholder="Search products..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" style="width: 250px;">
                                <select name="status" class="form-select" onchange="this.form.submit()" style="width: 150px;">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <select name="category" class="form-select" onchange="this.form.submit()" style="width: 150px;">
                                    <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('productsTable', 'products.csv')">
                                    📥 Export
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Vendor</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><strong>#<?php echo $product['id']; ?></strong></td>
                                    <td>
                                        <?php if ($product['image']): ?>
                                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($product['image']); ?>" 
                                             alt="Product" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--gray-200); border-radius: 8px; display: flex; align-items: center; justify-content: center;">📦</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['vendor_name']); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo formatCurrency($product['price']); ?></strong></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'active' => 'success',
                                            'pending' => 'warning',
                                            'inactive' => 'gray',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$product['status']] ?? 'gray';
                                        ?>
                                        <span class="badge badge-<?php echo $color; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($product['created_at']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($product['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this product?');">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve">✓</button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this product?');">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">✗</button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this product?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">🗑️</button>
                                            </form>
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
                    const response = await fetch('menu-items.php', {
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
