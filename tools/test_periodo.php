<?php
require __DIR__ . '/../app/config/Connect/ConnectDB.php';
require __DIR__ . '/../app/model/periodomodel.php';

use App\PracticaCrud\Model\periodoModel;

try {
    $m = new periodoModel();
    $res = $m->isValidFiscalYearRange(1, '2026-06-01', '2026-06-30');
    echo json_encode(['ok' => true, 'valid' => $res]) . PHP_EOL;

    // Also test with '1/06/2026' format
    $res2 = $m->isValidFiscalYearRange(1, '1/06/2026', '30/06/2026');
    echo json_encode(['ok' => true, 'valid2' => $res2]) . PHP_EOL;
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]) . PHP_EOL;
}
