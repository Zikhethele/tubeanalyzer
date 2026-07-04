<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

// ── Slug ─────────────────────────────────────────────────────────────────────
$rawSlug = trim($_GET['slug'] ?? '');
if ($rawSlug === '') {
    header('Location: /');
    exit;
}

$slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $rawSlug));
$slug = trim($slug, '-');

if ($slug === '') {
    header('Location: /');
    exit;
}

// ── DB lookup ────────────────────────────────────────────────────────────────
$channelData  = null;
$channelInput = $rawSlug;

try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT data, channel_name
        FROM   analyses
        WHERE  LOWER(REGEXP_REPLACE(channel_name, '[^a-zA-Z0-9]+', '-', 'g')) = :slug
          AND  analyzed = 1
        ORDER  BY created_at DESC
        LIMIT  1
    ");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $channelData  = json_decode($row['data'], true) ?? null;
        $channelInput = $row['channel_name'];
    }
} catch (Exception $e) {
    // Degrade gracefully — DB unavailable
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmtNum(string|int $n): string {
    $n = (int) $n;
    if ($n >= 1_000_000_000) return number_format($n / 1_000_000_000, 1) . 'B';
    if ($n >= 1_000_000)     return number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)         return number_format($n / 1_000, 1) . 'K';
    return number_format($n);
}

function engRate(array $v): float {
    $views = max(1, (int) $v['views']);
    return round(((int) $v['likes'] + (int) $v['comments']) / $views * 100, 1);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Derived stats ─────────────────────────────────────────────────────────────
$channelDisplayName = $channelData['channel_name'] ?? ucwords(str_replace('-', ' ', $slug));
$subscribers        = $channelData['subscribers']  ?? null;
$totalViews         = $channelData['views']        ?? null;
$totalVideos        = $channelData['totalVideos']  ?? null;
$channelId          = $channelData['channel_id']   ?? null;
$videos             = $channelData['videos']        ?? [];

if (!empty($videos)) {
    usort($videos, fn($a, $b) => (int) $b['views'] - (int) $a['views']);
}

$avgEng = null;
if (!empty($videos)) {
    $avgEng = round(array_sum(array_map('engRate', $videos)) / count($videos), 1);
}

// ── Page meta ────────────────────────────────────────────────────────────────
$pageTitle   = $channelDisplayName . ' YouTube Analytics — TubeAnalyzer';
$canonicalUrl = 'https://yessherlock.com/channel/' . rawurlencode($slug);

if ($channelData) {
    $pageDesc = "{$channelDisplayName} has " . fmtNum((string)($subscribers ?? '0'))
              . " subscribers and " . fmtNum((string)($totalViews ?? '0'))
              . " total views. Free YouTube channel analytics — video performance, engagement rates, and growth trends.";
} else {
    $pageDesc = "Free YouTube channel analytics for {$channelDisplayName}. Subscribers, video performance, and engagement rates delivered to your inbox.";
}

// ── JSON-LD ──────────────────────────────────────────────────────────────────
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'TubeAnalyzer', 'item' => 'https://yessherlock.com'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $channelDisplayName, 'item' => $canonicalUrl],
    ],
];

$pageSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => $pageTitle,
    'description' => $pageDesc,
    'url'         => $canonicalUrl,
    'isPartOf'    => ['@type' => 'WebSite', 'name' => 'TubeAnalyzer', 'url' => 'https://yessherlock.com'],
];

if ($channelId) {
    $pageSchema['about'] = [
        '@type'  => 'Person',
        'name'   => $channelDisplayName,
        'sameAs' => 'https://www.youtube.com/channel/' . $channelId,
    ];
}

$videoSchemas = [];
foreach (array_slice($videos, 0, 5) as $v) {
    $videoSchemas[] = [
        '@context'   => 'https://schema.org',
        '@type'      => 'VideoObject',
        'name'       => $v['title'],
        'url'        => 'https://www.youtube.com/watch?v=' . $v['video_id'],
        'uploadDate' => $v['published_at'],
        'interactionStatistic' => [
            ['@type' => 'InteractionCounter', 'interactionType' => 'WatchAction', 'userInteractionCount' => (int) $v['views']],
            ['@type' => 'InteractionCounter', 'interactionType' => 'LikeAction',  'userInteractionCount' => (int) $v['likes']],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta name="theme-color" content="#111111">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">

    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="TubeAnalyzer">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDesc) ?>">

    <script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php foreach ($videoSchemas as $vs): ?>
    <script type="application/ld+json"><?= json_encode($vs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endforeach; ?>

    <style>
        :root {
            --black:  #111;
            --muted:  #6b7280;
            --border: #e5e7eb;
            --bg:     #fafafa;
            --white:  #fff;
            --radius: 6px;
            --max-w:  1100px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--black);
            min-height: 100vh;
            font-size: 1rem;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        /* ── Nav ── */
        .nav { background: var(--white); border-bottom: 1px solid var(--border); }
        .nav-inner {
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 0 48px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-wordmark {
            font-size: 0.9375rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: var(--black);
            text-decoration: none;
        }
        .btn-nav {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--black);
            background: none;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 7px 16px;
            text-decoration: none;
            transition: border-color 0.15s;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
        }
        .btn-nav:hover        { border-color: var(--black); }
        .btn-nav:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

        /* ── Main ── */
        main {
            flex: 1;
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 64px 48px 96px;
            width: 100%;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            font-size: 0.8125rem;
            color: var(--muted);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .breadcrumb a { color: var(--muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--black); }

        /* ── Hero ── */
        .eyebrow {
            font-size: 0.8125rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }
        h1 {
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: -0.025em;
            margin-bottom: 40px;
        }

        /* ── Stats ── */
        .stats {
            display: flex;
            gap: 48px;
            margin-bottom: 64px;
            flex-wrap: wrap;
        }
        .stat-value {
            display: block;
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 0.8125rem;
            color: var(--muted);
        }

        /* ── Video list ── */
        .section-label {
            font-size: 0.8125rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 0;
        }
        .list-header {
            display: grid;
            grid-template-columns: 28px 1fr 80px 72px;
            gap: 16px;
            padding: 10px 0;
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }
        .list-header span:nth-child(3),
        .list-header span:nth-child(4) { text-align: right; }

        .video-list { margin-bottom: 64px; }
        .video-row {
            display: grid;
            grid-template-columns: 28px 1fr 80px 72px;
            align-items: center;
            gap: 16px;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }
        .video-row:last-child { border-bottom: none; }

        .video-num  { color: var(--muted); font-size: 0.8125rem; font-variant-numeric: tabular-nums; }
        .video-title {
            color: var(--black);
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.9375rem;
        }
        .video-title:hover { text-decoration: underline; text-underline-offset: 3px; }
        .video-views {
            color: var(--muted);
            font-size: 0.875rem;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .video-eng {
            font-size: 0.875rem;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .video-eng.high { color: #166534; }
        .video-eng.low  { color: var(--muted); }

        /* ── CTA ── */
        .cta {
            padding-top: 48px;
            border-top: 1px solid var(--border);
            max-width: 480px;
        }
        .cta-heading {
            font-size: 1.125rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin-bottom: 8px;
        }
        .cta-sub {
            font-size: 0.9375rem;
            color: var(--muted);
            margin-bottom: 24px;
            max-width: 44ch;
            line-height: 1.65;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            padding: 11px 20px;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--white);
            background: var(--black);
            border-radius: var(--radius);
            text-decoration: none;
            transition: opacity 0.15s;
            min-height: 44px;
        }
        .btn-primary:hover        { opacity: 0.82; }
        .btn-primary:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

        .hint {
            margin-top: 12px;
            font-size: 0.8125rem;
            color: var(--muted);
        }

        /* ── Footer ── */
        footer { border-top: 1px solid var(--border); }
        .footer-inner {
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 20px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        .footer-inner a { color: var(--muted); text-decoration: none; }
        .footer-inner a:hover { color: var(--black); }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .nav-inner, .footer-inner { padding-left: 24px; padding-right: 24px; }
            main   { padding: 40px 24px 64px; }
            h1     { font-size: 1.75rem; }
            .stats { gap: 28px; }
            .stat-value { font-size: 1.5rem; }
            .video-row, .list-header { grid-template-columns: 24px 1fr 72px; }
            .video-eng, .list-header span:nth-child(4) { display: none; }
        }
    </style>
</head>
<body>

<nav class="nav" role="banner">
    <div class="nav-inner">
        <a href="/" class="nav-wordmark">TubeAnalyzer</a>
        <a href="/" class="btn-nav">Analyze a channel</a>
    </div>
</nav>

<main>
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">TubeAnalyzer</a>
        <span aria-hidden="true">/</span>
        <span><?= e($channelDisplayName) ?></span>
    </nav>

    <?php if ($channelData): ?>

        <p class="eyebrow">YouTube Analytics</p>
        <h1><?= e($channelDisplayName) ?></h1>

        <div class="stats" aria-label="Channel statistics">
            <?php if ($subscribers !== null): ?>
            <div>
                <span class="stat-value"><?= fmtNum($subscribers) ?></span>
                <span class="stat-label">Subscribers</span>
            </div>
            <?php endif; ?>
            <?php if ($totalViews !== null): ?>
            <div>
                <span class="stat-value"><?= fmtNum($totalViews) ?></span>
                <span class="stat-label">Total views</span>
            </div>
            <?php endif; ?>
            <?php if ($totalVideos !== null): ?>
            <div>
                <span class="stat-value"><?= number_format((int) $totalVideos) ?></span>
                <span class="stat-label">Videos published</span>
            </div>
            <?php endif; ?>
            <?php if ($avgEng !== null): ?>
            <div>
                <span class="stat-value"><?= $avgEng ?>%</span>
                <span class="stat-label">Avg engagement</span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($videos)): ?>
        <section aria-labelledby="videos-label">
            <p class="section-label" id="videos-label">Top videos by views</p>
            <div class="list-header" aria-hidden="true">
                <span>#</span>
                <span>Title</span>
                <span>Views</span>
                <span>Engagement</span>
            </div>
            <div class="video-list">
                <?php foreach (array_slice($videos, 0, 15) as $i => $v):
                    $er  = engRate($v);
                    $cls = $er >= 5 ? 'high' : ($er < 2 ? 'low' : '');
                ?>
                <div class="video-row">
                    <span class="video-num"><?= $i + 1 ?></span>
                    <a href="https://www.youtube.com/watch?v=<?= e($v['video_id']) ?>"
                       class="video-title"
                       target="_blank"
                       rel="noopener noreferrer"
                       title="<?= e($v['title']) ?>"><?= e($v['title']) ?></a>
                    <span class="video-views"><?= fmtNum($v['views']) ?></span>
                    <span class="video-eng <?= $cls ?>"><?= $er ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <div class="cta">
            <p class="cta-heading">Get the full report</p>
            <p class="cta-sub">Growth trends, upload frequency, and a full interactive report — delivered free to your inbox.</p>
            <a href="/?channel=<?= urlencode($channelInput) ?>" class="btn-primary">Analyze <?= e($channelDisplayName) ?></a>
            <p class="hint">No account needed &middot; Results in a few minutes</p>
        </div>

    <?php else: ?>

        <p class="eyebrow">YouTube Analytics</p>
        <h1><?= e($channelDisplayName) ?></h1>
        <p style="color:var(--muted);max-width:44ch;line-height:1.65;margin-bottom:32px;">
            We don't have analytics data for this channel yet. Run a free analysis and we'll process it within a few minutes.
        </p>
        <a href="/?channel=<?= urlencode($channelInput) ?>" class="btn-primary">Analyze <?= e($channelDisplayName) ?></a>
        <p class="hint">No account needed &middot; Results in a few minutes</p>

    <?php endif; ?>
</main>

<footer>
    <div class="footer-inner">
        <span>&copy; 2026 TubeAnalyzer</span>
        <a href="mailto:support@yessherlock.com">support@yessherlock.com</a>
    </div>
</footer>

</body>
</html>
