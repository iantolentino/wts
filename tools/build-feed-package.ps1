$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Split-Path $PSScriptRoot -Parent)).Path
$packagePath = Join-Path $projectRoot 'dist/whittles-dashboard-layout-departments-private-upload.zip'
$files = @('frontend/index.php', 'frontend/external-tickets.php', 'backend/app/feed.php', 'backend/app/mixed-tickets.php', 'backend/app/layout.php', 'backend/config/feed.local.php', 'frontend/dashboard.php', 'frontend/ticket.php', 'frontend/assets/css/ticket-overview.css')
foreach ($name in $files) {
    if (-not (Test-Path -LiteralPath (Join-Path $projectRoot $name) -PathType Leaf)) { throw "Missing feed deployment file: $name" }
}
New-Item -ItemType Directory -Force -Path (Join-Path $projectRoot 'dist') | Out-Null
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
if (Test-Path -LiteralPath $packagePath) { Remove-Item -LiteralPath $packagePath }
$zip = [System.IO.Compression.ZipFile]::Open($packagePath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($name in $files) {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, (Join-Path $projectRoot $name), $name, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally { $zip.Dispose() }
$verify = [System.IO.Compression.ZipFile]::OpenRead($packagePath)
try {
    $entries = @($verify.Entries | ForEach-Object { $_.FullName })
    if ($entries.Count -ne $files.Count -or @($entries | Where-Object { $_ -notin $files }).Count -gt 0) { throw 'Unexpected files in the feed patch ZIP.' }
} finally { $verify.Dispose() }
Write-Output "Created private deployment package: $packagePath"
Write-Output 'Contains nine runtime files including the private API token config. Keep it off Git and remove the uploaded ZIP after extraction.'
