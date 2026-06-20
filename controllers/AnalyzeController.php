<?php

require_once __DIR__ . '/../models/Analysis.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

class AnalyzeController {
    private $analysisModel;

    public function __construct() {
        $this->analysisModel = new Analysis();
    }

    public function analyze($channelName, $email) {
        try {
            if (empty($channelName) || empty($email)) {
                throw new Exception("Channel name and email are required");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }

            // Save as pending — the GitHub Actions worker picks this up,
            // calls the FastAPI, and sends the email via Resend.
            $this->analysisModel->createAnalysis(null, $channelName, '[]', $email);

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
