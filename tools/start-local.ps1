$ErrorActionPreference = 'Stop'
$wtsRoot = Split-Path $PSScriptRoot -Parent
$wtsPhp = 'C:\xampp\php\php.exe'
if (Get-NetTCPConnection -LocalPort 8030 -State Listen -ErrorAction SilentlyContinue) {
    throw 'Port 8030 is already in use. Check the existing server before starting another.'
}
$wtsProcess = Start-Process -FilePath $wtsPhp -ArgumentList @('-d','extension=gd','-S','127.0.0.1:8030','-t',$wtsRoot,(Join-Path $PSScriptRoot 'router.php')) -WorkingDirectory $wtsRoot -WindowStyle Hidden -RedirectStandardOutput (Join-Path $wtsRoot '.local/server-output.log') -RedirectStandardError (Join-Path $wtsRoot '.local/server-error.log') -PassThru
Write-Output "Wheettle server PID: $($wtsProcess.Id)"
Write-Output 'Local URL: http://127.0.0.1:8030/login.php'
