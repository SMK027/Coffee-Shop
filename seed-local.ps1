$ErrorActionPreference = 'Stop'

$docker = Join-Path $env:LOCALAPPDATA 'Programs\DockerDesktop\resources\bin\docker.exe'
if (-not (Test-Path $docker)) {
    $docker = 'docker'
}

& $docker compose -f docker-compose.dev.yml ps --services --filter status=running |
    Select-String -Quiet '^app$' |
    Out-Null

if ($LASTEXITCODE -ne 0) {
    throw 'Le service Docker app n’est pas démarré. Lancez d’abord la stack avec docker compose -f docker-compose.dev.yml up -d.'
}

& $docker compose -f docker-compose.dev.yml exec app php artisan db:seed --force
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}