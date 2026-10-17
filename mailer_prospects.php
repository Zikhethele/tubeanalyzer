<?php
/**
 * Prospect campaign — runs via GitHub Actions cron or CLI.
 * Emails everyone queued in the `prospects` table who hasn't been sent to or
 * unsubscribed yet. The table starts empty — populate it manually (or build
 * a real intake later) before this has anything to send.
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

// Campaign name, batch size, template and subject live in mailer_common.php (PROSPECT_*),
// shared with the admin_mailer.php preview.
define('CAMPAIGN', PROSPECT_CAMPAIGN);
define('TEMPLATE', PROSPECT_TEMPLATE);
define('SUBJECT', PROSPECT_SUBJECT);

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    fwrite(STDERR, "DB init failed: " . $e->getMessage() . "\n");
    exit(1);
}

ensureMailerTables($db);

$candidates = prospectBatch($db);

if (!$candidates) {
    echo "No prospects queued.\n";
    exit(0);
}

echo "Considering " . count($candidates) . " prospect(s)...\n";

$sent = 0;
foreach ($candidates as $row) {
    $email = $row['email'];

    if (isSuppressed($db, $email)) {
        echo "[skip] {$email} — unsubscribed\n";
        continue;
    }

    if (empty($row['personal_reason'])) {
        fwrite(STDERR, "[skip] {$email} — personal_reason is empty; write a real reason before sending\n");
        continue;
    }

    try {
        $html = renderTemplate(TEMPLATE, [
            'first_name'      => $row['first_name'] ?: 'there',
            'personal_reason' => $row['personal_reason'],
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

echo "Prospect mailer finished — {$sent} sent.\n";
