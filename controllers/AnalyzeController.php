<?php

// require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Analysis.php';
require_once __DIR__ . '/../models/AnalyzerService.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

class AnalyzeController {
    // private $userModel;
    private $analysisModel;
     private $analyzerService;
    private $emailService;
    
    public function __construct() {
        // $this->userModel = new User();
        $this->analysisModel = new Analysis();
        $this->analyzerService = new AnalyzerService();
        $this->emailService = new EmailService();
    }
    
    public function analyze($channelName, $email) {
        try {
            // Validate input
            if (empty($channelName) || empty($email)) {
                throw new Exception("Channel name and email are required");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }
            
            // Get or create user
            // $user = $this->userModel->getUserByEmail($email);
            // if (!$user) {
            //     $userId = $this->userModel->createUser($email);
            // } else {
            //     $userId = $user['id'];
            // }
            
            // Check rate limiting
            // $dailyUsage = $this->userModel->getDailyUsage($userId);
            // $userTier = $user['subscription_tier'] ?? 'free';
            // $limit = $userTier === 'free' ? Config::FREE_DAILY_LIMIT : Config::PRO_DAILY_LIMIT;
            
            // if ($dailyUsage >= $limit) {
            //     throw new Exception("Daily analysis limit reached. Upgrade to Pro for unlimited analyses.");
            // }
            
            // Call API
            $data = $this->analyzerService->analyzeChannel($channelName, $email);
            // file_put_contents('analyze_log.txt', print_r($data, true), FILE_APPEND);
            
            // Save analysis
            $userId = 1; // Temporary user ID for demonstration
            $data = '[]';
            $analysisId = $this->analysisModel->createAnalysis($userId, $channelName, $data, $email);
            
            // Send email (async in production)
            $this->emailService->sendAnalysisReport($email, $channelName, $data);
            
            return [
                'success' => true,
                'message' => 'Analysis complete! Check your email for the full report.',
                'data' => $data,
                'analysisId' => $analysisId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
