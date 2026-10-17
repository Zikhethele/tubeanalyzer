<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/AdminAuth.php';
require_once __DIR__ . '/mailer_common.php';

adminRequireAuth();

// Matches the cron in .github/workflows/mailers.yaml ("0 8 * * *").
const RUN_HOUR_UTC = 8;
const RECENT_SENDS = 10;

/**
 * Read-only dry run of mailer_prospects.php: the same batch and the same skip
 * checks, in the same order, without sending or logging anything.
 */
function planNextRun(PDO $db): array {
    $failed = $db->prepare("SELECT 1 FROM email_sends WHERE campaign = :c AND email = :e AND status = 'failed'");

    $plan = [];
    foreach (prospectBatch($db) as $row) {
        if (isSuppressed($db, $row['email'])) {
            $row['outcome'] = 'skip';
            $row['note']    = 'Unsubscribed';
        } elseif (empty($row['personal_reason'])) {
            $row['outcome'] = 'skip';
            $row['note']    = 'No personal reason';
        } else {
            $failed->execute([':c' => PROSPECT_CAMPAIGN, ':e' => $row['email']]);
            $row['outcome'] = 'send';
            $row['note']    = $failed->fetchColumn() ? 'Retry — last attempt failed' : 'Will send';
        }
        $plan[] = $row;
    }
    return $plan;
}

function nextRunAt(): DateTimeImmutable {
    $now  = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $next = $now->setTime(RUN_HOUR_UTC, 0);
    return $next <= $now ? $next->modify('+1 day') : $next;
}

$db = Database::getInstance()->getConnection();
ensureMailerTables($db);

$plan      = planNextRun($db);
$sending   = array_values(array_filter($plan, fn($r) => $r['outcome'] === 'send'));
$skipping  = count($plan) - count($sending);

$unsent = (int) $db->query("
    SELECT COUNT(*) FROM prospects p
    WHERE NOT EXISTS (
        SELECT 1 FROM email_sends s
        WHERE s.campaign = '" . PROSPECT_CAMPAIGN . "' AND s.email = LOWER(p.email) AND s.status = 'sent'
    )
")->fetchColumn();
$waiting = max(0, $unsent - count($plan));

$next      = nextRunAt();
$nextLocal = $next->setTimezone(new DateTimeZone('Africa/Johannesburg'));

// Email preview: the chosen row if it will send, otherwise the first one that will.
$previewRow = $sending[0] ?? null;
$requested  = strtolower(trim((string) ($_GET['preview'] ?? '')));
foreach ($sending as $row) {
    if ($row['email'] === $requested) {
        $previewRow = $row;
    }
}

$previewHtml = null;
$unsubNote   = null;
if ($previewRow) {
    try {
        $unsubUrl = unsubscribeUrl($previewRow['email']);
    } catch (RuntimeException $e) {
        $unsubUrl  = '#';
        $unsubNote = 'APP_SECRET is not set on this server, so the preview has no unsubscribe link. The real email gets a signed one.';
    }
    $previewHtml = renderTemplate(PROSPECT_TEMPLATE, [
        'first_name'      => $previewRow['first_name'] ?: 'there',
        'personal_reason' => $previewRow['personal_reason'],
        'unsubscribe_url' => $unsubUrl,
    ]);
}

$stmt = $db->prepare("
    SELECT email, status, error, sent_at FROM email_sends
    WHERE campaign = :c ORDER BY sent_at DESC LIMIT " . RECENT_SENDS
);
$stmt->execute([':c' => PROSPECT_CAMPAIGN]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mailer — Admin — TubeAnalyzer</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root {
        --black:  #111;
        --muted:  #6b7280;
        --border: #e5e7eb;
        --bg:     #fafafa;
        --white:  #fff;
        --error:  #b91c1c;
        --success:#15803d;
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

    .wrap { max-width: 880px; margin: 0 auto; padding: 48px 24px 96px; }
    .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .admin-links { display: flex; gap: 4px; flex-wrap: wrap; }
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
    .signout:focus-visible, a:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

    h1 { font-size: 20px; margin: 0 0 4px; }
    h2 { font-size: 15px; margin: 48px 0 12px; }
    .sub { font-size: 13px; color: var(--muted); margin: 0 0 24px; }
    .hint { font-size: 12px; color: var(--muted); margin: 8px 0 0; max-width: 65ch; }

    .tally { display: flex; gap: 32px; flex-wrap: wrap; margin: 0 0 24px; padding: 0; list-style: none; }
    .tally li { font-size: 13px; color: var(--muted); }
    .tally strong { display: block; font-size: 20px; color: var(--black); font-weight: 600; }

    table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    th, td {
        text-align: left;
        padding: 10px 14px;
        font-size: 13px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }
    th { font-weight: 500; color: var(--muted); background: var(--bg); }
    tr:last-child td { border-bottom: none; }
    td.email { font-weight: 500; word-break: break-all; }
    td.reason { color: var(--muted); max-width: 320px; }
    td.num { color: var(--muted); width: 32px; }
    tr.skip td { color: var(--muted); }
    tr.skip td.email { font-weight: 400; }
    tr.previewing { background: var(--bg); }

    .pill {
        display: inline-block;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid var(--border);
        white-space: nowrap;
    }
    .pill.send { border-color: #bbf7d0; color: var(--success); }
    .pill.skip { border-color: #fecaca; color: var(--error); }
    .preview-link { font-size: 12px; color: var(--black); }

    .meta { font-size: 13px; margin: 0 0 12px; }
    .meta div { padding: 2px 0; }
    .meta span { display: inline-block; width: 64px; color: var(--muted); }
    iframe {
        display: block;
        width: 100%;
        height: 720px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--white);
    }
    .warn { font-size: 12px; color: var(--error); margin: 8px 0 0; }
    .empty { color: var(--muted); font-size: 14px; padding: 24px 0; }
</style>
</head>
<body>
    <div class="wrap">
        <?php adminNav('mailer'); ?>

        <h1>Next prospect run</h1>
        <p class="sub">
            <?= e($next->format('D j M, H:i')) ?> UTC (<?= e($nextLocal->format('H:i')) ?> SAST),
            then daily. GitHub can start scheduled runs a few minutes late.
        </p>

        <ul class="tally">
            <li><strong><?= count($sending) ?></strong> will send</li>
            <li><strong><?= $skipping ?></strong> skipped</li>
            <li><strong><?= $waiting ?></strong> waiting for a later run</li>
        </ul>

        <?php if (empty($plan)): ?>
            <p class="empty">Nothing queued. Every prospect has already been sent to, or the list is empty.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email</th>
                        <th>Greeting</th>
                        <th>Outcome</th>
                        <th>Personal reason</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($plan as $i => $row):
                    $isPreview = $previewRow && $row['email'] === $previewRow['email']; ?>
                    <tr class="<?= $row['outcome'] ?><?= $isPreview ? ' previewing' : '' ?>">
                        <td class="num"><?= $i + 1 ?></td>
                        <td class="email"><?= e($row['email']) ?></td>
                        <td>Hi <?= e($row['first_name'] ?: 'there') ?></td>
                        <td>
                            <span class="pill <?= $row['outcome'] ?>"><?= e($row['note']) ?></span>
                            <?php if ($row['outcome'] === 'send' && !$isPreview): ?>
                                <br><a class="preview-link" href="?preview=<?= urlencode($row['email']) ?>#preview">Preview</a>
                            <?php endif; ?>
                        </td>
                        <td class="reason"><?= $row['personal_reason'] ? e($row['personal_reason']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="hint">
                The run takes the <?= PROSPECT_BATCH_SIZE ?> oldest prospects not yet sent to. Skipped rows
                still take a place in that batch, so they hold back prospects added after them until
                they get a reason or are removed.
            </p>
        <?php endif; ?>

        <h2 id="preview">Email preview</h2>
        <?php if (!$previewRow): ?>
            <p class="empty">No email will go out on the next run.</p>
        <?php else: ?>
            <div class="meta">
                <div><span>To</span><?= e($previewRow['email']) ?></div>
                <div><span>Subject</span><?= e(PROSPECT_SUBJECT) ?></div>
            </div>
            <iframe title="Email to <?= e($previewRow['email']) ?>" sandbox srcdoc="<?= e($previewHtml) ?>"></iframe>
            <?php if ($unsubNote): ?>
                <p class="warn"><?= e($unsubNote) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Recent sends</h2>
        <?php if (empty($recent)): ?>
            <p class="empty">The prospect campaign hasn't sent anything yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Email</th><th>Status</th><th>When (UTC)</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td class="email"><?= e($row['email']) ?></td>
                        <td>
                            <span class="pill <?= $row['status'] === 'sent' ? 'send' : 'skip' ?>"><?= e($row['status']) ?></span>
                            <?php if ($row['error']): ?><div class="hint"><?= e($row['error']) ?></div><?php endif; ?>
                        </td>
                        <td><?= e(substr((string) $row['sent_at'], 0, 16)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
