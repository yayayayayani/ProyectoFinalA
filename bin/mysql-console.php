<?php

// Muestra los datos de conexión sin imprimir la contraseña.
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__).'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$url = parse_url($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
if (($url['scheme'] ?? null) !== 'mysql') {
    fwrite(STDERR, "La aplicación no está configurada para MySQL.\n");
    exit(1);
}
echo 'Servidor: '.($url['host'] ?? '127.0.0.1').':'.($url['port'] ?? 3306).PHP_EOL;
echo 'Base: '.ltrim($url['path'] ?? '', '/').PHP_EOL;
echo 'Usuario: '.($url['user'] ?? '').PHP_EOL;
echo "La contraseña está en DATABASE_URL, en .env.local. No se muestra aquí.\n";
