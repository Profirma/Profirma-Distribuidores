<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
require_csrf();
$email = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';
if (!is_string($email) || !is_string($password)) {
    http_response_code(400);
    exit('Solicitud inválida.');
}
try {
    $user = dist_authenticate(dist_db(), $email, $password);
    if ($user) {
        session_regenerate_id(true);
        $_SESSION = [
            'user_id' => (int)$user['id'], 'csrf' => bin2hex(random_bytes(32)),
            'last_activity' => time(), 'authenticated_at' => time(),
        ];
        redirect('dashboard.php');
    }
    $_SESSION['login_error'] = 'Correo o contraseña incorrectos. Si has intentado varias veces, espera 15 minutos.';
} catch (Throwable $error) {
    error_log('PRO-FIRMA: fallo de conexión al iniciar sesión.');
    $_SESSION['login_error'] = 'El acceso no está disponible temporalmente. Inténtalo más tarde.';
}
redirect('index.php#acceso');
