$ErrorActionPreference = 'Stop'
Write-Host 'Cantera ahora usa MariaDB de XAMPP en el puerto 3306.'
Write-Host 'En el panel de XAMPP, inicia Apache y MySQL.'
Write-Host 'El MySQL anterior del puerto 3307 queda como respaldo, apagado.'
if (!(Get-Process -Name xampp-control -ErrorAction SilentlyContinue)) {
    Start-Process 'C:\xampp\xampp-control.exe'
}
