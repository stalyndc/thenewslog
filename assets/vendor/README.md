Place self-hosted third-party scripts here to avoid external CDNs:

- htmx.min.js (tested with 1.9.x)
- alpine.min.js (tested with 3.x)

After adding these files, the app will load them from `/assets/vendor/` and only fall back to the CDN if the files are missing. Once verified in production, you can remove `https://unpkg.com` from the CSP `script-src` directive in `app/Http/Response.php`.

