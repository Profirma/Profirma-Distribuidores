<?php
declare(strict_types=1);

function dist_create_user(PDO $connection, string $name, string $email, string $password): void
{
    $email = strtolower(trim($email));
    $name = trim($name);
    if ($name === '' || strlen($name) > 120 || strlen($email) > 254
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12
        || strlen($password) > 72) {
        throw new InvalidArgumentException('Escribe un nombre, un correo válido y una contraseña de 12 a 72 caracteres.');
    }
    $statement = $connection->prepare(
        "INSERT INTO pf_distribuidores.users (name, email, password_hash, role) VALUES (?, ?, ?, 'distribuidor')"
    );
    $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
}

function dist_authenticate(PDO $connection, string $email, string $password): ?array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
        || strlen($password) > 72 || $password === '') {
        return null;
    }
    // A shared, per-account window prevents concurrent requests from bypassing the limit.
    $key = hash('sha256', $email);
    $connection->beginTransaction();
    try {
        $connection->exec("DELETE FROM pf_distribuidores.login_limits WHERE window_start < NOW() - INTERVAL '1 day'");
        $insert = $connection->prepare(
            'INSERT INTO pf_distribuidores.login_limits (account_key) VALUES (?) ON CONFLICT DO NOTHING'
        );
        $insert->execute([$key]);
        $lock = $connection->prepare(
            "SELECT attempts, window_start < NOW() - INTERVAL '15 minutes' AS expired
             FROM pf_distribuidores.login_limits WHERE account_key = ? FOR UPDATE"
        );
        $lock->execute([$key]);
        $limit = $lock->fetch();
        $expired = in_array($limit['expired'], [true, 1, '1', 't'], true);
        if ($expired) {
            $reset = $connection->prepare('UPDATE pf_distribuidores.login_limits SET attempts = 0, window_start = NOW() WHERE account_key = ?');
            $reset->execute([$key]);
        } elseif ((int)$limit['attempts'] >= 5) {
            $connection->commit();
            return null;
        }
        $find = $connection->prepare('SELECT * FROM pf_distribuidores.users WHERE email = ? AND active = TRUE');
        $find->execute([$email]);
        $user = $find->fetch();
        // Fixed dummy bcrypt hash also performs password work for unknown accounts.
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
        $valid = password_verify($password, $hash);
        if (!$user || !$valid) {
            $increment = $connection->prepare('UPDATE pf_distribuidores.login_limits SET attempts = attempts + 1 WHERE account_key = ?');
            $increment->execute([$key]);
            $connection->commit();
            return null;
        }
        $clear = $connection->prepare('DELETE FROM pf_distribuidores.login_limits WHERE account_key = ?');
        $clear->execute([$key]);
        $connection->commit();
        return $user;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
