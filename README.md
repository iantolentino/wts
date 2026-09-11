# Wheettle — Ticketing & Staff History

An independent PHP/MySQL application adapted from the owner's CNG ticketing design, authentication/ticket helpers and relational schema. Strata Staff logo and favicon are retained. The source project is not modified. No source employee data, credentials, private attachments or integrations are included.

## Local testing

URL: **http://127.0.0.1:8030/login.php**

This server listens only on this computer. Local demo account usernames are `demo.tl`, `demo.tl2`, `demo.management`, `demo.admin`, and `demo.viewer`. Random local passwords are in `.local/test-accounts.json`, excluded from Git and blocked over HTTP. The file is created on local initialization; passwords are never embedded in the repository.

Start the server from this folder in PowerShell:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/start-local.ps1
```

The server uses `C:\xampp\php\php.exe`, enables GD for this process, and runs on port 8030. It does not change global PHP settings. MySQL must already be running on 127.0.0.1:3306.

For a new developer installation only:

```powershell
C:\xampp\php\php.exe tools/setup-local.php
```

This creates `wheettle_ticketing` using the local XAMPP root account, applies `database/schema.sql` then `database/001_wheettle.sql`, and creates fictional demo records. It refuses an existing database. `--resume-empty-base` is restricted to recovery of a base-only database with no staff, tickets or users. It never drops the database. This is a local development initializer, not a production installer.

## Features

- Dashboard, ticket search/filtering, creation, assignment, status, priority, comments, resolution and activity history.
- Employee directory with code, name, position, shift/timezone, department, team, TL, dates, manually selected new/active/exited status and optional photo.
- Immutable manual history with effective date, author, recorded timestamp and before/after snapshots. Profile edits may not predate the latest profile change; use history-only notes for earlier context.
- Photo validation and conversion to JPEG; originals and metadata are not retained. Photos are served through authenticated access.
- Department/team overview, transfer history, staff/ticket filters, CSV exports, period reports, and account management.
- Optimistic edit versions prevent stale employee or ticket updates from overwriting newer changes.

## Access rules

| Role | Access |
| --- | --- |
| Super Admin | All features; create accounts/departments and activate/deactivate accounts. |
| Team Leader | View and manually manage staff and tickets; reports and exports. TLs currently share all Wheettle staff records. |
| Management | Read staff/tickets, reports and exports. No profile/ticket mutations. |
| Client Viewer | Read ticket list and detail only; no staff/photos/reports/exports. |

Employee profiles and login accounts are separate. No automation updates employment status, sends email or synchronizes external systems. A TL records each employment change manually. Account creation requires an initial password, which the new user changes at first sign-in.

## Report definitions

Current headcounts and team/TL assignments are snapshots now. Starters/exits use the employee's start/exit date in the selected range. Ticket period reports select creation dates and group by current status. History dates refer to event effective dates; department filters select the employee's current department. The department transfer timeline includes both old and new assignments, even after an employee moves. Dates and shift labels use Asia/Manila; shift timezone is explicitly entered by the TL.

Employee lists, ticket lists and staff history are paginated. Ticket conversation shows the latest 50 comments; department overview shows the latest 25 transfers. Full staff history is available through CSV export.

## Verification

All application PHP files pass syntax checks. `tests/browser.cjs` exercises the local app with Chrome and Playwright: authentication, permissions, directory filtering, photos, employee creation/update/exit, history, stale edits, CSRF, ticket resolution/comments, CSV downloads, and mobile overflow. It retains clearly labeled QA records in the local database for inspection. Screenshots/results are under `.local/qa/`, excluded from Git.

```powershell
$env:NODE_PATH='C:\Users\Admin\Downloads\wts-build-tools\node_modules'
& 'C:\Program Files\nodejs\node.exe' tests/browser.cjs
```

## Project continuity

Read `AGENTS.md`, then `_brain/AI_BRAIN.md`. AI Nexus source: https://github.com/iantolentino/ai-nexus at commit `0934093b5f45b939d1803adb940b122cf2ee2519`. Follow `_brain/CURRENT_STATE.md` and `_brain/sessions/LATEST_HANDOFF.md` for project-specific state. Specification: `SPEC.md`. Repository: https://github.com/iantolentino/wts.

Production deployment has not been performed. Before deployment, configure a dedicated database user via ignored `config/config.local.php`, enable GD and HTTPS, remove local demo accounts/fixtures through a reviewed deployment process, and enforce the equivalent private-directory restrictions in the chosen web server. Do not copy the local credentials or session directory.
