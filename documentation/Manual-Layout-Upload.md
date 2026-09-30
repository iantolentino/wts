# Manual layout upload

Authenticated live inspection on 2026-09-29 confirmed that Whittle is still serving an older Tickets page and stylesheet:

- Heading `All tickets` and the old read-only introduction remain.
- The body lacks `reference-layout`, and the compact `ticket-search-toolbar` is absent.
- The loaded CSS URL ends in `ticket-overview.css?v=20260929` and returns 7,377 bytes. Current local CSS is 14,064 bytes and the shared layout requests `?v=20260929-layout2`.

This explains why hard refresh does not display the prepared layout. The hosted PHP pages, shared layout and stylesheet must be replaced together. No new UI code was needed for this diagnosis. Whittle authenticated page reads and stylesheet requests only; no ticket or setting changes were submitted.

Run `tools/build-manual-layout-upload.ps1` to prepare `dist/manual-whittle-layout-update/`. Its eight files preserve the live directory structure, exclude both private configurations, and are verified against their source hashes. `UPLOAD.txt` lists the exact manual sequence and checks; `MANIFEST.json` records hashes. Neither instruction file needs to be uploaded.

Use the current nine-target backup tool first. In cPanel, find Whittle's document root containing `.htaccess`, `frontend/` and `backend/`. Upload the folder's PHP/CSS files to matching paths and overwrite. In particular, replace `frontend/index.php`, not an index.php placed beside frontend/. Root routing already maps `/index.php` to that frontend page.

After upload, verify Dashboard navigation, absent intro/welcome text, compact Tickets filters, and CSS `?v=20260929-layout2`. Source departments remain IT Department, HR Department, LND Department and Requisition. The previous reference layout remains tested with 145 checks and desktop/mobile review; copying these files requires no new database or configuration changes. Production layout verification remains pending this owner upload.
