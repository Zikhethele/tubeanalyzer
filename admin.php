<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

session_start();

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = (string) ($_POST['password'] ?? '');
    $expected = (string) Config::ADMIN_PASSWORD;

    // Throttle guesses regardless of outcome so timing can't reveal validity,
    // and cap attempts per session to blunt brute-forcing.
    $_SESSION['admin_attempts'] = ($_SESSION['admin_attempts'] ?? 0) + 1;
    usleep(300000);

    if ($_SESSION['admin_attempts'] > 20) {
        $error = 'Too many attempts. Try again later.';
    } elseif ($expected !== '' && hash_equals($expected, $submitted)) {
        session_regenerate_id(true);
        $_SESSION['admin_authed'] = true;
        unset($_SESSION['admin_attempts']);
        header('Location: /admin.php');
        exit;
    } else {
        $error = 'Incorrect password.';
    }
}

$authed = $_SESSION['admin_authed'] ?? false;

$rows = [];
if ($authed) {
    $limit = (int) ($_GET['limit'] ?? 50);
    $limit = max(10, min($limit, 500));

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare(
        "SELECT id, user_id, channel_name, email, analyzed, created_at
         FROM analyses
         ORDER BY created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — TubeAnalyzer</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root {
        --black:  #111;
        --muted:  #6b7280;
        --border: #e5e7eb;
        --bg:     #fafafa;
        --white:  #fff;
        --accent: #2563eb;
        --error:  #b91c1c;
        --radius: 6px;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: var(--bg);
        color: var(--black);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100vh;
    }

    /* Login state */
    .login-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 24px;
    }
    .card {
        max-width: 360px;
        width: 100%;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 32px;
    }
    .card h1 { font-size: 18px; margin: 0 0 20px; }
    .field { margin-bottom: 16px; }
    label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
    input[type="password"] {
        width: 100%;
        font-size: 15px;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--white);
        color: var(--black);
    }
    input[type="password"]:focus-visible {
        outline: 2px solid var(--black);
        outline-offset: 1px;
    }
    button {
        width: 100%;
        font-size: 14px;
        font-weight: 500;
        padding: 10px 16px;
        border: 1px solid var(--black);
        border-radius: var(--radius);
        background: var(--black);
        color: var(--white);
        cursor: pointer;
    }
    button:hover { background: #2a2a2a; }
    button:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }
    .error {
        font-size: 13px;
        color: var(--error);
        margin: 0 0 16px;
    }

    /* Dashboard state */
    .wrap { max-width: 960px; margin: 0 auto; padding: 48px 24px 96px; }
    .top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .top h1 { font-size: 20px; margin: 0; }
    .top .count { font-size: 13px; color: var(--muted); margin-top: 4px; }
    .top a {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 8px 12px;
    }
    .top a:hover { border-color: var(--black); color: var(--black); }
    .top a:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

    table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    th, td {
        text-align: left;
        padding: 10px 14px;
        font-size: 13px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }
    th {
        font-weight: 500;
        color: var(--muted);
        background: var(--bg);
    }
    tr:last-child td { border-bottom: none; }
    td.channel { font-weight: 500; }
    td.email, td.created { color: var(--muted); white-space: nowrap; }
    .badge {
        display: inline-block;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--muted);
    }
    .badge.done { color: #15803d; border-color: #bbf7d0; }
    .empty { color: var(--muted); font-size: 14px; padding: 32px 0; }
</style>
</head>
<body>
<?php if (!$authed): ?>
    <div class="login-wrap">
        <div class="card">
            <h1>Admin</h1>
            <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
            <form method="post" action="/admin.php">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autofocus required>
                </div>
                <button type="submit">Sign in</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="wrap">
        <div class="top">
            <div>
                <h1>Recent analyses</h1>
                <div class="count"><?= count($rows) ?> most recent<?= isset($_GET['limit']) ? '' : ' (of up to 50)' ?></div>
            </div>
            <a href="/admin.php?logout=1">Sign out</a>
        </div>

        <?php if (empty($rows)): ?>
            <p class="empty">No analyses yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Channel</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['id']) ?></td>
                        <td class="channel"><?= e((string) $row['channel_name']) ?></td>
                        <td class="email"><?= e((string) ($row['email'] ?? '—')) ?></td>
                        <td>
                            <?php if ((int) $row['analyzed'] === 1): ?>
                                <span class="badge done">analyzed</span>
                            <?php else: ?>
                                <span class="badge">pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="created"><?= e((string) $row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
</body>
</html>
