param([ValidateSet('install','start','stop','status')][string]$Action = 'status')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$runtimePath = Join-Path $projectRoot '.runtime'
$mysqlVersion = '8.4.13'
$mysqlRoot = Join-Path $runtimePath "mysql-$mysqlVersion-winx64"
$mysqlBin = Join-Path $mysqlRoot 'bin'
$dataPath = Join-Path $runtimePath 'mysql-data'
$configPath = Join-Path $runtimePath 'mysql.ini'
$adminPath = Join-Path $runtimePath 'mysql-admin.cnf'

if ($Action -eq 'install') {
    if (Test-Path -LiteralPath $dataPath) { throw 'Data directory already exists; installation will not reset it. Use start.' }
    New-Item -ItemType Directory -Force $runtimePath | Out-Null
    if (-not (Test-Path -LiteralPath (Join-Path $mysqlBin 'mysqld.exe'))) {
        $archivePath = Join-Path $runtimePath "mysql-$mysqlVersion-winx64.zip"
        if (-not (Test-Path -LiteralPath $archivePath)) {
            & curl.exe -fL --retry 2 --output $archivePath "https://cdn.mysql.com/Downloads/MySQL-8.4/mysql-$mysqlVersion-winx64.zip"
            if ($LASTEXITCODE -ne 0) { throw 'MySQL download failed.' }
        }
        if ((Get-FileHash -LiteralPath $archivePath -Algorithm MD5).Hash -ne '7B94489D5524DEA9B27097068BEA25A8') { throw 'MySQL archive checksum mismatch.' }
        Expand-Archive -LiteralPath $archivePath -DestinationPath $runtimePath
    }
    & php (Join-Path $PSScriptRoot 'prepare-local-db.php')
    if ($LASTEXITCODE -ne 0) { throw 'Local configuration preparation failed.' }
    & (Join-Path $mysqlBin 'mysqld.exe') "--defaults-file=$configPath" --initialize-insecure
    if ($LASTEXITCODE -ne 0) { throw 'MySQL initialization failed; inspect private runtime error log.' }
}

if ($Action -in @('start','install')) {
    if (-not (Test-Path -LiteralPath (Join-Path $dataPath 'mysql'))) { throw 'MySQL is not initialized. Run -Action install.' }
    $arguments = @("`"--defaults-file=$configPath`"")
    $initPath = Join-Path $runtimePath 'mysql-init.sql'
    if (Test-Path -LiteralPath $initPath) { $arguments += "`"--init-file=$initPath`"" }
    & (Join-Path $mysqlBin 'mysqladmin.exe') "--defaults-extra-file=$adminPath" ping --silent 2>$null
    if ($LASTEXITCODE -ne 0) {
        Start-Process -FilePath (Join-Path $mysqlBin 'mysqld.exe') -ArgumentList $arguments -WindowStyle Hidden
        $ready = $false
        for ($attempt = 0; $attempt -lt 45; $attempt++) {
            Start-Sleep -Milliseconds 500
            & (Join-Path $mysqlBin 'mysqladmin.exe') "--defaults-extra-file=$adminPath" ping --silent 2>$null
            if ($LASTEXITCODE -eq 0) { $ready = $true; break }
        }
        if (-not $ready) { throw 'MySQL did not become ready; inspect .runtime/mysql-error.log.' }
    }
    if (Test-Path -LiteralPath $initPath) { Remove-Item -LiteralPath $initPath }
    Write-Output 'Project MySQL ready on 127.0.0.1:3307 (development/test databases isolated).'
} elseif ($Action -eq 'stop') {
    & (Join-Path $mysqlBin 'mysqladmin.exe') "--defaults-extra-file=$adminPath" shutdown
    if ($LASTEXITCODE -ne 0) { throw 'MySQL shutdown failed.' }
} elseif ($Action -eq 'status') {
    & (Join-Path $mysqlBin 'mysqladmin.exe') "--defaults-extra-file=$adminPath" ping
    if ($LASTEXITCODE -ne 0) { exit 1 }
}
