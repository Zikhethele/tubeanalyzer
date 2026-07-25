# Debugging GitHub Actions workflows with `gh`

A walkthrough of the commands used to verify the campaign-mailer and pending-channel
worker were actually running correctly after the `yessherlock.com` Resend domain
was verified. No Python was involved — this is all `gh` CLI (GitHub's official
CLI, authenticated against the `Zikhethele/tubeanalyzer` repo) plus a couple of
plain bash patterns for polling and log filtering.

## 1. See what workflows exist

```bash
gh workflow list --repo Zikhethele/tubeanalyzer
```

Lists every workflow file under `.github/workflows/`, its state (`active`/
`disabled`), and its numeric ID. Useful as a sanity check before referencing a
workflow by name in later commands.

## 2. Check recent run history

```bash
gh run list --workflow="TubeAnalyzer Campaign Mailers" --repo Zikhethele/tubeanalyzer --limit 5
```

Output columns: status, conclusion, workflow name, branch, trigger
(`schedule` vs `workflow_dispatch`), run ID, duration, timestamp.

This is the first thing to check when something "isn't working" — it tells you
*whether the workflow is firing at all* and on what cadence, before you dig into
why a particular run did or didn't do what you expected.

**What this caught in practice:** the worker's cron is configured as `*/5 * * * *`
(every 5 minutes) in `worker.yaml`, but `gh run list` showed actual runs spaced
~60–70 minutes apart all day. That's GitHub Actions silently throttling
high-frequency scheduled workflows — a known platform limitation, not a bug in
the repo. You'd only discover that by looking at real run timestamps, not the
cron expression in the YAML.

## 3. Read a specific run's full log

```bash
gh run view <run-id> --repo Zikhethele/tubeanalyzer --log
```

Dumps the entire log for every step of that run. For a quick run this is fine
to read directly; for anything longer, pipe it through `grep` for the lines
that actually matter:

```bash
gh run view 29911579457 --repo Zikhethele/tubeanalyzer --log \
  | grep -E "Considering|sent|No prospects|failed|\[failed\]|HTTP"
```

This is how the Resend "domain not verified" failure was confirmed word-for-word
(`HTTP 400: {"statusCode":400,"message":"The associated domain with your API key
is not verified..."}`) even though the *job* still reported `success` — the PHP
script caught the API error internally and exited 0, so `gh run list` alone
would have shown a green check despite zero emails sending. Job-level success
and business-logic success are different things; always check the log body,
not just the conclusion, when a workflow is meant to *do* something external.

## 4. Manually trigger a workflow on demand

```bash
gh workflow run "TubeAnalyzer Campaign Mailers" --repo Zikhethele/tubeanalyzer
```

Requires the workflow to have `workflow_dispatch:` in its `on:` block (both
`mailers.yaml` and `worker.yaml` already do). This is how both fixes were
confirmed same-session instead of waiting for the next scheduled fire —
trigger it right after a config change (DNS verification, secret update, etc.)
and read the resulting log immediately.

## 5. Poll a run until it finishes

`gh workflow run` returns instantly (it just queues the run) — it doesn't wait
for completion or even print the new run's ID reliably. The pattern used to
wait for a just-triggered run:

```bash
for i in $(seq 1 15); do
  s=$(gh run view <run-id> --repo Zikhethele/tubeanalyzer \
        --json status,conclusion -q '.status + " " + (.conclusion // "null")')
  echo "$s"
  if [[ "$s" != in_progress* && "$s" != queued* ]]; then
    break
  fi
  sleep 6
done
```

Notes:
- `--json status,conclusion -q '...'` uses `gh`'s built-in `jq` query flag to
  print just the two fields you care about, instead of a full JSON blob.
- `conclusion` is `null` while a run is in progress, hence `// "null"` — jq's
  alternative operator swaps in a literal string so the shell comparison
  doesn't choke on an empty value.
- Avoid naming the loop variable `status` — it's a shell read-only variable in
  some shells and will error with `read-only variable: status`. Use `s` or
  anything else instead.
- To find the run ID right after triggering, re-run `gh run list` with a small
  `--limit` and grab the newest `workflow_dispatch` row — usually visible
  within a few seconds of triggering, before it needs a poll loop at all.

## 6. Cross-check against application state

The GitHub Actions logs only tell you what the *script* did. To confirm the
end-to-end effect, cross-reference against:
- The app's own view of the data — here, `admin.php`'s "Recent analyses" table,
  to see which rows were still `pending` vs which had flipped after a worker run.
- The actual inbox, for the mailer — a workflow log saying `[sent]` is Resend
  accepting the send request, not proof the email was delivered/legible.

Neither of those tells the full story alone: logs show intent and immediate
API response, application state shows persisted effect, and delivery confirms
the outcome reached the real world.
