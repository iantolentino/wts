# Completion and progress guide

Verified: 15 September 2026. Scope: local ticketing MVP, attachments and technical handover. The Day 0-9 plan is a proposed work allocation, not a certification of elapsed days or overtime hours.

| Plan day | Status | Evidence |
| --- | --- | --- |
| 0 - Documentation | Complete | Functionality Markdown/DOCX, SPEC.md and progress guide. |
| 1 - Database schema | Complete locally | Base schema, migrations 001 and 002; attachment migration applied without resetting existing data. |
| 2 - Shared backend and accounts | Complete locally | Authentication, sessions, validation, roles and account lifecycle tests. |
| 3 - Core business functions | Complete locally | Staff reference/history and ticket lifecycle; stale-edit and permission checks. |
| 4 - Attachments and reporting functions | Complete locally | Validated creation-time uploads, authenticated downloads, search, CSV and report reconciliation. |
| 5 - UI and integration | Complete locally | Connected forms, navigation, ticket attachment links and desktop/mobile checks. Employee photo controls retired. |
| 6 - Database/function testing | Complete for covered cases | 45 workflow checks plus 19 supplementary checks passed. |
| 7 - Full local testing | Complete for defined local scope | End-to-end, role, attachment, report, mobile and PHP syntax checks passed. |
| 8 - Release/handover preparation | Local materials complete; hosting preparation pending | User guide, migration instructions and deployment checklist available; hosting/domain/database/recovery details require target information. |
| 9 - Deployment/live testing | Pending | No live hosting target or credentials provided; no deployment or live acceptance claimed. |

## Verification evidence

- tests/browser.cjs: 45 passed. Covers staff/history and ticket workflow, rejected executable/oversized/excessive attachments, two valid files including DOCX, exact downloaded bytes, forced-download headers, anonymous denial, missing-file response and authorized viewer download.
- tests/handover.cjs: 19 passed. Covers first-password change, account activation/session revocation, reopening/reclosing tickets, stale ticket updates, report-to-CSV reconciliation and seven mobile routes.
- tests/visual.cjs: passed desktop/mobile layout checks and screenshot capture.
- 31 PHP files passed syntax checks.
- Latest fictional workflow fixture: staff ID 7, ticket ID 7. Supplementary QA accounts are left deactivated. Existing photo data is preserved but no longer served; no real employee data introduced.
- Local artifacts: .local/qa/results.json, handover-results.json and screenshots; excluded from Git with credentials and sessions.
- DOCX ZIP/Open XML validated and internal brain/Nexus references excluded. Word-rendered visual layout is not certified.

The first supplementary test attempt omitted clearing resolution before reopening; the app correctly rejected that combination. The test was corrected and verifies status after reloading. Final checks passed. No unresolved failing checks remain from these runs; this is not exhaustive testing of every possible input or a business acceptance sign-off.

## Handover and next steps

Local URL: http://127.0.0.1:8030/login.php. Test username: demo.tl. Passwords remain in the ignored local credential file. See User-Guide-and-Handover.md for startup and attachment migration instructions.

Owner review and live deployment are pending. Use Deployment-Checklist.md once the target is known. Record new feedback with role, route, steps, expected/actual result, priority and retest result. The latest work allocation is Development-Work-Plan-Day-0-to-Day-9.txt.
