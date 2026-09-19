# Wheettle user guide and technical handover

## Open the local system

1. Ensure XAMPP MySQL is running on port 3306.
2. Open PowerShell in C:\xampp\htdocs\wheettle-ticketing.
3. Run `powershell -NoProfile -ExecutionPolicy Bypass -File tools/start-local.ps1` if the server is not already running. If port 8030 is occupied, check the existing server before restarting anything.
4. Open http://127.0.0.1:8030/login.php on this computer.
5. Use demo.tl for staff/ticket work, demo.admin for account administration, demo.management for read/report access, or demo.viewer for ticket-only access. Passwords are in the local ignored .local/test-accounts.json file; they are not published with this guide.

Do not rerun database initialization on the existing installation. The local URL is not a hosted production service.

For an existing checkout receiving the attachment feature, run `C:\xampp\php\php.exe tools/migrate-ticket-files.php` once before serving the updated app. It is additive and safe to rerun. Fresh setup includes the migration. PHP Fileinfo and Zip must be enabled.

## Employee work

Open Staff, then Add employee. Enter the required profile details, active TL assignment, department, team, dates and status. Provide an effective date and history note. Save, then review the profile and history.

Use Edit profile for changes. Employment status changes are manual. Exited staff require a valid exit date. Record earlier context with Add a history note; that action does not change current profile values. If another user has changed the profile, reload and review their changes before submitting again.

In Staff directory, search by name, employee code or job title. Department and Team leader filters are beside search; expand More filters for team and employment start dates. Status tabs show counts within your current search. Applied filters appear as removable labels; use Clear all to reset. Export results downloads the matching staff. Select View profile for shift schedules, employment dates and history. On smaller screens, each employee appears as a labeled card. Department history filters use current assignments; the transfer timeline retains old and new assignments.

## Ticket work

Open Tickets, then Create ticket, or create a ticket from an employee profile. Select the employee, department, category, priority and optional assignee, and describe the issue. Add up to three optional attachments (2 MB each): PDF, TXT, CSV, JPG/JPEG, PNG, WebP, DOCX or XLSX. Re-select files if validation fails. Download saved files from ticket details.

Use the detail page to add comments or update ownership, priority and status. Enter a resolution to close a ticket. To reopen, select an open status and clear the resolution field; previous changes remain in activity history. Reload after a conflicting-edit message before trying again.

The ticket conversation displays the latest 50 comments. Use list filters to review assigned work and current status.

## Reports and exports

Choose a date range and optional department in Reports. Current headcounts and assignments describe current records; starter/exit totals use employment dates. Ticket-period totals use creation dates and current status. Use CSV exports for staff, history, tickets or report metrics, and apply the same filters when comparing results.

## Accounts and roles

Choose Create an account on the login page to register with an email, username, requested role, password and confirmation. Password matching feedback and Show passwords help you check your entry. Available roles are Team Leader, Admin (Management) and Client Viewer; public registration cannot request Super Admin. Accounts remain pending until approved.

Super Admin reviews pending registrations at the top of Accounts & TLs, checks the email and requested role, then selects Approve or Reject. Approval enables sign-in with the user's chosen password; rejection keeps access blocked. Registration does not send email. The username is also the registrant's display name.

Super Admin can also create accounts directly and activate/deactivate approved accounts. Directly created accounts must change their initial password. A TL with active/new assigned employees must have those employees reassigned before deactivation. Employee profiles do not automatically create login accounts.

Team Leaders manage staff and tickets. Management reads and reports. Client Viewers read tickets. TLs currently share staff access across teams.

## Verification and feedback

See Completion-and-Progress-Guide.md for completed work and test evidence. For a repeat local QA run, set NODE_PATH to the directory containing Playwright, then run `node tests/browser.cjs`, `node tests/handover.cjs` and `node tests/visual.cjs` in that order. These scripts use fictional records; browser.cjs leaves fixtures for inspection, and handover.cjs leaves its created account deactivated.

Provide feedback with: role, page, steps, expected result, actual result and screenshot. Owner acceptance and any new feature requests are the next review stage. Keep passwords and real staff information out of shared issue reports.
