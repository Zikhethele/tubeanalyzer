<?php
// models/User.php - User Model
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createUser($email, $name = null) {
        $query = "INSERT INTO users (email, name, created_at) 
                  VALUES (:email, :name, NOW()) 
                  ON DUPLICATE KEY UPDATE last_seen = NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':email' => $email,
            ':name' => $name
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getUserByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getDailyUsage($userId) {
        $query = "SELECT COUNT(*) as count FROM analyses 
                  WHERE user_id = :user_id 
                  AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
