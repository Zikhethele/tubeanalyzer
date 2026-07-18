<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = 'https://yessherlock.com';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo "  <url>\n    <loc>{$baseUrl}/</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT DISTINCT ON (slug) slug, created_at
        FROM (
            SELECT LOWER(REGEXP_REPLACE(channel_name, '[^a-zA-Z0-9]+', '-', 'g')) AS slug,
                   created_at
            FROM   analyses
            WHERE  analyzed = 1
        ) t
        WHERE slug != ''
        ORDER BY slug, created_at DESC
    ");

    foreach ($stmt as $row) {
        $slug    = trim((string) $row['slug'], '-');
        if ($slug === '') {
            continue;
        }
        $loc     = $baseUrl . '/channel/' . rawurlencode($slug);
        $lastmod = date('Y-m-d', strtotime((string) $row['created_at']));
        echo "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.7</priority>\n  </url>\n";
    }
} catch (Exception $e) {
    // DB unavailable — degrade to a sitemap containing just the homepage
}

echo '</urlset>' . "\n";
