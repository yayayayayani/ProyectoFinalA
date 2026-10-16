# Visitantes y administradores

La página inicial y las consultas del torneo son públicas: no hace falta crear una cuenta para mirar equipos, jugadores, jornadas, goles y reportes.

Para registrar, editar o eliminar datos, pulsa **Acceso administradores** en el menú e inicia sesión. Cada integrante del grupo puede tener una cuenta propia. Al terminar pulsa **Cerrar sesión**.

## Primera cuenta local

Se creó el usuario `admin`. La contraseña está en `var/acceso-admin.txt`, un archivo privado excluido del control de versiones y de las entregas. Esta contraseña es distinta de la del usuario `cantera` de MySQL.

## Crear cuentas del grupo o cambiar una contraseña

Desde la carpeta del proyecto:

```powershell
C:\xampp\php\php.exe bin/console app:crear-admin nombre_del_integrante
```

El comando solicita y confirma una contraseña de al menos 12 caracteres sin mostrarla. Si la cuenta ya existe, pide confirmación antes de cambiar su contraseña. Solo quien tenga acceso a la consola del servidor puede crear cuentas; no hay registro público de administradores. Todas las cuentas administradoras tienen los mismos permisos sobre el torneo.

## Cómo funciona

- La entidad `Administrador` guarda el nombre de usuario y el hash de la contraseña en MySQL. No guarda la contraseña en texto plano.
- Symfony Security autentica el formulario `/login` y mantiene la sesión.
- Las reglas de `security.yaml` y los atributos `IsGranted` de los controladores exigen `ROLE_ADMIN` para crear, editar y eliminar. Ocultar un botón no basta para proteger una aplicación.
- Twig muestra los botones de administración únicamente a quienes tienen ese rol.
- Inicio y cierre de sesión tienen protección CSRF, al igual que los formularios de modificación.
- La nueva migración solo añade la tabla `administrador`; conserva los datos del torneo.

Documentación de referencia: https://symfony.com/doc/7.4/security.html

## Instalación en otro equipo o hosting

Ejecuta las migraciones y crea las cuentas con `app:crear-admin`; las contraseñas locales no se incluyen en el ZIP. Antes de publicar usa HTTPS y `APP_ENV=prod`, `APP_DEBUG=0`. El hosting sigue pendiente.
