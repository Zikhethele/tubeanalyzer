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
