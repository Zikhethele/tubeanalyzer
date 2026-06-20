<?php
// services/AnalyzerService.php - API Service
class AnalyzerService {
    
    public function analyzeChannel($channelName, $email) {
        $url = Config::API_BASE_URL . "/channel-info";
        $url .= "?channel_name=" . urlencode($channelName);
        $url .= "&emails=" . urlencode($email);
        file_put_contents('analyze_log.txt', "Requesting URL: " . $url . "\n", FILE_APPEND);
        file_put_contents('analyze_log.txt', "Channel Name: " . $channelName . ", Email: " . $email . "\n", FILE_APPEND);
        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Accept: application/json'
        // ]);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // $response = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);


        // Using file_get_contents as an alternative to cURL remove after testing
        $httpCode = 200;
        $response = true;
        if ($httpCode !== 200) {
            throw new Exception("API request failed with status code: " . $httpCode);
            ini_set('log_errors', 1);
            ini_set('error_log', __DIR__ . '/errors.log');
            error_log("API request failed with status code: " . $httpCode . " Response: " . $response);
        }
        
        return json_decode($response, true);
    }
}



