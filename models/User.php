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
                  ON CONFLICT (email) DO UPDATE SET last_seen = NOW()";
        
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

    public function emailExists($email) {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email AND password IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function registerUser($name, $email, $password, $phone = null) {
        if ($this->emailExists($email)) {
            return false;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $query = "INSERT INTO users (name, email, password, phone, created_at, consent_at)
                  VALUES (:name, :email, :password, :phone, NOW(), NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => $hashed,
            ':phone'    => $phone ?: null,
        ]);

        return $this->db->lastInsertId();
    }
    
    public function getDailyUsage($userId) {
        $query = "SELECT COUNT(*) as count FROM analyses 
                  WHERE user_id = :user_id 
                  AND DATE(created_at) = CURRENT_DATE";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
