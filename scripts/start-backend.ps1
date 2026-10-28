param(
    [ValidateRange(1024,65535)][int]$Port = 8000,
    [ValidateSet('local','testing')][string]$Environment = 'local'
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$previousScan = $env:PHP_INI_SCAN_DIR
$uploadConfig = Join-Path $projectRoot 'backend/docker/php'
# An empty scan entry preserves PHP's compiled default scan directory.
$env:PHP_INI_SCAN_DIR = [string]$previousScan + [IO.Path]::PathSeparator + $uploadConfig
Push-Location (Join-Path $projectRoot 'backend')
try {
    & php artisan serve --env=$Environment --host=localhost --port=$Port --no-reload
    if ($LASTEXITCODE -ne 0) { throw 'Backend server stopped with an error.' }
} finally {
    Pop-Location
    $env:PHP_INI_SCAN_DIR = $previousScan
}
