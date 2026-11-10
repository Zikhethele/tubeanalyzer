# SEO daily log

## 2026-10-24
Red: none confirmed. Live checks could not run: the cloud sandbox's egress proxy returns 403 for yessherlock.com (all 12 live checks failed with "CONNECT tunnel failed"). Local run on `php -S` shows sitemap 404, unknown channel 200, no /reports/ header; these are Apache rewrites/headers that `php -S` ignores, not site bugs.
Changed: n/a (no baseline; no live data)
Sitemap: unknown (could not fetch)
Search Console: not connected
PR: this docs-only log PR — no code change warranted
Needs owner: allow yessherlock.com in the routine's environment network policy so the daily check can reach production.

## 2026-11-10
Red: none confirmed. All 10 live checks failed with "CONNECT tunnel failed, response 403": the sandbox egress proxy still blocks yessherlock.com (same as 2026-10-24). Not a site fault.
Changed: n/a (no live data; no baseline)
Sitemap: unknown (could not fetch)
Search Console: not connected
PR: none new — docs-only log PR #1 is still open; this entry was added to its branch
Needs owner: merge PR #1 and allow yessherlock.com and www.yessherlock.com in the routine environment's network policy. Until then the daily check cannot see production.
