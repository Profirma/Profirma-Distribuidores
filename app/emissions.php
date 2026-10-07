<?php
declare(strict_types=1);

function dist_profiles(): array
{
    return ['018' => '15 días', '001' => '1 mes', '002' => '1 año', '005' => '2 años',
        '010' => '3 años', '007' => '4 años', '013' => '5 años'];
}

function dist_emission_prices(): array
{
    $prices = [];
    foreach (dist_profiles() as $profile => $label) {
        $value = trim((string)getenv('DIST_PRICE_' . $profile));
        if ($value !== '') {
            $prices[$profile] = dist_amount_cents($value);
        }
    }
    return $prices;
}

function dist_enext_config(): array
{
    $config = [];
    foreach (['ENEXT_API_URL', 'ENEXT_BASIC_USER', 'ENEXT_BASIC_PASSWORD',
        'ENEXT_SOCIO_USER', 'ENEXT_SOCIO_PASSWORD'] as $key) {
        $config[$key] = (string)getenv($key);
        if (trim($config[$key]) === '') {
            throw new RuntimeException('La emisión todavía no está configurada. Contacta con PRO-FIRMA.');
        }
    }
    $url = parse_url($config['ENEXT_API_URL']);
    if (!$url || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
        || isset($url['user']) || isset($url['pass']) || isset($url['fragment'])
        || !function_exists('curl_init')) {
        throw new RuntimeException('La conexión de emisión requiere una dirección HTTPS válida y cURL.');
    }
    return $config;
}

function dist_emissions_ready(PDO $connection): bool
{
    return (bool)$connection->query("SELECT to_regclass('pf_distribuidores.emissions') IS NOT NULL")->fetchColumn();
}

function dist_emissions_initialize(PDO $connection): void
{
    if (!dist_wallet_ready($connection)) {
        throw new RuntimeException('Primero habilita las recargas de aliados.');
    }
    $connection->beginTransaction();
    try {
        $connection->exec('SELECT pg_advisory_xact_lock(74061007)');
        $connection->exec("SET LOCAL lock_timeout = '15s'");
        $connection->exec("CREATE TABLE IF NOT EXISTS pf_distribuidores.emissions (
            id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            user_id BIGINT NOT NULL REFERENCES pf_distribuidores.users(id),
            request_key CHAR(64) NOT NULL,
            numero_tramite VARCHAR(60) NOT NULL UNIQUE,
            perfil_firma VARCHAR(3) NOT NULL CHECK (perfil_firma IN ('018','001','002','005','010','007','013')),
            cost_cents INTEGER NOT NULL CHECK (cost_cents BETWEEN 1 AND 10000000),
            cedula VARCHAR(10) NOT NULL,
            titular VARCHAR(250) NOT NULL,
            correo VARCHAR(254) NOT NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'enviando'
                CHECK (status IN ('enviando','registrada','rechazada','revision')),
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            resolved_at TIMESTAMPTZ,
            resolution_note VARCHAR(500),
            http_code INTEGER,
            UNIQUE (user_id, request_key)
        );
        CREATE INDEX IF NOT EXISTS emissions_user_history ON pf_distribuidores.emissions(user_id, id DESC)");
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function dist_emission_data(array $input): array
{
    $data = [];
    foreach (['perfil_firma','nombres','apellidos','cedula','codigo_dactilar','correo',
        'provincia','ciudad','parroquia','direccion','celular'] as $field) {
        $value = $input[$field] ?? null;
        if (!is_string($value) || trim($value) === '' || strlen($value) > 250 || preg_match('//u', $value) !== 1
            || preg_match('/[\\x00-\\x1F\\x7F]/', $value)) {
            throw new InvalidArgumentException('Completa correctamente el campo: ' . $field . '.');
        }
        $data[$field] = trim($value);
    }
    if (!array_key_exists($data['perfil_firma'], dist_profiles())) {
        throw new InvalidArgumentException('Selecciona una vigencia habilitada.');
    }
    if (!preg_match('/^[0-9]{10}$/D', $data['cedula'])) {
        throw new InvalidArgumentException('La cédula debe tener 10 dígitos.');
    }
    if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('El correo del titular no es válido.');
    }
    if (!preg_match('/^\\+?[0-9]{9,15}$/D', $data['celular'])) {
        throw new InvalidArgumentException('Escribe un celular válido, sin espacios.');
    }
    if (strlen($data['nombres']) > 120 || strlen($data['apellidos']) > 120) {
        throw new InvalidArgumentException('El nombre o apellido supera la longitud permitida.');
    }
    $data['codigo_dactilar'] = strtoupper($data['codigo_dactilar']);
    return $data;
}

function dist_reserve_emission(PDO $connection, int $userId, string $key, array $data, int $cost): array
{
    if (!preg_match('/^[a-f0-9]{64}$/D', $key)) {
        throw new InvalidArgumentException('El formulario caducó. Recarga la página.');
    }
    $connection->beginTransaction();
    try {
        // Same user lock as recharge approval: concurrent emissions cannot overspend.
        $lock = $connection->prepare('SELECT id FROM pf_distribuidores.users WHERE id = ? AND active = TRUE FOR UPDATE');
        $lock->execute([$userId]);
        if (!$lock->fetch()) {
            throw new InvalidArgumentException('La cuenta no está activa.');
        }
        $existing = $connection->prepare('SELECT * FROM pf_distribuidores.emissions WHERE user_id = ? AND request_key = ?');
        $existing->execute([$userId, $key]);
        if ($row = $existing->fetch()) {
            $connection->commit();
            return ['new' => false, 'row' => $row];
        }
        $duplicate = $connection->prepare("SELECT numero_tramite FROM pf_distribuidores.emissions
            WHERE user_id = ? AND cedula = ? AND perfil_firma = ? AND status <> 'rechazada'
            AND created_at > NOW() - INTERVAL '24 hours' ORDER BY id DESC LIMIT 1");
        $duplicate->execute([$userId, $data['cedula'], $data['perfil_firma']]);
        if ($number = $duplicate->fetchColumn()) {
            throw new InvalidArgumentException('Ya tienes un trámite reciente para este titular y vigencia: ' . $number . '. Consulta el historial antes de volver a emitir.');
        }
        if ($cost < 1 || dist_wallet_balance($connection, $userId) < $cost) {
            throw new InvalidArgumentException('Saldo insuficiente. Recarga tu cuenta antes de emitir.');
        }
        $limit = $connection->prepare("SELECT COUNT(*) FROM pf_distribuidores.emissions
            WHERE user_id = ? AND created_at > NOW() - INTERVAL '1 hour'");
        $limit->execute([$userId]);
        if ((int)$limit->fetchColumn() >= 30) {
            throw new InvalidArgumentException('Alcanzaste el límite de solicitudes por hora. Inténtalo más tarde.');
        }
        $number = 'DIST-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(8)));
        $insert = $connection->prepare('INSERT INTO pf_distribuidores.emissions
            (user_id, request_key, numero_tramite, perfil_firma, cost_cents, cedula, titular, correo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING *');
        $insert->execute([$userId, $key, $number, $data['perfil_firma'], $cost,
            $data['cedula'], $data['nombres'] . ' ' . $data['apellidos'], $data['correo']]);
        $row = $insert->fetch();
        $connection->commit();
        return ['new' => true, 'row' => $row];
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function dist_finish_emission(PDO $connection, int $userId, int $id, string $status, int $http = 0, string $note = ''): void
{
    if (!in_array($status, ['registrada','rechazada','revision'], true)) {
        throw new InvalidArgumentException('Estado de emisión inválido.');
    }
    $connection->beginTransaction();
    try {
        $lock = $connection->prepare('SELECT id FROM pf_distribuidores.users WHERE id = ? FOR UPDATE');
        $lock->execute([$userId]);
        $update = $connection->prepare("UPDATE pf_distribuidores.emissions
            SET status = ?, http_code = ?, resolution_note = ?, resolved_at = NOW()
            WHERE id = ? AND user_id = ? AND status IN ('enviando','revision')");
        $update->execute([$status, $http, $note, $id, $userId]);
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function dist_enext_result(int $http, string $body, bool $transportOk): string
{
    if (!$transportOk) {
        return 'revision';
    }
    $result = json_decode(trim($body), true);
    if (!is_array($result) && ($start = strpos($body, '{')) !== false) {
        $result = json_decode(substr($body, $start), true);
    }
    if (!is_array($result) || !isset($result['codigo'])
        || !in_array($result['codigo'], [0, 1, '0', '1'], true)) {
        return 'revision';
    }
    if ($http >= 200 && $http < 300 && (int)$result['codigo'] === 1) {
        // An explicit provider acceptance registers the request.
        // Biometric links may be delivered directly by email; they are not needed by the portal.
        // This does not claim that biometrics or final certificate issuance are complete.
        return 'registrada';
    }
    if ((int)$result['codigo'] === 0 && (($http >= 200 && $http < 300)
        || in_array($http, [400,401,403,422], true))) {
        return 'rechazada';
    }
    return 'revision';
}

function dist_send_enext(array $config, array $data, string $number): array
{
    $payload = $data + ['numero_tramite' => $number, 'usuario' => $config['ENEXT_SOCIO_USER'],
        'password' => $config['ENEXT_SOCIO_PASSWORD'], 'tipo_envio' => 'EMAIL', 'tipo_clave' => 1];
    $body = '';
    $ch = curl_init(trim($config['ENEXT_API_URL']));
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $config['ENEXT_BASIC_USER'] . ':' . $config['ENEXT_BASIC_PASSWORD'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS_STR => 'HTTPS',
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) + strlen($chunk) > 1048576) {
                return 0;
            }
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    $ok = curl_exec($ch) !== false;
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlCode = curl_errno($ch);
    curl_close($ch);
    // Never persist raw responses, credential-bearing payloads, or biometric links.
    $parsed = json_decode(trim($body), true);
    if (!is_array($parsed) && ($start = strpos($body, '{')) !== false) {
        $parsed = json_decode(substr($body, $start), true);
    }
    $code = is_array($parsed) ? ($parsed['codigo'] ?? null) : null;
    $diagnostic = [
        'http' => $http, 'curl_code' => $curlCode, 'transport_ok' => $ok,
        'bytes' => strlen($body), 'json_object' => is_array($parsed),
        'codigo' => in_array($code, [0,1,'0','1'], true) ? (int)$code : 'other_or_missing',
        'token_present' => is_string($parsed['token_biometria'] ?? null) && trim($parsed['token_biometria']) !== '',
        'link_present' => is_string($parsed['link_biometria'] ?? null) && trim($parsed['link_biometria']) !== '',
    ];
    error_log('ALIADOS ENEXT ' . $number . ' ' . json_encode($diagnostic));
    return ['status' => dist_enext_result($http, $body, $ok), 'http' => $http,
        'diagnostic' => json_encode($diagnostic, JSON_THROW_ON_ERROR)];
}

function dist_submit_emission(PDO $connection, int $userId, array $input): array
{
    $data = dist_emission_data($input);
    if (($input['confirmed'] ?? '') !== '1') {
        throw new InvalidArgumentException('Confirma los datos del titular y el descuento de saldo.');
    }
    $key = $input['request_key'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D', $key)) {
        throw new InvalidArgumentException('El formulario caducó.');
    }
    // Completed and in-flight requests return their original result, never another API call.
    $find = $connection->prepare('SELECT * FROM pf_distribuidores.emissions WHERE user_id = ? AND request_key = ?');
    $find->execute([$userId, $key]);
    if ($row = $find->fetch()) {
        return $row;
    }
    $config = dist_enext_config();
    $prices = dist_emission_prices();
    $cost = $prices[$data['perfil_firma']] ?? null;
    if (!$cost) {
        throw new InvalidArgumentException('Esta vigencia todavía no tiene una tarifa de aliado.');
    }
    if (($input['quoted_cost'] ?? '') !== (string)$cost) {
        throw new InvalidArgumentException('La tarifa cambió. Recarga la página para confirmar el precio actualizado.');
    }
    $reservation = dist_reserve_emission($connection, $userId, $key, $data, $cost);
    $row = $reservation['row'];
    if (!$reservation['new']) {
        return $row;
    }
    try {
        $result = dist_send_enext($config, $data, $row['numero_tramite']);
    } catch (Throwable $error) {
        error_log('ALIADOS ENEXT ' . $row['numero_tramite'] . ' internal_exception');
        $result = ['status' => 'revision', 'http' => 0, 'diagnostic' => 'internal_exception'];
    }
    dist_finish_emission($connection, $userId, (int)$row['id'], $result['status'], $result['http'], $result['diagnostic'] ?? '');
    $find->execute([$userId, $key]);
    return $find->fetch();
}

function dist_emission_status(string $status): string
{
    return ['enviando' => 'En proceso · saldo reservado', 'registrada' => 'Registrada en ENEXT',
        'rechazada' => 'Rechazada · saldo liberado', 'revision' => 'Respuesta de ENEXT sin confirmar · saldo reservado'][$status] ?? 'En revisión';
}
