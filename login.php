<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| SI YA ESTÁ AUTENTICADO
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['distribuidor_id'])) {
    header('Location: dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| SOLO ACEPTAR POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS DEL FORMULARIO
|--------------------------------------------------------------------------
*/

$usuario = trim((string)($_POST['usuario'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {

    $_SESSION['login_error'] =
        'Ingresa tu usuario o correo electrónico y contraseña.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE_URL
|--------------------------------------------------------------------------
*/

$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {

    error_log('DATABASE_URL no está configurada.');

    $_SESSION['login_error'] =
        'No se pudo conectar con el sistema.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| CONECTAR A POSTGRESQL
|--------------------------------------------------------------------------
*/

try {

    $db = parse_url($databaseUrl);

    if (
        $db === false ||
        empty($db['host']) ||
        empty($db['path']) ||
        !isset($db['user']) ||
        !isset($db['pass'])
    ) {
        throw new RuntimeException(
            'DATABASE_URL inválida.'
        );
    }

    $host = (string)$db['host'];
    $port = (int)($db['port'] ?? 5432);
    $dbname = ltrim((string)$db['path'], '/');

    $dbUser = urldecode(
        (string)$db['user']
    );

    $dbPass = urldecode(
        (string)$db['pass']
    );

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $host,
        $port,
        $dbname
    );

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false,
        ]
    );

} catch (Throwable $e) {

    error_log(
        'Error conexión distribuidores: ' .
        $e->getMessage()
    );

    $_SESSION['login_error'] =
        'No se pudo conectar con el sistema.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR DISTRIBUIDOR
|--------------------------------------------------------------------------
|
| Puede ingresar con:
|
| - Correo electrónico
| - Número de documento
|
|--------------------------------------------------------------------------
*/

try {

    $sql = '
        SELECT
            id,
            nombres,
            apellidos,
            empresa,
            numero_documento,
            correo,
            password_hash,
            estado,
            tipo_membresia,
            fecha_inicio_membresia,
            fecha_vencimiento_membresia,
            saldo
        FROM distribuidores
        WHERE LOWER(correo) = LOWER(:usuario)
           OR numero_documento = :usuario
        LIMIT 1
    ';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $usuario
    ]);

    $distribuidor = $stmt->fetch();

} catch (Throwable $e) {

    error_log(
        'Error consulta distribuidor: ' .
        $e->getMessage()
    );

    $_SESSION['login_error'] =
        'No se pudo verificar la cuenta.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| DISTRIBUIDOR NO EXISTE
|--------------------------------------------------------------------------
*/

if (!$distribuidor) {

    $_SESSION['login_error'] =
        'Usuario o contraseña incorrectos.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR CONTRASEÑA
|--------------------------------------------------------------------------
*/

$hash = trim(
    (string)($distribuidor['password_hash'] ?? '')
);

if (
    $hash === '' ||
    !password_verify($password, $hash)
) {

    $_SESSION['login_error'] =
        'Usuario o contraseña incorrectos.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR ESTADO
|--------------------------------------------------------------------------
*/

$estado = strtoupper(
    trim(
        (string)($distribuidor['estado'] ?? '')
    )
);

if ($estado !== 'ACTIVO') {

    $_SESSION['login_error'] =
        'Tu cuenta de distribuidor no se encuentra activa.';

    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| CREAR SESIÓN
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$nombreCompleto = trim(
    (string)$distribuidor['nombres']
    . ' ' .
    (string)$distribuidor['apellidos']
);

$_SESSION['distribuidor_id'] =
    (int)$distribuidor['id'];

$_SESSION['distribuidor_nombre'] =
    $nombreCompleto;

$_SESSION['distribuidor_empresa'] =
    trim(
        (string)($distribuidor['empresa'] ?? '')
    );

$_SESSION['distribuidor_correo'] =
    (string)($distribuidor['correo'] ?? '');

$_SESSION['distribuidor_documento'] =
    (string)$distribuidor['numero_documento'];

$_SESSION['distribuidor_estado'] =
    $estado;

$_SESSION['distribuidor_membresia'] =
    (string)(
        $distribuidor['tipo_membresia'] ?? ''
    );

$_SESSION['distribuidor_saldo'] =
    (float)($distribuidor['saldo'] ?? 0);

$_SESSION['distribuidor_fecha_inicio'] =
    $distribuidor['fecha_inicio_membresia']
    ?? null;

$_SESSION['distribuidor_fecha_vencimiento'] =
    $distribuidor['fecha_vencimiento_membresia']
    ?? null;

/*
|--------------------------------------------------------------------------
| LIMPIAR ERROR ANTERIOR
|--------------------------------------------------------------------------
*/

unset($_SESSION['login_error']);

/*
|--------------------------------------------------------------------------
| ENTRAR AL DASHBOARD
|--------------------------------------------------------------------------
*/

header('Location: dashboard.php');
exit;
