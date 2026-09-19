# cPanel deployment and upload plan

Updated: 2026-09-19

## Recommended duration

Plan for **2 working days** after cPanel access, the domain and database details are available.

- **Day 1:** host preparation, backup, file upload, database setup and configuration.
- **Day 2:** role testing, ticket/database/attachment verification, bug checks, owner review and acceptance.

If the cPanel account, DNS, SSL and database are already ready, the technical work may fit into one day. Keep an additional buffer for DNS or SSL propagation and host-specific issues. Do not count the work as live-complete until the checks below pass on the real host.

## What to upload

Upload the reviewed application source from the release repository into the cPanel document root or an application directory configured as the domain document root:

- Root application PHP files: `index.php`, `login.php`, `logout.php`, `register.php`, `change-password.php`, `dashboard.php`, `staff.php`, `employee.php`, `employee-form.php`, `employee-photo.php`, `history.php`, `departments.php`, `ticket.php`, `create-ticket.php`, `attachment.php`, `reports.php`, `export.php`, and `users.php`.
- `.htaccess`.
- `app/`.
- `assets/`.
- `config/config.example.php` as a reference only; create the real `config/config.local.php` privately on the host.
- `storage/private/.htaccess`; do not copy local attachment contents.

Upload the exact reviewed release. Do not manually mix files from older versions.

## Do not upload

Keep these out of the public cPanel document root and out of the production database:

- `.git/`, `.local/`, `_brain/`, `tests/`, `documentation/` and local QA screenshots/results.
- `config/config.local.php` from the local machine.
- Local credential files, session files, logs, demo accounts, fictional QA records and private attachments.
- Local database dumps or a copy of the local `wheettle_ticketing` database.
- `tools/setup-local.php`, `tools/start-local.ps1` and `tools/router.php`.
- `tools/migrate-ticket-files.php` and `tools/provision-accounts.php` in a publicly reachable directory. If cPanel Terminal is available, use them temporarily from a private location, then remove them or confirm they are blocked.

The root `.htaccess` blocks sensitive directories, but those files should still not be uploaded unnecessarily.

## Database procedure

### Fresh production database

1. Create a dedicated cPanel MySQL database and user.
2. Grant only the permissions required by the application.
3. Import these files in order through phpMyAdmin or a private cPanel database tool:
   - `database/schema.sql`
   - `database/001_wheettle.sql`
   - `database/002_ticket_files.sql`
4. Confirm the expected tables, roles, foreign keys and `ticket_attachments.file_data` column exist.
5. Do not import local demo or QA records.

### Existing compatible database

1. Back up the database and verify the backup is readable.
2. Confirm the base schema and migration history.
3. Apply `database/002_ticket_files.sql` only if the attachment column is missing.
4. Never rerun the base schema against an existing production database.

## Host configuration

Create `config/config.local.php` on the host using production-only values:

- HTTPS application URL.
- Production database host, port, database name, username and password.
- A production session name.

Create the private `.local/sessions` directory with restricted permissions. Confirm PHP has:

- PHP 8.x.
- PDO MySQL.
- Fileinfo.
- Zip.
- mbstring.
- `upload_max_filesize` of at least 2 MB.
- `post_max_size` of at least 8 MB.

Enable HTTPS and confirm the cPanel web server supports the `.htaccess` rules used to block private paths.

## Day 1 upload and setup checks

- [ ] Confirm the hosting provider, domain, document root and deployment access.
- [ ] Back up any existing site and database; download or otherwise verify the backup.
- [ ] Confirm PHP version, extensions, MySQL/MariaDB version and upload limits.
- [ ] Upload only the approved application files listed above.
- [ ] Create production `config/config.local.php`; do not copy the local configuration.
- [ ] Create the private session directory and check its permissions.
- [ ] Import the schema/migrations using the correct fresh or existing-database procedure.
- [ ] Provision `tl`, `superadmin`, `admin1`, `admin2` and `admin3` with fresh temporary passwords using a private output path.
- [ ] Confirm credentials are delivered privately and are not inside `public_html`.
- [ ] Confirm the site loads over HTTPS and redirects or refuses plain HTTP as intended.
- [ ] Confirm the document root does not expose `.git`, `.local`, `_brain`, `app`, `config`, `database`, `tests`, `tools` or `storage`.

## Day 2 functional and security checks

### Login and roles

- [ ] Log in as Super Admin and change the temporary password.
- [ ] Log in as Team Leader and change the temporary password.
- [ ] Log in as Management and confirm read/report/export access.
- [ ] Log in as Client Viewer and confirm ticket-only read access.
- [ ] Confirm Management and Client Viewer cannot create or modify staff, tickets, departments or accounts.
- [ ] Confirm logout and session revocation work.

### UI and workflows

- [ ] Check login, registration, dashboard, staff, employee, history, departments, tickets, reports, exports and account pages.
- [ ] Check desktop and mobile layouts.
- [ ] Create an employee, edit it, record history, change department/team and verify before/after history.
- [ ] Create a ticket, link an employee, assign ownership, add a comment, update priority/status, resolve, close and reopen it.
- [ ] Confirm stale edits are rejected without overwriting newer data.

### Database and CRUD verification

- [ ] Confirm new employee, ticket, comment, assignment, history and attachment records are saved.
- [ ] Confirm edits update the correct rows and activity history records before/after values.
- [ ] Confirm deletion is not exposed where the system requires historical preservation.
- [ ] Confirm reports and CSV exports reconcile with the records just created.
- [ ] Confirm dates, status changes, department filters and ticket totals are correct after reload.

### Attachments and private access

- [ ] Upload one permitted file and verify the downloaded bytes match exactly.
- [ ] Verify invalid type, oversized file and excessive file-count rejection.
- [ ] Verify an authorized ticket user can download the attachment.
- [ ] Verify anonymous users and users without ticket access cannot download it.
- [ ] Verify missing attachments return the expected not-found response.
- [ ] Confirm private configuration, credentials, database files, tools and Git paths are denied over HTTP.

### Final acceptance

- [ ] Review application and server logs for errors after the smoke tests.
- [ ] Confirm backup and rollback instructions are available.
- [ ] Record the live URL, deployed commit, database migration result, test evidence, operator and date.
- [ ] Obtain owner acceptance before treating deployment as complete.

## Rollback trigger

Stop acceptance and restore the approved backup if authentication, database writes, private-file protection, attachment downloads, role restrictions or critical pages fail. Record the failure and retest after correction; do not silently continue with a partially working production release.

