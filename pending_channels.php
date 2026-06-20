<?php
/**
 * Process pending channel analysis requests
 * Run via CLI / cron
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

$API_BASE = Config::API_BASE_URL . '/channel-info';

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    error_log("DB init failed: " . $e->getMessage());
    exit(1);
}

/**
 * Fetch pending records
 */
$sql = "
    SELECT id, channel_name, email
    FROM analyses
    WHERE analyzed = 0
    ORDER BY created_at ASC
    LIMIT 5
";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No pending channels\n";
    exit(0);
}

foreach ($rows as $row) {
    $id      = (int)$row['id'];
    $channel = urlencode($row['channel_name']);
    $email   = urlencode($row['email']);

    $url = "{$API_BASE}?channel_name={$channel}&emails={$email}";

    echo "Processing ID {$id} → {$row['channel_name']}\n";

    // ---- CURL ----
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log("Render failed [ID {$id}] HTTP {$httpCode} {$curlErr}");
        continue; // do NOT mark analyzed
    }

    // Validate JSON
    json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON for ID {$id}");
        continue;
    }
    file_put_contents('pending_log.txt', "ID {$id} Response: " . $response . "\n", FILE_APPEND);
    echo "✓ Fetched data for ID {$response}\n";
    // ---- UPDATE DB ----
    $update = $db->prepare("
        UPDATE analyses
        SET data = :data,
            analyzed = 1
        WHERE id = :id
    ");

    $update->execute([
        ':data' => $response,
        ':id'   => $id
    ]);

    echo "✓ Completed ID {$id}\n";
}

echo "Done\n";
