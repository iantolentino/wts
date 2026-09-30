# External ticket reader upload

**Superseded:** the owner requested a single mixed ticket list. Use `Mixed-Tickets-Upload.md`, `whittles-mixed-tickets-private-upload.zip`, the updated six-file backup tool, and the fetcher comments update. The instructions below describe the earlier separate-list release.

Upload `dist/whittles-feed-private-upload.zip` to a private cPanel folder outside the public web root, then extract it into the live Whittle document root (the folder that already contains `.htaccess`, `frontend/`, and `backend/`). It is a private deployment ZIP containing the API token; keep it off Git and remove the uploaded ZIP after extraction. Alternatively, upload its four files individually to the matching locations below.

Back up the existing `backend/app/layout.php` first. The ZIP contains exactly:

For an automatic backup, upload the separate `dist/whittles-feed-backup.php` into the existing live `frontend/` folder. Sign in as Super Admin and open `https://whittles-ticketing.stratastaff.com/whittles-feed-backup.php` (use this top-level URL, not `/frontend/`). Click **Download backup ZIP**, confirm it opens and contains `MANIFEST.json`, then remove `frontend/whittles-feed-backup.php` from hosting. The tool backs up the existing versions of all four targets below and records missing/new files for rollback. A transient ZIP is created only in the protected `backend/storage/private` folder and deleted after download; PHP ZIP must be enabled. Keep the backup private because it may contain a previous API token. It does not back up or change database records.

- `frontend/external-tickets.php` — signed-in ticket list, search, source filter, pagination and conversation view.
- `backend/app/feed.php` — authenticated HTTPS reader and response validation.
- `backend/app/layout.php` — External tickets sidebar link, alongside the current local navigation.
- `backend/config/feed.local.php` — endpoint and generated API token, private and ignored by Git.

No SQL or application database configuration is included. Do not upload the private config into `frontend/`. The existing root and backend `.htaccess` rules deny web requests to `backend/`. Keep those rules active. Confirm `https://whittles-ticketing.stratastaff.com/backend/config/feed.local.php` returns 403.

After upload, sign in and select **External tickets**, or open `https://whittles-ticketing.stratastaff.com/external-tickets.php`. PHP cURL and a working trusted CA store are required. The reader verifies HTTPS certificates and does not follow redirects with the API token.

The page allows approved users with `view_all_tickets`, except Team Members whose local ticket access is restricted to their own tickets. Unauthorized users cannot call the feed through this page. It uses the same first-login password-change enforcement as the rest of Whittle.

Refreshing the page requests current data from the four source databases. Only the existing five-member/Carly filter is included. No background polling is provided. Source tickets are read-only and are not inserted into Whittle's database or included in local dashboard totals, reports or CSV exports. Source ticket numbers are preserved; identity is the combination of source and ticket ID.

Conversation markup is converted to escaped text; no remote HTML/scripts run in Whittle. Attachments, sender names and internal notes are not provided by the current API adapters; the reader shows the report conversations they expose.

If the feed cannot be reached or rejects the token, the page shows a connection error rather than an empty successful list. For rollback, restore `layout.php` and remove the other three new files. Existing ticket and staff data are unaffected.

The general cPanel package excludes `feed.local.php`; this private patch ZIP intentionally includes it for the requested upload. Never overwrite `backend/config/config.local.php` with local settings.

## Validation on 2026-09-29

PHP syntax checks and 15 feed checks passed, including authenticated reads of 39 live tickets and scoped conversation details. An isolated browser harness used the real reader, layout and permission helpers with test account rows and the live hosted API: 24 checks passed for login redirects, role denial, password-change enforcement, filtering, pagination, token secrecy, and 390/768/1440px layouts. Full local database sign-in testing was blocked by the stopped local MySQL service (plugin-table initialization failure); no repairs or account changes were made. Verify live Whittle sign-in and the new page after upload.
