<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/users.php';

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('pf_distribuidores');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => getenv('SESSION_SECURE') !== '0',
    'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function require_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('La sesión del formulario caducó. Regresa y vuelve a intentarlo.');
    }
}
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if (time() - (int)($_SESSION['last_activity'] ?? 0) > 1800
        || time() - (int)($_SESSION['authenticated_at'] ?? 0) > 28800) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $statement = dist_db()->prepare('SELECT id, name, email, role FROM pf_distribuidores.users WHERE id = ? AND active = TRUE');
    $statement->execute([$_SESSION['user_id']]);
    $user = $statement->fetch();
    if (!$user) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['last_activity'] = time();
    return $user;
}
function require_user(string $role): array
{
    try {
        $user = current_user();
    } catch (Throwable $error) {
        error_log('PRO-FIRMA: no se pudo comprobar la sesión.');
        http_response_code(503);
        exit('Servicio temporalmente no disponible.');
    }
    if (!$user) {
        redirect('index.php#acceso');
    }
    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('No tienes permiso para acceder a este panel.');
    }
    return $user;
}
