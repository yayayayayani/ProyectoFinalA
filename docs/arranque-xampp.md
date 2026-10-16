# Cantera desde XAMPP

## Arranque diario

1. Abre el panel de XAMPP (también puedes ejecutar `iniciar.bat`).
2. Pulsa **Start** en **Apache** y **MySQL**.
3. Abre **http://127.0.0.1:8000** para la aplicación o **http://localhost/phpmyadmin** para la base.

No ejecutes `php -S`: Apache ya atiende el puerto 8000. El botón MySQL de XAMPP
inicia MariaDB 10.4.32, que ahora contiene la base activa `cantera_torneo` en el puerto 3306.
No necesitas iniciar el MySQL 8.4 del puerto 3307.

El dominio del usuario es **https://lacantera.lol**. Cloudflared está instalado como
servicio automático de Windows, separado del panel de XAMPP. Debe seguir ejecutándose
para el acceso público. No se cambió su configuración: Apache conserva el origen
`http://127.0.0.1:8000`. La laptop debe seguir encendida, conectada y sin suspenderse.

## Consultar la base

En phpMyAdmin selecciona **Cantera XAMPP 3306** y entra con `cantera` y la contraseña
configurada en `DATABASE_URL` de `.env.local` (se conservó la contraseña anterior).
Abre `cantera_torneo`, selecciona una tabla y pulsa **Examinar**.
Las cuentas de la aplicación, como `admin`, son distintas de las cuentas de MySQL.

Para dar acceso a un compañero o árbitro, ejecuta desde la carpeta del proyecto:

```powershell
php bin/console app:crear-admin nombre_companero --rol=admin
php bin/console app:crear-admin nombre_arbitro --rol=arbitro
```

El comando pide una contraseña oculta. Los administradores pueden añadir y editar
registros desde el dominio; los árbitros solo consultan fichas, nóminas e incidencias.
Los visitantes solo ven la información básica del torneo. Ninguna cuenta de la web
concede acceso directo a MySQL.

Workbench también funciona con `127.0.0.1`, puerto `3306`, usuario `cantera`.
La base es MariaDB, aunque XAMPP llama **MySQL** al botón y phpMyAdmin use ese nombre.

## Qué se cambió

- Se importaron las ocho tablas, conservando identificadores, hashes de contraseñas y datos.
- Se compararon filas y hashes de contenido de cada tabla antes y después de importar.
- Las fotografías siguen en `var/uploads/jugadores`; no se movieron ni se borraron.
- `.env.local` apunta a MariaDB 3306 y declara su versión real para Doctrine.
- Las pruebas usan otra base: `cantera_torneo_test`, también en XAMPP.
- Apache incluye `config/xampp/cantera.conf` desde `C:/xampp/apache/conf/extra/httpd-vhosts.conf`.
- El DocumentRoot de Cantera es exclusivamente `public`. Los alias administrativos de
  XAMPP están bloqueados en el puerto 8000 que usa el túnel.
- phpMyAdmin queda en Apache, puerto 80; se retiró el servidor auxiliar del puerto 8081.
- `iniciar.bat` abre el panel; no inicia el servidor PHP ni MySQL 8.4 por separado.

Las rutas de Apache corresponden a esta laptop. Si mueves el proyecto, actualiza las
rutas en `config/xampp/cantera.conf` y su Include en Apache antes de reiniciarlo.

## Respaldo de la migración

La ruta exacta del respaldo está en `var/backups/ultima-migracion-xampp.txt`.
Contiene los volcados SQL original y compatible con MariaDB, la comparación de datos,
la configuración anterior y las credenciales locales originales. Es privado y no se incluye en Git.
El directorio de datos del MySQL anterior se conserva sin borrar y su proceso quedó apagado.
Los registros nuevos se guardan únicamente en XAMPP; el respaldo no se sincroniza.

Los PDF antiguos describen la instalación anterior; esta guía
describe el arranque actual. El nombre de la carpeta `migrations/mysql` se conserva
por compatibilidad; las migraciones admiten MySQL y MariaDB.

Referencias técnicas: [Symfony con Apache](https://symfony.com/doc/current/setup/web_server_configuration.html)
y [migración lógica de MySQL a MariaDB](https://mariadb.com/docs/server/server-management/install-and-upgrade-mariadb/migrating-to-mariadb/moving-from-mysql).
