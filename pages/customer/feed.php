<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Cart.php';

requireRole('customer');

$userId = getUserId();
$productModel = new Product($pdo);
$userModel = new User($pdo);
$cartModel = new Cart($pdo);

// Get user data
$user = $_SESSION['user_data'];
$cartCount = $cartModel->getCount($userId);

// Get search query and category
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Get products and vendors
if ($search) {
    // Search in products
    $allProducts = $productModel->search($search);
    $featuredProducts = array_slice($allProducts, 0, 12);
    
    // Filter vendors based on search
    $vendors = [];
    $vendorIds = [];
    foreach ($allProducts as $product) {
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
} elseif ($category) {
    // Filter by category
    $allProducts = $productModel->getByCategory($category);
    $featuredProducts = array_slice($allProducts, 0, 12);
    
    // Get vendors from filtered products
    $vendors = [];
    $vendorIds = [];
    foreach ($allProducts as $product) {
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
} else {
    // Get all products
    $allProducts = $productModel->getActiveProducts(50);
    $featuredProducts = array_slice($allProducts, 0, 12);
    
    // Get unique vendors from products
    $vendors = [];
    $vendorIds = [];
    foreach ($allProducts as $product) {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Food - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/master-dashboard.css?v=6">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/feed-styles.css?v=1">
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
                <li><a href="dashboard.php">🏠 Dashboard</a></li>
                <li><a href="feed.php" class="active">📰 News Feed</a></li>
                <li><a href="products.php">🍽️ Browse Products</a></li>
                <li><a href="cart.php">🛒 Cart <?php if ($cartCount > 0) echo "($cartCount)"; ?></a></li>
                <li><a href="orders.php">📦 My Orders</a></li>
                <li><a href="messages.php">💬 Messages</a></li>
                <li><a href="map.php">🗺️ Find Vendors</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content" style="background: transparent;">
            <!-- Search Header -->
            <div class="food-app-header">
                <form method="GET" action="feed.php" class="search-container" style="position: relative;">
                    <input type="text" name="search" id="searchInput" class="search-box" placeholder="Search for shops & restaurants" value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 20px;" autocomplete="off">
                    <?php if ($search): ?>
                        <a href="feed.php" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #718096; text-decoration: none; font-size: 1.2rem; cursor: pointer; z-index: 10;" title="Clear search">✕</a>
                    <?php endif; ?>
                    
                    <!-- Search Suggestions Dropdown -->
                    <div id="searchSuggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 0 0 16px 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); max-height: 400px; overflow-y: auto; z-index: 1000; margin-top: -8px; padding-top: 8px;"></div>
                </form>
            </div>

            <script>
                // All products and vendors data for search suggestions
                const allProducts = <?php echo json_encode($allProducts); ?>;
                const allVendors = <?php echo json_encode($vendors); ?>;
                
                const searchInput = document.getElementById('searchInput');
                const suggestionsBox = document.getElementById('searchSuggestions');
                
                searchInput.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();
                    
                    if (query.length < 2) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }
                    
                    // Filter products
                    const matchingProducts = allProducts.filter(product => 
                        product.name.toLowerCase().includes(query) || 
                        (product.description && product.description.toLowerCase().includes(query))
                    ).slice(0, 5);
                    
                    // Filter vendors
                    const matchingVendors = allVendors.filter(vendor => 
                        vendor.business_name.toLowerCase().includes(query)
                    ).slice(0, 3);
                    
                    if (matchingProducts.length === 0 && matchingVendors.length === 0) {
                        suggestionsBox.innerHTML = '<div style="padding: 20px; text-align: center; color: #718096;">No suggestions found</div>';
                        suggestionsBox.style.display = 'block';
                        return;
                    }
                    
                    let html = '';
                    
                    // Show matching products
                    if (matchingProducts.length > 0) {
                        html += '<div style="padding: 12px 20px; font-weight: 600; color: #2D3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">🍽️ Food Items</div>';
                        matchingProducts.forEach(product => {
                            const imageHtml = product.image 
                                ? `<img src="${product.image}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">` 
                                : '<div style="width: 50px; height: 50px; background: linear-gradient(135deg, #C67D3B 0%, #E8C89F 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">🍽️</div>';
                            
                            html += `
                                <a href="feed.php?search=${encodeURIComponent(product.name)}" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; text-decoration: none; color: inherit; transition: background 0.2s;" onmouseover="this.style.background='#F7FAFC'" onmouseout="this.style.background='white'">
                                    ${imageHtml}
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2D3748; margin-bottom: 2px;">${product.name}</div>
                                        <div style="font-size: 0.85rem; color: #718096;">by ${product.vendor_name}</div>
                                    </div>
                                    <div style="font-weight: 700; color: #C67D3B;">₱${parseFloat(product.price).toFixed(2)}</div>
                                </a>
                            `;
                        });
                    }
                    
                    // Show matching vendors
                    if (matchingVendors.length > 0) {
                        html += '<div style="padding: 12px 20px; font-weight: 600; color: #2D3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-top: 1px solid #F0F0F0; margin-top: 8px;">🏪 Restaurants</div>';
                        matchingVendors.forEach(vendor => {
                            html += `
                                <a href="feed.php?search=${encodeURIComponent(vendor.business_name)}" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; text-decoration: none; color: inherit; transition: background 0.2s;" onmouseover="this.style.background='#F7FAFC'" onmouseout="this.style.background='white'">
                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #C67D3B 0%, #E8C89F 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">🏪</div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2D3748; margin-bottom: 2px;">${vendor.business_name}</div>
                                        <div style="font-size: 0.85rem; color: #718096;">⭐ ${vendor.rating} • ${vendor.delivery_time}</div>
                                    </div>
                                </a>
                            `;
                        });
                    }
                    
                    suggestionsBox.innerHTML = html;
                    suggestionsBox.style.display = 'block';
                });
                
                // Close suggestions when clicking outside
                document.addEventListener('click', function(event) {
                    if (!searchInput.contains(event.target) && !suggestionsBox.contains(event.target)) {
                        suggestionsBox.style.display = 'none';
                    }
                });
                
                // Show suggestions when focusing on search input with existing text
                searchInput.addEventListener('focus', function() {
                    if (this.value.trim().length >= 2) {
                        this.dispatchEvent(new Event('input'));
                    }
                });
            </script>

            <!-- Categories -->
            <div class="category-scroll">
                <a href="feed.php" class="category-btn <?php echo empty($category) && empty($search) ? 'active' : ''; ?>" style="text-decoration: none;">🔥 Popular</a>
                <a href="feed.php?category=Local Cuisine" class="category-btn <?php echo $category === 'Local Cuisine' ? 'active' : ''; ?>" style="text-decoration: none;">🍜 Filipino</a>
                <a href="feed.php?category=Restaurants" class="category-btn <?php echo $category === 'Restaurants' ? 'active' : ''; ?>" style="text-decoration: none;">🍽️ Restaurants</a>
                <a href="feed.php?category=Street Food" class="category-btn <?php echo $category === 'Street Food' ? 'active' : ''; ?>" style="text-decoration: none;">🍕 Fast Food</a>
                <a href="feed.php?category=Desserts" class="category-btn <?php echo $category === 'Desserts' ? 'active' : ''; ?>" style="text-decoration: none;">🍰 Desserts</a>
                <a href="feed.php?category=Beverages" class="category-btn <?php echo $category === 'Beverages' ? 'active' : ''; ?>" style="text-decoration: none;">☕ Drinks</a>
                <a href="feed.php?category=Biliran Delicacies" class="category-btn <?php echo $category === 'Biliran Delicacies' ? 'active' : ''; ?>" style="text-decoration: none;">🍿 Healthy</a>
            </div>

            <!-- Featured Dishes -->
            <section style="margin-bottom: 40px;">
                <?php if ($search): ?>
                    <h2 class="section-title">🔍 Search Results for "<?php echo htmlspecialchars($search); ?>"</h2>
                    <?php if (empty($featuredProducts)): ?>
                        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 16px; margin: 20px 0;">
                            <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
                            <h3 style="font-size: 1.5rem; margin-bottom: 10px; color: #2D3748;">No results found</h3>
                            <p style="color: #718096; margin-bottom: 20px;">Try searching for something else</p>
                            <a href="feed.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); color: white; text-decoration: none; border-radius: 12px; font-weight: 600;">Clear Search</a>
                        </div>
                    <?php endif; ?>
                <?php elseif ($category): ?>
                    <h2 class="section-title">🍽️ <?php echo htmlspecialchars($category); ?></h2>
                    <?php if (empty($featuredProducts)): ?>
                        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 16px; margin: 20px 0;">
                            <div style="font-size: 4rem; margin-bottom: 20px;">🍽️</div>
                            <h3 style="font-size: 1.5rem; margin-bottom: 10px; color: #2D3748;">No items in this category</h3>
                            <p style="color: #718096; margin-bottom: 20px;">Try another category</p>
                            <a href="feed.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #C67D3B 0%, #A86A2E 100%); color: white; text-decoration: none; border-radius: 12px; font-weight: 600;">View All</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 class="section-title">🎉 Featured Dishes</h2>
                <?php endif; ?>
                <div class="dishes-scroll">
                    <?php foreach (array_slice($featuredProducts, 0, 8) as $index => $product): ?>
                    <div class="dish-card">
                        <div class="dish-image-container">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo getProductImage($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="dish-image">
                            <?php endif; ?>
                            <?php if ($index % 3 === 0): ?>
                                <div class="discount-badge">20% OFF</div>
                            <?php endif; ?>
                        </div>
                        <div class="dish-info">
                            <div class="dish-header">
                                <h3 class="dish-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="dish-price"><?php echo formatCurrency($product['price']); ?></div>
                            </div>
                            <div class="dish-vendor">by <?php echo htmlspecialchars($product['vendor_name']); ?></div>
                            <p class="dish-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 80)) . (strlen($product['description']) > 80 ? '...' : ''); ?>
                            </p>
                            <button class="order-btn" onclick="openOrderModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', <?php echo $product['price']; ?>, '<?php echo $product['image'] ? getProductImage($product['image']) : ''; ?>')">Order Now</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- All Restaurants -->
            <section>
                <h2 class="section-title">🏪 All Restaurants</h2>
                <div class="vendor-grid">
                    <?php foreach ($vendors as $vendor): ?>
                    <div class="vendor-card" onclick="window.location.href='products.php'">
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
        </main>
    </div>

    <!-- Order Confirmation Modal -->
    <div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; animation: fadeIn 0.3s;">
        <div style="background: white; border-radius: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s; position: relative;">
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
                    <div style="display: flex; align-items: center; gap: 15px; justify-content: center;">
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
                <form method="POST" action="dashboard.php?view=products" id="modalCartForm">
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
</body>
</html>
