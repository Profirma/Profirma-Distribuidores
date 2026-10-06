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

El panel muestra la cuenta, el saldo acreditado y los historiales de recargas y movimientos. El administrador crea cuentas, revisa comprobantes y aprueba o rechaza recargas. La emisión de firmas y sus descuentos, y la recuperación/cambio de contraseña, siguen pendientes.
DATABASE_URL solo se configura en Railway; nunca subir credenciales al repositorio.

## Validación

La propuesta del administrador incluye pruebas automáticas de integración contra PostgreSQL para ambos repositorios. Requiere que la rama coordinada del portal siga disponible.

## Recargas por transferencia

Ahora se puede enviar un comprobante privado, consultar su revisión y ver el saldo acreditado por el administrador. Activación y pruebas: [RECARGAS.md](RECARGAS.md). La emisión de firmas y los descuentos aún no están habilitados.
