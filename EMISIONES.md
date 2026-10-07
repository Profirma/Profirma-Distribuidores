# Emisión de distribuidores con ENEXT

Este cambio pertenece únicamente a `Profirma/Profirma-Distribuidores`. No modifica el repositorio principal, el panel administrador, PayPhone ni `public.solicitudes`.

Se reutiliza el contrato de persona natural que ya tiene `panel/procesar_emision.php`: POST JSON, autenticación HTTP Basic, credenciales de socio dentro del JSON y perfiles 018, 001, 002, 005, 010, 007 y 013. No se inventa un endpoint ni un contrato de persona jurídica. ENEXT recibe `tipo_envio=EMAIL` y `tipo_clave=1`.

## Variables del servicio Distribuidores en Railway

Conservar `DATABASE_URL`. Añadir o referenciar desde el servicio que ya usa ENEXT:

| Variable | Valor |
| --- | --- |
| `ENEXT_API_URL` | Endpoint HTTPS que ya utiliza la integración existente |
| `ENEXT_BASIC_USER` | Usuario HTTP Basic |
| `ENEXT_BASIC_PASSWORD` | Contraseña HTTP Basic |
| `ENEXT_SOCIO_USER` | Usuario de socio |
| `ENEXT_SOCIO_PASSWORD` | Contraseña de socio |
| `DIST_PRICE_018` | Tarifa final USD para 15 días |
| `DIST_PRICE_001` | Tarifa final USD para 1 mes |
| `DIST_PRICE_002` | Tarifa final USD para 1 año |
| `DIST_PRICE_005` | Tarifa final USD para 2 años |
| `DIST_PRICE_010` | Tarifa final USD para 3 años |
| `DIST_PRICE_007` | Tarifa final USD para 4 años |
| `DIST_PRICE_013` | Tarifa final USD para 5 años |

Las tarifas son las cantidades finales que se descontarán, con hasta dos decimales, sin símbolo `$`; por ejemplo, `10.00` es solamente un ejemplo, no una tarifa aprobada. Una vigencia sin tarifa queda oculta. Sin credenciales o sin tarifas, la página muestra emisión en preparación y no llama a ENEXT.

`composer.json` incluye cURL. `railway.toml` ejecuta `php scripts/install_emissions.php` como predeploy. El comando crea idempotentemente una única tabla nueva `pf_distribuidores.emissions` e índices; requiere que las recargas ya estén habilitadas. No cambia los usuarios ni la estructura o datos de las recargas. Si el servicio usa una ruta de configuración Railway personalizada, configurar allí el mismo comando de predeploy. No poner claves en GitHub ni en el navegador.

## Saldo y estados

El saldo disponible mostrado al distribuidor es la suma del registro existente de abonos menos el costo de solicitudes enviadas, registradas o en revisión. Se conserva intacto el registro de abonos que utiliza el administrador. El nuevo historial del distribuidor reúne abonos y consumos/reservas.

Se reserva antes de la petición mediante una transacción con bloqueo de la cuenta, compatible con el bloqueo que ya utiliza la aprobación de recargas. La transacción termina antes de contactar al proveedor. Se guarda el costo original de cada solicitud; los cambios posteriores de tarifas no cambian trámites anteriores.

| Estado | Saldo | Significado |
| --- | --- | --- |
| `enviando` | Reservado | Solicitud registrada localmente; envío en curso o proceso interrumpido |
| `registrada` | Descontado | ENEXT confirmó la aceptación con HTTP 2xx y código 1; el titular debe completar la biometría |
| `rechazada` | Liberado | Rechazo explícito del proveedor o ausencia confirmada por un operador |
| `revision` | Reservado | Error de red, respuesta incompleta/no JSON o resultado incierto |

Registrar la solicitud no demuestra que ENEXT haya emitido el certificado final. No se inventa un webhook ni una consulta de estado no documentada. No se expone ni guarda el token/enlace biométrico; ENEXT debe enviarlo al titular por correo conforme al contrato existente.

La clave de solicitud es única por usuario. Repetirla nunca vuelve a llamar al proveedor. Dos solicitudes recientes para la misma cédula y vigencia en una cuenta también se bloquean durante 24 horas, para evitar duplicados accidentales. El servidor valida la tarifa y una confirmación del costo mostrado. Hay un límite de 30 solicitudes por hora y cuenta.

## Resolver una respuesta incierta

Contactar con ENEXT usando el número `DIST-...` y confirmar si registró la solicitud antes de actuar. No reenviar a ciegas. Un operador con acceso a la consola del servicio Distribuidores puede ejecutar:

```sh
php scripts/resolver_emision.php DIST-... registrada 'ENEXT confirmó el registro; referencia de verificación'
php scripts/resolver_emision.php DIST-... rechazada 'ENEXT confirmó que no existe ni habrá un registro para este trámite'
```

El comando solo acepta trámites en proceso o revisión. No envía nada a ENEXT. La resolución queda anotada y liberar el saldo ocurre bajo el mismo bloqueo de cuenta. Estos scripts solo funcionan por CLI, nunca por URL.

## Verificación

GitHub Actions levanta PostgreSQL y un servidor ENEXT simulado con HTTPS y certificado de prueba. Comprueba Basic Auth y payload, reservas, liberaciones, reintentos, concurrencia, saldo insuficiente, tarifas manipuladas, fallo de base de datos después de una aceptación, sesión/CSRF, separación de historial y preservación de abonos, usuarios y datos existentes. No usa credenciales reales ni solicita firmas reales.

Antes de abrir el servicio a distribuidores, verificar con las credenciales del servicio su endpoint y tarifas finales y realizar una prueba controlada autorizada con ENEXT. La documentación de API disponible en el repositorio no incluye consulta final de certificado, cancelación ni condiciones adicionales de facturación del proveedor.
