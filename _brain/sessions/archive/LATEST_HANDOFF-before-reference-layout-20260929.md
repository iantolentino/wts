# Session Handoff

## Current status - 2026-09-29

Latest request completed locally: fix viewed ticket formatting and show all permitted local/external totals, status counts and graphs in Overview. Shared backend/app/mixed-tickets.php mixed_ticket_load drives both index/dashboard; summary reconciles known and unknown statuses, origins, urgent active tickets and six creation months by current active/closed status. Overview recent rows mixed, status/source drilldowns match index. Team Members retain own local rows; feed failure explicitly marks local-only counts and hides unavailable source bars. Staff stats retained collapsibly; separate Reports remains local-only.

Ticket views rewritten for title/status header, readable conversation, details/attachment panels and mobile. backend/app/feed.php whittle_feed_html uses DOM tag allowlist, strips scripts/styles/events/embeds/remote images and unsafe URLs; fallback escaped plain text. Source notes/activity/custom details preserved; source GET-only edits blocked. Local multiline text and multiassignment labels/latest comment date shown; local POST write handler matches Git HEAD unchanged.

Ready: dist/whittles-overview-ticket-layout-private-upload.zip (nine files including unchanged private API token); dist/whittles-feed-backup.php updated nine-target backup. Guide: documentation/Overview-Ticket-Layout-Upload.md. Upload backup tool into live frontend, sign in Super Admin/download/delete; extract private ZIP to Whittle document root. Supersedes earlier Whittle full-details/mixed ZIPs. Generic cPanel ZIP rebuilt, excludes private configs. Source C:/Users/Admin/.local/wts-fetch-api/wts-fetch-full-details-update.zip unchanged; apply previous source update if not already deployed. WTS configs/password/token unchanged.

Passed 30 source/aggregation, 23 feed/format, 54 isolated browser and 19 backup checks (126), PHP lint, byte/allowlist checks and desktop/mobile visual review. Screenshots in ignored .local/overview-screenshots. Real local MySQL still cannot initialize; actual SQL and write flows require post-upload checks. No DB repair/reset, migration, production writes or deployment performed. Keep private config/token and ZIPs out of Git.

Previous full-details handoff archived in sessions/archive/LATEST_HANDOFF-before-overview-20260929.md. Older fixes and uncommitted work preserved.

## Git Baseline

- Base commit: d3bfe1695067d5a885f1431b06ce098d31da245e
- Branch: main
- Working tree at handoff: has uncommitted changes
- Verification: 126 checks (30 aggregation/source, 23 feed/format, 54 browser, 19 backup); PHP lint, ZIP allowlist/bytes, desktop/mobile visual review. Owner live upload pending.
