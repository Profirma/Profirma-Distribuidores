<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$user = require_user('distribuidor');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi cuenta | PRO-FIRMA</title><link rel="stylesheet" href="assets/panel.css"></head>
<body><header><a class="brand" href="dashboard.php"><img src="logo.jpeg" alt="PRO-FIRMA"> Portal de distribuidores</a>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button>Cerrar sesión</button></form></header>
<main><h1>Bienvenido, <?= escape($user['name']) ?></h1><section><h2>Tu cuenta está activa</h2><p><?= escape($user['email']) ?></p><p>Ya puedes acceder a tu cuenta privada. Las compras, saldos y movimientos se habilitarán en las siguientes etapas.</p></section></main></body></html>
