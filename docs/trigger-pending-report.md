# Manually triggering a pending "Status" report email

The retention/prospect campaign mailers and this worker are two different
systems — this doc is about the **core product flow**: a visitor submits a
channel on the site, `analyze.php` inserts an `analyses` row with
`analyzed = 0` (shown as **pending** on `admin.php`), and normally has to wait
for the next `pending_channels.php` cron fire to get processed and emailed.

That cron is configured `*/5 * * * *` but GitHub Actions throttles frequent
schedules in practice — real gaps have been ~60–70 minutes (see
[`github-actions-debugging.md`](./github-actions-debugging.md)). Rather than
wait, trigger the worker on demand with `gh`.

## What actually sends the email

Understanding the chain matters for reading the logs correctly:

1. `pending_channels.php` (this repo) — queries up to `BATCH_SIZE` (5) rows
   where `analyzed = 0`, and for each one calls the external FastAPI backend's
   `/channel-info` endpoint.
2. The FastAPI backend (`~/Sites/fastapi_app`, deployed at
   `tube-analyzer.onrender.com`) does the actual analysis, saves the HTML
   dashboard, and — inside that same request — calls `send_emails()`
   (`youtube_utils.py:73`), which hits the Resend API directly.
3. Back in `pending_channels.php`, the JSON response is stored into the row
   and `analyzed` is flipped to `1` — regardless of whether the email inside
   step 2 succeeded. **A row moving out of "pending" does not by itself prove
   the email was delivered** — check the FastAPI service's own Render logs
   (not the GitHub Actions log, and not the PHP web app's logs) for the
   `Email sent to [...]` / `Email failed: ...` line.

## Steps

### 1. Check which rows are actually pending

Confirm via `admin.php`, or query the DB directly if you have `DATABASE_URL`
locally:

```bash
psql "$DATABASE_URL" -c "SELECT id, channel_name, email, created_at FROM analyses WHERE analyzed = 0 ORDER BY created_at ASC;"
```

### 2. Trigger the worker

```bash
gh workflow run "TubeAnalyzer Worker" --repo Zikhethele/tubeanalyzer
```

This queues a `workflow_dispatch` run — same job as the cron, just fired now.

### 3. Find the run and wait for it to finish

`gh workflow run` doesn't print the new run's ID, so grab it from the list
(it'll be the newest `workflow_dispatch` row):

```bash
gh run list --workflow="TubeAnalyzer Worker" --repo Zikhethele/tubeanalyzer --limit 3
```

Then poll until it's done (avoid naming the loop variable `status` — it's a
read-only shell variable in some shells):

```bash
run_id=<paste-the-id>
for i in $(seq 1 20); do
  s=$(gh run view "$run_id" --repo Zikhethele/tubeanalyzer \
        --json status,conclusion -q '.status + " " + (.conclusion // "null")')
  echo "$s"
  [[ "$s" != in_progress* && "$s" != queued* ]] && break
  sleep 6
done
```

Expect ~60–90 seconds — the script wakes the FastAPI free-tier instance
before processing (`pending_channels.php:51-63`), and each row can take
several seconds against the live YouTube API.

### 4. Read the log to confirm each row actually processed

```bash
gh run view "$run_id" --repo Zikhethele/tubeanalyzer --log \
  | grep -E "Processing|Requesting|Done|failed|No pending|Worker finished"
```

Look for one `[<id>] Requesting: <channel>` → `[<id>] Done — <channel>` pair
per row. A row that logs `Requesting` but never logs `Done` means the FastAPI
call itself failed (HTTP error or bad JSON) — that row stays `pending` and
will be retried on the next run.

### 5. If there are more than 5 pending rows

`BATCH_SIZE` is 5 (`pending_channels.php:20`), so with e.g. 6+ pending rows
(as in the screenshot with #38–41 plus any older ones), one trigger only
clears the oldest 5. Re-run step 2 again to pick up the rest — the query is
always `ORDER BY created_at ASC`, so it works through the backlog oldest-first.

### 6. Confirm the email itself, not just the DB flip

Since step 3 above (the row flipping to `analyzed = 1`) happens independent of
whether the email succeeded, the only way to confirm delivery is either:
- the recipient actually receiving it, or
- checking the FastAPI backend's own Render service logs for that request's
  `Email sent to [...]` / `Email failed: ...` line — this is a *different*
  Render service and a *different* log stream than anything `gh run view`
  shows you, since the GitHub Actions log only captures `pending_channels.php`'s
  own stdout, not what the remote FastAPI process printed on its own dyno.
