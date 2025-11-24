<?php
/**
 * Notification Model
 */

class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($userId, $type, $title, $message, $link = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, link)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $type, $title, $message, $link]);
        } catch (PDOException $e) {
            // Table doesn't exist yet - fail silently
            error_log("Notification table error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByUser($userId, $limit = 20) {
        try {
            // Cast to integer to avoid PDO binding issues
            $limit = (int)$limit;
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT $limit
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Table doesn't exist yet - return empty array
            error_log("Notification table error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            // Table doesn't exist yet - return 0
            error_log("Notification table error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function markAsRead($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Table doesn't exist yet - fail silently
            error_log("Notification table error: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // Table doesn't exist yet - fail silently
            error_log("Notification table error: " . $e->getMessage());
            return false;
        }
    }
}
