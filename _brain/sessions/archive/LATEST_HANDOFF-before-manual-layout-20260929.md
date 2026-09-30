# Session Handoff

## Current status - 2026-09-29

Latest task completed locally: owner requested source departments, rename Overview to Dashboard/remove welcome and All tickets intro, and CNG Dashboard/Tickets layout. Subsequent correction: layout only, retain Whittle colors. CNG authenticated pages inspected read-only with provided credentials; browser blocked non-read requests except login. No CNG records/settings changed. Credentials not stored in source/docs.

Dashboard: eight cards in four columns (total/known statuses/urgent/other/unassigned), six-month creation line/area left and status ring right, source bars/four recent links below. Individual unknown statuses remain in legend; totals reconcile, role restrictions and explicit local-only feed failure behavior retained. Tickets: compact search/select/date toolbar and table spacing; ten columns/shared filters/CSV/pagination preserved. Dashboard nav/header renamed, hero/introduction removed, create/view links moved into header, no notification button. Whittle palette/logos kept. Internal CSS filename ticket-overview.css unchanged.

Departments authoritative by fixed source: stratast_support IT Department; stratast_escalations HR Department; stratast_wp346 LND Department; stratast_requisition Requisition. Whittle feed constant/mixed rows/detail normalization applies to old responses too. WTS adapter emits same labels via fixed display-source map; configs/auth/token hash untouched. Source custom field values preserved; no DB writes or local handler edits.

Ready: dist/whittles-dashboard-layout-departments-private-upload.zip (nine targets including private token), C:/Users/Admin/.local/wts-fetch-api/wts-fetch-departments-update.zip (api.php/adapters.php/index.php), existing updated dist/whittles-feed-backup.php (nine-target backup). Guide documentation/Dashboard-Layout-Departments-Upload.md. Older packages superseded; generic cPanel ZIP rebuilt. Upload by owner; private Whittle ZIP outside public web root, delete after extraction. Source mapping works in Whittle before source upload, but both uploads keep API/UI consistent.

Passed 39 source/aggregation, 64 isolated browser, 23 feed/format, 19 backup checks (145), lint/package byte equality, original config/token/private file checks and visual review. Local write handler matches Git HEAD. Screenshots in ignored .local/overview-screenshots; private reference in .local/cng-reference. Local MySQL still unavailable; actual local DB and live data checks await owner upload. No DB repairs/reset/migrations/hosting deploy or production ticket writes. Keep private token/config/ZIPs out of Git.

Previous handoff archived in sessions/archive/LATEST_HANDOFF-before-reference-layout-20260929.md. Prior uncommitted fixes preserved.

## Git Baseline

- Base commit: d3bfe1695067d5a885f1431b06ce098d31da245e
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: 145 cases: 39 source/aggregation, 64 browser, 23 feed, 19 backup; lint, ZIP bytes/allowlists, config/auth preservation, desktop/mobile visual review. Hosting upload pending.
