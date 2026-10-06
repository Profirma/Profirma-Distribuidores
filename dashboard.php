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
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi cuenta | PRO-FIRMA</title><link rel="stylesheet" href="assets/panel.css"></head>
<body><header><a class="brand" href="dashboard.php"><img src="logo.jpeg" alt="PRO-FIRMA"> Portal de Aliados</a>
<nav class="links"><a href="emisiones.php">Emitir firma</a><a href="recargas.php">Recargas y saldo</a><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button>Cerrar sesión</button></form></nav></header>
<main><h1>Bienvenido, <?= escape($user['name']) ?></h1>
<?php if ($walletReady): ?><div class="stat"><strong><?= escape(dist_money($balance)) ?></strong> saldo disponible <p><a href="recargas.php">Recargar por transferencia y consultar movimientos</a></p></div>
<?php else: ?><section><h2>Recargas en preparación</h2><p>El administrador todavía debe habilitar las recargas por transferencia.</p></section><?php endif; ?>
<section><h2>Tu cuenta está activa</h2><p><?= escape($user['email']) ?></p><p>Inicia una solicitud de firma electrónica para tu cliente. El costo de la vigencia elegida se descuenta de tu saldo.</p><p><a href="emisiones.php">Emitir firma y consultar trámites</a></p></section></main></body></html>
