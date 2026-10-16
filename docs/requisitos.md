# Revisión del enunciado ProyectoFinalA.pdf

## Funcionalidad implementada

| Requisito | Implementación y comprobación |
| --- | --- |
| Nombres y apellidos separados | Dos campos en entidad y formulario Jugador |
| Fecha de nacimiento | Campo de fecha; se rechazan fechas futuras y vacías |
| Fotografía | Carga JPEG/PNG/WebP, validación del servidor y visualización |
| Catálogo de equipos | CRUD Equipo y selector por nombre |
| Registro de goles | Jugador + jornada + cantidad; registro único por combinación |
| Catálogo de jornadas y fechas | CRUD Jornada, disponible desde el inicio |
| Descripción, tarjeta y fecha de incidencia | CRUD Incidencia |
| Fecha de suspensión por roja | Obligatoria con tarjeta roja; se valida coherencia de fechas |
| Lista de equipos/jugadores para árbitro | Reporte filtrable por equipo y jornada con foto y datos |
| Suspensión en la jornada a jugar | Coincidencia exacta entre fecha de jornada y suspensión |
| Incidencias por equipo, uno o todos los jugadores | Reporte con ambos filtros y validación de pertenencia |
| Goleadores descendentes con total, foto y equipo | SUM de goles por jugador y orden descendente |
| Consulta completa de jugador | Ficha con datos personales, foto, goles e incidencias |

## Entregables y aspectos pendientes

| Entregable | Estado |
| --- | --- |
| Datos precargados | Listos: registros ficticios y avatares ilustrativos |
| Demostración funcional | Aplicación local, pruebas y guion listos; exposición a cargo del grupo |
| Publicación y URL | Pendiente de elegir hosting, según decisión del usuario |
| Manual técnico en PDF | Generado, con instalación, publicación y explicación del framework |
| Recomendaciones del framework | Incluidas en el manual |
| Comentarios personales del grupo | Pendientes de redactar por los integrantes; no se inventaron |
| Código fuente e instrucciones | Incluidos en el proyecto y ZIP |
| ZIP con código y PDF | Generado; se entrega una versión actualizada tras los cambios visuales |
| Subida a la plataforma | A cargo del grupo; no se ha realizado |
| Presentación personal y preguntas | A cargo de los integrantes; hay un guion de preparación |

## Precisiones

- La fotografía es opcional para conservar compatibilidad con los registros anteriores.
  El enunciado la incluye como dato a registrar, pero no especifica obligatoriedad.
  Para la demostración se recomienda que todos los jugadores tengan una imagen.
- Los avatares de Demo no son fotografías reales. La carga de fotografías reales funciona.
- No se define acumulación de amarillas ni suspensión de varios partidos en el PDF.
  Se implementó la fecha de suspensión individual que solicita el enunciado.
- La interfaz nueva no cambia registros ni elimina archivos.
- No corresponde afirmar cumplimiento del 100 % de la entrega hasta completar
  publicación, comentarios personales, envío a plataforma y exposición.

## Requisito adicional del usuario: MySQL

La aplicación se migró a MySQL Community Server 8.4.9 con InnoDB y utf8mb4.
Se conservaron los registros existentes, identificadores y fotografías.
SQLite permanece únicamente como respaldo y para pruebas rápidas aisladas.
