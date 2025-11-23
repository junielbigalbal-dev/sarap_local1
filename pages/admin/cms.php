<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Handle content actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO cms_content (type, title, content, excerpt, status, author_id, published_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['type'], $_POST['title'], $_POST['content'],
                $_POST['excerpt'], $_POST['status'], $_SESSION['user']['id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Content created successfully']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get content
$content = [];
try {
    $stmt = $pdo->query("
        SELECT c.*, u.email as author_email
        FROM cms_content c
        LEFT JOIN users u ON c.author_id = u.id
        ORDER BY c.created_at DESC
    ");
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - <?php echo SITE_NAME; ?></title>
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
                        <h1>Content Management</h1>
                        <p class="header-subtitle">Manage announcements, blogs, and notifications</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <div style="margin-bottom: 24px;">
                    <button class="btn btn-primary btn-lg" onclick="openModal('createContentModal')">
                        ➕ Create New Content
                    </button>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Content</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Published</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($content as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst($item['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['author_email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $item['status'] === 'published' ? 'success' : 'gray'; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $item['published_at'] ? formatDateTime($item['published_at']) : 'Not published'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary">Edit</button>
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

    <!-- Create Content Modal -->
    <div class="modal-overlay" id="createContentModal">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Create New Content</h3>
                <button class="modal-close" onclick="closeModal('createContentModal')">×</button>
            </div>
            <form method="POST" id="createContentForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="announcement">Announcement</option>
                                <option value="blog">Blog Post</option>
                                <option value="notification">Notification</option>
                                <option value="page">Page</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Excerpt</label>
                        <input type="text" name="excerpt" class="form-input" placeholder="Short summary">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Content *</label>
                        <textarea name="content" class="form-textarea" rows="8" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createContentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Content</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }

        document.getElementById('createContentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const response = await fetch('cms.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                closeModal('createContentModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', result.message);
            }
        });
    </script>
</body>
</html>
