<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/wallet.php';
require_once __DIR__ . '/../app/emissions.php';
try {
    dist_emissions_initialize(dist_db());
    echo "Tablas de emisión de distribuidores disponibles.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo preparar la emisión. Comprueba DATABASE_URL y que las recargas estén habilitadas.\n");
    exit(1);
}
