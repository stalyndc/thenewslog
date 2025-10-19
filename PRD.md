PRD — TheNewsLog.org (PHP, Hostinger)

1) Concept

A human-curated daily log of the best links across tech, culture, business, and the web. Short, punchy headlines + one-line blurbs. Powered by RSS ingestion, de-duplication, and a simple editor queue.

2) Core User Flows (MVP)

Reader

Browse the Daily Edition (5–10 links/day).

Browse the Stream (latest curated links).

Filter by Tags (e.g., AI, Startups, Design).

Subscribe to email digest (daily/weekly).

Editor

Auto-ingest RSS → Inbox (Unreviewed).

Click to Curate: tweak title, add 1-line blurb, choose tags.

Publish immediately or queue for the next Daily Edition.

Hide/Archive or Pin featured stories.

3) MVP Features

RSS ingestion (15–60 min cadence).

De-duplication by normalized URL hash.

OG/OpenGraph extraction for site name + favicon.

Minimal, fast frontend (Bootstrap or vanilla CSS).

SEO-friendly URLs + sitemap.xml.

Lightweight admin at /admin (password login).

Email capture (export CSV for now).

4) Nice-to-Have (phase 2+)

AI assist (optional flag): summarize first sentence; suggest tags.

OPML import/export of feeds.

Notion/Slack webhook to push daily set.

Newsletter send via MailerSend/Mailgun (or RSS-to-email tool).

Reader accounts to save/bookmark.

5) Architecture Overview (PHP, no framework)

App: Modular PHP with Composer autoloading.

Templates: Twig (clean separation, cached).

HTTP: Simple front controller (public/index.php) + tiny router.

Storage: MySQL (Hostinger) for content; Filesystem cache for fetches.

Jobs: Cron (Hostinger) for RSS pull + housekeeping.

Config: .env (Symfony Dotenv).

Key Composer Packages

guzzlehttp/guzzle — HTTP client (feed fetch + OG pages)

debril/feed-io or simplepie/simplepie — RSS/Atom parser

twig/twig — templating

symfony/dotenv — env vars

symfony/cache — filesystem cache

monolog/monolog — logging

ezyang/htmlpurifier — safe HTML (if needed for excerpts)

phpmailer/phpmailer — email (optional for newsletter send)

voku/portable-ascii — slug/normalization helpers

6) Data Model (MySQL)

feeds

id PK

title

site_url

feed_url (unique)

active TINYINT

last_checked_at DATETIME

fail_count INT

items (raw ingested)

id PK

feed_id FK

title

url (canonical, unique if possible)

url_hash (normalized SHA1, indexed) ← used for de-dup

summary_raw TEXT (from feed)

author VARCHAR(255)

published_at DATETIME

source_name VARCHAR(255) (from OG or host)

og_image TEXT NULL

status ENUM('new','discarded','curated') DEFAULT 'new'

Indexes: (feed_id), (published_at), (url_hash)

curated_links

id PK

item_id FK (nullable if manual link)

title_custom VARCHAR(280)

note VARCHAR(280) — your 1-line “why it matters”

tags_csv VARCHAR(255)

edition_date DATE NULL — attach to a Daily Edition

is_published TINYINT DEFAULT 0

pinned TINYINT DEFAULT 0

created_at DATETIME

updated_at DATETIME

editions

id PK

date DATE (unique)

title VARCHAR(120) NULL (e.g., “Friday Brief”)

intro TEXT NULL

is_published TINYINT DEFAULT 0

(links associated via curated_links.edition_date)

subscribers

id PK

email (unique)

created_at DATETIME

verified TINYINT DEFAULT 0

7) Ingestion Pipeline (Cron)

Fetch feeds (respect If-Modified-Since & ETag).

Parse entries; normalize URL (strip UTM, trailing slashes).

Compute url_hash → skip if exists.

Store new items as status='new'.

(Optional) fetch article HTML to pull OG title/site & favicon.

Log success/fail on each feed; backoff after repeated fails.

De-dup rules

Prefer canonical link rel if present.

Normalize host (lowercase), strip tracking query.

Hash: sha1(host + path).

8) Curation Workflow (Admin)

/admin/inbox — table of new items with:

Source, title, first sentence preview, published time

Actions: Curate, Discard

/admin/curate/:id — form:

Edit headline, add 1-line note, tags, choose edition (today/tomorrow)

Toggle Publish now or Queue

/admin/edition/:date — reorder with drag handles (htmx/alpine.js)

/admin/feeds — add/edit feeds, toggle active

/admin/subscribers — list/export CSV

Auth: single editor account for now (password hash in DB).

9) URL Structure & SEO

Home: / → Today’s Edition if published; else recent stream.

Editions: /editions/2025-10-19

Tag pages: /tag/ai

Link permalinks: /l/{id}-{slug}

Pages: /about, /subscribe, /contact

sitemap.xml (editions + permalinks + tags)

<link rel="alternate" type="application/rss+xml"> for site RSS:

/rss/editions.xml (daily)

/rss/stream.xml (every curated link)

10) Frontend (lean + fast)

CSS: Bootstrap 5 or minimal custom CSS (your call)

Typography: Work Sans (you’ve used it before)

Components:

Header w/ logo, date picker (← jump to edition)

“Today’s 7 Picks” list (title • source • 1-line note • tags)

Secondary “More links” stream

Footer: subscribe form, sitemap, credits

Accessibility: focus states, alt text, semantic lists.


11) File/Folder Structure

thenewslog/
├── app/
│   ├── bootstrap.php
│   ├── Router.php
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── EditionsController.php
│   │   ├── TagsController.php
│   │   ├── RssController.php
│   │   └── AdminController.php
│   ├── Models/
│   │   ├── Feed.php
│   │   ├── Item.php
│   │   ├── CuratedLink.php
│   │   ├── Edition.php
│   │   └── Subscriber.php
│   ├── Services/
│   │   ├── FeedFetcher.php
│   │   ├── OgExtractor.php
│   │   ├── Curator.php
│   │   └── Mailer.php
│   ├── Helpers/
│   │   ├── Url.php
│   │   └── Str.php
│   └── Views/ (Twig templates)
│       ├── layout.twig
│       ├── home.twig
│       ├── edition.twig
│       ├── tag.twig
│       ├── link.twig
│       └── admin/
│           ├── login.twig
│           ├── inbox.twig
│           ├── curate.twig
│           ├── edition.twig
│           └── feeds.twig
├── config/
│   ├── database.php
│   ├── feeds.seed.php
│   └── mail.php
├── public/
│   ├── index.php        ← front controller/router
│   ├── assets/          ← css/js/images
│   └── .htaccess        ← pretty URLs
├── scripts/
│   ├── cron_fetch.php   ← RSS ingestion
│   ├── cron_housekeep.php
│   └── generate_sitemap.php
├── storage/
│   ├── cache/
│   └── logs/
├── vendor/
├── .env
├── composer.json
└── README.md


12) Cron Jobs (Hostinger)

*/30 * * * * php /home/USER/public_html/scripts/cron_fetch.php >> /home/USER/logs/fetch.log 2>&1

0 3 * * * php /home/USER/public_html/scripts/generate_sitemap.php

(Optional) 0 7 * * * php /home/USER/public_html/scripts/send_newsletter.php

13) Security & Ops

Admin auth: hashed password (bcrypt), CSRF tokens on forms.

Rate-limit subscribe endpoint, double opt-in (or at least honeypot).

Escape all output in Twig; sanitize imported HTML with HTML Purifier if ever displayed.

Backups: Hostinger DB backups + weekly manual export.

14) Deployment on Hostinger

Create MySQL DB & user; update .env.

composer install --no-dev (locally → upload vendor/ or use Hostinger’s terminal).

Upload public/ to public_html/ (or point domain to public).

Ensure storage/cache & storage/logs are writable.

Set up cron entries in hPanel.

Seed initial feeds via config/feeds.seed.php or Admin > Feeds.

.env sample

APP_ENV=production
APP_KEY=base64:CHANGE_THIS
DB_HOST=localhost
DB_NAME=thenewslog
DB_USER=thenewslog_user
DB_PASS=********
BASE_URL=https://thenewslog.org
ADMIN_EMAIL=you@domain.com
ADMIN_PASS_HASH=$2y$10$...   # bcrypt hash
AI_SUMMARY_ENABLED=false


15) Editor Guide (Daily Ritual)

Check /admin/inbox each morning.

Curate 7–10 best links (tight titles, 1-line why).

Assign to today’s edition, reorder, Publish.

Share: copy edition URL to socials/newsletter.

(Optional) Mark a few as Pinned for the home hero.

17) Initial Feed Suggestions (seed)

Tech: The Verge, TechCrunch, Hacker News (front), Stratechery (RSS), Benedict Evans, AI newsletters.

Business: FT, Bloomberg Technology, WSJ Tech.

Design: Sidebar-style sources, Smashing Magazine, UX Collective.

Culture: The Atlantic, The New Yorker (blogs), Arts & Letters Daily.

(Add these to config/feeds.seed.php with titles + feed URLs.)

18) Milestones (1–2 weeks)

Day 1–2: Project scaffold, DB, router, Twig, basic pages.

Day 3–4: Feed fetcher + cron + inbox view.

Day 5: Curation form + create edition + publish.

Day 6: Home/edition/tag + RSS endpoints + sitemap.

Day 7: Styling pass, SEO, hPanel cron, seed feeds, soft launch.