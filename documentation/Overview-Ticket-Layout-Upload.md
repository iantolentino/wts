# Overview totals and ticket layout update

Superseded by [Dashboard-Layout-Departments-Upload.md](Dashboard-Layout-Departments-Upload.md), which applies the owner-requested CNG composition while retaining Whittle colors and maps source departments.

Use `dist/whittles-overview-ticket-layout-private-upload.zip`. This replaces the earlier Whittle mixed/full-details ZIPs and includes their existing feed connection.

## Changes

Overview now uses the same permitted local-plus-external rows as Tickets. It shows total tickets, every status, urgent active work, recent mixed tickets, a status ring, source bars, and six creation-month bars split by current active/closed status. Status and source counts link to matching ticket filters. New/Open and Resolved/Closed share the existing status mappings; unfamiliar source statuses get their own counts and remain included in the total. Deleted local tickets are excluded.

Staff totals remain available in a collapsible section for users with staff permissions. Team Members see their own local tickets. If the external feed fails, Overview clearly shows local-only totals and hides unavailable external source bars; it never presents the partial result as the complete total.

Local and external ticket views now have a full-width title/status header, readable conversation cards, separate details and attachment panels, and a mobile layout. Local issue/comment line breaks are preserved. External paragraphs, emphasis, lists, tables and safe absolute links retain formatting. Source scripts, styles, forms, embeds, remote images and unsafe link attributes are removed. External notes remain clearly labeled; external edits/comments still return 405. Local edit/comment/delete handlers, CSRF and version checks are preserved.

Totals refresh on page load or Refresh. The monthly graph groups by creation date and current status; it does not claim to measure historical closure dates or status transitions. Attachment downloads from external systems still use the original source system. Reports & exports is a separate local-only page; the main Tickets CSV continues to include mixed rows.

## Back up and upload

1. Upload the updated `dist/whittles-feed-backup.php` into Whittle's live `frontend/`. Sign in as Super Admin and open `/whittles-feed-backup.php`. Download and verify the ZIP, then delete the tool. This version backs up all nine targets; older six-file backup tools do not cover the new Overview, local ticket view and stylesheet.
2. Upload `dist/whittles-overview-ticket-layout-private-upload.zip` to a private cPanel folder outside the public web root.
3. Extract into Whittle's document root containing `.htaccess`, `frontend/` and `backend/`, overwriting the matching files. Delete the uploaded ZIP afterward; it contains the private API token.
4. Refresh Overview and Tickets. If the browser still uses old styles, reload without cache.

Included paths:

- `frontend/dashboard.php`
- `frontend/ticket.php`
- `frontend/external-tickets.php`
- `frontend/index.php`
- `frontend/assets/css/ticket-overview.css`
- `backend/app/feed.php`
- `backend/app/mixed-tickets.php`
- `backend/app/layout.php`
- `backend/config/feed.local.php`

The source/database configurations are unchanged. This task does not require another WTS update if the previous full-details fetcher update is already installed. If it is not, apply the existing `C:\Users\Admin\.local\wts-fetch-api\wts-fetch-full-details-update.zip` to obtain notes/custom fields/attachment metadata. See `Full-Ticket-Details-Upload.md` for that source-side deployment only.

## Verification and rollback

Passed 30 source/aggregation checks, 23 feed/schema/format checks, 54 isolated browser checks, 19 nine-target backup checks, PHP syntax checks and ZIP validation. Visual review covered desktop and mobile. Browser checks include total/status reconciliation, source ID collisions, unknown statuses, drilldown filters, feed failures, role scope, rich-content safety and retained local edit/comment controls.

Real local MySQL cannot start, so actual local database queries and write workflows require a post-upload check. No database repair, migration, reset, production writes or live deployment were performed.

Restore files marked backed_up in the backup manifest, and remove files marked not_present_before_upload. Keep the backup private. Existing database records are untouched.
