<?php
// services/AnalyzerService.php - API Service
class AnalyzerService {
    
    public function analyzeChannel($channelName, $email) {
        $url = Config::API_BASE_URL . "/channel-info";
        $url .= "?channel_name=" . urlencode($channelName);
        $url .= "&emails=" . urlencode($email);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API request failed with status code: " . $httpCode);
        }
        
        return json_decode($response, true);
    }
}
