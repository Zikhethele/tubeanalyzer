<?php

require_once __DIR__ . '/../models/Analysis.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

class AnalyzeController {
    private $analysisModel;
    private $userModel;

    public function __construct() {
        $this->analysisModel = new Analysis();
        $this->userModel     = new User();
    }

    public function analyze($channelName, $email, $userId = null) {
        try {
            if (empty($channelName) || empty($email)) {
                throw new Exception("Channel name and email are required");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }

            if ($userId !== null) {
                $user  = $this->userModel->getUserById($userId);
                $tier  = $user['subscription_tier'] ?? 'free';
                $limit = $tier === 'pro' ? Config::PRO_DAILY_LIMIT : Config::FREE_DAILY_LIMIT;

                if ($this->userModel->getDailyUsage($userId) >= $limit) {
                    throw new Exception("You've reached your daily limit of $limit analyses. Try again tomorrow.");
                }
            }

            // Save as pending — the GitHub Actions worker picks this up,
            // calls the FastAPI, and sends the email via Resend.
            $this->analysisModel->createAnalysis($userId, $channelName, '[]', $email);

            return [
                'success' => true,
                'message' => 'Your report is on its way — usually arrives within a few minutes.',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
