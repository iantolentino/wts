# Session Handoff

## Objective and delivered
Owner requested local testing plus TL, super admin and three admin accounts, also available for future deployment.
Local app is running at http://127.0.0.1:8030/login.php. Accounts: tl (team-leader), superadmin (super-admin), admin1/admin2/admin3 (management). Owner clarified admins mean Management; local roles corrected, passwords unchanged. Deployment documentation uses --admin-role=management.
Credentials: ignored .local/owner-accounts.json. All five require password change on first sign-in. Existing users and fixtures preserved.

## Implementation and verification
CLI-only tools/provision-accounts.php creates missing usernames using configured DB, fresh random passwords and exclusive private credential output. Existing users/passwords/roles are preserved. README and deployment checklist document fresh schema import and live provisioning.
Passed: PHP lint, idempotent rerun, five browser login/password-change checks, HTTP denial for credentials and provisioner. Role correction verified effective read/report/export grants and absence of user/staff/ticket write grants for all three admins.

## Next and constraints
Owner can test now. Production hosting/configuration remains unspecified; no production deployment or commit/push this session. Run provisioner on deployment after schema setup, with output outside document root. Do not deploy local credentials, sessions or demo database. Source cng-ticketing untouched.

## Git Baseline

- Base commit: c6c1f19af1821483009c5019908a8bfcc8a4a80f
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: [tests/checks run, or not yet run]
## Latest branding update
- Owner supplied assets/Whittles Body Corp.webp. Login/sidebar/browser icon now use it; visible app name is Whittles, including titles/header/footer/sign-out. PHP syntax and desktop/mobile browser checks passed; screenshots in .local/whittles-*.png. No commit/push or deployment this turn.

