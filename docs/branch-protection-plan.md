# Branch protection plan (parked 2026-10-24)

## Current state

Ruleset **Analyzer_Protect** (id `25775617`), target type `branch`.

| | |
|---|---|
| Enforcement | **disabled** (parked on purpose) |
| Target branches | none; it applied to nothing even when it was active |
| Rules | Restrict deletions (`deletion`), Block force pushes (`non_fast_forward`) |
| Bypass list | empty |

Neither rule blocks a normal push, so finishing this changes nothing about day-to-day work.

## To finish

1. Target the default branch and switch it back on:
   ```bash
   gh api -X PUT repos/Zikhethele/tubeanalyzer/rulesets/25775617 --input - <<'EOF'
   {
     "enforcement": "active",
     "conditions": { "ref_name": { "include": ["~DEFAULT_BRANCH"], "exclude": [] } }
   }
   EOF
   ```
   In the UI the same steps are: Settings → Rules → Rulesets → Analyzer_Protect → **Target branches → Add target → Include default branch**, then set **Enforcement status → Active** and save.
2. Check:
   ```bash
   gh api repos/Zikhethele/tubeanalyzer/rulesets/25775617 --jq '{enforcement, include: .conditions.ref_name.include}'
   ```
   It should show `active` and `["~DEFAULT_BRANCH"]`, and the yellow "does not target any resources" warning in the UI should be gone.

## Rules to avoid without a bypass

- **Require a pull request before merging** would refuse every direct push to `main`. That includes:
  - commits pushed from local sessions
  - the `git push` step in `.github/workflows/publish_report.yml`

  If you want this rule, first add **Repository admin** to the bypass list, or move all changes to PRs. The SEO routine already works through PRs (`seo/<date>-<topic>`).
- **Require status checks** would make PRs wait on the Netlify preview check, which tests nothing real (see below).

## Related open items

- **Netlify:** the project `thriving-paletas-13b5b9` is linked to the repo.
  - It builds a preview for every PR and serves the repo as static files, so raw PHP shows at `/config/Config.php` and `/` returns 404.
  - Unlink it in Netlify: Project configuration → Build & deploy → Unlink repository.
- **GitHub Pages:** `.github/workflows/static.yml` publishes the whole repo to `zikhethele.github.io/tubeanalyzer` on every push to `main`.
  - That includes copies of `/reports/` with no `noindex`, which compete with yessherlock.com.
  - Nothing depends on it. To remove it:
   ```bash
   gh api -X DELETE repos/Zikhethele/tubeanalyzer/pages
   git rm .github/workflows/static.yml && git commit -m "Remove GitHub Pages workflow" && git push origin main
   ```
