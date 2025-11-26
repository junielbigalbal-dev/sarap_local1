<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
$pdo = require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/User.php';

$productModel = new Product($pdo);
$userModel = new User($pdo);

// Get featured products
$featuredProducts = $productModel->getActiveProducts(8);

// Get top vendors
$vendors = $userModel->getUsersByRole('vendor', 6);

// Categories
$categories = [
    ['name' => 'Local Cuisine', 'icon' => '🍲', 'color' => '#FF6B35'],
    ['name' => 'Restaurants', 'icon' => '🍽️', 'color' => '#F7931E'],
    ['name' => 'Cafés', 'icon' => '☕', 'color' => '#FFD23F'],
    ['name' => 'Biliran Delicacies', 'icon' => '🥘', 'color' => '#FF8C61'],
    ['name' => 'Street Food', 'icon' => '🌮', 'color' => '#E85D2F'],
    ['name' => 'Desserts', 'icon' => '🍰', 'color' => '#FFA07A']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title><?php echo SITE_NAME; ?> - Biliran's #1 Food Delivery | Order Local Food Online</title>
    <meta name="title" content="<?php echo SITE_NAME; ?> - Biliran's #1 Food Delivery Service">
    <meta name="description" content="Order delicious local food from the best restaurants in Biliran Province. Fast delivery, fresh ingredients, and authentic Filipino cuisine. Free delivery on your first order!">
    <meta name="keywords" content="Biliran food delivery, Naval food delivery, Biliran restaurants, Filipino food delivery, local food Biliran, food delivery Philippines, Sarap Local, Biliran delicacies, online food order Biliran">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo SITE_URL; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:title" content="<?php echo SITE_NAME; ?> - Biliran's #1 Food Delivery">
    <meta property="og:description" content="Order delicious local food from the best restaurants in Biliran Province. Fast delivery, fresh ingredients, and authentic Filipino cuisine.">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/images/S.png">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="en_PH">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo SITE_URL; ?>">
    <meta property="twitter:title" content="<?php echo SITE_NAME; ?> - Biliran's #1 Food Delivery">
    <meta property="twitter:description" content="Order delicious local food from the best restaurants in Biliran Province. Fast delivery, fresh ingredients, and authentic Filipino cuisine.">
    <meta property="twitter:image" content="<?php echo SITE_URL; ?>/images/S.png">
    
    <!-- Geo Tags -->
    <meta name="geo.region" content="PH-BIL">
    <meta name="geo.placename" content="Naval, Biliran">
    <meta name="geo.position" content="11.5544;124.3975">
    <meta name="ICBM" content="11.5544, 124.3975">
    
    <!-- Structured Data / Schema.org -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FoodEstablishment",
      "name": "<?php echo SITE_NAME; ?>",
      "description": "Biliran's #1 Food Delivery Service",
      "url": "<?php echo SITE_URL; ?>",
      "logo": "<?php echo SITE_URL; ?>/images/S.png",
      "image": "<?php echo SITE_URL; ?>/images/S.png",
      "telephone": "+63-912-345-6789",
      "email": "hello@saraplocal.com",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Naval",
        "addressLocality": "Biliran",
        "addressRegion": "Eastern Visayas",
        "postalCode": "6560",
        "addressCountry": "PH"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": "11.5544",
        "longitude": "124.3975"
      },
      "servesCuisine": "Filipino",
      "priceRange": "₱₱",
      "acceptsReservations": "False",
      "hasDeliveryMethod": {
        "@type": "DeliveryMethod",
        "name": "Food Delivery"
      }
    }
    </script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/images/S.png">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/images/S.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/foodpanda-style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar-modern">
        <div class="container-fluid">
            <div class="navbar-brand">
                <img src="<?php echo SITE_URL; ?>/images/S.png" alt="<?php echo SITE_NAME; ?>" class="logo">
                <span class="brand-name"><?php echo SITE_NAME; ?></span>
            </div>
            <div class="navbar-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/pages/<?php echo getUserRole(); ?>/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php" class="btn-outline">Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="nav-link">Login</a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/signup.php" class="btn-primary-nav">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Search -->
    <section class="hero-foodpanda">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">It's the food and groceries you love, delivered</h1>
                <p class="hero-subtitle">Discover the best food & drinks in Biliran Province</p>
                
                <div class="search-container">
                    <div class="search-box">
                        <span class="search-icon">📍</span>
                        <input type="text" placeholder="Enter your location in Biliran (Naval, Caibiran, Kawayan...)" class="search-input">
                        <button class="btn-search">Find Food</button>
                    </div>
                    <p class="search-hint">Or <a href="<?php echo SITE_URL; ?>/pages/auth/login.php">login</a> to see your saved addresses</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Tiles -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">What do you want to eat today?</h2>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card" style="--category-color: <?php echo $category['color']; ?>">
                    <div class="category-icon"><?php echo $category['icon']; ?></div>
                    <h3 class="category-name"><?php echo $category['name']; ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Promo Banner -->
    <section class="promo-banner">
        <div class="container">
            <div class="promo-content">
                <div class="promo-badge">🎉 Limited Offer</div>
                <h2>Get 20% off your first order!</h2>
                <p>Use code: <strong>BILIRAN20</strong> at checkout</p>
                <a href="<?php echo SITE_URL; ?>/pages/auth/signup.php" class="btn-promo">Order Now</a>
            </div>
        </div>
    </section>

    <!-- Featured Vendors -->
    <section class="vendors-section">
        <div class="container">
            <div class="section-header-flex">
                <h2 class="section-title">Popular restaurants in Biliran</h2>
                <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="view-all">View all →</a>
            </div>
            <div class="vendor-grid">
                <?php foreach ($vendors as $vendor): ?>
                <div class="vendor-card-modern">
                    <div class="vendor-image-placeholder">
                        <div class="vendor-badge">⭐ 4.8</div>
                    </div>
                    <div class="vendor-info">
                        <h3 class="vendor-name"><?php echo esc($vendor['business_name'] ?: $vendor['name']); ?></h3>
                        <p class="vendor-meta">
                            <span class="vendor-cuisine">Filipino</span>
                            <span class="vendor-dot">•</span>
                            <span class="vendor-location"><?php echo esc($vendor['address'] ?? 'Biliran'); ?></span>
                        </p>
                        <div class="vendor-footer">
                            <span class="delivery-time">⚡ 20-30 min</span>
                            <span class="delivery-fee">Free delivery</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Popular Meals -->
    <section class="meals-section">
        <div class="container">
            <h2 class="section-title">Popular dishes near you</h2>
            <div class="meals-grid">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="meal-card">
                    <div class="meal-image-wrapper">
                        <?php if ($product['image']): ?>
                            <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="meal-image">
                        <?php else: ?>
                            <div class="meal-image-placeholder">🍽️</div>
                        <?php endif; ?>
                        <button class="btn-favorite">❤️</button>
                    </div>
                    <div class="meal-info">
                        <h3 class="meal-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="meal-vendor"><?php echo htmlspecialchars($product['vendor_name'] ?: 'Local Vendor'); ?></p>
                        <div class="meal-footer">
                            <span class="meal-price"><?php echo formatCurrency($product['price']); ?></span>
                            <button class="btn-add-cart">+ Add</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Customer Testimonials -->
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title">What our customers say</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                    <p class="testimonial-text">"Best food delivery in Biliran! Fast service and delicious local food. Highly recommended!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">JD</div>
                        <div>
                            <p class="author-name">Juan Dela Cruz</p>
                            <p class="author-location">Naval, Biliran</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                    <p class="testimonial-text">"Love supporting local vendors through this app. The lechon from Pedro's is amazing!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">MS</div>
                        <div>
                            <p class="author-name">Maria Santos</p>
                            <p class="author-location">Caibiran, Biliran</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                    <p class="testimonial-text">"Easy to use and great selection of local delicacies. Delivery is always on time!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RC</div>
                        <div>
                            <p class="author-name">Rosa Cruz</p>
                            <p class="author-location">Kawayan, Biliran</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vendor CTA -->
    <section class="vendor-cta-section">
        <div class="container">
            <div class="cta-content">
                <div class="cta-text">
                    <h2>Are you a restaurant or food vendor?</h2>
                    <p>Join Sarap Local and reach thousands of customers in Biliran Province</p>
                    <ul class="cta-benefits">
                        <li>✓ Increase your sales</li>
                        <li>✓ Easy order management</li>
                        <li>✓ Free marketing support</li>
                        <li>✓ No setup fees</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/signup.php?role=vendor" class="btn-cta-large">Become a Partner</a>
                </div>
                <div class="cta-image">
                    <div class="cta-image-placeholder">
                        <span style="font-size: 8rem;">🍳</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="<?php echo SITE_URL; ?>/images/S.png" alt="<?php echo SITE_NAME; ?>" class="footer-logo">
                    <p class="footer-tagline">Biliran's favorite food delivery service</p>
                    <div class="social-links">
                        <a href="#" class="social-icon">📘</a>
                        <a href="#" class="social-icon">📷</a>
                        <a href="#" class="social-icon">🐦</a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4 class="footer-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-title">For Partners</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/pages/auth/signup.php?role=vendor">Become a Vendor</a></li>
                        <li><a href="#">Partner Portal</a></li>
                        <li><a href="#">Delivery Partner</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-title">Contact</h4>
                    <ul class="footer-links">
                        <li>📍 Naval, Biliran Province</li>
                        <li>📞 +63 912 345 6789</li>
                        <li>✉️ hello@saraplocal.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. | Serving Biliran Province 🇵🇭</p>
                <div class="footer-legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
