-- Sample Seed Data for Sarap Local
USE sarap_local;

-- Insert admin user
-- Password: admin123
INSERT INTO users (email, password_hash, role, email_verified, status) VALUES
('admin@saraplocal.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxIvT5u3i', 'admin', TRUE, 'active');

INSERT INTO user_profiles (user_id, name, phone, address) VALUES
(1, 'Admin User', '09123456789', 'Biliran Province, Philippines');

-- Insert sample customers
-- Password: customer123
INSERT INTO users (email, password_hash, role, email_verified, status) VALUES
('customer1@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxIvT5u3i', 'customer', TRUE, 'active'),
('customer2@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxIvT5u3i', 'customer', TRUE, 'active');

INSERT INTO user_profiles (user_id, name, phone, address, lat, lng) VALUES
(2, 'Juan Dela Cruz', '09111111111', 'Naval, Biliran', 11.5547, 124.3956),
(3, 'Maria Santos', '09222222222', 'Caibiran, Biliran', 11.5667, 124.5667);

-- Insert sample vendors
-- Password: vendor123
INSERT INTO users (email, password_hash, role, email_verified, status) VALUES
('vendor1@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxIvT5u3i', 'vendor', TRUE, 'active'),
('vendor2@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxIvT5u3i', 'vendor', TRUE, 'active'),
('vendor3@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxIvT5u3i', 'vendor', TRUE, 'active');

INSERT INTO user_profiles (user_id, name, phone, address, lat, lng, business_name, business_hours) VALUES
(4, 'Pedro Restaurant Owner', '09333333333', 'Naval, Biliran', 11.5547, 124.3956, 'Pedro\'s Lechon', '8:00 AM - 8:00 PM'),
(5, 'Ana Bakery Owner', '09444444444', 'Caibiran, Biliran', 11.5667, 124.5667, 'Ana\'s Sweet Treats', '6:00 AM - 6:00 PM'),
(6, 'Jose Seafood Vendor', '09555555555', 'Kawayan, Biliran', 11.6167, 124.4833, 'Jose\'s Fresh Catch', '5:00 AM - 12:00 PM');

-- Insert sample products
INSERT INTO products (vendor_id, name, description, price, category, status, stock_quantity) VALUES
-- Pedro's Lechon products
(4, 'Whole Lechon', 'Crispy roasted pig, perfect for celebrations', 8500.00, 'Main Dish', 'active', 5),
(4, 'Lechon Belly', 'Crispy pork belly, 1kg', 450.00, 'Main Dish', 'active', 20),
(4, 'Lechon Kawali', 'Deep fried pork belly, 500g', 250.00, 'Main Dish', 'active', 30),

-- Ana's Sweet Treats products
(5, 'Ensaymada', 'Buttery brioche pastry with cheese', 35.00, 'Pastry', 'active', 50),
(5, 'Pandesal', 'Filipino bread rolls, 10 pieces', 25.00, 'Bread', 'active', 100),
(5, 'Chocolate Cake', 'Rich chocolate cake, whole', 450.00, 'Cake', 'active', 10),
(5, 'Ube Cake', 'Purple yam cake, whole', 500.00, 'Cake', 'active', 8),

-- Jose's Fresh Catch products
(6, 'Fresh Tuna', 'Locally caught tuna, per kg', 350.00, 'Seafood', 'active', 25),
(6, 'Bangus (Milkfish)', 'Fresh milkfish, per kg', 180.00, 'Seafood', 'active', 40),
(6, 'Squid', 'Fresh squid, per kg', 280.00, 'Seafood', 'active', 30),
(6, 'Shrimp', 'Fresh shrimp, per kg', 450.00, 'Seafood', 'active', 20);

-- Insert sample posts for news feed
INSERT INTO posts (user_id, content, type, product_id) VALUES
(4, 'Fresh lechon available today! Order now for your celebrations! 🐷🔥', 'product', 1),
(5, 'New batch of ensaymada just came out of the oven! 🥐✨', 'product', 4),
(6, 'Just caught fresh tuna this morning! Get them while they\'re fresh! 🐟', 'product', 8),
(4, 'Thank you for all the orders! We\'re preparing for the weekend rush! 🙏', 'post', NULL),
(5, 'Special discount on cakes this weekend! 🎂 20% off!', 'post', NULL);

-- Insert sample orders
INSERT INTO orders (customer_id, vendor_id, total, status, delivery_address, delivery_lat, delivery_lng) VALUES
(2, 4, 700.00, 'completed', 'Naval, Biliran', 11.5547, 124.3956),
(3, 5, 110.00, 'accepted', 'Caibiran, Biliran', 11.5667, 124.5667),
(2, 6, 630.00, 'pending', 'Naval, Biliran', 11.5547, 124.3956);

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
-- Order 1: Customer 2 from Pedro's Lechon
(1, 2, 1, 450.00),
(1, 3, 1, 250.00),
-- Order 2: Customer 3 from Ana's Sweet Treats
(2, 4, 2, 35.00),
(2, 5, 2, 25.00),
-- Order 3: Customer 2 from Jose's Fresh Catch
(3, 8, 1, 350.00),
(3, 10, 1, 280.00);

-- Insert sample messages
INSERT INTO messages (sender_id, receiver_id, message, is_read) VALUES
(2, 4, 'Hi! Is the lechon belly available for tomorrow?', TRUE),
(4, 2, 'Yes! We have plenty in stock. What time do you need it?', TRUE),
(2, 4, 'Around 2 PM would be great. Thanks!', FALSE),
(3, 5, 'Do you have ube cake available?', TRUE),
(5, 3, 'Yes! We have 8 in stock. Would you like to order?', FALSE);

-- Insert sample notifications
INSERT INTO notifications (user_id, type, title, message, link, is_read) VALUES
(4, 'order', 'New Order', 'You have a new order from Juan Dela Cruz', '/pages/vendor/orders.php?id=1', TRUE),
(4, 'order', 'New Order', 'You have a new order from Juan Dela Cruz', '/pages/vendor/orders.php?id=3', FALSE),
(5, 'order', 'New Order', 'You have a new order from Maria Santos', '/pages/vendor/orders.php?id=2', FALSE),
(2, 'order', 'Order Accepted', 'Your order has been accepted by Pedro\'s Lechon', '/pages/customer/orders.php?id=1', TRUE),
(3, 'order', 'Order Accepted', 'Your order has been accepted by Ana\'s Sweet Treats', '/pages/customer/orders.php?id=2', FALSE);

-- Insert sample reviews
INSERT INTO reviews (product_id, user_id, order_id, rating, comment) VALUES
(2, 2, 1, 5, 'Amazing lechon belly! Very crispy and flavorful!'),
(3, 2, 1, 5, 'Best lechon kawali in Biliran!');
