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

- None. Owner completed GitHub device authorization as iantolentino. HTTPS authentication configured, main pushed, remote/local SHA match verified on 2026-09-11.

## Relevant Files

- SPEC.md, README.md, CURRENT_STATE.md, tests/browser.cjs, tests/visual.cjs, tools/start-local.ps1.

## Next Action

Await owner testing feedback and implement the requested changes. Repository https://github.com/iantolentino/wts now contains the app and brain. Do not rerun database setup or repeat passing workflow tests unless code changes.

## Warnings

- Never edit or delete C:/xampp/htdocs/cng-ticketing or connect Wheettle to its database.
- Never commit .local, config/config.local.php, credentials, sessions or real staff data.
- Always provide the local test URL and a local test account in handoffs to the owner. Do not claim GitHub push succeeded without verifying it.

## Git Baseline

- Base commit: 7c464d92de307cd98f805b4370b7297940c8537b
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: main pushed and remote/local SHA matched; local login HTTP 200. Prior 36 workflow checks and PHP lint remain valid; this session changed only brain records.
