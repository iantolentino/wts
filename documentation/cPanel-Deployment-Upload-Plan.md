# cPanel upload package and database setup

This guide applies after the domain and MySQL database have been created in cPanel. The application has not been deployed until the extracted files, database connection, first Super Admin login and live checks all succeed.

## Build and upload the ZIP

From the project folder, run:

    powershell -NoProfile -ExecutionPolicy Bypass -File tools/build-cpanel-package.ps1

The output is dist/whittles-cpanel-upload.zip. Upload it into the document root assigned to this domain, then extract it in place. The ZIP has the folder layout the PHP application expects:

- .htaccess
- frontend/ with PHP pages and assets/
- backend/ with private application code, config template, runtime storage restrictions and the CLI account provisioner
- database/ with the schema and SQL migrations

The package builder checks that hidden .htaccess files and all three SQL files are present. It rejects local credentials, session files, test data, Git metadata and development documentation.

## Configure the database

In phpMyAdmin, select the database you created. For a fresh empty database, use Import once per file in this order:

1. database/schema.sql
2. database/001_wheettle.sql
3. database/002_ticket_files.sql

Wait for the success message after each import. The first file creates the base tables and roles, migration 001 adds Wheettle staff history and permissions, and migration 002 adds private ticket attachment bytes. Do not import schema.sql into an existing application database. Back up an existing database first and apply only migrations it is missing.

## Configure the application and Super Admin

In cPanel File Manager, copy backend/config/config.example.php to backend/config/config.local.php and edit the copy. Enter the database host, port, full cPanel database name, full database username and password. Hosting commonly uses localhost for the database host; use the value provided by the host. Set the application base URL to the domain's HTTPS URL and set a unique value for accounts.superadmin_initial_password (12 to 72 characters).

If cPanel Terminal is enabled, open the application document root and run the following, replacing CPANEL_USER and the output path with the account's actual values:

    php backend/tools/provision-accounts.php --admin-role=management --output=/home/CPANEL_USER/whittles-owner-credentials.json

The command creates only missing owner accounts. It uses the configured initial password for superadmin, generates random passwords for the other new owner accounts, and preserves any existing accounts. Keep the output file outside public_html. The Super Admin must change the initial password on first login; then remove the initial password from config.local.php.

The account provisioner is CLI-only and the backend directory is denied over HTTP. If Terminal is unavailable, ask the hosting provider to enable a private PHP CLI session; do not make a public web installer.

## First live checks

- Confirm https://your-domain/login.php and the domain root load.
- Confirm CSS, logos and browser icon load.
- Confirm /backend/config/config.example.php, /backend/tools/provision-accounts.php and /database/schema.sql return 403.
- Sign in as superadmin, change the initial password, sign out, then sign in again.
- Check the application logs and confirm no database or PHP errors.
- Keep a database backup and the provisioning credentials file outside the document root until the owners have securely received their passwords.
