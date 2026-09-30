# Mixed ticket list update

Superseded by [Full-Ticket-Details-Upload.md](Full-Ticket-Details-Upload.md), including internal notes, custom fields, attachment metadata and ticket activity. Use the new full-details ZIPs for current uploads.

This supersedes the earlier separate External tickets page. The main `index.php` now combines local Whittle and permitted source tickets in one list with exactly these columns:

ID, Title, Requestor, Status, Priority, Assignee, Assigned Department, Category, Created, Last update.

Shared search, source/status/priority/assignment/category/date filters, 25-row pagination and the main-page CSV export operate on the combined list. Local ticket links preserve the original editing and commenting permissions. External ticket links open a read-only detail view, including the original report and reply comments. External POST edits/comments are rejected with 405. The old external-list URL redirects to the main list, and its separate sidebar link is removed.

## 1. Update the fetcher

Download copies of the current hosted `api.php` and `adapters.php` first using cPanel. Upload `C:\Users\Admin\.local\wts-fetch-api\wts-fetch-comments-update.zip` and extract its two files directly inside `public_html/wts-fetch-test/` on stratastaffglobal.com. This ZIP contains **only** `api.php` and `adapters.php`; it preserves the existing token hash and does not include database connection settings or browser password files.

The update adds active `reply` comments to the original `report` thread and resolves their authors from source customers. Internal notes, logs and deleted replies are excluded. Carly's scope now checks report and reply content for the same five members.

Assignment names come from SupportCandy agents. Assigned Department uses a matching Department/Assigned Department custom field and its option labels, with an assigned agent-group label as fallback. Last update uses the source `date_updated`. If a source stores no department or label, the UI explicitly shows `Not provided` or an agent ID; it does not invent a department from the source name. Optional metadata tables missing in an older installation are handled as unavailable labels.

## 2. Back up Whittle

Use the **updated** `dist/whittles-feed-backup.php`: upload it to Whittle's live `frontend/`, sign in as Super Admin, open `/whittles-feed-backup.php`, and download/verify the ZIP. The backup now covers all six Whittle targets below. Remove the tool afterward. The old four-file backup tool is insufficient for this update.

## 3. Update Whittle

Upload `dist/whittles-mixed-tickets-private-upload.zip` into a private cPanel folder outside the public web root, and extract it into Whittle's document root (containing `.htaccess`, `frontend/`, and `backend/`). Or upload its six files individually to matching paths:

- `frontend/index.php`
- `frontend/external-tickets.php`
- `backend/app/feed.php`
- `backend/app/mixed-tickets.php`
- `backend/app/layout.php`
- `backend/config/feed.local.php`

The ZIP intentionally contains the private API token; keep it off Git, never publish it at a public URL, and delete it from hosting after extraction. Do not change `backend/config/config.local.php` or import/reset database tables. PHP cURL/ZIP and trusted certificates must remain available.

Sign in and open `https://whittles-ticketing.stratastaff.com/index.php`. Check combined rows, pagination, external comments and unchanged local editing. The earlier hosted API remains compatible during upload ordering; until the fetcher update is deployed, Whittle shows a notice about missing comment/date/assignment capabilities.

## Behavior and checks

Each page request fetches current source data. IDs retain a visible source label and independent source+ID identity; overlapping local/external IDs are never merged or sent to the local editing handler. Team Members retain their own local-ticket scope and cannot access the shared source list/details. First-login password changes and existing ticket/export permissions still apply.

No source records are modified. External rows are not inserted into the local database, so dashboard totals and the separate Reports page remain local. The main Tickets CSV now includes mixed rows. Local Last update includes the latest local comment timestamp. If a source refresh fails, an explicit warning labels the local-only list and mixed CSV export is blocked to prevent an apparently complete partial export.

Validation: 16 SQLite-backed adapter/merge checks, 32 isolated browser checks using real renderers and auth helpers with fixture account/database rows, 19 updated backup checks, plus PHP lint. The current hosted API still returns 39 tickets in live read checks. The new source metadata/reply queries and the mixed live page require verification after owner upload; this environment's local MySQL cannot initialize its plugin table, and no repair was performed.

Rollback: restore the six files marked backed_up in the Whittle backup manifest and remove any new file marked absent. Restore the old fetcher `api.php` and `adapters.php` copies if necessary. Existing ticket/staff database data is unaffected.
