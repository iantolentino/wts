# Wheettle local MVP verification — 2026-09-11

Verified against http://127.0.0.1:8030 with fictional local records.

- 36 Playwright browser workflow checks passed: sign-in and role boundaries, protected directories, employee creation/photo upload/update/transfer/exit, staff history notes, stale profile edit rejection, CSRF rejection, directory filters, ticket linkage/resolution/comments, staff/ticket/history/report CSV downloads, management read-only access, admin account page, mobile overflow, and browser errors.
- Final read-only desktop/mobile visual checks passed; screenshots reviewed. Fixed source CSS conflicts in sidebar offsets, cards, headings and timelines.
- All application PHP files passed php -l.
- Review: prepared queries, escaped user content, protected photo route, image validation/re-encoding, atomic profile/ticket changes, optimistic versions, authorization, and CSV formula escaping inspected.
- Local test passwords, sessions, logs and screenshots are ignored and were explicitly checked for exclusion from the Git commit.
- No production deployment or GitHub upload verified. Initial push blocked on missing GitHub authentication; follow CURRENT_STATE.md.

Local-only evidence: .local/qa/results.json and screenshot files. Reproducible tests: tests/browser.cjs and tests/visual.cjs. Browser workflow tests retain QA records for inspection.
