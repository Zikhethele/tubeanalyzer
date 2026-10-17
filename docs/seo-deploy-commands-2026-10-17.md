# Commands run — SEO deploy session, 2026-10-17

Every shell command run in this session, in order. All of them ran from the
repo root (`/Users/zikhethelegumede/Sites/TubeAnalyzer`). `$S` is the session
scratchpad: `/private/tmp/claude-501/-Users-zikhethelegumede-Sites-TubeAnalyzer/4fbce96d-6173-4400-9509-917c420734eb/scratchpad`.

## 1. Read the SEO memory, workflow and status

```bash
cat ~/.claude/projects/-Users-zikhethelegumede-Sites-TubeAnalyzer/memory/seo-agent.md \
    ~/.claude/projects/-Users-zikhethelegumede-Sites-TubeAnalyzer/memory/admin-page.md
ls .claude/agents
cat .github/workflows/seo-daily.yaml
ls .github/workflows
git diff --stat
cat reports/.htaccess
cat docs/seo-session-log.md | head -80
```

## 2. Read the rest of the log, the agent file and the diffs

```bash
sed -n 80,400p docs/seo-session-log.md
cat .claude/agents/seo.md
wc -l seo_daily.php admin_seo.php
grep -n "seo_checks\|CREATE TABLE" migrate.php seo_daily.php admin_seo.php | head -30
git diff config/ .gitignore Dockerfile .htaccess robots.txt
```

## 3. Inspect untracked files, the admin page and secrets

```bash
ls -la tanal scraper write-the-plan-to-logical-babbage.md
head -20 write-the-plan-to-logical-babbage.md
file tanal
sed -n 1,40p admin_seo.php
sed -n 1,60p seo_daily.php
gh secret list 2>&1 | head
gh pr list 2>&1 | head
git remote -v
git status -sb | head -2
```

## 4. Lint every changed PHP file

```bash
for f in channel.php config/AdminAuth.php config/Config.php index.php instagram.php \
         partials/cookie-notice.php sitemap.php terms.php seo_daily.php admin_seo.php \
         partials/helpers.php; do
  php -l $f
done
cat tanal
ls docker
```

Result: no syntax errors in any file.

## 5. Serve locally and log into the SEO admin page

```bash
S=/private/tmp/claude-501/-Users-zikhethelegumede-Sites-TubeAnalyzer/4fbce96d-6173-4400-9509-917c420734eb/scratchpad
mkdir -p $S
cat > $S/router.php <<'EOF'
<?php
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = '/Users/zikhethelegumede/Sites/TubeAnalyzer';
if (preg_match('#^/(seo_daily|migrate|pending_channels|mailer_common)\.php$#', $p)) { http_response_code(403); exit; }
if (preg_match('#^/channel/([^/]+)/?$#', $p, $m)) { $_GET['slug'] = urldecode($m[1]); require "$root/channel.php"; return true; }
if ($p === '/sitemap.xml') { require "$root/sitemap.php"; return true; }
if (preg_match('#^/terms/?$#', $p)) { require "$root/terms.php"; return true; }
return false;
EOF
(php -S 127.0.0.1:8765 -t . $S/router.php > $S/server.log 2>&1 &) ; sleep 1
curl -s -o /dev/null -w "admin_seo %{http_code}\n" http://127.0.0.1:8765/admin_seo.php
PW=$(grep '^ADMIN_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'")
grep -n 'name="password"\|<form' config/AdminAuth.php | head -3
rm -f $S/cj; curl -s -c $S/cj -b $S/cj -o /dev/null http://127.0.0.1:8765/admin_seo.php
curl -s -c $S/cj -b $S/cj --data-urlencode "password=$PW" http://127.0.0.1:8765/admin_seo.php -o $S/admin.html -w "login %{http_code}\n"
curl -s -b $S/cj http://127.0.0.1:8765/admin_seo.php | sed -e 's/<[^>]*>/ /g' | tr -s ' \n' | grep -v '^\s*$' | tail -25
```

Result: 200 before login, 302 after login, and the page lists the stored results.

## 6. Run the SEO checks against the local server

```bash
php seo_daily.php --no-db --no-email --base-url=http://127.0.0.1:8765 2>&1 | tail -30
curl -s -o /dev/null -w "seo_daily via web (router) %{http_code}\n" http://127.0.0.1:8765/seo_daily.php
pkill -f "php -S 127.0.0.1:8765"
```

Result: 14 checks, 1 failing and 1 warning. The failure is the `/reports/`
X-Robots-Tag header, which needs Apache. The warning is the empty channel
sample, because there is no local channel data. `/seo_daily.php` returned 403.

## 7. Set the report email secret

```bash
gh secret set SEO_REPORT_EMAIL --body zikhethelegumede@gmail.com
gh secret list
```

## 8. Check whether the commit had been pushed

```bash
git fetch -q origin
git log --oneline -3 origin/main
git status -s | head -25
```

Result: not pushed yet. `origin/main` is still at `4c808f9`.

---

## Not run yet

### Owner: commit and push

```bash
git add .gitignore .htaccess Dockerfile channel.php config/AdminAuth.php config/Config.php \
  index.php instagram.php partials/cookie-notice.php robots.txt sitemap.php terms.php \
  seo_daily.php admin_seo.php partials/helpers.php reports/.htaccess \
  .github/workflows/seo-daily.yaml .claude/agents/seo.md docs/seo-session-log.md marketing/social-campaign.md
git commit -m "SEO Phase 1 fixes, daily SEO checks, SEO admin page and agent

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push origin main
```

### After deploy: check the live site

```bash
curl -sI https://yessherlock.com/terms | head -1                                   # 200
curl -sI https://yessherlock.com/reports/engagement_trends.html | grep -i x-robots # noindex
curl -sI https://yessherlock.com/channel/zzz-not-real | head -1                    # 404
curl -sI https://yessherlock.com/seo_daily.php | head -1                           # 403
curl -sI https://yessherlock.com/admin_seo.php | head -1                           # 200
gh workflow run seo-daily.yaml
gh run watch
```

---

## After the push (run)

```bash
git fetch -q origin && git log --oneline -3 origin/main          # a9bd8e0 is on main
for u in / /terms /channel/zzz-not-real /seo_daily.php /admin_seo.php /terms.php /index.php; do
  curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" "https://yessherlock.com$u"
done
curl -sI https://yessherlock.com/reports/engagement_trends.html | grep -i -E "^HTTP|x-robots"
curl -sI https://yessherlock.com/ | grep -i -E "set-cookie|cache-control"
curl -s https://yessherlock.com/robots.txt | grep admin_seo
gh workflow run seo-daily.yaml
gh run watch 39057180966 --exit-status
gh run view 39057180966 --log | grep -E "\[(pass|fail|warn)\]|checks:"
curl -sI https://yessherlock.com/reports/index.html               # 403: why the check false-failed
# Fix: seo_daily.php now probes /reports/engagement_trends.html
php -l seo_daily.php
php seo_daily.php --no-db --no-email                               # 20 checks, 0 failing
```
