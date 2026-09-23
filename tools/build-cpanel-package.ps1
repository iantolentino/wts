$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path -LiteralPath (Split-Path $PSScriptRoot -Parent)).Path
$outputDirectory = Join-Path $projectRoot 'dist'
$outputPath = Join-Path $outputDirectory 'whittles-cpanel-upload.zip'
if (-not $outputPath.StartsWith($projectRoot + '\', [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Package output escaped the project directory: $outputPath"
}
if (-not (Test-Path -LiteralPath $outputDirectory)) {
    New-Item -ItemType Directory -Path $outputDirectory | Out-Null
}

$includedPaths = @(
    '.htaccess',
    'frontend',
    'backend/.htaccess',
    'backend/app',
    'backend/config/config.example.php',
    'backend/storage/.htaccess',
    'backend/storage/private/.htaccess',
    'backend/tools/provision-accounts.php',
    'database'
)
$packageFiles = [System.Collections.Generic.List[System.IO.FileInfo]]::new()
foreach ($relativePath in $includedPaths) {
    $source = Join-Path $projectRoot $relativePath
    if (-not (Test-Path -LiteralPath $source)) {
        throw "Required deployment path is missing: $relativePath"
    }
    if ((Get-Item -LiteralPath $source).PSIsContainer) {
        Get-ChildItem -LiteralPath $source -File -Recurse -Force | ForEach-Object { $packageFiles.Add($_) }
    } else {
        $packageFiles.Add((Get-Item -LiteralPath $source))
    }
}

$entries = @($packageFiles | ForEach-Object {
    $relative = $_.FullName.Substring($projectRoot.Length).TrimStart([char[]]@([char]92, [char]47))
    [pscustomobject]@{ File = $_; Name = $relative.Replace('\', '/') }
})
$forbidden = @($entries | Where-Object {
    $_.Name -match '(^|/)(\.local|_brain|tests|documentation)/|(^|/)config\.local\.php$|(^|/)(owner|test)-accounts\.json$|(^|/)storage/private/(?!\.htaccess$)'
})
if ($forbidden.Count -gt 0) {
    throw "Private or development files entered the package: $($forbidden.Name -join ', ')"
}

$requiredEntries = @(
    '.htaccess',
    'frontend/login.php',
    'backend/.htaccess',
    'backend/app/bootstrap.php',
    'backend/config/config.example.php',
    'backend/tools/provision-accounts.php',
    'database/schema.sql',
    'database/001_wheettle.sql',
    'database/002_ticket_files.sql'
)
foreach ($required in $requiredEntries) {
    if ($entries.Name -notcontains $required) { throw "Deployment package is missing $required" }
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
if (Test-Path -LiteralPath $outputPath) { Remove-Item -LiteralPath $outputPath -Force }
$archive = [System.IO.Compression.ZipFile]::Open($outputPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($entry in $entries) {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $entry.File.FullName,
            $entry.Name,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
    }
} finally {
    $archive.Dispose()
}

$verify = [System.IO.Compression.ZipFile]::OpenRead($outputPath)
try {
    $actualEntries = @($verify.Entries | ForEach-Object { $_.FullName })
    foreach ($required in $requiredEntries) {
        if ($actualEntries -notcontains $required) { throw "ZIP validation failed: $required is absent" }
    }
    $badEntries = @($actualEntries | Where-Object {
        $_ -match '(^|/)(\.local|_brain|tests|documentation)/|(^|/)config\.local\.php$|(^|/)(owner|test)-accounts\.json$|(^|/)storage/private/(?!\.htaccess$)'
    })
    if ($badEntries.Count -gt 0) { throw "ZIP validation found private or development files: $($badEntries -join ', ')" }
} finally {
    $verify.Dispose()
}

$zipInfo = Get-Item -LiteralPath $outputPath
Write-Output "Created $($zipInfo.FullName)"
Write-Output "Included $($entries.Count) files; compressed size $($zipInfo.Length) bytes."
