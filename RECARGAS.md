# Recargas por transferencia y saldo de distribuidores

## Activación

1. Incorporar y desplegar las propuestas coordinadas de Profirma/Profirma y Profirma/Profirma-Distribuidores.
2. Conservar la conexión DATABASE_URL al mismo Postgres existente en ambos servicios.
3. Entrar al administrador actual → **Recargas de distribuidores** → **Activar recargas**. Solo crea tablas e índices nuevos bajo pf_distribuidores. No modifica ni elimina cuentas, solicitudes, ventas o pagos actuales.
4. El distribuidor abre **Recargas y saldo**, indica monto en USD, banco de origen, referencia y fecha, y sube un comprobante JPG, PNG o PDF de hasta 5 MB.
5. El administrador descarga el comprobante y verifica el ingreso real en el banco. Para aprobar, debe marcar la confirmación y pulsar **Aprobar y acreditar**. Si el pago no consta o el monto no coincide, lo rechaza con un motivo.
6. El distribuidor ve el resultado, saldo disponible, historial de recargas y movimientos.

Opcional: configurar BANK_TRANSFER_INSTRUCTIONS en las variables de **Pro-firma-Distribuidores** con banco, cuenta y titular que deben usar. No se inventa ningún dato bancario; si falta, el portal indica que el distribuidor solicite los datos a PRO-FIRMA.

## Almacenamiento y permisos

Los comprobantes se guardan como BYTEA en PostgreSQL, asociados al usuario; se conservan en los despliegues sin configurar volúmenes nuevos. Esta opción ocupa espacio en la base y sus copias de seguridad; para grandes volúmenes se puede migrar a almacenamiento privado de objetos.
La descarga autenticada comprueba la propiedad del distribuidor; el administrador usa su sesión existente. No hay rutas públicas de archivos.
Los archivos se entregan como descarga, con nombre generado, nosniff y CSP sandbox. Se valida el tipo real con Fileinfo; JPG/PNG también se validan con getimagesize. Se rechazan ejecutables y SVG. No hay análisis antivirus en esta primera etapa.
.user.ini en el portal configura 5 MB por archivo y 6 MB por solicitud para PHP-FPM; en una configuración con límites personalizados verificar que se apliquen. Composer exige ext-fileinfo.
Hasta 5 solicitudes pendientes y 20 envíos en 24 horas por distribuidor. Imágenes limitadas a 20 megapíxeles.

## Acreditación

Montos entre USD 0.01 y 100000.00, convertidos a centavos enteros sin floats. Una recarga pendiente o rechazada no aporta saldo.
La aprobación bloquea la solicitud, inserta un movimiento único y actualiza el estado dentro de una sola transacción. Un segundo clic, una repetición o dos administradores simultáneos no vuelven a acreditar.
Se registra quién revisó, cuándo y el motivo si rechaza. No se permite editar o borrar créditos desde la interfaz.
El saldo se obtiene de los movimientos aprobados; no se escribe un saldo arbitrario. Esta etapa solo añade créditos de recarga.
Se bloquean comprobantes idénticos y transferencias con el mismo banco normalizado, referencia normalizada y fecha mientras estén pendientes o aprobadas. El administrador sigue siendo responsable de detectar recibos alterados, referencias equivalentes o bancos escritos de otra forma.
Una recarga rechazada puede volver a enviarse con datos corregidos. Cada nuevo envío requiere aprobación.
Las pruebas usan una base desechable y comprueban actualización del esquema, usuarios existentes, subidas reales, privacidad, CSRF, importes exactos, duplicados, concurrencia y rollback.

La emisión de firmas, la reserva y el descuento de saldo, los precios y los reembolsos por emisión fallida se implementarán en otra etapa, cuando estén definidos los productos y tarifas.
Mantener una réplica mientras las sesiones sean locales. No cambiar ADMIN_USER/ADMIN_PASSWORD del administrador.
