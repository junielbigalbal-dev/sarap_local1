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
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, link)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $type, $title, $message, $link]);
    }
    
    public function getByUser($userId, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    }
    
    public function markAsRead($id) {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
