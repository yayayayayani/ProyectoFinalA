# MySQL: configuración y conservación de los datos

La aplicación usa **MySQL Community Server 8.4.9**. SQLite dejó de ser la base
activa; `var/torneo.db` se conserva como respaldo de los datos anteriores.

## En esta computadora

Se utiliza el MySQL instalado en `C:/Program Files/MySQL/MySQL Server 8.4`.
XAMPP incluye MariaDB; no se utilizó para sustituir el requisito de MySQL.
Se configuró una instancia independiente, sin cambiar bases anteriores:

- Servidor: `127.0.0.1`
- Puerto: `3306`
- Base: `cantera_torneo`
- Usuario de la aplicación: `cantera`
- Contraseña: en `DATABASE_URL` dentro de `.env.local` (archivo privado).
- Datos del servidor local: `var/mysql/data/`; no modificar sus archivos a mano.
- Configuración: `var/mysql/my.ini`.
- Fotografías: `var/uploads/jugadores/`, como antes.

Después de reiniciar Windows, desde la carpeta del proyecto:

```powershell
& 'C:/Program Files/MySQL/MySQL Server 8.4/bin/mysqld.exe' --defaults-file=C:/Users/dayli/ProyectoFinalA/var/mysql/my.ini
```

Mantén esa terminal abierta. En otra terminal inicia la aplicación:

```powershell
php -S 127.0.0.1:8000 -t public bin/router.php
```

Si tu configuración de PowerShell permite scripts locales, también puedes usar
`./bin/iniciar-mysql.ps1` para arrancar MySQL en segundo plano. El script no detiene
ni modifica un servidor que ya esté usando el puerto 3306. No inicies el MySQL de
XAMPP simultáneamente en el mismo puerto. No se instaló un servicio de Windows.

Puedes explorar `cantera_torneo` con MySQL Workbench usando los datos anteriores.
En la URL, la contraseña está entre `cantera:` y `@127.0.0.1`.
No publiques ni incluyas `.env.local` en entregas.

## Instalar en otra computadora o hosting

1. Instala MySQL 8.4 y PHP 8.2+ con `pdo_mysql`.
2. Crea una base `cantera_torneo` con `utf8mb4` y un usuario con permisos sobre ella.
3. Copia el proyecto, ejecuta `composer install` y crea `.env.local`:

```dotenv
DATABASE_URL="mysql://USUARIO:CLAVE@127.0.0.1:3306/cantera_torneo?serverVersion=8.4.9&charset=utf8mb4"
```

Adapta versión, puerto y credenciales al servidor. Los caracteres especiales de
usuario/contraseña deben codificarse para URL. Las claves locales generadas usan
caracteres hexadecimales para evitar esa ambigüedad.

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:demo
php bin/console doctrine:schema:validate
```

El comando Demo crea registros ficticios y copia sus avatares. Las migraciones
MySQL están en `migrations/mysql/`. Las migraciones SQLite originales se conservaron,
pero Doctrine ya no las ejecuta. No ejecutes las migraciones SQLite contra MySQL.

## Cómo se copiaron los datos existentes

Después del respaldo y de crear el esquema vacío en MySQL se ejecutó:

```powershell
php bin/console app:import-sqlite var/torneo.db
```

La copia respeta el orden equipo → jugador → jornada → gol → incidencia, conserva
los identificadores y comprueba cada campo antes de confirmar la transacción.
SQLite se abre en modo de consultas, sin escrituras. Si alguna tabla destino
contiene registros, el comando se niega a sobrescribirlos o mezclarlos.
No lo repitas sobre la base activa: ya se importaron sus datos.

No se copian los metadatos de migraciones SQLite ni colas de mensajes.
Los archivos de fotografías no se mueven: se conservan sus referencias.

## Pruebas

Las pruebas funcionales rápidas siguen usando SQLite en memoria para no tocar los
datos del usuario. `tests/Integration/MySqlTest.php` usa una base MySQL independiente
llamada `cantera_torneo_test`, con otro usuario sin permisos sobre la base principal.
Su URL está en `MYSQL_TEST_DATABASE_URL` en `.env.test.local`; si falta, se omiten
explícitamente esas pruebas.

Las pruebas MySQL verifican sumas, filtros, fechas, acentos y claves foráneas.
Sus cambios de datos se revierten al terminar. En otra máquina aplica primero la
migración a la base de pruebas usando su DATABASE_URL temporal en la terminal.

## Respaldos

Respalda MySQL mediante `mysqldump` y conserva además `var/uploads/jugadores/`.
No copies los archivos internos de InnoDB con el servidor en funcionamiento.
Los respaldos locales están en `var/backups/`; no se incluyen en el ZIP.

```powershell
mysqldump -h 127.0.0.1 -u cantera -p --single-transaction --no-tablespaces --result-file=respaldo.sql cantera_torneo
```

Referencias: [Symfony y Doctrine](https://symfony.com/doc/7.4/doctrine.html),
[MySQL en Windows](https://dev.mysql.com/doc/refman/8.4/en/windows-initialize-data-directory.html).
