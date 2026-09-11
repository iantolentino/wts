# Session Handoff

## Objective

Deliver Wheettle ticketing and manual staff history with local user testing and GitHub backup at iantolentino/wts.

## Completed

- Created C:/xampp/htdocs/wheettle-ticketing, cloned empty wts remote, installed current AI Nexus brain.
- Adapted CNG design/helpers/schema, retained Strata Staff logo/favicon, removed source staff records and old branding. Original source untouched.
- Implemented employee photos, new/active/exited staff, TL/position/shift assignments, manual dated history, department/team transfers, ticket workflows, filters, CSV exports, reports and account management.
- Initialized isolated wheettle_ticketing database with fictional demo and QA fixtures; random passwords only in ignored .local/test-accounts.json.
- Passed 36 browser workflow checks, final desktop/mobile checks and application PHP lint. Fixed duplicate schema seed and inherited layout collisions.

## Current State

Verified local MVP. Test URL: http://127.0.0.1:8030/login.php. TL username demo.tl; admin demo.admin; management demo.management. Read the ignored credential file only for local testing; do not publish passwords. Restart server using tools/start-local.ps1. No production deployment.

## Decisions

- TLs manually manage all Wheettle employee records; profiles are distinct from login accounts. Optimistic versions protect simultaneous updates.
- Immutable history snapshots preserve department/team/position/shift/TL changes and dates. Photo bytes are authenticated and re-encoded.
- See decisions/ADR-001-wheettle-foundation.md and architecture/wheettle.md. Older framework example decisions are not Wheettle requirements.

## Blockers

- Implementation committed locally as 9c95ba0. Push to origin main failed because GitHub credentials are absent. No remote backup yet. User must complete gh auth login; never collect a token in chat.

## Relevant Files

- SPEC.md, README.md, CURRENT_STATE.md, tests/browser.cjs, tests/visual.cjs, tools/start-local.ps1.

## Next Action

After owner authentication: prepend C:/Users/Admin/Downloads/wts-build-tools/git/cmd to the process PATH; run gh auth setup-git, push main to origin, and verify remote commit. Do not rerun database setup or repeat passing workflow tests unless code changes.

## Warnings

- Never edit or delete C:/xampp/htdocs/cng-ticketing or connect Wheettle to its database.
- Never commit .local, config/config.local.php, credentials, sessions or real staff data.
- Always provide the local test URL and a local test account in handoffs to the owner. Do not claim GitHub push succeeded without verifying it.

## Git Baseline

- Base commit: 9c95ba06fdd5561d4a1654e552f3bddb8f1d3f98
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: 36 browser workflow checks, final desktop/mobile checks, PHP lint and credential exclusion scan passed. A subsequent handoff-only commit records this baseline; GitHub push remains blocked on authentication.
