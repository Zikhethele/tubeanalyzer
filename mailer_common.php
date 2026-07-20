<?php
/**
 * Shared helpers for the campaign mailers (mailer_retention.php, mailer_prospects.php)
 * and unsubscribe.php. Not web-accessible on its own — always required by another script.
 */

function ensureMailerTables(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_suppressions (
            id         SERIAL PRIMARY KEY,
            email      VARCHAR(255) NOT NULL UNIQUE,
            reason     VARCHAR(50)  NOT NULL DEFAULT 'unsubscribe',
            created_at TIMESTAMP    NOT NULL DEFAULT NOW()
        )
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_sends (
            id       SERIAL PRIMARY KEY,
            campaign VARCHAR(50)  NOT NULL,
            email    VARCHAR(255) NOT NULL,
            status   VARCHAR(20)  NOT NULL DEFAULT 'sent' CHECK (status IN ('sent', 'failed')),
            error    TEXT,
            sent_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
            UNIQUE (campaign, email)
        )
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS prospects (
            id              SERIAL PRIMARY KEY,
            email           VARCHAR(255) NOT NULL UNIQUE,
            first_name      VARCHAR(255),
            personal_reason TEXT,
            source          VARCHAR(100),
            created_at      TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");
}

function isSuppressed(PDO $db, string $email): bool {
    $stmt = $db->prepare("SELECT 1 FROM email_suppressions WHERE email = :email");
    $stmt->execute([':email' => strtolower(trim($email))]);
    return (bool) $stmt->fetchColumn();
}

function alreadySent(PDO $db, string $campaign, string $email): bool {
    $stmt = $db->prepare("SELECT 1 FROM email_sends WHERE campaign = :campaign AND email = :email AND status = 'sent'");
    $stmt->execute([':campaign' => $campaign, ':email' => strtolower(trim($email))]);
    return (bool) $stmt->fetchColumn();
}

function logSend(PDO $db, string $campaign, string $email, string $status, ?string $error = null): void {
    $stmt = $db->prepare("
        INSERT INTO email_sends (campaign, email, status, error)
        VALUES (:campaign, :email, :status, :error)
        ON CONFLICT (campaign, email) DO UPDATE
        SET status = EXCLUDED.status, error = EXCLUDED.error, sent_at = NOW()
    ");
    $stmt->execute([
        ':campaign' => $campaign,
        ':email'    => strtolower(trim($email)),
        ':status'   => $status,
        ':error'    => $error,
    ]);
}

function unsubscribeToken(string $email): string {
    if (Config::APP_SECRET === '') {
        throw new RuntimeException('APP_SECRET is not configured — cannot sign unsubscribe links.');
    }
    return hash_hmac('sha256', strtolower(trim($email)), Config::APP_SECRET);
}

function unsubscribeUrl(string $email): string {
    return Config::SITE_URL . '/unsubscribe.php?email=' . urlencode($email) . '&token=' . unsubscribeToken($email);
}

function renderTemplate(string $path, array $vars): string {
    $html = file_get_contents($path);
    foreach ($vars as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }
    return $html;
}

/**
 * Sends one email via the Resend API. Returns [success, error-or-null].
 */
function sendViaResend(string $to, string $subject, string $html): array {
    if (Config::RESEND_API_KEY === '') {
        return [false, 'RESEND_API_KEY is not configured'];
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . Config::RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'from'    => Config::RESEND_FROM,
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
        ]),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [false, "cURL error: {$curlErr}"];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return [false, "HTTP {$httpCode}: {$response}"];
    }
    return [true, null];
}
