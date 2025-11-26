<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Message.php';

requireRole('customer');

$userId = getUserId();
$productModel = new Product($pdo);
$orderModel = new Order($pdo);
$cartModel = new Cart($pdo);
$notificationModel = new Notification($pdo);
$userModel = new User($pdo);
$messageModel = new Message($pdo);

// Get user data
$user = $_SESSION['user_data'];
$cartCount = $cartModel->getCount($userId);
$unreadNotifications = $notificationModel->getUnreadCount($userId);

// Get active order (pending or accepted)
$activeOrders = $orderModel->getByCustomer($userId, 1); // Get latest order
$activeOrder = null;
if (!empty($activeOrders)) {
    $latestOrder = $activeOrders[0];
    if (in_array($latestOrder['status'], ['pending', 'accepted'])) {
        $activeOrder = $latestOrder;
    }
}

// Determine View
$view = $_GET['view'] ?? 'home';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$categories = ['Local Cuisine', 'Restaurants', 'Cafés', 'Biliran Delicacies', 'Street Food', 'Desserts', 'Beverages'];
$products = [];
$vendors = [];
$cartItems = [];
$cartTotal = 0;
$myOrders = [];
$activeOrdersList = [];
$pastOrdersList = [];
$userProfile = [];
$conversations = [];
$activeChatId = null;
$activeMessages = [];
$activeChatUser = null;

if ($view === 'products') {
    // Handle add to cart
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF Token');
        }
        $productId = (int)$_POST['product_id'];
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        $cartModel->add($userId, $productId, $quantity);
        setFlashMessage('Product added to cart!', 'success');
        redirect(SITE_URL . '/pages/customer/dashboard.php?view=products');
    }

    // Get products
    if ($search) {
        $products = $productModel->search($search);
    } elseif ($category) {
        $products = $productModel->getByCategory($category);
    } else {
        $products = $productModel->getActiveProducts(50);
    }

    // Get vendors (mock logic for now based on products)
    $vendorIds = [];
    foreach ($products as $product) {
        if (!in_array($product['vendor_id'], $vendorIds)) {
            $vendorIds[] = $product['vendor_id'];
            $vendorData = $userModel->getUserWithProfile($product['vendor_id']);
            if ($vendorData) {
                $vendors[] = [
                    'id' => $product['vendor_id'],
                    'name' => $product['vendor_name'],
                    'business_name' => $vendorData['business_name'] ?? $product['vendor_name'],
                    'image' => null,
                    'rating' => number_format(4.0 + (rand(0, 10) / 10), 1),
                    'delivery_time' => rand(20, 45) . '-' . rand(45, 60) . ' min',
                    'delivery_fee' => rand(0, 50)
                ];
            }
        }
    }
} elseif ($view === 'checkout') {
    // Order Confirmation Screen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_order') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF Token');
        }
        
        // Get cart items
        $cartItems = $cartModel->getItems($userId);
        if (empty($cartItems)) {
            setFlashMessage('Your cart is empty', 'error');
            redirect(SITE_URL . '/pages/customer/dashboard.php?view=cart');
        }
        
        // Create order
        $vendorId = $cartItems[0]['vendor_id'];
        $total = $cartModel->getTotal($userId);
        $deliveryFee = 50; // Fixed delivery fee
        $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
        $finalTotal = $total + $deliveryFee - $discount;
        
        $orderId = $orderModel->create($userId, $vendorId, $finalTotal);
        
        // Add order items
        foreach ($cartItems as $item) {
            $orderModel->addItem($orderId, $item['product_id'], $item['quantity'], $item['price']);
        }
        
        // Clear cart
        $cartModel->clear($userId);
        
        setFlashMessage('Order placed successfully!', 'success');
        redirect(SITE_URL . '/pages/customer/dashboard.php?view=orders');
    }
    
    // Get cart items for confirmation
    $cartItems = $cartModel->getItems($userId);
    $cartTotal = $cartModel->getTotal($userId);
    $userProfile = $userModel->getUserWithProfile($userId);
    
    if (empty($cartItems)) {
        setFlashMessage('Your cart is empty', 'error');
        redirect(SITE_URL . '/pages/customer/dashboard.php?view=cart');
    }
} elseif ($view === 'cart') {
    // Handle Cart Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF Token');
        }
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update') {
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            if ($quantity > 0) {
                $cartModel->update($userId, $productId, $quantity);
                setFlashMessage('Cart updated', 'success');
            }
        } elseif ($action === 'remove') {
            $productId = (int)$_POST['product_id'];
            $cartModel->remove($userId, $productId);
            setFlashMessage('Item removed', 'success');
        } elseif ($action === 'clear') {
            $cartModel->clear($userId);
            setFlashMessage('Cart cleared', 'success');
        }
        redirect(SITE_URL . '/pages/customer/dashboard.php?view=cart');
    }

    $cartItems = $cartModel->getItems($userId);
    $cartTotal = $cartModel->getTotal($userId);
} elseif ($view === 'orders') {
    // Get all orders
    $allOrders = $orderModel->getByCustomer($userId);
    
    // Separate active and past orders
    foreach ($allOrders as $order) {
        if (in_array($order['status'], ['pending', 'accepted'])) {
            $activeOrdersList[] = $order;
        } else {
            $pastOrdersList[] = $order;
        }
    }
} elseif ($view === 'profile') {
    // Handle Profile Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF Token');
        }
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $name = sanitize($_POST['name']);
            $phone = sanitize($_POST['phone']);
            $address = sanitize($_POST['address']);
            
            $userModel->updateProfile($userId, ['name' => $name, 'phone' => $phone, 'address' => $address]);
            setFlashMessage('Profile updated successfully', 'success');
            redirect(SITE_URL . '/pages/customer/dashboard.php?view=profile');
            
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            $userData = $userModel->findById($userId);
            
            if (!verifyPassword($currentPassword, $userData['password_hash'])) {
                setFlashMessage('Current password is incorrect', 'error');
            } elseif ($newPassword !== $confirmPassword) {
                setFlashMessage('New passwords do not match', 'error');
            } elseif (strlen($newPassword) < 6) {
                setFlashMessage('Password must be at least 6 characters', 'error');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([hashPassword($newPassword), $userId]);
                setFlashMessage('Password changed successfully', 'success');
            }
            redirect(SITE_URL . '/pages/customer/dashboard.php?view=profile');
        }
    }
    
    $userProfile = $userModel->getUserWithProfile($userId);
} elseif ($view === 'messages') {
    // Handle Sending Message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF Token');
        }
        $receiverId = (int)$_POST['receiver_id'];
        $message = trim($_POST['message']);
        
        if (!empty($message)) {
            $messageModel->send($userId, $receiverId, $message);
            redirect(SITE_URL . "/pages/customer/dashboard.php?view=messages&id=$receiverId");
        }
    }

    // Get conversations
    $conversations = $messageModel->getConversations($userId);
    
    // Get active chat
    $activeChatId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if ($activeChatId) {
        $activeMessages = $messageModel->getConversation($userId, $activeChatId);
        $activeChatUser = $userModel->getProfile($activeChatId);
        $messageModel->markAsRead($activeChatId, $userId);
    }
} else {
    // Home view data
    $featuredProducts = $productModel->getActiveProducts(10);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-layout.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/master-dashboard.css?v=6">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/feed-styles.css?v=7">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/customer-mobile-sidebar.css?v=1">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/sidebar-visibility-fix.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
    function updateQuantity(btn, change) {
        const form = btn.closest('form');
        const input = form.querySelector('.qty-input');
        let value = parseInt(input.value) + change;
        if (value < 1) value = 1;
        if (value > 99) value = 99;
        input.value = value;
        form.submit();
    }
    </script>
</head>
<body style="background: linear-gradient(to bottom, #FFF5F8 0%, #FFFFFF 50%);">
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="<?php echo SITE_URL; ?>/images/S.png" alt="<?php echo SITE_NAME; ?>">
                </div>
                <div class="sidebar-brand">
                    <h2><?php echo SITE_NAME; ?></h2>
                    <p>Customer Panel</p>
                </div>
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link <?php echo $view === 'home' ? 'active' : ''; ?>">
                                <span class="nav-icon">🏠</span>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="feed.php" class="nav-link">
                                <span class="nav-icon">📰</span>
                                <span>News Feed</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard.php?view=products" class="nav-link <?php echo $view === 'products' ? 'active' : ''; ?>">
                                <span class="nav-icon">🍽️</span>
                                <span>Browse Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard.php?view=cart" class="nav-link <?php echo $view === 'cart' ? 'active' : ''; ?>">
                                <span class="nav-icon">🛒</span>
                                <span>Cart</span>
                                <?php if ($cartCount > 0): ?>
                                <span class="nav-badge"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard.php?view=orders" class="nav-link <?php echo $view === 'orders' ? 'active' : ''; ?>">
                                <span class="nav-icon">📦</span>
                                <span>My Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard.php?view=messages" class="nav-link <?php echo $view === 'messages' ? 'active' : ''; ?>">
                                <span class="nav-icon">💬</span>
                                <span>Messages</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard.php?view=vendors" class="nav-link <?php echo $view === 'vendors' ? 'active' : ''; ?>">
                                <span class="nav-icon">🗺️</span>
                                <span>Find Vendors</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="dashboard.php?view=profile" class="nav-link <?php echo $view === 'profile' ? 'active' : ''; ?>">
                                <span class="nav-icon">👤</span>
                                <span>Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php" class="nav-link">
                                <span class="nav-icon">🚪</span>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" style="background: transparent; padding: 0;">
            
            <!-- Mobile Navigation Bar -->
            <div class="mobile-navbar" style="display: none;">
                <button id="customerMobileMenuBtn" class="mobile-nav-btn">☰</button>
                <div class="mobile-brand">Sarap Local</div>
                <div class="mobile-profile-icon">👤</div>
            </div>
            
            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin: 20px 20px 0;">
                    <?php echo htmlspecialchars($flash['text']); ?>
                </div>
            <?php endif; ?>

            <?php if ($view === 'home'): ?>
            <!-- Modern Header (Persistent) -->
            <div class="dashboard-header-modern">
                <div class="user-welcome">
                    <h1>Hello, <?php echo htmlspecialchars(explode(' ', $user['name'] ?? '')[0] ?: 'Guest'); ?>! 👋</h1>
                    <p>What are you craving today?</p>
                </div>
                <div class="profile-icon-large">
                    👤
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-content">
                <?php if ($view === 'home'): ?>
                <!-- Quick Actions (Persistent) -->
                <div class="quick-actions-grid">
                    <a href="dashboard.php?view=orders" class="action-btn">
                        <div class="action-icon">📦</div>
                        <span class="action-label">Orders</span>
                    </a>
                    <a href="dashboard.php?view=favorites" class="action-btn">
                        <div class="action-icon">❤️</div>
                        <span class="action-label">Favorites</span>
                    </a>
                    <a href="dashboard.php?view=wallet" class="action-btn">
                        <div class="action-icon">👛</div>
                        <span class="action-label">Wallet</span>
                    </a>
                    <a href="dashboard.php?view=vouchers" class="action-btn">
                        <div class="action-icon">🎟️</div>
                        <span class="action-label">Vouchers</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Active Order Status (Persistent) -->
                <?php if ($activeOrder && $view === 'home'): ?>
                <div class="active-order-card">
                    <div class="order-status-header">
                        <div class="status-badge-large">
                            <span>⏳</span> <?php echo ucfirst($activeOrder['status']); ?>
                        </div>
                        <span class="order-time">Est. 30-45 min</span>
                    </div>
                    <div class="order-preview">
                        <div class="order-icon-large">🛵</div>
                        <div class="order-details">
                            <h3>Order #<?php echo $activeOrder['id']; ?></h3>
                            <p><?php echo htmlspecialchars($activeOrder['vendor_name']); ?> • <?php echo formatCurrency($activeOrder['total']); ?></p>
                        </div>
                    </div>
                    <button class="track-btn" onclick="window.location.href='dashboard.php?view=orders'">Track Order</button>
                </div>
                <?php endif; ?>

                <!-- DYNAMIC CONTENT AREA -->
                <?php if ($view === 'home'): ?>
                    <!-- HOME VIEW -->
                    
                    <!-- Categories Scroll -->
                    <h2 class="section-title">Explore Categories</h2>
                    <div class="category-scroll" style="margin-bottom: 30px; padding-left: 0;">
                        <a href="dashboard.php?view=products" class="category-btn active">🍔 All</a>
                        <a href="dashboard.php?view=products&category=Chicken" class="category-btn">🍗 Chicken</a>
                        <a href="dashboard.php?view=products&category=Noodles" class="category-btn">🍜 Noodles</a>
                        <a href="dashboard.php?view=products&category=Rice%20Meals" class="category-btn">🍚 Rice Meals</a>
                        <a href="dashboard.php?view=products&category=Drinks" class="category-btn">🥤 Drinks</a>
                        <a href="dashboard.php?view=products&category=Desserts" class="category-btn">🍰 Desserts</a>
                    </div>

                    <!-- Featured Dishes -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 class="section-title" style="margin: 0; padding: 0;">Recommended for You</h2>
                        <a href="dashboard.php?view=products" style="color: #FF6B9D; text-decoration: none; font-weight: 600; font-size: 0.9rem;">See all</a>
                    </div>
                    
                    <div class="dishes-scroll" style="padding-left: 0; margin-bottom: 40px;">
                        <?php foreach ($featuredProducts as $index => $product): ?>
                        <div class="dish-card">
                            <div class="dish-image-container">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo getProductImage($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="dish-image">
                                <?php endif; ?>
                                <?php if ($index % 2 === 0): ?>
                                    <div class="discount-badge">Free Delivery</div>
                                <?php endif; ?>
                            </div>
                            <div class="dish-info">
                                <div class="dish-header">
                                    <h3 class="dish-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="dish-price"><?php echo formatCurrency($product['price']); ?></div>
                                </div>
                                <div class="dish-vendor">by <?php echo htmlspecialchars($product['vendor_name']); ?></div>
                                <button class="order-btn" onclick="window.location.href='product-details.php?id=<?php echo $product['id']; ?>'">Add to Cart</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Promo Banner -->
                    <div style="background: linear-gradient(135deg, #6B46C1, #805AD5); border-radius: 20px; padding: 30px; color: white; margin-bottom: 40px; position: relative; overflow: hidden;">
                        <div style="position: relative; z-index: 2;">
                            <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Free Delivery Weekend! 🛵</h2>
                            <p style="margin-bottom: 20px; opacity: 0.9;">Order now and get free delivery on all orders over ₱500.</p>
                            <button style="background: white; color: #6B46C1; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 700; cursor: pointer;">Order Now</button>
                        </div>
                        <div style="position: absolute; right: -20px; bottom: -20px; font-size: 8rem; opacity: 0.2;">🎁</div>
                    </div>

                <?php elseif ($view === 'products'): ?>
                    <!-- PRODUCTS VIEW -->
                    
                    <!-- Search and Filter -->
                    <div class="food-app-header" style="margin: 0 -32px 30px; border-radius: 0 0 24px 24px; padding: 20px 32px;">
                        <form method="GET" action="dashboard.php" class="search-container">
                            <input type="hidden" name="view" value="products">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" class="search-box" placeholder="Search for restaurants or dishes..." value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>

                    <!-- Categories -->
                    <div class="category-scroll" style="padding-left: 0; margin-bottom: 30px;">
                        <a href="dashboard.php?view=products" class="category-btn <?php echo empty($category) ? 'active' : ''; ?>">All</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="dashboard.php?view=products&category=<?php echo urlencode($cat); ?>" class="category-btn <?php echo $category === $cat ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🍽️</div>
                            <h2>No results found</h2>
                            <p>Try a different search or category</p>
                        </div>
                    <?php else: ?>
                        
                        <!-- Restaurants Section -->
                        <?php if (!empty($vendors)): ?>
                        <section style="margin-bottom: 40px;">
                            <h2 class="section-title">🏪 Restaurants</h2>
                            <div class="vendor-grid" style="padding: 0; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                                <?php foreach ($vendors as $vendor): ?>
                                <div class="vendor-card" onclick="window.location.href='products.php?search=<?php echo urlencode($vendor['business_name']); ?>'">
                                    <div class="vendor-image" style="display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                                        🏪
                                    </div>
                                    <div class="vendor-info">
                                        <div class="vendor-header">
                                            <h3 class="vendor-name"><?php echo htmlspecialchars($vendor['business_name']); ?></h3>
                                            <div class="rating-badge">⭐ <?php echo $vendor['rating']; ?></div>
                                        </div>
                                        <div class="vendor-meta">
                                            <div class="meta-item">
                                                <span>🕐</span>
                                                <span><?php echo $vendor['delivery_time']; ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span>🚚</span>
                                                <span><?php echo $vendor['delivery_fee'] == 0 ? 'Free' : '₱' . $vendor['delivery_fee']; ?></span>
                                            </div>
                                        </div>
                                        <div class="vendor-tags">
                                            <span class="tag">Filipino</span>
                                            <span class="tag">Fast Delivery</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <!-- Dishes Section -->
                        <section>
                            <h2 class="section-title">🍽️ Popular Dishes</h2>
                            <div class="vendor-grid" style="padding: 0; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                                <?php foreach ($products as $product): ?>
                                <div class="dish-card" style="width: 100%;">
                                    <div class="dish-image-container">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="dish-image">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem;">🍽️</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dish-info">
                                        <div class="dish-header">
                                            <h3 class="dish-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                            <div class="dish-price"><?php echo formatCurrency($product['price']); ?></div>
                                        </div>
                                        <div class="dish-vendor">by <?php echo htmlspecialchars($product['vendor_name']); ?></div>
                                        <p class="dish-description"><?php echo htmlspecialchars(truncate($product['description'], 80)); ?></p>
                                        <button type="button" class="order-btn" onclick="openOrderModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', <?php echo $product['price']; ?>, '<?php echo $product['image'] ? getProductImage($product['image']) : ''; ?>')">Add to Cart</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                    <?php endif; ?>

                    <!-- Order Confirmation Modal -->
                    <div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; animation: fadeIn 0.3s;">
                        <div style="background: white; border-radius: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s;">
                            <div style="padding: 30px;">
                                <!-- Close Button -->
                                <button onclick="closeOrderModal()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #718096; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='#F0F0F0'" onmouseout="this.style.background='none'">×</button>
                                
                                <!-- Product Image -->
                                <div id="modalImage" style="width: 100%; height: 250px; border-radius: 16px; overflow: hidden; margin-bottom: 20px; background: linear-gradient(135deg, #C67D3B 0%, #E8C89F 100%); display: flex; align-items: center; justify-content: center; font-size: 5rem;"></div>
                                
                                <!-- Product Details -->
                                <h2 id="modalProductName" style="font-size: 1.8rem; font-weight: 700; margin-bottom: 10px; color: #2D3748;"></h2>
                                <p id="modalProductPrice" style="font-size: 1.3rem; font-weight: 700; color: #C67D3B; margin-bottom: 25px;"></p>
                                
                                <!-- Quantity Controls -->
                                <div style="background: #F7FAFC; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 12px; color: #2D3748;">Quantity</label>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <button onclick="decreaseQuantity()" style="width: 50px; height: 50px; border-radius: 50%; background: white; border: 2px solid #E2E8F0; font-size: 1.5rem; font-weight: 700; cursor: pointer; transition: all 0.2s; color: #C67D3B;" onmouseover="this.style.background='#C67D3B'; this.style.color='white'; this.style.borderColor='#C67D3B'" onmouseout="this.style.background='white'; this.style.color='#C67D3B'; this.style.borderColor='#E2E8F0'">−</button>
                                        <input type="number" id="modalQuantity" value="1" min="1" readonly style="width: 80px; text-align: center; font-size: 1.5rem; font-weight: 700; border: 2px solid #E2E8F0; border-radius: 12px; padding: 10px; background: white;">
                                        <button onclick="increaseQuantity()" style="width: 50px; height: 50px; border-radius: 50%; background: white; border: 2px solid #E2E8F0; font-size: 1.5rem; font-weight: 700; cursor: pointer; transition: all 0.2s; color: #C67D3B;" onmouseover="this.style.background='#C67D3B'; this.style.color='white'; this.style.borderColor='#C67D3B'" onmouseout="this.style.background='white'; this.style.color='#C67D3B'; this.style.borderColor='#E2E8F0'">+</button>
                                    </div>
                                </div>
                                
                                <!-- Total Price -->
                                <div style="background: #FFF5F0; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 1.1rem; font-weight: 600; color: #2D3748;">Total Price</span>
                                        <span id="modalTotalPrice" style="font-size: 1.5rem; font-weight: 700; color: #C67D3B;"></span>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <form method="POST" id="modalCartForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" id="modalProductId">
                                    <input type="hidden" name="quantity" id="modalQuantityInput">
                                    <button type="submit" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1.1rem; cursor: pointer; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(198, 125, 59, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(198, 125, 59, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(198, 125, 59, 0.3)'">
                                        🛒 Proceed to Checkout
                                    </button>
                                </form>
                                <button onclick="closeOrderModal()" style="width: 100%; padding: 14px; background: white; color: #718096; border: 2px solid #E2E8F0; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#C67D3B'; this.style.color='#C67D3B'" onmouseout="this.style.borderColor='#E2E8F0'; this.style.color='#718096'">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <style>
                        @keyframes fadeIn {
                            from { opacity: 0; }
                            to { opacity: 1; }
                        }
                        @keyframes slideUp {
                            from { transform: translateY(30px); opacity: 0; }
                            to { transform: translateY(0); opacity: 1; }
                        }
                        @media (max-width: 768px) {
                            #orderModal > div {
                                max-width: 95% !important;
                                margin: 20px;
                            }
                        }
                    </style>

                    <script>
                        let currentProductId = null;
                        let currentProductPrice = 0;

                        function openOrderModal(productId, productName, productPrice, productImage) {
                            currentProductId = productId;
                            currentProductPrice = productPrice;
                            
                            // Set product details
                            document.getElementById('modalProductName').textContent = productName;
                            document.getElementById('modalProductPrice').textContent = '₱' + productPrice.toFixed(2);
                            document.getElementById('modalProductId').value = productId;
                            
                            // Set product image
                            const imageContainer = document.getElementById('modalImage');
                            if (productImage) {
                                imageContainer.innerHTML = '<img src="' + productImage + '" style="width: 100%; height: 100%; object-fit: cover;" alt="' + productName + '">';
                            } else {
                                imageContainer.innerHTML = '🍽️';
                            }
                            
                            // Reset quantity
                            document.getElementById('modalQuantity').value = 1;
                            document.getElementById('modalQuantityInput').value = 1;
                            updateTotalPrice();
                            
                            // Show modal
                            const modal = document.getElementById('orderModal');
                            modal.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }

                        function closeOrderModal() {
                            document.getElementById('orderModal').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }

                        function increaseQuantity() {
                            const quantityInput = document.getElementById('modalQuantity');
                            let quantity = parseInt(quantityInput.value);
                            quantity++;
                            quantityInput.value = quantity;
                            document.getElementById('modalQuantityInput').value = quantity;
                            updateTotalPrice();
                        }

                        function decreaseQuantity() {
                            const quantityInput = document.getElementById('modalQuantity');
                            let quantity = parseInt(quantityInput.value);
                            if (quantity > 1) {
                                quantity--;
                                quantityInput.value = quantity;
                                document.getElementById('modalQuantityInput').value = quantity;
                                updateTotalPrice();
                            }
                        }

                        function updateTotalPrice() {
                            const quantity = parseInt(document.getElementById('modalQuantity').value);
                            const total = currentProductPrice * quantity;
                            document.getElementById('modalTotalPrice').textContent = '₱' + total.toFixed(2);
                        }

                        // Close modal when clicking outside
                        document.addEventListener('click', function(event) {
                            const modal = document.getElementById('orderModal');
                            if (event.target === modal) {
                                closeOrderModal();
                            }
                        });

                        // Close modal on ESC key
                        document.addEventListener('keydown', function(event) {
                            if (event.key === 'Escape') {
                                closeOrderModal();
                            }
                        });
                    </script>

                <?php elseif ($view === 'cart'): ?>
                    <!-- CART VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">🛒 Your Cart</h2>
                        <p style="color: var(--gray-600);">Review your items and checkout</p>
                    </div>

                    <?php if (empty($cartItems)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🛒</div>
                            <h2>Your cart is empty</h2>
                            <p>Hungry? Add some delicious items!</p>
                            <a href="dashboard.php?view=products" class="track-btn" style="display: inline-block; width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none; text-align: center;">Browse Food</a>
                        </div>
                    <?php else: ?>
                        <div class="cart-grid">
                            <!-- Cart Items List -->
                            <div class="cart-items-container">
                                <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item-card">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo getProductImage($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                    <?php else: ?>
                                        <div class="cart-item-image" style="display: flex; align-items: center; justify-content: center; font-size: 2rem;">🍽️</div>
                                    <?php endif; ?>
                                    
                                    <div class="cart-item-details">
                                        <h3 class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="cart-item-vendor">by <?php echo htmlspecialchars($item['vendor_name']); ?></p>
                                        <div class="cart-item-price"><?php echo formatCurrency($item['price']); ?></div>
                                    </div>

                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <div class="quantity-control">
                                            <button type="button" class="qty-btn" onclick="updateQuantity(this, -1)">-</button>
                                            <input type="text" name="quantity" value="<?php echo $item['quantity']; ?>" class="qty-input" readonly>
                                            <button type="button" class="qty-btn" onclick="updateQuantity(this, 1)">+</button>
                                        </div>
                                    </form>

                                    <form method="POST" style="position: absolute; top: 10px; right: 10px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="remove-btn" onclick="return confirm('Remove item?')">🗑️</button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                                
                                <form method="POST" style="margin-top: 20px; text-align: right;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="remove-btn" style="position: static; color: #FF4D4D; font-weight: 600;" onclick="return confirm('Clear entire cart?')">Clear All Items</button>
                                </form>
                            </div>

                            <!-- Bill Summary -->
                            <div class="bill-card">
                                <h3 class="bill-title">Order Summary</h3>
                                <div class="bill-row">
                                    <span>Subtotal</span>
                                    <span><?php echo formatCurrency($cartTotal); ?></span>
                                </div>
                                <div class="bill-row">
                                    <span>Delivery Fee</span>
                                    <span style="color: #48BB78; font-weight: 600;">Free</span>
                                </div>
                                <div class="bill-row">
                                    <span>Platform Fee</span>
                                    <span>₱0.00</span>
                                </div>
                                
                                <div class="voucher-input-group">
                                    <input type="text" placeholder="Enter voucher code" class="voucher-input">
                                    <button class="apply-btn">Apply</button>
                                </div>

                                <div class="bill-row total">
                                    <span>Total</span>
                                    <span style="color: #FF6B9D;"><?php echo formatCurrency($cartTotal); ?></span>
                                </div>

                                <button class="checkout-btn" onclick="window.location.href='?view=checkout'">Proceed to Checkout</button>
                            </div>
                        </div>
                    <?php endif; ?>
                
                <?php elseif ($view === 'checkout'): ?>
                    <!-- CHECKOUT CONFIRMATION VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 30px;">
                        <h2 style="font-size: 1.8rem; font-weight: 700;">✅ Confirm Your Order</h2>
                        <p style="color: var(--gray-600);">Review your order details before confirming</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
                        <!-- Left Column - Order Details -->
                        <div>
                            <!-- Order Items -->
                            <div style="background: white; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; color: #2D3748;">📦 Order Items</h3>
                                <?php foreach ($cartItems as $item): ?>
                                <div style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #F0F0F0;">
                                    <div style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #C67D3B 0%, #E8C89F 100%); display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                        <?php if ($item['image']): ?>
                                            <img src="<?php echo getProductImage($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            🍽️
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <h4 style="font-weight: 600; margin-bottom: 5px;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p style="color: #718096; font-size: 0.9rem; margin-bottom: 8px;">Quantity: <?php echo $item['quantity']; ?></p>
                                        <p style="font-weight: 700; color: #C67D3B;"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Delivery Address -->
                            <div style="background: white; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h3 style="font-size: 1.2rem; font-weight: 700; color: #2D3748;">📍 Delivery Address</h3>
                                    <a href="?view=profile" style="color: #C67D3B; font-weight: 600; text-decoration: none; font-size: 0.9rem;">Edit</a>
                                </div>
                                <p style="font-weight: 600; margin-bottom: 5px;"><?php echo htmlspecialchars($userProfile['name'] ?? 'N/A'); ?></p>
                                <p style="color: #718096; margin-bottom: 5px;"><?php echo htmlspecialchars($userProfile['phone'] ?? 'N/A'); ?></p>
                                <p style="color: #718096;"><?php echo htmlspecialchars($userProfile['address'] ?? 'No address provided'); ?></p>
                            </div>

                            <!-- Payment Method -->
                            <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h3 style="font-size: 1.2rem; font-weight: 700; color: #2D3748;">💳 Payment Method</h3>
                                    <button style="color: #C67D3B; font-weight: 600; background: none; border: none; cursor: pointer; font-size: 0.9rem;">Change</button>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; padding: 15px; background: #FFF5F0; border-radius: 12px;">
                                    <span style="font-size: 1.5rem;">💵</span>
                                    <div>
                                        <p style="font-weight: 600;">Cash on Delivery</p>
                                        <p style="color: #718096; font-size: 0.85rem;">Pay when you receive your order</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Order Summary -->
                        <div>
                            <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); position: sticky; top: 20px;">
                                <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; color: #2D3748;">💰 Order Summary</h3>
                                
                                <!-- Delivery Time -->
                                <div style="background: #FFF5F0; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 1.3rem;">⚡</span>
                                        <div>
                                            <p style="font-weight: 600; color: #C67D3B;">Estimated Delivery</p>
                                            <p style="font-size: 0.9rem; color: #718096;">30-45 minutes</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cost Breakdown -->
                                <div style="margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F0F0F0;">
                                        <span style="color: #718096;">Items Subtotal</span>
                                        <span style="font-weight: 600;"><?php echo formatCurrency($cartTotal); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F0F0F0;">
                                        <span style="color: #718096;">Delivery Fee</span>
                                        <span style="font-weight: 600; color: #48BB78;">₱50.00</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F0F0F0;">
                                        <span style="color: #718096;">Platform Fee</span>
                                        <span style="font-weight: 600;">₱0.00</span>
                                    </div>
                                </div>

                                <!-- Voucher Input -->
                                <div style="margin-bottom: 20px;">
                                    <input type="text" id="voucherCode" placeholder="Enter promo code" style="width: 100%; padding: 12px; border: 2px solid #E2E8F0; border-radius: 8px; font-size: 0.95rem; margin-bottom: 8px;">
                                    <button onclick="alert('Voucher feature coming soon!')" style="width: 100%; padding: 10px; background: #FFF5F0; color: #C67D3B; border: 2px solid #C67D3B; border-radius: 8px; font-weight: 600; cursor: pointer;">Apply Voucher</button>
                                </div>

                                <!-- Total -->
                                <div style="padding: 20px 0; border-top: 2px solid #F0F0F0; border-bottom: 2px solid #F0F0F0; margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 1.1rem; font-weight: 700;">Total Amount</span>
                                        <span style="font-size: 1.3rem; font-weight: 700; color: #C67D3B;"><?php echo formatCurrency($cartTotal + 50); ?></span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <form method="POST" style="margin-bottom: 12px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="confirm_order">
                                    <input type="hidden" name="discount" value="0">
                                    <button type="submit" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(198, 125, 59, 0.3); transition: all 0.3s;">
                                        ✅ Confirm & Place Order
                                    </button>
                                </form>
                                <button onclick="window.location.href='?view=cart'" style="width: 100%; padding: 14px; background: white; color: #718096; border: 2px solid #E2E8F0; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                                    ← Back to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($view === 'orders'): ?>
                    <!-- ORDERS VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">📦 My Orders</h2>
                        <p style="color: var(--gray-600);">Track and view your order history</p>
                    </div>

                    <div class="order-tabs">
                        <a href="#" class="order-tab active" id="activeTab" onclick="showActiveOrders(); return false;">Active Orders</a>
                        <a href="#" class="order-tab" id="pastTab" onclick="showPastOrders(); return false;">Past Orders</a>
                    </div>

                    <?php if (empty($activeOrdersList) && empty($pastOrdersList)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <h2>No orders yet</h2>
                            <p>Start ordering from our amazing vendors!</p>
                            <a href="dashboard.php?view=products" class="track-btn" style="display: inline-block; width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none; text-align: center;">Browse Food</a>
                        </div>
                    <?php else: ?>
                        
                        <!-- Active Orders -->
                        <div id="activeOrdersSection">
                        <?php if (!empty($activeOrdersList)): ?>
                            <h3 style="margin-bottom: 20px; color: var(--gray-800);">Active Orders</h3>
                            <?php foreach ($activeOrdersList as $order): ?>
                            <div class="order-card-modern">
                                <div class="order-card-header">
                                    <div class="order-vendor-info">
                                        <h3><?php echo htmlspecialchars($order['vendor_name']); ?></h3>
                                        <p class="order-date"><?php echo formatDateTime($order['created_at']); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-items-list">
                                    <p style="color: var(--gray-600); font-size: 0.9rem;">Order #<?php echo $order['id']; ?></p>
                                </div>
                                <div class="order-total-row">
                                    <span>Total</span>
                                    <span><?php echo formatCurrency($order['total']); ?></span>
                                </div>
                                <div class="order-actions">
                                    <button class="btn-primary-sm" onclick="showOrderTracking(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['vendor_name'], ENT_QUOTES); ?>', '<?php echo $order['status']; ?>')">Track Order</button>
                                    <button class="btn-outline" onclick="window.location.href='?view=messages&id=<?php echo $order['vendor_id']; ?>'">Contact Support</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>

                        <!-- Past Orders -->
                        <div id="pastOrdersSection" style="display: none;">
                        <?php if (!empty($pastOrdersList)): ?>
                            <h3 style="margin: 40px 0 20px; color: var(--gray-800);">Past Orders</h3>
                            <?php foreach ($pastOrdersList as $order): ?>
                            <div class="order-card-modern" style="opacity: 0.9;">
                                <div class="order-card-header">
                                    <div class="order-vendor-info">
                                        <h3><?php echo htmlspecialchars($order['vendor_name']); ?></h3>
                                        <p class="order-date"><?php echo formatDateTime($order['created_at']); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-total-row">
                                    <span>Total</span>
                                    <span><?php echo formatCurrency($order['total']); ?></span>
                                </div>
                                <div class="order-actions">
                                    <button class="btn-primary-sm" onclick="reorderItems(<?php echo $order['id']; ?>)">Reorder</button>
                                    <button class="btn-outline" 
                                        data-order-id="<?php echo $order['id']; ?>"
                                        data-vendor="<?php echo htmlspecialchars($order['vendor_name']); ?>"
                                        data-total="<?php echo formatCurrency($order['total']); ?>"
                                        data-date="<?php echo formatDateTime($order['created_at']); ?>"
                                        data-status="<?php echo $order['status']; ?>"
                                        onclick="showOrderDetails(this)">View Details</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>

                    <?php endif; ?>

                    <script>
                        function showActiveOrders() {
                            document.getElementById('activeOrdersSection').style.display = 'block';
                            document.getElementById('pastOrdersSection').style.display = 'none';
                            document.getElementById('activeTab').classList.add('active');
                            document.getElementById('pastTab').classList.remove('active');
                        }

                        function showPastOrders() {
                            document.getElementById('activeOrdersSection').style.display = 'none';
                            document.getElementById('pastOrdersSection').style.display = 'block';
                            document.getElementById('activeTab').classList.remove('active');
                            document.getElementById('pastTab').classList.add('active');
                        }
                    </script>

                    <!-- Order Details Modal -->
                    <div id="orderDetailsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
                        <div style="background: white; border-radius: 24px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;">
                            <div style="padding: 30px;">
                                <button onclick="closeOrderDetailsModal()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #718096; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">×</button>
                                
                                <h2 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 10px; color: #2D3748;">Order Details</h2>
                                <p id="modalOrderVendor" style="color: #718096; margin-bottom: 25px;"></p>
                                
                                <div style="background: #F7FAFC; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span style="color: #718096;">Order ID</span>
                                        <span id="modalOrderId" style="font-weight: 600;"></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span style="color: #718096;">Date</span>
                                        <span id="modalOrderDate" style="font-weight: 600;"></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span style="color: #718096;">Status</span>
                                        <span id="modalOrderStatus" style="font-weight: 600;"></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 2px solid #E2E8F0;">
                                        <span style="font-size: 1.1rem; font-weight: 700;">Total</span>
                                        <span id="modalOrderTotal" style="font-size: 1.1rem; font-weight: 700; color: #C67D3B;"></span>
                                    </div>
                                </div>
                                
                                <button onclick="closeOrderDetailsModal()" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>

                    <!-- Order Tracking Modal -->
                    <div id="orderTrackingModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
                        <div style="background: white; border-radius: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;">
                            <div style="padding: 30px;">
                                <button onclick="closeOrderTrackingModal()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #718096; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">×</button>
                                
                                <h2 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 10px; color: #2D3748;">Track Order</h2>
                                <p id="trackingVendor" style="color: #718096; margin-bottom: 25px;"></p>
                                
                                <div style="background: #FFF5F0; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                                    <div style="font-size: 3rem; margin-bottom: 10px;">🚨</div>
                                    <p style="font-weight: 600; color: #C67D3B; font-size: 1.1rem; margin-bottom: 5px;">Order Status</p>
                                    <p id="trackingStatus" style="font-size: 1.3rem; font-weight: 700; color: #2D3748;"></p>
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #F7FAFC; border-radius: 12px; margin-bottom: 10px;">
                                        <div style="width: 40px; height: 40px; background: #C67D3B; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">✓</div>
                                        <div>
                                            <p style="font-weight: 600; color: #2D3748;">Order Placed</p>
                                            <p style="font-size: 0.85rem; color: #718096;">Your order has been received</p>
                                        </div>
                                    </div>
                                    <div id="trackingStep2" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #F7FAFC; border-radius: 12px; margin-bottom: 10px; opacity: 0.5;">
                                        <div style="width: 40px; height: 40px; background: #E2E8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #718096; font-weight: 700;">2</div>
                                        <div>
                                            <p style="font-weight: 600; color: #2D3748;">Preparing</p>
                                            <p style="font-size: 0.85rem; color: #718096;">Restaurant is preparing your food</p>
                                        </div>
                                    </div>
                                    <div id="trackingStep3" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #F7FAFC; border-radius: 12px; opacity: 0.5;">
                                        <div style="width: 40px; height: 40px; background: #E2E8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #718096; font-weight: 700;">3</div>
                                        <div>
                                            <p style="font-weight: 600; color: #2D3748;">On the Way</p>
                                            <p style="font-size: 0.85rem; color: #718096;">Rider is delivering your order</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <button onclick="closeOrderTrackingModal()" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>

                    <script>
                        function showOrderDetails(button) {
                            const orderId = button.getAttribute('data-order-id');
                            const vendor = button.getAttribute('data-vendor');
                            const total = button.getAttribute('data-total');
                            const date = button.getAttribute('data-date');
                            const status = button.getAttribute('data-status');
                            
                            document.getElementById('modalOrderId').textContent = '#' + orderId;
                            document.getElementById('modalOrderVendor').textContent = vendor;
                            document.getElementById('modalOrderDate').textContent = date;
                            document.getElementById('modalOrderStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
                            document.getElementById('modalOrderTotal').textContent = total;
                            document.getElementById('orderDetailsModal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }

                        function closeOrderDetailsModal() {
                            document.getElementById('orderDetailsModal').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }

                        function showOrderTracking(orderId, vendor, status) {
                            document.getElementById('trackingVendor').textContent = 'Order #' + orderId + ' from ' + vendor;
                            document.getElementById('trackingStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
                            
                            // Update tracking steps based on status
                            if (status === 'accepted' || status === 'completed') {
                                document.getElementById('trackingStep2').style.opacity = '1';
                                document.getElementById('trackingStep2').querySelector('div').style.background = '#C67D3B';
                                document.getElementById('trackingStep2').querySelector('div').style.color = 'white';
                                document.getElementById('trackingStep2').querySelector('div').textContent = '✓';
                            }
                            if (status === 'completed') {
                                document.getElementById('trackingStep3').style.opacity = '1';
                                document.getElementById('trackingStep3').querySelector('div').style.background = '#C67D3B';
                                document.getElementById('trackingStep3').querySelector('div').style.color = 'white';
                                document.getElementById('trackingStep3').querySelector('div').textContent = '✓';
                            }
                            
                            document.getElementById('orderTrackingModal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }

                        function closeOrderTrackingModal() {
                            document.getElementById('orderTrackingModal').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }

                        function reorderItems(orderId) {
                            if (confirm('Add items from this order to your cart?')) {
                                // In a real implementation, this would add order items to cart
                                alert('Reorder functionality will be implemented soon!');
                                // window.location.href = '?view=cart&reorder=' + orderId;
                            }
                        }

                        // Close modals when clicking outside
                        document.addEventListener('click', function(event) {
                            if (event.target.id === 'orderDetailsModal') closeOrderDetailsModal();
                            if (event.target.id === 'orderTrackingModal') closeOrderTrackingModal();
                        });
                    </script>

                <?php elseif ($view === 'favorites'): ?>
                    <!-- FAVORITES VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">❤️ My Favorites</h2>
                        <p style="color: var(--gray-600);">Your favorite dishes and restaurants</p>
                    </div>

                    <div class="empty-state">
                        <div class="empty-state-icon">❤️</div>
                        <h2>No favorites yet</h2>
                        <p>Start adding your favorite dishes!</p>
                        <a href="dashboard.php?view=products" class="track-btn" style="display: inline-block; width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none; text-align: center;">Browse Food</a>
                    </div>

                <?php elseif ($view === 'wallet'): ?>
                    <!-- WALLET VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">👛 My Wallet</h2>
                        <p style="color: var(--gray-600);">Manage your balance and transactions</p>
                    </div>

                    <!-- Wallet Balance Card -->
                    <div style="background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); border-radius: 20px; padding: 30px; margin-bottom: 30px; color: white; box-shadow: 0 8px 24px rgba(198, 125, 59, 0.3);">
                        <p style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 10px;">Available Balance</p>
                        <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 20px;">₱0.00</h1>
                        <button style="background: white; color: #C67D3B; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">Top Up Wallet</button>
                    </div>

                    <!-- Transaction History -->
                    <h3 style="margin-bottom: 20px; color: var(--gray-800);">Transaction History</h3>
                    <div class="empty-state">
                        <div class="empty-state-icon">💳</div>
                        <h2>No transactions yet</h2>
                        <p>Your transaction history will appear here</p>
                    </div>

                <?php elseif ($view === 'vouchers'): ?>
                    <!-- VOUCHERS VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">🎟️ My Vouchers</h2>
                        <p style="color: var(--gray-600);">Available promo codes and discounts</p>
                    </div>

                    <div class="empty-state">
                        <div class="empty-state-icon">🎟️</div>
                        <h2>No vouchers available</h2>
                        <p>Check back later for amazing deals!</p>
                        <a href="dashboard.php?view=products" class="track-btn" style="display: inline-block; width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none; text-align: center;">Browse Food</a>
                    </div>

                <?php elseif ($view === 'profile'): ?>
                    <!-- PROFILE VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">👤 Profile Settings</h2>
                        <p style="color: var(--gray-600);">Manage your account information</p>
                    </div>

                    <div class="profile-grid">
                        <!-- Personal Info Card -->
                        <div class="profile-card">
                            <h2>Personal Information</h2>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group-modern">
                                    <label>Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($userProfile['name'] ?? ''); ?>" required class="form-control-modern">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Email</label>
                                    <input type="email" value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>" readonly class="form-control-modern">
                                    <small style="color: #A0AEC0; margin-top: 4px; display: block;">Email cannot be changed</small>
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>" class="form-control-modern" placeholder="e.g. 09123456789">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Delivery Address</label>
                                    <textarea name="address" class="form-control-modern" rows="3" placeholder="Enter your delivery address"><?php echo htmlspecialchars($userProfile['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn-save">Save Changes</button>
                            </form>
                        </div>

                        <!-- Security Card -->
                        <div class="profile-card">
                            <h2>Security</h2>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group-modern">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required class="form-control-modern">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required class="form-control-modern" minlength="6">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required class="form-control-modern" minlength="6">
                                </div>
                                
                                <button type="submit" class="btn-save" style="background: white; border: 2px solid #FF6B9D; color: #FF6B9D;">Change Password</button>
                            </form>

                            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #F0F0F0;">
                                <h3 style="font-size: 1rem; color: var(--gray-900); margin-bottom: 10px;">Account Actions</h3>
                                <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php" style="display: block; text-align: center; padding: 12px; background: #FFF5F5; color: #F56565; border-radius: 12px; text-decoration: none; font-weight: 600;">Sign Out</a>
                            </div>
                        </div>
                    </div>

                <?php elseif ($view === 'vendors'): ?>
                    <!-- VENDORS VIEW -->
                    
                    <!-- Search and Filter -->
                    <div class="food-app-header" style="margin: 0 -32px 30px; border-radius: 0 0 24px 24px; padding: 20px 32px;">
                        <form method="GET" action="dashboard.php" class="search-container">
                            <input type="hidden" name="view" value="vendors">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" class="search-box" placeholder="Search for restaurants..." value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>

                    <!-- Categories -->
                    <div class="category-scroll" style="padding-left: 0; margin-bottom: 30px;">
                        <a href="dashboard.php?view=vendors" class="category-btn <?php echo empty($category) ? 'active' : ''; ?>">All</a>
                        <a href="dashboard.php?view=vendors&category=Popular" class="category-btn <?php echo $category === 'Popular' ? 'active' : ''; ?>">🔥 Popular</a>
                        <a href="dashboard.php?view=vendors&category=New" class="category-btn <?php echo $category === 'New' ? 'active' : ''; ?>">🆕 New</a>
                        <a href="dashboard.php?view=vendors&category=Near Me" class="category-btn <?php echo $category === 'Near Me' ? 'active' : ''; ?>">📍 Near Me</a>
                        <a href="dashboard.php?view=vendors&category=Fast Delivery" class="category-btn <?php echo $category === 'Fast Delivery' ? 'active' : ''; ?>">⚡ Fast Delivery</a>
                    </div>

                    <?php
                    // Fetch Vendors Logic (Simulated for now as we don't have a dedicated getVendors method yet)
                    // In a real app, we would have $userModel->getVendors($search, $category);
                    // For now, we'll fetch all users with role 'vendor'
                    $stmt = $pdo->prepare("SELECT u.id, u.email, up.business_name, up.name, up.address FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.role = 'vendor'");
                    $stmt->execute();
                    $allVendors = $stmt->fetchAll();
                    
                    // Filter in PHP for now if search is active
                    if ($search) {
                        $allVendors = array_filter($allVendors, function($v) use ($search) {
                            return stripos($v['business_name'] ?? '', $search) !== false || stripos($v['name'] ?? '', $search) !== false;
                        });
                    }
                    ?>

                    <?php if (empty($allVendors)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🏪</div>
                            <h2>No vendors found</h2>
                            <p>Try a different search term</p>
                        </div>
                    <?php else: ?>
                        <div class="vendor-grid" style="padding: 0; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                            <?php foreach ($allVendors as $vendor): ?>
                            <div class="vendor-card" onclick="window.location.href='products.php?search=<?php echo urlencode($vendor['business_name']); ?>'">
                                <div class="vendor-image" style="display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                                    🏪
                                </div>
                                <div class="vendor-info">
                                    <div class="vendor-header">
                                        <h3 class="vendor-name"><?php echo htmlspecialchars($vendor['business_name'] ?: $vendor['name'] ?? 'Vendor'); ?></h3>
                                        <div class="rating-badge">⭐ <?php echo number_format(4.0 + (rand(0, 10) / 10), 1); ?></div>
                                    </div>
                                    <div class="vendor-meta">
                                        <div class="meta-item">
                                            <span>🕐</span>
                                            <span><?php echo rand(20, 45) . '-' . rand(45, 60); ?> min</span>
                                        </div>
                                        <div class="meta-item">
                                            <span>🚚</span>
                                            <span><?php echo rand(0, 1) ? 'Free' : '₱' . rand(20, 50); ?></span>
                                        </div>
                                    </div>
                                    <div class="vendor-tags">
                                        <span class="tag">Filipino</span>
                                        <span class="tag">Rice Meals</span>
                                    </div>
                                    <button class="order-btn" style="margin-top: 15px; background: #FFF5F8; color: #FF6B9D;">View Menu</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($view === 'messages'): ?>
                    <!-- MESSAGES VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">💬 Messages</h2>
                        <p style="color: var(--gray-600);">Chat with vendors</p>
                    </div>

                    <div class="chat-layout">
                        <!-- Sidebar / List -->
                        <div class="chat-sidebar <?php echo !$activeChatId ? 'active' : ''; ?>">
                            <div style="padding: 20px; border-bottom: 1px solid #F0F0F0;">
                                <input type="text" placeholder="Search chats..." style="width: 100%; padding: 10px 15px; border: 1px solid #E2E8F0; border-radius: 20px; background: #F7FAFC;">
                            </div>
                            <div class="chat-list">
                                <?php if (empty($conversations)): ?>
                                    <div class="empty-chat-state" style="padding: 40px 20px;">
                                        <div style="font-size: 3rem; margin-bottom: 10px;">💬</div>
                                        <p>No conversations yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($conversations as $conv): ?>
                                    <a href="dashboard.php?view=messages&id=<?php echo $conv['contact_id']; ?>" class="chat-item <?php echo $activeChatId == $conv['contact_id'] ? 'active' : ''; ?>">
                                        <div class="chat-avatar">
                                            <?php echo strtoupper(substr($conv['business_name'] ?: $conv['contact_name'], 0, 1)); ?>
                                        </div>
                                        <div class="chat-info">
                                            <div class="chat-name-row">
                                                <span class="chat-name"><?php echo htmlspecialchars($conv['business_name'] ?: $conv['contact_name']); ?></span>
                                                <span class="chat-time"><?php echo date('h:i A', strtotime($conv['last_message_time'])); ?></span>
                                            </div>
                                            <div class="chat-preview" style="<?php echo $conv['unread_count'] > 0 ? 'font-weight: 700; color: #2D3748;' : ''; ?>">
                                                <?php echo htmlspecialchars($conv['last_message']); ?>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Main Chat Area -->
                        <div class="chat-main <?php echo $activeChatId ? 'active' : ''; ?>">
                            <?php if ($activeChatId && $activeChatUser): ?>
                                <div class="chat-header">
                                    <a href="dashboard.php?view=messages" style="margin-right: 10px; color: var(--gray-600); text-decoration: none; font-size: 1.2rem; display: none;" class="mobile-back-btn">←</a>
                                    <div class="chat-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <?php echo strtoupper(substr($activeChatUser['business_name'] ?: $activeChatUser['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($activeChatUser['business_name'] ?: $activeChatUser['name']); ?></div>
                                        <div style="font-size: 0.75rem; color: #48BB78;">Online</div>
                                    </div>
                                </div>

                                <div class="chat-messages-area" id="chatMessages">
                                    <?php foreach ($activeMessages as $msg): ?>
                                    <div class="message-bubble <?php echo $msg['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                        <div class="message-time-stamp"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="chat-input-wrapper">
                                    <form method="POST" class="chat-form-modern">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="hidden" name="receiver_id" value="<?php echo $activeChatId; ?>">
                                        <input type="text" name="message" class="chat-input-modern" placeholder="Type a message..." required autocomplete="off">
                                        <button type="submit" class="btn-send-modern">➤</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="empty-chat-state">
                                    <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">💬</div>
                                    <h3>Select a conversation</h3>
                                    <p>Choose a vendor to start chatting</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <style>
                        @media (max-width: 768px) {
                            .mobile-back-btn { display: block !important; }
                        }
                    </style>
                    <script>
                        const chatContainer = document.getElementById('chatMessages');
                        if (chatContainer) {
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        }
                    </script>

                <?php endif; ?>

            </div>
        </main>
    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        // Mobile navbar visibility
        function updateMobileNavbar() {
            const mobileNavbar = document.querySelector('.mobile-navbar');
            if (mobileNavbar) {
                if (window.innerWidth <= 768) {
                    mobileNavbar.style.display = 'flex';
                } else {
                    mobileNavbar.style.display = 'none';
                }
            }
        }
        
        // Run on load
        document.addEventListener('DOMContentLoaded', function() {
            updateMobileNavbar();
            
            // Mobile menu button handler
            const mobileMenuBtn = document.getElementById('customerMobileMenuBtn');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    toggleSidebar();
                });
            }
        });
        
        // Run on resize
        window.addEventListener('resize', updateMobileNavbar);
        
        // Run immediately
        updateMobileNavbar();
    </script>
</body>
</html>
