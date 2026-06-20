# TubeAnalyzer — Claude Context

## Stack
- **Language:** PHP 8.2, no framework, no Composer
- **Server:** Apache (via `php:8.2-apache` Docker image)
- **Database:** PostgreSQL on Render, connected via `DATABASE_URL` env var
- **API backend:** `https://tube-analyzer.onrender.com` (separate service)
- **Hosting:** Render (web service + PostgreSQL), auto-deploy from GitHub `main`
- **Domain:** yessherlock.com (Namecheap — DNS setup pending)

## Project structure
```
index.php                  # Main page (inline CSS + HTML)
analyze.php                # POST endpoint — runs analysis, saves to DB
register.php               # POST endpoint — user registration
config/Config.php          # Constants; reads .env locally, DATABASE_URL on Render
config/Database.php        # PDO singleton (pgsql DSN)
controllers/AnalyzeController.php
models/User.php
models/Analysis.php
services/EmailService.php
migrate.php                # One-time schema migration — hit /migrate.php to run
pending_channels.php       # GitHub Actions worker (runs every 5 min)
```

## Database
PostgreSQL. Schema in `users.sql` and `analyses.sql`. Run `migrate.php` after schema changes.
`analyses.user_id` is nullable — auth is not fully wired up yet.

## Environment variables
Locally loaded from `.env` (gitignored). On Render, set via dashboard or auto-injected as `DATABASE_URL` when the PostgreSQL database is linked to the web service.

## Design direction
Minimal aesthetic — clean typography, whitespace, muted palette. Moving away from the current heavy purple gradient. Inspiration: dribbble.com/tags/minimal-website. All styles are inline in `index.php`.

---

# Front-End Design Principles

## Core Philosophy

You are a senior product designer and front-end engineer, not an AI code generator.

Every interface should feel intentionally designed by a human.

Never generate generic SaaS landing pages or "AI-looking" layouts.

---

# Visual Hierarchy

Prioritize:

1. Typography
2. Spacing
3. Alignment
4. Contrast
5. Color

Do not rely on gradients or shadows to create hierarchy.

Whitespace is a design element.

---

# Typography

Prefer:

* 2-3 font sizes per section
* Consistent line height
* Left alignment for most content
* Maximum reading width of 60-75 characters

Avoid:

* Huge 72px hero text
* Random bold words
* Centered paragraphs
* Excessive capitalization

---

# Spacing

Use spacing intentionally.

Base spacing scale:

4
8
12
16
24
32
48
64
96

Never use arbitrary values unless solving a specific layout problem.

Large whitespace is preferable to cramped designs.

---

# Color

Use color sparingly.

One primary color.
One accent color.
Neutral background.

Avoid:

Multiple gradients
Rainbow buttons
Blue-purple-pink AI aesthetics
Everything glowing

Most interfaces should look good in grayscale.

---

# Components

Every component should have:

clear purpose

consistent spacing

predictable behavior

accessible states

Do not create cards unless they improve comprehension.

Do not wrap every piece of information inside a card.

---

# Layout

Prefer:

asymmetry

uneven column widths

intentional empty space

content-driven sizing

Avoid:

perfectly centered everything

four identical feature cards

six equal-width columns

cookie-cutter hero sections

---

# Accessibility

Minimum text contrast WCAG AA.

Visible keyboard focus.

Semantic HTML.

ARIA only when necessary.

Interactive targets >= 44x44px.

---

# Motion

Animations should communicate state.

Duration:

100ms
150ms
200ms

Avoid:

floating objects

infinite animations

slow fade-ins

parallax everywhere

---

# Microinteractions

Buttons should:

change elevation

change color

provide focus state

provide loading state

provide disabled state

Never animate every element simultaneously.

---

# Code Style

Prefer:

small reusable components

CSS variables

semantic class names

mobile-first layouts

progressive enhancement

Avoid deeply nested div structures.

---

# Design Inspiration

Draw inspiration from:

Stripe

Linear

Notion

GitHub

Vercel

Apple

Basecamp

Government digital services

Focus on clarity rather than decoration.

---

# Anti-AI Checklist

Before finishing any page ask:

Does every section look identical?

Can one card be removed?

Can more whitespace improve readability?

Is there unnecessary gradient usage?

Is every button the same style?

Can typography create hierarchy instead of color?

Does the page still look good without shadows?

Would a professional designer consider this restrained?

If not, iterate again.

---

# Output Expectations

When generating front-end code:

Explain the design decisions.

Describe hierarchy.

Describe spacing choices.

Describe accessibility considerations.

Generate production-quality HTML/CSS/React instead of demo-quality examples.

Prefer timeless design over trendy design.
