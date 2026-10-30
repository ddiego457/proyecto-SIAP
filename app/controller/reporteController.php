<?php

use EquipoSiap\Siap\model\reporteModel;
$this->requireLogin();
$this->requireModule('reporte');

$model = new reporteModel();

$type = isset($_GET['type']) ? trim((string)$_GET['type']) : 'main';

if(isset($_GET['type'])){
if ($type === 'main') {
    $buttons = $model->getReportButtons();
    include 'app/view/reporte/dashboard.php';
    return;
}

if ($type === 'get_dependencias') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $deps = $model->getDependenciasParaSelect();
        echo json_encode(['success' => true, 'data' => $deps]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($type === 'export') {
    $report = isset($_GET['report']) ? trim((string)$_GET['report']) : '';
    $format = isset($_GET['format']) ? trim((string)$_GET['format']) : 'excel';

    $errorValidacion = $model->validar('formato_reporte', $format);
    if ($errorValidacion === null) {
        $errorValidacion = $model->validar('nombre_reporte', $report);
    }
    if ($errorValidacion !== null) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $errorValidacion]);
        exit;
    }

    try {
        switch ($report) {
            case 'productos_partida':
                if ($format === 'excel') {
                    $model->exportProductosExcel();
                } else {
                    $model->exportProductosPDF();
                }
                break;

            case 'dependencias_responsable':
                if ($format === 'excel') {
                    $model->exportDependenciasResponsableExcel();
                } else {
                    $model->exportDependenciasResponsablePDF();
                }
                break;

            case 'req_individual':
                $idDepRaw = isset($_POST['id_dep']) ? trim((string)$_POST['id_dep']) : (isset($_GET['id_dep']) ? trim((string)$_GET['id_dep']) : '');
                $errorValidacion = $model->validar('id_dep_reporte', $idDepRaw);
                if ($errorValidacion !== null || (int)$idDepRaw <= 0) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errorValidacion !== null ? $errorValidacion : 'Debe seleccionar una dependencia.']);
                    exit;
                }
                $idDep = (int)$idDepRaw;
                if ($format === 'excel') {
                    $model->exportReqIndividualExcel($idDep);
                } else {
                    $model->exportReqIndividualPDF($idDep);
                }
                break;

            case 'req_global':
                if ($format === 'excel') {
                    $model->exportReqGlobalExcel();
                } else {
                    $model->exportReqGlobalPDF();
                }
                break;

            default:
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Reporte no válido: ' . $report]);
                break;
        }
    } catch (\Throwable $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
}
echo 'Error: Tipo de vista no válido.';