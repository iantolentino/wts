# Ten-day development work-plan tracker

Updated: 2026-09-19

This tracker records what is evidenced in the local Whittles build and what remains open. It does not invent calendar dates, work hours, overtime, owner acceptance, or live deployment evidence.

## Current status

| Plan day | Status | Evidence or remaining condition |
| --- | --- | --- |
| Day 0 - Requirements and functionality documentation | Complete | Functionality Markdown/DOCX, `SPEC.md`, completion guide, user handover, and the proposed work plan are present. Actual work dates/hours remain unrecorded. |
| Day 1 - Database schema and creation | Complete locally | Base schema plus migrations `001_wheettle.sql` and `002_ticket_files.sql`; fresh-install and additive migration checks passed locally. |
| Day 2 - Shared backend, authentication and accounts | Complete locally | Authentication, sessions, first-password change, registration approval, role checks, CSRF and account lifecycle are implemented and covered by the local suites. |
| Day 3 - Ticket, employee-reference and history functions | Complete locally | Ticket lifecycle, employee profiles, assignments, comments, resolution/reopening, history and stale-edit protection are implemented and tested. |
| Day 4 - Attachments, search and reporting | Complete locally | Creation-time attachments, private authenticated downloads, filters, pagination, reports and CSV exports are implemented and reconciled locally. |
| Day 5 - UI and workflow integration | Complete locally | Login, registration, dashboard, staff, ticket, history, reports, account screens and role-aware responsive layouts are connected. |
| Day 6 - Database and functional testing | Complete for covered cases | 45 core, 19 handover, 49 audit and 62 registration checks passed locally; PHP syntax checks also passed. |
| Day 7 - Complete local system testing and corrections | Complete for defined local scope | End-to-end, role, attachment, report/export, mobile and visual checks passed. Remaining limitations are target-environment and owner-acceptance items. |
| Day 8 - Release preparation and deployment planning | Complete for local release prep | Documentation, handover and deployment checklist exist; the reviewed release state is committed as `9819d15` and pushed to `origin/main`. Target-specific hosting and recovery details are still unknown. |
| Day 9 - Deployment and final live testing | **Pending / blocked on environment** | No hosting target, live URL, deployment access, production database, HTTPS setup or owner-approved live test data has been provided. |

## Remaining to-do list

### Release preparation

- [x] Review the complete worktree and confirm the current tested source/docs are the intended release.
- [x] Commit the reviewed release to `main` and push it to `origin/main` (`9819d15`).
- [x] Verify the pushed revision and record the commit in the handoff/deployment state.
- [ ] Obtain owner review of the local build, known limitations and attachment policy.
- [ ] Decide whether a staging environment is required before production.

### Deployment inputs required from the owner/host

- [ ] Hosting provider/server and authorized deployment method.
- [ ] Live domain/URL, document root and HTTPS certificate/proxy details.
- [ ] Dedicated production database name/user and secure configuration delivery method.
- [ ] Backup location, recovery owner, maintenance window and restoration procedure.
- [ ] Owner-approved live test accounts/data and acceptance contact.
- [ ] Attachment retention and malware-scanning decision; the current application validates uploads but does not provide antivirus scanning.

### Day 9 execution after inputs are available

- [ ] Back up the target and verify that the backup can be read/restored according to the agreed recovery procedure.
- [ ] Deploy the reviewed commit and environment-specific configuration; do not copy local credentials, sessions, QA records or local demo data.
- [ ] Confirm PHP extensions, database connectivity, private storage/config/tool restrictions and HTTPS.
- [ ] Apply only the required schema/migrations and provision approved accounts using fresh private credentials.
- [ ] Run live smoke tests for login/password change, role restrictions, ticket creation, attachments, authorized/unauthorized downloads, history, reports and exports.
- [ ] Inspect application/server logs and correct environment-specific defects.
- [ ] Record live URL, deployed revision, operator/date, evidence, remaining concerns and owner acceptance.

## Evidence captured on 2026-09-19

- `tests/browser.cjs`: 45 checks passed.
- `tests/handover.cjs`: 19 checks passed.
- `tests/audit.cjs`: 49 checks passed.
- `tests/registration.cjs`: 62 checks passed.
- `tests/visual.cjs`: desktop/mobile visual checks and screenshots passed.
- PHP syntax checks passed for application, configuration and tool PHP files.
- Local server was verified through the project test suites at `http://127.0.0.1:8030/login.php` after startup.
- Credentials, sessions, private attachments and QA artifacts remain ignored by Git.
- A second complete local release-gate run also passed on 2026-09-19 before cPanel preparation.

## Schedule fields intentionally left for the worker/owner

- Actual date/time/hours for Days 0–9: not available from repository evidence.
- Rest-day designation: not assigned; only record it if work actually occurred on a scheduled rest day.
- Completed by / reviewed by / total actual hours: not recorded.
