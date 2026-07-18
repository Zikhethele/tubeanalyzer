<?php
/**
 * Pending channel worker — runs via GitHub Actions cron or CLI.
 * Picks up unanalyzed rows, calls the Render API, stores results.
 * All output goes to stdout/stderr so Render and GitHub Actions capture it.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

define('BATCH_SIZE',  5);
define('API_TIMEOUT', 120); // Allow time for Render cold-start (~50s) + processing

$API_BASE = Config::API_BASE_URL . '/channel-info';

// ── Connect ──────────────────────────────────────────────────────────────────
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    fwrite(STDERR, "DB init failed: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Fetch pending ─────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT id, channel_name, email
    FROM   analyses
    WHERE  analyzed = 0
    ORDER  BY created_at ASC
    LIMIT  " . BATCH_SIZE
);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No pending channels.\n";
    exit(0);
}

echo "Processing " . count($rows) . " channel(s)...\n";

// Wake up the Render free-tier service before the batch so cold-start
// doesn't eat the timeout of the first real request.
$wakeUrl = Config::API_BASE_URL . '/docs';
$wch = curl_init($wakeUrl);
curl_setopt_array($wch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 70,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
]);
curl_exec($wch);
curl_close($wch);
echo "API warmed up.\n";

// ── Process each row ─────────────────────────────────────────────────────────
foreach ($rows as $row) {
    $id      = (int) $row['id'];
    $channel = $row['channel_name'];
    $email   = $row['email'];

    $url = $API_BASE
         . '?channel_name=' . urlencode($channel)
         . '&emails='       . urlencode($email);

    echo "[{$id}] Requesting: {$channel}\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => API_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        fwrite(STDERR, "[{$id}] API failed — HTTP {$httpCode} {$curlErr}\n");
        continue;
    }

    json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, "[{$id}] Invalid JSON in response\n");
        continue;
    }

    $db->prepare("
        UPDATE analyses
        SET    data     = :data,
               analyzed = 1
        WHERE  id = :id
    ")->execute([':data' => $response, ':id' => $id]);

    echo "[{$id}] Done — {$channel}\n";
}

echo "Worker finished.\n";
