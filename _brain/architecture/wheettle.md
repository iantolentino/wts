# Wheettle architecture

Active project architecture; legacy/example architecture files are not Wheettle requirements.

- Runtime: standalone PHP 8, MySQL/MariaDB InnoDB, HTML/CSS. Source design assets, auth/ticket helpers and sanitized relational schema reused from owner-provided cng-ticketing.
- Shared boot: app/bootstrap.php loads isolated config/session, database, auth/security, ticket helpers, staff helpers and layout.
- Staff: staff.php, employee-form.php, employee.php, employee-photo.php, history.php, app/wheettle.php. staff_directory is the current profile; staff_history stores immutable dated before/after snapshots; staff_photos stores sanitized JPEG data separately.
- Tickets: index.php, create-ticket.php, ticket.php, app/tickets.php. Original tickets/assignees/departments/comments/activity tables retained; tickets.staff_id links an employee.
- Reporting: reports.php, departments.php, export.php. Prepared filter queries, permission checks, CSV formula escaping.
- Accounts: login.php, logout.php, change-password.php, users.php. Password hashing, CSRF, regenerated sessions, role permissions and sign-in throttling. No public registration, automation or email integration.
- Persistence: database/schema.sql then database/001_wheettle.sql. Fresh database only. Profile/ticket edits use version checks, row locks and transactional history.
- Local operations: tools/setup-local.php, tools/start-local.ps1, tools/router.php. Database wheettle_ticketing; loopback port 8030. .local contains ignored credentials/sessions/logs/screenshots. Source project/database untouched.
- Verification: 36 browser workflow checks, final desktop/mobile screenshots, PHP lint. Tests use fictional records only. See releases/wheettle-mvp-verification.md.
