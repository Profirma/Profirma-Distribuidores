<?php
declare(strict_types=1);

function dist_db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }
    $url = parse_url((string)getenv('DATABASE_URL'));
    if (!$url || !in_array($url['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
        throw new RuntimeException('Configure DATABASE_URL con PostgreSQL.');
    }
    $host = $url['host'] ?? '';
    $database = rawurldecode(ltrim($url['path'] ?? '', '/'));
    if ($host === '' || $database === '' || preg_match('/[;\s]/', $host . $database)) {
        throw new RuntimeException('DATABASE_URL inválida.');
    }
    parse_str($url['query'] ?? '', $query);
    $ssl = $query['sslmode'] ?? 'prefer';
    if (!in_array($ssl, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
        throw new RuntimeException('sslmode inválido.');
    }
    $dsn = 'pgsql:host=' . $host . ';port=' . (int)($url['port'] ?? 5432)
        . ';dbname=' . $database . ';sslmode=' . $ssl;
    $connection = new PDO($dsn, rawurldecode($url['user'] ?? ''), rawurldecode($url['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $connection;
}
