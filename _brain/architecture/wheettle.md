# Wheettle architecture

Active project architecture; legacy/example architecture files are not Wheettle requirements.

- Runtime: standalone PHP 8, MySQL/MariaDB InnoDB, HTML/CSS. Source design assets, auth/ticket helpers and sanitized relational schema reused from owner-provided cng-ticketing.
- Layout: `frontend/` contains public PHP entry pages and assets; `backend/app/` contains shared PHP helpers, `backend/config/` contains the private deployment config, and `backend/storage/` contains sessions/private data. `database/` contains fresh-install schema and additive migrations. Root `.htaccess` routes legacy top-level URLs into `frontend/` and denies direct access to private/source folders.
- Shared boot: `backend/app/bootstrap.php` loads isolated config/session, database, auth/security, ticket helpers, staff helpers and layout.
- Staff: `frontend/staff.php`, `frontend/employee-form.php`, `frontend/employee.php`, `frontend/history.php`, `backend/app/wheettle.php`. staff_directory is the current profile; staff_history stores immutable dated before/after snapshots; legacy staff_photos data is retained but no longer exposed.
- Tickets: `frontend/index.php`, `frontend/create-ticket.php`, `frontend/ticket.php`, `backend/app/tickets.php`. Original tickets/assignees/departments/comments/activity tables retained; tickets.staff_id links an employee.
- Live audit 2026-09-23: the schema has `deleted_at` and the Super Admin has `delete_tickets`, but deployed `frontend/ticket.php` does not implement delete UI/action; ticket subject and issue details are not editable after creation.
- Reporting: `frontend/reports.php`, `frontend/departments.php`, `frontend/export.php`. Prepared filter queries, permission checks, CSV formula escaping.
- Accounts: `frontend/login.php`, `frontend/logout.php`, `frontend/change-password.php`, `frontend/users.php`. Password hashing, CSRF, regenerated sessions, role permissions and sign-in throttling. Public registration requires Super Admin approval; no email integration.
- Persistence: `database/schema.sql`, then `database/001_whettle.sql` and `database/002_ticket_files.sql` for a fresh database. Existing deployments apply only outstanding migrations. Profile/ticket edits use version checks, row locks and transactional history.
- Local operations: `tools/setup-local.php`, `tools/start-local.ps1`, `tools/router.php`. Database wheettle_ticketing; loopback port 8030. `.local` contains ignored credentials/sessions/logs/screenshots. Source project/database untouched.
- Verification: 36 browser workflow checks, final desktop/mobile screenshots, PHP lint. Tests use fictional records only. See releases/wheettle-mvp-verification.md.

- Scope update 2026-09-15: app/attachments.php and attachment.php provide validated ticket uploads and authenticated downloads. Migration 002 adds private file_data to ticket_attachments. Employee photo functionality retired. Day 0-9 planning and deployment boundaries are in documentation/.
- Deployment package: run `tools/build-cpanel-package.ps1` to create `dist/whittles-cpanel-upload.zip`. Its allowlist excludes `_brain`, docs, tests, local config/secrets, and developer files. The cPanel instructions are in `documentation/cPanel-Deployment-Upload-Plan.md`.
