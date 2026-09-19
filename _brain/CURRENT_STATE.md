# Current State

## Current scope
Local ticketing MVP with creation-time file attachments; employee photos retired. Owner requested commit/push to https://github.com/iantolentino/wts.git and a realistic Day 0-9 work plan. Live deployment is pending hosting details, not claimed complete.

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
Collect owner feedback and hosting details, then perform target-specific deployment when access is provided. Latest reviewed release is `322ca8b` on `origin/main`. Portable Git: C:/Users/Admin/Downloads/wts-build-tools/git/cmd/git.exe. Exclude credentials, local artifacts, sessions, legacy Word draft and lockfiles.

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
- Added `documentation/Development-Work-Plan-Tracker.md` and refreshed the completion guide. Days 0–7 are complete for the defined local scope. Day 8 local release preparation is complete through pushed release `322ca8b`; target-specific preparation remains pending. Day 9 remains pending because hosting, live access, HTTPS, production database and owner-approved live test data are not provided.
- Credentials, sessions, QA artifacts and private attachment data remain excluded by Git. Actual plan dates/hours, rest-day designation and owner review remain intentionally unrecorded.
