# Wheettle architecture

Active project architecture. Legacy/template architecture files are not the Wheettle design.

- Runtime: standalone PHP 8, MySQL/MariaDB InnoDB, vanilla HTML/CSS. Source styling and schema copied/sanitized from owner-provided cng-ticketing.
- Shared boot: app/bootstrap.php loads isolated config/session, database, auth/security, ticket helpers, staff helpers and layout.
- Staff module: staff.php, employee-form.php, employee.php, employee-photo.php, history.php, app/wheettle.php. Staff_directory is the current profile; staff_history stores immutable dated before/after snapshots. staff_photos stores sanitized JPEG data separately.
- Ticket module: index.php, create-ticket.php, ticket.php, app/tickets.php. Original tickets/assignees/departments/comments/activity tables retained. tickets.staff_id links an employee.
- Reporting: reports.php, departments.php, export.php. Prepared filter queries; permission checks before exports; CSV spreadsheet-formula escaping.
- Accounts: login.php, logout.php, change-password.php, users.php. Password hashing, CSRF, session regeneration, per-role permissions, sign-in throttling. No public registration or emails.
- Persistence: base database/schema.sql followed by database/001_wheettle.sql. Fresh database only. Profile and ticket updates lock/version-check records and commit history atomically.
- Local operations: tools/setup-local.php, tools/start-local.ps1, tools/router.php. No source database writes. Loopback server port 8030. .local contains only ignored runtime credentials/sessions/logs/screenshots.
- Quality evidence: 36 browser workflow checks and final desktop/mobile screenshots; application PHP lint. Tests create only fictional local records.
