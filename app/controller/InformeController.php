<?php

use EquipoSiap\Siap\model\InformeModel;

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

$modelInforme = new InformeModel();

$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';

if ($type === '' || $type === 'dashboard' || $type === 'main' || $type === 'list') {
    $buttons = $modelInforme->getReportButtons();
    include 'app/view/informe/dashboard.php';
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
            $items = $modelInforme->getRequerimientosPorPartida((int)$reportCode);
            sendExcelDownload(
                sprintf('Informe_Partida_%s.xls', $reportCode),
                ['ID', 'Código', 'Descripción', 'Dependencia', 'Partida', 'Año Fiscal', 'Periodo', 'Estado', 'Fecha Envío'],
                formatRequerimientoRows($items)
            );
            break;
        case 'requerimiento_individual':
            $items = $modelInforme->getReporteRequerimientoIndividual();
            sendExcelDownload(
                'Informe_Requerimiento_Individual.xls',
                ['ID', 'Código', 'Descripción', 'Dependencia', 'Partida', 'Año Fiscal', 'Periodo', 'Estado', 'Fecha Envío'],
                formatRequerimientoRows($items)
            );
            break;
        case 'dependencia_individual':
            $items = $modelInforme->getResumenPorDependencia();
            sendExcelDownload(
                'Informe_Por_Dependencia.xls',
                ['Dependencia', 'Total Requerimientos'],
                array_map(static function ($item) {
                    return [$item['dependencia'] ?? '', $item['total_requerimientos'] ?? 0];
                }, $items)
            );
            break;
        case 'anteproyecto_global':
            $items = $modelInforme->getAnteproyectoGlobal();
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

echo 'Error: Tipo de vista no válido.';
return;