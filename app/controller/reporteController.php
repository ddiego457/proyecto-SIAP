<?php

use EquipoSiap\Siap\model\reporteModel;
require_once "app/config/session.php";

$model = new reporteModel();

$type = isset($_GET['type']) ? trim((string)$_GET['type']) : 'dashboard';

if ($type === 'dashboard') {
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

    if ($format !== 'excel' && $format !== 'pdf') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Formato no válido. Use excel o pdf.']);
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
                $idDep = isset($_POST['id_dep']) ? (int)$_POST['id_dep'] : (isset($_GET['id_dep']) ? (int)$_GET['id_dep'] : 0);
                if ($idDep <= 0) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Debe seleccionar una dependencia.']);
                    exit;
                }
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

echo 'Error: Tipo de vista no válido.';