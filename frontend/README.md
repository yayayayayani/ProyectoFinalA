# Frontend: pantallas y presentación

- `templates/`: HTML con Twig. `base.html.twig` define el menú y la estructura común.
- `templates/jugador/`, `equipo/`, `registro/`, `reporte/`: pantallas de cada módulo.
- `assets/styles/`: CSS de la interfaz; `cantera.css` contiene ajustes del tema.
- `assets/app.js`: menú, confirmaciones y vista previa de fotografías.
- `assets/controllers/`: integración de Stimulus; conserva la protección CSRF.
- `assets/vendor/`: dependencias JavaScript instaladas por Importmap; no editar.

Twig se procesa en el servidor y envía HTML al navegador. Las reglas definitivas
de datos y permisos están en `backend/src/`, no en estas pantallas.

Después de modificar estilos o JavaScript en producción, desde la raíz:

```powershell
php bin/console asset-map:compile --env=prod --no-debug
```

Para cambios Twig o de configuración, limpia la caché con
`php bin/console cache:clear --env=prod --no-debug`.
No edites `public/assets/`: son copias compiladas que se regeneran.
