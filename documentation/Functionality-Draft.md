# Wheettle Ticketing & Staff History
## Draft Functionality Document
Version 0.1 | Prepared 15 September 2026 | For owner review and user acceptance testing

Purpose: Describe the implemented user-facing functionality, access rules, operating limits, and proposed testing of the local Wheettle system.

Status: The existing local MVP is available for testing. This document is a draft based on the project requirements, application pages, and shared ticket/staff helpers. Suggested improvements below are proposals, not delivered features.

Progress guide: The project has a progress guide to track completed work, current tasks, testing results and next steps. A separate user guide provides startup instructions, common workflows and handover information.

Local test URL: http://127.0.0.1:8030/login.php
Project folder: C:\xampp\htdocs\wheettle-ticketing
Primary test username: demo.tl. Other roles: demo.admin, demo.management, demo.viewer; second TL: demo.tl2. Passwords remain in the local .local/test-accounts.json file and are not included in this document.

## 1. Purpose and access
The system brings employee information, manually recorded employment history, and employee-related service tickets into one workspace. Employee profiles and login accounts are separate: an employee does not need a login to have a staff record.

- Super Admin: Full staff and ticket access, reports and exports; create accounts and departments; activate or deactivate other accounts.
- Team Leader: View and manage employee records, manual history, and tickets; use reports and exports. TLs currently share all Wheettle staff records, including employees assigned to other TLs.
- Management: Read staff and tickets; use reports and exports. Profile and ticket changes are not permitted.
- Client Viewer: Ticket-focused read access, including ticket overview/list/detail. No staff directory, reports, exports, or record changes.
- Login uses username and password. Accounts created through the app must change their initial password at first sign-in. Users can change their password and sign out.
- Access checks apply on the server as well as in navigation. Authentication includes hashed passwords, session regeneration and sign-in throttling.

## 2. Overview dashboard
- Displays total, active, new and exited staff counts for staff-authorized users, with links to filtered directories.
- Displays recent tickets, ticket numbers, linked employees and current statuses.
- Summarizes open work, closed tickets and urgent open work.
- Shows the latest five staff history updates for staff-authorized users.
- Provides Add employee and Create ticket shortcuts when the signed-in role allows them. Ticket-only viewers receive ticket summary metrics.

## 3. Ticket management
- Browse a paginated ticket list with ticket number/subject, employee, department, assignee, priority, status and creation date.
- Search by ticket, subject or employee; filter by status, priority, department, assignee and creation date range. Employee-linked ticket views can also narrow results to a staff record. Apply and reset filters.
- Create a ticket with required subject, employee, department, category, priority and issue details. Assignment is optional; unassigned tickets are supported. A ticket reference is generated.
- Available categories: Onboarding, General Request, Performance, Resignation, Personal Requests, Attendance and Behavioural Issues. The current creation screen selects a top-level category; nested values in helper configuration are not separate subcategory controls.
- Priorities: Low, Normal, High and Urgent. Statuses: Open, In progress, Pending and Closed.
- View issue details, creator, creation time, category, employee, department, owner and resolution.
- Authorized users can update status, priority, assignee and resolution. Closing requires resolution text; reopening requires clearing that field. Closing and reopening are permission checked.
- Add text comments with author and timestamp. The conversation displays the latest 50 comments.
- Inspect paginated activity history, including recorded changes with before/after values. Ticket updates use version checks to prevent an older form overwriting a newer edit.
- Export filtered tickets to CSV. Export columns include ticket reference, subject, employee, department, category, priority, status, assignee, issue, resolution, creation and closure times.
- The current update form does not provide general editing of the original subject, linked employee, department, category or issue text. Ticket deletion and post-creation attachment management are not exposed in the reviewed screens.

- Attach up to three optional files during ticket creation, maximum 2 MB each: PDF, TXT, CSV, JPG/JPEG, PNG, WebP, DOCX and XLSX. File extension and content are validated; Office containers are checked and macro-enabled documents rejected.
- Attachment bytes and metadata save with the ticket in a database transaction. Ticket details list filenames and sizes. Signed-in users who can view the ticket can download its attachments; downloads are forced rather than rendered inline. Existing employee photo functionality is retired.

## 4. Employee directory and profiles
- Browse and paginate staff records; search name, employee code or position. Filter by employment status, department, team, TL and start-date range.
- Create and edit employee code, full name, position, shift schedule including manually entered timezone, department, team label, assigned TL, start date, exit date, employment status and notes.
- Select New staff, Active or Exited manually. Dates do not automatically change employment status.
- Required profile details include code, name, position, shift schedule, team, valid department, active TL and start date. Employee codes must be unique.
- An exited employee requires an exit date between the start date and today. Non-exited profiles cannot retain an exit date.
- View profile facts, employee-linked tickets and employee-specific history. Authorized users can start an employee-linked ticket or edit the profile.

- Export filtered current staff records to CSV, including code, name, position, shift, department, team, TL, dates, status and notes.
- Profile version checks reject stale saves. The current UI has no employee deletion or bulk import workflow.

## 5. Manual history, departments and teams
- Profile creation and edits require an effective date and explanatory history note. The system records the author, recorded timestamp and before/after profile snapshots.
- History is immutable through the application: existing entries cannot be edited or deleted. Additional history-only notes can record context without changing current profile values.
- Profile changes cannot be future effective-dated or predate the latest profile-changing event. Earlier context belongs in a history-only note.
- Browse global staff history with employee search, current department and effective-date filters. Expand entries to inspect changes; pages contain 25 entries.
- Employee history preserves changes in department, team, TL, position, shift, dates and employment status.
- Departments & teams shows current total, active, new and exited headcounts per department/team, with links to staff and history.
- Super Admin can add a department with a unique name/code. Codes use lowercase letters, digits and hyphens. Team names are profile labels, not a separate team administration module.
- The department/team transfer timeline displays the latest 25 transfers, retaining old/new assignments even after an employee moves.
- History department filters refer to the employee's current department. They do not reconstruct the full department roster at a past date. Full matching staff history can be exported to CSV, including snapshot data.

## 6. Reports and CSV exports
- Select a report period and optionally a department. The default period is the current month to date; invalid date ranges are rejected or surfaced with a validation message.
- Show current active/new headcounts, staff who started in the period and staff whose exit dates fall in the period.
- Show current department/team totals and active counts, plus current TL assignment totals for new and active employees.
- Show tickets created within the selected period, grouped by their current status.
- Download report metrics, current staff, period history and period tickets as CSV. Staff and ticket exports follow their respective filters and permissions.
- CSV output protects spreadsheet cells against formula injection. History exports include before/after snapshots; ticket exports do not include the full comment conversation.
- Interpretation: current headcounts and assignments are today's snapshot; starter/exit counts use start/exit dates; history uses effective dates; ticket reports use creation dates and current status. These are not historical month-end staffing snapshots or a count of tickets closed during the period.
- Application dates use Asia/Manila. Shift timezone is entered manually as part of the shift schedule text.

## 7. Account administration and integrity controls
- Super Admin can list account names, usernames, roles and activation status; create accounts with an initial password; and activate/deactivate other accounts. The current screen excludes self-deactivation.
- Available roles are Team Leader, Management, Client Viewer and Super Admin. Initial account passwords must be 12 to 72 characters in the creation form.
- Active Team Leader accounts populate employee assignment choices. Creating an employee does not automatically create an account.
- Forms use server-side permissions, input/date validation and CSRF tokens. Database writes use prepared statements; staff/ticket updates use transactions and concurrency controls.
- Local development routing restricts private configuration, tools, storage, credentials and project-state paths. The test server binds only to 127.0.0.1.

## 8. Current boundaries and improvement candidates
Current boundaries: manual staff updates; no scheduled employment changes, email notifications, external synchronization, public registration, payroll, attendance clocking, overtime approval or overtime calculation module. Attendance and salary-related request categories are ticket labels, not payroll or timekeeping features. Production deployment has not been completed.

Proposals for owner review:
- Decide whether TL access should remain shared or become restricted to assigned teams.
- Review ticket categories, required fields, resolution rules and whether editable original ticket details or additional attachment formats are needed.
- Decide whether all comments need pagination beyond the latest 50 and whether transfer history needs more than the latest 25 entries on its overview.
- Assess historical headcount snapshots, closure-date metrics and clearer report labels if management needs them.
- Collect usability feedback on staff forms, mobile tables, search, empty states and validation messages.
- Define account recovery and production-readiness requirements before a separate deployment project.

## 9. Local testing guide and acceptance checklist
Start/restart: Open PowerShell in the project folder and run powershell -NoProfile -ExecutionPolicy Bypass -File tools/start-local.ps1. MySQL must be running on port 3306. The launcher uses XAMPP PHP and listens on port 8030. If that port is occupied, inspect the existing server first. Do not rerun database initialization on the existing database.

Verified on 15 September 2026: 45 browser workflow checks, 19 supplementary handover checks, desktop/mobile layout checks and syntax checks for 31 PHP files passed. Coverage includes staff/ticket workflows, ticket file uploads and authenticated downloads, account activation and first-password change, report/export reconciliation and mobile screens. Local screenshots and machine-readable results are retained. Technical handover materials are complete; owner acceptance remains pending.

Suggested owner acceptance tests (pending owner execution):
- AT-01: Sign in with each role; confirm allowed screens and blocked changes match section 1.
- AT-02: Create a fictional employee; verify required-field and duplicate-code errors, initial history and required fields.
- AT-03: Update status, position, TL, team and department; verify effective dates, notes and before/after history. Confirm an older open form cannot overwrite a newer save.
- AT-04: Mark a fictional employee exited; confirm date validation, directory filters and report totals. Add an earlier history-only note.
- AT-05: Create an employee-linked ticket, assign it, change priority/status, add a comment, close with a resolution and reopen with an authorized account.
- AT-06: Search and paginate staff, history and tickets; check no-result states and date filters.
- AT-07: Compare CSV exports and reports to a small known sample, including a staff transfer and tickets created before/inside the selected period.
- AT-08: Review desktop/mobile layouts and ask Management/Client Viewer to verify their read access.
- AT-09: As Super Admin, create a fictional account, test mandatory first-password change and activation/deactivation using a separate account.

Feedback template: Test ID | Role | Page | Steps | Expected result | Actual result | Screenshot | Priority | Requested improvement | Retest result.
Review sign-off: Owner/reviewer __________ | Review date __________ | Approved changes __________.

## 10. Source map
- README.md and SPEC.md: scope, startup, role rules and documented limits.
- dashboard.php, index.php, create-ticket.php and ticket.php: overview and ticket UI/workflows.
- staff.php, employee-form.php, employee.php, history.php: staff screens and history.
- departments.php, reports.php, export.php and users.php: administration, aggregation and CSV output.
- backend/app/wheettle.php and backend/app/tickets.php: field/status/category definitions, validation, filters and concurrency helpers.
- tests/visual.cjs: the browser checks run for this draft.
