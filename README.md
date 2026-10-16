# Cantera · Torneo infantil de fútbol

Configuración vigente: [arranque, cuentas y base de datos en XAMPP](docs/arranque-xampp.md).
El dominio configurado por el propietario es `lacantera.lol`, con Cloudflare y la laptop
como servidor. PHP, MySQL y el túnel deben permanecer activos para que el grupo acceda.

Proyecto universitario con Symfony 7.4, Doctrine ORM, Twig y **MariaDB 10.4 de XAMPP**.

Arranque actual: abre XAMPP y pulsa **Start en Apache y MySQL**. No necesitas `php -S`.
Aplicación: http://127.0.0.1:8000 · Base: http://localhost/phpmyadmin.
Consulta [la guía de XAMPP](docs/arranque-xampp.md).

## Empieza aquí: frontend y backend

```text
ProyectoFinalA/
├── frontend/                 Lo que ve y usa el visitante
│   ├── templates/            Pantallas HTML escritas con Twig
│   └── assets/               CSS, JavaScript, iconos y controladores Stimulus
├── backend/
│   └── src/                  Lógica PHP: rutas, validaciones, consultas y seguridad
├── config/                   Configuración compartida de Symfony
├── migrations/mysql/         Cambios del esquema de MySQL
├── public/                   Entrada web y archivos compilados; no editar el CSS aquí
├── tests/                    Pruebas del sistema
├── docs/                     Manuales, informes y guías
├── entrega/                  Un ZIP vigente para entregar
├── resources/demo/           Datos y avatares ficticios
├── bin/                      Comandos para ejecutar el proyecto
├── vendor/                   Librerías PHP instaladas automáticamente
└── var/                      Datos privados, fotografías, respaldos, caché y logs
```

| Parte | Tecnologías que usamos |
| --- | --- |
| Backend | PHP 8.2+, Symfony 7.4 y Doctrine ORM |
| Frontend | Twig para generar HTML, CSS y JavaScript; Symfony UX Turbo y Stimulus |
| Base de datos activa | MariaDB 10.4.32 de XAMPP, puerto 3306 |
| Dependencias PHP | Composer |
| Archivos del frontend | AssetMapper e Importmap de Symfony |
| Pruebas | PHPUnit; SQLite en memoria y MariaDB de pruebas |
| Acceso público | lacantera.lol mediante Cloudflare Tunnel |

Es **una aplicación Symfony que genera páginas en el servidor**. Las carpetas
frontend y backend separan responsabilidades; ambas se ejecutan con el mismo
servidor PHP. No hay que arrancar un servidor frontend adicional.

**Docker no se usa y su configuración se retiró.** Node.js/Playwright se usó
durante la revisión; sus herramientas auxiliares se apartaron del código activo
y quedaron en `var/backups/herramientas-retiradas` porque se bloqueó su borrado definitivo.
No hace falta Node para abrir la página. SQLite es respaldo histórico y soporte de pruebas,
no la base activa. Lee [la guía de estructura](docs/informe-funcionamiento.pdf) para saber qué editar.

En la raíz solo quedan `.env` (valores base) y `.env.local` (secretos y conexión
de esta computadora). Las pruebas usan `phpunit.dist.xml` y, opcionalmente,
`tests/.env.local` para conectarse a la base MySQL aislada de pruebas.

Para estudiar antes de exponer: [guía de defensa con ejemplos y preguntas](docs/defensa-proyecto.pdf).

## Iniciar esta copia local

La base activa está en MariaDB de XAMPP, `127.0.0.1:3306`, esquema
`cantera_torneo`, usuario `cantera`. La contraseña está en `.env.local` y no se incluye
en Git ni en el ZIP. El MySQL 8.4 anterior quedó apagado y conservado como respaldo.

Abre `iniciar.bat` o el panel de XAMPP y pulsa **Start** en **Apache** y **MySQL**.
Abre http://127.0.0.1:8000. No ejecutes a la vez `php -S`: el puerto lo utiliza Apache.
Las fotografías siguen en `var/uploads/jugadores/`. Cloudflared ya está configurado
como servicio automático de Windows para el dominio `lacantera.lol`.

## Instalar una copia nueva

Requiere PHP 8.2+, Composer 2 y **MySQL 8.4 o MariaDB de XAMPP**. Crea la base `cantera_torneo`
y un usuario con permisos sobre ella. Configura tus credenciales en `.env.local`:

```dotenv
DATABASE_URL="mysql://USUARIO:CLAVE@127.0.0.1:3306/cantera_torneo?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

```powershell
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console importmap:install
php bin/console app:demo
```

En esta laptop, inicia Apache y MySQL desde XAMPP. Para otra instalación, configura
el servidor web con `public/` como raíz y ajusta DATABASE_URL a su motor y versión reales.

Las migraciones activas están en `migrations/mysql/`. Las SQLite originales
permanecen archivadas en la carpeta principal de migraciones, sin ejecutarse.
`app:demo` carga registros ficticios y avatares ilustrativos; no son fotos reales.
No dupliques la carga usando a la vez Demo e importación SQL sobre datos existentes.

El ZIP MySQL incluye `resources/demo/mysql-demo.sql` con solo los datos ficticios
para inspección o importación manual en una base vacía con el esquema creado.
La instalación recomendada usa `app:demo`, que también copia las imágenes.

Consulta [docs/Manual-Cantera.pdf](docs/Manual-Cantera.pdf) para la conexión desde Workbench, el
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

Los jugadores deben tener al menos **6 años cumplidos**, sin edad máxima configurada.
Nombres y apellidos son obligatorios y admiten hasta 100 caracteres: letras,
tildes, ñ, espacios, guiones y apóstrofes; no números ni otros símbolos.
Se rechazan fechas inexistentes y equipos inválidos al crear y editar.
Los registros anteriores no se corrigen automáticamente.


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
php bin/console lint:twig frontend/templates
```

Las pruebas funcionales utilizan SQLite en memoria y carpetas temporales.
Las pruebas de integración usan MariaDB de XAMPP en `cantera_torneo_test`, con otro
usuario. Ninguna escribe datos de prueba en la base principal. La revisión visual se hizo con Chrome en escritorio
y móvil; las capturas anteriores se archivaron en `var/backups/documentacion-fuentes/capturas/`.

## Documentación y entrega

La guía vigente de arranque es [XAMPP](docs/arranque-xampp.md). Los cuatro PDF de la
entrega anterior conservan referencias al antiguo servidor MySQL 8.4:

- [Manual completo](docs/Manual-Cantera.pdf): instalación, uso, MySQL, administración y publicación.
- [Informe de auditoría](docs/informe-auditoria.pdf): hallazgos, correcciones y riesgos pendientes.
- [Funcionamiento y archivos](docs/informe-funcionamiento.pdf): arquitectura y responsabilidades.
- [Defensa del proyecto](docs/defensa-proyecto.pdf): explicación, demostración y preguntas de clase.

Las fuentes y capturas se archivaron en `var/backups/documentacion-fuentes/`.
El ZIP de la entrega anterior está en `entrega/ProyectoFinalA-Cantera-actualizado.zip`;
debe regenerarse para incluir la migración a XAMPP.
El dominio `lacantera.lol` usa Cloudflare y depende de esta laptop como servidor;
no se ha trasladado la aplicación a un servidor externo. Los comentarios personales corresponden al grupo.
## Estructura para aprender

`backend/src/Entity` describe los datos y sus reglas. `backend/src/Form` convierte esas entidades
en formularios. `backend/src/Controller` recibe las solicitudes y coordina las acciones.
`backend/src/Service` concentra la carga de fotografías y las consultas de reportes.
`frontend/templates` presenta los datos con Twig. `frontend/assets` contiene CSS y JavaScript.
`migrations` versiona los cambios de la base de datos. `tests` comprueba comportamientos.

## Acceso público y administración

Los visitantes consultan equipos, nombres de plantilla, calendario y goleadores. Las fotos,
fechas de nacimiento, fichas completas, nóminas e incidencias requieren una cuenta de
árbitro o administrador. Solo los administradores pueden registrar, editar o eliminar.
Consulta [la guía de XAMPP](docs/arranque-xampp.md) para crear
cuentas del grupo. Los PDF anteriores a esta actualización
no incluyen el nuevo rol árbitro.
