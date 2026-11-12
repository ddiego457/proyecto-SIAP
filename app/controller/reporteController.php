<?php

use EquipoSiap\Siap\model\reporteModel;
$this->requireLogin();
$this->requireModule('reporte');

$model = new reporteModel();

$type = isset($_GET['type']) && is_scalar($_GET['type']) ? trim((string)$_GET['type']) : 'main';

if(isset($_GET['type'])){
if ($type === 'main') {
    $buttons = $model->getReportButtons();
    $esAdminReportes = $this->esAdmin();
    if (!$esAdminReportes) {
        $buttons = array_values(array_filter($buttons, function ($button) {
            return $button['code'] === 'req_individual';
        }));
        if (!empty($buttons)) {
            $buttons[0]['description'] = 'Requerimiento creado para su dependencia';
            unset($buttons[0]['requiresDep']);
        }
    }
    include 'app/view/reporte/dashboard.php';
    return;
}

if ($type === 'get_dependencias') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$this->esAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para consultar dependencias de otros usuarios.']);
        exit;
    }
    $estadoEnvioRaw = isset($_GET['estado_envio']) && is_scalar($_GET['estado_envio'])
        ? trim((string)$_GET['estado_envio'])
        : '';
    $errorValidacion = $model->validar('estado_envio_reporte', $estadoEnvioRaw);
    if ($errorValidacion !== null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $errorValidacion]);
        exit;
    }
    try {
        $deps = $model->getDependenciasParaSelect($estadoEnvioRaw === 'enviado' ? 1 : 0);
        echo json_encode(['success' => true, 'data' => $deps]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($type === 'get_partidas') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$this->esAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para consultar partidas de reportes.']);
        exit;
    }
    try {
        echo json_encode(['success' => true, 'data' => $model->getPartidasParaReporte()]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($type === 'export') {
    $report = isset($_GET['report']) && is_scalar($_GET['report']) ? trim((string)$_GET['report']) : '';
    $format = isset($_GET['format']) && is_scalar($_GET['format']) ? trim((string)$_GET['format']) : 'excel';

    if (!$this->esAdmin() && $report !== 'req_individual') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Solo puede generar el informe de su propio requerimiento.']);
        exit;
    }

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
                $idPartidasRaw = isset($_POST['id_partidas']) ? $_POST['id_partidas'] : null;
                $errorValidacion = $model->validar('partidas_reporte', $idPartidasRaw);
                if ($errorValidacion !== null) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errorValidacion]);
                    exit;
                }
                $partidasDisponibles = array_map('intval', array_column($model->getPartidasParaReporte(), 'id_partida'));
                $idPartidas = $idPartidasRaw === 'todas' ? [] : array_values(array_unique(array_map('intval', $idPartidasRaw)));
                if ($idPartidas && array_diff($idPartidas, $partidasDisponibles)) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'La selección contiene una partida no disponible.']);
                    exit;
                }
                if ($format === 'excel') {
                    $model->exportProductosExcel($idPartidas);
                } else {
                    $model->exportProductosPDF($idPartidas);
                }
                break;

            case 'dependencias_responsable':
                $filtroDependencias = isset($_POST['filtro_dependencias']) && is_scalar($_POST['filtro_dependencias'])
                    ? trim((string)$_POST['filtro_dependencias'])
                    : '';
                $errorValidacion = $model->validar('filtro_dependencias', $filtroDependencias);
                if ($errorValidacion !== null) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errorValidacion]);
                    exit;
                }
                if ($format === 'excel') {
                    $model->exportDependenciasResponsableExcel($filtroDependencias);
                } else {
                    $model->exportDependenciasResponsablePDF($filtroDependencias);
                }
                break;

            case 'req_individual':
                $estadoEnvioRaw = isset($_POST['estado_envio']) && is_scalar($_POST['estado_envio'])
                    ? trim((string)$_POST['estado_envio'])
                    : '';
                $errorValidacion = $model->validar('estado_envio_reporte', $estadoEnvioRaw);
                if ($errorValidacion !== null) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errorValidacion]);
                    exit;
                }
                $idDepRaw = $this->esAdmin()
                    ? (isset($_POST['id_dep']) && is_scalar($_POST['id_dep'])
                        ? trim((string)$_POST['id_dep'])
                        : (isset($_GET['id_dep']) && is_scalar($_GET['id_dep']) ? trim((string)$_GET['id_dep']) : ''))
                    : (isset($_SESSION['id_dep']) && is_scalar($_SESSION['id_dep']) ? (string)$_SESSION['id_dep'] : '');
                $errorValidacion = $model->validar('id_dep_reporte', $idDepRaw);
                if ($errorValidacion !== null || (int)$idDepRaw <= 0) {
                    http_response_code($this->esAdmin() ? 422 : 403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => !$this->esAdmin()
                            ? 'No se encontró una dependencia asociada a su usuario.'
                            : ($errorValidacion !== null ? $errorValidacion : 'Debe seleccionar una dependencia.')
                    ]);
                    exit;
                }
                $idDep = (int)$idDepRaw;
                $incluirNoEnviados = $estadoEnvioRaw === 'sin_enviar';
                if (!$model->hasReqReportData($idDep, $incluirNoEnviados)) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => $incluirNoEnviados
                            ? ($this->esAdmin()
                                ? 'No hay requerimientos sin enviar con datos para la dependencia seleccionada.'
                                : 'Su requerimiento está vacío; agregue productos y cantidades antes de generar el informe.')
                            : 'No hay requerimientos con datos para la dependencia seleccionada.'
                    ]);
                    exit;
                }
                if ($format === 'excel') {
                    $model->exportReqIndividualExcel($idDep, $incluirNoEnviados);
                } else {
                    $model->exportReqIndividualPDF($idDep, $incluirNoEnviados);
                }
                break;

            case 'req_global':
                $modoGlobal = isset($_POST['modo_global']) && is_scalar($_POST['modo_global'])
                    ? trim((string)$_POST['modo_global'])
                    : '';
                $errorValidacion = $model->validar('modo_requerimiento_global', $modoGlobal);
                if ($errorValidacion !== null) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errorValidacion]);
                    exit;
                }
                if ($format === 'excel') {
                    $model->exportReqGlobalExcel($modoGlobal);
                } else {
                    $model->exportReqGlobalPDF($modoGlobal);
                }
                break;

            default:
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Reporte no válido: ' . $report]);
                break;
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
}
echo 'Error: Tipo de vista no válido.';