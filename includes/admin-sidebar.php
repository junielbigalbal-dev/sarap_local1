<?php
// Admin Sidebar Navigation Component
$currentPage = basename($_SERVER['PHP_SELF']);

// Get notification counts
$pendingVendorsCount = 0;
$pendingProductsCount = 0;
$openTicketsCount = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor' AND status = 'pending'");
    $pendingVendorsCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'pending'");
    $pendingProductsCount = $stmt->fetch()['count'];
    
    // Check if support_tickets table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_tickets'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'in_progress')");
        $openTicketsCount = $stmt->fetch()['count'];
    }
} catch (Exception $e) {
    // Silently fail if tables don't exist yet
}
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="<?php echo SITE_URL; ?>/frontend/public/assets/logo.png" alt="<?php echo SITE_NAME; ?>">
        </div>
        <div class="sidebar-brand">
            <h2><?php echo SITE_NAME; ?></h2>
            <p>Admin Panel</p>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
    </div>

    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🏠</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        <span>Analytics</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Management Section -->
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="vendors.php" class="nav-link <?php echo $currentPage === 'vendors.php' || $currentPage === 'vendor-detail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🏪</span>
                        <span>Vendors</span>
                        <?php if ($pendingVendorsCount > 0): ?>
                        <span class="nav-badge"><?php echo $pendingVendorsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo $currentPage === 'customers.php' || $currentPage === 'customer-detail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">👥</span>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' || $currentPage === 'order-detail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📦</span>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="menu-items.php" class="nav-link <?php echo $currentPage === 'menu-items.php' || $currentPage === 'categories.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🍽️</span>
                        <span>Menu & Categories</span>
                        <?php if ($pendingProductsCount > 0): ?>
                        <span class="nav-badge"><?php echo $pendingProductsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Financial Section -->
        <div class="nav-section">
            <div class="nav-section-title">Financial</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="payments.php" class="nav-link <?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">💰</span>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="transactions.php" class="nav-link <?php echo $currentPage === 'transactions.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">💳</span>
                        <span>Transactions</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Marketing Section -->
        <div class="nav-section">
            <div class="nav-section-title">Marketing</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="promotions.php" class="nav-link <?php echo $currentPage === 'promotions.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🎁</span>
                        <span>Promotions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="banners.php" class="nav-link <?php echo $currentPage === 'banners.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🖼️</span>
                        <span>Banners</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cms.php" class="nav-link <?php echo $currentPage === 'cms.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📝</span>
                        <span>Content</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Support Section -->
        <div class="nav-section">
            <div class="nav-section-title">Support</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="support.php" class="nav-link <?php echo $currentPage === 'support.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🎫</span>
                        <span>Tickets</span>
                        <?php if ($openTicketsCount > 0): ?>
                        <span class="nav-badge"><?php echo $openTicketsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php" class="nav-link <?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">💬</span>
                        <span>Messages</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Settings Section -->
        <div class="nav-section">
            <div class="nav-section-title">Settings</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin-users.php" class="nav-link <?php echo $currentPage === 'admin-users.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">👨‍💼</span>
                        <span>Admin Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="security.php" class="nav-link <?php echo $currentPage === 'security.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🔒</span>
                        <span>Security</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Other Section -->
        <div class="nav-section">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>" class="nav-link">
                        <span class="nav-icon">🌐</span>
                        <span>View Site</span>
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
