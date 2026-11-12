<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ReporteExcelGeneratorTrait;
use EquipoSiap\Siap\helpers\ReportePdfGeneratorTrait;
use EquipoSiap\Siap\helpers\ValidationTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class reporteModel extends ConnectDB
{
    use ValidationTrait;
    use ReporteExcelGeneratorTrait;
    use ReportePdfGeneratorTrait;

    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getReportButtons(): array
    {
        return [
            [
                'code' => 'productos_partida',
                'title' => 'Productos por Partida',
                'description' => 'Seleccione una o varias partidas presupuestarias, o todas.',
            ],
            [
                'code' => 'dependencias_responsable',
                'title' => 'Dependencias',
                'description' => 'Activas con/sin responsable o inactivas.',
            ],
            [
                'code' => 'req_individual',
                'title' => 'Requerimientos Individuales por Dependencia',
                'description' => 'Seleccione dependencia y estado del requerimiento.',
                'requiresDep' => true,
            ],
            [
                'code' => 'req_global',
                'title' => 'Requerimientos Global (Consolidado)',
                'description' => 'Consolidado, cantidades por producto o totales por partida.',
            ],
        ];
    }

    public function getPartidasParaReporte(): array
    {
        try {
            $stmt = $this->conex->query(
                "SELECT id_partida, cod_partida, descripcion
                 FROM partidas
                 WHERE estado = 1
                 ORDER BY cod_partida"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error cargando partidas para el reporte: ' . $e->getMessage());
        }
    }

    public function getDependenciasConResponsable(string $filtro = 'activas_con_responsable'): array
    {
        try {
            $where = match ($filtro) {
                'activas_con_responsable' => 'd.estado = 1 AND r.id_responsable IS NOT NULL',
                'activas_sin_responsable' => 'd.estado = 1 AND r.id_responsable IS NULL',
                'inactivas' => 'd.estado = 0',
                default => throw new \InvalidArgumentException('Filtro de dependencias no válido.'),
            };
            $query = "
                SELECT 
                    d.id_dep,
                    d.nom_dep AS dependencia,
                    COALESCE(r.nom_rep, 'SIN ASIGNAR') AS responsable,
                    COALESCE(ro.descripcion, 'SIN CARGO') AS cargo,
                    COALESCE(t.telefono, 'SIN TELÉFONO') AS telefono,
                    CASE WHEN d.estado = 1 THEN 'ACTIVA' ELSE 'INACTIVA' END AS estado_dependencia,
                    CASE
                        WHEN r.id_responsable IS NULL THEN 'SIN ASIGNAR'
                        WHEN r.estado = 1 THEN 'ACTIVO'
                        ELSE 'INACTIVO'
                    END AS estado_responsable
                FROM dependencias d
                LEFT JOIN cargo c ON c.id_dep = d.id_dep AND c.estado = 1
                LEFT JOIN responsables r ON r.id_responsable = c.id_responsable AND r.estado = 1
                LEFT JOIN roles ro ON ro.id_rol = r.id_rol
                LEFT JOIN telefonos t ON t.id_proveedor = r.id_responsable AND t.estado = 1
                WHERE {$where}
                ORDER BY d.nom_dep ASC
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando dependencias para el reporte: ' . $e->getMessage());
        }
    }

    public function getDependenciasParaSelect(int $estadoEnvio = 1): array
    {
        try {
            $query = "
                SELECT d.id_dep, d.nom_dep
                FROM dependencias d
                WHERE d.estado = 1
                AND EXISTS (
                    SELECT 1 FROM requerimientos r
                    JOIN anio_fiscal af ON af.id_aniof = r.id_aniof AND af.activo = 1
                    JOIN detalle_req dr ON dr.id_req = r.id_req AND dr.cant_mes > 0
                    WHERE r.id_dep = d.id_dep
                      AND r.estado = 1
                      AND r.estado_envio = :estado_envio
                )
                ORDER BY d.nom_dep ASC
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute([':estado_envio' => $estadoEnvio]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error cargando dependencias para select: ' . $e->getMessage());
        }
    }

    public function hasReqReportData(int $idDep, bool $incluirNoEnviados = false): bool
    {
        try {
            $estadoEnvio = $incluirNoEnviados ? 0 : 1;
            $stmt = $this->conex->prepare(
                "SELECT 1
                 FROM requerimientos r
                 JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                 JOIN detalle_req dr ON dr.id_req = r.id_req
                 WHERE r.id_dep = ?
                   AND r.estado = 1
                   AND dr.cant_mes > 0
                   AND af.activo = 1
                   AND r.estado_envio = ?
                 LIMIT 1"
            );
            $stmt->execute([$idDep, $estadoEnvio]);
            return (bool)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error verificando datos del requerimiento: ' . $e->getMessage());
        }
    }

    public function exportReqIndividualExcel(int $idDep, bool $incluirNoEnviados = false): void
    {
        $data = $this->executeGetReqReport($idDep, $incluirNoEnviados);
        $context = $this->getReqReportContext($idDep, $incluirNoEnviados);
        $this->executeGetXlxsReqReport(
            $data,
            'TOTAL_Dependencia.xlsx',
            '',
            $context,
            $this->buildIndividualReportFilename($context) . '.xlsx'
        );
    }

    public function exportReqIndividualPDF(int $idDep, bool $incluirNoEnviados = false): void
    {
        $data = $this->executeGetReqReport($idDep, $incluirNoEnviados);
        $context = $this->getReqReportContext($idDep, $incluirNoEnviados);
        $title = $this->buildIndividualReportTitle($context);
        $filename = $this->buildIndividualReportFilename($context) . '.pdf';
        $this->executeGetPdfReqReport($data, $title, $filename, (string)$context['anio']);
    }

    public function exportReqGlobalExcel(string $modo = 'consolidado'): void
    {
        $data = $this->executeGetReqReport();
        if ($modo === 'consolidado') {
            $year = $this->getActiveFiscalYear();
            $this->executeGetXlxsReqReport(
                $data,
                'TOTAL_Todas_las_Dependencias.xlsx',
                '',
                null,
                'POA_Global_' . $year . '.xlsx',
                $year
            );
            return;
        }
        $this->executeGetXlsxGlobalResumen($data, $modo);
    }

    public function exportReqGlobalPDF(string $modo = 'consolidado'): void
    {
        $data = $this->executeGetReqReport();
        if ($modo === 'consolidado') {
            $year = $this->getActiveFiscalYear();
            $this->executeGetPdfReqReport($data, 'Consolidado POA Global', 'POA_Global_' . $year . '.pdf', $year);
            return;
        }
        $this->executeGetPdfGlobalResumen($data, $modo);
    }

    public function exportProductosExcel(array $idPartidas = []): void
    {
        $data = $this->executeGetProReport($idPartidas);
        $this->executeGetXlsxProReport($data, $idPartidas);
    }

    public function exportDependenciasResponsableExcel(string $filtro = 'activas_con_responsable'): void
    {
        $data = $this->getDependenciasConResponsable($filtro);
        $this->executeGetXlsxDependenciasResponsable($data, $filtro);
    }

    public function exportProductosPDF(array $idPartidas = []): void
    {
        $data = $this->executeGetProReport($idPartidas);
        $this->executeGetPdfProReport($data, $idPartidas);
    }

    public function exportDependenciasResponsablePDF(string $filtro = 'activas_con_responsable'): void
    {
        $data = $this->getDependenciasConResponsable($filtro);
        $this->executeGetPdfDependenciasResponsable($data, $filtro);
    }

    private function getReqReportContext(int $idDep, bool $incluirNoEnviados): array
    {
        $estadoEnvio = $incluirNoEnviados ? 0 : 1;
        $stmt = $this->conex->prepare(
            "SELECT d.nom_dep AS dependencia, af.anio AS anio
             FROM dependencias d
             JOIN requerimientos r ON r.id_dep = d.id_dep
             JOIN anio_fiscal af ON af.id_aniof = r.id_aniof
             JOIN detalle_req dr ON dr.id_req = r.id_req AND dr.cant_mes > 0
             WHERE d.id_dep = ?
               AND r.estado = 1
               AND r.estado_envio = ?
               AND af.activo = 1
             LIMIT 1"
        );
        $stmt->execute([$idDep, $estadoEnvio]);
        $context = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$context) {
            throw new \RuntimeException('No se encontró el año fiscal y requerimiento para generar el informe.');
        }

        $context['estado'] = $incluirNoEnviados ? 'Sin enviar' : 'Enviado';
        return $context;
    }

    private function buildIndividualReportTitle(array $context): string
    {
        return 'Requerimiento ' . $context['estado'] . ' - ' . $context['dependencia'];
    }

    private function buildIndividualReportFilename(array $context): string
    {
        $parts = [$context['dependencia'], 'Requerimiento', $context['estado'], (string)$context['anio']];
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', implode('_', $parts));
        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $filename);
        return trim((string)$filename, '_') . '_' . date('Ymd');
    }

    // ===================== CONSULTAS BASE =====================

    private function executeGetReqReport($id = '', bool $incluirNoEnviados = false)
    {
        $params = [];
        if (!empty($id)) {
            $estadoEnvio = $incluirNoEnviados ? 0 : 1;
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
                    p.descripcion AS partida,
                    prod.nom_prod AS Descripcion,
                    prod.precio AS precio,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 1 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ene,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 2 THEN req_valido.cant_mes ELSE 0 END), 0) AS Feb,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 3 THEN req_valido.cant_mes ELSE 0 END), 0) AS Mar,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 4 THEN req_valido.cant_mes ELSE 0 END), 0) AS Abr,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 5 THEN req_valido.cant_mes ELSE 0 END), 0) AS May,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 6 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jun,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 7 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jul,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 8 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ago,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 9 THEN req_valido.cant_mes ELSE 0 END), 0) AS Sep,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 10 THEN req_valido.cant_mes ELSE 0 END), 0) AS Oct,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 11 THEN req_valido.cant_mes ELSE 0 END), 0) AS Nov,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 12 THEN req_valido.cant_mes ELSE 0 END), 0) AS Dic,
                    COALESCE(SUM(req_valido.cant_mes), 0) AS cantidad_Total,
                    (prod.precio * req_valido.tasa_bcv_usd) as precio,
                    COALESCE(SUM(req_valido.cant_mes * prod.precio), 0) AS Total_precio_dolares,
                    COALESCE(SUM(req_valido.cant_mes * prod.precio * req_valido.tasa_bcv_usd), 0) AS Total_precio
                FROM productos prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN (
                    SELECT 
                        dr.id_prod, 
                        dr.mes, 
                        dr.cant_mes, 
                        tb.tasa_bcv_usd
                    FROM detalle_req dr
                    JOIN requerimientos r ON dr.id_req = r.id_req
                    JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                    JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
                    WHERE r.estado = 1 AND af.activo = 1 AND r.estado_envio = {$estadoEnvio} AND r.id_dep = ?
                ) AS req_valido ON prod.id_prod = req_valido.id_prod
                GROUP BY 
                    p.cod_partida, 
                    p.descripcion,
                    prod.id_prod, 
                    prod.nom_prod, 
                    prod.precio
                HAVING COALESCE(SUM(req_valido.cant_mes), 0) > 0
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_prod ASC";
            $params = [$id];
        } else {
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
                    p.descripcion AS partida,
                    prod.nom_prod AS Descripcion,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 1 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ene,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 2 THEN req_valido.cant_mes ELSE 0 END), 0) AS Feb,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 3 THEN req_valido.cant_mes ELSE 0 END), 0) AS Mar,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 4 THEN req_valido.cant_mes ELSE 0 END), 0) AS Abr,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 5 THEN req_valido.cant_mes ELSE 0 END), 0) AS May,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 6 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jun,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 7 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jul,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 8 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ago,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 9 THEN req_valido.cant_mes ELSE 0 END), 0) AS Sep,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 10 THEN req_valido.cant_mes ELSE 0 END), 0) AS Oct,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 11 THEN req_valido.cant_mes ELSE 0 END), 0) AS Nov,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 12 THEN req_valido.cant_mes ELSE 0 END), 0) AS Dic,
                    (prod.precio * req_valido.tasa_bcv_usd) as precio,
                    prod.precio as precio_dolares,
                    COALESCE((SUM(req_valido.cant_mes) * prod.precio), 0) as Total_precio_dolares,
                    COALESCE(SUM(req_valido.cant_mes), 0) AS cantidad_Total,
                    COALESCE(SUM(req_valido.cant_mes * prod.precio * req_valido.tasa_bcv_usd), 0) AS Total_precio
                FROM productos prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN (
                    SELECT 
                        dr.id_prod, 
                        dr.mes, 
                        dr.cant_mes, 
                        tb.tasa_bcv_usd
                    FROM detalle_req dr
                    JOIN requerimientos r ON dr.id_req = r.id_req
                    JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                    JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
                    WHERE r.estado = 1 AND r.estado_envio = 1 AND af.activo = 1
                ) AS req_valido ON prod.id_prod = req_valido.id_prod
                GROUP BY 
                    p.cod_partida, 
                    p.descripcion,
                    prod.id_prod, 
                    prod.nom_prod, 
                    prod.precio
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_prod ASC";
        }

        try {
            $stmt = $this->conex->prepare($query);
            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando requerimientos: ' . $e->getMessage());
        }
    }

    private function executeGetProReport(array $idPartidas = [])
    {
        try {
            $filtroPartidas = '';
            $params = [];
            if ($idPartidas) {
                $placeholders = implode(',', array_fill(0, count($idPartidas), '?'));
                $filtroPartidas = "WHERE prod.id_partida IN ({$placeholders})";
                $params = $idPartidas;
            }
            $query = "
                SELECT 
                    p.descripcion AS partida,
                    p.cod_partida AS codigo,
                    prod.nom_prod AS Descripcion,
                    prod.precio AS precio_dolares,
                    COALESCE(prod.precio * tb.tasa_bcv_usd, prod.precio) AS precio_bolivares
                FROM productos prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN tasa_bcv tb ON tb.estado = 1
                {$filtroPartidas}
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_prod ASC";
            $stmt = $this->conex->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando productos: ' . $e->getMessage());
        }
    }

    private function getGlobalReportRows(array $data, string $modo): array
    {
        if ($modo === 'cantidades') {
            $rows = [];
            foreach ($data as $row) {
                if ((float)$row['cantidad_Total'] <= 0) {
                    continue;
                }
                [$descripcion, $unidad] = $this->parseProductDescription((string)$row['Descripcion']);
                $rows[] = [
                    'codigo' => $row['codigo'],
                    'partida' => $row['partida'],
                    'producto' => $descripcion,
                    'unidad' => $unidad,
                    'cantidad' => (float)$row['cantidad_Total'],
                ];
            }
            return $rows;
        }

        if ($modo === 'totales') {
            $totales = [];
            foreach ($data as $row) {
                $codigo = (string)$row['codigo'];
                if (!isset($totales[$codigo])) {
                    $totales[$codigo] = [
                        'codigo' => $codigo,
                        'partida' => $row['partida'],
                        'total_usd' => 0.0,
                        'total_bs' => 0.0,
                    ];
                }
                $totales[$codigo]['total_usd'] += (float)$row['Total_precio_dolares'];
                $totales[$codigo]['total_bs'] += (float)$row['Total_precio'];
            }
            return array_values($totales);
        }

        throw new \InvalidArgumentException('Modo de consolidado no válido.');
    }

    private function executeGetXlsxGlobalResumen(array $data, string $modo): void
    {
        $rows = $this->getGlobalReportRows($data, $modo);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $title = $modo === 'cantidades'
            ? 'Cantidades anuales por producto y partida'
            : 'Totales por partida en USD y Bs.';
        $sheet->setTitle($modo === 'cantidades' ? 'Cantidades por producto' : 'Totales por partida');
        $columnCount = $modo === 'cantidades' ? 5 : 4;
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', $title . ' - Año ' . $this->getActiveFiscalYear());
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true)->setSize(14);

        $headers = $modo === 'cantidades'
            ? ['Partida', 'Descripción de partida', 'Producto', 'U.M.', 'Cantidad anual']
            : ['Partida', 'Descripción de partida', 'Total USD', 'Total Bs.'];
        foreach ($headers as $index => $header) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '3';
            $sheet->setCellValue($coordinate, $header);
        }
        $sheet->getStyle('A3:' . $lastColumn . '3')->getFont()->setBold(true);

        $rowNumber = 4;
        foreach ($rows as $row) {
            if ($modo === 'cantidades') {
                $values = [$row['codigo'], $row['partida'], $row['producto'], $row['unidad'], $row['cantidad']];
            } else {
                $values = [$row['codigo'], $row['partida'], $row['total_usd'], $row['total_bs']];
            }
            foreach ($values as $index => $value) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . $rowNumber;
                $sheet->setCellValue($coordinate, $value);
            }
            $rowNumber++;
        }
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A4');

        $this->downloadReportExcel(
            $spreadsheet,
            'POA_Global_' . $modo . '_' . $this->getActiveFiscalYear() . '.xlsx',
            false
        );
    }

    private function executeGetPdfGlobalResumen(array $data, string $modo): void
    {
        $rows = $this->getGlobalReportRows($data, $modo);
        $year = $this->getActiveFiscalYear();
        $title = $modo === 'cantidades'
            ? 'Cantidades anuales por producto y partida'
            : 'Totales por partida en USD y Bs.';
        $tableClass = 'tabla-resumen';
        $headers = $modo === 'cantidades'
            ? ['Partida', 'Descripción de partida', 'Producto', 'U.M.', 'Cantidad anual']
            : ['Partida', 'Descripción de partida', 'Total USD', 'Total Bs.'];

        $html = '<table class="' . $tableClass . '"><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $values = $modo === 'cantidades'
                ? [$row['codigo'], $row['partida'], $row['producto'], $row['unidad'], $this->formatReportNumber($row['cantidad'])]
                : [$row['codigo'], $row['partida'], $this->formatReportNumber($row['total_usd'], true), $this->formatReportNumber($row['total_bs'], true)];
            $html .= '<tr>';
            foreach ($values as $value) {
                $html .= '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        if (!$rows) {
            $html .= '<tr><td colspan="' . count($headers) . '">No se encontraron registros para mostrar.</td></tr>';
        }
        $html .= '</tbody></table>';

        $mpdf = $this->createReportPdf($title, $year);
        $this->outputReportPdf(
            $mpdf,
            $this->reportTableCss($tableClass),
            $html,
            'POA_Global_' . $modo . '_' . $year . '.pdf'
        );
    }

    private function getActiveFiscalYear(): string
    {
        $year = $this->conex->query('SELECT anio FROM anio_fiscal WHERE activo = 1 LIMIT 1')->fetchColumn();
        if (!$year) {
            throw new \RuntimeException('No hay un año fiscal activo para generar el reporte.');
        }
        return (string)$year;
    }

    private function parseProductDescription(string $value, string $defaultUnit = 'UND'): array
    {
        $separatorStart = strpos($value, '|');
        if ($separatorStart === false) {
            return [$value, $defaultUnit];
        }

        $separatorEnd = strpos($value, '|', $separatorStart + 1);
        if ($separatorEnd === false) {
            return [trim(substr($value, 0, $separatorStart)), trim(substr($value, $separatorStart + 1)) ?: $defaultUnit];
        }

        $unit = substr($value, $separatorStart + 1, $separatorEnd - $separatorStart - 1);
        $description = trim(substr($value, 0, $separatorStart) . substr($value, $separatorEnd + 1));
        return [$description, $unit !== '' ? $unit : $defaultUnit];
    }

    private function formatReportNumber($value, bool $isAmount = false): string
    {
        if (!is_numeric($value) || (float)$value == 0.0) {
            return '';
        }

        return number_format((float)$value, $isAmount ? 2 : 0, ',', '.');
    }

    private function safeReportFilenamePart(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $part = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ascii === false ? $value : $ascii);
        return trim((string)$part, '_') ?: 'reporte';
    }

    private function getPartidaReportLabel(array $idPartidas): string
    {
        if (!$idPartidas) {
            return 'Todas las partidas';
        }
        $labels = [];
        foreach ($this->getPartidasParaReporte() as $partida) {
            if (in_array((int)$partida['id_partida'], $idPartidas, true)) {
                $labels[] = (string)$partida['cod_partida'];
            }
        }
        return implode(', ', $labels);
    }

    private function formatPartidaSheetName(string $codigo): string
    {
        $formatted = str_replace('.', ',', $codigo);
        if (strlen($formatted) === 3 && ctype_digit($formatted)) {
            $formatted = substr($formatted, 0, 1) . ',' . substr($formatted, 1);
        }
        return $formatted;
    }

    private function getDependencyFilterLabel(string $filtro): string
    {
        return match ($filtro) {
            'activas_con_responsable' => 'Dependencias activas con responsable',
            'activas_sin_responsable' => 'Dependencias activas sin responsable',
            'inactivas' => 'Dependencias inactivas (con o sin responsable)',
            default => throw new \InvalidArgumentException('Filtro de dependencias no válido.'),
        };
    }

    // ===================== GENERADORES EXCEL (con try-catch) =====================

    private function executeGetXlxsReqReport(
        array $datos,
        string $plantilla,
        string $prefijo,
        ?array $metadata = null,
        ?string $downloadFilename = null,
        ?string $reportYear = null
    ): void
    {
        try {
            $documento = $this->loadReportTemplate($plantilla);

            $filasHojas = [
                '4,01' => 21, '4,02' => 21, '4,03' => 21,
                '4,04' => 21, '4,07' => 21
            ];

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    $partidaFormateada = str_replace('.', ',', (string)$fila['codigo']);
                    [$descripcion, $uMedida] = $this->parseProductDescription((string)$fila['Descripcion'], 'UNIDAD');

                    if (strlen($partidaFormateada) === 3 && is_numeric($partidaFormateada)) {
                        $partidaFormateada = substr($partidaFormateada, 0, 1) . ',' . substr($partidaFormateada, 1);
                    }

                    if (isset($filasHojas[$partidaFormateada]) && $documento->sheetNameExists($partidaFormateada)) {
                        $hoja = $documento->getSheetByName($partidaFormateada);
                        $numFila = $filasHojas[$partidaFormateada];

                        $hoja->setCellValue('A' . $numFila, $fila['codigo']);
                        $hoja->setCellValue('B' . $numFila, $descripcion);
                        $hoja->setCellValue('C' . $numFila, $uMedida);
                        $hoja->setCellValue('D' . $numFila, $fila['precio'] ?? 0);

                        $val = fn($v) => (!empty($v) && $v > 0) ? $v : '';

                        $hoja->setCellValue('E' . $numFila, $val($fila['Ene']));
                        $hoja->setCellValue('F' . $numFila, $val($fila['Feb']));
                        $hoja->setCellValue('G' . $numFila, $val($fila['Mar']));
                        $hoja->setCellValue('H' . $numFila, $val($fila['Abr']));
                        $hoja->setCellValue('I' . $numFila, $val($fila['May']));
                        $hoja->setCellValue('J' . $numFila, $val($fila['Jun']));
                        $hoja->setCellValue('K' . $numFila, $val($fila['Jul']));
                        $hoja->setCellValue('L' . $numFila, $val($fila['Ago']));
                        $hoja->setCellValue('M' . $numFila, $val($fila['Sep']));
                        $hoja->setCellValue('N' . $numFila, $val($fila['Oct']));
                        $hoja->setCellValue('O' . $numFila, $val($fila['Nov']));
                        $hoja->setCellValue('P' . $numFila, $val($fila['Dic']));

                        $hoja->setCellValue('Q' . $numFila, $fila['cantidad_Total']);
                        $hoja->setCellValue('R' . $numFila, $fila['Total_precio']);
                        $hoja->setCellValue('S' . $numFila, $fila['precio_dolares'] ?? $fila['precio']);
                        $hoja->setCellValue('T' . $numFila, $fila['Total_precio_dolares']);

                        $filasHojas[$partidaFormateada]++;
                    }
                }
            }

            if ($metadata !== null) {
                $summarySheet = $documento->getSheetByName('TOTAL POA');
                if ($summarySheet !== null) {
                    $summarySheet->setCellValue(
                        'B14',
                        'Requerimiento ' . $metadata['estado'] . ' - Año ' . $metadata['anio']
                    );
                    $summarySheet->setCellValue(
                        'B15',
                        'DEPENDENCIA: ' . $metadata['dependencia'] . ' | ESTADO: ' . $metadata['estado']
                    );
                }
                $reportYear = (string)$metadata['anio'];
            }
            if ($reportYear !== null) {
                $titleSheet = $documento->getSheetByName('TITULO');
                if ($titleSheet !== null) {
                    $titleSheet->setCellValue('B15', 'ANTEPROYECTO POA ' . $reportYear);
                }
                if ($metadata === null) {
                    $summarySheet = $documento->getSheetByName('TOTAL POA');
                    if ($summarySheet !== null) {
                        $summarySheet->setCellValue(
                            'B14',
                            'Recursos a presupuestar por las dependencias. Año ' . $reportYear
                        );
                    }
                }
            }

            $filename = $downloadFilename ?? ($prefijo . date('Y') . '.xlsx');
            $this->downloadReportExcel($documento, $filename);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando Excel: ' . $e->getMessage());
        }
    }

    private function executeGetXlsxProReport(array $datos, array $idPartidas): void
    {
        try {
            $documento = $this->loadReportTemplate('TOTAL_Productos.xlsx');
            $selectedCodes = [];
            foreach ($this->getPartidasParaReporte() as $partida) {
                if (in_array((int)$partida['id_partida'], $idPartidas, true)) {
                    $selectedCodes[] = $this->formatPartidaSheetName((string)$partida['cod_partida']);
                }
            }
            if ($idPartidas) {
                $documento->setActiveSheetIndex(0);
                for ($index = $documento->getSheetCount() - 1; $index > 0; $index--) {
                    $sheetName = $documento->getSheet($index)->getTitle();
                    if (!in_array($sheetName, $selectedCodes, true)) {
                        $documento->removeSheetByIndex($index);
                    }
                }
            }
            $documento->getProperties()->setTitle('Productos por partida: ' . $this->getPartidaReportLabel($idPartidas));
            $documento->getSheetByName('TITULO')->setCellValue(
                'B16',
                'PARTIDAS: ' . $this->getPartidaReportLabel($idPartidas)
            );

            $filasHojas = [
                '4,01' => 21, '4,02' => 21, '4,03' => 21,
                '4,04' => 21, '4,07' => 21
            ];

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    $partidaFormateada = $this->formatPartidaSheetName((string)$fila['codigo']);

                    [$descripcion, $uMedida] = $this->parseProductDescription((string)$fila['Descripcion'], 'UNIDAD');

                    if (isset($filasHojas[$partidaFormateada]) && $documento->sheetNameExists($partidaFormateada)) {
                        $hoja = $documento->getSheetByName($partidaFormateada);
                        $numFila = $filasHojas[$partidaFormateada];

                        $hoja->setCellValue('A' . $numFila, $fila['codigo']);
                        $hoja->setCellValue('B' . $numFila, $descripcion);
                        $hoja->setCellValue('C' . $numFila, $uMedida);
                        $hoja->setCellValue('D' . $numFila, $fila['precio_bolivares']);
                        $hoja->setCellValue('E' . $numFila, $fila['precio_dolares']);

                        $filasHojas[$partidaFormateada]++;
                    }
                }
            }

            $codes = $idPartidas
                ? implode('_', array_map(fn($code) => $this->safeReportFilenamePart($code), array_filter(explode(', ', $this->getPartidaReportLabel($idPartidas)))))
                : 'todas';
            $this->downloadReportExcel($documento, 'Productos_partidas_' . $codes . '_' . date('Y') . '.xlsx', false);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando Excel productos: ' . $e->getMessage());
        }
    }

    private function executeGetXlsxDependenciasResponsable(array $datos, string $filtro): void
    {
        try {
            $documento = $this->loadReportTemplate('Dependencias_Responsable.xlsx');
            $hoja = $documento->getActiveSheet();

            // Headers en fila 1 (asumiendo que la plantilla tiene headers)
            // Datos desde fila 2
            $fila = 2;
            foreach ($datos as $row) {
                $hoja->setCellValue('A' . $fila, $row['id_dep']);
                $hoja->setCellValue('B' . $fila, $row['dependencia']);
                $hoja->setCellValue('C' . $fila, $row['responsable']);
                $hoja->setCellValue('D' . $fila, $row['cargo']);
                $hoja->setCellValue('E' . $fila, $row['telefono']);
                $hoja->setCellValue('F' . $fila, $row['estado_dependencia']);
                $hoja->setCellValue('G' . $fila, $row['estado_responsable']);
                $fila++;
            }

            $documento->getProperties()->setTitle($this->getDependencyFilterLabel($filtro));
            $this->downloadReportExcel(
                $documento,
                $this->safeReportFilenamePart($filtro) . '_Dependencias_' . date('Y') . '.xlsx'
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando Excel dependencias: ' . $e->getMessage());
        }
    }

    // ===================== GENERADOR PDF (con try-catch) =====================

    private function executeGetPdfProReport(array $datos, array $idPartidas): void
    {
        try {
            $partidaLabel = $this->getPartidaReportLabel($idPartidas);
            $mpdf = $this->createReportPdf('Productos por partidas: ' . $partidaLabel);

            $css = $this->reportTableCss('tabla-pro');

            $html = '
            <table class="tabla-pro">
                <thead>
                    <tr>
                        <th width="6%">Partida</th>
                        <th width="40%">Descripci&oacute;n</th>
                        <th width="10%">U.M.</th>
                        <th width="12%">P. U. (Bs.)</th>
                        <th width="12%">P. U. ($)</th>
                        <th width="10%">Partida C&oacute;digo</th>
                    </tr>
                </thead>
                <tbody>';

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    [$descripcion, $uMedida] = $this->parseProductDescription((string)$fila['Descripcion']);

                    $partidaCode = $fila['codigo'];
                    if (strlen($partidaCode) === 3 && is_numeric($partidaCode)) {
                        $partidaCode = substr($partidaCode, 0, 1) . ',' . substr($partidaCode, 1);
                    }

                    $html .= '<tr>
                        <td class="font-bold">' . htmlspecialchars($partidaCode) . '</td>
                        <td class="text-left">' . htmlspecialchars($descripcion) . '</td>
                        <td>' . htmlspecialchars($uMedida) . '</td>
                        <td class="text-right">' . $this->formatReportNumber($fila['precio_bolivares'], true) . '</td>
                        <td class="text-right">' . $this->formatReportNumber($fila['precio_dolares'], true) . '</td>
                        <td class="font-bold">' . htmlspecialchars($fila['codigo']) . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="6">No se encontraron productos para mostrar.</td></tr>';
            }

            $html .= '</tbody></table>';

            $codes = $idPartidas
                ? implode('_', array_map(fn($code) => $this->safeReportFilenamePart($code), array_filter(explode(', ', $partidaLabel))))
                : 'todas';
            $this->outputReportPdf($mpdf, $css, $html, 'Productos_partidas_' . $codes . '_' . date('Y_m_d') . '.pdf');
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando PDF productos: ' . $e->getMessage());
        }
    }

    private function executeGetPdfDependenciasResponsable(array $datos, string $filtro): void
    {
        try {
            $label = $this->getDependencyFilterLabel($filtro);
            $mpdf = $this->createReportPdf($label);

            $css = $this->reportTableCss('tabla-dep');

            $html = '
            <table class="tabla-dep">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="25%">Dependencia</th>
                        <th width="20%">Responsable</th>
                        <th width="18%">Cargo</th>
                        <th width="14%">Tel&eacute;fono</th>
                        <th width="9%">Est. Dep.</th>
                        <th width="9%">Est. Resp.</th>
                    </tr>
                </thead>
                <tbody>';

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    $html .= '<tr>
                        <td class="font-bold">' . htmlspecialchars($fila['id_dep']) . '</td>
                        <td class="text-left">' . htmlspecialchars($fila['dependencia']) . '</td>
                        <td class="text-left">' . htmlspecialchars($fila['responsable']) . '</td>
                        <td class="text-left">' . htmlspecialchars($fila['cargo']) . '</td>
                        <td class="text-left">' . htmlspecialchars($fila['telefono']) . '</td>
                        <td>' . htmlspecialchars($fila['estado_dependencia']) . '</td>
                        <td>' . htmlspecialchars($fila['estado_responsable']) . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="7">No se encontraron dependencias para mostrar.</td></tr>';
            }

            $html .= '</tbody></table>';

            $this->outputReportPdf(
                $mpdf,
                $css,
                $html,
                $this->safeReportFilenamePart($filtro) . '_Dependencias_' . date('Y_m_d') . '.pdf'
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando PDF dependencias: ' . $e->getMessage());
        }
    }

    private function executeGetPdfReqReport(
        array $datos,
        string $tituloReporte = 'Consolidado POA',
        string $filename = '',
        ?string $year = null
    ): void
    {
        try {
            $mpdf = $this->createReportPdf($tituloReporte, $year);

            $css = $this->reportTableCss('tabla-poa');

            $html = '
            <table class="tabla-poa">
                <thead>
                    <tr>
                        <th width="4%">Part.</th>
                        <th width="18%">Descripci&oacute;n</th>
                        <th width="4%">U.M.</th>
                        <th width="5%">P. U. ($)</th>
                        <th width="3%">Ene</th>
                        <th width="3%">Feb</th>
                        <th width="3%">Mar</th>
                        <th width="3%">Abr</th>
                        <th width="3%">May</th>
                        <th width="3%">Jun</th>
                        <th width="3%">Jul</th>
                        <th width="3%">Ago</th>
                        <th width="3%">Sep</th>
                        <th width="3%">Oct</th>
                        <th width="3%">Nov</th>
                        <th width="3%">Dic</th>
                        <th width="4%">Total Cant.</th>
                        <th width="7%">Total (Bs.)</th>
                        <th width="7%">Total ($)</th>
                    </tr>
                </thead>
                <tbody>';

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    [$descripcion, $uMedida] = $this->parseProductDescription((string)$fila['Descripcion']);

                    $html .= '<tr>
                        <td class="font-bold">' . htmlspecialchars($fila['codigo']) . '</td>
                        <td class="text-left">' . htmlspecialchars($descripcion) . '</td>
                        <td>' . htmlspecialchars($uMedida) . '</td>
                        <td class="text-right">' . $this->formatReportNumber($fila['precio'] ?? ($fila['precio_dolares'] ?? 0), true) . '</td>
                        <td>' . $this->formatReportNumber($fila['Ene']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Feb']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Mar']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Abr']) . '</td>
                        <td>' . $this->formatReportNumber($fila['May']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Jun']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Jul']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Ago']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Sep']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Oct']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Nov']) . '</td>
                        <td>' . $this->formatReportNumber($fila['Dic']) . '</td>
                        <td class="font-bold">' . $this->formatReportNumber($fila['cantidad_Total']) . '</td>
                        <td class="text-right font-bold">' . $this->formatReportNumber($fila['Total_precio'], true) . '</td>
                        <td class="text-right font-bold">' . $this->formatReportNumber($fila['Total_precio_dolares'], true) . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="19">No se encontraron registros para mostrar.</td></tr>';
            }

            $html .= '</tbody></table>';

            $this->outputReportPdf(
                $mpdf,
                $css,
                $html,
                $filename !== '' ? $filename : 'Reporte_POA_' . date('Y_m_d') . '.pdf'
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando PDF: ' . $e->getMessage());
        }
    }
}
