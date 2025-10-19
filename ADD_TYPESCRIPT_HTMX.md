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
