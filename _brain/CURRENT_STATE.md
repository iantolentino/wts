# Current State

> Compact active state only. Do not write a project history here. Every important fact should name its source when one exists.

## Current objective

Complete and verify Wheettle ticketing/manual staff history, then commit and push to iantolentino/wts.

## Active work

- Feature/project: Wheettle (WTS), C:/xampp/htdocs/wheettle-ticketing.
- Implementation state: local MVP implemented and verified. Source CNG project untouched.
- Relevant files/modules: SPEC.md, tools/setup-local.php, tools/start-local.ps1, tests/, app/wheettle.php.

## Important facts

- Keep Strata Staff logo and favicon. No Jamesons/CNG records, credentials or integrations. Manual TL updates; no automation. Source: SPEC.md, owner conversation.
- Local database: wheettle_ticketing; verified loopback URL: http://127.0.0.1:8030/login.php. PHP GD enabled per test-server process. tools/start-local.ps1 restarts the server.
- Portable Git: C:/Users/Admin/Downloads/wts-build-tools/git/cmd/git.exe. Origin points to owner-authorized https://github.com/iantolentino/wts.git.
- GitHub CLI was unauthenticated on initial check. Credentials belong only in ignored .local/test-accounts.json; never publish them.

## Blockers and bugs

- No failing application checks. First setup's duplicate permission seed fixed and database initialized successfully. 36 workflow checks passed plus PHP lint and final desktop/mobile checks. Source: .local/qa/results.json (local evidence), tests/browser.cjs and tests/visual.cjs.
- GitHub publication still requires a push attempt; initial gh authentication was absent.

## Immediate next action

Commit verified app/brain, attempt push, then report local test URL/account and exact GitHub status.

## Last significant decision

Reuse source CSS, auth/ticket helpers and sanitized relational schema; employee history uses immutable before/after snapshots with optimistic edit versions. Source: app/wheettle.php and database/001_wheettle.sql.

Last updated: 2026-09-11
