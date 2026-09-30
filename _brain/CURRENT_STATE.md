# Current State

## Sync and audit request - 2026-09-30

- Live is owner's source of truth, but only website login is available; owner declined file-level access. Exact server-side source comparison/sync cannot be completed through HTTP. Git local HEAD equals origin/main at d3bfe16, while 45 working-tree paths differ/untracked. No commit/push, production edits or live data writes made.
- Read-only live Super Admin login and dashboard/index/staff/reports succeeded; sampled public login assets match local bytes, private path probes return 403. Live login has no HSTS or CSP header. Median authenticated response time over three samples: dashboard 610 ms, index 844 ms, staff 594 ms, reports 610 ms.
- Local 38 PHP lint clean, 23 feed and 39 mixed checks pass; mixed browser fixture fails after 21 passes at external detail conversation/activity assertion. Full security/functionality certification pending diagnosis and source access.
- Continuation: isolated local fixture on port 8040 matches live Dashboard/Tickets structure and all tested public assets byte for byte; 90 browser checks pass on rerun, and live read-only pagination/CSV/external detail work. Normal local auth remains blocked by MariaDB Aria/mysql.plugin startup failure. Earlier browser failure was transient. Git publication of reviewed local revision is in progress; exact deployed PHP/database equivalence remains unverified.
- Reviewed local revision published as 31886a4 on origin/main; remote hash and local HEAD agreed. Post-push live login/core GETs and CSS/logo byte comparison passed. Exact hosted PHP equivalence and real local MySQL workflows remain unverified.

## Ticket pagination, all-role export and client staff access - 2026-09-29
- Latest owner scope: no ZIP; numbered ticket pagination for all roles, Export CSV for other roles, Whittle client staff list read-only. Two runtime files changed this turn: frontend/index.php and backend/app/auth.php. Upload both matching paths in existing Whittle document root; previous ZIP/manual copies predate this change.
- Tickets retain 25 rows/page, numbered window with ellipses, First/Previous/Next/Last, current-page/disabled-edge accessibility, counts and validated filter preservation. CSV allowed for every authenticated user with view_all_tickets and includes all filtered pages from the same scoped mixed rows. Team Members still only own local tickets; partial-feed export blocked.
- auth.php explicitly grants view_staff and denies manage_staff for client-viewer. Existing Staff directory/history/profile navigation reads work without a DB grant; add/edit/history-note UI hidden and direct employee-form/profile POST writes denied, even if DB overrides grant manage_staff. Other roles retain current permissions. No migrations/config/source/CNG changes.
- Passed PHP lint for both runtime files and 90 isolated browser checks including 10-page navigation, filters, empty/clamped pages, all eight roles' exports, Team Member CSV scope, client staff list/profile and direct GET/POST denial. Fixture uses SQLite staff data; live MySQL unavailable and hosting upload remains owner action.

## Live layout mismatch diagnosis and manual files - 2026-09-29
- Owner reports hard refresh still differs from CNG. Authenticated read-only live Whittle check confirms older hosted files: All tickets/intro still shown, body wts without reference-layout, no compact toolbar, CSS v=20260929 (7377 bytes) differs from current local 14064-byte layout2 CSS. Existing source department labels already show IT Department. This is a deployment mismatch; no new runtime/UI changes required.
- Prepared dist/manual-whittle-layout-update/ with eight verified PHP/CSS files matching frontend/backend paths, excluding unchanged private feed/app config. UPLOAD.txt gives sequence/exact paths; MANIFEST.json hashes. Builder tools/build-manual-layout-upload.ps1 checks copies and allowlist. Guide documentation/Manual-Layout-Upload.md. Owner must replace frontend/index.php, shared backend layout, stylesheet and other included views/dependencies together. No ZIP required.
- Live requests only login, pages and CSS; no ticket/settings writes. CNG remains untouched. Existing 145 implementation checks remain applicable; manual copies verified byte-for-byte. Post-upload live layout check pending owner action.

## Reference layout and source departments - 2026-09-29
- Owner requested CNG Dashboard/Tickets composition and then explicitly clarified layout only, retaining Whittle colors. CNG pages inspected through supplied authenticated login/read-only GETs; no record/settings updates submitted. Kept Whittle palette/logos, omitted notifications. Renamed Overview nav/title to Dashboard, removed hero and All tickets/read-only introductory copy. Dashboard now eight cards/four columns, creation area-line trend left/status ring right, source bars/four recent links below. Compact Tickets search/select/date toolbar and ten-column table spacing.
- Authoritative source departments applied across Whittle rows/filter/CSV/detail/recent labels, even old API: Support->IT Department, escalations->HR Department, wp346/Training->LND Department, requisition->Requisition. WTS adapter emits same labels; source business records/custom values/config/password/token unchanged. Local POST handlers preserved; source read-only/scope unchanged.
- Ready: dist/whittles-dashboard-layout-departments-private-upload.zip (same nine targets/private token), C:/Users/Admin/.local/wts-fetch-api/wts-fetch-departments-update.zip (api/adapters/index). Nine-target backup tool still current. Guide documentation/Dashboard-Layout-Departments-Upload.md. Older Whittle/source ZIPs superseded for current upload. No hosting deployments.
- Passed 39 source/aggregation + 64 browser + 23 feed/format + 19 backup (145), lint, ZIP bytes/allowlists, config/token/private-file preservation and desktop/mobile visual review. Real local MySQL still unavailable; post-upload data/SQL checks required. CNG remains untouched apart from normal authentication sessions.

## Combined overview and ticket layout - 2026-09-29
- Owner requests readable viewed tickets and Overview totals/status graphs. Completed: shared mixed_ticket_load for index/dashboard, status/source aggregates including unknown statuses, last-six-creation-month current active/closed counts, mixed recent rows and status/source drilldowns. Ticket metrics primary; authorized staff stats collapsible. Team Members retain own local scope; source failure labels all remaining totals/graphs local-only. Separate Reports remains local-only.
- Local/external detail views share full-width title/status headers, conversation cards, details/attachment side panels and responsive layout. Local multiline text and latest-comment last-update preserved; all assignment labels shown. External paragraphs/lists/tables/safe links sanitized through DOM (no remote embeds/images/scripts/styles/event attributes). GET-only source writes blocked; local POST handler verified identical to Git HEAD.
- Ready: dist/whittles-overview-ticket-layout-private-upload.zip (nine files incl. unchanged private token), updated dist/whittles-feed-backup.php backs up all nine. Instructions: documentation/Overview-Ticket-Layout-Upload.md. New ZIP supersedes earlier Whittle packages; source full-details ZIP remains unchanged/applicable if not yet deployed. WTS/database/password configs untouched. No production deployment or record writes.
- Passed 30 source/aggregation + 23 feed/format + 54 isolated browser + 19 backup checks (126); PHP lint, package byte/allowlist checks and desktop/mobile visual review. Local MySQL still unavailable; actual local DB queries/write flows require post-upload check, no repair/reset performed.

## Full external ticket details - 2026-09-29
- Expanded fetcher api.php/adapters.php/index.php and Whittle feed decoder/read-only detail view: active reports/replies/internal notes, author emails, comment update dates, attachment references/metadata, ticket business metadata, defined custom fields with option labels, active ticket activity. Latest request supersedes the earlier notes exclusion; team/Carly eligibility still uses report/reply content. Deleted comments and other-ticket uploads excluded; auth codes/server paths omitted.
- Lists stay lightweight; single detail and include_threads=1 return additive schema-version-1 sections and capability flags. Missing optional sections explicitly reported. Both browser views escape source text. Attachments expose metadata only: WordPress upload storage is not available through DB connections, so downloads remain in original source system. Add-on tables outside standard schema may require dedicated adapters.
- Ready: C:/Users/Admin/.local/wts-fetch-api/wts-fetch-full-details-update.zip (api.php/adapters.php/index.php) and dist/whittles-full-details-private-upload.zip (same six Whittle targets/private token). See documentation/Full-Ticket-Details-Upload.md. Existing six-file backup tool remains applicable. Both sites await owner upload; no DB writes, config/password/token changes or migrations.
- Passed 24 SQLite source/merge cases, 20 feed cases including existing live 39-ticket API, 33 isolated browser cases, 19 backup cases, PHP lint and package allowlists. Expanded live source queries require post-upload verification; local MySQL still unavailable, untouched.

## Mixed tickets correction - 2026-09-29
- Owner explicitly requires one mixed `/index.php` list, superseding the separate External tickets design below. Implemented exact ten columns, shared filters/pagination/CSV, source-labeled collision-safe IDs and current source reads. Local links preserve existing ticket edit/comment handlers; external details accept GET only and show read-only report/reply comments. Separate sidebar link removed; old list URL redirects to index.
- Updated fetcher `adapters.php` to expose active report/reply comments with authors, agent names, optional department custom-field/agent-group labels and `date_updated`; API adds capability flags. Source notes/deleted replies excluded; existing configs, password gate and token hash unchanged. Missing metadata labels are explicit. Both sites require owner upload.
- Ready: `dist/whittles-mixed-tickets-private-upload.zip` (six Whittle files/private token); `C:/Users/Admin/.local/wts-fetch-api/wts-fetch-comments-update.zip` (api.php + adapters.php); updated `dist/whittles-feed-backup.php` backs up six targets. See `documentation/Mixed-Tickets-Upload.md` for sequence/rollback.
- Validation: 16 SQLite-backed source/merge checks, 32 isolated mixed-page/auth/browser checks, 19 six-file backup checks, PHP lint and package validation passed. Current live API remains compatible with 39 tickets; revised queries/mixed live UI await upload. Local MySQL remains unavailable; no repairs or migrations.

## External ticket reader — 2026-09-29
- Live owner-uploaded reader verified by authenticated Super Admin: `/external-tickets.php` HTTP 200, 39 matching tickets, 25 rows on page 1, no page errors; sidebar link present. `/index.php` HTTP 200 shows local empty state by design; it does not load external tickets. Private feed config request returns 403. User's reported non-reading index is a navigation/scope misunderstanding; no application code change needed for verified feed access.
- Temporary backup tool prepared at `dist/whittles-feed-backup.php`: upload into live `frontend/`, open top-level `/whittles-feed-backup.php` as Super Admin, download/verify ZIP then remove tool. Backs up the four feed patch targets, with missing-file manifest and restore notes. PHP lint + 19 isolated checks passed; hosting unchanged.
- Added a read-only `external-tickets.php` page and sidebar link using the hosted `wts-fetch-test/api.php`. The generated token stays in Git-ignored `backend/config/feed.local.php`; existing DB config is unchanged.
- Login, first-password change and `view_all_tickets` required; Team Members denied because the shared source feed does not provide per-user ownership scope. Search/source filters, 25-row pagination and escaped report conversations supported. Every request refreshes source data; no local imports, report totals or background polling.
- PHP lint and 15 feed checks passed, including 39 real live API tickets and detail conversations. 24 browser checks passed with isolated account rows plus the real hosted feed. Normal local sign-in was blocked by MySQL plugin initialization failure; no database repair performed.
- Private patch `dist/whittles-feed-private-upload.zip` has exactly four runtime files and intentionally contains the token config. Upload outside the public web root, extract to the live document root; follow `documentation/External-Tickets-Upload.md`. Live Whittle deployment remains owner action.

## Current scope
Local ticketing MVP with creation-time file attachments; employee photos retired. cPanel deployment is live and was tested on 2026-09-23. Ticket deletion remains unimplemented in the deployed UI/handler; live QA records are clearly labeled and closed.

## Production setup recheck 2026-09-24
- HTTPS login, root redirect, CSS and private path requests passed. A production-host-only HTTPS redirect was added to root `.htaccess`, included in a rebuilt 51-file cPanel ZIP, and uploaded by the owner. Post-upload HTTP returns 301, HTTPS login returns 200 with a Secure cookie, and all six private path probes return 403.
- The initial five passwords supplied for `tl`, `superadmin`, and `admin1`-`admin3` were rejected. A corrected Super Admin password then succeeded. A fresh QA ticket 1 write advanced version 4 to 5 while preserving status and priority.
- At owner request, created `tl.new` and `admin1.new`-`admin3.new`, verified first-login password changes, fresh sign-ins, and role access, then deactivated old `tl` and `admin1`-`admin3`. `superadmin` remains active. Final replacement credentials are in ignored `.local/replacement-accounts-20260924.json`.
- Results and deployment steps: `documentation/Setup-Checks-2026-09-24.md`.

## Verified 2026-09-15
- 45 workflow checks, 19 supplementary checks, desktop/mobile visual checks and 31 PHP syntax checks passed.
- Additive attachment migration applied locally; no data reset. Up to 3 files, 2 MB each; format/content validation, private database bytes and authenticated ticket-access downloads.
- Employee photo inputs/display removed; old endpoint returns 404. Legacy photo data preserved.
- DOCX and source updated; internal references replaced by plain progress-guide wording. Canonical file: documentation/Wheettle-Functionality-Draft-Revised.docx.
- documentation/Development-Work-Plan-Day-0-to-Day-9.txt is the current proposed allocation. Actual dates/hours are unverified; Day 8 target preparation and Day 9 live deployment are pending.
- User handover, completion guide and deployment checklist available. Owner acceptance is pending.

## Operations
http://127.0.0.1:8030/login.php; username demo.tl; passwords only in ignored .local/test-accounts.json. Restart tools/start-local.ps1. Existing installs run tools/migrate-ticket-files.php; fresh setup includes migration 002. Never reinitialize existing wheettle_ticketing database or modify cng-ticketing.

## Next
The cPanel site is live. The old TL/Management accounts are inactive; replacement credentials are in ignored `.local/replacement-accounts-20260924.json` for owner handover. The root `.htaccess` HTTPS redirect is prepared locally and needs authorized host deployment. If requested, implement and test a permission-checked soft-delete route in `frontend/ticket.php`, then rebuild/redeploy the ZIP. Coordinate cleanup of labeled QA records after an approved delete/cleanup method exists. Portable Git: C:/Users/Admin/Downloads/wts-build-tools/git/cmd/git.exe. Exclude credentials, local artifacts, sessions, legacy Word draft and lockfiles.

## Publication result
- Published implementation/docs as 449dc0d and verified origin/main matched on 2026-09-15. Local checks passed; hosting details and owner acceptance are next. Git textconv helper unavailable for DOCX diff; repeated inspection with --no-textconv successfully verified credential exclusion.

## Owner testing 2026-09-16
- Local server and MySQL already running; http://127.0.0.1:8030/login.php responds 200.
- Created tl (team-leader), superadmin (super-admin), and admin1/admin2/admin3 (management), per owner clarification. Verified read/report/export access, no account/staff/ticket write grants, and unchanged passwords. Deployment command uses --admin-role=management.
- Temporary credentials are in ignored .local/owner-accounts.json; first login requires password change. Existing demo users/data preserved.
- Added CLI-only tools/provision-accounts.php for the same deployment usernames with fresh passwords. Reruns preserve existing accounts; exclusive credential output prevents overwrites.
- Verified PHP lint, rerun preservation, browser login/password-change enforcement for all five users, and HTTP denial of credentials/tool paths. README and deployment checklist updated. Production still pending target details.

## Branding 2026-09-16
- App now displays Whittles with supplied assets/Whittles Body Corp.webp on login/sidebar and browser icon. Updated titles, header, footer, sign-out wording and app display config. Database/session/path identifiers unchanged. PHP lint and desktop/mobile browser checks passed.

## System audit 2026-09-16
- Fixed first-login logout, reuse of initial passwords, null-byte password errors, malformed/out-of-range dates, UTF-8/TEXT byte overflow, zero-valued resolution loss, missing default TL and inconsistent reset history filters. Apache private path matching is now case-insensitive. Corrected ticket creation punctuation and CSV branding.
- Final verification: 45 main + 19 handover + 49 audit browser checks; 32 PHP lint checks; 11 isolated fresh-install checks; Apache login 200 and six private probes 403. Audit database removed, disposable QA accounts deactivated, labeled QA staff/tickets retained. Owner passwords/data preserved.
- Report: documentation/System-Audit-2026-09-16.md. New regressions: tests/audit.cjs. Production target verification remains pending.

## Registration and dual branding 2026-09-16
- User requested public registration with Super Admin approval, password confirmation/visibility and dual logos. Implemented register.php, shared auth layout, registration JS and branding CSS; removed workspace-name/People & Service text. Sidebar has Strata above Whittles; login/register card has Whittles left, Strata right; ticket-focused headline.
- Registration requires email/username/password/confirmation/role; allowlist excludes super-admin. Creates inactive pending users with chosen password, uses existing schema, limits registrations per IP, validates CSRF and duplicates. Users screen lists pending first with email/requested role and Super Admin-only Approve/Reject. Toggle cannot bypass approval; unapproved accounts excluded from assignment helpers.
- Passed 62 new registration checks plus 45 main, 19 handover and 49 audit checks (175 total); 34 PHP syntax checks and JS syntax check. Desktop/mobile screenshots reviewed. QA registration accounts inactive/rejected; owner accounts/passwords preserved. No migration needed.
- Dashboard refinement: current date now appears below Welcome in place of the descriptive paragraph; removed the welcome eyebrow and shared header date. Date remains visible at mobile widths.

## Staff directory usability 2026-09-16
- Reworked staff.php with prominent search, department/TL filters and expandable team/start-date filters. Applied filter chips support individual removal and clear-all. Status tabs show scoped counts; result range and filtered export are explicit.
- Simplified list to employee identity/job/code, department/team, TL, status and View profile. Full shift/dates/history remain in profile. Responsive labeled cards below 1000px; no business data changes.
- Passed PHP lint and 14 targeted read-only browser checks: four viewport widths, search/results, filtered CSV, status/filter preservation and empty state, reset, advanced filters/removal, invalid dates, profile navigation and Management/Viewer permissions. Desktop/mobile screenshots reviewed. Evidence .local/qa/staff-improved-*.png.

## Ten-day work-plan audit 2026-09-19
- Re-ran the local suites after starting the documented PHP server: 45 core, 19 handover, 49 audit and 62 registration checks passed; visual desktop/mobile checks passed; PHP syntax checks passed.
- Added `documentation/Development-Work-Plan-Tracker.md` and refreshed the completion guide. Days 0–7 are complete for the defined local scope. Day 8 local release preparation is complete through pushed release `9819d15`; target-specific preparation remains pending. Day 9 remains pending because hosting, live access, HTTPS, production database and owner-approved live test data are not provided.
- Credentials, sessions, QA artifacts and private attachment data remain excluded by Git. Actual plan dates/hours, rest-day designation and owner review remain intentionally unrecorded.

## Full release rerun and cPanel preparation 2026-09-19
- Ran the complete local release gate again before deployment preparation: PHP syntax, 45 core checks, 19 handover checks, 49 audit checks, 62 registration checks and desktop/mobile visual checks all passed.
- Added `documentation/cPanel-Deployment-Upload-Plan.md` with a realistic solo-developer schedule of five active working days plus two buffer days, exact upload/exclusion list, database setup order, role/UI/CRUD/attachment checks, rollback triggers and owner-acceptance requirements.

## cPanel package and source layout 2026-09-23
- Reorganized deployable source into `frontend/`, `backend/` and `database/`; the root `.htaccess` preserves existing top-level URLs and blocks private/source paths. Local setup/router and deployment docs now use the new paths.
- Added an editable `accounts.superadmin_initial_password` value to the private local config template and updated the CLI provisioning tool to use it for a new Super Admin account. Existing accounts remain unchanged; initial password change is still required.
- Built `dist/whittles-cpanel-upload.zip` (51 files). The archive allowlist contains runtime files only and excludes `_brain`, docs, tests, local config/secrets, sessions, and developer tools other than the CLI account provisioner. `_brain` continuity notes are committed to GitHub only.
- Verified 34 PHP files lint; 45 core, 19 handover, 49 audit, and 62 registration browser checks; desktop/mobile visual checks; and Apache route/private-file behavior (home redirects 302, login/assets 200, backend config and SQL 403).

## Production cPanel verification 2026-09-23
- Live URL `https://whittles-ticketing.stratastaff.com` responds over HTTPS; login, CSS, logos, root redirect, reports and expected private-path 403s passed. Five provisioned accounts passed fresh sign-in and role checks.
- TL and Management accounts completed first-login password changes for testing; they now share a temporary password and owner must replace each with a unique password. Super Admin authenticated with the corrected supplied password and was left unchanged. The original cPanel credential output is stale for the four changed accounts.
- Live database initially had no staff or tickets. Created QA employee ID 1 (`QA-WHIT-1790146796591`), then 10 uniquely tagged tickets (`QA-LIVE-1790146903549`) to exercise creation, updates, assignment, comments, close/reopen validation, stale-edit rejection, attachment download/access control, and report exports. All 10 tickets were closed after testing; QA employee was marked Exited. No prior records were present or altered.
- Ticket deletion is not implemented in the deployed handler/UI: a safe `action=delete` attempt returned `Unknown ticket action` and left the ticket intact. The schema and Super Admin role contain soft-delete permission/column, but `frontend/ticket.php` has no delete flow. Subject and issue text are read-only after creation. Full evidence: ignored `.local/qa/prod-live-audit-2026-09-23.md`.

## Production workflow QA 2026-09-25
- Fresh login, role routes, desktop/mobile layouts, staff/ticket workflows, attachments, and reports were checked on the live site. 127 checks were reviewed: 125 passed; the two failures were the missing ticket deletion and read-only subject/issue fields. Ticket-viewer attachment downloads are expected by the regression contract.
- Live QA created employee ID 2 (`QA-WHIT-20260925-MUGBT5Z7`, marked Exited) and ticket ID 11 (`QA-LIVE-20260925-MUGBT5Z7`, Closed) with two small QA attachments. No existing production record was edited or deleted. Full evidence: `.local/qa/live-defects-2026-09-25.md` and `.local/qa/live-workflow-results-2026-09-25.json`.
- Fixed both gaps locally in `frontend/ticket.php`: permission-checked CSRF soft delete for `delete_tickets`, and editable subject/issue fields included in versioned update history. No database migration is needed; the live schema already has the columns and permission.
- Local verification passed: browser 49, handover 19, authorization audit 49, soft-delete regression 6, and PHP lint. Rebuilt `dist/whittles-cpanel-upload.zip` with 51 allowlisted runtime files. Live behavior remains unchanged until the owner uploads the ZIP.

- Owner now requests one-shot ZIP: dist/whittles-layout-one-shot-upload.zip prepared with exactly the eight manual PHP/CSS paths, verified bytes and allowlist, no configuration/token/password files. Extract into live document root containing frontend/backend, overwrite. This supersedes manual-only preference; deployment remains owner action.
