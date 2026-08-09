<?php

// models/InstagramAudit.php - Instagram Audit Model
class InstagramAudit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createAudit($userId, $username, $data, $botScore, $botLabel) {
        $query = "INSERT INTO instagram_audits (user_id, username, data, bot_score, bot_label, created_at)
                  VALUES (:user_id, :username, :data, :bot_score, :bot_label, NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id'   => $userId,
            ':username'  => $username,
            ':data'      => json_encode($data),
            ':bot_score' => $botScore,
            ':bot_label' => $botLabel,
        ]);

        return $this->db->lastInsertId();
    }

    public function getDailyUsage($userId) {
        $query = "SELECT COUNT(*) as count FROM instagram_audits
                  WHERE user_id = :user_id
                  AND DATE(created_at) = CURRENT_DATE";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
