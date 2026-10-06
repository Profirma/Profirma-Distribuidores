<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/wallet.php';
require_once __DIR__ . '/../app/emissions.php';
$number = $argv[1] ?? '';
$status = $argv[2] ?? '';
$note = trim($argv[3] ?? '');
if (!in_array($status, ['registrada', 'rechazada'], true) || $note === '' || strlen($note) > 500) {
    fwrite(STDERR, "Uso: php scripts/resolver_emision.php DIST-... registrada|rechazada 'Verificación realizada con ENEXT'\n");
    exit(1);
}
try {
    $find = dist_db()->prepare("SELECT id, user_id, status FROM pf_distribuidores.emissions WHERE numero_tramite = ?");
    $find->execute([$number]);
    $row = $find->fetch();
    if (!$row || !in_array($row['status'], ['enviando','revision'], true)) {
        throw new RuntimeException('El trámite no está pendiente de revisión.');
    }
    dist_finish_emission(dist_db(), (int)$row['user_id'], (int)$row['id'], $status, 0, $note);
    echo "Trámite actualizado sin reenviarlo a ENEXT.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo resolver el trámite. Revisa su estado.\n");
    exit(1);
}
