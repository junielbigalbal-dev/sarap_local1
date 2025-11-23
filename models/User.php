<?php
/**
 * User Model
 * Handles user-related database operations
 */

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create new user
     */
    public function create($email, $password, $role = 'customer') {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password_hash, role, email_verified) 
            VALUES (?, ?, ?, FALSE)
        ");
        
        $passwordHash = hashPassword($password);
        $stmt->execute([$email, $passwordHash, $role]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get user with profile
     */
    public function getUserWithProfile($id) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, p.* 
            FROM users u
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get user profile (Alias for getUserWithProfile)
     */
    public function getProfile($id) {
        return $this->getUserWithProfile($id);
    }
    
    /**
     * Update user email verification status
     */
    public function verifyEmail($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Update user status
     */
    public function updateStatus($userId, $status) {
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }
    
    /**
     * Get all users by role
     */
    /**
     * Get all users by role
     */
    public function getUsersByRole($role, $limit = 100, $offset = 0) {
        // Cast to integers to avoid PDO binding issues
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $stmt = $this->pdo->prepare("
            SELECT u.*, p.name, p.business_name 
            FROM users u
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE u.role = ?
            ORDER BY u.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    
    /**
     * Create user profile
     */
    public function createProfile($userId, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_profiles (user_id, name, phone, address, lat, lng, business_name, business_hours)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId,
            $data['name'] ?? '',
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['lat'] ?? null,
            $data['lng'] ?? null,
            $data['business_name'] ?? null,
            $data['business_hours'] ?? null
        ]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = ['name', 'phone', 'address', 'lat', 'lng', 'avatar', 'bio', 'business_name', 'business_hours'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $userId;
        $sql = "UPDATE user_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get vendors near location
     */
    public function getVendorsNearLocation($lat, $lng, $radiusKm = 50) {
        // Using Haversine formula for distance calculation
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.email, p.*, 
                   (6371 * acos(cos(radians(?)) * cos(radians(p.lat)) * 
                   cos(radians(p.lng) - radians(?)) + sin(radians(?)) * 
                   sin(radians(p.lat)))) AS distance
            FROM users u
            INNER JOIN user_profiles p ON u.id = p.user_id
            WHERE u.role = 'vendor' 
              AND u.status = 'active'
              AND p.lat IS NOT NULL 
              AND p.lng IS NOT NULL
            HAVING distance < ?
            ORDER BY distance
        ");
        
        $stmt->execute([$lat, $lng, $lat, $radiusKm]);
        return $stmt->fetchAll();
    }
}
