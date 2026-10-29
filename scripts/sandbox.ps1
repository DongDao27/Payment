param([ValidateSet('start', 'stop', 'status')][string]$Action = 'start')

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$runtimeDir = Join-Path $projectRoot '.runtime'
$sandboxDir = Join-Path $runtimeDir 'sandbox-backend'
$receiptPath = Join-Path $runtimeDir 'sandbox-services.json'
$phpExe = (Get-Command php -ErrorAction Stop).Source
$services = @()
if (Test-Path -LiteralPath $receiptPath) {
    $loaded = Get-Content -LiteralPath $receiptPath -Raw | ConvertFrom-Json
    $services = @($loaded)
}

function Get-OwnedProcess($service) {
    $process = Get-Process -Id $service.ProcessId -ErrorAction SilentlyContinue
    if ($process -and $process.ProcessName -eq 'php' -and
        $process.StartTime.ToUniversalTime().ToString('o') -eq $service.StartedUtc) {
        return $process
    }
    return $null
}

function Stop-ProcessTree([int]$serviceId) {
    foreach ($child in @(Get-CimInstance Win32_Process -Filter "ParentProcessId = $serviceId")) {
        Stop-ProcessTree ([int]$child.ProcessId)
    }
    Stop-Process -Id $serviceId -ErrorAction SilentlyContinue
}

if ($Action -eq 'stop') {
    foreach ($service in $services) {
        if (Get-OwnedProcess $service) {
            Stop-ProcessTree ([int]$service.ProcessId)
            Write-Output "Stopped Sandbox $($service.Name)"
        }
    }
    Write-Output 'MySQL, frontend, tunnel and all saved data were kept.'
    exit 0
}

if ($Action -eq 'status') {
    foreach ($name in @('http', 'queue', 'scheduler')) {
        $service = $services | Where-Object Name -eq $name | Select-Object -First 1
        $state = if ($service -and (Get-OwnedProcess $service)) { 'running' } else { 'stopped' }
        Write-Output "Sandbox ${name}: $state"
    }
    foreach ($port in @(3307, 8000, 3000)) {
        $listening = @(Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue).Count -gt 0
        Write-Output "Port ${port}: $listening"
    }
    Write-Output 'Readiness and running processes do not prove a successful VNPAY transaction.'
    exit 0
}

foreach ($file in @('artisan', '.env', 'vendor/autoload.php')) {
    if (-not (Test-Path -LiteralPath (Join-Path $sandboxDir $file))) {
        throw 'Private Sandbox workspace is missing. Follow the Sandbox setup guide; this command never recreates or resets databases.'
    }
}
$envText = [IO.File]::ReadAllText((Join-Path $sandboxDir '.env'))
if ($envText -notmatch '(?m)^DB_DATABASE=thu_thanh_sandbox\r?$') {
    throw 'Refusing to start: expected private DB_DATABASE=thu_thanh_sandbox.'
}
$httpService = $services | Where-Object Name -eq 'http' | Select-Object -First 1
$portOccupied = @(Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue).Count -gt 0
if ($portOccupied -and -not ($httpService -and (Get-OwnedProcess $httpService))) {
    throw 'Port8000 is occupied by another process. Stop that backend in its terminal first; it will not be killed by this script.'
}

& (Join-Path $PSScriptRoot 'mysql-local.ps1') -Action start
if ($LASTEXITCODE -ne 0) { throw 'MySQL did not start.' }

$certDir = Join-Path $runtimeDir 'certs'
$certPath = Join-Path $certDir 'cacert.pem'
if (-not (Test-Path -LiteralPath $certPath)) {
    New-Item -ItemType Directory -Force -Path $certDir | Out-Null
    $downloadPath = Join-Path $certDir 'cacert.pem.download'
    Invoke-WebRequest -UseBasicParsing -Uri 'https://curl.se/ca/cacert.pem' -OutFile $downloadPath -TimeoutSec 30
    $checksumResponse = Invoke-WebRequest -UseBasicParsing -Uri 'https://curl.se/ca/cacert.pem.sha256' -TimeoutSec 30
    $checksum = if ($checksumResponse.Content -is [byte[]]) {
        [Text.Encoding]::ASCII.GetString($checksumResponse.Content)
    } else {
        [string]$checksumResponse.Content
    }
    $expected = ($checksum.Trim() -split '\s+')[0]
    if ($expected -notmatch '^[a-fA-F0-9]{64}$') { throw 'Invalid CA checksum response.' }
    if ((Get-FileHash -LiteralPath $downloadPath -Algorithm SHA256).Hash -ne $expected) {
        throw 'CA checksum mismatch; refusing to use the download.'
    }
    Move-Item -LiteralPath $downloadPath -Destination $certPath
}
if ([IO.File]::ReadAllText($certPath) -notmatch '-----BEGIN CERTIFICATE-----') {
    throw 'CA file is invalid.'
}
$phpScanDir = Join-Path $sandboxDir 'docker/php'
New-Item -ItemType Directory -Force -Path $phpScanDir | Out-Null
$iniPath = Join-Path $phpScanDir 'cacert.ini'
$certIniPath = $certPath.Replace('\', '/')
[IO.File]::WriteAllText($iniPath, "curl.cainfo=`"$certIniPath`"`nopenssl.cafile=`"$certIniPath`"`n", [Text.Encoding]::ASCII)

$previousScanDir = $env:PHP_INI_SCAN_DIR
$previousLocation = Get-Location
try {
    $env:PHP_INI_SCAN_DIR = ";$phpScanDir"
    Set-Location -LiteralPath $sandboxDir
    & $phpExe artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'Cannot clear Sandbox config.' }
    & $phpExe artisan app:environment-check
    if ($LASTEXITCODE -ne 0) { throw 'Sandbox database/runtime is unavailable.' }
    & $phpExe artisan payments:check-sandbox
    if ($LASTEXITCODE -ne 0) { throw 'Sandbox credentials/configuration are incomplete. Edit only the private Sandbox .env.' }

    $jobs = @(
        @{Name='http'; Args=@('artisan', 'serve', '--host=localhost', '--port=8000', '--no-reload')},
        @{Name='queue'; Args=@('artisan', 'queue:work', 'database', '--tries=5', '--timeout=60')},
        @{Name='scheduler'; Args=@('artisan', 'schedule:work')}
    )
    foreach ($job in $jobs) {
        $existing = $services | Where-Object Name -eq $job.Name | Select-Object -First 1
        if ($existing -and (Get-OwnedProcess $existing)) {
            Write-Output "Sandbox $($job.Name) is already running."
            continue
        }
        $process = Start-Process -FilePath $phpExe -ArgumentList $job.Args -WorkingDirectory $sandboxDir -WindowStyle Hidden -PassThru `
            -RedirectStandardOutput (Join-Path $sandboxDir "storage/logs/sandbox-$($job.Name).out.log") `
            -RedirectStandardError (Join-Path $sandboxDir "storage/logs/sandbox-$($job.Name).err.log")
        $services = @($services | Where-Object Name -ne $job.Name) + @(@{Name=$job.Name; ProcessId=$process.Id; StartedUtc=$process.StartTime.ToUniversalTime().ToString('o')})
        $services | ConvertTo-Json | Set-Content -LiteralPath $receiptPath -Encoding utf8
    }
    $ready = $false
    for ($attempt = 0; $attempt -lt 20; $attempt++) {
        try {
            Invoke-RestMethod 'http://localhost:8000/api/v1/storefront' -TimeoutSec 2 | Out-Null
            $ready = $true
            break
        } catch { Start-Sleep -Milliseconds 500 }
    }
    if (-not $ready) { throw 'Sandbox HTTP did not become ready; inspect private storage/logs/sandbox-http logs.' }
    Write-Output 'Sandbox backend8000, queue and scheduler are ready. Start frontend separately, then use http://localhost:3000.'
    Write-Output 'Merchant/env/key/database were kept. Payment success still requires verified provider confirmation.'
} finally {
    Set-Location -LiteralPath $previousLocation
    $env:PHP_INI_SCAN_DIR = $previousScanDir
}
