# ADR-001: Independent Wheettle foundation

Date: 2026-09-11. Status: accepted from owner instructions.

Reuse the existing CNG PHP design/assets, authentication/ticket helpers and sanitized schema in C:/xampp/htdocs/wheettle-ticketing. Preserve the source and keep Strata Staff branding/favicon. No Jamesons staff seed data, credentials, private storage or external integrations are copied.

Employee profiles are separate from login accounts. TLs manually update profiles and add history. History stores effective date, recorded timestamp, author and before/after snapshots. Current profiles use edit versions and transactions. Photos are validated/re-encoded and stored in a separate database table; authenticated routes serve them. TLs share all Wheettle records in this MVP; management reads/reports, client viewers see tickets only.

Fresh database wheettle_ticketing. Test server is loopback 127.0.0.1:8030 with GD enabled per process. Random demo passwords stay in ignored .local/test-accounts.json; do not commit or publish them. Git remote is owner-authorized iantolentino/wts. AI Nexus framework installed at upstream commit 0934093b5f45b939d1803adb940b122cf2ee2519.
