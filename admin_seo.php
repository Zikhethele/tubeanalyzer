<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/AdminAuth.php';

adminRequireAuth();

const HISTORY_DAYS = 14;

// Rows written by seo_daily.php. The table only exists after its first run.
$latest  = [];
$history = [];
$runDate = null;
try {
    $db      = Database::getInstance()->getConnection();
    $runDate = $db->query("SELECT MAX(run_date) FROM seo_checks")->fetchColumn() ?: null;

    if ($runDate) {
        $stmt = $db->prepare("SELECT check_id, label, status, value, detail FROM seo_checks WHERE run_date = :d ORDER BY id");
        $stmt->execute([':d' => $runDate]);
        $latest = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT check_id, run_date, status FROM seo_checks
            WHERE  run_date > (:d::date - :days * INTERVAL '1 day')
            ORDER  BY run_date
        ");
        $stmt->bindValue(':d', $runDate);
        $stmt->bindValue(':days', HISTORY_DAYS, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt as $row) {
            $history[$row['check_id']][$row['run_date']] = $row['status'];
        }
    }
} catch (PDOException $e) {
    // No table yet (seo_daily.php hasn't run) or DB down — render the empty state
    error_log('admin_seo: ' . $e->getMessage());
}

// Failing first, then warnings, so the page reads as a to-do list
$order = ['fail' => 0, 'warn' => 1, 'skip' => 2, 'pass' => 3];
usort($latest, fn($a, $b) => $order[$a['status']] <=> $order[$b['status']]);
$counts = array_count_values(array_column($latest, 'status'));

$days = [];
if ($runDate) {
    for ($i = HISTORY_DAYS - 1; $i >= 0; $i--) {
        $days[] = date('Y-m-d', strtotime("{$runDate} -{$i} days"));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SEO checks — Admin</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root {
        --black:  #111;
        --muted:  #6b7280;
        --border: #e5e7eb;
        --bg:     #fafafa;
        --white:  #fff;
        --pass:   #15803d;
        --warn:   #b45309;
        --fail:   #b91c1c;
        --skip:   #d1d5db;
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
    .admin-links a:focus-visible, .signout:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }
    .signout {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 8px 12px;
    }
    .signout:hover { border-color: var(--black); color: var(--black); }

    h1 { font-size: 20px; margin: 0 0 4px; }
    .count { font-size: 13px; color: var(--muted); margin: 0 0 24px; }

    .table-scroll { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); }
    th, td {
        text-align: left;
        padding: 12px 16px;
        font-size: 13px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }
    th { font-weight: 500; color: var(--muted); background: var(--bg); }
    tr:last-child td { border-bottom: none; }
    td.label { font-weight: 500; max-width: 280px; }
    td.detail { color: var(--muted); max-width: 360px; overflow-wrap: anywhere; }

    .badge {
        display: inline-block;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid currentColor;
    }
    .badge.pass { color: var(--pass); }
    .badge.warn { color: var(--warn); }
    .badge.fail { color: var(--fail); }
    .badge.skip { color: var(--muted); }

    .history { display: flex; gap: 2px; white-space: nowrap; }
    .day { width: 10px; height: 16px; border-radius: 2px; background: var(--border); }
    .day.pass { background: var(--pass); }
    .day.warn { background: var(--warn); }
    .day.fail { background: var(--fail); }
    .day.skip { background: var(--skip); }

    .empty { color: var(--muted); font-size: 14px; padding: 32px 0; max-width: 60ch; }
    code { font-size: 12px; }
    .sr-only {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
    }
</style>
</head>
<body>
    <div class="wrap">
        <?php adminNav('seo'); ?>

        <h1>SEO checks</h1>

        <?php if (!$latest): ?>
            <p class="empty">No results yet. They appear after the first run of <code>seo_daily.php</code> (GitHub Actions, daily at 06:00 UTC).</p>
        <?php else: ?>
            <p class="count">
                Run <?= e((string) $runDate) ?> ·
                <?= (int) ($counts['fail'] ?? 0) ?> failing,
                <?= (int) ($counts['warn'] ?? 0) ?> warning,
                <?= (int) ($counts['pass'] ?? 0) ?> passing
            </p>

            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Last <?= HISTORY_DAYS ?> days</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($latest as $row): ?>
                    <tr>
                        <td class="label"><?= e((string) $row['label']) ?></td>
                        <td><span class="badge <?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
                        <td class="detail"><?= e(trim($row['value'] . ' ' . $row['detail'])) ?></td>
                        <td>
                            <div class="history">
                            <?php foreach ($days as $day):
                                $s = $history[$row['check_id']][$day] ?? null; ?>
                                <span class="day <?= e((string) $s) ?>" title="<?= e($day . ': ' . ($s ?? 'no run')) ?>"></span>
                            <?php endforeach; ?>
                            </div>
                            <span class="sr-only">
                                <?= e(implode(', ', array_map(fn($d) => $d . ' ' . ($history[$row['check_id']][$d] ?? 'no run'), $days))) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
