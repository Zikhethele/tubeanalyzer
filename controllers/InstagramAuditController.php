<?php

require_once __DIR__ . '/../models/InstagramAudit.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

class InstagramAuditController {
    private $auditModel;
    private $userModel;

    public function __construct() {
        $this->auditModel = new InstagramAudit();
        $this->userModel  = new User();
    }

    public function audit($username, $userId) {
        try {
            if (empty($username)) {
                throw new Exception("Instagram username is required");
            }

            $user  = $this->userModel->getUserById($userId);
            $tier  = $user['subscription_tier'] ?? 'free';
            $limit = $tier === 'pro' ? Config::PRO_DAILY_LIMIT : Config::FREE_DAILY_LIMIT;

            if ($this->auditModel->getDailyUsage($userId) >= $limit) {
                throw new Exception("You've reached your daily limit of $limit audits. Try again tomorrow.");
            }

            $profile = $this->fetchProfile($username);

            $this->auditModel->createAudit(
                $userId,
                $profile['username'] ?? $username,
                $profile,
                $profile['bot_score'] ?? null,
                $profile['bot_label'] ?? null
            );

            return [
                'success' => true,
                'data'    => $profile,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function fetchProfile($username) {
        $url = rtrim(Config::INSTAGRAM_API_URL, '/') . '/audit/profile';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['username' => $username]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new Exception("Could not reach the Instagram audit service. Please try again later.");
        }

        $data = json_decode($response, true);

        if ($httpCode === 404) {
            throw new Exception("Instagram account not found.");
        }
        if ($httpCode === 403) {
            throw new Exception("This account's profile is private or unavailable.");
        }
        if ($httpCode !== 200) {
            throw new Exception($data['detail'] ?? "Something went wrong fetching that profile. Please try again.");
        }

        return $data;
    }
}
