# Social media campaign — yessherlock.com

Four-week organic launch campaign. Written 2026-10-12.

Assumptions (change these and the plan shifts): no ad budget, one person,
about 30–45 minutes a day, English-language audience.

## Goal

One number: **channel analyses submitted per week**. Target 300 a week by week 4.

Secondary: 150 new accounts, 500 followers on the main account, and 200+
indexable channel pages (every analysis from the campaign also adds a page for
search).

## Before launch

Three things on the site will undercut the campaign if left as they are.

1. **Link previews are bare.** There is no `og:image`, so every shared link
   shows as a text-only card. Ship the Phase 2 items of the SEO plan (preview
   image, Sherlock titles) first.
2. **Brand name.** The site still says "TubeAnalyzer". Posts below say
   "Sherlock". Launch after the rename, or the click lands on a different name.
3. **Engagement figure was wrong on some pages.** MKBHD's page showed 2224.5%
   average engagement. Most likely cause: videos with no view count yet.
   Fixed in `channel.php` (not yet deployed); check that page after deploy
   before screenshotting anything.

Also needed: cookieless analytics (Phase 4), otherwise the UTM links below
record nothing. Until then, count rows per day in `admin.php`.

Do not promote the Instagram audit yet. The backend is rate-limited and
visitors would hit an error.

## Audience

| Who | What they want | Where they are |
|---|---|---|
| Small and mid-size YouTubers (1K–100K subs) | To know if their numbers are good, and what bigger channels in their niche do | r/NewTubers, r/PartneredYoutube, X, YouTube Shorts, TikTok |
| Brands and agencies vetting creators | A quick, free check that a creator's engagement is real before paying | LinkedIn, X |
| Social media managers | Competitor numbers for a client report | LinkedIn, Facebook groups |

Lead with the first group. They are the largest, the most likely to share
their own results, and their channels become pages.

## Positioning

**One line:** Type any YouTube channel name, get its real numbers. Free, no account.

What to lean on, all true of the product today:

- Works on any public channel, not only your own. YouTube Studio cannot do that.
- No sign-up for a report. Channel name and email only.
- Every analysed channel has a public page to link to: `yessherlock.com/channel/{name}`.
- "Sherlock" gives a voice for free: investigating channels, case files, evidence.

What not to claim: growth predictions, revenue estimates, "AI insights", or
anything about private analytics. The tool reads public data.

## Channels

| Platform | Role | Cadence |
|---|---|---|
| X | Main account. Daily data posts, replies to creators | 1 post a day, 10 replies a day |
| YouTube Shorts + TikTok | Reach. Same 20–30 second video on both | 3 a week |
| Reddit | Depth and trust. Useful answers, the tool mentioned only where it fits | 2 helpful comments a day, 1 post a week |
| LinkedIn | The brand/agency angle, from your personal profile | 2 a week |

Skip Instagram and Facebook pages for now. One person cannot keep six
channels alive, and a dead profile looks worse than none.

Handle: `@yessherlock` everywhere, matching the domain (availability not checked).

## Content pillars

1. **Case files** (about half of all posts). One well-known channel, three
   numbers, one observation, a link to its page. Easy to make from pages
   that already exist.
2. **Benchmarks.** "What is a good engagement rate?" answered with ranges
   from the site's own data. Becomes more credible as more channels are analysed.
3. **Your channel.** Invitations to check your own or a rival's. Replies with
   a creator's own numbers when they ask.
4. **Build in public.** Solo founder, built in South Africa, what shipped this
   week. Works on X and LinkedIn; ignore elsewhere.

## Four-week calendar

**Week 1 — Introduce.** Launch post on X and LinkedIn. Three case files on
the homepage example channels. First Short. Daily Reddit comments, no links yet.

**Week 2 — Compare.** Head-to-head posts (two channels in one niche). First
benchmark thread. Reddit post in r/NewTubers offering free breakdowns in the comments.

**Week 3 — Involve.** "Reply with a channel and I'll post its case file."
Quote-reply each one with the page link. Short: "I checked 10 channels under
10K subs; here's what the best ones share."

**Week 4 — Prove.** Round-up of what the campaign's analyses showed
(aggregate numbers only). LinkedIn post on vetting creators. Product Hunt or
Indie Hackers launch if weeks 1–3 produced testimonials.

Weekly rhythm on X: Mon case file · Tue tip · Wed head-to-head · Thu
benchmark · Fri build-in-public · weekend: replies only.

## Ready-to-post copy

Figures are from the live channel pages on 2026-10-12. Re-check them on the
day you post. Engagement here means (likes + comments) ÷ views, averaged
across the channel's recent videos.

**Launch (X)**

> I built a free tool that tells you the real numbers behind any YouTube channel.
>
> Type a name. Get subscribers, views per video, engagement rate and top videos.
>
> No account. No card. Any public channel, not only yours.
>
> yessherlock.com

**Case file (X)**

> Case file: Kurzgesagt
>
> 25.4M subscribers
> 3.8B views
> 375 videos
>
> That is about 10M views per video, on roughly 375 uploads in over a decade.
> Fewer, better videos is a real strategy.
>
> yessherlock.com/channel/kurzgesagt

**Head-to-head (X)**

> Veritasium: 21.0M subs, 2.7% engagement.
> Kurzgesagt: 25.4M subs, 4% engagement.
>
> Similar size, similar audience, and one gets about half again as many
> likes and comments per view.
>
> Check any two channels: yessherlock.com

**Scale check (X)**

> MrBeast has 514M subscribers and a 2.9% engagement rate.
>
> If your 2,000-subscriber channel is at 5%, your audience is more engaged
> than his. Subscriber count is not the number to watch.
>
> See yours: yessherlock.com

**Prompt (X, week 3)**

> Reply with a YouTube channel, yours or one you're curious about.
>
> I'll run it and reply with the case file.

**Reddit post (r/NewTubers; read the sub's self-promotion rules first)**

> **I'll break down the public numbers for your channel (free, no catch)**
>
> I built a small tool that pulls a channel's public stats and works out
> engagement rate and views per video. Drop your channel below and I'll reply
> with the numbers and one thing I'd look at. Happy to do competitors too.

Reply with the numbers in the comment itself. Add the link only when someone asks.

**LinkedIn (personal profile)**

> Before you pay a YouTuber to promote your product, check two numbers.
>
> Subscriber count is not one of them.
>
> 1. Views per video over the last few months. This is the audience you are
>    buying, not the one they built years ago.
> 2. Engagement rate: likes plus comments, divided by views. Under 1% on a
>    sponsored-content channel is a warning sign.
>
> I built a free tool that shows both for any channel in a couple of minutes.
> No account needed. Link in the comments.

**Short / TikTok script (25 seconds)**

> [Screen: typing a channel name]
> "This channel has 20 million subscribers. Is it actually doing well?"
> [Screen: the channel page, stats row]
> "Subscribers, total views, and this one: engagement. Anything from 2 to 5
> percent is healthy."
> [Screen: top videos list]
> "And these are the videos carrying the channel."
> [Screen: homepage]
> "It's free and works on any channel. Yes Sherlock dot com."

## Links and tracking

- Homepage with the channel pre-filled: `https://yessherlock.com/?channel=MKBHD`.
  Use this in replies so the visitor only has to add an email.
- Add UTM tags to every link, one source per platform:
  `?utm_source=x&utm_medium=social&utm_campaign=launch` (`reddit`, `linkedin`,
  `tiktok`, `shorts`).
- Weekly check, Fridays: analyses submitted, sign-ups, followers, top three
  posts by link clicks. Drop the weakest pillar each week and double the strongest.

## Rules

- Reddit: about nine helpful comments for every one that mentions the tool.
  Accounts that only post their own link get banned.
- Only post numbers taken from the tool that day. Never round up.
- Use channels as examples of what the tool shows, not as targets. No "this
  creator is failing" posts, especially about small channels.
- People who submit a channel gave an email for a report. Do not add them to
  social retargeting or tag them publicly without asking.
- If a creator asks for their page to be removed, remove it.
