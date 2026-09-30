# Session Handoff

## Current status — 2026-09-25

Live full-workflow QA is complete. All five current live test accounts passed fresh sign-in and their route/role checks. Desktop and 390px mobile UI, staff workflow, ticket creation/comments/status, attachment validation/downloads, and reports passed. Evidence: .local/qa/live-workflow-results-2026-09-25.json and .local/qa/live-defects-2026-09-25.md.

The live test added employee ID 2 (QA-WHIT-20260925-MUGBT5Z7, Exited) and ticket ID 11 (QA-LIVE-20260925-MUGBT5Z7, Closed) with two small QA attachments. No existing production record was edited or deleted. Ticket-viewer attachment downloads match the regression contract.

Two live workflow gaps were fixed locally in frontend/ticket.php: a CSRF-protected, delete_tickets-permission-checked soft-delete flow, and subject/issue editing with version checks and history details. The deployed database already has the needed columns and permission, so no SQL migration is required. Local tests passed: browser 49, handover 19, authorization audit 49, soft-delete 6, and PHP lint.

The upload package is dist/whittles-cpanel-upload.zip (51 allowlisted runtime files). Live behavior remains unchanged until the owner uploads it. Next: upload the ZIP to the cPanel document root, then verify the edit and delete workflows on live and clean up the labeled QA tickets if desired. Four replacement-account passwords remain in ignored .local/replacement-accounts-20260924.json; do not copy credentials into reports or source.

The older notes below record the prior deployment checks.

## Status
2026-09-24 recheck: The production-host-only HTTPS redirect was added to root `.htaccess` and uploaded by the owner. Post-upload HTTP returns 301 to HTTPS; HTTPS login returns 200 with a Secure cookie; private paths remain 403. The package contains 51 allowlisted files. All five initially supplied passwords were rejected; a corrected Super Admin password then succeeded. An authenticated save of existing QA ticket 1 advanced its version from 4 to 5 with status and priority unchanged. At owner request, created `tl.new` and `admin1.new`-`admin3.new`, verified password changes/fresh logins/role routes, then deactivated old `tl` and `admin1`-`admin3`. `superadmin` remains active. Final replacement credentials are in ignored `.local/replacement-accounts-20260924.json`. See `documentation/Setup-Checks-2026-09-24.md`.

The owner had web-app login access only and uploaded the root `.htaccess` directly. The live HTTPS redirect is verified.

Created `documentation/Production-Setup-Work-Report-2026-09-24.docx` for owner handover. Its package and tables were validated; it contains no passwords.

The cPanel deployment at https://whittles-ticketing.stratastaff.com is live and was tested on 2026-09-23. Refactor/package source is pushed as `a47882f`. `_brain` notes are in GitHub only; the upload ZIP excludes `_brain`.

## Live verification
All five accounts passed fresh login. Super Admin has expected full access; Team Leader can create/update tickets and staff but cannot administer accounts; the three Management users can view and report but cannot create/edit records. TL and Management accounts now share a temporary test password after first-login change; owner must rotate each separately. Super Admin password was left unchanged.

Created QA employee ID 1 (`QA-WHIT-1790146796591`, marked Exited) and 10 tickets IDs 1–10 with prefix `[QA-LIVE-1790146903549]`; all tickets are closed after tests. Ticket 1 retains a small QA text attachment. No pre-existing staff/tickets existed or were modified. Evidence: ignored `.local/qa/prod-live-audit-2026-09-23.md`.

HTTPS login, CSS/logos, root redirect, private-path 403s, five fresh logins, role checks, ticket create/update/comment/close/reopen, stale edit rejection, attachment download/access control, HTML report, ticket CSV and summary CSV passed.

## Known gaps / next actions
- Ticket deletion is absent from the deployed route/UI despite `deleted_at` and `delete_tickets` schema permission. A safe `action=delete` test returns “Unknown ticket action”; no test data was deleted. Main fix target: `frontend/ticket.php`.
- Ticket subject and issue text cannot be edited after creation; only status, priority, assignee and resolution are editable.
- The cPanel credentials file is stale for TL and admin1/admin2/admin3 after their temporary test password changes. Keep private and remove after saving needed details. Do not expose passwords in source, docs or reports.
- If owner requests, implement/test soft delete, create a replacement ZIP (still exclude `_brain`), commit/push, then coordinate redeployment and QA-data cleanup.

## Git Baseline

- Base commit: 36e96bd08b54e33c6ac9e9e27aa30d9ddfcc0f12
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: Live QA reviewed 127 checks (125 passed; two confirmed gaps); local browser, handover, authorization audit, and soft-delete checks passed (49/19/49/6); PHP lint and cPanel package validation passed.
