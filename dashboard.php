<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$user = require_user('distribuidor');
try {
    $walletReady = dist_wallet_ready(dist_db());
    $balance = $walletReady ? dist_wallet_balance(dist_db(), (int)$user['id']) : null;
} catch (Throwable $exception) {
    http_response_code(503);
    exit('No se pudo cargar tu cuenta. Inténtalo más tarde.');
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi cuenta | PRO-FIRMA</title><link rel="stylesheet" href="assets/panel.css?v=20261008-cuenta"></head>
<body class="portal-page account-page"><header><a class="brand" href="dashboard.php"><img src="logo.jpeg" alt="PRO-FIRMA"> Portal de Aliados</a><nav class="links"><a href="dashboard.php">Mi cuenta</a><a href="emisiones.php">Emitir firma</a><a href="recargas.php">Recargas y saldo</a><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button>Cerrar sesión</button></form></nav></header><main>
<div class="portal-heading"><div><p class="portal-eyebrow">TU PORTAL DE ALIADOS</p><h1>Bienvenido, <?= escape($user['name']) ?></h1><p>Gestiona tu saldo y las solicitudes de firma de tus clientes.</p></div><span class="account-active">Cuenta activa</span></div>
<div class="account-grid">
<section class="portal-balance"><span>Saldo disponible</span><?php if ($walletReady): ?><strong><?= escape(dist_money($balance)) ?></strong><p>Disponible para solicitar firmas electrónicas.</p><a class="portal-button light" href="recargas.php">Recargar saldo →</a><?php else: ?><h2>Recargas en preparación</h2><p>El administrador todavía debe habilitar las recargas por transferencia.</p><?php endif; ?></section>
<section class="portal-card profile-card"><p class="portal-eyebrow">MI CUENTA</p><h2>Datos del aliado</h2><dl><dt>Nombre</dt><dd><?= escape($user['name']) ?></dd><dt>Correo electrónico</dt><dd><?= escape($user['email']) ?></dd><dt>Tipo de cuenta</dt><dd>Aliado PRO-FIRMA</dd></dl></section>
</div>
<div class="account-actions"><a class="portal-card action-card" href="emisiones.php"><span class="action-icon">01</span><h2>Emitir firma</h2><p>Inicia una solicitud para tu cliente y consulta sus trámites. El costo se descuenta de tu saldo.</p><span class="action-link">Nueva solicitud →</span></a><a class="portal-card action-card" href="recargas.php"><span class="action-icon">02</span><h2>Recargas y saldo</h2><p>Envía tu comprobante de transferencia y consulta el estado de tus recargas y movimientos.</p><span class="action-link">Gestionar saldo →</span></a></div>
<section class="portal-card account-guide"><h2>Todo listo para trabajar</h2><p>Recarga por transferencia, espera la acreditación de tu pago y solicita firmas con el saldo disponible. El enlace del trámite se envía al cliente; no se muestra en el portal.</p></section>
</main></body></html>
