# Session Handoff

## Status
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

- Base commit: a47882fc2aca1e9cd1e9810ea7d0a063d2a28350
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: live test on 2026-09-23 — 5/5 fresh logins, 10/10 test tickets created/updated/closed, report exports and attachment/security checks passed; deletion unsupported.

