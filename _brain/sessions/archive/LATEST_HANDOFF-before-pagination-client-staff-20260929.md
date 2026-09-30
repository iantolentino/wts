# Session Handoff

## Current status - 2026-09-29

Latest owner steering: now requests a one-shot ZIP. Ready dist/whittles-layout-one-shot-upload.zip with eight verified runtime files and correct frontend/backend paths, no config/token/password files. Extract into existing Whittle document root and overwrite; owner upload pending. This supersedes manual-only preference.

Latest owner report: hard-refreshing Tickets still does not match CNG. Authenticated read-only Whittle inspection confirms old deployed page/layout/style, not current local update: All tickets intro remains, body lacks reference-layout, compact toolbar absent, CSS ?v=20260929 is 7377 bytes; local current CSS is 14064 bytes and uses ?v=20260929-layout2. Live source rows already show IT Department. No runtime implementation change needed.

User prefers manual files, no ZIP. Prepared dist/manual-whittle-layout-update/ via tools/build-manual-layout-upload.ps1: eight source-identical files frontend/index.php, dashboard.php, external-tickets.php, ticket.php, assets/css/ticket-overview.css; backend/app/layout.php, feed.php, mixed-tickets.php. Exact paths/sequence in folder UPLOAD.txt; hashes MANIFEST.json. No private configs/token/passwords included; existing hosted config stays. Find live document root containing frontend/backend/.htaccess; replace frontend/index.php, not a root-level copy. Guide documentation/Manual-Layout-Upload.md. Existing nine-target backup tool applies. Live verification awaits owner upload; no hosting access for deployment.

Existing implementation: CNG layout composition only, retain Whittle palette/brands and no notifications. Dashboard renamed/welcome/all-tickets intro removed, eight metric cards, trend/status/source graphs and recent links; Tickets compact filters/table with ten columns. All permitted local/external counts, unknown status detail and local-only feed failure labeling retained. Local editable/source read-only, role scope preserved.

Departments: Support IT Department; HR HR Department; Training LND Department; Requisition Requisition. WTS adapters.php emits same labels; original config/db/auth/token untouched. Whittle mappings work with old API responses too. Previous source ZIP wts-fetch-departments-update.zip and Whittle nine-file ZIP remain available, but user now prefers manual folder.

Current runtime passed 145 cases previously (39 source,64 browser,23 feed,19 backup), lint/visual/package checks; unchanged this turn. New manual copies byte-for-byte/allowlist verified. Live diagnosis performed using supplied Super Admin credentials without ticket/settings changes. CNG remains untouched beyond prior reference login/reads. Local MySQL unavailable; no repair/reset/migrations. Keep ignored configs/token/ZIPs out of Git.

Previous handoff archived in sessions/archive/LATEST_HANDOFF-before-manual-layout-20260929.md. Live evidence is in ignored .local/tickets-layout-check/, prepared visual screenshots .local/overview-screenshots/. Prior uncommitted changes preserved.

## Git Baseline

- Base commit: d3bfe1695067d5a885f1431b06ce098d31da245e
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: Eight-file ZIP allowlist/byte equality verified; no configs/secrets. Runtime unchanged; prior 145 checks applicable. Owner upload pending.
