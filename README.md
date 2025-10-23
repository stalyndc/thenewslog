# TheNewsLog.org

Initial scaffold generated from PRD and Agents guidelines.

## Getting Started

- Clone the repo and install dependencies with `composer install`.
- Copy `.env.example` to `.env` and update credentials as needed.
- Default admin login uses `admin@example.com` / `admin123` (replace `ADMIN_PASS_HASH` with your own hash via `php -r "echo password_hash('yourpass', PASSWORD_BCRYPT);"` — keep the hash wrapped in quotes to prevent env expansion).

## Local Development

- Run the built-in PHP server: `php -S 127.0.0.1:8000 -t .`.
- Visit `http://127.0.0.1:8000/` for the reader view (Daily Edition), `/editions` for the archive, and `/tags` to browse topics; `/admin/login` for the admin portal.
- Authenticated admin routes are under `/admin/*`; log in via `/admin` with the credentials configured in `.env`.

## Admin Workflow

- Inbox: `/admin/inbox` lists new items pulled from feeds (once ingestion runs).
- Curate: use `/admin/curate/:id` to craft a title/blurb, pick an edition date, and optionally publish/pin the link. Saving auto-creates the edition if needed and marks the item curated.
- Editions: review assembled sets at `/admin/edition/:date`, reorder links, pin/unpin, schedule a go-live time, or publish immediately.

## Feed Ingestion

- Seed default feeds (optional): `php scripts/seed_feeds.php` (reads `config/feeds.seed.php`).
- Trigger fetch manually: `php scripts/cron_fetch.php`.
- Production cron suggestion: `*/30 * * * * php /path/to/scripts/cron_fetch.php >> /path/to/storage/logs/fetch.log 2>&1`.
- Housekeeping cron (auto-publishes scheduled editions, future cleanup tasks): `* * * * * php /path/to/scripts/cron_housekeep.php >> /path/to/storage/logs/housekeep.log 2>&1`.

## Sitemap

- Generate sitemap: `php scripts/generate_sitemap.php > sitemap.xml` (run after publishing new editions).
- Served at `/sitemap.xml`; add to search console or robots.txt as needed.

## RSSFeeds

- Daily Edition RSS: `/rss/daily.xml` (legacy `/rss/stream.xml` redirects here)
- Feed includes the latest 20 curated links with title, link, and blurb for syndication.

## Frontend Assets

- TypeScript sources live in `resources/ts/`. Build with:
  ```bash
  npm install
  npm run build
  ```
- Built assets land in `assets/app.js`; include in deploy uploads alongside PHP files.

## Deployment Notes (Future)

- Keep local development values in `.env` (ignored by git).
- For production, create `.env.production` on the server (use `.env.production.example` as the template). Never commit real credentials.
- When packaging for Hostinger, **do not overwrite** the server’s `.env.production`; deploy code + assets only.
- Run `composer install --no-dev` locally, `npm run build`, generate `sitemap.xml`, then upload `index.php`, `.htaccess`, `assets/`, `vendor/`, etc.
- Configure Hostinger cron: `*/30 * * * * php /home/USER/public_html/scripts/cron_fetch.php >> /home/USER/logs/fetch.log 2>&1`.
- Add a second cron for housekeeping/scheduled publishes: `* * * * * php /home/USER/public_html/scripts/cron_housekeep.php >> /home/USER/logs/housekeep.log 2>&1`.
- Newsletter: plan to add an email subscribe form later (likely via MailerSend/Mailgun or exportable CSV service).

## Logs

- Application logs write to `storage/logs/app.log` (created automatically on bootstrap).
