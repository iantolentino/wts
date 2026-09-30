$ErrorActionPreference='Stop'
$fetcherRoot='C:\xampp\htdocs\wts-fetch-test'
$outputPath='C:\Users\Admin\.local\wts-fetch-api\wts-fetch-departments-update.zip'
$files=@('api.php','adapters.php','index.php')
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
if(Test-Path -LiteralPath $outputPath){Remove-Item -LiteralPath $outputPath}
$zip=[System.IO.Compression.ZipFile]::Open($outputPath,[System.IO.Compression.ZipArchiveMode]::Create)
try{foreach($name in $files){[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip,(Join-Path $fetcherRoot $name),$name,[System.IO.Compression.CompressionLevel]::Optimal)|Out-Null}}finally{$zip.Dispose()}
$verify=[System.IO.Compression.ZipFile]::OpenRead($outputPath)
try{if($verify.Entries.Count-ne$files.Count -or @($verify.Entries|Where-Object{$_.FullName-notin$files}).Count-gt0){throw 'Unexpected files in fetcher details ZIP.'}}finally{$verify.Dispose()}
Write-Output "Created $outputPath (api.php, adapters.php and index.php only)."
