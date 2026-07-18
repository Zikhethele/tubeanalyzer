<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/mailer_common.php';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$email = trim((string) ($_GET['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));

$valid = false;
if ($email !== '' && $token !== '') {
    try {
        $valid = hash_equals(unsubscribeToken($email), $token);
    } catch (RuntimeException $e) {
        $valid = false;
    }
}

if ($valid) {
    try {
        $db = Database::getInstance()->getConnection();
        ensureMailerTables($db);
        $stmt = $db->prepare("
            INSERT INTO email_suppressions (email, reason)
            VALUES (:email, 'unsubscribe')
            ON CONFLICT (email) DO NOTHING
        ");
        $stmt->execute([':email' => strtolower($email)]);
    } catch (Exception $e) {
        $valid = false; // fall through to the error state rather than claim success
    }
}

$heading = $valid ? "You're unsubscribed" : "That link didn't work";
$message = $valid
    ? "You won't get any more emails from TubeAnalyzer at " . e($email) . "."
    : "This unsubscribe link is invalid or expired. If you'd rather not receive further emails, reply to any of them and let us know.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($heading) ?> — TubeAnalyzer</title>
<meta name="robots" content="noindex">
<style>
    :root {
        --black:  #111;
        --muted:  #6b7280;
        --border: #e5e7eb;
        --bg:     #fafafa;
        --white:  #fff;
        --radius: 6px;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: var(--bg);
        color: var(--black);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 24px;
    }
    .card {
        max-width: 420px;
        width: 100%;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 32px;
    }
    h1 { font-size: 20px; margin: 0 0 12px; }
    p  { font-size: 15px; line-height: 1.6; color: var(--muted); margin: 0 0 24px; }
    a.home {
        display: inline-block;
        color: var(--black);
        font-size: 14px;
        text-decoration: none;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 10px 16px;
    }
    a.home:hover { border-color: var(--black); }
    a.home:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }
</style>
</head>
<body>
    <div class="card">
        <h1><?= e($heading) ?></h1>
        <p><?= $message ?></p>
        <a class="home" href="<?= e(Config::SITE_URL) ?>">Back to TubeAnalyzer</a>
    </div>
</body>
</html>
