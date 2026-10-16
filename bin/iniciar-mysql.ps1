param([string]$MySqlBin = 'C:\Program Files\MySQL\MySQL Server 8.4\bin')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$mysqlConfig = Join-Path $projectRoot 'var\mysql\my.ini'
$mysqlExecutable = Join-Path $MySqlBin 'mysqld.exe'
if (!(Test-Path -LiteralPath $mysqlConfig) -or !(Test-Path -LiteralPath $mysqlExecutable)) {
    throw 'Esta copia no tiene la instancia local configurada. Sigue docs/mysql.md para conectar tu servidor MySQL.'
}
$portLine = Get-Content -LiteralPath $mysqlConfig | Where-Object { $_ -match '^port=\d+$' } | Select-Object -First 1
$mysqlPort = [int]($portLine -replace '^port=', '')
$listener = Get-NetTCPConnection -LocalPort $mysqlPort -State Listen -ErrorAction SilentlyContinue
if ($listener) {
    Write-Host "El puerto $mysqlPort ya tiene un servidor activo. No se inicia ni se modifica otro servidor."
    exit 0
}
Start-Process -FilePath $mysqlExecutable -ArgumentList ('--defaults-file="' + $mysqlConfig + '"') -WindowStyle Hidden
Write-Host 'MySQL iniciado para Cantera. Si hay un error, revisa var/mysql/mysql.log.'
