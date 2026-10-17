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

- ~~Commit and deploy Phase 1, then run the three checks above.~~ Done
  2026-10-17, see §8.
- Verify the domain in Google Search Console and Bing Webmaster Tools (DNS TXT
  record at Namecheap) and submit `sitemap.xml`, so a baseline exists before
  content changes.
- Phases 2–4 of the plan.

## 8. Deploy — 2026-10-17

Shipped Phase 1 together with the daily SEO tooling (`seo_daily.php`,
`admin_seo.php`, `.github/workflows/seo-daily.yaml`, `.claude/agents/seo.md`).
Every command run is listed in `docs/seo-deploy-commands-2026-10-17.md`.

1. **Scope.** The owner chose to ship all the SEO work in one commit, because
   the daily checks assume Phase 1 is live. Left out `tanal`, `scraper/` and a
   stray plan file.
2. **Pre-flight.** `php -l` was clean on all 11 changed PHP files. On `php -S`
   with a router that mimics the rewrites, login to `/admin_seo.php` worked and
   showed stored rows, and `/seo_daily.php` returned 403. `seo_daily.php` ran
   against the local server; its only failure was the Apache-only `/reports/`
   header.
3. **Secret.** Set `SEO_REPORT_EMAIL` in GitHub Actions. `DATABASE_URL` and
   `RESEND_API_KEY` already existed.
4. **Commit.** The owner committed and pushed `a9bd8e0` to `main`, and Render
   deployed it.
5. **Live checks** (GET/HEAD only):

   | URL | Result |
   |---|---|
   | `/` | 200, no `Set-Cookie` |
   | `/terms` | 200 |
   | `/terms.php` | 301 → `/terms` |
   | `/index.php` | 301 → `/` |
   | `/channel/zzz-not-real` | 404 |
   | `/seo_daily.php` | 403 |
   | `/admin_seo.php` | 200 (login form) |
   | `/reports/engagement_trends.html` | `X-Robots-Tag: noindex, nofollow` |
   | `/robots.txt` | disallows `/admin_seo.php` |

   This also covers the three checks from §5 that couldn't run locally.
6. **First workflow run.** Ran the workflow by hand
   ([run 39057180966](https://github.com/Zikhethele/tubeanalyzer/actions/runs/39057180966)).
   It succeeded, stored results in `seo_checks` and emailed the report.
   20 checks: 1 failing, 2 warnings.
   - **Fail, false alarm:** "/reports/ sends X-Robots-Tag". The script checked
     `/reports/index.html`, but the root `.htaccess` denies every `index.html`.
     Apache's `Header set` doesn't apply to a 403, so the header could never
     appear there. Changed the check to use `/reports/engagement_trends.html`.
     A rerun against production: 20 checks, 0 failing. **This fix is
     uncommitted.**
   - **Warn:** one sampled channel page has a title longer than 60 characters.
     That belongs in Phase 2.
   - **Warn:** max time to first byte was 1004 ms, just over the 1000 ms limit.
     Probably a Render cold start; keep an eye on it.

### Still to do after the deploy

- Commit the `seo_daily.php` check fix.
- Log into `/admin_seo.php` on production and confirm today's run shows (needs
  `ADMIN_PASSWORD` set on Render).
- Schedule the daily SEO agent routine for 06:30 UTC.
- Optional: `PSI_API_KEY` secret (PageSpeed returns 429 without one) and a
  Search Console service account (`GSC_SA_JSON`).
