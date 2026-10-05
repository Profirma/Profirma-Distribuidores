<?php

declare(strict_types=1);

session_start();

if (!empty($_SESSION['distribuidor_id'])) {
    header('Location: dashboard.php');
    exit;
}

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
    $_SESSION['login_error'] = 'Ingresa tu número de documento y contraseña.';
    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| CONEXIÓN A POSTGRESQL
|--------------------------------------------------------------------------
*/

$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    $_SESSION['login_error'] = 'No se pudo conectar con el sistema.';
    header('Location: index.php#acceso');
    exit;
}

try {

    $db = parse_url($databaseUrl);

    if (
        $db === false ||
        empty($db['host']) ||
        empty($db['path']) ||
        !isset($db['user']) ||
        !isset($db['pass'])
    ) {
        throw new RuntimeException('DATABASE_URL inválida.');
    }

    $host = $db['host'];
    $port = $db['port'] ?? 5432;
    $dbname = ltrim($db['path'], '/');
    $dbUser = urldecode((string)$db['user']);
    $dbPass = urldecode((string)$db['pass']);

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $host,
        $port,
        $dbname
    );

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (Throwable $e) {

    error_log(
        'Error conexión distribuidores: ' .
        $e->getMessage()
    );

    $_SESSION['login_error'] = 'No se pudo conectar con el sistema.';
    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR DISTRIBUIDOR
|--------------------------------------------------------------------------
|
| Para esta primera prueba utilizaremos el número de documento.
|
|--------------------------------------------------------------------------
*/

try {

    $sql = '
        SELECT
            id,
            nombres,
            apellidos,
            numero_documento,
            password_hash,
            saldo
        FROM distribuidores
        WHERE numero_documento = :usuario
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

    $_SESSION['login_error'] = 'No se pudo verificar la cuenta.';
    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR QUE EXISTA
|--------------------------------------------------------------------------
*/

if (!$distribuidor) {

    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header('Location: index.php#acceso');
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR CONTRASEÑA
|--------------------------------------------------------------------------
*/

$hash = (string)($distribuidor['password_hash'] ?? '');

if (
    $hash === '' ||
    !password_verify($password, $hash)
) {

    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
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
    (string)$distribuidor['nombres'] . ' ' .
    (string)$distribuidor['apellidos']
);

$_SESSION['distribuidor_id'] =
    (int)$distribuidor['id'];

$_SESSION['distribuidor_nombre'] =
    $nombreCompleto;

$_SESSION['distribuidor_empresa'] =
    '';

$_SESSION['distribuidor_documento'] =
    (string)$distribuidor['numero_documento'];

$_SESSION['distribuidor_saldo'] =
    (float)$distribuidor['saldo'];

/*
|--------------------------------------------------------------------------
| ENTRAR AL PANEL
|--------------------------------------------------------------------------
*/

header('Location: dashboard.php');
exit;
