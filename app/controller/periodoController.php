<?php


use EquipoSiap\Siap\model\periodoModel;

$object = new periodomodel();

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (isset($_GET['type'])) {
    if ($_GET['type'] == 'list') {
        $result = $object->getAll();
        include 'app/view/periodo/listView.php';
    } elseif ($_GET['type'] == 'register') {
        if (isset($_POST['registerPeriodo'])) {
            if (isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin']) && isset($_POST['anio_fiscal_id'])) {
                $fechaInicio = $_POST['fecha_inicio'];
                $fechaFin = $_POST['fecha_fin'];
                $anioFiscalId = (int)$_POST['anio_fiscal_id'];

                if (!$object->isValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)) {
                    // Añadir diagnóstico para entender por qué falla la validación
                    $startYear = null;
                    $endYear = null;
                    try { $sd = new \DateTime($fechaInicio); $startYear = $sd->format('Y'); } catch (\Exception $e) { }
                    try { $ed = new \DateTime($fechaFin); $endYear = $ed->format('Y'); } catch (\Exception $e) { }
                    $anioInfo = $object->getAnioFiscalById($anioFiscalId);
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Fechas inválidas: la fecha de inicio debe ser anterior o igual a la fecha fin, y ambas deben corresponder al año fiscal seleccionado.',
                        'diagnostic' => [
                            'fecha_inicio' => $fechaInicio,
                            'fecha_fin' => $fechaFin,
                            'startYear' => $startYear,
                            'endYear' => $endYear,
                            'fiscalYearRecord' => $anioInfo
                        ]
                    ]);
                }

                $result = $object->add($fechaInicio, $fechaFin, $anioFiscalId);
                $payload = ['success' => (bool)$result, 'message' => $result ? 'Periodo registrado' : 'Error al guardar el periodo.'];
                if ($result) $payload['redirect'] = '?url=periodo&type=main';
                sendJsonResponse($payload);
            }
        }
        $anioFiscales = $object->getAniosFiscalesActivos();
        include 'app/view/periodo/registerView.php';
    } elseif ($_GET['type'] == 'main') {
        if (isset($_POST['getAll'])) {
            sendJsonResponse($object->getAll());
        }
        if (isset($_POST['activateItem'])) {
            sendJsonResponse(['success' => $object->activate((int)$_POST['idItem'])]);
        }
        if (isset($_POST['inactivateItem'])) {
            sendJsonResponse(['success' => $object->inactivate((int)$_POST['idItem'])]);
        }

        if (isset($_POST['updateItem'])) {
            $idItem = (int)$_POST['idItem'];
            $fechaInicio = $_POST['fecha_inicio'];
            $fechaFin = $_POST['fecha_fin'];
            $anioFiscalId = (int)$_POST['anio_fiscal_id'];

            if (!$object->isValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)) {
                sendJsonResponse(['success' => false, 'message' => 'Fechas inválidas: la fecha de inicio debe ser anterior o igual a la fecha fin, y ambas deben corresponder al año fiscal seleccionado.']);
            }

            $result = $object->update($idItem, $fechaInicio, $fechaFin, $anioFiscalId);
            sendJsonResponse(['success' => $result, 'message' => $result ? 'Periodo actualizado' : 'Error al actualizar el periodo.']);
        }
        $anioFiscales = $object->getAniosFiscalesActivos();
        include 'app/view/periodo/userView.php';
    } else {
        echo "Error: Tipo de vista no valido.";
    }
} else {
    // Endpoint para obtener periodo activo
    if (isset($_GET['type']) && $_GET['type'] === 'current') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($object->getLatestActive());
        die();
    }
    include 'app/view/welcomeView.php';
}

?>
