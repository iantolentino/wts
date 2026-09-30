# Production setup checks — 2026-09-24

Target: `https://whittles-ticketing.stratastaff.com/login.php`

## Current results

| Check | Result |
| --- | --- |
| HTTPS login, root redirect, CSS | Passed. TLS requests completed without certificate errors; login and CSS returned 200, root returned 302 to login. |
| Plain HTTP | Passed after upload. `/login.php` returns 301 to the HTTPS URL. |
| Private paths | Passed. Backend config, CLI provisioner, database schema, `_brain`, `.local`, and direct `frontend/login.php` requests returned 403 over HTTPS. |
| Five initially supplied account passwords | Failed. `tl`, `superadmin`, `admin1`, `admin2`, and `admin3` each received the application's invalid-credentials response. The owner then supplied a corrected Super Admin password, which reached `/dashboard.php`. No further retries of the four stale passwords are planned. |
| Account provisioning and roles | Passed. Four new accounts were created and completed forced password change, fresh login, and role checks. The four old non-Super Admin accounts were then deactivated. Super Admin stayed active. |
| New authenticated database write | Passed. With Super Admin access, QA ticket 1 was saved with its existing field values. Its version advanced from 4 to 5 on a fresh read; status and priority stayed unchanged. This adds one ticket history entry but creates no new ticket. |

The 2026-09-23 production audit independently recorded five successful logins, one QA employee write, ten ticket create/update/close sequences, attachment storage/download, and report exports. Four account passwords were changed during that audit, and its provisioning output was already stale. These are historical results, not a substitute for a new authenticated check.

## Final account list

| Username | Role | Status |
| --- | --- | --- |
| `superadmin` | Super Admin | Active |
| `tl.new` | Team Leader | Active |
| `admin1.new` | Management | Active |
| `admin2.new` | Management | Active |
| `admin3.new` | Management | Active |
| `tl` | Team Leader | Inactive |
| `admin1` | Management | Inactive |
| `admin2` | Management | Inactive |
| `admin3` | Management | Inactive |

The four replacement accounts used unique random initial and final passwords. Each completed first-login password change, a separate fresh login, and role-route checks. `tl.new` could open ticket creation and was denied account administration. Each new Management account could open reports and was denied ticket creation. Final credentials are in ignored local file `.local/replacement-accounts-20260924.json`; no passwords are in this report or the deployment ZIP.

## Fix prepared

The root `.htaccess` redirects plain HTTP requests for this production host to HTTPS. `dist/whittles-cpanel-upload.zip` was rebuilt with 51 allowlisted files and contains the updated rule. The owner uploaded the root `.htaccess` to the live host on 2026-09-24. Local development hosts are excluded from the redirect. The ZIP does not contain `backend/config/config.local.php`.

## Post-upload verification

After upload, HTTP `/login.php` returned 301 with the HTTPS URL in `Location`; HTTPS `/login.php` returned 200 and set a session cookie with `Secure`, `HttpOnly`, and `SameSite=Lax`. All six private path probes still returned 403.

Four replacement accounts were created, changed passwords, and left active; the four old non-Super Admin accounts were deactivated. The root `.htaccess` was uploaded. Existing QA ticket 1's version and history changed during the write test; its status and priority stayed unchanged. Failed sign-ins may also have updated login-attempt counters.
