<?php

// services/EmailService.php - Email Service
// services/EmailService.php - Email Service
class EmailService {
    
    public function sendAnalysisReport($email, $channelName, $data) {
        $subject = "Your YouTube Analytics Report - " . $channelName;
        
        $message = $this->getEmailTemplate($channelName, $data);
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . Config::SITE_NAME . " <" . Config::SUPPORT_EMAIL . ">" . "\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    private function getEmailTemplate($channelName, $data) {
        $siteUrl = Config::SITE_URL;
        
        return 
        
<<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; border: 2px solid #667eea; }
        .stat-label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #667eea; }
        .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Your YouTube Analytics Report</h1>
            <p>Analysis complete for <strong>{$channelName}</strong></p>
        </div>
        
        <div class="content">
            <h2>Channel Overview</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Subscribers</div>
                    <div class="stat-value">1.7M</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Views</div>
                    <div class="stat-value">431M</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Videos</div>
                    <div class="stat-value">3,028</div>
                </div>
            </div>
            
            <p>Your comprehensive channel analysis is ready! Click the button below to view the full interactive report with detailed insights, growth trends, and top-performing videos.</p>
            
            <center>
                <a href="{$siteUrl}/report.php?id=123" class="button">📈 View Full Report</a>
            </center>
            
            <div class="footer">
                <p>© 2026 TubeAnalyzer. All rights reserved.</p>
                <p>Need help? Contact us at {$this->escape(Config::SUPPORT_EMAIL)}</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    private function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
