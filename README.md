# Whittles — Ticketing & Staff History

An independent PHP/MySQL application adapted from the owner's CNG ticketing design, authentication/ticket helpers and relational schema. The supplied Whittles logo is used for application branding and the browser icon. The source project is not modified. No source employee data, credentials, private attachments or integrations are included.

## Project folders

- frontend/ contains the public PHP pages and browser assets. The root .htaccess keeps the existing page URLs and serves assets from frontend/assets/.
- backend/ contains the application code, private configuration, session storage and CLI deployment tools. HTTP access to this directory is denied.
- database/ contains the empty-install schema and ordered migrations.

## cPanel upload archive

Run powershell -NoProfile -ExecutionPolicy Bypass -File tools/build-cpanel-package.ps1 to create dist/whittles-cpanel-upload.zip. Extract that ZIP directly into the domain document root. It contains the frontend, backend runtime, protected SQL files and the Super Admin provisioning command; it omits Git, local configuration, credentials, sessions, test data and development files.

For a new database, import database/schema.sql, database/001_wheettle.sql and database/002_ticket_files.sql in that order. Copy backend/config/config.example.php to backend/config/config.local.php and enter the cPanel database details. Set accounts.superadmin_initial_password there before running backend/tools/provision-accounts.php. The password is used only when creating a missing superadmin account; the account requires a password change at first login. Remove the setting after provisioning.
## Local testing

URL: **http://127.0.0.1:8030/login.php**

This server listens only on this computer. Local demo account usernames are `demo.tl`, `demo.tl2`, `demo.management`, `demo.admin`, and `demo.viewer`. Random local passwords are in `.local/test-accounts.json`, excluded from Git and blocked over HTTP. The file is created on local initialization; passwords are never embedded in the repository.

Start the server from this folder in PowerShell:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/start-local.ps1
```

The server uses `C:\xampp\php\php.exe`, runs on port 8030. It does not change global PHP settings. MySQL must already be running on 127.0.0.1:3306.

For a new developer installation only:

```powershell
C:\xampp\php\php.exe tools/setup-local.php
```

This creates `wheettle_ticketing` using the local XAMPP root account, applies `database/schema.sql` then `database/001_wheettle.sql`, and creates fictional demo records. It refuses an existing database. `--resume-empty-base` is restricted to recovery of a base-only database with no staff, tickets or users. It never drops the database. This is a local development initializer, not a production installer.

## Owner test accounts and deployment accounts

The five owner accounts are `tl`, `superadmin`, `admin1`, `admin2`, and `admin3`.
`tl` is a Team Leader; `superadmin` has full Super Admin access. The three admin accounts use the Management role: read staff/tickets, reports and exports.
Local temporary passwords are in `.local/owner-accounts.json`. Each account must change
its password on first login using the application's password-change form.

To provision these usernames on an installation with the schema and migrations applied:

```powershell
C:\xampp\php\php.exe backend/tools/provision-accounts.php --admin-role=management --output=.local/owner-accounts.json
```

For deployment, configure `backend/config/config.local.php` for the target database, import
`database/schema.sql`, `database/001_wheettle.sql`, and `database/002_ticket_files.sql`
in that order into an empty database, then run the command with the host's PHP CLI
and an output path outside the public document root. Existing databases must already
have the appropriate migrations; do not reimport the base schema. Provisioning creates
only missing accounts, preserves existing passwords/roles/status, and never overwrites
a credentials file. New accounts receive fresh random temporary passwords on each
installation. Do not deploy the local credential file or demo database. Retain the
generated credentials privately for handover and change passwords on first login.

## Attachment migration

Existing installations: run `C:\xampp\php\php.exe backend/tools/migrate-ticket-files.php` before using the updated app. This adds private attachment bytes to the existing metadata table and preserves records. Fresh setup applies the migration automatically. Employee photos are retired; legacy stored data is preserved but not served. PHP Fileinfo and Zip are required.

## Features

- Dashboard, ticket search/filtering, creation, assignment, status, priority, comments, resolution and activity history.
- Employee directory with code, name, position, shift/timezone, department, team, TL, dates, manually selected new/active/exited status.
- Immutable manual history with effective date, author, recorded timestamp and before/after snapshots. Profile edits may not predate the latest profile change; use history-only notes for earlier context.
- Ticket attachments at creation: up to three 2 MB files (PDF, TXT, CSV, JPG/PNG/WebP, DOCX/XLSX), private database storage and authenticated ticket-scoped downloads.
- Department/team overview, transfer history, staff/ticket filters, CSV exports, period reports, and account management.
- Optimistic edit versions prevent stale employee or ticket updates from overwriting newer changes.

## Access rules

### Registration and approval

Choose **Create an account** on the login page. Registration requires email, username,
password, confirmation and a requested role: Team Leader, Admin (Management), or Client Viewer.
The form provides password matching feedback and a Show passwords control. Super Admin is
never available through public registration, including direct form submissions.

Registrations remain pending and inactive until a Super Admin opens **Accounts & TLs** and
chooses **Approve**. Pending requests appear first with their email and requested role.
**Reject** leaves the account unable to sign in. Only approved active users appear in ticket
and employee assignment lists. Registrants use their username as their display name and keep
their chosen password after approval. Accounts created directly by a Super Admin still
require a first-login password change. Emails are collected for manual review; the app does
not send email or verify email ownership. This feature uses the existing user approval
columns and requires no additional database migration.

Run `tests/registration.cjs` for registration, approval, role restrictions and dual-logo layout
checks. It creates fictional accounts and leaves them inactive or rejected.

| Role | Access |
| --- | --- |
| Super Admin | All features; create accounts/departments and activate/deactivate accounts. |
| Team Leader | View and manually manage staff and tickets; reports and exports. TLs currently share all Wheettle staff records. |
| Management | Read staff/tickets, reports and exports. No profile/ticket mutations. |
| Client Viewer | Read ticket list and detail only; no staff/reports/exports. |

Employee profiles and login accounts are separate. No automation updates employment status, sends email or synchronizes external systems. A TL records each employment change manually. Account creation requires an initial password, which the new user changes at first sign-in.

## Report definitions

Current headcounts and team/TL assignments are snapshots now. Starters/exits use the employee's start/exit date in the selected range. Ticket period reports select creation dates and group by current status. History dates refer to event effective dates; department filters select the employee's current department. The department transfer timeline includes both old and new assignments, even after an employee moves. Dates and shift labels use Asia/Manila; shift timezone is explicitly entered by the TL.

Employee lists, ticket lists and staff history are paginated. Ticket conversation shows the latest 50 comments; department overview shows the latest 25 transfers. Full staff history is available through CSV export.

## Verification

All application PHP files pass syntax checks. `tests/browser.cjs` exercises the local app with Chrome and Playwright: authentication, permissions, directory filtering, attachments, employee creation/update/exit, history, stale edits, CSRF, ticket resolution/comments, CSV downloads, and mobile overflow. It retains clearly labeled QA records in the local database for inspection. Screenshots/results are under `.local/qa/`, excluded from Git.

```powershell
$env:NODE_PATH='C:\Users\Admin\Downloads\wts-build-tools\node_modules'
& 'C:\Program Files\nodejs\node.exe' tests/browser.cjs
```

## Documentation and handover

- [System audit, fixes and verification (2026-09-16)](documentation/System-Audit-2026-09-16.md): 113 browser checks, 32 PHP syntax checks, fresh installation and Apache private-path checks. Run `tests/audit.cjs` after the two existing browser suites for the added regression checks.

- [Functionality draft](documentation/Functionality-Draft.md) and [Word document](documentation/Wheettle-Functionality-Draft-Revised.docx).
- [Completion and progress guide](documentation/Completion-and-Progress-Guide.md): work sections, verified tests and remaining owner acceptance.
- [Development work-plan tracker](documentation/Development-Work-Plan-Tracker.md): current Day 0–9 status and actionable release/deployment to-do list.
- [User guide and handover](documentation/User-Guide-and-Handover.md): startup, roles and common workflows.
- [Ten-day development work plan](documentation/Development-Work-Plan-Day-0-to-Day-9.txt): Day 0 documentation through Day 9 deployment; proposed allocation, with actual dates/hours left for the worker to record.
- [cPanel deployment and upload plan](documentation/cPanel-Deployment-Upload-Plan.md): recommended solo-developer schedule with five active days plus two buffer days.
- Supplementary verification: run `tests/handover.cjs` after `tests/browser.cjs` with the same NODE_PATH configuration. It creates a fictional account and leaves it deactivated.

## Project continuity details

Read `AGENTS.md`, then `_brain/AI_BRAIN.md`. AI Nexus source: https://github.com/iantolentino/ai-nexus at commit `0934093b5f45b939d1803adb940b122cf2ee2519`. Follow `_brain/CURRENT_STATE.md` and `_brain/sessions/LATEST_HANDOFF.md` for project-specific state. Specification: `SPEC.md`. Repository: https://github.com/iantolentino/wts.

Production deployment has not been performed. Before deployment, configure a dedicated database user via ignored `backend/config/config.local.php`, enable Fileinfo, Zip and HTTPS, remove local demo accounts/fixtures through a reviewed deployment process, and enforce the equivalent private-directory restrictions in the chosen web server. Do not copy the local credentials or session directory.
