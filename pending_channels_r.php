<?php
/**
 * Background worker
 * Connects to Afrihost MySQL
 * Calls Render API
 * Updates analysis state
 */

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/pending_channels.log');

// -----------------------------
// AFIHOST DATABASE CONFIG
// -----------------------------
$dbHost = '102.222.124.24';   // e.g. mysql123.afrihost.co.za
$dbName = 'tubeanm7u9e5_tubeanalyz';
$dbUser = 'tubeanm7u9e5_tubeapp';
$dbPass = ',$AETMD69B~}j{{P';
$dbPort = 3306;

// -----------------------------
// API CONFIG
// -----------------------------
$API_BASE = 'https://tube-analyzer.onrender.com/channel-info';

// -----------------------------
// CONNECT DB
// -----------------------------
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    exit(1);
}

// -----------------------------
// FETCH PENDING JOBS
// -----------------------------
$sql = "
    SELECT id, channel_name, email
    FROM channel_requests
    WHERE analyzed = 0
    ORDER BY created_at ASC
    LIMIT 5
";

$stmt = $pdo->query($sql);
$jobs = $stmt->fetchAll();

if (!$jobs) {
    echo "No pending jobs\n";
    exit(0);
}

foreach ($jobs as $job) {
    $id      = (int)$job['id'];
    $channel = urlencode($job['channel_name']);
    $email   = urlencode($job['email']);

    $url = "{$API_BASE}?channel_name={$channel}&emails={$email}";

    echo "Processing ID {$id} ({$job['channel_name']})\n";

    // -----------------------------
    // CURL → RENDER
    // -----------------------------
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log("Render failed [ID {$id}] HTTP {$httpCode} {$err}");
        continue;
    }

    // -----------------------------
    // VALIDATE JSON
    // -----------------------------
    json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON [ID {$id}]");
        continue;
    }

    // -----------------------------
    // UPDATE DB
    // -----------------------------
    $update = $pdo->prepare("
        UPDATE channel_requests
        SET analyzed = 1,
            data = :data
        WHERE id = :id
    ");

    $update->execute([
        ':data' => $response,
        ':id'   => $id
    ]);

    echo "✓ Completed ID {$id}\n";
}

echo "Worker finished\n";
