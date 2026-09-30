# Session Handoff

## Real local/live parity recheck - 2026-09-30

36 paired checks passed against XAMPP local snapshot and live: Super Admin login, nine pages with identical normalized visible text/structure, five list/filter states, external detail/read-only POST denial, and five byte-identical CSV/report exports. Local snapshot table/record counts agree with live-visible counts. Git local and origin/main were 7a0566c with clean tree before this note. No live writes; full mutation and other-role parity remains untested. See daily/2026-09-30.md.

## XAMPP repaired and real comparison - 2026-09-30

XAMPP MySQL/Apache now work. Repaired Aria corruption after full backup at C:/xampp/mysql/data-backup-before-aria-repair-20260930; one unreadable mysql.proxies_priv row lost, original preserved. User live SQL dump imported into new wheettle_live_snapshot_20260930, existing local DB untouched. Ignored backend/config/config.local.php points app at snapshot, without BOM. Local Super Admin login works via XAMPP Apache and PHP 8030. Nine live/local pages match normalized visible text; CSV byte identical. No live writes. See daily/2026-09-30.md.

## Current request - 2026-09-30

Owner wants live deployment (source of truth) synchronized to local and GitHub, then security/speed/functionality checks. Supplied website login but explicitly declined file-level access. Cannot retrieve deployed PHP/config through browser, so do not claim an exact sync or push the existing 45-path dirty tree. Local HEAD and origin/main d3bfe16. Read-only live checks: authenticated dashboard/index/staff/reports work; tested login assets match local bytes; private paths 403; HSTS/CSP absent. Three-sample median response times dashboard 610 ms, index 844 ms, staff 594 ms, reports 610 ms. Local lint 38 PHP files clean, feed 23 and mixed 39 pass; mixed-browser fixture fails after 21 passes at external detail conversation/activity. No production write testing. See daily/2026-09-30.md.

Continuation: Owner explicitly asks to compare local browser UI/functionality, then publish if matching. Isolated local fixture versus live Dashboard/Tickets layouts and structural workflows match; all live authenticated CSS/images match local bytes. 90 browser checks pass on rerun, live list pagination/CSV/external detail work. Local MySQL still fails Aria/mysql.plugin startup, so normal local authenticated path and exact PHP equivalence remain unverified. Reviewed staged changes for secrets; publication in progress. See daily/2026-09-30.md.

Published tested local revision 31886a4 to origin/main; confirmed remote hash and post-push live login/core pages/assets. Site PHP source was not retrieved; normal local DB path remains unavailable. No production writes. Finish with that limit explicit.

Owner clarified behavioral matching matters, rather than exact PHP file identity. Ran 20 additional live browser checks: Super Admin login, ten routes, 25+14 pagination, no-result search, CSV for all 39 tickets, external detail/read-only POST 405, mobile login width. All passed and agree with isolated local fixture behavior. Live create/edit/delete untested because production currently lacks local staff/tickets; avoid claiming all functionality matches.

## Current status - 2026-09-29

Latest owner requests ticket pagination for all roles, CSV export in other roles, and Whittle client read-only staff list. No ZIP. Completed locally, user upload pending: frontend/index.php and backend/app/auth.php, exactly two runtime files changed this turn. Direct file links provided; overwrite matching frontend/backend paths in live Whittle document root. Earlier ZIP/manual folder has older index and omits this auth change; do not present it as current.

Tickets: 25 rows/page, compact numbered pages/ellipses, First/Previous/Next/Last, aria current/disabled states, count/page display, filters preserved in links. Every authenticated view_all_tickets user can export CSV from this list, no separate export_tickets grant required; all filtered rows across pages, same existing scope. Team Members own local tickets only, source feed failure blocks incomplete export. Other legacy export.php routes/permissions unchanged.

Client role identified by client-viewer: auth.php user_can grants view_staff and denies manage_staff before DB grants/overrides. Existing staff list/history/departments/profile reads and sidebar become available; profile edit/add/history-note buttons hidden and direct editor GET/POST/profile-note POST denied. No DB grants/migrations needed. Other permission logic unchanged, source tickets still read-only, native local ticket handlers preserved.

Validation: 90 isolated browser checks passed (previous 64 expanded) with SQLite staff fixture, including large 10-page navigation, empty/oversized pages, filter retention, all-eight-role ticket exports and own-ticket scope, client staff/profile access and denied direct writes even with database manage_staff grant. PHP syntax passed index/auth. MySQL real data unavailable, no repair/reset performed. Live upload/deployment not performed.

Persistent layout scope: CNG composition only with Whittle colors/logos, no notifications, Dashboard rename and removed hero/intro. Ten mixed columns, all permitted counts/status/source/month graphs, canonical source departments Support IT Department, HR HR Department, Training LND Department, Requisition Requisition. WTS adapters.php latest source change emits these; source config/API auth token/password unchanged. Prior live layout mismatch was old deployed frontend/layout/CSS (read-only evidence .local/tickets-layout-check/); prior layout ZIP still available for missing base update only. Source/external comments readonly; Carly matching restrictions and five-member scope preserved. No CNG changes.

Previous handoff archived in sessions/archive/LATEST_HANDOFF-before-pagination-client-staff-20260929.md. Tests changed tests/mixed-browser.cjs and mixed-browser-fixture.py; no test files need upload. Existing uncommitted project changes preserved. No ZIP made this turn.

## Git Baseline

- Base commit: 7a0566cc86bbe314cc0b8dcd1d6d0cc3df775ce8
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: [tests/checks run, or not yet run]
