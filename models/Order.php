<?php
/**
 * Order Model
 */

class Order {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($customerId, $vendorId, $total, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (customer_id, vendor_id, total, delivery_address, delivery_lat, delivery_lng, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $customerId,
            $vendorId,
            $total,
            $data['delivery_address'] ?? null,
            $data['delivery_lat'] ?? null,
            $data['delivery_lng'] ?? null,
            $data['notes'] ?? null
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    public function addItem($orderId, $productId, $quantity, $price) {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$orderId, $productId, $quantity, $price]);
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   c.email as customer_email, cp.name as customer_name,
                   v.email as vendor_email, vp.business_name as vendor_name
            FROM orders o
            INNER JOIN users c ON o.customer_id = c.id
            INNER JOIN users v ON o.vendor_id = v.id
            LEFT JOIN user_profiles cp ON c.id = cp.user_id
            LEFT JOIN user_profiles vp ON v.id = vp.user_id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getItems($orderId) {
        $stmt = $this->pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image
            FROM order_items oi
            INNER JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function getByCustomer($customerId, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, vp.business_name as vendor_name
            FROM orders o
            INNER JOIN users v ON o.vendor_id = v.id
            LEFT JOIN user_profiles vp ON v.id = vp.user_id
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getByVendor($vendorId, $status = null, $limit = 50) {
        if ($status) {
            $stmt = $this->pdo->prepare("
                SELECT o.*, cp.name as customer_name, cp.phone
                FROM orders o
                INNER JOIN users c ON o.customer_id = c.id
                LEFT JOIN user_profiles cp ON c.id = cp.user_id
                WHERE o.vendor_id = ? AND o.status = ?
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$vendorId, $status, $limit]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT o.*, cp.name as customer_name, cp.phone
                FROM orders o
                INNER JOIN users c ON o.customer_id = c.id
                LEFT JOIN user_profiles cp ON c.id = cp.user_id
                WHERE o.vendor_id = ?
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$vendorId, $limit]);
        }
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function getVendorStats($vendorId, $month = null, $year = null) {
        if ($month && $year) {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_sales,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
                FROM orders
                WHERE vendor_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?
            ");
            $stmt->execute([$vendorId, $month, $year]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_sales,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
                FROM orders
                WHERE vendor_id = ?
            ");
            $stmt->execute([$vendorId]);
        }
        return $stmt->fetch();
    }

    public function getDailySales($vendorId, $days = 30) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                SUM(total) as daily_total,
                COUNT(*) as daily_count
            FROM orders
            WHERE vendor_id = ? 
              AND status = 'completed'
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$vendorId, $days]);
        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
    }
}
