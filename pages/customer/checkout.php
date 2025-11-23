<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Notification.php';

requireRole('customer');

$userId = getUserId();
$cartModel = new Cart($pdo);
$orderModel = new Order($pdo);
$notificationModel = new Notification($pdo);

// Get cart items
$cartItems = $cartModel->getItems($userId);
$total = $cartModel->getTotal($userId);

if (empty($cartItems)) {
    redirect(SITE_URL . '/pages/customer/cart.php');
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (empty($address) || empty($phone)) {
        setFlashMessage('Please fill in all required fields', 'error');
    } else {
        try {
            // Group items by vendor
            $vendorItems = [];
            foreach ($cartItems as $item) {
                $vendorId = $item['vendor_id'];
                if (!isset($vendorItems[$vendorId])) {
                    $vendorItems[$vendorId] = [];
                }
                $vendorItems[$vendorId][] = $item;
            }
            
            // Create separate order for each vendor
            foreach ($vendorItems as $vendorId => $items) {
                $vendorTotal = array_sum(array_map(function($item) {
                    return $item['price'] * $item['quantity'];
                }, $items));
                
                // Create order
                $orderId = $orderModel->create($userId, $vendorId, $vendorTotal, $address, $phone, $notes);
                
                // Add order items
                foreach ($items as $item) {
                    $orderModel->addItem($orderId, $item['product_id'], $item['quantity'], $item['price']);
                }
                
                // Create notification for vendor
                $notificationModel->create(
                    $vendorId,
                    'order',
                    'New Order Received',
                    'You have a new order #' . $orderId,
                    '/pages/vendor/orders.php?id=' . $orderId
                );
            }
            
            // Clear cart
            $cartModel->clear($userId);
            
            setFlashMessage('Order placed successfully!', 'success');
            redirect(SITE_URL . '/pages/customer/orders.php');
            
        } catch (Exception $e) {
            setFlashMessage('Error placing order: ' . $e->getMessage(), 'error');
        }
    }
}

$user = $_SESSION['user_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
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
                <li><a href="products.php">🍽️ Browse Products</a></li>
                <li><a href="cart.php">🛒 Cart</a></li>
                <li><a href="orders.php">📦 My Orders</a></li>
                <li><a href="messages.php">💬 Messages</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['text']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h1>📦 Checkout</h1>
                <p>Complete your order</p>
            </div>

            <div class="cart-container">
                <div class="checkout-form-section">
                    <div class="checkout-card">
                        <h2>Delivery Information</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required class="form-control" placeholder="+63 912 345 6789">
                            </div>
                            
                            <div class="form-group">
                                <label>Delivery Address *</label>
                                <textarea name="address" required class="form-control" rows="3" placeholder="House/Unit No., Street, Barangay, Municipality, Biliran"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Order Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions for the vendor..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-checkout">Place Order (<?php echo formatCurrency($total); ?>)</button>
                        </form>
                    </div>
                </div>

                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="checkout-item">
                            <span class="checkout-item-qty"><?php echo $item['quantity']; ?>x</span>
                            <span class="checkout-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="checkout-item-price"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($total); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span class="text-success">FREE</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span><?php echo formatCurrency($total); ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
