# Full external ticket details

For the current Whittle upload and backup, use [Overview-Ticket-Layout-Upload.md](Overview-Ticket-Layout-Upload.md). The fetcher instructions below remain applicable; its full-details ZIP has not changed.

This update supersedes the comments-only fetcher and mixed-ticket ZIPs. Whittle continues to mix local and external tickets in its ten-column main list. External tickets remain read-only; local ticket editing stays with the existing handler.

## Fetcher: stratastaffglobal.com

1. Download backups of the hosted `api.php`, `adapters.php` and `index.php` using cPanel File Manager.
2. Upload `C:\Users\Admin\.local\wts-fetch-api\wts-fetch-full-details-update.zip` into `public_html/wts-fetch-test/` (the folder containing the existing fetcher `config.php`). Extract and overwrite its three matching files.
3. Delete the uploaded ZIP. Keep the existing `config.php`, `db.php`, `auth.php` and `team.php`.

The API token hash and all database/password configuration are preserved. The source filter remains the five Whittles members plus Carly tickets mentioning them in the subject/report/replies. Detail queries run only after the ticket passes that filter. No source writes occur.

Single-ticket API requests (`?source=SOURCE&id=ID`) now return:

- `thread`: active original reports, replies and internal notes, with author names/emails, creation/update dates and attachment IDs. Deleted messages are excluded.
- `details`: available ticket channel, closed/last reply dates, last reply author/channel, requestor type, recipients and tags.
- `custom_fields`: defined ticket custom fields with names, types, stored values and option labels where available, including empty fields.
- `attachments`: active attachment IDs, names, image flags and creation dates. Other-ticket uploads and server paths are excluded.
- `activity`: active ticket activity threads; known field changes resolve their labels. Authentication-code changes are excluded.
- `unavailable_sections`: explicitly lists optional tables/columns absent from an older source installation.

List responses remain summaries. `include_threads=1` requests these complete detail sections for every permitted ticket. `schema_version` remains 1 for compatibility; new capability flags advertise the additive fields. The fetcher's own View page also displays the new sections and safely escapes source message content.

Attachment binaries are not copied or downloadable through this feed: database connection settings do not supply access to WordPress upload storage. The views direct users to the original source system for downloads. Technical authentication codes, IP/browser data and server paths are deliberately not exported. Some add-on fields stored outside the standard SupportCandy ticket tables may need a separate adapter.

## Whittle: whittles-ticketing.stratastaff.com

1. Use `dist/whittles-feed-backup.php` as previously instructed: upload into live `frontend/`, sign in as Super Admin, open `/whittles-feed-backup.php`, download/verify the six-file backup, then remove the tool.
2. Upload `dist/whittles-full-details-private-upload.zip` to a private folder outside the public web root. Extract into Whittle's document root containing `frontend/`, `backend/` and `.htaccess`.
3. Delete the uploaded ZIP. It includes the private feed token; do not leave it at a public URL.

The six paths are `frontend/index.php`, `frontend/external-tickets.php`, `backend/app/feed.php`, `backend/app/mixed-tickets.php`, `backend/app/layout.php`, and `backend/config/feed.local.php`. Do not replace the application's database configuration or reset/import databases.

Open Tickets and select an external row. Verify its original report, replies, notes, custom fields and attachment information against the fetcher's View page. Both sites require this upload to display the expanded data. New source tickets/comments appear on the next page load or refresh if they pass the team filter. External GET-only routes and existing role restrictions continue to apply.

## Validation and rollback

Passed 24 SQLite-backed source/merge checks, 18 feed decoder checks, and 33 isolated browser checks covering new details, mixed rows, permissions, unsafe HTML and write rejection. PHP syntax checks and ZIP allowlists also passed. Real source database queries must be checked after upload; local MySQL cannot initialize, and no database repair was performed.

To roll back, restore the three fetcher files downloaded in step 1 and the six Whittle paths in the backup manifest. Remove new files marked absent in that manifest. Database records are untouched.
