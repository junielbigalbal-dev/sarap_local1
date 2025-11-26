<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';

requireRole('vendor');

$userId = getUserId();
$productModel = new Product($pdo);

// Get product if editing
$product = null;
$productId = $_GET['id'] ?? null;
if ($productId) {
    $product = $productModel->findById($productId);
    if (!$product || $product['vendor_id'] != $userId) {
        redirect(SITE_URL . '/pages/vendor/products.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $category = sanitize($_POST['category']);
    $stockQuantity = (int)$_POST['stock_quantity'];
    
    $data = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'category' => $category,
        'stock_quantity' => $stockQuantity
    ];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/products', ALLOWED_IMAGE_TYPES);
        if ($upload['success']) {
            $data['image'] = $upload['filename'];
        }
    }
    
    if ($productId) {
        // Update
        $productModel->update($productId, $data);
        setFlashMessage('Product updated successfully', 'success');
    } else {
        // Create
        $data['vendor_id'] = $userId;
        $productModel->create($data);
        setFlashMessage('Product created successfully', 'success');
    }
    
    redirect(SITE_URL . '/pages/vendor/products.php');
}

$categories = ['Local Cuisine', 'Restaurants', 'Cafés', 'Biliran Delicacies', 'Street Food', 'Desserts', 'Beverages'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? 'Edit' : 'Add'; ?> Product - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/master-dashboard.css?v=4">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="<?php echo SITE_URL; ?>/frontend/public/assets/logo.png" alt="<?php echo SITE_NAME; ?>">
                <h3><?php echo SITE_NAME; ?></h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">🏠 Dashboard</a></li>
                <li><a href="products.php" class="active">🍽️ My Products</a></li>
                <li><a href="orders.php">📦 Orders</a></li>
                <li><a href="analytics.php">📊 Analytics</a></li>
                <li><a href="messages.php">💬 Messages</a></li>
                <li><a href="profile.php">🏪 Business Profile</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1><?php echo $product ? '✏️ Edit' : '➕ Add'; ?> Product</h1>
                <a href="products.php" class="btn-outline">← Back to Products</a>
            </div>

            <div class="profile-card" style="max-width: 800px;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" required class="form-control" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₱) *</label>
                            <input type="number" name="price" value="<?php echo $product['price'] ?? ''; ?>" required min="0" step="0.01" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Stock Quantity *</label>
                            <input type="number" name="stock_quantity" value="<?php echo $product['stock_quantity'] ?? ''; ?>" required min="0" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required class="form-control">
                            <option value="">Select category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($product['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Image</label>
                        <?php if ($product && $product['image']): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="<?php echo getProductImage($product['image']); ?>" alt="Current image" style="max-width: 200px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small style="color: #666;">JPG, PNG, or GIF. Max 5MB.</small>
                    </div>
                    
                    <button type="submit" class="btn-checkout"><?php echo $product ? 'Update' : 'Create'; ?> Product</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
