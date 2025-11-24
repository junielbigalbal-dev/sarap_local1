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
            INSERT INTO users (email, password, role, is_verified) 
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
        $stmt = $this->pdo->prepare("UPDATE users SET is_verified = TRUE WHERE id = ?");
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
    /**
     * Create verification code
     */
    public function createVerificationCode($userId) {
        // Generate 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO email_verifications (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $otp, $expiresAt]);
        
        return $otp;
    }

    /**
     * Verify code
     */
    public function verifyCode($userId, $code) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_verifications 
            WHERE user_id = ? AND token = ? AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId, $code]);
        $verification = $stmt->fetch();
        
        if ($verification) {
            // Mark email as verified
            $this->verifyEmail($userId);
            
            // Clean up verifications
            $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Resend verification code
     */
    public function resendVerificationCode($userId) {
        // Check cooldown (1 minute)
        $stmt = $this->pdo->prepare("
            SELECT created_at FROM email_verifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $lastSent = $stmt->fetch();
        
        if ($lastSent && (time() - strtotime($lastSent['created_at'])) < 60) {
            return ['success' => false, 'message' => 'Please wait a minute before resending'];
        }
        
        $otp = $this->createVerificationCode($userId);
        return ['success' => true, 'code' => $otp];
    }
}
