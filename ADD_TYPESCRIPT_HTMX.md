Goal

Enhance TheNewsLog.org with small, maintainable interactivity using TypeScript (progressive enhancement) and htmx (server-rendered partials + AJAX), compatible with shared hosting.

| Use case                                                              | Best choice                      | Why                                                    |
| --------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------ |
| Inline pagination, filters, “load more”, in-place actions             | **htmx**                         | No SPA; server renders HTML fragments; zero build      |
| Small UI behaviors (keyboard nav, sticky header, time-ago, copy link) | **TypeScript**                   | Tiny client logic; type-safe; bundle once              |
| Drag-to-reorder edition links                                         | **TypeScript**                   | Uses HTML5 DnD + fetch; minimal JS                     |
| Inbox bulk actions + live counters                                    | **htmx** (plus a sprinkle of TS) | Submit to PHP and swap HTML; TS for keyboard shortcuts |

Recommendation: Use both: htmx for server interactions, TypeScript for micro-interactions.

thenewslog/
├── public/
│   ├── assets/
│   │   ├── app.js        # built from /resources/ts/app.ts
│   │   └── app.css       # optional
│   └── ...
├── resources/
│   ├── ts/
│   │   ├── app.ts
│   │   ├── timeago.ts
│   │   └── reorder.ts
│   └── css/ (optional)
├── app/… (PHP)
├── composer.json
├── package.json          # new
└── vite.config.ts        # or esbuild/tsup (pick one)

Hostinger compatibility

Static files (JS/CSS) are served directly—works on shared.

Build locally: npm run build → upload public/assets/*.

No Node on server, no PM2, nothing long-running.

Option A — TypeScript with Vite (simple bundle)

package.json

{
  "name": "thenewslog",
  "private": true,
  "devDependencies": {
    "typescript": "^5.6.3",
    "vite": "^5.4.8"
  },
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  }
}

vite.config.ts

import { defineConfig } from "vite";
export default defineConfig({
  root: "resources",
  build: {
    outDir: "../public/assets",
    emptyOutDir: true,
    rollupOptions: {
      input: { app: "resources/ts/app.ts" }
    }
  }
});

resources/ts/app.ts (register micro-features)

import "./timeago";
import "./reorder";

// Copy-to-clipboard for “share” buttons
document.addEventListener("click", (e) => {
  const t = e.target as HTMLElement;
  const btn = t.closest("[data-copy]");
  if (!btn) return;
  const value = (btn as HTMLElement).getAttribute("data-copy")!;
  navigator.clipboard.writeText(value);
});

// Sticky header on scroll
const masthead = document.querySelector<HTMLElement>(".masthead");
if (masthead) {
  const observer = new IntersectionObserver(
    ([entry]) => masthead.classList.toggle("is-stuck", !entry.isIntersecting),
    { threshold: [1] }
  );
  observer.observe(masthead);
}

resources/ts/timeago.ts

function timeAgo(ts: string) {
  const d = new Date(ts);
  const diff = Math.max(0, Date.now() - d.getTime());
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${mins} min ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs} h ago`;
  const days = Math.floor(hrs / 24);
  return `${days} d ago`;
}

document.querySelectorAll<HTMLElement>("[data-time]")
  .forEach(el => {
    const iso = el.dataset.time!;
    el.textContent = timeAgo(iso);
    // refresh every minute
    setInterval(() => el.textContent = timeAgo(iso), 60000);
  });

resources/ts/reorder.ts (drag to reorder edition list; posts new order to PHP)

const list = document.querySelector<HTMLElement>("[data-reorder-list]");
if (list) {
  let dragging: HTMLElement | null = null;

  list.querySelectorAll<HTMLElement>("[data-id]").forEach(item => {
    item.draggable = true;

    item.addEventListener("dragstart", () => dragging = item);
    item.addEventListener("dragover", (e) => {
      e.preventDefault();
      const target = e.currentTarget as HTMLElement;
      if (!dragging || dragging === target) return;
      const rect = target.getBoundingClientRect();
      const before = (e.clientY - rect.top) < rect.height / 2;
      target.parentElement!.insertBefore(dragging, before ? target : target.nextSibling);
    });
  });

  document.addEventListener("drop", async () => {
    if (!dragging) return;
    dragging = null;
    const ids = Array.from(list.querySelectorAll<HTMLElement>("[data-id]"))
      .map(el => el.dataset.id);
    await fetch("/admin/edition/reorder", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ids })
    });
  });
}

Twig include (layout.twig)

<link rel="preload" href="/assets/app.js" as="script">
<script defer src="/assets/app.js"></script>

Build & ship

# locally

npm i
npm run build

# upload /public/assets/* to Hostinger

Option B — htmx (no build step)

<script src="https://unpkg.com/htmx.org@1.9.12"></script>

1) Inbox pagination (server-rendered rows)

<div
  id="inbox"
  hx-get="/admin/inbox/partial?page=1"
  hx-trigger="load"
  hx-target="#inbox"
  hx-swap="innerHTML">
</div>

PHP route /admin/inbox/partial?page=N

Render only the <tbody> rows (Twig partial).

Return next/prev buttons as <button hx-get="/admin/inbox/partial?page=2" hx-target="#inbox" hx-swap="innerHTML">Next</button>.

2) Filter by feed (live)

<select
  name="feed"
  hx-get="/admin/inbox/partial"
  hx-params="serialize"
  hx-target="#inbox"
  hx-swap="innerHTML">
  <option value="">All feeds</option>
  <option value="hn">Hacker News</option>
  <option value="tc">TechCrunch</option>
</select>

3) Curate/Discard inline actions

<button
  hx-post="/admin/inbox/curate"
  hx-vals='{"id":"{{ item.id }}"}'
  hx-target="#row-{{ item.id }}"
  hx-swap="outerHTML">
  Curate
</button>
<button
  class="muted"
  hx-post="/admin/inbox/discard"
  hx-vals='{"id":"{{ item.id }}"}'
  hx-target="#row-{{ item.id }}"
  hx-swap="outerHTML">
  Discard
</button>

4) Live Stream “Load more”

<div id="stream-list">
  {% include 'partials/stream_list.twig' %}
</div>
<button
  hx-get="/stream/partial?after={{ last_id }}"
  hx-target="#stream-list"
  hx-swap="beforeend">
  Load more
</button>

Suggested Enhancements (what to build with which)

Daily Edition bar sticky on scroll → TS (class toggle)

“Updated X min ago” on Live Stream → TS (data-time)

Inbox: filter, pagination, bulk actions → htmx

Curate form: autosave draft → htmx (hx-post on change)

Edition reorder → TS DnD + fetch POST (above)

PHP endpoints to add (minimal)

GET /admin/inbox/partial → returns table rows + pager (respects feed, page)

POST /admin/inbox/curate|discard → updates item, returns replacement row (e.g., removed/curated state)

POST /admin/edition/reorder → accepts { ids: [] }, updates position column for today’s edition

(optional) POST /admin/curate/autosave → saves blurb/title drafts

Deploy steps (Hostinger)

Build locally: npm run build (creates public/assets/app.js).

Upload public/assets/* via File Manager or FTP.

Ensure cache headers:

In .htaccess under public/, enable long cache for assets:

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType text/css "access plus 1 year"
</IfModule>

Keep source TS in repo (resources/ts); only /public/assets is deployed.

Rollback safety

Keep previous /public/assets/app.js as app.prev.js for quick revert.

Version file in Twig: /assets/app.js?v={{ app_version }} (bump app_version in .env on deploy).

Notes

If you prefer zero tooling, you can skip Vite and use esbuild or tsup to bundle TS → a single app.js. Same deploy flow.

htmx doesn’t require any build step and plays perfectly with Twig/PHP.
