TheNewsLog.org – Improvement Plan (Step‑by‑Step)

This checklist turns the earlier review into actionable steps. Items checked are already completed in code.

Completed

- [x] Fix sitemap path and remove non‑existent `/contact`.
  - app/Controllers/SitemapController.php now serves `sitemap.xml` from project root.
  - scripts/generate_sitemap.php no longer adds `/contact`.
- [x] Relax env validation to allow `DB_HOST` or `DB_SOCKET`.
  - app/Bootstrap/App.php validates BASE_URL, DB_NAME, DB_USER, and host OR socket.
- [x] Guard JSON body parsing to avoid 500s on invalid JSON.
  - app/Http/Request.php wraps json_decode with try/catch and returns an empty array.
- [x] RSS channel canonical points to `/editions/{date}`.
  - app/Controllers/RssController.php.
- [x] Add `.htaccess` front‑controller rewrite.
  - Routes work on Hostinger/Apache.
- [x] Decode HTML entities for titles/authors/source names at ingest and render.
  - app/Services/FeedFetcher.php and curate page sanitization.
- [x] Backfill script to normalize existing text fields.
  - scripts/backfill_decode_titles.php (items, curated_links, editions).

Next Up (Security)

- [x] Add modern security headers + CSP (Content-Security-Policy) defaults in one place.
  - File: app/Http/Response.php – extend `applySecurityHeaders()` with:
    - `Content-Security-Policy` allowing self, fonts.googleapis.com, fonts.gstatic.com, www.googletagmanager.com for the current layout.
    - `Permissions-Policy` (e.g., camera=(), geolocation=()).
    - `Cross-Origin-Opener-Policy: same-origin`, `Cross-Origin-Resource-Policy: same-origin`.
- [x] Hide exception messages in production.
  - File: index.php – render generic 500 and log with Monolog; do not echo exception text.
- [x] Session hardening for admin.
  - File: index.php – set `cookie_samesite` to `Strict` for admin, and ensure ini settings `session.use_strict_mode=1`, `session.use_only_cookies=1`.

Next Up (Correctness/Content)

- [x] Strip URL fragments before hashing/normalizing to avoid dupes (`#...`).
  - app/Helpers/Url.php drops fragments; new script to backfill existing rows.
  - Backfill once: `php scripts/backfill_normalize_urls.php`

- Next Up (SEO/Perf)

- [x] Add `<link rel="canonical">` per page and per‑edition.
  - app/Views/layout.twig with `canonical_url`; controllers now set it.
- [x] Use `Response::cached()` for public endpoints (home, edition show, tags) and cache headers for RSS.
  - HomeController, EditionArchiveController, TagController, RssController.
- [ ] Optional: meta descriptions for edition/tag pages.

Operational Notes

- Backfill once (safe to rerun):
  - `php scripts/backfill_decode_titles.php`
- Regenerate sitemap after publishing new editions:
  - `php scripts/generate_sitemap.php > sitemap.xml`

Step Execution Order

1) Security headers + hide exception details.
2) URL fragment stripping for duplicate prevention.
3) Canonical tags and public caching for SEO/Perf.

Acceptance Criteria (for each step)

- Headers: Pages include CSP, HSTS stays, and other headers; no mixed‑content/CSP console errors.
- Exceptions: in production, generic 500 page with no stack/exception string.
- URL hashing: ingesting `https://site/article#anchor` and `https://site/article` yields one item.
- Canonical: home/edition/tag pages return canonical link tag; RSS uses edition link; legacy `/rss/stream.xml` already 301s.
- Cache: HTML endpoints set small `Cache-Control` max‑age; RSS sets appropriate caching.

Use this file as the running checklist. We’ll mark each as completed after merge.
