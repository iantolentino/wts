# Deployment checklist - pending target

For the cPanel-specific upload order, file exclusions, seven-working-day solo-developer schedule and per-phase checks, see `cPanel-Deployment-Upload-Plan.md`.

No production deployment has occurred. Complete this checklist against the chosen host; do not substitute localhost results for live evidence.

## Information required

- Hosting provider/server and authorized deployment method.
- Live domain/URL, document root and HTTPS certificate setup.
- Dedicated database name/user and secure configuration delivery.
- Backup location, recovery owner and acceptable maintenance window.
- Owner-approved live test accounts/data and acceptance contact.

## Prepare

- Choose the reviewed Git commit. Exclude .local, demo credentials, sessions, QA artifacts and real staff exports from deployment.
- Review which sample/demo records must be excluded from the production database. Do not copy the local QA database as production data.
- Confirm PHP 8, PDO MySQL, Fileinfo, Zip, mbstring and supported MySQL/MariaDB. Configure upload_max_filesize at least 2M and post_max_size at least 8M, and verify reverse-proxy request limits.
- Back up any existing target database and application. Record restoration steps and verify the backup can be read before migration.
- Use dedicated database credentials and HTTPS. Configure equivalent private-directory restrictions for the chosen web server; storage/config/tools must not be publicly readable.
- For an existing compatible schema, apply database/002_ticket_files.sql once after checking whether file_data exists. The local migration helper deliberately restricts itself to wheettle_ticketing; do not edit its database guard casually for production.
- Define attachment retention, permitted content and malware-scanning requirements with the owner. Current uploads validate formats but do not provide antivirus scanning.

## Deploy and test

- Deploy the reviewed revision and environment-specific configuration.
- Provision the owner accounts (`tl`, `superadmin`, `admin1`, `admin2`, `admin3`) using `tools/provision-accounts.php --admin-role=management --output=<private-path-outside-document-root>`. See README for fresh schema import order. TL has Team Leader access; superadmin has full Super Admin access; admin1/admin2/admin3 have Management access (read staff/tickets, reports and exports). Existing accounts are preserved; new accounts get random passwords and must change them at first login. Deliver credentials privately; do not copy local passwords or fixtures.
- Confirm migration, login, session behavior and role restrictions.
- Test ticket creation with permitted attachments, invalid file rejection, authorized download and anonymous denial.
- Exercise staff/history, ticket resolution/reopening, reports and exports with approved test data.
- Check HTTPS, response headers, desktop/mobile screens and application/server logs.
- On failure, stop acceptance and use the agreed recovery procedure. Record what was restored and retest.

## Record completion

Live URL: pending. Deployed commit: pending. Date/operator: pending.
Live test evidence: pending. Owner acceptance: pending. Remaining issues: pending target review.
