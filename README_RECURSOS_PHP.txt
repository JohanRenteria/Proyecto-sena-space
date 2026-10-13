INTEGRACIÓN DEL MÓDULO DE RECURSOS Y PRÉSTAMOS EN LOG-IN
==========================================================

Se integraron DOS MÓDULOS INDEPENDIENTES al proyecto existente:

1. usuario.php
   - Dashboard
   - Inventario disponible
   - Solicitar objeto o servicio
   - Verificación de disponibilidad por rango de fechas
   - Mis solicitudes
   - Mis préstamos activos
   - Aviso de devolución solicitado por administrador

2. administrador.php
   - Dashboard administrativo
   - Aprobar / rechazar préstamos
   - Ver quién tiene cada objeto o servicio
   - Solicitar devolución
   - Confirmar devolución
   - Inventario
   - Detectar stock bajo/agotado
   - Solicitar reposición de implementos
   - Gestionar roles Usuario / Administrador
   - Historial de movimientos

BACKEND
-------
recursos_api.php reemplaza la API PHP del beta y trabaja directamente con MySQL/MariaDB usando conexion.php.

BASE DE DATOS
-------------
Se añadieron al sena_space:
- recursos
- solicitudes
- reposiciones
- historial_recursos
- rol_sistema en admin

También se cargan los recursos de prueba indicados en la solicitud.
La tabla `password_resets` almacena los tokens temporales del restablecimiento de contraseña.

VERIFICACIÓN DE DISPONIBILIDAD
------------------------------
Se comprueba en servidor:
- recurso disponible
- fechas válidas
- stock total
- solicitudes Pendientes y Aprobadas que se cruzan con el rango
- cantidad solicitada

La disponibilidad se vuelve a verificar al aprobar, evitando que dos solicitudes se aprueben sobre el mismo stock.

ROLES
-----
El campo admin.rol_sistema define:
- Usuario
- Administrador

Para la instalación de demostración, kd@gmail.com queda como Administrador y conserva la contraseña que ya tenía en el proyecto (actualmente 0 en el SQL original).

LOGIN
-----
login.php ahora guarda en sesión:
- usuario_id
- usuario
- correo
- rol_sistema

Después del login:
- Administrador -> administrador.php
- Usuario -> usuario.php

LOGOUT
------
logout.php destruye la sesión.

INSTALACIÓN
-----------
1. Copiar este proyecto a XAMPP/htdocs o al servidor PHP.
2. Crear/importar la base sena_space usando sena_space.sql.
3. Revisar conexion.php si usuario/clave de MySQL son diferentes.
4. Configurar las variables de entorno `MAIL_USERNAME` y `MAIL_PASSWORD` para SMTP. Para Gmail, usa una contraseña de aplicación y activa la verificación en dos pasos; el remitente será esa misma cuenta. `MAIL_HOST` es opcional (por defecto `smtp.gmail.com`).
5. Configurar `APP_BASE_URL` con la URL base del proyecto, por ejemplo `http://localhost/Proyecto-sena-space`, sin barra final. En Windows, define estas variables de entorno y reinicia Apache desde XAMPP.
6. Si la base de datos ya existe, ejecutar `recursos_schema.sql` para crear `password_resets`. En una instalación nueva, importar `sena_space.sql`.
7. Abrir Login.html.
8. Iniciar sesión.

No guardes la contraseña SMTP en archivos del proyecto ni reutilices claves que ya se hayan publicado.

El módulo conserva el lenguaje visual del proyecto LOG-IN (SB Admin 2) y el estilo funcional de los betas originales: navegación lateral, tarjetas KPI, tablas, estados, modales, filtros y verificación.
