<?php
/**
 * Retention campaign — runs via GitHub Actions cron or CLI.
 * Emails everyone who's ever requested a channel report, inviting them to
 * analyze another one. Skips anyone unsubscribed, already sent to, or whose
 * most recent request is too fresh to re-engage yet.
 * All output goes to stdout/stderr so GitHub Actions captures it.
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
require_once __DIR__ . '/mailer_common.php';

define('CAMPAIGN', 'retention');
define('BATCH_SIZE', 25);
define('MIN_DAYS_SINCE_LAST_REQUEST', 14);
define('TEMPLATE', __DIR__ . '/marketing/emails/retention.html');
define('SUBJECT', 'Got another channel on your mind?');

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    fwrite(STDERR, "DB init failed: " . $e->getMessage() . "\n");
    exit(1);
}

ensureMailerTables($db);

$stmt = $db->prepare("
    SELECT LOWER(email) AS email, MAX(created_at) AS last_request
    FROM   analyses
    WHERE  email IS NOT NULL AND email != ''
    GROUP  BY LOWER(email)
    HAVING MAX(created_at) <= NOW() - (:min_days || ' days')::INTERVAL
    ORDER  BY last_request ASC
    LIMIT  " . BATCH_SIZE
);
$stmt->execute([':min_days' => MIN_DAYS_SINCE_LAST_REQUEST]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$candidates) {
    echo "No retention candidates ready.\n";
    exit(0);
}

echo "Considering " . count($candidates) . " past requester(s)...\n";

$sent = 0;
foreach ($candidates as $row) {
    $email = $row['email'];

    if (isSuppressed($db, $email)) {
        echo "[skip] {$email} — unsubscribed\n";
        continue;
    }
    if (alreadySent($db, CAMPAIGN, $email)) {
        echo "[skip] {$email} — already sent\n";
        continue;
    }

    try {
        $html = renderTemplate(TEMPLATE, [
            'unsubscribe_url' => unsubscribeUrl($email),
        ]);
    } catch (RuntimeException $e) {
        fwrite(STDERR, "Template render failed: " . $e->getMessage() . "\n");
        exit(1);
    }

    [$ok, $error] = sendViaResend($email, SUBJECT, $html);

    if ($ok) {
        logSend($db, CAMPAIGN, $email, 'sent');
        echo "[sent] {$email}\n";
        $sent++;
    } else {
        logSend($db, CAMPAIGN, $email, 'failed', $error);
        fwrite(STDERR, "[failed] {$email} — {$error}\n");
    }

    usleep(300000); // be gentle with the API
}

echo "Retention mailer finished — {$sent} sent.\n";
