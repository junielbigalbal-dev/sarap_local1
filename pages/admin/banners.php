<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle banner actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO banners (title, image_url, link_url, position, status) 
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['image_url'],
                $_POST['link_url'],
                $_POST['position']
            ]);
            echo json_encode(['success' => true, 'message' => 'Banner created successfully']);
        } elseif ($_POST['action'] === 'toggle_status') {
            $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE banners SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $_POST['banner_id']]);
            echo json_encode(['success' => true, 'message' => 'Banner status updated']);
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$_POST['banner_id']]);
            echo json_encode(['success' => true, 'message' => 'Banner deleted']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all banners
$banners = [];
try {
    $stmt = $pdo->query("SELECT * FROM banners ORDER BY position ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners - <?php echo SITE_NAME; ?></title>
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
                        <h1>Banners</h1>
                        <p class="header-subtitle">Manage promotional banners</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <?php if (empty($banners)): ?>
                <div class="card" style="text-align: center; padding: 48px;">
                    <p style="color: var(--gray-600); margin-bottom: 16px;">
                        ⚠️ The banners table doesn't exist yet. Please run the database migrations first.
                    </p>
                    <p style="color: var(--gray-600);">
                        See <strong>ADMIN-SETUP.md</strong> for instructions.
                    </p>
                </div>
                <?php else: ?>
                
                <!-- Create Banner Button -->
                <div style="margin-bottom: 24px;">
                    <button class="btn btn-primary btn-lg" onclick="openModal('createBannerModal')">
                        ➕ Create New Banner
                    </button>
                </div>

                <!-- Banners Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px;">
                    <?php foreach ($banners as $banner): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <span class="badge badge-<?php echo $banner['status'] === 'active' ? 'success' : 'gray'; ?>">
                                <?php echo ucfirst($banner['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($banner['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                 alt="Banner" 
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;">
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 8px;">
                                <strong>Position:</strong> <?php echo htmlspecialchars($banner['position']); ?>
                            </div>
                            
                            <?php if ($banner['link_url']): ?>
                            <div style="margin-bottom: 8px;">
                                <strong>Link:</strong> 
                                <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" target="_blank" style="color: var(--admin-primary);">
                                    <?php echo htmlspecialchars($banner['link_url']); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 16px; display: flex; gap: 8px;">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $banner['status']; ?>">
                                    <button type="submit" class="btn btn-sm btn-<?php echo $banner['status'] === 'active' ? 'warning' : 'success'; ?>" style="width: 100%;">
                                        <?php echo $banner['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Delete this banner?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Banner Modal -->
    <div class="modal-overlay" id="createBannerModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Create New Banner</h3>
                <button class="modal-close" onclick="closeModal('createBannerModal')">×</button>
            </div>
            <form method="POST" id="createBannerForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Image URL *</label>
                        <input type="url" name="image_url" class="form-input" required>
                        <small style="color: var(--gray-600);">Full URL to the banner image</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Link URL</label>
                        <input type="url" name="link_url" class="form-input">
                        <small style="color: var(--gray-600);">Where the banner should link to (optional)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Position *</label>
                        <select name="position" class="form-select" required>
                            <option value="home_hero">Home - Hero Section</option>
                            <option value="home_middle">Home - Middle Section</option>
                            <option value="home_bottom">Home - Bottom Section</option>
                            <option value="products_top">Products - Top</option>
                            <option value="checkout">Checkout Page</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createBannerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Banner</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }

        document.getElementById('createBannerForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const response = await fetch('banners.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                closeModal('createBannerModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', result.message);
            }
        });

        document.querySelectorAll('form[method="POST"]:not(#createBannerForm)').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const response = await fetch('banners.php', {
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
