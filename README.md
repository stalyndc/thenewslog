# TheNewsLog.org

Initial scaffold generated from PRD and Agents guidelines.

## Getting Started

- Clone the repo and install dependencies with `composer install`.
- Copy `.env.example` to `.env` and update credentials as needed.
- Default admin login uses `admin@example.com` / `admin123` (replace `ADMIN_PASS_HASH` with your own hash via `php -r "echo password_hash('yourpass', PASSWORD_BCRYPT);"` — keep the hash wrapped in quotes to prevent env expansion).

## Local Development

- Run the built-in PHP server: `php -S 127.0.0.1:8000 -t public`.
- Visit `http://127.0.0.1:8000/` for the reader view (Daily Edition), `/stream` for the live stream, `/editions` for the archive, and `/tags` to browse topics; `/admin/login` for the admin portal.
- Authenticated admin routes are under `/admin/*`; log in via `/admin` with the credentials configured in `.env`.

## Admin Workflow

- Inbox: `/admin/inbox` lists new items pulled from feeds (once ingestion runs).
- Curate: use `/admin/curate/:id` to craft a title/blurb, pick an edition date, and optionally publish/pin the link. Saving auto-creates the edition if needed and marks the item curated.
- Editions: review assembled sets at `/admin/edition/:date` (currently read-only until ordering is implemented).

## Feed Ingestion

- Seed default feeds (optional): `php scripts/seed_feeds.php` (reads `config/feeds.seed.php`).
- Trigger fetch manually: `php scripts/cron_fetch.php`.
- Production cron suggestion: `*/30 * * * * php /path/to/scripts/cron_fetch.php >> /path/to/storage/logs/fetch.log 2>&1`.

## Sitemap

- Generate sitemap: `php scripts/generate_sitemap.php > public/sitemap.xml` (run after publishing new editions).
- Served at `/sitemap.xml`; add to search console or robots.txt as needed.

## RSSFeeds

- Daily Edition RSS: `/rss/daily.xml`
- Live Stream RSS: `/rss/stream.xml`
- Both feeds include the latest 20–50 curated links with title, link, and blurb for syndication.

## Logs

- Application logs write to `storage/logs/app.log` (created automatically on bootstrap).
