<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Credenciales opcionales de la base MySQL aislada; nunca las de producción.
if (is_file(__DIR__.'/.env.local')) {
    (new Dotenv())->load(__DIR__.'/.env.local');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
