<?php
/**
 * Message Model
 */

class Message {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function send($senderId, $receiverId, $message) {
        $stmt = $this->pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$senderId, $receiverId, $message]);
    }
    
    public function getConversation($user1Id, $user2Id, $limit = 50) {
        // Cast to integer to avoid PDO binding issues
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT m.*, 
                   s.email as sender_email, sp.name as sender_name,
                   r.email as receiver_email, rp.name as receiver_name
            FROM messages m
            INNER JOIN users s ON m.sender_id = s.id
            INNER JOIN users r ON m.receiver_id = r.id
            LEFT JOIN user_profiles sp ON s.id = sp.user_id
            LEFT JOIN user_profiles rp ON r.id = rp.user_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$user1Id, $user2Id, $user2Id, $user1Id]);
        return array_reverse($stmt->fetchAll());
    }
    
    public function getConversations($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.contact_id,
                up.name as contact_name,
                up.business_name,
                u.role as contact_role,
                (SELECT message FROM messages m2
                 WHERE (m2.sender_id = ? AND m2.receiver_id = c.contact_id) 
                    OR (m2.sender_id = c.contact_id AND m2.receiver_id = ?)
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages m3
                 WHERE (m3.sender_id = ? AND m3.receiver_id = c.contact_id) 
                    OR (m3.sender_id = c.contact_id AND m3.receiver_id = ?)
                 ORDER BY m3.created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages m4
                 WHERE m4.sender_id = c.contact_id AND m4.receiver_id = ? AND m4.is_read = FALSE) as unread_count
            FROM (
                SELECT DISTINCT 
                    CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as contact_id
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
            ) c
            INNER JOIN users u ON c.contact_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            ORDER BY last_message_time DESC
        ");
        $stmt->execute([
            $userId, $userId, // Subquery 1
            $userId, $userId, // Subquery 2
            $userId,          // Subquery 3
            $userId,          // Derived table CASE
            $userId, $userId  // Derived table WHERE
        ]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($senderId, $receiverId) {
        $stmt = $this->pdo->prepare("
            UPDATE messages SET is_read = TRUE 
            WHERE sender_id = ? AND receiver_id = ?
        ");
        return $stmt->execute([$senderId, $receiverId]);
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM messages 
            WHERE receiver_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    }
}
