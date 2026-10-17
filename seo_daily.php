<?php
/**
 * Daily SEO check — runs via GitHub Actions cron or CLI.
 * Fetches the live site (GET/HEAD only, never writes to it), records one row per
 * check in `seo_checks`, and emails a report when something fails or changes.
 * All output goes to stdout/stderr so GitHub Actions captures it.
 *
 *   php seo_daily.php                       full run: checks, DB, email
 *   php seo_daily.php --no-db --no-email    checks only (local runs, the SEO agent)
 *   php seo_daily.php --json                print results as JSON instead of text
 *   php seo_daily.php --base-url=http://localhost:8080   check another host
 *   php seo_daily.php --pagespeed           force the PageSpeed check (normally Mondays)
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

define('SAMPLE_SIZE', 10);        // channel pages inspected per day, rotating through the sitemap
define('TTFB_WARN_MS', 1000);
define('TITLE_MAX_CHARS', 60);
define('USER_AGENT', 'SherlockSEOCheck/1.0 (+https://yessherlock.com)');

$opts      = getopt('', ['base-url:', 'no-db', 'no-email', 'json', 'pagespeed']);
$base      = rtrim($opts['base-url'] ?? Config::SITE_URL, '/');
$useDb     = !isset($opts['no-db']);
$sendEmail = !isset($opts['no-email']);
$asJson    = isset($opts['json']);
$isProd    = $base === Config::SITE_URL;
$today     = gmdate('Y-m-d');

// ── HTTP ─────────────────────────────────────────────────────────────────────

/**
 * One request, redirects not followed. Returns status, lowercased headers
 * (name => list of values), body, time to first byte in ms, and any cURL error.
 * Retried once when no response arrives, so a network blip isn't reported as a failure.
 */
function fetch(string $url, bool $headOnly = false): array {
    $res = fetchOnce($url, $headOnly);
    return $res['status'] === 0 ? fetchOnce($url, $headOnly) : $res;
}

function fetchOnce(string $url, bool $headOnly): array {
    $headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_NOBODY         => $headOnly,
        CURLOPT_USERAGENT      => USER_AGENT,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$headers) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))][] = trim($value);
            }
            return strlen($line);
        },
    ]);
    $body  = curl_exec($ch);
    $res   = [
        'status'  => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'headers' => $headers,
        'body'    => is_string($body) ? $body : '',
        'ttfb_ms' => (int) round(curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME) * 1000),
        'error'   => curl_error($ch),
    ];
    curl_close($ch);
    return $res;
}

function header1(array $res, string $name): ?string {
    return $res['headers'][$name][0] ?? null;
}

/** Resolves a Location header against the URL it came from. */
function absoluteLocation(string $requestUrl, ?string $location): ?string {
    if ($location === null || $location === '') return null;
    if (preg_match('#^https?://#i', $location)) return $location;
    $p = parse_url($requestUrl);
    $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    return $origin . (str_starts_with($location, '/') ? $location : '/' . $location);
}

function describe(array $res): string {
    return $res['error'] !== '' ? $res['error'] : 'HTTP ' . $res['status'];
}

// ── Results ──────────────────────────────────────────────────────────────────

$results = [];

/** status: pass | warn | fail | skip */
function record(string $id, string $label, string $status, string $value = '', string $detail = ''): void {
    global $results;
    $results[$id] = compact('id', 'label', 'status', 'value', 'detail');
}

// ── HTML inspection ──────────────────────────────────────────────────────────

/** SEO-relevant parts of a page's HTML. */
function inspectPage(string $html): array {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);

    $values = fn(string $q, string $attr) => array_map(
        fn(DOMElement $n) => $n->getAttribute($attr),
        iterator_to_array($xp->query($q))
    );

    $titleNode = $xp->query('//title')->item(0);
    $jsonLd    = array_map(fn(DOMNode $n) => $n->textContent, iterator_to_array($xp->query('//script[@type="application/ld+json"]')));

    // Visible text only: what a visitor would read, without CSS widths or script values
    foreach (iterator_to_array($xp->query('//script|//style')) as $n) {
        $n->parentNode->removeChild($n);
    }
    $bodyNode = $xp->query('//body')->item(0);

    return [
        'title'        => $titleNode ? trim($titleNode->textContent) : '',
        'h1_count'     => $xp->query('//h1')->length,
        'canonicals'   => $values('//link[@rel="canonical"]', 'href'),
        'descriptions' => $values('//meta[@name="description"]', 'content'),
        'robots'       => strtolower(implode(',', $values('//meta[@name="robots"]', 'content'))),
        'json_ld'      => $jsonLd,
        'text'         => $bodyNode ? preg_replace('/\s+/', ' ', $bodyNode->textContent) : '',
    ];
}

// ── 0. Warm-up ───────────────────────────────────────────────────────────────
// The first request after an idle spell can be slow; don't let it skew timings.
fetch($base . '/');

// ── A. Site health ───────────────────────────────────────────────────────────

$home    = fetch($base . '/');
$robots  = fetch($base . '/robots.txt');
$sitemap = fetch($base . '/sitemap.xml');
$terms   = fetch($base . '/terms');

foreach (['up_home' => ['Homepage', $home], 'up_terms' => ['/terms', $terms],
          'up_sitemap' => ['/sitemap.xml', $sitemap], 'up_robots' => ['/robots.txt', $robots]] as $id => [$label, $res]) {
    record($id, "{$label} returns 200", $res['status'] === 200 ? 'pass' : 'fail', (string) $res['status'],
        $res['status'] === 200 ? '' : describe($res));
}

// Channel slugs from the sitemap, used by several checks below
$channelUrls = [];
$sitemapXml  = null;
if ($sitemap['status'] === 200) {
    libxml_use_internal_errors(true);
    $sitemapXml = simplexml_load_string($sitemap['body']);
    libxml_clear_errors();
    if ($sitemapXml !== false) {
        foreach ($sitemapXml->url as $u) {
            $loc = (string) $u->loc;
            if (str_contains($loc, '/channel/')) $channelUrls[] = $loc;
        }
    }
}
// Checks run against $base, sitemap URLs always name production
$toBase = fn(string $url) => $base . (parse_url($url, PHP_URL_PATH) ?? '/');

$redirects = [
    'redirect_index' => ['/index.php → /', $base . '/index.php', $base . '/'],
    'redirect_terms' => ['/terms.php → /terms', $base . '/terms.php', $base . '/terms'],
];
if ($isProd) {
    $redirects['redirect_https'] = ['http → https', 'http://yessherlock.com/', 'https://yessherlock.com/'];
    $redirects['redirect_www']   = ['www → apex', 'https://www.yessherlock.com/', 'https://yessherlock.com/'];
}
if ($channelUrls) {
    $canonical = $toBase($channelUrls[0]);
    $path      = parse_url($canonical, PHP_URL_PATH);
    $upper     = $base . '/channel/' . strtoupper(substr($path, strlen('/channel/')));
    if ($upper !== $canonical) {
        $redirects['redirect_channel_case'] = ['Upper-case channel slug → canonical', $upper, $canonical];
    }
}
foreach ($redirects as $id => [$label, $from, $to]) {
    $res = fetch($from, true);
    $loc = absoluteLocation($from, header1($res, 'location'));
    $ok  = in_array($res['status'], [301, 308], true) && $loc === $to;
    record($id, $label, $ok ? 'pass' : 'fail', $res['status'] . ($loc ? " → {$loc}" : ''),
        $ok ? '' : "Expected 301 → {$to}");
}

$fake = fetch($base . '/channel/sherlock-seo-check-not-a-channel-' . str_replace('-', '', $today));
$fakePage = inspectPage($fake['body']);
$ok = $fake['status'] === 404 && str_contains($fakePage['robots'], 'noindex');
record('soft_404', 'Unknown channel returns 404 + noindex', $ok ? 'pass' : 'fail',
    $fake['status'] . ' ' . ($fakePage['robots'] ?: 'no robots meta'),
    $ok ? '' : 'Unknown channel pages must not be indexable');

$report = fetch($base . '/reports/index.html', true);
$xRobots = strtolower((string) header1($report, 'x-robots-tag'));
if ($report['status'] === 404) {
    record('reports_noindex', '/reports/ sends X-Robots-Tag: noindex', 'skip', '404', 'No report file to test');
} else {
    $ok = str_contains($xRobots, 'noindex');
    record('reports_noindex', '/reports/ sends X-Robots-Tag: noindex', $ok ? 'pass' : 'fail',
        $xRobots ?: 'header missing', $ok ? '' : 'Report dumps are indexable');
}

$cookie = $home['headers']['set-cookie'] ?? [];
record('no_anon_cookie', 'Homepage sets no cookie for anonymous visitors', $cookie ? 'fail' : 'pass',
    $cookie ? 'Set-Cookie sent' : 'none', $cookie ? 'Stops caching; see index.php session handling' : '');

if ($robots['status'] === 200) {
    $problems = [];
    if (preg_match('/^\s*Disallow:\s*\/\s*$/mi', $robots['body'])) $problems[] = 'contains "Disallow: /"';
    if (!str_contains($robots['body'], 'Sitemap: ' . Config::SITE_URL . '/sitemap.xml')) $problems[] = 'sitemap line missing';
    record('robots_txt', 'robots.txt allows the site and names the sitemap', $problems ? 'fail' : 'pass', '', implode('; ', $problems));
}

// ── B. Sitemap and pages ─────────────────────────────────────────────────────

if ($sitemap['status'] === 200) {
    if (!$sitemapXml) {
        record('sitemap_valid', 'Sitemap is valid XML without duplicates', 'fail', '', 'Not valid XML');
    } else {
        $locs  = array_map(fn($u) => (string) $u->loc, iterator_to_array($sitemapXml->url, false));
        $dupes = array_keys(array_filter(array_count_values($locs), fn($n) => $n > 1));
        record('sitemap_valid', 'Sitemap is valid XML without duplicates', $dupes ? 'fail' : 'pass',
            count($locs) . ' URLs', $dupes ? 'Duplicates: ' . implode(', ', $dupes) : '');

        // Every channel URL the sitemap promises must resolve directly
        $bad = [];
        foreach ($channelUrls as $url) {
            $res = fetch($toBase($url), true);
            if ($res['status'] !== 200) $bad[] = parse_url($url, PHP_URL_PATH) . ' (' . describe($res) . ')';
        }
        record('sitemap_urls_200', 'Every sitemap channel URL returns 200', $bad ? 'fail' : 'pass',
            count($channelUrls) - count($bad) . '/' . count($channelUrls), implode(', ', $bad));
    }
}

// Rotating sample: a different slice of the sitemap each day
$sample = [];
if ($channelUrls) {
    $offset = ((int) gmdate('z') * SAMPLE_SIZE) % count($channelUrls);
    $rotated = array_merge(array_slice($channelUrls, $offset), array_slice($channelUrls, 0, $offset));
    $sample  = array_slice($rotated, 0, SAMPLE_SIZE);
}

$metaIssues = $longTitles = $jsonIssues = $numberIssues = [];
$timings = ['/' => $home['ttfb_ms']];
foreach ($sample as $i => $url) {
    $path = parse_url($url, PHP_URL_PATH);
    $res  = fetch($toBase($url));
    if ($i < 3) $timings[$path] = $res['ttfb_ms'];
    if ($res['status'] !== 200) continue;   // already reported by sitemap_urls_200
    $p = inspectPage($res['body']);

    $issues = [];
    if ($p['h1_count'] !== 1) $issues[] = "{$p['h1_count']} H1s";
    if (count($p['canonicals']) !== 1 || $p['canonicals'][0] !== $url) $issues[] = 'canonical ≠ own URL';
    if (count($p['descriptions']) !== 1 || trim($p['descriptions'][0]) === '') $issues[] = 'meta description missing or repeated';
    if (str_contains($p['robots'], 'noindex')) $issues[] = 'noindex but listed in sitemap';
    if ($issues) $metaIssues[] = "{$path}: " . implode(', ', $issues);

    $len = mb_strlen($p['title']);
    if ($len > TITLE_MAX_CHARS) $longTitles[] = "{$path} ({$len})";

    foreach ($p['json_ld'] as $block) {
        json_decode($block);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonIssues[] = "{$path}: " . json_last_error_msg();
            break;
        }
    }

    // Stats a visitor would recognise as impossible
    preg_match_all('/(-?\d+(?:\.\d+)?)\s?%/', $p['text'], $m);
    $over = array_filter(array_map('floatval', $m[1]), fn($n) => $n > 100 || $n < 0);
    if ($over) $numberIssues[] = "{$path}: " . implode('%, ', array_unique($over)) . '%';
}

if ($sample) {
    $n = count($sample) . ' pages';
    record('sample_meta', 'Sampled channel pages: one H1, canonical, description, indexable',
        $metaIssues ? 'fail' : 'pass', $n, implode('; ', $metaIssues));
    record('sample_title_length', 'Sampled titles ≤ ' . TITLE_MAX_CHARS . ' characters',
        $longTitles ? 'warn' : 'pass', count($longTitles) . ' too long', implode(', ', $longTitles));
    record('sample_json_ld', 'Sampled structured data parses as JSON',
        $jsonIssues ? 'fail' : 'pass', $n, implode('; ', $jsonIssues));
    record('sample_numbers', 'Sampled pages show no impossible percentages',
        $numberIssues ? 'fail' : 'pass', $n, implode('; ', $numberIssues));
} else {
    record('sample_meta', 'Sampled channel pages: one H1, canonical, description, indexable',
        'warn', '0 pages', 'No channel URLs in the sitemap to sample');
}

// ── C. Speed ─────────────────────────────────────────────────────────────────

$slow = array_filter($timings, fn($ms) => $ms > TTFB_WARN_MS);
record('ttfb', 'Time to first byte under ' . TTFB_WARN_MS . ' ms', $slow ? 'warn' : 'pass',
    'max ' . max($timings) . ' ms',
    implode(', ', array_map(fn($p, $ms) => "{$p} {$ms} ms", array_keys($slow), $slow)));

// PageSpeed Insights' free API is rate limited, so this runs weekly
if ($isProd && (gmdate('N') === '1' || isset($opts['pagespeed']))) {
    $targets = ['/' => Config::SITE_URL . '/'];
    if ($channelUrls) $targets[parse_url($channelUrls[0], PHP_URL_PATH)] = $channelUrls[0];

    $scores = $low = [];
    $key    = getenv('PSI_API_KEY') ?: '';
    foreach ($targets as $path => $url) {
        $api = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?strategy=mobile&category=performance&category=seo&url='
             . urlencode($url) . ($key !== '' ? '&key=' . urlencode($key) : '');
        $res  = fetch($api);
        $data = json_decode($res['body'], true);
        $perf = $data['lighthouseResult']['categories']['performance']['score'] ?? null;
        $seo  = $data['lighthouseResult']['categories']['seo']['score'] ?? null;
        if ($perf === null || $seo === null) {
            $scores[] = "{$path}: unavailable (" . describe($res) . ')';
            continue;
        }
        $perf = (int) round($perf * 100);
        $seo  = (int) round($seo * 100);
        $scores[] = "{$path}: perf {$perf}, seo {$seo}";
        if ($perf < 50 || $seo < 90) $low[] = $path;
    }
    $unavailable = count(array_filter($scores, fn($s) => str_contains($s, 'unavailable')));
    $status = $unavailable === count($targets) ? 'skip' : ($low ? 'warn' : 'pass');
    record('pagespeed', 'PageSpeed (mobile): performance ≥ 50, SEO ≥ 90', $status, '', implode('; ', $scores));
}

// ── Store, compare, report ───────────────────────────────────────────────────

$previous = [];
if ($useDb) {
    try {
        $db = Database::getInstance()->getConnection();
        $db->exec("
            CREATE TABLE IF NOT EXISTS seo_checks (
                id         SERIAL PRIMARY KEY,
                run_date   DATE         NOT NULL,
                check_id   VARCHAR(64)  NOT NULL,
                label      VARCHAR(255) NOT NULL,
                status     VARCHAR(10)  NOT NULL CHECK (status IN ('pass', 'warn', 'fail', 'skip')),
                value      TEXT,
                detail     TEXT,
                created_at TIMESTAMP    NOT NULL DEFAULT NOW(),
                UNIQUE (run_date, check_id)
            )
        ");

        $stmt = $db->prepare("
            SELECT check_id, status FROM seo_checks
            WHERE  run_date = (SELECT MAX(run_date) FROM seo_checks WHERE run_date < :today)
        ");
        $stmt->execute([':today' => $today]);
        $previous = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $upsert = $db->prepare("
            INSERT INTO seo_checks (run_date, check_id, label, status, value, detail)
            VALUES (:run_date, :check_id, :label, :status, :value, :detail)
            ON CONFLICT (run_date, check_id) DO UPDATE
            SET label = EXCLUDED.label, status = EXCLUDED.status, value = EXCLUDED.value,
                detail = EXCLUDED.detail, created_at = NOW()
        ");
        foreach ($results as $r) {
            $upsert->execute([
                ':run_date' => $today, ':check_id' => $r['id'], ':label' => $r['label'],
                ':status'   => $r['status'], ':value' => $r['value'], ':detail' => $r['detail'],
            ]);
        }
    } catch (Exception $e) {
        fwrite(STDERR, "DB error: " . $e->getMessage() . "\n");
        exit(1);
    }
}

foreach ($results as $id => $r) {
    $results[$id]['previous'] = $previous[$id] ?? null;
}
$failing = array_filter($results, fn($r) => $r['status'] === 'fail');
$warning = array_filter($results, fn($r) => $r['status'] === 'warn');
$changed = array_filter($results, fn($r) => $r['previous'] !== null && $r['previous'] !== $r['status']);

if ($asJson) {
    echo json_encode(['date' => $today, 'base_url' => $base, 'checks' => array_values($results)],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    foreach ($results as $r) {
        $change = $r['previous'] !== null && $r['previous'] !== $r['status'] ? " (was {$r['previous']})" : '';
        printf("[%s] %s — %s%s%s\n", $r['status'], $r['label'], $r['value'], $change, $r['detail'] !== '' ? "\n       {$r['detail']}" : '');
    }
    printf("\n%d checks: %d failing, %d warning, %d changed since last run.\n",
        count($results), count($failing), count($warning), count($changed));
}

// Quiet when nothing is broken and nothing moved; a standing warning alone isn't news
if (!$sendEmail || (!$failing && !$changed)) {
    exit(0);
}
if (Config::SEO_REPORT_EMAIL === '') {
    fwrite(STDERR, "SEO_REPORT_EMAIL is not configured — report not sent.\n");
    exit(1);
}

$esc  = fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rows = function (array $list) use ($esc): string {
    $html = '';
    foreach ($list as $r) {
        $was   = $r['previous'] !== null && $r['previous'] !== $r['status'] ? " <span style=\"color:#6b7280\">(was {$esc($r['previous'])})</span>" : '';
        $html .= '<li style="margin:0 0 12px"><strong>' . $esc($r['label']) . "</strong>{$was}<br>"
               . '<span style="color:#6b7280">' . $esc(trim($r['value'] . ' ' . $r['detail'])) . '</span></li>';
    }
    return $html;
};
$section = fn(string $title, array $list) => $list
    ? '<h2 style="font-size:15px;margin:24px 0 8px">' . $title . '</h2><ul style="padding-left:20px;margin:0">' . $rows($list) . '</ul>'
    : '';

$recovered = array_filter($changed, fn($r) => $r['status'] === 'pass');
$html = '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.5;color:#111;max-width:600px">'
      . '<p style="margin:0 0 8px">Daily SEO check for ' . $esc($base) . ', ' . $today . '.</p>'
      . $section('Failing', $failing)
      . $section('Warnings', $warning)
      . $section('Fixed since last run', $recovered)
      . '<p style="margin:24px 0 0;color:#6b7280">Full history: <a href="' . Config::SITE_URL . '/admin_seo.php">admin → SEO</a></p>'
      . '</div>';

$subject = sprintf('SEO check %s: %d failing, %d changed', $today, count($failing), count($changed));
[$ok, $error] = sendViaResend(Config::SEO_REPORT_EMAIL, $subject, $html);
if (!$ok) {
    fwrite(STDERR, "Report email failed: {$error}\n");
    exit(1);
}
echo "Report emailed.\n";
