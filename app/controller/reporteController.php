<?php

use EquipoSiap\Siap\model\reporteModel;
require_once "app/config/session.php";
$object = new reporteModel();
// ======== antiguas funciones del controlador. Utilizado para crear las cartas de descargas y funciones que no se utilizaron. ========
// ======== Recomiendo pasar el formato de las cartas a la vista y quitar las funciones que no se utilicen ========

function sendExcelDownload(string $fileName, array $headers, array $rows): void
{
    if (headers_sent()) {
        echo 'Error: no se pueden enviar cabeceras para descarga.';
        return;
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: public');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        echo 'Error: no se pudo abrir la salida para escritura.';
        return;
    }

    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers, "\t");

    foreach ($rows as $row) {
        fputcsv($output, $row, "\t");
    }

    fclose($output);
    exit;
}

function formatRequerimientoRows(array $items): array
{
    return array_map(static function ($item) {
        return [
            $item['id_requerimiento'] ?? '',
            $item['codigo'] ?? '',
            $item['descripcion'] ?? '',
            $item['dependencia'] ?? '',
            $item['partida_presupuestaria'] ?? '',
            $item['anio_fiscal'] ?? '',
            $item['periodo'] ?? '',
            $item['estado'] ?? '',
            $item['fecha_envio'] ?? '',
        ];
    }, $items);
}

$modelInforme = new reporteModel();

$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';

if ($type === '' || $type === 'dashboard' || $type === 'main' || $type === 'list') {
    $buttons = $modelInforme->getReportButtons();
    include 'app/view/reporte/dashboard.php';
    return;
}

if ($type === 'export') {
    $reportCode = isset($_GET['report']) ? trim((string)$_GET['report']) : '';

    switch ($reportCode) {
        case '401':
        case '402':
        case '403':
        case '404':
        case '407':
            //$items = $modelInforme->getRequerimientosPorPartida((int)$reportCode);
            sendExcelDownload(
                sprintf('Informe_Partida_%s.xls', $reportCode),
                ['ID', 'Código', 'Descripción', 'Dependencia', 'Partida', 'Año Fiscal', 'Periodo', 'Estado', 'Fecha Envío'],
                formatRequerimientoRows($items)
            );
            break;
        case 'requerimiento_individual':
            //$items = $modelInforme->getReporteRequerimientoIndividual();
            sendExcelDownload(
                'Informe_Requerimiento_Individual.xls',
                ['ID', 'Código', 'Descripción', 'Dependencia', 'Partida', 'Año Fiscal', 'Periodo', 'Estado', 'Fecha Envío'],
                formatRequerimientoRows($items)
            );
            break;
        case 'dependencia_individual':
           // $items = $modelInforme->getResumenPorDependencia();
            sendExcelDownload(
                'Informe_Por_Dependencia.xls',
                ['Dependencia', 'Total Requerimientos'],
                array_map(static function ($item) {
                    return [$item['dependencia'] ?? '', $item['total_requerimientos'] ?? 0];
                }, $items)
            );
            break;
        case 'anteproyecto_global':
            //$items = $modelInforme->getAnteproyectoGlobal();
            sendExcelDownload(
                'Informe_Anteproyecto_Global.xls',
                ['Partida', 'Total Requerimientos'],
                array_map(static function ($item) {
                    return [$item['partida'] ?? '', $item['total_requerimientos'] ?? 0];
                }, $items)
            );
            break;
        default:
            echo 'Error: reporte no válido.';
            break;
    }

    return;
}

// llamada a la función que descarga el archivo excel. actualmente no funciona por diferencias de consulta sql  

if (isset($_GET['type']) && $_GET['type'] === 'descarga') {
    
    if (isset($_POST['requerimientoExc'])) {
        //ajustar para que obtenga los datos correspondiente desde la vista con vriables post
        //y un formulario sencillo
        //el primer argumento representa el id_Dep, el segundo es el nombre de la plantilla
        //el tercero es el nombre de archivo que se va a descargar

        //la segunda plantilla, en el caso de ser una dependencia debe llamarse 'TOTAL_Dependencia(Nombre de la dependencia).xlsx'
        $plantilla = $_POST['plantilla'];
        $result = $object->getReqReport('' , $plantilla ,"Consolidado_POA_");        
        exit;
    }
    if (isset($_POST['productoExc'])) {
        $result = $object->getProReport();
        exit;
    }
    //include 'app/view/reporte/dashboard.php';

}

echo 'Error: Tipo de vista no válido.';
return;

