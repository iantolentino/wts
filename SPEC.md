# Wheettle Ticketing and Staff History

Approved by the project owner in the conversation on 2026-09-10.

Reuse CNG ticketing design and schema in a separate application. Keep Strata Staff logos and favicon. Remove Jamesons and CNG branding and customer records. Source C:/xampp/htdocs/cng-ticketing must remain untouched.

Core: tickets, creation with optional file attachments, updates and history; employee reference profiles, position, shift schedule, TL, department/team, start date, exit date and status; new/active/exited staff lists; manually entered effective-dated staff and department/team history; search, filters, CSV exports and reports. No automations. Employee records are separate from login accounts. TLs manage staff; management can read and report; administrators manage accounts. All changes require authenticated permission checks.

Use fresh local database wheettle_ticketing. Test fixtures must be fictional. Local-only test accounts and passwords must never be committed. Preserve AI Nexus project state, decisions and handoffs. Repository: https://github.com/iantolentino/wts.git. Always include the local URL and local test account in user handoffs, distinguishing verified from planned access.

Scope update approved 2026-09-15: remove employee photo functionality; provide ticket attachments during creation and authenticated downloads. Ten-day planning breakdown is Day 0 through Day 9; production deployment remains pending hosting details.
