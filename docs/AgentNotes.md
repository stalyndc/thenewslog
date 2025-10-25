# Agent Notes — Alpine.js + Admin Enhancements

These notes summarize recent changes that fixed tag autocomplete, improved delete confirmations, and adopted Alpine.js for small, maintainable UI behaviors. Use this as shared context for future agents.

## Goals
- Reliable in-admin tag autocomplete and validation on the curate form
- Safer, consistent delete confirmations across the app
- Replace bespoke JS with lightweight Alpine.js where it simplifies the UI

## What Changed (High Level)
- Added an Alpine.js component for the tags field on the curate form with chips UI, server-driven suggestions, and a JSON fallback.
- Centralized confirm dialog using Alpine (replaces multiple `confirm()` calls). Fixes delete actions not submitting reliably.
- Switched mobile drawer to Alpine and removed redundant TS wiring.
- Added inline Alpine validations (blurb length counter, schedule guard).
- Small CSS additions: `[x-cloak]`, chip styles, modal/backdrop visuals.

## Tags Autocomplete — Design & Fixes

User symptom: Typing partial terms (e.g., `am`, `amaz`) sometimes produced no suggestions, even though matching tags existed (e.g., `Amazon`).

Root cause(s):
- The new Alpine component originally tried to parse the HTML returned by the server partial; parsing could fail or produce an empty set in edge cases.
- If the server suggestion route returned an empty partial or transient error, the component showed nothing.

Implementation fixes:
- Keep server-rendered HTML as-is and inject it directly (no client-side parsing).
- Add a resilient fallback to a JSON list of all tags for client-side filtering when the partial is unavailable or empty.
- Exclude already-selected tags (case-insensitive) from suggestions to prevent duplicates.

Endpoints used:
- `GET /admin/tags/suggest` → returns Twig partial HTML of suggestions (existing route).
- `GET /admin/tags/validate` → returns Twig snippet of feedback (existing route).
- NEW: `GET /admin/tags/all` → returns JSON `{ tags: string[] }` (added for fallback).

Key files:
- app/Views/admin/curate.twig:61 — Alpine form state (title, blurb, date) and `tagsField()` component.
- app/Controllers/Admin/TagController.php:all — new action returning JSON list of tags.
- app/Bootstrap/App.php:265 — register `/admin/tags/all` route.
- assets/css/main.css:19 — `[x-cloak]`; chip styles; modal styles.

Component behavior (tagsField):
- Options: `{ initial, suggestUrl, validateUrl, allUrl }`.
- Suggestion flow:
  1. Try server partial: `GET suggestUrl?tags=<active>&tags_full=<csv>&existing=<csv>`.
  2. If error/empty, fetch `allUrl` once and client-filter: `includes(term)`.
  3. Exclude existing chips; display up to 8.
- Commit tag: Enter or comma `,` inserts the active token as a chip.
- Hidden input `name="tags"` always reflects chips + active token as CSV (backend-compatible).
- Validation: fetches HTML from `/admin/tags/validate` and injects into the feedback container.

## Confirm Modal — Reliable Deletes

User symptom: Deletes sometimes did not execute after the new confirm modal.

Root cause:
- Using `requestSubmit()` in a delegated handler could be intercepted by other submit listeners, preventing the POST.

Fix:
- Submit forms from the confirm handler via `form.submit()` (bypasses extra listeners). Fallback to `requestSubmit()` if needed.

How to use:
- Add `data-confirm="Your message"` to any `<form>` or clickable trigger.
- The global handler in the layout opens the modal and runs the confirmed action.

Key files:
- app/Views/layout.twig:25 — `appShell()` Alpine controller wraps the page and handles confirm+drawer.
- app/Views/home.twig:49 — delete form now uses `data-confirm`.
- app/Views/admin/feeds.twig:120 — delete feed uses `data-confirm`.
- app/Views/admin/curate.twig:154 — delete item uses `data-confirm`.
- app/Views/admin/partials/inbox_rows.twig:21 — inbox delete uses `data-confirm`.

## Mobile Drawer via Alpine

Changes:
- Moved drawer open/close logic to Alpine with `x-show` + transitions and body class locking.
- Removed TS functions that cloned links and managed drawer state.

Benefits:
- Less custom code; clearer, declarative toggling.
- Keeps nav markup in one place.

Key files:
- app/Views/layout.twig:25 — Alpine `appShell()` with `mobileOpen` state; drawer/overlay use `x-show` and `.is-open` classes.
- resources/ts/app.ts:392 — removed the previous drawer helpers and binds.

## Inline Validations & Micro-UX

Curate form:
- Title/Blurb/Date Alpine state; submit disabled unless valid.
- Blurb counter with 180-char soft limit and hint.

Edition scheduling:
- Disable "Schedule" until a datetime is selected; subtle hint under the form.
- File: app/Views/admin/edition.twig:64.

## CSS Additions
- `[x-cloak] { display: none !important; }` to prevent FOUC.
- Chip UI styles (`.chips`, `.chip`) and modal styles (backdrop + card).
- Body lock class for modals: `.is-modal-open`.

## How Alpine.js Helps Here
- Keeps interactivity local to templates; no heavy framework.
- Replaces imperative DOM code with declarative bindings (`x-data`, `x-show`, `x-model`).
- Enables composable widgets (tagsField, confirm modal, drawer) with tiny surface area.
- Works alongside HTMX and existing TS modules without contention.

## Testing Notes
1) Hard refresh (Shift+Reload) after deployment to bust caches.
2) Curate page: typing partials (e.g., `am`, `amaz`) should show matches (Amazon, …). Selecting a chip removes it from the suggestion list.
3) Delete flows: Home, Inbox, Curate, Feeds all show the modal and proceed on Continue.
4) Schedule form: "Schedule Edition" disabled until a date-time is chosen.

## Future Enhancements (Optional)
- Keyboard navigation for suggestions (Up/Down + Enter).
- Show already-selected tags in suggestions as disabled items.
- Minimum query length for suggestions to reduce noise (e.g., `>= 2`).
- Debounced server + local caching for suggestions.

## Modified Files (Pointers)
- app/Views/admin/curate.twig:61
- app/Views/admin/edition.twig:64
- app/Views/admin/feeds.twig:120
- app/Views/admin/partials/inbox_rows.twig:21
- app/Views/home.twig:49
- app/Views/layout.twig:25
- app/Controllers/Admin/TagController.php:all
- app/Bootstrap/App.php:265
- assets/css/main.css:19
- resources/ts/app.ts:392

## Rollback Strategy
- Revert the Alpine modal by removing `appShell()` and `data-confirm` attributes; restore per-button `confirm()` handlers.
- Restore the original tags input by removing the Alpine tagsField wrapper and re-adding `data-tags-input` for the TS helper.

