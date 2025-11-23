<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle promo actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO promotions (code, title, description, discount_type, discount_value, 
                                      min_order_amount, max_discount, usage_limit, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['code'], $_POST['title'], $_POST['description'],
                $_POST['discount_type'], $_POST['discount_value'],
                $_POST['min_order_amount'], $_POST['max_discount'] ?? null,
                $_POST['usage_limit'] ?? null,
                $_POST['start_date'], $_POST['end_date'], 'active'
            ]);
            echo json_encode(['success' => true, 'message' => 'Promotion created successfully']);
        } elseif ($_POST['action'] === 'toggle_status') {
            $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE promotions SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $_POST['promo_id']]);
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get promotions
$promotions = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, COUNT(pu.id) as usage_count
        FROM promotions p
        LEFT JOIN promo_usage pu ON p.id = pu.promo_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions & Coupons - <?php echo SITE_NAME; ?></title>
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
                        <h1>Promotions & Coupons</h1>
                        <p class="header-subtitle">Manage discount codes and campaigns</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Create Promo Button -->
                <div style="margin-bottom: 24px;">
                    <button class="btn btn-primary btn-lg" onclick="openModal('createPromoModal')">
                        ➕ Create New Promotion
                    </button>
                </div>

                <!-- Promotions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Promotions</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Discount</th>
                                    <th>Min Order</th>
                                    <th>Usage</th>
                                    <th>Valid Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($promotions as $promo): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($promo['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($promo['title']); ?></td>
                                    <td>
                                        <?php if ($promo['discount_type'] === 'percentage'): ?>
                                        <?php echo $promo['discount_value']; ?>%
                                        <?php else: ?>
                                        <?php echo formatCurrency($promo['discount_value']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatCurrency($promo['min_order_amount']); ?></td>
                                    <td>
                                        <?php echo $promo['usage_count']; ?>
                                        <?php if ($promo['usage_limit']): ?>
                                        / <?php echo $promo['usage_limit']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d', strtotime($promo['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($promo['end_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $promo['status'] === 'active' ? 'success' : 'gray'; ?>">
                                            <?php echo ucfirst($promo['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="promo_id" value="<?php echo $promo['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $promo['status']; ?>">
                                            <button type="submit" class="btn btn-sm btn-<?php echo $promo['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                <?php echo $promo['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
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

    <!-- Create Promo Modal -->
    <div class="modal-overlay" id="createPromoModal">
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header">
                <h3 class="modal-title">Create New Promotion</h3>
                <button class="modal-close" onclick="closeModal('createPromoModal')">×</button>
            </div>
            <form method="POST" id="createPromoForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Promo Code *</label>
                            <input type="text" name="code" class="form-input" required placeholder="e.g., WELCOME20">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-input" required placeholder="e.g., Welcome Discount">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" rows="2" placeholder="Promotion description"></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Discount Type *</label>
                            <select name="discount_type" class="form-select" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₱)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Discount Value *</label>
                            <input type="number" name="discount_value" class="form-input" required step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Min Order Amount *</label>
                            <input type="number" name="min_order_amount" class="form-input" required step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max Discount</label>
                            <input type="number" name="max_discount" class="form-input" step="0.01" min="0" placeholder="Optional">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Start Date *</label>
                            <input type="datetime-local" name="start_date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date *</label>
                            <input type="datetime-local" name="end_date" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-input" min="1" placeholder="Leave empty for unlimited">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createPromoModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Promotion</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }

        document.getElementById('createPromoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const response = await fetch('promotions.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                closeModal('createPromoModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', result.message);
            }
        });

        document.querySelectorAll('form[method="POST"]:not(#createPromoForm)').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const response = await fetch('promotions.php', {
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
        });
    </script>
</body>
</html>
