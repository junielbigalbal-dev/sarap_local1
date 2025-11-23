<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Message.php';

requireRole('vendor');

$userId = getUserId();
$productModel = new Product($pdo);
$orderModel = new Order($pdo);
$notificationModel = new Notification($pdo);
$userModel = new User($pdo);
$messageModel = new Message($pdo);

// Get user data
$user = $_SESSION['user_data'];
$userProfile = $userModel->getUserWithProfile($userId);

// Determine View
$view = $_GET['view'] ?? 'home';
$statusFilter = $_GET['status'] ?? '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_order_status') {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['new_status'];
        
        $order = $orderModel->getById($orderId);
        if ($order && $order['vendor_id'] == $userId) {
            $orderModel->updateStatus($orderId, $newStatus);
            
            $statusMessages = [
                'accepted' => 'Your order has been accepted!',
                'completed' => 'Your order has been completed!',
                'cancelled' => 'Your order has been cancelled.'
            ];
            
            if (isset($statusMessages[$newStatus])) {
                $notificationModel->create(
                    $order['customer_id'],
                    'order',
                    'Order Update',
                    $statusMessages[$newStatus] . ' Order #' . $orderId,
                    '/pages/customer/orders.php'
                );
            }
            
            setFlashMessage('Order status updated', 'success');
        }
        redirect(SITE_URL . '/pages/vendor/dashboard.php?view=orders');
    }
    
    if ($action === 'update_profile') {
        $businessName = sanitize($_POST['business_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        $userModel->updateProfile($userId, [
            'business_name' => $businessName,
            'phone' => $phone,
            'address' => $address
        ]);
        setFlashMessage('Profile updated successfully', 'success');
        redirect(SITE_URL . '/pages/vendor/dashboard.php?view=profile');
    }
    
    if ($action === 'send_message') {
        $receiverId = (int)$_POST['receiver_id'];
        $message = trim($_POST['message']);
        
        if (!empty($message)) {
            $messageModel->send($userId, $receiverId, $message);
            redirect(SITE_URL . "/pages/vendor/dashboard.php?view=messages&id=$receiverId");
        }
    }
}

// Get data based on view
$orders = [];
$products = [];
$stats = [];

if ($view === 'home' || $view === 'orders') {
    $orders = $orderModel->getByVendor($userId, $statusFilter ?: null);
}

if ($view === 'home' || $view === 'products') {
    $products = $productModel->getByVendor($userId);
}

// Calculate stats for home view
if ($view === 'home') {
    $allOrders = $orderModel->getByVendor($userId);
    $pendingOrders = array_filter($allOrders, fn($o) => $o['status'] === 'pending');
    $completedOrders = array_filter($allOrders, fn($o) => $o['status'] === 'completed');
    $totalRevenue = array_sum(array_map(fn($o) => $o['total'], $completedOrders));
    
    $stats = [
        'total_orders' => count($allOrders),
        'pending_orders' => count($pendingOrders),
        'total_revenue' => $totalRevenue,
        'total_products' => count($products),
        'avg_rating' => 4.5 // Mock for now
    ];
}

// Messages data
$conversations = [];
$activeChatId = null;
$activeMessages = [];
$activeChatUser = null;

if ($view === 'messages') {
    $conversations = $messageModel->getConversations($userId);
    $activeChatId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if ($activeChatId) {
        $activeMessages = $messageModel->getConversation($userId, $activeChatId);
        $activeChatUser = $userModel->getProfile($activeChatId);
        $messageModel->markAsRead($activeChatId, $userId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/master-dashboard.css?v=7">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/feed-styles.css?v=8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="background: linear-gradient(to bottom, #FFF5F8 0%, #FFFFFF 50%);">
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="<?php echo SITE_URL; ?>/frontend/public/assets/logo.png" alt="<?php echo SITE_NAME; ?>">
                <h3><?php echo SITE_NAME; ?></h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?php echo $view === 'home' ? 'active' : ''; ?>">🏠 Dashboard</a></li>
                <li><a href="dashboard.php?view=orders" class="<?php echo $view === 'orders' ? 'active' : ''; ?>">📦 Orders</a></li>
                <li><a href="dashboard.php?view=products" class="<?php echo $view === 'products' ? 'active' : ''; ?>">🍽️ Menu</a></li>
                <li><a href="dashboard.php?view=analytics" class="<?php echo $view === 'analytics' ? 'active' : ''; ?>">📊 Analytics</a></li>
                <li><a href="dashboard.php?view=messages" class="<?php echo $view === 'messages' ? 'active' : ''; ?>">💬 Messages</a></li>
                <li><a href="dashboard.php?view=profile" class="<?php echo $view === 'profile' ? 'active' : ''; ?>">🏪 Settings</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content" style="background: transparent; padding: 0;">
            
            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin: 20px 20px 0;">
                    <?php echo htmlspecialchars($flash['text']); ?>
                </div>
            <?php endif; ?>

            <!-- Modern Header (Persistent) -->
            <div class="dashboard-header-modern">
                <div class="user-welcome">
                    <h1>Hello, <?php echo htmlspecialchars($userProfile['business_name'] ?: $userProfile['name']); ?>! 👋</h1>
                    <p>Manage your restaurant and orders</p>
                </div>
                <div class="profile-icon-large">
                    🏪
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Quick Actions (Persistent) -->
                <div class="quick-actions-grid">
                    <a href="dashboard.php?view=orders" class="action-btn">
                        <div class="action-icon">📦</div>
                        <span class="action-label">Orders</span>
                    </a>
                    <a href="dashboard.php?view=products" class="action-btn">
                        <div class="action-icon">🍽️</div>
                        <span class="action-label">Menu</span>
                    </a>
                    <a href="dashboard.php?view=analytics" class="action-btn">
                        <div class="action-icon">💰</div>
                        <span class="action-label">Earnings</span>
                    </a>
                    <a href="dashboard.php?view=profile" class="action-btn">
                        <div class="action-icon">⚙️</div>
                        <span class="action-label">Settings</span>
                    </a>
                </div>

                <!-- DYNAMIC CONTENT AREA -->
                <?php if ($view === 'home'): ?>
                    <!-- HOME VIEW - Overview -->
                    
                    <!-- Stats Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 20px; color: white;">
                            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Total Orders</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['total_orders']; ?></div>
                        </div>
                        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 16px; padding: 20px; color: white;">
                            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Pending Orders</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['pending_orders']; ?></div>
                        </div>
                        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 16px; padding: 20px; color: white;">
                            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Total Revenue</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                        </div>
                        <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 16px; padding: 20px; color: white;">
                            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Rating</div>
                            <div style="font-size: 2rem; font-weight: 700;">⭐ <?php echo number_format($stats['avg_rating'], 1); ?></div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <h2 class="section-title">Recent Orders</h2>
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <h2>No orders yet</h2>
                            <p>Orders from customers will appear here</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 15px;">
                            <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                            <div class="order-card-modern">
                                <div class="order-card-header">
                                    <div class="order-vendor-info">
                                        <h3>Order #<?php echo $order['id']; ?></h3>
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
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="dashboard.php?view=orders" style="display: inline-block; margin-top: 20px; color: #FF6B9D; text-decoration: none; font-weight: 600;">View All Orders →</a>
                    <?php endif; ?>

                <?php elseif ($view === 'orders'): ?>
                    <!-- ORDERS VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">📦 Orders</h2>
                        <p style="color: var(--gray-600);">Manage your customer orders</p>
                    </div>

                    <!-- Status Filters -->
                    <div class="category-scroll" style="padding-left: 0; margin-bottom: 30px;">
                        <a href="dashboard.php?view=orders" class="category-btn <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
                        <a href="dashboard.php?view=orders&status=pending" class="category-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">⏳ Pending</a>
                        <a href="dashboard.php?view=orders&status=accepted" class="category-btn <?php echo $statusFilter === 'accepted' ? 'active' : ''; ?>">✅ Accepted</a>
                        <a href="dashboard.php?view=orders&status=completed" class="category-btn <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">🎉 Completed</a>
                        <a href="dashboard.php?view=orders&status=cancelled" class="category-btn <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">❌ Cancelled</a>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <h2>No orders found</h2>
                            <p>Try a different filter</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 15px;">
                            <?php foreach ($orders as $order): ?>
                            <div class="order-card-modern">
                                <div class="order-card-header">
                                    <div class="order-vendor-info">
                                        <h3>Order #<?php echo $order['id']; ?></h3>
                                        <p class="order-date"><?php echo formatDateTime($order['created_at']); ?></p>
                                        <p style="color: var(--gray-600); font-size: 0.9rem;">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-total-row">
                                    <span>Total</span>
                                    <span><?php echo formatCurrency($order['total']); ?></span>
                                </div>
                                <?php if ($order['status'] === 'pending'): ?>
                                <div class="order-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="accepted">
                                        <button type="submit" class="btn-primary-sm">Accept Order</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" class="btn-outline" onclick="return confirm('Cancel this order?')">Decline</button>
                                    </form>
                                </div>
                                <?php elseif ($order['status'] === 'accepted'): ?>
                                <div class="order-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="completed">
                                        <button type="submit" class="btn-primary-sm">Mark as Completed</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($view === 'products'): ?>
                    <!-- PRODUCTS VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 style="font-size: 1.5rem; font-weight: 700;">🍽️ Menu Management</h2>
                            <p style="color: var(--gray-600);">Manage your dishes and availability</p>
                        </div>
                        <a href="product-form.php" class="track-btn" style="width: auto; padding: 12px 24px; text-decoration: none; display: inline-block;">+ Add New Dish</a>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🍽️</div>
                            <h2>No products yet</h2>
                            <p>Add your first dish to get started</p>
                            <a href="product-form.php" class="track-btn" style="display: inline-block; width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none; text-align: center;">Add Product</a>
                        </div>
                    <?php else: ?>
                        <div class="vendor-grid" style="padding: 0; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                            <?php foreach ($products as $product): ?>
                            <div class="dish-card" style="width: 100%;">
                                <div class="dish-image-container">
                                    <?php if ($product['image']): ?>
                                        <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="dish-image">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem;">🍽️</div>
                                    <?php endif; ?>
                                    <?php if (!($product['is_available'] ?? true)): ?>
                                        <div class="discount-badge" style="background: #EF4444;">Unavailable</div>
                                    <?php endif; ?>
                                </div>
                                <div class="dish-info">
                                    <div class="dish-header">
                                        <h3 class="dish-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="dish-price"><?php echo formatCurrency($product['price']); ?></div>
                                    </div>
                                    <p class="dish-description"><?php echo htmlspecialchars(truncate($product['description'], 80)); ?></p>
                                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                                        <a href="product-form.php?id=<?php echo $product['id']; ?>" class="order-btn" style="flex: 1; text-align: center; text-decoration: none; background: #FFF5F8; color: #FF6B9D;">Edit</a>
                                        <button class="order-btn" style="flex: 1; background: <?php echo ($product['is_available'] ?? true) ? '#48BB78' : '#EF4444'; ?>; color: white;">
                                            <?php echo ($product['is_available'] ?? true) ? 'Available' : 'Unavailable'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($view === 'analytics'): ?>
                    <!-- ANALYTICS VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">📊 Analytics & Earnings</h2>
                        <p style="color: var(--gray-600);">Track your performance</p>
                    </div>

                    <!-- Stats Overview -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 8px;">Total Revenue</div>
                            <div style="font-size: 2rem; font-weight: 700; color: #FF6B9D;"><?php echo formatCurrency($stats['total_revenue'] ?? 0); ?></div>
                        </div>
                        <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 8px;">Completed Orders</div>
                            <div style="font-size: 2rem; font-weight: 700; color: #48BB78;"><?php echo count($completedOrders ?? []); ?></div>
                        </div>
                        <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 8px;">Menu Items</div>
                            <div style="font-size: 2rem; font-weight: 700; color: #667eea;"><?php echo $stats['total_products'] ?? 0; ?></div>
                        </div>
                    </div>

                    <div style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 20px;">Sales Overview</h3>
                        <p style="color: var(--gray-600);">Detailed analytics coming soon...</p>
                    </div>

                <?php elseif ($view === 'profile'): ?>
                    <!-- PROFILE VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">🏪 Business Settings</h2>
                        <p style="color: var(--gray-600);">Manage your business information</p>
                    </div>

                    <div class="profile-grid">
                        <div class="profile-card">
                            <h2>Business Information</h2>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group-modern">
                                    <label>Business Name</label>
                                    <input type="text" name="business_name" value="<?php echo htmlspecialchars($userProfile['business_name'] ?? ''); ?>" required class="form-control-modern">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Email</label>
                                    <input type="email" value="<?php echo htmlspecialchars($userProfile['email']); ?>" readonly class="form-control-modern">
                                    <small style="color: #A0AEC0; margin-top: 4px; display: block;">Email cannot be changed</small>
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>" class="form-control-modern" placeholder="e.g. 09123456789">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label>Business Address</label>
                                    <textarea name="address" class="form-control-modern" rows="3" placeholder="Enter your business address"><?php echo htmlspecialchars($userProfile['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn-save">Save Changes</button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($view === 'messages'): ?>
                    <!-- MESSAGES VIEW -->
                    <div class="dashboard-header" style="margin-bottom: 20px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700;">💬 Messages</h2>
                        <p style="color: var(--gray-600);">Chat with customers</p>
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
                                            <?php echo strtoupper(substr($conv['contact_name'], 0, 1)); ?>
                                        </div>
                                        <div class="chat-info">
                                            <div class="chat-name-row">
                                                <span class="chat-name"><?php echo htmlspecialchars($conv['contact_name']); ?></span>
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
                                        <?php echo strtoupper(substr($activeChatUser['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($activeChatUser['name']); ?></div>
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
                                    <p>Choose a customer to start chatting</p>
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
</body>
</html>
