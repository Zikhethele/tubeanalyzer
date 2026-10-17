# SEO session log — 2026-10-12

Record of every step taken in the session that produced the SEO plan for
yessherlock.com and implemented its first phase. Nothing has been committed or
deployed; all changes are in the working tree.

The full plan is at `~/.claude/plans/create-a-seo-plan-rosy-cocoa.md`.

## 1. Audit (read-only)

1. Listed the project files and checked for existing SEO files (`robots.txt`,
   `sitemap.php`, `.htaccess`, `Dockerfile`).
2. Searched every page for titles, meta tags, canonicals, Open Graph tags,
   JSON-LD, H1s and analytics snippets.
3. Read `robots.txt`, `sitemap.php`, `.htaccess`, `docker/rewrite.conf`,
   `docker/entrypoint.sh`, `Dockerfile`, `marketing/.htaccess`,
   `partials/cookie-notice.php`, `docs/next-steps.md`, `channel.php`, and the
   head and body of `index.php`.
4. Probed the live site with `curl` (GET/HEAD only):

   | URL | Result |
   |---|---|
   | `http://yessherlock.com/` | 301 → https |
   | `https://www.yessherlock.com/` | 301 → apex |
   | `/index.php` | 200 (duplicate of `/`) |
   | `/channel/zzz-not-a-real-channel-xyz` | 200 (soft 404) |
   | `/channel/MrBeast` | 200 (duplicate of lowercase slug) |
   | `/reports/engagement_trends.html` | 200, 4.7 MB, indexable |
   | `/sitemap.xml` | 43 channel URLs, incl. duplicates and name-like slugs |
   | `/` headers | `Set-Cookie: PHPSESSID` + `Cache-Control: no-store` for every visitor |

5. Read the FAQ schema block, slug handling in the model, `.gitignore`, and
   where the brand name appears.

## 2. Decisions asked of the user

- **Brand name** → "Both, transitional": move to Sherlock, keep TubeAnalyzer
  discoverable as "formerly".
- **Scope** → technical fixes plus new pages; no off-site work.

## 3. Plan

Wrote a four-phase plan and had it approved:

1. Indexing hygiene
2. Brand, snippets and structured data
3. Pages worth ranking (richer channel pages, directory, landing pages)
4. Measurement and speed

## 4. Phase 1 implementation

Before coding: read `config/Auth.php`, the shape of `config/Config.php`
(secrets redacted), `.env` key names only, the `analyses` schema in
`migrate.php`, and how `pending_channels.php` stores results. Ran read-only
queries against the local dev database (`data` is `jsonb`; it held 0 rows).

Changes:

| File | Change |
|---|---|
| `partials/helpers.php` (new) | `channelSlug()`, `canonicalChannelSlug()`, `channelIsIndexable()` shared by `channel.php` and `sitemap.php` |
| `channel.php` | 301 non-normalised slugs to the normalised one; look up by typed name or YouTube display name; 301 typed variants to the display-name slug; 404 when there is no data (503 if the DB is down); `noindex, follow` unless the channel has an id, subscriber count and at least one video. Also fixed the SQL slug not trimming hyphens, which made names like `MrBeast!` unreachable |
| `sitemap.php` | Only indexable channels, one entry per `channel_id`, canonical slugs; added `/terms`; dropped `changefreq` and `priority` |
| `index.php` | 301 `/index.php` → `/` (query string kept); session only resumed when a session cookie already exists; canonical and `og:url` get a trailing slash; removed `meta keywords` and `meta author`; terms links → `/terms` |
| `terms.php` | 301 `/terms.php` → `/terms`; canonical updated |
| `.htaccess` | Rewrite `/terms` → `terms.php` |
| `reports/.htaccess` (new) | `X-Robots-Tag: noindex, nofollow` |
| `Dockerfile` | `a2enmod headers` |
| `robots.txt` | Disallow `/login.php`, `/logout.php` |
| `instagram.php`, `partials/cookie-notice.php` | Terms links → `/terms` |

Deviations from the plan, with reasons:

- **Redirects are done in PHP, not `.htaccess`.** Apache behind Render's proxy
  would build an `http://` Location and add a hop; PHP sends a relative one.
- **`/reports/` and `/unsubscribe.php` are not disallowed in `robots.txt`.**
  Disallowing them would stop crawlers seeing their `noindex`.
- **`/terms` has no `lastmod` in the sitemap.** File mtime resets on every
  deploy, so it would be wrong.

## 5. Verification

- `php -l` clean on all changed PHP files.
- Inserted 6 fixture rows into the local dev database (marked with email
  `seo-fixture@test.invalid`), served the site with `php -S` and a scratch
  router that mimics the rewrites, and checked with `curl`:

  | Request | Result |
  |---|---|
  | `/` | 200, `index, follow`, no `Set-Cookie` |
  | `/index.php?channel=MKBHD` | 301 → `/?channel=MKBHD` |
  | `/terms.php` | 301 → `/terms` (200) |
  | `/channel/zzz-not-real` | 404, `noindex, follow` |
  | `/channel/MrBeast` | 301 → `/channel/mrbeast` (200, indexable) |
  | `/channel/andilekamajola` | 301 → `/channel/andile-kamajola` (200) |
  | `/channel/ashley-jones` (error body stored) | 404 |
  | `/channel/no-videos` (no videos) | 200, `noindex, follow` |
  | `/channel/nihongo` (non-Latin display name) | 200, stays on typed slug |
  | `/sitemap.xml` | home, terms, 3 channels; no duplicates, no data-less slugs |

- Deleted the fixture rows and stopped the test server afterwards.

**Not verified:** the Apache-level pieces (`/terms` rewrite in `.htaccess`,
the `X-Robots-Tag` header on `/reports/`, `a2enmod headers`). Docker was not
running locally, so the image could not be built. Check these after deploy:

```bash
curl -sI https://yessherlock.com/terms | head -1                              # expect 200
curl -sI https://yessherlock.com/reports/engagement_trends.html | grep -i x-robots
curl -sI https://yessherlock.com/channel/zzz-not-real | head -1               # expect 404
```

## 6. Social media campaign

1. Read the stats shown on four live channel pages (`mrbeast`, `mkbhd`,
   `veritasium`, `kurzgesagt`) to use real figures in example posts.
2. Found MKBHD's page showing 2224.5% average engagement. Most likely cause:
   videos with zero views were divided as if they had one. Changed
   `channel.php` so `engRate()` returns 0 for zero-view videos and the average
   skips them. Lint clean; not tested against the real MKBHD data, which is
   only in production.
3. Wrote `marketing/social-campaign.md`: goal, audience, positioning,
   platforms, four-week calendar, ready-to-post copy, tracking and rules.

## 7. Still to do

- Commit and deploy Phase 1, then run the three checks above.
- Verify the domain in Google Search Console and Bing Webmaster Tools (DNS TXT
  record at Namecheap) and submit `sitemap.xml`, so a baseline exists before
  content changes.
- Phases 2–4 of the plan.
