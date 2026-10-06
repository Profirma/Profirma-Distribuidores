# PRO-FIRMA — Distribuidores

Portal PHP conectado a PostgreSQL y a la sección Distribuidores del administrador existente en Profirma/Profirma/panel.

## Railway

1. En **Pro-firma-Distribuidores → Variables**, añadir `DATABASE_URL=${{Postgres.DATABASE_URL}}`. Debe ser el Postgres que usa el administrador.
2. Incorporar esta propuesta en main y desplegar. composer.json declara las extensiones PDO y PostgreSQL.
3. Incorporar también la propuesta del administrador en Profirma/Profirma y desplegar su servicio.
4. Entrar en el administrador existente → Distribuidores → Inicializar distribuidores → Crear cuenta.
5. Entrar en https://distribuidores.pro-firmaec.com/ con el correo y contraseña de esa cuenta.

No añadir ADMIN_USER ni ADMIN_PASSWORD a este servicio: los administradores conservan su acceso existente en el servicio Pro-firma-Admin. Esta aplicación solo autentica distribuidores.

## Datos y seguridad

Las cuentas están en `pf_distribuidores.users`. No se utilizan las cuentas de clientes normales ni las tablas de solicitudes o pagos.
Contraseñas con hash, consultas preparadas, CSRF en login/logout, regeneración del ID de sesión, cookies Secure/HttpOnly/SameSite=Lax, comprobación de cuenta activa en cada petición privada.
La sesión caduca tras 30 minutos de inactividad u 8 horas desde el acceso.
Cinco intentos fallidos por correo bloquean ese correo durante una ventana de 15 minutos. Falta un límite global/IP para ataques contra muchos correos diferentes.

Una sola réplica mientras las sesiones PHP permanezcan en el contenedor. Los despliegues pueden cerrar sesiones. Mantener HTTPS; SESSION_SECURE=0 se permite solo en pruebas HTTP locales.

El panel de distribuidor muestra únicamente su nombre y correo. Compras, saldo, movimientos y recuperación/cambio de contraseña quedan pendientes. El módulo administrativo permite crear cuentas y consultar las últimas 100.
DATABASE_URL solo se configura en Railway; nunca subir credenciales al repositorio.

## Validación

La propuesta del administrador incluye pruebas automáticas de integración contra PostgreSQL para ambos repositorios. Requiere que la rama coordinada del portal siga disponible.
