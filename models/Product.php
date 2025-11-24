<?php
/**
 * Product Model
 * Handles product-related database operations
 */

class Product {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create new product
     */
    public function create($vendorId, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (vendor_id, name, description, price, category_id, image, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $vendorId,
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['category_id'] ?? null,
            $data['image'] ?? null
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get product by ID
     */
    public function findById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.email as vendor_email, up.name as vendor_name, up.business_name
            FROM products p
            INNER JOIN users u ON p.vendor_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get products by vendor
     */
    public function getByVendor($vendorId, $limit = 50, $offset = 0) {
        // Cast to integers to avoid PDO binding issues
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM products 
            WHERE vendor_id = ?
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get active products
     */
    public function getActiveProducts($limit = 50, $offset = 0) {
        // Cast to integers to avoid PDO binding issues
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, up.business_name as vendor_name
            FROM products p
            INNER JOIN users u ON p.vendor_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE p.status = 'active' AND u.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Search products
     */
    public function search($query, $limit = 50) {
        // Cast to integer to avoid PDO binding issues
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, up.business_name as vendor_name, c.name as category_name
            FROM products p
            INNER JOIN users u ON p.vendor_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active' 
              AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)
            ORDER BY p.created_at DESC
            LIMIT $limit
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = ['name', 'description', 'price', 'category_id', 'image', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete product
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($category, $limit = 50) {
        // Cast to integer to avoid PDO binding issues
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, up.business_name as vendor_name, c.name as category_name
            FROM products p
            INNER JOIN users u ON p.vendor_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE c.name = ? AND p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get top products (by orders)
     */
    public function getTopProducts($limit = 10) {
        // Cast to integer to avoid PDO binding issues
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold
            FROM products p
            INNER JOIN order_items oi ON p.id = oi.product_id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY order_count DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
