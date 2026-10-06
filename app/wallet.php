<?php
declare(strict_types=1);

function dist_wallet_schema(): string
{
    return "CREATE TABLE IF NOT EXISTS pf_distribuidores.recharges (
        id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id BIGINT NOT NULL REFERENCES pf_distribuidores.users(id),
        amount_cents INTEGER NOT NULL CHECK (amount_cents BETWEEN 1 AND 10000000),
        bank VARCHAR(100) NOT NULL,
        bank_key VARCHAR(100) NOT NULL,
        transfer_reference VARCHAR(80) NOT NULL,
        reference_key VARCHAR(80) NOT NULL,
        transfer_date DATE NOT NULL,
        receipt_mime VARCHAR(30) NOT NULL CHECK (receipt_mime IN ('image/jpeg', 'image/png', 'application/pdf')),
        receipt_data BYTEA NOT NULL CHECK (octet_length(receipt_data) BETWEEN 1 AND 5242880),
        receipt_sha256 CHAR(64) NOT NULL,
        request_key CHAR(64) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'pendiente' CHECK (status IN ('pendiente', 'aprobada', 'rechazada')),
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        reviewed_at TIMESTAMPTZ,
        reviewed_by VARCHAR(120),
        rejection_reason VARCHAR(500),
        UNIQUE (user_id, request_key),
        CHECK (
            (status = 'pendiente' AND reviewed_at IS NULL AND reviewed_by IS NULL AND rejection_reason IS NULL)
            OR (status = 'aprobada' AND reviewed_at IS NOT NULL AND reviewed_by IS NOT NULL AND rejection_reason IS NULL)
            OR (status = 'rechazada' AND reviewed_at IS NOT NULL AND reviewed_by IS NOT NULL AND rejection_reason IS NOT NULL)
        )
    );
    CREATE UNIQUE INDEX IF NOT EXISTS recharges_receipt_once
        ON pf_distribuidores.recharges(receipt_sha256) WHERE status <> 'rechazada';
    CREATE UNIQUE INDEX IF NOT EXISTS recharges_transfer_once
        ON pf_distribuidores.recharges(bank_key, reference_key, transfer_date) WHERE status <> 'rechazada';
    CREATE INDEX IF NOT EXISTS recharges_user_history ON pf_distribuidores.recharges(user_id, id DESC);
    CREATE INDEX IF NOT EXISTS recharges_pending ON pf_distribuidores.recharges(status, id);
    CREATE TABLE IF NOT EXISTS pf_distribuidores.wallet_movements (
        id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id BIGINT NOT NULL REFERENCES pf_distribuidores.users(id),
        recharge_id BIGINT NOT NULL UNIQUE REFERENCES pf_distribuidores.recharges(id),
        amount_cents INTEGER NOT NULL CHECK (amount_cents BETWEEN 1 AND 10000000),
        credited_by VARCHAR(120) NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    CREATE INDEX IF NOT EXISTS wallet_user_history ON pf_distribuidores.wallet_movements(user_id, id DESC)";
}

function dist_wallet_initialize(PDO $connection): void
{
    $connection->beginTransaction();
    try {
        $connection->exec('SELECT pg_advisory_xact_lock(74061006)');
        $connection->exec(dist_wallet_schema());
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function dist_wallet_ready(PDO $connection): bool
{
    return (int)$connection->query("SELECT CASE WHEN to_regclass('pf_distribuidores.recharges') IS NOT NULL
        AND to_regclass('pf_distribuidores.wallet_movements') IS NOT NULL THEN 1 ELSE 0 END")->fetchColumn() === 1;
}

function dist_amount_cents(string $input): int
{
    $input = trim($input);
    if (!preg_match('/^(0|[1-9][0-9]{0,5})(?:[.,]([0-9]{1,2}))?$/D', $input, $parts)) {
        throw new InvalidArgumentException('Escribe el monto con hasta dos decimales, sin separadores de miles.');
    }
    $cents = (int)$parts[1] * 100 + (int)str_pad($parts[2] ?? '', 2, '0');
    if ($cents < 1 || $cents > 10000000) {
        throw new InvalidArgumentException('La recarga debe estar entre $0.01 y $100000.00.');
    }
    return $cents;
}

function dist_money(int|string $cents): string
{
    $value = (int)$cents;
    return ($value < 0 ? '-' : '') . '$' . intdiv(abs($value), 100)
        . '.' . str_pad((string)(abs($value) % 100), 2, '0', STR_PAD_LEFT);
}

function dist_receipt_upload(array $file): array
{
    if (!isset($file['error'], $file['tmp_name']) || !is_int($file['error']) || !is_string($file['tmp_name'])
        || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Selecciona un comprobante JPG, PNG o PDF de hasta 5 MB.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size < 1 || $size > 5242880) {
        throw new InvalidArgumentException('El comprobante debe pesar entre 1 byte y 5 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
        throw new InvalidArgumentException('Solo se aceptan comprobantes JPG, PNG o PDF.');
    }
    if ($mime !== 'application/pdf') {
        $image = getimagesize($file['tmp_name']);
        if (!$image || ($image['mime'] ?? '') !== $mime || $image[0] * $image[1] > 20000000) {
            throw new InvalidArgumentException('La imagen no es válida o supera 20 megapíxeles.');
        }
    }
    $data = file_get_contents($file['tmp_name']);
    if ($data === false || ($mime === 'application/pdf' && !str_starts_with($data, '%PDF-'))) {
        throw new InvalidArgumentException('No se pudo leer un comprobante válido.');
    }
    return ['mime' => $mime, 'data' => $data, 'hash' => hash('sha256', $data)];
}

function dist_submit_recharge(PDO $connection, int $userId, array $fields, array $file): int
{
    foreach (['amount', 'bank', 'reference', 'date', 'request_key'] as $key) {
        if (!isset($fields[$key]) || !is_string($fields[$key])) {
            throw new InvalidArgumentException('Completa los datos de la transferencia.');
        }
    }
    $amount = dist_amount_cents($fields['amount']);
    $bank = trim($fields['bank']);
    $reference = trim($fields['reference']);
    $bankKey = preg_replace('/[^a-z0-9]/', '', strtolower($bank));
    $referenceKey = preg_replace('/[^A-Z0-9]/', '', strtoupper($reference));
    if ($bank === '' || strlen($bank) > 100 || strlen($bankKey) < 3
        || strlen($reference) > 80 || strlen($referenceKey) < 3
        || !preg_match('/^[a-f0-9]{64}$/D', $fields['request_key'])) {
        throw new InvalidArgumentException('Escribe el banco y una referencia de transferencia válida.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fields['date'], new DateTimeZone('America/Guayaquil'));
    $today = new DateTimeImmutable('today', new DateTimeZone('America/Guayaquil'));
    if (!$date || $date->format('Y-m-d') !== $fields['date'] || $date > $today) {
        throw new InvalidArgumentException('La fecha de transferencia no es válida o está en el futuro.');
    }
    $receipt = dist_receipt_upload($file);
    $connection->beginTransaction();
    try {
        $user = $connection->prepare("SELECT id FROM pf_distribuidores.users WHERE id = ? AND active = TRUE FOR UPDATE");
        $user->execute([$userId]);
        if (!$user->fetch()) {
            throw new InvalidArgumentException('La cuenta de distribuidor no está activa.');
        }
        $existing = $connection->prepare('SELECT id FROM pf_distribuidores.recharges WHERE user_id = ? AND request_key = ?');
        $existing->execute([$userId, $fields['request_key']]);
        if ($id = $existing->fetchColumn()) {
            $connection->commit();
            return (int)$id;
        }
        $limits = $connection->prepare("SELECT COUNT(*) FILTER (WHERE status = 'pendiente') AS pending,
            COUNT(*) FILTER (WHERE created_at > NOW() - INTERVAL '24 hours') AS daily
            FROM pf_distribuidores.recharges WHERE user_id = ?");
        $limits->execute([$userId]);
        $limit = $limits->fetch();
        if ((int)$limit['pending'] >= 5 || (int)$limit['daily'] >= 20) {
            throw new InvalidArgumentException('Tienes varias recargas en revisión. Espera a que el administrador las revise.');
        }
        $insert = $connection->prepare("INSERT INTO pf_distribuidores.recharges
            (user_id, amount_cents, bank, bank_key, transfer_reference, reference_key, transfer_date,
             receipt_mime, receipt_data, receipt_sha256, request_key)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, decode(?, 'base64'), ?, ?) RETURNING id");
        $insert->execute([$userId, $amount, $bank, $bankKey, $reference, $referenceKey, $date->format('Y-m-d'),
            $receipt['mime'], base64_encode($receipt['data']), $receipt['hash'], $fields['request_key']]);
        $id = (int)$insert->fetchColumn();
        $connection->commit();
        return $id;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        if ($error instanceof PDOException && $error->getCode() === '23505') {
            throw new InvalidArgumentException('Ese comprobante o transferencia ya está registrado. Consulta el historial antes de enviarlo otra vez.');
        }
        throw $error;
    }
}

function dist_review_recharge(PDO $connection, int $id, string $action, string $actor, string $reason = ''): bool
{
    $actor = trim($actor);
    $reason = trim($reason);
    if ($id < 1 || !in_array($action, ['aprobar', 'rechazar'], true) || $actor === '' || strlen($actor) > 120
        || ($action === 'rechazar' && ($reason === '' || strlen($reason) > 500))) {
        throw new InvalidArgumentException('Para rechazar una recarga, escribe el motivo (hasta 500 caracteres).');
    }
    $connection->beginTransaction();
    try {
        $find = $connection->prepare('SELECT id, user_id, amount_cents, status FROM pf_distribuidores.recharges WHERE id = ? FOR UPDATE');
        $find->execute([$id]);
        $recharge = $find->fetch();
        if (!$recharge) {
            throw new InvalidArgumentException('La recarga no existe.');
        }
        if ($recharge['status'] !== 'pendiente') {
            $connection->commit();
            return false;
        }
        if ($action === 'aprobar') {
            $lock = $connection->prepare('SELECT id FROM pf_distribuidores.users WHERE id = ? AND active = TRUE FOR UPDATE');
            $lock->execute([$recharge['user_id']]);
            if (!$lock->fetch()) {
                throw new InvalidArgumentException('No se puede acreditar saldo a una cuenta inactiva.');
            }
            $credit = $connection->prepare('INSERT INTO pf_distribuidores.wallet_movements
                (user_id, recharge_id, amount_cents, credited_by) VALUES (?, ?, ?, ?)');
            $credit->execute([$recharge['user_id'], $id, $recharge['amount_cents'], $actor]);
        }
        $update = $connection->prepare('UPDATE pf_distribuidores.recharges
            SET status = ?, reviewed_at = NOW(), reviewed_by = ?, rejection_reason = ? WHERE id = ?');
        $update->execute([$action === 'aprobar' ? 'aprobada' : 'rechazada', $actor,
            $action === 'rechazar' ? $reason : null, $id]);
        $connection->commit();
        return true;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function dist_wallet_balance(PDO $connection, int $userId): int
{
    $statement = $connection->prepare('SELECT COALESCE(SUM(amount_cents), 0)
        FROM pf_distribuidores.wallet_movements WHERE user_id = ?');
    $statement->execute([$userId]);
    return (int)$statement->fetchColumn();
}

function dist_receipt_download(PDO $connection, int $id, ?int $ownerId): never
{
    $sql = "SELECT receipt_mime, encode(receipt_data, 'base64') AS data FROM pf_distribuidores.recharges WHERE id = ?";
    $params = [$id];
    if ($ownerId !== null) {
        $sql .= ' AND user_id = ?';
        $params[] = $ownerId;
    }
    $statement = $connection->prepare($sql);
    $statement->execute($params);
    $receipt = $statement->fetch();
    if (!$receipt) {
        http_response_code(404);
        exit('Comprobante no encontrado.');
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'][$receipt['receipt_mime']];
    $data = base64_decode($receipt['data'], true);
    if ($data === false) {
        http_response_code(503);
        exit('No se pudo descargar el comprobante.');
    }
    header('Content-Type: ' . $receipt['receipt_mime']);
    header('Content-Disposition: attachment; filename="comprobante-' . $id . '.' . $ext . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: sandbox; default-src 'none'");
    echo $data;
    exit;
}
