<?php
/**
 * Cart Model
 */

class Cart {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function add($userId, $productId, $quantity = 1) {
        // Check if item already exists
        $stmt = $this->pdo->prepare("SELECT * FROM carts WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $stmt = $this->pdo->prepare("
                UPDATE carts SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?
            ");
            return $stmt->execute([$quantity, $userId, $productId]);
        } else {
            // Insert new item
            $stmt = $this->pdo->prepare("
                INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)
            ");
            return $stmt->execute([$userId, $productId, $quantity]);
        }
    }
    
    public function update($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->remove($userId, $productId);
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?
        ");
        return $stmt->execute([$quantity, $userId, $productId]);
    }
    
    public function remove($userId, $productId) {
        $stmt = $this->pdo->prepare("DELETE FROM carts WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    public function getItems($userId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.name, p.price, p.image, p.vendor_id,
                   up.business_name as vendor_name
            FROM carts c
            INNER JOIN products p ON c.product_id = p.id
            INNER JOIN users u ON p.vendor_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE c.user_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getTotal($userId) {
        $stmt = $this->pdo->prepare("
            SELECT SUM(c.quantity * p.price) as total
            FROM carts c
            INNER JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch()['total'] ?? 0;
    }
    
    public function getCount($userId) {
        $stmt = $this->pdo->prepare("SELECT SUM(quantity) as count FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] ?? 0;
    }
    
    public function clear($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM carts WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function clearByVendor($userId, $vendorId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM carts
            USING products
            WHERE carts.product_id = products.id 
            AND carts.user_id = ? 
            AND products.vendor_id = ?
        ");
        return $stmt->execute([$userId, $vendorId]);
    }
}
