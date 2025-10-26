Short answer: don’t install everything up front. Most core dependencies are already in place; focus next on a few high‑impact fixes, then add optional libs only when you implement those features.

What I found in docs/Droid.md

Many items are already resolved in the codebase (auth/session hardening, tag rate‑limiting, timing‑safe compare). Your repo reflects this.
Optional frontend suggestions (Sortable.js, Day.js, Fuse.js, Playwright) are nice-to-haves, not blockers.
A few concrete issues remain worth tackling before adding more dependencies.
Recommended next steps

Fix RSS GUID fallback and undefined variable
File: app/Controllers/RssController.php: inside buildFeed(), $today is referenced but not defined. Also ensure GUID falls back to a canonical edition permalink (e.g., "$base/editions/{edition_date}#link-{id}") when source URL is missing.
Mailer: implement or defer cleanly
File: app/Services/Mailer.php is a stub. Either wire PHPMailer (already in composer) with env configuration or temporarily remove subscriber flows from UI until it’s ready.
Schema cleanup decision
File: scripts/migrate.sql (editions): evaluate the generated date column aliasing edition_date. Keep it (and document intent) or remove if redundant.
Optional safety refactor
File: app/Repositories/ItemRepository.php: replace the sprintf($sql, $where) pattern with a static WHERE 1=1 + concatenation to avoid future misuse confusion (current binding is safe, this is mainly for clarity).
Frontend resilience
File: resources/ts/app.ts: add a global htmx:responseError handler to show a friendly toast when XHR fails; this is a quick UX win.
Defer optional libraries until used
Sortable.js only if you decide to replace resources/ts/reorder.ts.
Fuse.js if you add client-side search in admin.
Day.js if you switch timeago to a library.
Playwright when you’re ready to invest in E2E tests.
Install checklist (only what’s needed now)

Backend: composer install (composer.json already includes feed-io, Guzzle, Twig, Monolog, Symfony Cache, etc.).
Frontend: npm install && npm run build (HTMX + Alpine + plugins are already self-hosted in assets/vendor/).
If you want, I can:

Patch the RSS GUID bug and add the HTMX error handler.
Sketch a minimal PHPMailer-backed Mailer using your .env settings.
