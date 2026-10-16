# Equipos y fotografías de jugadores

## 1. Mostrar la relación con Equipo

La relación ya existía: `Jugador::getEquipo()` devuelve un `Equipo` y
`Equipo::getNombre()` devuelve su nombre. Por eso Twig puede escribir
`jugador.equipo.nombre` en la tabla. La nueva columna también requiere cambiar
el `colspan` de la fila vacía de 6 a 7.

En `JugadorType`, `choice_label: nombre` muestra nombres en el selector.
El valor enviado sigue siendo el identificador y Doctrine sigue guardando
la misma relación. No fue necesario cambiar las entidades ni las tablas.

## 2. Separar el archivo del nombre guardado

`fotografia` sigue siendo una columna de texto nullable. El formulario ahora
utiliza `fotografiaArchivo`, de tipo `FileType`, con dos opciones importantes:

- `mapped: false`: Symfony no intenta meter un objeto `UploadedFile` en una
  propiedad de tipo string. El controlador guarda únicamente el nombre resultante.
- `required: false`: permite crear sin foto y editar conservando la existente.

`form_start(form)` agrega automáticamente `enctype="multipart/form-data"`,
necesario para transmitir archivos. La protección CSRF existente sigue activa.

## 3. Validar antes de guardar

La restricción `Image` se ejecuta en el servidor al llamar a `isValid()`:

- Máximo 2 MB (2 000 000 bytes).
- JPEG, PNG o WebP: se comprueban la extensión y el MIME detectado del contenido.
- Dimensiones legibles y máximo 4096 × 4096 píxeles.
- SVG y formatos ejecutables quedan excluidos.

El atributo HTML `accept` ayuda al usuario a seleccionar archivos, pero no es
una protección: la validación del servidor es la que decide.
Esta configuración no recodifica imágenes ni elimina sus metadatos y no hace
una decodificación completa para detectar toda posible corrupción.

## 4. Guardar y mostrar

`FotografiaUploader` recibe el archivo ya validado, vuelve a restringir el MIME
y genera un nombre aleatorio con `random_bytes(16)`. La extensión se elige
según el contenido; el nombre original nunca se usa para construir una ruta.

La carpeta configurada en `config/services.yaml` es
`var/uploads/jugadores`. Se crea al subir el primer archivo. PHP necesita permiso
de escritura allí. Está fuera de `public/`, de modo que el servidor web no
ejecuta directamente lo subido. Incluye esta carpeta en tus copias de seguridad:
son archivos persistentes, aunque estén dentro de `var/`, y Git los ignora.

La ruta `GET /jugador/{id}/fotografia` obtiene el nombre desde la entidad,
acepta solo el patrón generado y devuelve el archivo con `nosniff`.
Un archivo inexistente o un nombre con rutas devuelve 404.
La ruta tiene el mismo acceso que el CRUD actual: almacenar fuera de `public/`
no equivale a implementar autenticación o autorización.

El parcial `_fotografia.html.twig` se reutiliza en lista, detalle y edición.
Los valores de texto anteriores permanecen visibles como texto escapado;
no se interpretan como rutas ni se mueven archivos antiguos automáticamente.

## 5. Qué ocurre al editar y ante errores

Sin un nuevo archivo, no se llama a `setFotografia()`. Con uno válido, primero
se mueve el archivo y después se actualiza la referencia en la entidad.
Un error de escritura aparece en el formulario y evita el guardado en Doctrine.

Para conservar tus archivos, sustituir una foto o eliminar un jugador no borra
fotos del disco. Esto puede dejar archivos sin referencia. Asimismo, si el
archivo se guarda pero falla posteriormente la base de datos, puede quedar
un archivo sin referencia: el sistema de archivos no participa en la transacción
de MySQL. Su limpieza queda para una tarea explícita posterior.

## 6. Probarlo

1. Abre `/jugador/new`, elige un equipo por su nombre y sube un PNG o JPEG.
2. Comprueba el equipo y la miniatura en `/jugador`.
3. Edita el jugador sin seleccionar archivo: la foto debe mantenerse.
4. Prueba un archivo de texto renombrado a `.png` o una imagen de más de 2 MB:
   debe aparecer un error y mantenerse la fotografía anterior.

PHP también limita el tamaño antes de que Symfony procese el formulario:
`upload_max_filesize` debe permitir al menos 2 MB y `post_max_size` debe ser
mayor para incluir el resto del formulario. No se modificó tu `php.ini`.

Ejecuta `php bin/phpunit` para las pruebas automáticas. Utilizan SQLite en memoria
y una carpeta temporal independiente; no modifican tu base de datos ni tus fotos.
La aplicación usa MySQL; las pruebas funcionales rápidas conservan SQLite en memoria.
La migración del proyecto a MySQL se documenta en `docs/mysql.md`.

Referencia: [carga de archivos en Symfony 7.4](https://symfony.com/doc/7.4/controller/upload_file.html).
