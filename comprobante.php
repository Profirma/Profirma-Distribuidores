<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$user = require_user('distribuidor');
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(404);
    exit('Comprobante no encontrado.');
}
try {
    dist_receipt_download(dist_db(), (int)$id, (int)$user['id']);
} catch (Throwable $exception) {
    http_response_code(503);
    exit('No se pudo descargar el comprobante.');
}
