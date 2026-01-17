<?php

// models/Analysis.php - Analysis Model
class Analysis {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createAnalysis($userId, $channelName, $data) {
        $query = "INSERT INTO analyses (user_id, channel_name, data, created_at) 
                  VALUES (:user_id, :channel_name, :data, NOW())";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':channel_name' => $channelName,
            ':data' => json_encode($data)
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getAnalysis($id) {
        $query = "SELECT a.*, u.email FROM analyses a 
                  JOIN users u ON a.user_id = u.id 
                  WHERE a.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUserAnalyses($userId, $limit = 10) {
        $query = "SELECT * FROM analyses 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
