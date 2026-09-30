# Dashboard layout and source departments

This supersedes the previous Whittle overview/layout ZIP. The owner requested CNG's layout only, explicitly retaining Whittle colors and branding. CNG was inspected through authenticated page reads; no ticket, account or setting updates were submitted there.

## Current behavior

- Navigation and page title now say Dashboard.
- The welcome hero and the All tickets heading/read-only introductory paragraph are removed.
- Dashboard follows the reference composition: eight summary cards in four columns, creation trend on the left, status distribution on the right, source workload and four recent tickets below. Existing Whittle colors, logos and role permissions remain. No notification control is added.
- Tickets follows the reference compact search/filter bar and table spacing. All ten requested columns, shared filters/export/pagination and read-only source routes remain. Native dropdowns and date inputs provide the existing filtering behavior; CNG-specific ownership, bulk-action and SLA features are not copied.
- Counts remain based on permitted local and external tickets. Other status cards aggregate unfamiliar source statuses, whose individual labels/counts remain visible in the status chart. Missing feeds are explicitly labeled local-only.

Source department identity is authoritative in Whittle rows, filters, CSV exports, detail views and recent-ticket labels:

| Source | Department |
| --- | --- |
| stratast_support / Strata Support Desk | IT Department |
| stratast_escalations / HR Escalation Desk | HR Department |
| stratast_wp346 / Training Desk | LND Department |
| stratast_requisition / Requisition Desk | Requisition |

Whittle applies the mapping even to older hosted API responses. The WTS adapter now returns the same department names. Connection settings, passwords and token hash are unchanged. Original custom field values remain available in detail sections; no source database values are rewritten.

## Back up and deploy Whittle

1. Use the current `dist/whittles-feed-backup.php`, which covers nine targets. Upload it into live `frontend/`, sign in as Super Admin, open `/whittles-feed-backup.php`, download/verify the backup and remove the tool.
2. Upload `dist/whittles-dashboard-layout-departments-private-upload.zip` outside the public web root. Extract into Whittle's document root containing `frontend/` and `backend/`, overwriting matching paths.
3. Delete the ZIP afterward; it contains the unchanged private feed token. Reload Dashboard and Tickets without cache if needed.

The nine paths are frontend/index.php, frontend/external-tickets.php, frontend/dashboard.php, frontend/ticket.php, frontend/assets/css/ticket-overview.css, backend/app/feed.php, backend/app/mixed-tickets.php, backend/app/layout.php, and backend/config/feed.local.php. Application database configuration and records are unaffected.

## Deploy the fetcher mapping

Back up hosted `api.php`, `adapters.php` and `index.php` in cPanel. Extract `C:\Users\Admin\.local\wts-fetch-api\wts-fetch-departments-update.zip` into `public_html/wts-fetch-test/`, overwriting those three files. Delete the ZIP. It also includes the previous full-details functionality; do not overwrite config.php, db.php, auth.php or team.php.

The Whittle mapping works independently of this source upload, but deploying both keeps the fetcher UI/API and Whittle consistent.

## Checks and rollback

Passed 39 source/aggregation cases, 64 isolated browser cases, 23 feed/schema/format cases, and 19 nine-target backup cases (145), plus PHP lint, ZIP validation and desktop/mobile visual review. Checks cover all four source mappings, department filtering/detail display, removed text/notification controls, preserved Whittle button colors, chart composition/counts, partial feeds, role scope and local/source write boundaries.

No production deployment, CNG changes, database repair/reset or migrations were performed. Real local MySQL remains unavailable; post-upload verification is needed for real local database queries and source data.

Rollback Whittle using its backup manifest, restoring existing files and removing paths marked absent before upload. Restore the three downloaded fetcher files if needed. Keep backup archives private.
