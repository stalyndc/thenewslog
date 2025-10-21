# Latest Updates

## Security Hardening
- Enabled Twig auto-escaping and introduced a reusable `csrf_field()` helper to inject CSRF tokens into every admin form and HTMX request.
- Added a dedicated `Csrf` service plus controller guard helpers to validate tokens server-side.
- Strengthened the `Auth` service with session regeneration, idle timeout enforcement, and cleaner logout handling.
- Replaced raw exception messages in the curator workflow with Monolog logging to prevent information leakage.
- Converted the admin logout endpoint into a CSRF-protected POST workflow and updated the UI to submit tokenized forms.
- Ensured HTMX-based inbox actions send CSRF headers so ignore/delete operations continue functioning after the security hardening.
- Added HTMX redirect handling so inbox actions automatically refresh, preventing stale rows after deletion or ignore.

## Feed Ingestion Enhancements
- Implemented conditional fetching by persisting `ETag` and `Last-Modified` headers on feeds and reusing them for future requests.
- Added a custom FeedIo client wrapper to send `If-None-Match`/`If-Modified-Since` headers and skip processing when feeds are unchanged.
- Updated the schema (`scripts/migrate.sql`) and repository logic to store the new metadata.

## Admin Experience Improvements
- Resolved the inbox status bug by aligning item dismissal with the existing `discarded` enum value.
- Added validation and normalization for feed URLs before persistence to prevent malformed sources.
- Injected CSRF tokens into all admin templates (login, inbox actions, curation, edition management, and feed CRUD).

## Supporting Infrastructure
- Bootstrapped container bindings for the new services (CSRF, custom FeedIo client, PSR logger interface) and ensured Twig receives the shared token.
- Exposed convenient Twig globals/functions for templates and HTMX to consume.
- Added an idempotent upgrade script (`scripts/upgrade_20241021_add_feed_headers.sql`) and a legacy-safe fallback path for feeds missing the new header columns.
