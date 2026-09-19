# Session Handoff

## Current objective and result
Owner authorized a whole-system audit and bug fixes. Local audit completed; see documentation/System-Audit-2026-09-16.md.
Fixed first-login logout, initial password reuse, null-byte password errors, malformed/out-of-range dates, multibyte TEXT overflow, resolution `0` loss, TL form default, history-filter inconsistency and Windows/Apache mixed-case private path protection. Corrected ticket creation punctuation and CSV branding.

## Verification
Final: 45 main + 19 handover + 49 audit browser checks; 32 PHP lint checks; 11 isolated fresh-install checks; Apache login 200 and six private route probes 403. Added tests/audit.cjs. Results in ignored .local/qa. Temporary audit database removed; disposable QA accounts deactivated; labeled QA staff/tickets retained. Owner accounts/passwords preserved.

## Local access and branding
http://127.0.0.1:8030/login.php. App uses supplied Whittles logo and visible wording. Owner usernames: tl (team-leader), superadmin (super-admin), admin1/admin2/admin3 (management). Temporary credentials in ignored .local/owner-accounts.json; first login requires changing password.
Deployment provisioning: tools/provision-accounts.php --admin-role=management --output=<private-path>. Preserves existing accounts; creates fresh random credentials only for missing users.

## Next / boundaries
Owner can test fixes locally. Changes are uncommitted; no production deployment or push performed in this audit. Hosting-specific HTTPS/proxy, load and recovery checks remain pending. Do not deploy local credentials/sessions/QA database. cng-ticketing untouched.

## Git Baseline

- Base commit: c6c1f19af1821483009c5019908a8bfcc8a4a80f
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: 113 browser checks, 32 PHP lint checks, 11 fresh-install checks, Apache private-path checks passed
