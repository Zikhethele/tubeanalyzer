# Commands run — prospects, mailer and workflow session, 2026-10-17/18

The second half of the session. The first half (SEO deploy) is in
`docs/seo-deploy-commands-2026-10-17.md`. All commands ran from the repo root
unless a `cd` says otherwise. `$S` is the session scratchpad:
`/private/tmp/claude-501/-Users-zikhethelegumede-Sites-TubeAnalyzer/4fbce96d-6173-4400-9509-917c420734eb/scratchpad`.
Email addresses are written as `<email>`.

## 1. Scheduled SEO agent

The schedule skill does this through the routines API, not the shell. It
created routine `trig_011Csn9MmNrhSBDYY1HMzAuL`: cron `30 6 * * *`, model
Sonnet 5.5, repo `Zikhethele/tubeanalyzer`. A second call removed the Gmail
and Claude Docs connectors that were attached automatically.

```bash
uuidgen | tr A-Z a-z                 # event id for the routine
date -u +%Y-%m-%dT%H:%M:%SZ
```

## 2. Email scraper (`scraper/`)

The owner ran these:

```bash
cd scraper
python3 scrape_emails.py urls.txt --out prospects_scraped.csv --max-pages 5 --delay 2
#   1st: FileNotFoundError, because urls.txt didn't exist yet
#   2nd: sharpedgemedia1.odoo.com returned 404
#   3rd: "No URLs found", because urls.txt had only comments
#   4th: 11 addresses from 4 agencies
#   5th, after prospect-finder added 4 more sites: 14 addresses
find . -type f -name "urls.txt"
```

Claude ran these:

```bash
cat scraper/scrape_emails.py; cat scraper/.htaccess
for u in https://sharpedgemedia1.odoo.com https://sharpedgemedia1.odoo.com/ https://sharpedgemedia1.odoo.com/about-us; do
  curl -s -o /dev/null -w "%{http_code} %{redirect_url}  $u\n" -A "YesSherlockBot/1.0 (+https://yessherlock.com)" "$u"
done                                  # all 404, so the site was removed from urls.txt

# Check each candidate agency site loads and has the right title
for d in hyhagency.com haveyouheard.co.za peoplehaveinfluence.co.za actorvate.com actorvate.co.za thesocialagency.co.za brandinfluence.co.za; do
  curl -sL -m 15 -A "YesSherlockBot/1.0 (+https://yessherlock.com)" -o page.html -w "%{http_code} %{url_effective}" "https://$d"
  grep -o -i '<title[^>]*>[^<]*' page.html | head -1
done

# Read agency homepages to write personal_reason lines
for u in https://hyhagency.com/ https://www.peoplehaveinfluence.com/ https://actorvate.co.za/; do
  curl -sL -m 20 -A "YesSherlockBot/1.0 (+https://yessherlock.com)" "$u" | python3 -I -c "<strip tags, print text>"
done
grep -n -i "personal_reason\|first_name" mailer_prospects.php marketing/emails/prospect.html

# Junk filter test after editing JUNK_LOCAL / JUNK_DOMAIN
python3 -I -c "<load scrape_emails.py, run extract_emails() on sample addresses>"
#   dropped name@domain.co.za, hello@example.co.uk, you@yourcompany.com and info@domain.com; kept the 3 real ones

cp scraper/prospects_scraped.csv scraper/prospects_scraped.raw.csv
python3 -I -c "<csv.DictReader check of prospects_scraped.csv / prospects_ready.csv>"
```

## 3. prospect-finder agent

Created `.claude/agents/prospect-finder.md`. Claude checked the first test run:

```bash
cat scraper/urls.txt
for u in https://thesalt.co.za/ https://wearetheracketclub.com/; do
  curl -sL -m 15 -o /dev/null -w "%{http_code} %{url_effective}\n" -A "YesSherlockBot/1.0 (+https://yessherlock.com)" "$u"
done
```

The enrichment run (Job 2) was done by the subagent, as listed in its report:

```bash
REPORT_EMAIL="$(git config user.email)"
timeout 75 ./tanal --name "Syntak Media" --email "$REPORT_EMAIL"   # failed: macOS has no `timeout`
./tanal --name "Syntak Media" --email "$REPORT_EMAIL"              # wrong channel ("Syntac")
```

Claude then ran:

```bash
find scraper/prospects -type f | sort
grep -rl "$(git config user.email)" scraper/prospects          # nothing found
E="$(git config user.email)"
./tanal --name "@SyntakMedia" --email "$E" > $S/syntak.json     # right channel: 4 subs, 1 video
cp $S/syntak.json scraper/prospects/syntakmedia.com/youtube.json
```

## 4. Mailer preview page (`admin_mailer.php`)

```bash
cat mailer_prospects.php
sed -n 1,90p mailer_common.php
grep -n "define(\|BATCH\|CAMPAIGN" mailer_retention.php
for f in admin_mailer.php mailer_common.php mailer_prospects.php config/AdminAuth.php; do php -l $f; done

# Local test (DB_HOST=localhost): log in, then add five test rows
php -S 127.0.0.1:8766 -t .
curl -s -c $S/cj2 -b $S/cj2 --data-urlencode "password=$PW" http://127.0.0.1:8766/admin_mailer.php
php $S/fixture.php add                # rows covering send, no reason, unsubscribed, retry and already sent
curl -s -b $S/cj2 http://127.0.0.1:8766/admin_mailer.php
curl -s -b $S/cj2 "http://127.0.0.1:8766/admin_mailer.php?preview=d-retry@fixture.invalid"
php -r '<prospectBatch() count and constants>'
php $S/fixture.php remove
pkill -f "php -S 127.0.0.1:8766"

git add admin_mailer.php mailer_common.php mailer_prospects.php config/AdminAuth.php robots.txt
git commit -m "Add admin page previewing the next prospect mailer run"     # f96ecac
git push origin main

# Waiting for the deploy: still 404 after 15 minutes, because Render deploys are manual
curl -s -o /dev/null -w "%{http_code}" https://yessherlock.com/admin_mailer.php
gh api repos/Zikhethele/tubeanalyzer/commits/f96ecac/status
gh api repos/Zikhethele/tubeanalyzer/deployments

# After the owner's manual deploy
curl -s -o /dev/null -w "%{http_code}\n" https://yessherlock.com/admin_mailer.php   # 200
curl -s https://yessherlock.com/robots.txt | grep admin_mailer
```

## 5. Pushes

```bash
git add seo_daily.php docs/seo-session-log.md docs/seo-deploy-commands-2026-10-17.md \
  docs/seo-deploy-session-2026-10-17.md docs/seo-gh-commands.md
git commit -m "Fix /reports/ noindex check probing a denied URL; add SEO deploy docs"   # 06b781b
git push origin main

git add .claude/agents/prospect-finder.md
git commit -m "Add prospect-finder agent for building the prospect site list"         # 405d4e4
git push origin main

sed -i '' 's/TubeAnalyzer/Sherlock/g' marketing/emails/prospect.html
git add marketing/emails/prospect.html
git commit -m "Use the Sherlock name in the prospect email template"                  # cb9ddb5
git push origin main
```

## 6. Scheduled workflows

```bash
cat .github/workflows/mailers.yaml
gh run list --workflow mailers.yaml --limit 5        # last run 2026-10-09
gh workflow list --all
#   TubeAnalyzer Campaign Mailers   disabled_inactivity
#   TubeAnalyzer Worker             disabled_inactivity

gh workflow enable worker.yaml                       # worker active again (every 5 min)
gh workflow run worker.yaml
gh run watch 39074747468 --exit-status
gh run view 39074747468 --log                        # "No pending channels."
```

The mailers workflow is **still disabled**. To turn it on after checking
`/admin_mailer.php`:

```bash
gh workflow enable mailers.yaml                      # next run 08:00 UTC
```
