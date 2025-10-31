# Fix: Constrain Admin Trix Editor Width

## Issue
- The admin `trix-editor` buttons inherited the default spacing from `trix/dist/trix.css`.
- Trix adds `margin-left: 1.5vw` to every toolbar button group except the first. With our long list of controls, that pushed the toolbar wider than the `.card` container.

## Changes
- `assets/css/main.css:1342` now forces the toolbar and button groups to wrap, applies a small `gap`, and overrides the `:not(:first-child)` margin.
- `resources/css/trix-overrides.css:8` mirrors those overrides (with `!important`) so Vite-compiled bundles ship the same fix.

## Result
The toolbar wraps to multiple rows instead of stretching the card, so the editor and its controls stay within the admin layout at all breakpoints.
