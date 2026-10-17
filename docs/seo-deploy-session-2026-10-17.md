# SEO deploy session — 2026-10-17

Everything done in the session that shipped SEO Phase 1, the SEO admin page
and the daily SEO agent. The exact shell commands are in
`docs/seo-deploy-commands-2026-10-17.md`. The ongoing SEO history is in
`docs/seo-session-log.md` (§8 covers this deploy).

## Summary

- **Live:** Phase 1 indexing fixes, `seo_daily.php`, `admin_seo.php`, the
  `seo-daily.yaml` workflow and the `.claude/agents/seo.md` agent, all in
  commit `a9bd8e0` on `main`.
- **Verified:** every live URL check passes. The first workflow run succeeded,
  saved its results and sent the report email.
- **Scheduled:** a cloud routine runs the SEO agent every day at 06:30 UTC
  (08:30 SAST). It may only open pull requests.
- **Open:** a one-line fix in `seo_daily.php` and three docs files are
  uncommitted. See [Open items](#open-items).

## Decisions

| Question | Owner's answer | Reason |
|---|---|---|
| What ships? | All SEO work in one commit | The daily checks assume Phase 1 is live. Several files mix both changes. |
| Who commits and pushes? | The owner | Render auto-deploys `main`. |
| Where does the report go? | Owner's gmail, via the `SEO_REPORT_EMAIL` secret | — |

Left out of the commit on purpose:

- `tanal`: a local shell helper for calling the channel-info API.
- `scraper/`: unrelated, and the SEO agent's rules say not to touch it.
- `write-the-plan-to-logical-babbage.md`: a stray copy of an earlier plan.

## Timeline

1. **Read the state** (Claude). Read the SEO memory notes, the session log, the
   agent file, the workflow and the uncommitted diff. Found that the
   `SEO_REPORT_EMAIL` secret was missing. `DATABASE_URL` and `RESEND_API_KEY`
   already existed.
2. **Pre-flight** (Claude, local only).
   - `php -l` was clean on all 11 changed PHP files.
   - Served the site with `php -S` and a router that mimics the Apache
     rewrites. `/admin_seo.php` returned 200. Login with the local
     `ADMIN_PASSWORD` returned 302 and showed stored check results.
     `/seo_daily.php` returned 403.
   - `php seo_daily.php --no-db --no-email` against the local server ran 14
     checks: 1 fail and 1 warning, both expected. The fail was the `/reports/`
     header, which needs Apache. The warning was the empty channel sample,
     because there is no local channel data.
3. **Secret** (Claude). Set `SEO_REPORT_EMAIL` with `gh secret set`.
4. **Commands file** (Claude, at the owner's request). Wrote
   `docs/seo-deploy-commands-2026-10-17.md`.
5. **First "pushed"** (owner). `origin/main` was still at `4c808f9` and
   nothing was committed locally, so Claude didn't run any checks and gave the
   commit commands again.
6. **Push** (owner). Commit `a9bd8e0`: 20 files, +1275 / −47. Render deployed
   it.
7. **Live checks** (Claude, GET/HEAD only).

   | URL | Expected | Result |
   |---|---|---|
   | `/` | 200, no cookie | 200, no `Set-Cookie` |
   | `/terms` | 200 | 200 |
   | `/terms.php` | 301 → `/terms` | 301 → `/terms` |
   | `/index.php` | 301 → `/` | 301 → `/` |
   | `/channel/zzz-not-real` | 404 | 404 |
   | `/seo_daily.php` | 403 | 403 |
   | `/admin_seo.php` | 200 login form | 200 |
   | `/reports/engagement_trends.html` | noindex header | `X-Robots-Tag: noindex, nofollow` |
   | `/robots.txt` | disallows admin page | `Disallow: /admin_seo.php` |

8. **First workflow run** (Claude triggered it).
   [Run 39057180966](https://github.com/Zikhethele/tubeanalyzer/actions/runs/39057180966)
   succeeded, wrote results to `seo_checks` and sent the report email.
   20 checks: 1 fail and 2 warnings.
   - **Warn:** one sampled channel title is over 60 characters. That's for
     Phase 2.
   - **Warn:** max time to first byte was 1004 ms against a 1000 ms limit.
     Probably a Render cold start.
9. **False failure fixed** (Claude). The "/reports/ sends X-Robots-Tag" check
   requested `/reports/index.html`. The root `.htaccess` denies every
   `index.html`, and Apache's `Header set` adds nothing to a 403, so that check
   could never pass. Changed `seo_daily.php` to request
   `/reports/engagement_trends.html`, with a comment explaining why. Lint was
   clean, and a rerun against production gave 20 checks with 0 failing.
10. **Logs** (Claude, at the owner's request). Added §8 "Deploy — 2026-10-17"
    to `docs/seo-session-log.md` and marked the old "commit and deploy" to-do
    as done. Added the post-push commands to the commands file.
11. **Scheduled agent** (Claude). Created routine
    `trig_011Csn9MmNrhSBDYY1HMzAuL`, "Sherlock daily SEO agent":
    - Cron `30 6 * * *` (06:30 UTC daily), first run 2026-10-18.
    - Model Sonnet 5.5, repo `Zikhethele/tubeanalyzer`, default cloud
      environment.
    - The prompt tells it to follow `.claude/agents/seo.md` and repeats the hard
      rules: never push to `main`, merge or deploy; at most one PR, on a branch
      named `seo/<date>-<topic>`; only GET and HEAD requests to production; no
      database writes, and `seo_daily.php` always with `--no-db --no-email`.
      It installs PHP if missing and treats Search Console as not connected.
      It is warned about the `index.html` 403 trap.
    - The server attached Gmail and Claude Docs automatically. Claude removed
      both and left only the Claude Code connector, because an SEO agent
      doesn't need inbox access.
12. **Memory** (Claude). Updated the `seo-agent` project memory with the
    deploy, the fix and the routine.

## Files touched

**Committed in `a9bd8e0`:**
- Modified: `.gitignore`, `.htaccess`, `Dockerfile`, `channel.php`,
  `config/AdminAuth.php`, `config/Config.php`, `index.php`, `instagram.php`,
  `partials/cookie-notice.php`, `robots.txt`, `sitemap.php` and `terms.php`.
- New: `seo_daily.php`, `admin_seo.php`, `partials/helpers.php`,
  `reports/.htaccess`, `.github/workflows/seo-daily.yaml`,
  `.claude/agents/seo.md`, `docs/seo-session-log.md` and
  `marketing/social-campaign.md`.

**Uncommitted:**
- `seo_daily.php`: the `/reports/` check fix.
- `docs/seo-session-log.md`: the new §8.
- `docs/seo-deploy-commands-2026-10-17.md`
- `docs/seo-deploy-session-2026-10-17.md` (this file)

**Outside the repo:** the GitHub Actions secret `SEO_REPORT_EMAIL` and the
cloud routine `trig_011Csn9MmNrhSBDYY1HMzAuL`.

## Open items

1. **Push before 06:30 UTC on 2026-10-18.** Otherwise the 06:00 workflow
   reports the false `/reports/` failure again, and the agent may spend its
   one PR on fixing it.
   ```bash
   git add seo_daily.php docs/seo-session-log.md \
     docs/seo-deploy-commands-2026-10-17.md docs/seo-deploy-session-2026-10-17.md
   git commit -m "Fix /reports/ noindex check probing a denied URL; log SEO deploy"
   git push origin main
   ```
2. Log into `/admin_seo.php` on production and confirm today's run shows. This
   needs `ADMIN_PASSWORD` set on Render.
3. Review the first agent run and its PR, if any, tomorrow morning.
4. Watch the long-title and time-to-first-byte warnings.
5. Optional: add a `PSI_API_KEY` secret (PageSpeed returns 429 without one)
   and a Search Console service account as `GSC_SA_JSON`.

## Links

- Workflow run: https://github.com/Zikhethele/tubeanalyzer/actions/runs/39057180966
- Routine: https://claude.ai/code/routines/trig_011Csn9MmNrhSBDYY1HMzAuL
- Admin page: https://yessherlock.com/admin_seo.php
