# Next steps — session handoff (2026-08-09)

## 1. Instagram Audit — finish shipping

**Code status:** done, committed, and pushed to `main`.

- Commit `b5df148` — `instagram.php`, `instagram_audit.php`,
  `controllers/InstagramAuditController.php`, `models/InstagramAudit.php`,
  plus `authRequireUser()` added to `config/Auth.php`, `INSTAGRAM_API_URL`
  added to `config/Config.php`, the nav link in `index.php`, and
  `instagram_audit.php` added to `robots.txt`'s Disallow list (same pattern
  as `analyze.php`/`register.php`).
- Backend: `https://insta-scrapper-d70l.onrender.com` (FastAPI "Instagram
  Scraper API", confirmed live — `/` and `/health` respond instantly, exposes
  the `POST /audit/profile` endpoint the PHP controller calls).

**Two manual steps left, both need Render dashboard access:**

### a. Set the API URL env var on Render
Web service → Environment tab → add:
- Key: `INSTAGRAM_API_URL`
- Value: `https://insta-scrapper-d70l.onrender.com`

Saving triggers a redeploy on its own.

### b. Create the `instagram_audits` table on production
`migrate.php` is intentionally gitignored and never deployed (established
repo pattern — same reasoning that got it pulled off the web-servable file
list previously). Run it locally against the production DB as a one-off:

```bash
# Get the production connection string from Render → your Postgres/Neon
# resource → Connection string
mv .env .env.local.bak
DATABASE_URL="<paste production connection string here>" php migrate.php
mv .env.local.bak .env
```

Look for `[ok] CREATE TABLE IF NOT EXISTS instagram_audits...` in the
output. Every statement uses `IF NOT EXISTS`, so it's safe to re-run even if
some tables already exist — it won't touch existing data. Moving `.env`
aside first matters: `config/Config.php` reads `.env` when present and
ignores the `DATABASE_URL` env var otherwise, so leaving `.env` in place
would silently run the migration against the local dev DB instead.

### Known blocker (not a repo issue)
Tested `/audit/profile` (nasa) and `/profile` (instagram) against the live
backend — both hung with no response for 150s / 60s respectively, while `/`
and `/health` returned instantly. The backend's own Render logs (pasted
mid-session) confirmed why: Instagram is returning 429s, and the scraper's
retry/backoff logic can stretch a single request out to 5+ minutes:

```
19:30:46 [WARNING] Rate limited (429). Retry 1/4 — waiting 31s...
19:31:17 [WARNING] Rate limited (429). Retry 2/4 — waiting 61s...
19:34:19 [WARNING] Rate limited (429). Retry 4/4 — waiting 301s...
```

This means the feature will currently show real users "Could not reach the
Instagram audit service" (the PHP side's `CURLOPT_TIMEOUT` is 20s, far
shorter than the observed retry chain) until the rate-limiting is resolved
on the scraper backend — a separate repo/service, out of scope here.
Decision made this session: ship the PHP side anyway and fix the backend
separately, rather than hide the nav link or hold the whole feature back.

**Worth revisiting once the backend is healthy:** the 20s `CURLOPT_TIMEOUT`
in `InstagramAuditController.php` was never validated against a real
success-path latency (every test so far hit the rate limit) — once a normal
request's actual duration is known, bump the timeout to match if needed.

---

## 2. Site audit — what else to add (recap)

Ranked by how much of the backend already exists vs. is still missing:

1. **Instagram Audit** — in progress, see above.

2. **Monetization is invisible to users.** `config/Config.php` already
   defines real prices (`PRICE_PRO_MONTHLY = 19`, `PRICE_AGENCY_MONTHLY = 99`,
   etc.) and `users.subscription_tier` (`free`/`pro`/`agency`) already gates
   daily limits in `AnalyzeController.php` and
   `InstagramAuditController.php`. But there's no pricing page, no upgrade
   button anywhere in `index.php`, and no payment integration at all (no
   Stripe/Paddle in the codebase) — the only way to become "pro" today is
   editing the DB by hand. `subscriptions.sql` is a leftover MySQL dump for a
   `subscriptions` table that was never migrated into the live Postgres
   schema. Biggest gap between what the backend already supports and what a
   visitor can actually do.

3. **No account/dashboard page for logged-in users.** After signing in, a
   user gets a name greeting and a logout link — nothing else. No history of
   past reports, no "3 of 5 free analyses used today" indicator.
   `models/Analysis.php` has a `getUserAnalyses()` query already written,
   just commented out — most of the backend piece exists, it's missing a
   page.

**Smaller things worth knowing about:**
- Anonymous (not-logged-in) submissions skip the daily-limit check entirely
  (`AnalyzeController::analyze()` only checks usage
  `if ($userId !== null)`) — no incentive currently baked in to sign up.
- No automated tests anywhere in the repo — changes get verified with
  `php -l` and manual curl/browser checks.
- `models/AnalyzerService.php` is dead code (not `require`'d anywhere) but
  writes email addresses to a plaintext `analyze_log.txt` in the webroot if
  ever revived — safe to delete.

**Suggested order if picking this back up:** finish #1 (the two manual steps
above + backend rate-limit fix), then #2 and #3 together since they reuse
the same tier/usage data (account page with usage + history, pricing page
wired to Stripe Checkout).
