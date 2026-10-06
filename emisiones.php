<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$user = require_user('distribuidor');
$error = $configurationError = '';
$profiles = dist_profiles();
$prices = [];
$ready = false;
$history = [];
$balance = 0;
$key = bin2hex(random_bytes(32));
$fields = array_fill_keys(['perfil_firma','nombres','apellidos','cedula','codigo_dactilar','correo',
    'provincia','ciudad','parroquia','direccion','celular'], '');
try {
    $ready = dist_emissions_ready(dist_db());
    $balance = dist_wallet_balance(dist_db(), (int)$user['id']);
    if ($ready) {
        try {
            $prices = dist_emission_prices();
            dist_enext_config();
            if (!$prices) {
                $configurationError = 'Las tarifas de distribuidor todavía no están configuradas.';
            }
        } catch (Throwable $exception) {
            $configurationError = 'La emisión todavía no está habilitada. Contacta con PRO-FIRMA.';
        }
    }
} catch (Throwable $exception) {
    http_response_code(503);
    exit('No se pudo cargar tu cuenta. Inténtalo más tarde.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    foreach ($fields as $field => $unused) {
        $fields[$field] = is_string($_POST[$field] ?? null) ? $_POST[$field] : '';
    }
    if (is_string($_POST['request_key'] ?? null) && preg_match('/^[a-f0-9]{64}$/D', $_POST['request_key'])) {
        $key = $_POST['request_key'];
    }
    try {
        if (!$ready) {
            throw new InvalidArgumentException('La emisión todavía no está habilitada.');
        }
        $result = dist_submit_emission(dist_db(), (int)$user['id'], $_POST);
        redirect('emisiones.php?tramite=' . rawurlencode($result['numero_tramite']));
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        $error = 'No se pudo completar la solicitud. Consulta el historial antes de volver a enviarla.';
    }
}
if ($ready) {
    try {
        $find = dist_db()->prepare('SELECT numero_tramite, titular, correo, perfil_firma, cost_cents, status,
            created_at FROM pf_distribuidores.emissions WHERE user_id = ? ORDER BY id DESC LIMIT 50');
        $find->execute([$user['id']]);
        $history = $find->fetchAll();
        $balance = dist_wallet_balance(dist_db(), (int)$user['id']);
    } catch (Throwable $exception) {
        http_response_code(503);
        exit('No se pudo cargar el historial. Inténtalo más tarde.');
    }
}
$selected = $fields['perfil_firma'];
if (!isset($prices[$selected])) {
    $selected = (string)(array_key_first($prices) ?? '');
}
$labels = ['nombres'=>'Nombres del titular', 'apellidos'=>'Apellidos del titular',
    'cedula'=>'Cédula', 'codigo_dactilar'=>'Código dactilar', 'correo'=>'Correo del titular',
    'celular'=>'Celular del titular', 'provincia'=>'Provincia', 'ciudad'=>'Ciudad',
    'parroquia'=>'Parroquia', 'direccion'=>'Dirección'];
$requested = is_string($_GET['tramite'] ?? null) ? $_GET['tramite'] : '';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Emitir firma | PRO-FIRMA</title><link rel="stylesheet" href="assets/panel.css"></head>
<body><header><a class="brand" href="dashboard.php"><img src="logo.jpeg" alt="PRO-FIRMA"> Portal de distribuidores</a>
<nav class="links"><a href="dashboard.php">Mi cuenta</a><a href="recargas.php">Recargas y saldo</a>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button>Cerrar sesión</button></form></nav></header>
<main><h1>Emitir firma electrónica</h1><div class="stat"><strong><?= escape(dist_money($balance)) ?></strong> saldo disponible</div>
<?php if ($error): ?><p class="notice error" role="alert"><?= escape($error) ?></p><?php endif; ?>
<?php foreach ($history as $item): if ($requested === $item['numero_tramite']): ?>
<p class="notice" role="status">Trámite <?= escape($item['numero_tramite']) ?>: <?= escape(dist_emission_status($item['status'])) ?>.</p>
<?php endif; endforeach; ?>
<?php if (!$ready || $configurationError !== ''): ?><section><h2>Emisión en preparación</h2><p><?= escape($configurationError ?: 'El servicio de emisión se habilitará cuando termine su configuración.') ?></p></section>
<?php else: ?><section><h2>Nueva solicitud · Persona natural</h2>
<p>Completa los datos de tu cliente. ENEXT enviará al correo del titular las instrucciones de biometría; el certificado se emite después de completar las verificaciones del proveedor.</p>
<form method="post" id="emission-form">
<input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
<input type="hidden" name="request_key" value="<?= escape($key) ?>">
<input type="hidden" name="quoted_cost" id="quoted-cost" value="<?= (int)($prices[$selected] ?? 0) ?>">
<label>Vigencia<select name="perfil_firma" id="profile" required>
<?php foreach ($prices as $profile => $price): ?><option value="<?= escape((string)$profile) ?>" data-cost="<?= (int)$price ?>" <?= (string)$profile === $selected ? 'selected' : '' ?>><?= escape($profiles[$profile]) ?> · <?= escape(dist_money($price)) ?></option><?php endforeach; ?>
</select></label>
<?php foreach ($labels as $field => $label): ?><label><?= escape($label) ?><input name="<?= escape($field) ?>"
value="<?= escape($fields[$field]) ?>" maxlength="<?= in_array($field, ['nombres','apellidos'], true) ? 120 : 250 ?>"
<?= $field === 'correo' ? 'type="email"' : ($field === 'celular' ? 'type="tel"' : 'type="text"') ?>
<?= $field === 'cedula' ? 'inputmode="numeric" pattern="[0-9]{10}"' : '' ?> required></label><?php endforeach; ?>
<p>Costo de esta solicitud: <strong id="price"><?= escape(dist_money($prices[$selected] ?? 0)) ?></strong>.</p>
<p>El costo se reserva al enviar. Si ENEXT rechaza expresamente la solicitud, se libera el saldo. Si la respuesta queda en revisión, consulta con PRO-FIRMA antes de intentar otra vez.</p>
<label class="check"><input type="checkbox" name="confirmed" value="1" required> Confirmo los datos del titular, su autorización para tramitar la firma y el descuento del costo indicado de mi saldo.</label>
<button class="primary" id="submit-emission">Enviar solicitud a ENEXT</button>
<noscript><p>Activa JavaScript para actualizar el costo cuando cambies la vigencia.</p></noscript>
</form></section><?php endif; ?>
<section><h2>Mis últimos 50 trámites</h2><?php if (!$history): ?><p>Todavía no has enviado solicitudes de firma.</p><?php endif; ?>
<div class="table"><table><thead><tr><th>Trámite</th><th>Titular</th><th>Vigencia</th><th>Costo</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
<?php foreach ($history as $item): ?><tr><td><?= escape($item['numero_tramite']) ?></td><td><?= escape($item['titular']) ?><br><?= escape($item['correo']) ?></td>
<td><?= escape($profiles[$item['perfil_firma']]) ?></td><td><?= escape(dist_money($item['cost_cents'])) ?></td>
<td><?= escape(dist_emission_status($item['status'])) ?></td><td><?= escape($item['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section></main>
<script>
const profile = document.getElementById('profile');
if (profile) {
    profile.addEventListener('change', () => {
        const cost = Number(profile.selectedOptions[0].dataset.cost);
        document.getElementById('quoted-cost').value = String(cost);
        document.getElementById('price').textContent = '$' + (cost / 100).toFixed(2);
    });
}
const form = document.getElementById('emission-form');
if (form) {
    form.addEventListener('submit', () => {
        const button = document.getElementById('submit-emission');
        button.disabled = true;
        button.textContent = 'Enviando solicitud…';
    });
}
</script></body></html>
