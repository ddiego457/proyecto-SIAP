<?php
require __DIR__ . '/../app/config/Connect/ConnectDB.php';
require __DIR__ . '/../app/model/periodomodel.php';

use App\PracticaCrud\Model\periodoModel;

try {
    $m = new periodoModel();
    $ok = $m->add('2026-06-01', '2026-06-30', 1);
    echo json_encode(['add_ok' => $ok]) . PHP_EOL;

    // Show last inserted rows
    $stmt = $m->getAll();
    echo json_encode(['rows' => $stmt], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]) . PHP_EOL;
}
