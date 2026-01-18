<?php
// services/AnalyzerService.php - API Service
class AnalyzerService {
    
    public function analyzeChannel($channelName, $email) {
        // Enable error logging
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . 'api_errors.log');
        
        // Build URL
        $url = Config::API_BASE_URL . "/channel-info";
        $url .= "?channel_name=" . urlencode($channelName);
        $url .= "&emails=" . urlencode($email);
        
        // Log the request
        error_log("=== API REQUEST ===");
        error_log("URL: " . $url);
        error_log("Channel: " . $channelName);
        error_log("Email: " . $email);
        error_log("Timestamp: " . date('Y-m-d H:i:s'));
        
        // Initialize cURL
        $ch = curl_init($url);
        
        if ($ch === false) {
            error_log("ERROR: Failed to initialize cURL");
            throw new Exception("Failed to initialize API request");
        }
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: TubeAnalyzer/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        
        // Get request info
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $requestInfo = curl_getinfo($ch);
        
        // Close cURL
        curl_close($ch);
        
        // Log response details
        error_log("=== API RESPONSE ===");
        error_log("HTTP Code: " . $httpCode);
        error_log("cURL Error: " . ($curlError ?: 'None'));
        error_log("cURL Errno: " . $curlErrno);
        error_log("Total Time: " . $requestInfo['total_time'] . "s");
        error_log("Response Length: " . strlen($response));
        error_log("Response Preview: " . substr($response, 0, 500));
        
        // Check for cURL errors
        if ($curlErrno !== 0) {
            $errorMsg = "cURL Error ({$curlErrno}): {$curlError}";
            error_log("ERROR: " . $errorMsg);
            throw new Exception("API request failed: " . $curlError);
        }
        
        // Check for empty response
        if (empty($response)) {
            error_log("ERROR: Empty response from API");
            throw new Exception("API returned empty response");
        }
        
        // Check HTTP status code
        if ($httpCode !== 200) {
            $errorMsg = "API request failed with status code: {$httpCode}";
            error_log("ERROR: " . $errorMsg);
            error_log("Response Body: " . $response);
            
            // Try to parse error message from response
            $errorData = json_decode($response, true);
            if ($errorData && isset($errorData['message'])) {
                throw new Exception("API Error: " . $errorData['message']);
            }
            
            throw new Exception($errorMsg . " - Response: " . substr($response, 0, 200));
        }
        
        // Decode JSON response
        $data = json_decode($response, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ERROR: JSON decode failed - " . json_last_error_msg());
            error_log("Raw response: " . $response);
            throw new Exception("Failed to parse API response: " . json_last_error_msg());
        }
        
        // Validate response structure
        if (!is_array($data)) {
            error_log("ERROR: Invalid response structure");
            throw new Exception("API returned invalid data format");
        }
        
        error_log("SUCCESS: API request completed successfully");
        error_log("Data keys: " . implode(', ', array_keys($data)));
        
        return $data;
    }
    
    /**
     * Test the API connection
     */
    public function testConnection() {
        $url = Config::API_BASE_URL . "/health"; // Assuming you have a health endpoint
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        return [
            'success' => $httpCode === 200 || $httpCode === 404, // 404 is fine, means server is reachable
            'http_code' => $httpCode,
            'error' => $curlError,
            'api_url' => Config::API_BASE_URL
        ];
    }
}



