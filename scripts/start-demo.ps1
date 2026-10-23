param([ValidateSet('backend','frontend')][string]$Component='backend')
$projectRoot = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path -LiteralPath (Join-Path $projectRoot 'backend/.env.demo'))) { throw 'Run php scripts/prepare-demo.php first.' }
if ($Component -eq 'backend') {
    Push-Location (Join-Path $projectRoot 'backend/public')
    $opcacheArgs = @('-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1')
    if ((php -r "echo extension_loaded('Zend OPcache') ? '1' : '0';") -ne '1') { $opcacheArgs += @('-d','zend_extension=opcache') }
    try { php @opcacheArgs -S localhost:18081 (Join-Path $projectRoot 'scripts/demo-router.php') } finally { Pop-Location }
} else {
    $previousBackend = $env:LARAVEL_INTERNAL_URL
    $previousDemo = $env:NEXT_PUBLIC_DEMO_MODE
    $env:LARAVEL_INTERNAL_URL='http://localhost:18081'
    $env:NEXT_PUBLIC_DEMO_MODE='1'
    Push-Location (Join-Path $projectRoot 'frontend')
    try { npm run dev } finally { Pop-Location; $env:LARAVEL_INTERNAL_URL=$previousBackend; $env:NEXT_PUBLIC_DEMO_MODE=$previousDemo }
}
