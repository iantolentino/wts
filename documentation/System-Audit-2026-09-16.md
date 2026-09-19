# Whittles system audit - 2026-09-16

## Result

Completed a local source review and workflow audit of authentication, role enforcement,
employee records/history, tickets, attachments, filters, reports/exports, account management,
private HTTP paths, and fresh database/account provisioning. All final checks passed.
This is local verification; target hosting, HTTPS/proxy configuration, production load,
backup restoration and live acceptance still need target-specific testing.

## Bugs fixed

| Finding | Correction |
| --- | --- |
| Accounts awaiting their first password change could not open sign-out confirmation. | Allow authenticated access to logout while retaining the password-change restriction on other pages. |
| Reusing the initial password satisfied the mandatory password change. | Reject a new password that matches the current password. |
| Null bytes in new passwords could trigger a PHP hashing error. | Validate passwords on account creation and password change before hashing. |
| Malformed dates containing null bytes caused report/export server errors; unsupported database years were accepted. | Validate exact date format and the supported year range before parsing. |
| Long multibyte ticket text could fit the character limit but exceed the database TEXT byte limit. | Reject invalid UTF-8 and text above 65,535 bytes with a form validation message. |
| A ticket resolution of `0` was stored as NULL and hidden. | Distinguish an empty string from the valid string `0` during storage and display. |
| The new employee form did not select the current TL as intended. | Initialize the new profile's TL explicitly. |
| Invalid history dates reset the visible filters but retained hidden department/search filters in the query. | Build the query from the same validated/reset filters displayed in the form and export link. |
| Apache private-directory matching was case-sensitive on the Windows filesystem. | Make private path and protected filename matching case-insensitive. Verified mixed-case requests return 403. |

Also corrected broken punctuation on the ticket creation page, renamed CSV downloads to
`whittles-*`, and updated the browser suite to expect the supplied Whittles logo.

## Final verification

- `tests/browser.cjs`: 45 checks passed, including staff lifecycle, history, concurrent edit
  rejection, CSRF, ticket lifecycle, attachments, exports and role restrictions.
- `tests/handover.cjs`: 19 checks passed, including account/password lifecycle, session
  revocation, reopening tickets, report reconciliation and mobile layouts.
- `tests/audit.cjs`: 49 checks passed, including the fixes above, authenticated forbidden
  mutations, anonymous route protection and mixed-case private paths.
- PHP syntax: all 32 application/configuration/tool PHP files passed.
- Fresh installation: 11 checks passed using a newly created isolated audit database.
  Applied schema and both migrations, provisioned TL/superadmin/three Management accounts,
  verified hashes and first-login change flags, confirmed rerun preservation, confirmed
  credential-file collision rolls back all account inserts, and checked no staff/ticket
  fixtures were imported. The temporary database was removed afterwards.
- XAMPP Apache: login returned 200; six private path probes returned 403, including mixed-case
  credentials/config paths. The port 8030 PHP router was checked separately by the audit suite.

## Data and evidence

Existing owner accounts, passwords and business records were preserved. Browser tests added
clearly labeled fictional QA staff/tickets/history. Disposable QA login accounts were left
deactivated. The audit's temporary resolution edit was restored; its test activity remains
in that QA ticket's history. Credentials remain in ignored `.local` files.

Machine-readable results: `.local/qa/results.json`, `.local/qa/handover-results.json`,
and `.local/qa/audit-results.json`. Screenshots are in `.local/qa/`.

Run the browser suites in this order with the local app running and the existing fictional
demo credentials present: `tests/browser.cjs`, `tests/handover.cjs`, `tests/audit.cjs`.
Use the NODE_PATH and Node command documented in README. Do not run fixture tests on production.
