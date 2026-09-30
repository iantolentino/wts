$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Split-Path $PSScriptRoot -Parent)).Path
$outputPath = Join-Path $projectRoot 'dist/manual-whittle-layout-update'
$files = @('frontend/index.php', 'frontend/dashboard.php', 'frontend/external-tickets.php', 'frontend/ticket.php', 'frontend/assets/css/ticket-overview.css', 'backend/app/layout.php', 'backend/app/feed.php', 'backend/app/mixed-tickets.php')
New-Item -ItemType Directory -Force -Path $outputPath | Out-Null
$manifest = @()
foreach ($name in $files) {
    $source = Join-Path $projectRoot $name
    $target = Join-Path $outputPath $name
    New-Item -ItemType Directory -Force -Path (Split-Path $target -Parent) | Out-Null
    Copy-Item -LiteralPath $source -Destination $target -Force
    $sourceHash = (Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash
    if ((Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash -ne $sourceHash) { throw "Manual upload copy differs: $name" }
    $manifest += [pscustomobject]@{ path = $name; sha256 = $sourceHash }
}
$manifest | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $outputPath 'MANIFEST.json') -Encoding UTF8
@'
MANUAL WHITTLE UPLOAD - NO ZIP

The live Tickets page was verified on 2026-09-29 to be serving older PHP/layout files and the earlier stylesheet. Hard refresh cannot install the replacement server files.

Back up the existing files first with the current whittles-feed-backup.php tool, or download the matching files in cPanel File Manager.

Find the Whittle document root containing .htaccess, frontend and backend. Upload each of these eight files into the matching path, choosing Overwrite:

frontend/index.php
frontend/dashboard.php
frontend/external-tickets.php
frontend/ticket.php
frontend/assets/css/ticket-overview.css
backend/app/layout.php
backend/app/feed.php
backend/app/mixed-tickets.php

Important: the index.php displayed at the top-level website URL is served from frontend/index.php by the existing rewrite. Replace frontend/index.php; do not place it at the document root. The stylesheet belongs in frontend/assets/css/. The shared layout belongs in backend/app/.

Upload dependencies feed.php and mixed-tickets.php first, then the stylesheet, shared layout and page files. The existing backend/config/feed.local.php and application/database configuration stay in place. This folder contains no token or password configuration. Do not upload MANIFEST.json or this instruction file.

After upload, open /index.php and /dashboard.php. The sidebar should say Dashboard, All tickets/read-only intro should be gone, search/filters should be compact, and CSS should load as ticket-overview.css?v=20260929-layout2. Whittle colors/logos remain and there is no notification button.

This upload changes Whittle only. CNG remains the read-only reference. WTS does not need another upload to fix the layout.
'@ | Set-Content -LiteralPath (Join-Path $outputPath 'UPLOAD.txt') -Encoding UTF8
$actual = @(Get-ChildItem -LiteralPath $outputPath -Recurse -File | ForEach-Object { $_.FullName.Substring($outputPath.Length + 1).Replace('\', '/') })
$allowed = $files + @('MANIFEST.json', 'UPLOAD.txt')
if ($actual.Count -ne $allowed.Count -or @($actual | Where-Object { $_ -notin $allowed }).Count -gt 0) { throw 'Unexpected files in manual upload folder.' }
Write-Output "Prepared eight verified runtime files: $outputPath"
Write-Output 'No ZIP, token, passwords or database configuration included.'
