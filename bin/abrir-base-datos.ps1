param([switch]$SinNavegador)
$ErrorActionPreference = 'Stop'
Write-Host 'En XAMPP deben estar iniciados Apache y MySQL.'
Write-Host 'Abre http://localhost/phpmyadmin y selecciona Cantera XAMPP 3306.'
Write-Host 'Usuario: cantera. Usa la clave de DATABASE_URL en .env.local.'
if (!$SinNavegador) { Start-Process 'http://localhost/phpmyadmin' }
