# Cantera · Torneo infantil de fútbol

Proyecto universitario con Symfony 7.4, Doctrine ORM, Twig y **MySQL 8.4**.

## Iniciar esta copia local

MySQL ya quedó configurado en `127.0.0.1:3306`, base `cantera_torneo`, usuario
`cantera`. La contraseña está en `.env.local` y no se incluye en Git ni en el ZIP.
Los datos anteriores se copiaron y verificaron campo por campo. `var/torneo.db`
se conserva como respaldo, pero ya no recibe los registros nuevos.

Después de reiniciar Windows, inicia MySQL siguiendo [docs/mysql.md](docs/mysql.md).
Después, desde la carpeta del proyecto:

```powershell
php -S 127.0.0.1:8000 -t public bin/router.php
```

Abre http://127.0.0.1:8000. Mantén la terminal abierta y usa Ctrl+C para detener
el servidor web. Las fotografías siguen en `var/uploads/jugadores/`.

## Instalar una copia nueva

Requiere PHP 8.2+, Composer 2 y **MySQL 8.4**. Crea la base `cantera_torneo`
y un usuario con permisos sobre ella. Configura tus credenciales en `.env.local`:

```dotenv
DATABASE_URL="mysql://USUARIO:CLAVE@127.0.0.1:3306/cantera_torneo?serverVersion=8.4.9&charset=utf8mb4"
```

```powershell
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console importmap:install
php bin/console app:demo
php -S 127.0.0.1:8000 -t public bin/router.php
```

Las migraciones activas están en `migrations/mysql/`. Las SQLite originales
permanecen archivadas en la carpeta principal de migraciones, sin ejecutarse.
`app:demo` carga registros ficticios y avatares ilustrativos; no son fotos reales.
No dupliques la carga usando a la vez Demo e importación SQL sobre datos existentes.

El ZIP MySQL incluye `resources/demo/mysql-demo.sql` con solo los datos ficticios
para inspección o importación manual en una base vacía con el esquema creado.
La instalación recomendada usa `app:demo`, que también copia las imágenes.

Consulta [docs/mysql.md](docs/mysql.md) para la conexión desde Workbench, el
arranque local, pruebas, respaldos e importación desde SQLite.

## Funcionalidades

- Panel de inicio con datos reales del torneo.
- Equipos: registro, edición, consulta de plantilla y eliminación protegida.
- Jugadores: datos personales, fotografías seguras, búsqueda, filtros y ficha.
- Jornadas: catálogo con nombre y fecha de juego.
- Goles: total por jugador/jornada, sin duplicados.
- Incidencias: tarjetas, descripción, fecha y suspensión para tarjetas rojas.
- Nómina del árbitro por equipo y jornada, con fotografías y estado.
- Reporte de incidencias por equipo y jugador.
- Goleadores ordenados por total descendente, filtrables por equipo y jornada.
- Reportes imprimibles y guardables como PDF desde el navegador.
- Interfaz adaptable a escritorio y móvil, en español.

## Reglas del proyecto

Una tarjeta roja exige fecha de suspensión. Una amarilla no admite suspensión.
El jugador se considera suspendido **solo en la fecha indicada**. No se inventan
sanciones por acumulación de amarillas ni períodos de varios partidos.

Los goles son cantidades enteras entre 1 y 100. Existe un único registro para
cada combinación jugador/jornada; edítalo para corregir el total.
Los equipos/jugadores/jornadas con registros dependientes no se eliminan hasta
resolver esas dependencias; no hay borrado en cascada del historial.

Las fotos aceptan JPEG, PNG o WebP, máximo 2 MB y 4096 × 4096. Se guardan en
`var/uploads/jugadores`, fuera de `public/`, con nombres aleatorios. Conserva esa
carpeta junto al respaldo SQL de MySQL. Las fotos antiguas no se borran al
reemplazarlas. Los textos de fotografías del CRUD original se conservan en ficha
y edición, pero no se interpretan como rutas de archivos.

## Comprobar

```powershell
php bin/phpunit
php bin/console doctrine:schema:validate
php bin/console lint:container
php bin/console lint:twig templates
```

Las pruebas funcionales utilizan SQLite en memoria y carpetas temporales.
Las pruebas de integración usan MySQL real en `cantera_torneo_test`, con otro
usuario. Ninguna escribe datos de prueba en la base principal. La revisión visual se hizo con Chrome en escritorio
y móvil; las capturas están en `docs/capturas/`.

## Documentación y entrega

- `docs/mysql.md`: guía de conexión, migración y respaldo de MySQL.
- `docs/Manual-Cantera.pdf`: manual técnico, uso, aprendizaje y publicación.
- `docs/manual.html`: fuente editable del manual.
- `docs/fotografias-jugadores.md`: explicación detallada de la carga de imágenes.
- `docs/ejemplo-nomina-arbitro.pdf`: ejemplo de reporte exportado.
- `docs/guion-demostracion.md`: recorrido para presentar el proyecto.

El hosting queda pendiente por decisión del usuario. El manual explica el
procedimiento para un servidor PHP; no se ha publicado ni inventado una URL.
Los comentarios personales del grupo se deben escribir a partir de su experiencia.

## Estructura para aprender

`src/Entity` describe los datos y sus reglas. `src/Form` convierte esas entidades
en formularios. `src/Controller` recibe las solicitudes y coordina las acciones.
`src/Service` concentra la carga de fotografías y las consultas de reportes.
`templates` presenta los datos con Twig. `assets` contiene CSS y JavaScript.
`migrations` versiona los cambios de la base de datos. `tests` comprueba comportamientos.

## Acceso público y administración

El torneo se puede consultar sin iniciar sesión. Para registrar, editar o eliminar datos se necesita una cuenta administradora. Consulta [la gu?a de acceso](docs/acceso-administradores.md) para crear cuentas del grupo.
