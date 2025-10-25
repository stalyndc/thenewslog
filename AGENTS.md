# AGENTS.md — TheNewsLog.org

## Goals
Build a PHP + Composer site on Hostinger that ingests RSS, lets an editor curate links, and publishes a daily edition.

## Tech Constraints
- PHP 8.1+ on Hostinger shared hosting.
- Composer packages only (no big frameworks).
- Twig for templating, Guzzle for HTTP, Feed-io or SimplePie for RSS.
- MySQL for storage; Symfony Cache (filesystem) for caching.

## Definitions
- Item: raw ingested record from an RSS feed.
- Curated Link: editor-approved item with custom title/note/tags.
- Edition: daily set of curated links.

## Tasks (Do in order)
1. **Scaffold Project**
   - Create folders exactly as in README’s tree.
   - Add `composer.json` with required packages & PSR-4 autoload.
   - Implement `public/index.php` front controller and a tiny Router.

2. **Database**
   - Create schema from PRD (feeds/items/curated_links/editions/subscribers).
   - Provide `/scripts/migrate.sql` and execute once.

3. **Ingestion**
   - Implement `Services/FeedFetcher.php`:
     - Fetch feeds with ETag/If-Modified-Since.
     - Parse entries, normalize URL, compute url_hash.
     - Insert `items` with `status='new'`, skip duplicates.
   - Implement `scripts/cron_fetch.php` to call FeedFetcher across active feeds.

4. **Admin**
   - `/admin/login` session auth (bcrypt).
   - `/admin/inbox` list `items.status=new`.
   - `/admin/curate/:id` form to create `curated_links`.
   - `/admin/edition/:date` list + reorder curated links; publish toggle.
   - `/admin/feeds` CRUD for feeds.

5. **Frontend**
   - Home: show latest **published edition**; if none published yet, show a friendly placeholder.
   - Edition page: list curated links with source and one-liners.
   - Tag pages.
   - RSS endpoint: `/rss/daily.xml` (legacy `/rss/stream.xml` must 301 here).

6. **Sitemap & SEO**
   - `scripts/generate_sitemap.php` for editions/links/tags.
   - Add `<link rel="alternate" ...>` in `<head>`.

7. **Quality & Ops**
   - Add Monolog logging; error.log to `storage/logs/`.
   - 404/500 templates.
   - Ensure PSR-12 code style; small classes, no god-files.

## UI Guidelines
- Keep layout minimal; mobile-first.
- Work Sans; 18–19px body; 28–34px headlines.
- Clear affordances: “Curate,” “Publish,” “Pin.”

## Feature Flags
- `AI_SUMMARY_ENABLED` (env): when true, fetch 1-sentence suggestion via API (stub a method).
