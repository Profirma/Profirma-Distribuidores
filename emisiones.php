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
                $configurationError = 'Las tarifas de aliado todavía no están configuradas.';
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
<body class="emission-page"><header><a class="brand" href="dashboard.php"><img src="logo.jpeg" alt="PRO-FIRMA"> Portal de Aliados</a>
<nav class="links"><a href="dashboard.php">Mi cuenta</a><a href="recargas.php">Recargas y saldo</a>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button>Cerrar sesión</button></form></nav></header>
<main><div class="emission-heading"><div><p class="eyebrow">PORTAL DE ALIADOS</p><h1>Emite una nueva firma</h1><p class="heading-description">Completa los datos del titular y elige la vigencia de su firma electrónica.</p></div><div class="balance-chip"><span>Tu saldo disponible</span><strong><?= escape(dist_money($balance)) ?></strong></div></div>
<?php if ($error): ?><p class="notice error" role="alert"><?= escape($error) ?></p><?php endif; ?>
<?php foreach ($history as $item): if ($requested === $item['numero_tramite']): ?>
<p class="notice" role="status">Trámite <?= escape($item['numero_tramite']) ?>: <?= escape(dist_emission_status($item['status'])) ?>.</p>
<?php endif; endforeach; ?>
<?php if (!$ready || $configurationError !== ''): ?><section><h2>Emisión en preparación</h2><p><?= escape($configurationError ?: 'El servicio de emisión se habilitará cuando termine su configuración.') ?></p></section>
<?php else: ?>
<form method="post" id="emission-form" class="emission-layout" autocomplete="off">
<input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
<input type="hidden" name="request_key" value="<?= escape($key) ?>">
<input type="hidden" name="quoted_cost" id="quoted-cost" value="<?= (int)($prices[$selected] ?? 0) ?>">
<div class="emission-fields">
<?php $groups = [
    ['title'=>'Datos del titular', 'description'=>'Escribe los datos tal como aparecen en su documento.', 'fields'=>['nombres','apellidos','cedula','codigo_dactilar']],
    ['title'=>'Información de contacto', 'description'=>'ENEXT enviará las instrucciones al correo del titular. Revisa que esté bien escrito.', 'fields'=>['correo','celular']],
    ['title'=>'Dirección del titular', 'description'=>'Indica dónde reside la persona que solicita la firma.', 'fields'=>['provincia','ciudad','parroquia','direccion']],
]; foreach ($groups as $number => $group): ?>
<fieldset class="form-card"><legend><span class="step-number"><?= $number + 1 ?></span><?= escape($group['title']) ?></legend>
<p class="form-description"><?= escape($group['description']) ?></p><div class="field-grid">
<?php foreach ($group['fields'] as $field): ?><div class="form-field <?= $field === 'direccion' ? 'field-full' : '' ?>">
<label for="field-<?= escape($field) ?>"><?= escape($labels[$field]) ?></label>
<input id="field-<?= escape($field) ?>" name="<?= escape($field) ?>"
value="<?= escape($fields[$field]) ?>" maxlength="<?= $field === 'cedula' ? 10 : (in_array($field, ['nombres','apellidos'], true) ? 120 : 250) ?>"
<?= $field === 'correo' ? 'type="email" placeholder="nombre@correo.com"' : ($field === 'celular' ? 'type="tel" placeholder="0991234567"' : 'type="text"') ?>
<?= $field === 'cedula' ? 'inputmode="numeric" pattern="[0-9]{10}" placeholder="10 dígitos"' : '' ?>
<?= $field === 'correo' ? 'aria-describedby="email-help"' : '' ?> required>
<?php if ($field === 'correo'): ?><small id="email-help">El enlace llegará por correo; no se mostrará en este portal.</small><?php endif; ?>
</div><?php endforeach; ?></div></fieldset>
<?php endforeach; ?>
</div>
<aside class="emission-summary">
<div class="summary-label">TU SOLICITUD</div><h2>Configura tu firma</h2><span class="person-tag">Persona natural</span>
<label for="profile">Vigencia de la firma</label><select name="perfil_firma" id="profile" required>
<?php foreach ($prices as $profile => $price): ?><option value="<?= escape((string)$profile) ?>" data-cost="<?= (int)$price ?>" <?= (string)$profile === $selected ? 'selected' : '' ?>><?= escape($profiles[$profile]) ?> · <?= escape(dist_money($price)) ?></option><?php endforeach; ?>
</select>
<div class="cost-box"><span>Costo de la solicitud</span><strong id="price"><?= escape(dist_money($prices[$selected] ?? 0)) ?></strong><small>Se descontará de tu saldo disponible.</small></div>
<div class="summary-row"><span>Saldo actual</span><strong><?= escape(dist_money($balance)) ?></strong></div>
<div class="summary-row"><span>Saldo después del envío</span><strong id="remaining-balance" data-balance="<?= (int)$balance ?>"><?= escape(dist_money($balance - ($prices[$selected] ?? 0))) ?></strong></div>
<p id="balance-warning" class="balance-warning" role="status" <?= $balance >= ($prices[$selected] ?? 0) ? 'hidden' : '' ?>>Tu saldo no alcanza para esta vigencia. <a href="recargas.php">Recarga tu cuenta</a>.</p>
<label class="check"><input type="checkbox" name="confirmed" value="1" required><span>Confirmo los datos, la autorización del titular y el descuento del costo indicado.</span></label>
<button class="primary" id="submit-emission">Solicitar firma electrónica <span aria-hidden="true">→</span></button>
<p class="summary-help">Envío automático a ENEXT con saldo suficiente, sin aprobación del administrador.</p>
<details class="payment-details"><summary>¿Cómo se maneja el saldo?</summary><p>El costo se reserva al enviar. Si ENEXT rechaza la solicitud, se libera. Si su respuesta queda sin confirmar, consulta con PRO-FIRMA antes de volver a enviarla.</p></details>
<noscript><p>Activa JavaScript para actualizar el costo cuando cambies la vigencia.</p></noscript>
</aside>
</form><?php endif; ?>
<section class="emission-history"><h2>Mis últimos 50 trámites</h2><?php if (!$history): ?><p>Todavía no has enviado solicitudes de firma.</p><?php endif; ?>
<div class="table"><table><thead><tr><th>Trámite</th><th>Titular</th><th>Vigencia</th><th>Costo</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
<?php foreach ($history as $item): ?><tr><td><?= escape($item['numero_tramite']) ?></td><td><?= escape($item['titular']) ?><br><?= escape($item['correo']) ?></td>
<td><?= escape($profiles[$item['perfil_firma']]) ?></td><td><?= escape(dist_money($item['cost_cents'])) ?></td>
<td><span class="status-badge status-<?= escape($item['status']) ?>"><?= escape(dist_emission_status($item['status'])) ?></span></td><td><?= escape($item['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section></main>
<script>
const profile = document.getElementById('profile');
if (profile) {
    profile.addEventListener('change', () => {
        const cost = Number(profile.selectedOptions[0].dataset.cost);
        document.getElementById('quoted-cost').value = String(cost);
        document.getElementById('price').textContent = '
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
 + (cost / 100).toFixed(2);
        const remaining = document.getElementById('remaining-balance');
        const difference = Number(remaining.dataset.balance) - cost;
        remaining.textContent = (difference < 0 ? '-
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
 : '
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
) + (Math.abs(difference) / 100).toFixed(2);
        document.getElementById('balance-warning').hidden = difference >= 0;
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
