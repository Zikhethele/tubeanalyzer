# gh commands — SEO workflow

GitHub CLI commands for the daily SEO check (`.github/workflows/seo-daily.yaml`)
and its secrets. Run them from the repo root. For the full deploy session,
including `curl`, `php` and `git`, see `docs/seo-deploy-commands-2026-10-17.md`.

## Run in the 2026-10-17 deploy session

In order:

```bash
gh secret list                    # APP_SECRET, DATABASE_URL, RESEND_API_KEY; SEO_REPORT_EMAIL missing
gh pr list                        # no open PRs

gh secret set SEO_REPORT_EMAIL --body <email>
gh secret list                    # SEO_REPORT_EMAIL now listed

gh run list --limit 3             # recent runs, checked while confirming the push

gh workflow run seo-daily.yaml    # start the first run by hand
gh run list --workflow seo-daily.yaml --limit 1 --json databaseId,status -q '.[0].databaseId'
                                  # → 39057180966
gh run watch 39057180966 --exit-status
                                  # exit 0
gh run view 39057180966 --log | grep -E "\[(pass|fail|warn)\]|checks:"
                                  # 20 checks: 1 failing, 2 warnings
gh run view 39057180966 --log | grep -i -E "email|resend|stored|rows|insert"
                                  # "Report emailed."
```

## Day-to-day reference

Start the daily check by hand and wait for it:

```bash
gh workflow run seo-daily.yaml
sleep 5
gh run watch "$(gh run list --workflow seo-daily.yaml --limit 1 --json databaseId -q '.[0].databaseId')" --exit-status
```

Results of the latest run:

```bash
gh run view "$(gh run list --workflow seo-daily.yaml --limit 1 --json databaseId -q '.[0].databaseId')" --log \
  | grep -E "\[(pass|fail|warn)\]|checks:"
```

Recent runs:

```bash
gh run list --workflow seo-daily.yaml --limit 7
```

Pull requests from the SEO agent (branches named `seo/<date>-<topic>`):

```bash
gh pr list --search "head:seo/"
gh pr view <number>
gh pr diff <number>
```

Optional secrets, not set yet:

```bash
gh secret set PSI_API_KEY                                  # prompts for the value; stops PageSpeed 429s
gh secret set GSC_SA_JSON < path/to/service-account.json   # Search Console service account
```
