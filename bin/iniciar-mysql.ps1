param([string]$MySqlBin = 'C:\Program Files\MySQL\MySQL Server 8.4\bin')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$mysqlConfig = Join-Path $projectRoot 'var\mysql\my.ini'
$mysqlExecutable = Join-Path $MySqlBin 'mysqld.exe'
if (!(Test-Path -LiteralPath $mysqlConfig) -or !(Test-Path -LiteralPath $mysqlExecutable)) {
    throw 'Esta copia no tiene la instancia local configurada. Sigue docs/mysql.md para conectar tu servidor MySQL.'
}
$listener = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
if ($listener) {
    Write-Host 'El puerto 3306 ya tiene un servidor activo. No se inicia ni se modifica otro servidor.'
    exit 0
}
Start-Process -FilePath $mysqlExecutable -ArgumentList ('--defaults-file="' + $mysqlConfig + '"') -WindowStyle Hidden
Write-Host 'MySQL iniciado para Cantera. Si hay un error, revisa var/mysql/mysql.log.'
