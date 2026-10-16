# Verificación de la entrega

Fecha de revisión: 13 de octubre de 2026 (America/Guatemala).

- PHPUnit: 14 pruebas, 180 aserciones, todas correctas.
- Doctrine: mapeo correcto y esquema sincronizado.
- Contenedor Symfony y plantillas Twig: comprobados.
- Chrome real, escritorio 1440 px y móvil 390 px: sin errores de JavaScript.
- Menú móvil, búsqueda, vista previa, carga de foto y formularios reales: comprobados.
- Reporte del árbitro: suspensión visible en la fecha correspondiente y PDF generado.
- Manual: PDF válido de 10 páginas, con fuente HTML editable.
- SQLite: integrity_check correcto en base local y base Demo de entrega.
- Comparación con respaldo: el equipo y jugador originales conservan todos sus campos.
- Datos Demo: comando ejecutado dos veces; la segunda ejecución no duplicó registros.
- Los registros temporales de las pruebas de navegador fueron eliminados mediante sus formularios.

La publicación en hosting y la verificación del entorno de producción quedan para
la fase final. Los comentarios personales del grupo y sus nombres deben completarse
con información auténtica de los integrantes.

## Revisión visual del 14 de octubre de 2026

Se incorporó una identidad verde oscuro y lima, tipografía más legible, tarjetas
con colores, accesos rápidos y una tabla de goleadores adaptada a móvil.
Se corrigió el orden de carga del CSS para que AssetMapper no sobrescriba el tema.
PHPUnit volvió a pasar: 14 pruebas y 180 aserciones.
La matriz completa está en `docs/requisitos.md`.

## Migración a MySQL del 14 de octubre de 2026

- Servidor real: MySQL Community Server 8.4.9, puerto 3306.
- Base activa: cantera_torneo, conexión privada en .env.local.
- Copia verificada campo por campo: 5 equipos, 13 jugadores, 4 jornadas,
  12 registros de goles y 4 incidencias. SQLite se conserva sin cambios.
- Esquema MySQL validado y migración inicial comprobada desde cero en la base de pruebas.
- Suite ampliada: 17 pruebas y 195 aserciones correctas, incluyendo MySQL real.
- Se comprobó el rechazo de una segunda importación sobre datos existentes.
- Se generó un respaldo SQL local y datos Demo SQL separados para la entrega.
- Los ZIP anteriores se conservan como historial; el paquete vigente es el de MySQL.

## Acceso de administradores (15/10/2026)

20 pruebas, 285 comprobaciones correctas. Consultas públicas, escritura protegida, login y logout con CSRF. Verificados en navegador real con MySQL y vista móvil. Esquema sincronizado; datos del torneo conservados.
