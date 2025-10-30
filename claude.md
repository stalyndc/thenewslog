# Claude Brief — TheNewsLog.org

This repo implements a lightweight PHP 8.1+ app (no big frameworks) that ingests RSS feeds, lets an editor curate links, and publishes a daily edition. It targets Hostinger shared hosting. Use Composer packages, Twig templating, and MySQL. Keep code PSR‑12, small classes, and avoid god files.

## Ground Rules
- Stack: PHP 8.1+, Composer packages only; Twig, Guzzle, Feed‑io (or SimplePie), Monolog, Symfony Dotenv, optional Symfony Cache (filesystem), HTMLPurifier.
- Hosting: Shared Hostinger (no long‑running daemons). Cron runs PHP scripts.
- Storage: MySQL; filesystem for cache/logs under `storage/`.
- Security: Session auth (bcrypt), CSRF tokens, CSP nonces, secure cookies, basic rate limiting.
- Style: PSR‑12; dependency injection via our simple Container; keep classes small and focused.

## Current Status (Implemented)
- Front controller and router
  - `index.php` boots `App\Bootstrap\App` and dispatches to a tiny Router.
  - Router + DI container in `app/Bootstrap/` wire controllers and services.
- Database schema and migrations
  - Primary tables from PRD: `feeds`, `items`, `curated_links`, `editions`, `subscribers`, plus `tags` and pivot tables. See `scripts/migrate.sql`.
  - Additional upgrade scripts exist in `scripts/upgrade_*.sql` and utilities like `scripts/add_indexes.php`.
- Ingestion
  - `app/Services/FeedFetcher.php` uses Feed‑io + a conditional Guzzle client to respect `ETag` and `If-Modified-Since`.
  - URLs are normalized, `url_hash` computed (SHA‑1), duplicates skipped. New records insert into `items` with `status='new'`.
  - Cron entry: `scripts/cron_fetch.php`.
- Admin
  - Session auth at `/admin/login` with CSRF and basic rate limiting.
  - Inbox `/admin/inbox` lists `items.status=new` with HTMX partial refresh and polling.
  - Curate `/admin/curate/:id` creates `curated_links`, attaches to editions, and marks items curated.
  - Editions `/admin/edition/:date` reorder, pin, publish/schedule.
  - Feeds `/admin/feeds` CRUD and refresh.
- Frontend
  - Home shows the latest published edition; archives under `/editions`; tag pages under `/tags`.
  - RSS at `/rss/daily.xml` with legacy `/rss/stream.xml` redirect.
- SEO & Ops
  - `scripts/generate_sitemap.php` generates `sitemap.xml` at repo root.
  - Global `<link rel="alternate" type="application/rss+xml">` in `app/Views/layout.twig`.
  - Monolog logs to `storage/logs/app.log`. Health endpoint `/healthz` (short‑circuits bootstrap).

## Key Paths
- Front controller: `index.php`
- Bootstrap/DI/Router: `app/Bootstrap/App.php`, `app/Bootstrap/Router.php`, `app/Bootstrap/Container.php`
- Controllers: `app/Controllers/...` (Admin and Frontend)
- Services (ingestion, auth, etc.): `app/Services/...`
- Repos (DB access): `app/Repositories/...`
- Views (Twig): `app/Views/...`
- Config: `config/database.php`, `.env*`
- Scripts: `scripts/*.php`, `scripts/*.sql`

## Environment
- Copy `.env.example` ➜ `.env` (or `.env.production` on server). Required vars: `BASE_URL`, `DB_*`, `ADMIN_EMAIL`, `ADMIN_PASS_HASH`.
- Example: generate bcrypt hash with `php -r "echo password_hash('yourpass', PASSWORD_BCRYPT);"` and set `ADMIN_PASS_HASH='...hash...'`.

## Data Model (summary)
- feeds(id, title, site_url, feed_url UNIQUE, active, http_etag, last_modified, last_checked_at, fail_count)
- items(id, feed_id FK, title, url, url_hash UNIQUE, summary_raw, author, source_name, og_image, published_at, status ENUM('new','discarded','curated'))
- curated_links(id, item_id FK NULL, external_url, title, title_custom, blurb, note, source_name, source_url, tags_csv, edition_date, is_published, is_pinned, position, curator_notes, published_at)
- editions(id, edition_date UNIQUE, slug UNIQUE, title, intro, status ENUM('draft','scheduled','published'), is_published, published_at, scheduled_for)
- tags, curated_link_tag (pivot)
- subscribers(id, email UNIQUE, verified)

## Ingestion Details
- Conditional fetch: `app/Services/Feed/ConditionalClient.php` injects `If-None-Match` using stored `http_etag`; `If-Modified-Since` via Feed‑io using `last_modified`.
- On 304: update feed metadata (`http_etag`, `last_modified`, `last_checked_at`).
- On 200: iterate entries, normalize canonical URL, compute `url_hash`, insert new `items` with `status='new'`.

## Admin Flow
1) Inbox: triage `items.status=new` ➜ delete/ignore/curate.
2) Curate: craft title + 1‑line blurb, tags, assign an edition date, publish/pin options.
3) Edition: reorder and publish or schedule; cron can flip scheduled editions.

## Frontend
- Latest published edition on `/` with fallback placeholder if none.
- Edition page lists curated links with source and blurbs; tag pages aggregate by tag.
- RSS `/rss/daily.xml` includes latest edition items; daily channel link points to the edition page.

## Scripts
- `scripts/migrate.sql` — initial schema. Run once.
- `scripts/seed_feeds.php` — optional seed from `config/feeds.seed.php`.
- `scripts/cron_fetch.php` — ingest active feeds.
- `scripts/cron_housekeep.php` — scheduled publish and tidy tasks.
- `scripts/generate_sitemap.php` — emit sitemap to stdout (redirect to `sitemap.xml`).
- Index/encoding checks: `scripts/check-lint.php`, `scripts/test-encoding.php`.

## Build/Run
- Composer: `composer install`; `composer ci` runs basic validation/lint.
- Local server: `php -S 127.0.0.1:8000 -t .` then visit `/`.
- Assets: TypeScript under `resources/ts/` → built to `assets/app.js` via `npm install && npm run build`.

## Constraints & Conventions
- No frameworks (Laravel/Symfony full‑stack). Prefer small, explicit classes.
- Use Twig for templates; never echo raw user input without escaping. See `app/Views/*.twig`.
- DB writes go through repositories; keep SQL parameterized.
- Keep routes in `App::registerRoutes()` coherent; prefer controller methods over closures for testability.
- Logs to `storage/logs/app.log`; ensure `storage/` is writable on Hostinger.
- Security headers are set in `app/Http/Response.php` (CSP nonce available as `csp_nonce` in Twig).

## Feature Flag
- `AI_SUMMARY_ENABLED` (env): gate any AI calls. Stub exists; integrate suggestions non‑blocking and cache results when implemented.

## Open Tasks / Next Steps
- Edition scheduling polish: ensure cron flips `scheduled` ➜ `published` and homepage cache headers reflect publish.
- Feed health dashboard: surface `last_checked_at`, `fail_count`, success/fail trend.
- Ops docs: cron snippets, seeding steps, deploy checklist (Hostinger).
- UI tightening per `next.md` (spacing, active nav glow, hover states, touch targets).
- Optional: independent blog posts, search, analytics, collections (see `next.md`).

## Hostinger Deployment Notes
- Don’t run daemons. Use cron:
  - `*/30 * * * * php /home/USER/public_html/scripts/cron_fetch.php >> /home/USER/logs/fetch.log 2>&1`
  - `* * * * * php /home/USER/public_html/scripts/cron_housekeep.php >> /home/USER/logs/housekeep.log 2>&1`
- Upload code + `vendor/` + built assets. Keep `.env.production` server‑side.
- `.htaccess` routes everything to `index.php`; `/healthz` is served by `healthz.php` without full bootstrap.

## Quick Orientation Links
- Router and route map: `app/Bootstrap/App.php`
- Ingestion: `app/Services/FeedFetcher.php`
- Admin controllers: `app/Controllers/Admin/*`
- Frontend views/layout: `app/Views/layout.twig`, `app/Views/home.twig`
- DB access: `app/Repositories/*`
- Schema: `scripts/migrate.sql`

If you add or modify features, keep them aligned with AGENTS.md and PRD.md, prefer small, well‑named classes, and update scripts/docs where relevant.

