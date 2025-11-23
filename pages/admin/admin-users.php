<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle admin user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'create') {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
            
            // Create admin user
            $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (?, ?, 'admin', 'active')");
            $stmt->execute([$_POST['email'], $passwordHash]);
            
            // Create profile
            $userId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $_POST['name']]);
            
            echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
        } elseif ($_POST['action'] === 'delete') {
            // Don't allow deleting yourself
            if ($_POST['user_id'] == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$_POST['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Admin user deleted']);
        } elseif ($_POST['action'] === 'toggle_status') {
            $newStatus = $_POST['current_status'] === 'active' ? 'banned' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'admin'");
            $stmt->execute([$newStatus, $_POST['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all admin users
$stmt = $pdo->query("
    SELECT u.*, up.name
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.role = 'admin'
    ORDER BY u.created_at DESC
");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - <?php echo SITE_NAME; ?></title>
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
                        <h1>Admin Users</h1>
                        <p class="header-subtitle">Manage administrator accounts</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Create Admin Button -->
                <div style="margin-bottom: 24px;">
                    <button class="btn btn-primary btn-lg" onclick="openModal('createAdminModal')">
                        ➕ Create New Admin
                    </button>
                </div>

                <!-- Admin Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Administrators</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><strong>#<?php echo $admin['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($admin['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($admin['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($admin['created_at']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $admin['status']; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $admin['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                    <?php echo $admin['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this admin user?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                            <?php else: ?>
                                            <span class="badge badge-info">You</span>
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

    <!-- Create Admin Modal -->
    <div class="modal-overlay" id="createAdminModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Create New Admin User</h3>
                <button class="modal-close" onclick="closeModal('createAdminModal')">×</button>
            </div>
            <form method="POST" id="createAdminForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-input" required minlength="6">
                        <small style="color: var(--gray-600);">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createAdminModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }

        document.getElementById('createAdminForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const response = await fetch('admin-users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                closeModal('createAdminModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', result.message);
            }
        });

        document.querySelectorAll('form[method="POST"]:not(#createAdminForm)').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const response = await fetch('admin-users.php', {
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
