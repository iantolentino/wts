# FIX LOG

> Read this file when the bug-fix context selection or symptom suggests a prior issue may match. It is the compact memory of solved bugs; most entries should need nothing more than this table.

---

## Format

```
| ID   | Title                        | Category  | Root Cause (1 line)          | Detail File          | Date       | Status |
|------|------------------------------|-----------|-------------------------------|-----------------------|------------|--------|
| F001 | [Short bug description]     | WEB       | [One-line cause]              | inline / F001-slug.md | YYYY-MM-DD | FIXED  |
```

Categories: `WEB` | `BACKEND` | `DB` | `AUTH` | `BUILD` | `DEPLOY` | `AUTOMATION` | `CLI` | `INFRA` | `OTHER`

Status: `FIXED` | `WORKAROUND` (not a real fix, revisit) | `SUPERSEDED` (see linked replacement)

---

## Log

| ID | Title | Category | Root Cause (1 line) | Detail File | Date | Status |
|----|-------|----------|----------------------|-------------|------|--------|
| F001 | Fresh schema duplicate permission grant | DB | Super-admin received manage_recruitment twice; final insert now ignores existing grants. | inline | 2026-09-11 | FIXED |
| F002 | Copied theme doubled layout offsets | WEB | Source theme padded body while new shell positions its own sidebar and header; reset inherited body padding. | inline | 2026-09-11 | FIXED |

---

## Usage Rule

- Skim the table only. Open a detail file ONLY if its title matches the current problem.
- If no match exists, proceed with normal debugging, then add a new row here before stopping.
- Keep "Root Cause" to one line — that line is what future AI sessions scan for a match.
