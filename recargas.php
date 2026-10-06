<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$user = require_user('distribuidor');
$error = '';
$notice = (string)($_SESSION['recharge_notice'] ?? '');
unset($_SESSION['recharge_notice']);
$banks = ['Banco Pichincha', 'Banco Guayaquil', 'Banco del Pacífico', 'Produbanco', 'Banco Bolivariano', 'Banco Internacional', 'Banco del Austro'];
$fields = ['bank_other' => '', 'amount' => '', 'bank' => '', 'reference' => '', 'date' => (new DateTimeImmutable('now', new DateTimeZone('America/Guayaquil')))->format('Y-m-d')];
try {
    $ready = dist_wallet_ready(dist_db());
} catch (Throwable $exception) {
    http_response_code(503);
    exit('Las recargas no están disponibles temporalmente.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 6291456) {
        http_response_code(413);
        exit('La solicitud supera el tamaño permitido. Usa un comprobante de hasta 5 MB.');
    }
    require_csrf();
    try {
        if (!$ready) {
            throw new InvalidArgumentException('Las recargas todavía no están habilitadas.');
        }
        foreach (array_keys($fields) as $key) {
            $fields[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        }
        $upload = $_FILES['receipt'] ?? [];
        if (!is_array($upload)) {
            throw new InvalidArgumentException('Selecciona un comprobante.');
        }
        $submission = $_POST;
        if ($fields['bank'] === 'otro') {
            $submission['bank'] = trim($fields['bank_other']);
        }
        $id = dist_submit_recharge(dist_db(), (int)$user['id'], $submission, $upload);
        $_SESSION['recharge_notice'] = 'Recarga #' . $id . ' enviada. El saldo se acreditará cuando el administrador verifique y apruebe tu transferencia.';
        redirect('recargas.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        $error = 'No se pudo registrar la recarga. Consulta tu historial antes de enviarla otra vez.';
    }
}
$balance = 0;
$history = $movements = [];
if ($ready) {
    try {
        $balance = dist_wallet_balance(dist_db(), (int)$user['id']);
        $statement = dist_db()->prepare("SELECT id, amount_cents, bank, transfer_reference, transfer_date,
            status, created_at, rejection_reason FROM pf_distribuidores.recharges WHERE user_id = ? ORDER BY id DESC LIMIT 50");
        $statement->execute([$user['id']]);
        $history = $statement->fetchAll();
        $movements = dist_wallet_history(dist_db(), (int)$user['id']);
    } catch (Throwable $exception) {
        http_response_code(503);
        exit('No se pudo cargar tu saldo. Inténtalo más tarde.');
    }
}
$instructions = trim((string)getenv('BANK_TRANSFER_INSTRUCTIONS'));
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recargar saldo | PRO-FIRMA</title><link rel="stylesheet" href="assets/panel.css"></head>
<body><header><a class="brand" href="dashboard.php"><img src="logo.jpeg" alt="PRO-FIRMA"> Portal de Aliados</a><nav class="links"><a href="dashboard.php">Mi cuenta</a><a href="emisiones.php">Emitir firma</a>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button>Cerrar sesión</button></form></nav></header>
<main><h1>Recargar saldo</h1>
<?php if ($error): ?><p class="notice error" role="alert"><?= escape($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p class="notice" role="status"><?= escape($notice) ?></p><?php endif; ?>
<?php if (!$ready): ?><section><h2>Recargas en preparación</h2><p>El administrador todavía debe habilitar las recargas por transferencia.</p></section>
<?php else: ?><div class="stat"><strong><?= escape(dist_money($balance)) ?></strong> saldo disponible</div>
<section><h2>Enviar comprobante de transferencia</h2>
<p class="bank-details"><strong>Banco de destino: Banco Pichincha</strong><br>Realiza la transferencia a la cuenta de PRO-FIRMA.</p>
<?php if ($instructions): ?><p class="bank-details"><?= nl2br(escape($instructions)) ?></p>
<?php else: ?><p>Solicita a PRO-FIRMA el número de cuenta y los datos del titular antes de transferir.</p><?php endif; ?>
<p>El comprobante quedará pendiente de revisión. Enviarlo no acredita saldo automáticamente.</p>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(32)) ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="5242880">
<label>Monto transferido (USD)<input name="amount" inputmode="decimal" placeholder="100.00" maxlength="12" value="<?= escape($fields['amount']) ?>" required></label>
<label>Banco desde el que transferiste<select name="bank" id="bank" required>
<option value="">Selecciona tu banco</option>
<?php foreach ($banks as $bank): ?><option value="<?= escape($bank) ?>" <?= $fields['bank'] === $bank ? 'selected' : '' ?>><?= escape($bank) ?></option><?php endforeach; ?>
<option value="otro" <?= $fields['bank'] === 'otro' ? 'selected' : '' ?>>Otro banco o cooperativa</option>
</select></label>
<label id="bank-other-label">Si elegiste otro, indica el banco o cooperativa<input name="bank_other" id="bank-other" maxlength="100" value="<?= escape($fields['bank_other']) ?>"></label>
<label>Número o referencia de transferencia<input name="reference" maxlength="80" value="<?= escape($fields['reference']) ?>" required></label>
<label>Fecha de transferencia<input name="date" type="date" value="<?= escape($fields['date']) ?>" max="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Guayaquil')))->format('Y-m-d') ?>" required></label>
<label>Comprobante<input type="file" name="receipt" accept="image/jpeg,image/png,application/pdf" required></label><small>JPG, PNG o PDF · Hasta 5 MB. El administrador y tú podrán descargarlo de forma privada.</small>
<button class="primary">Enviar recarga para revisión</button></form></section>
<section><h2>Mis últimas 50 recargas</h2><?php if (!$history): ?><p>Todavía no has enviado recargas.</p><?php endif; ?>
<div class="table"><table><thead><tr><th>Recarga</th><th>Monto</th><th>Transferencia</th><th>Estado</th><th>Motivo</th><th>Comprobante</th></tr></thead><tbody>
<?php foreach ($history as $item): ?><tr><td>#<?= (int)$item['id'] ?></td><td><?= escape(dist_money($item['amount_cents'])) ?></td><td><?= escape($item['bank']) ?><br><?= escape($item['transfer_reference']) ?><br><?= escape($item['transfer_date']) ?></td><td><?= escape($item['status']) ?></td><td><?= escape($item['rejection_reason'] ?? '') ?></td><td><a href="comprobante.php?id=<?= (int)$item['id'] ?>">Descargar</a></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section><h2>Últimos 50 movimientos de saldo</h2><?php if (!$movements): ?><p>Todavía no tienes movimientos de saldo.</p><?php endif; ?>
<div class="table"><table><thead><tr><th>Fecha</th><th>Concepto</th><th>Movimiento</th></tr></thead><tbody>
<?php foreach ($movements as $item): ?><tr><td><?= escape($item['created_at']) ?></td><td><?= escape($item['concept']) ?><br><?= escape($item['reference']) ?></td><td><?= (int)$item['amount_cents'] > 0 ? '+' : '' ?><?= escape(dist_money($item['amount_cents'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section><?php endif; ?></main><script>
const bank = document.getElementById('bank');
const other = document.getElementById('bank-other');
const otherLabel = document.getElementById('bank-other-label');
if (bank && other && otherLabel) {
    function updateBankField() {
        const isOther = bank.value === 'otro';
        otherLabel.hidden = !isOther;
        other.required = isOther;
        other.disabled = !isOther;
    }
    bank.addEventListener('change', updateBankField);
    updateBankField();
}
</script></body></html>
