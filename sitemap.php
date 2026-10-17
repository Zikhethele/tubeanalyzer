<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/partials/helpers.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = 'https://yessherlock.com';

function sitemapUrl(string $loc, ?string $lastmod = null): string {
    $xml = "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</loc>\n";
    if ($lastmod !== null) {
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
    }
    return $xml . "  </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo sitemapUrl($baseUrl . '/');
echo sitemapUrl($baseUrl . '/terms');

try {
    $db = Database::getInstance()->getConnection();
    // Same gate as channelIsIndexable(): only channels with an id, a subscriber
    // count and at least one video. Newest first, so the first row seen per
    // channel is its latest analysis.
    $stmt = $db->query("
        SELECT channel_name,
               data::jsonb->>'channel_name' AS display_name,
               data::jsonb->>'channel_id'   AS channel_id,
               created_at
        FROM   analyses
        WHERE  analyzed = 1
          AND  COALESCE(data::jsonb->>'channel_id', '') <> ''
          AND  data::jsonb->>'subscribers' IS NOT NULL
          AND  jsonb_typeof(data::jsonb->'videos') = 'array'
          AND  jsonb_array_length(data::jsonb->'videos') > 0
        ORDER  BY created_at DESC
    ");

    $seenChannels = [];
    $seenSlugs    = [];
    foreach ($stmt as $row) {
        $slug = canonicalChannelSlug($row['display_name'], (string) $row['channel_name']);
        if ($slug === '' || isset($seenChannels[$row['channel_id']]) || isset($seenSlugs[$slug])) {
            continue;
        }
        $seenChannels[$row['channel_id']] = true;
        $seenSlugs[$slug]                 = true;

        echo sitemapUrl(
            $baseUrl . '/channel/' . rawurlencode($slug),
            date('Y-m-d', strtotime((string) $row['created_at']))
        );
    }
} catch (Exception $e) {
    // DB unavailable — degrade to a sitemap containing just the static pages
}

echo '</urlset>' . "\n";
