<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/AdminAuth.php';

adminRequireAuth();

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

    .wrap { max-width: 960px; margin: 0 auto; padding: 48px 24px 96px; }
    .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .admin-links { display: flex; gap: 4px; }
    .admin-links a {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        padding: 8px 12px;
        border-radius: var(--radius);
    }
    .admin-links a.active { color: var(--black); background: var(--white); border: 1px solid var(--border); font-weight: 500; }
    .admin-links a:hover { color: var(--black); }
    .signout {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 8px 12px;
    }
    .signout:hover { border-color: var(--black); color: var(--black); }
    .signout:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

    h1 { font-size: 20px; margin: 0 0 4px; }
    .count { font-size: 13px; color: var(--muted); margin: 0 0 24px; }

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
    <div class="wrap">
        <?php adminNav('analyses'); ?>

        <h1>Recent analyses</h1>
        <p class="count"><?= count($rows) ?> most recent<?= isset($_GET['limit']) ? '' : ' (of up to 50)' ?></p>

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
</body>
</html>
