# Backend: lógica del sistema

El código PHP está en `src/`:

| Carpeta | Responsabilidad |
| --- | --- |
| `Controller/` | Recibe solicitudes, verifica permisos y coordina respuestas. |
| `Entity/` | Define tablas, relaciones y reglas de datos. |
| `Form/` | Define formularios y transforma/valida sus campos. |
| `Repository/` | Acceso a consultas de entidades con Doctrine. |
| `Service/` | Consultas de reportes y gestión segura de fotografías. |
| `EventSubscriber/` | Cabeceras HTTP y registro de operaciones. |
| `Command/` | Administración por consola: cuentas, demo, importación y auditoría. |

`src/Kernel.php` inicia Symfony. El espacio de nombres sigue siendo `App\`;
Composer lo conecta con `backend/src/` desde `composer.json`.

La configuración está en `config/` de la raíz, las migraciones en `migrations/mysql/`
y la entrada web en `public/index.php`. Ejecuta los comandos desde la raíz del
proyecto, no desde esta carpeta.
