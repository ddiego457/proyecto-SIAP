<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class reporteModel extends ConnectDB
{
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
                'description' => 'Partidas 401, 402, 403, 404, 407 con precios y stock',
            ],
            [
                'code' => 'dependencias_responsable',
                'title' => 'Dependencias Activas + Responsable',
                'description' => 'Listado de dependencias con su responsable asignado',
            ],
            [
                'code' => 'req_individual',
                'title' => 'Requerimientos Individuales por Dependencia',
                'description' => 'POA detallado por dependencia (requiere selección)',
                'requiresDep' => true,
            ],
            [
                'code' => 'req_global',
                'title' => 'Requerimientos Global (Consolidado)',
                'description' => 'Consolidado general de todos los requerimientos enviados',
            ],
        ];
    }

    public function getDependenciasConResponsable(): array
    {
        try {
            $query = "
                SELECT 
                    d.id_dep,
                    d.nom_dep AS dependencia,
                    COALESCE(r.nom_rep, 'SIN ASIGNAR') AS responsable,
                    COALESCE(ro.descripcion, 'SIN CARGO') AS cargo,
                    COALESCE(t.telefono, 'SIN TELÉFONO') AS telefono,
                    CASE WHEN d.estado = 1 THEN 'ACTIVA' ELSE 'INACTIVA' END AS estado_dependencia,
                    CASE WHEN r.estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END AS estado_responsable
                FROM dependencias d
                LEFT JOIN cargo c ON c.id_dep = d.id_dep AND c.estado = 1
                LEFT JOIN responsables r ON r.id_responsable = c.id_responsable AND r.estado = 1
                LEFT JOIN roles ro ON ro.id_rol = r.id_rol
                LEFT JOIN telefonos t ON t.id_proveedor = r.id_responsable AND t.estado = 1
                WHERE d.estado = 1
                ORDER BY d.nom_dep ASC
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando dependencias con responsable: ' . $e->getMessage());
        }
    }

    public function getDependenciasParaSelect(): array
    {
        try {
            $query = "
                SELECT d.id_dep, d.nom_dep
                FROM dependencias d
                WHERE d.estado = 1
                AND EXISTS (
                    SELECT 1 FROM requerimientos r
                    WHERE r.id_dep = d.id_dep
                      AND r.estado = 1
                      AND r.estado_envio = 1
                )
                ORDER BY d.nom_dep ASC
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error cargando dependencias para select: ' . $e->getMessage());
        }
    }

    public function exportReqIndividualExcel(int $idDep): void
    {
        $this->validateTemplate('TOTAL_Dependencia.xlsx');
        $data = $this->executeGetReqReport($idDep);
        $this->executeGetXlxsReqReport($data, 'TOTAL_Dependencia.xlsx', 'POA_Dependencia_' . $idDep . '_');
    }

    public function exportReqIndividualPDF(int $idDep, string $titulo = ''): void
    {
        $data = $this->executeGetReqReport($idDep);
        $depName = $this->getDependenciaNombre($idDep);
        $tituloFinal = $titulo ?: 'POA Dependencia - ' . $depName;
        $this->executeGetPdfReqReport($data, $tituloFinal);
    }

    public function exportReqGlobalExcel(): void
    {
        $this->validateTemplate('TOTAL_Todas_las_Dependencias.xlsx');
        $data = $this->executeGetReqReport();
        $this->executeGetXlxsReqReport($data, 'TOTAL_Todas_las_Dependencias.xlsx', 'POA_Global_');
    }

    public function exportReqGlobalPDF(): void
    {
        $data = $this->executeGetReqReport();
        $this->executeGetPdfReqReport($data, 'Consolidado POA Global');
    }

    public function exportProductosExcel(): void
    {
        $this->validateTemplate('TOTAL_Productos.xlsx');
        $data = $this->executeGetProReport();
        $this->executeGetXlsxProReport($data);
    }

    public function exportDependenciasResponsableExcel(): void
    {
        $this->validateTemplate('Dependencias_Responsable.xlsx');
        $data = $this->getDependenciasConResponsable();
        $this->executeGetXlsxDependenciasResponsable($data);
    }

    public function exportProductosPDF(): void
    {
        $data = $this->executeGetProReport();
        $this->executeGetPdfProReport($data);
    }

    public function exportDependenciasResponsablePDF(): void
    {
        $data = $this->getDependenciasConResponsable();
        $this->executeGetPdfDependenciasResponsable($data);
    }

    private function validateTemplate(string $filename): void
    {
        $path = __DIR__ . '/../template/' . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Plantilla no encontrada: {$filename} (ruta: {$path})");
        }
    }

    private function getDependenciaNombre(int $idDep): string
    {
        try {
            $stmt = $this->conex->prepare("SELECT nom_dep FROM dependencias WHERE id_dep = ?");
            $stmt->execute([$idDep]);
            return $stmt->fetchColumn() ?: "ID {$idDep}";
        } catch (\PDOException $e) {
            return "ID {$idDep}";
        }
    }

    // ===================== CONSULTAS BASE =====================

    private function executeGetReqReport($id = '')
    {
        $params = [];
        if (!empty($id)) {
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
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
                    WHERE r.estado = 1 
                      AND r.estado_envio = 1 
                      AND af.activo = 1
                      AND r.id_dep = ?
                ) AS req_valido ON prod.id_prod = req_valido.id_prod
                GROUP BY 
                    p.cod_partida, 
                    prod.id_prod, 
                    prod.nom_prod, 
                    prod.precio
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_prod ASC";
            $params = [$id];
        } else {
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
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
                    WHERE r.estado = 1 
                      AND r.estado_envio = 1 
                      AND af.activo = 1
                ) AS req_valido ON prod.id_prod = req_valido.id_prod
                GROUP BY 
                    p.cod_partida, 
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

    private function executeGetProReport()
    {
        try {
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
                    prod.nom_prod AS Descripcion,
                    prod.precio AS precio_dolares,
                    COALESCE(prod.precio * tb.tasa_bcv_usd, prod.precio) AS precio_bolivares
                FROM productos prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN tasa_bcv tb ON tb.estado = 1
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_prod ASC";
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando productos: ' . $e->getMessage());
        }
    }

    // ===================== GENERADORES EXCEL (con try-catch) =====================

    private function executeGetXlxsReqReport(array $datos, string $plantilla, string $prefijo): void
    {
        try {
            $rutaPlantilla = __DIR__ . '/../template/' . $plantilla;
            if (!file_exists($rutaPlantilla)) {
                throw new \RuntimeException("Plantilla no encontrada: {$plantilla}");
            }

            $documento = IOFactory::load($rutaPlantilla);

            $filasHojas = [
                '4,01' => 21, '4,02' => 21, '4,03' => 21,
                '4,04' => 21, '4,07' => 21
            ];

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    $partidaFormateada = str_replace('.', ',', (string)$fila['codigo']);
                    $descr = $fila['Descripcion'];

                    if (strpos($descr, '|') !== false) {
                        $posicion = strpos($descr, '|') + 1;
                        $texto = substr($descr, $posicion);
                        $uMedida = substr($texto, 0, strpos($texto, '|'));
                        $descripcion = trim(str_replace('|' . $uMedida . '|', '', $descr));
                    } else {
                        $uMedida = 'UNIDAD';
                        $descripcion = $descr;
                    }

                    if (strlen($partidaFormateada) === 3 && is_numeric($partidaFormateada)) {
                        $partidaFormateada = substr($partidaFormateada, 0, 1) . ',' . substr($partidaFormateada, 1);
                    }

                    if ($documento->sheetNameExists($partidaFormateada)) {
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

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $prefijo . date('Y') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando Excel: ' . $e->getMessage());
        }
    }

    private function executeGetXlsxProReport(array $datos): void
    {
        try {
            $rutaPlantilla = __DIR__ . '/../template/TOTAL_Productos.xlsx';
            if (!file_exists($rutaPlantilla)) {
                throw new \RuntimeException('Plantilla no encontrada: TOTAL_Productos.xlsx');
            }

            $documento = IOFactory::load($rutaPlantilla);

            $filasHojas = [
                '4,01' => 21, '4,02' => 21, '4,03' => 21,
                '4,04' => 21, '4,07' => 21
            ];

            if (!empty($datos)) {
                foreach ($datos as $fila) {
                    $partidaFormateada = str_replace('.', ',', (string)$fila['codigo']);
                    if (strlen($partidaFormateada) === 3 && is_numeric($partidaFormateada)) {
                        $partidaFormateada = substr($partidaFormateada, 0, 1) . ',' . substr($partidaFormateada, 1);
                    }

                    $descr = $fila['Descripcion'];
                    if (strpos($descr, '|') !== false) {
                        $posicion = strpos($descr, '|') + 1;
                        $texto = substr($descr, $posicion);
                        $uMedida = substr($texto, 0, strpos($texto, '|'));
                        $descripcion = trim(str_replace('|' . $uMedida . '|', '', $descr));
                    } else {
                        $uMedida = 'UNIDAD';
                        $descripcion = $descr;
                    }

                    if ($documento->sheetNameExists($partidaFormateada)) {
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

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Productos_' . date('Y') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando Excel productos: ' . $e->getMessage());
        }
    }

    private function executeGetXlsxDependenciasResponsable(array $datos): void
    {
        try {
            $rutaPlantilla = __DIR__ . '/../template/Dependencias_Responsable.xlsx';
            if (!file_exists($rutaPlantilla)) {
                throw new \RuntimeException('Plantilla no encontrada: Dependencias_Responsable.xlsx');
            }

            $documento = IOFactory::load($rutaPlantilla);
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

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Dependencias_Responsable_' . date('Y') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando Excel dependencias: ' . $e->getMessage());
        }
    }

    // ===================== GENERADOR PDF (con try-catch) =====================

    private function executeGetPdfProReport(array $datos): void
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'LETTER-L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 22,
                'margin_bottom' => 12,
                'margin_header' => 5,
                'margin_footer' => 5,
            ]);

            $mpdf->SetHTMLHeader('
                <div style="text-align: center; font-weight: bold; font-size: 10pt; font-family: sans-serif; border-bottom: 1px solid #000; padding-bottom: 4px;">
                    REPUBLICA BOLIVARIANA DE VENEZUELA<br>
                    CAT&Aacute;LOGO DE PRODUCTOS Y SERVICIOS - A&Ntilde;O ' . date('Y') . '
                </div>
            ');

            $mpdf->SetHTMLFooter('
                <table width="100%" style="font-size: 7pt; font-family: sans-serif; border-top: 1px solid #ccc;">
                    <tr>
                        <td width="33%">Fecha de emisi&oacute;n: ' . date('d/m/Y h:i A') . '</td>
                        <td width="33%" align="center">P&aacute;gina {PAGENO} de {nbpg}</td>
                        <td width="33%" align="right">Sistema SIAP</td>
                    </tr>
                </table>
            ');

            $css = '
                body { font-family: sans-serif; font-size: 7pt; }
                .tabla-pro { width: 100%; border-collapse: collapse; margin-top: 5px; }
                .tabla-pro th { background-color: #1a365d; color: #ffffff; border: 0.5pt solid #000; padding: 3px 1px; font-size: 6.5pt; text-align: center; }
                .tabla-pro td { border: 0.5pt solid #999; padding: 3px 2px; font-size: 6.5pt; text-align: center; }
                .tabla-pro tr:nth-child(even) { background-color: #f8fafc; }
                .text-left { text-align: left !important; }
                .text-right { text-align: right !important; }
                .font-bold { font-weight: bold; }
            ';

            $fmt = function($valor, $esMonto = false) {
                if (empty($valor) || $valor == 0) return '';
                return $esMonto ? number_format($valor, 2, ',', '.') : number_format($valor, 0, ',', '.');
            };

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
                    $descr = $fila['Descripcion'];
                    if (strpos($descr, '|') !== false) {
                        $posicion = strpos($descr, '|') + 1;
                        $texto = substr($descr, $posicion);
                        $uMedida = substr($texto, 0, strpos($texto, '|'));
                        $descripcion = trim(str_replace('|' . $uMedida . '|', '', $descr));
                    } else {
                        $uMedida = 'UND';
                        $descripcion = $descr;
                    }

                    $partidaCode = $fila['codigo'];
                    if (strlen($partidaCode) === 3 && is_numeric($partidaCode)) {
                        $partidaCode = substr($partidaCode, 0, 1) . ',' . substr($partidaCode, 1);
                    }

                    $html .= '<tr>
                        <td class="font-bold">' . htmlspecialchars($partidaCode) . '</td>
                        <td class="text-left">' . htmlspecialchars($descripcion) . '</td>
                        <td>' . htmlspecialchars($uMedida) . '</td>
                        <td class="text-right">' . $fmt($fila['precio_bolivares'], true) . '</td>
                        <td class="text-right">' . $fmt($fila['precio_dolares'], true) . '</td>
                        <td class="font-bold">' . htmlspecialchars($fila['codigo']) . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="6">No se encontraron productos para mostrar.</td></tr>';
            }

            $html .= '</tbody></table>';

            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->Output('Catalogo_Productos_' . date('Y_m_d') . '.pdf', 'I');
            exit;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando PDF productos: ' . $e->getMessage());
        }
    }

    private function executeGetPdfDependenciasResponsable(array $datos): void
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'LETTER-L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 22,
                'margin_bottom' => 12,
                'margin_header' => 5,
                'margin_footer' => 5,
            ]);

            $mpdf->SetHTMLHeader('
                <div style="text-align: center; font-weight: bold; font-size: 10pt; font-family: sans-serif; border-bottom: 1px solid #000; padding-bottom: 4px;">
                    REPUBLICA BOLIVARIANA DE VENEZUELA<br>
                    DEPENDENCIAS ACTIVAS CON RESPONSABLE - A&Ntilde;O ' . date('Y') . '
                </div>
            ');

            $mpdf->SetHTMLFooter('
                <table width="100%" style="font-size: 7pt; font-family: sans-serif; border-top: 1px solid #ccc;">
                    <tr>
                        <td width="33%">Fecha de emisi&oacute;n: ' . date('d/m/Y h:i A') . '</td>
                        <td width="33%" align="center">P&aacute;gina {PAGENO} de {nbpg}</td>
                        <td width="33%" align="right">Sistema SIAP</td>
                    </tr>
                </table>
            ');

            $css = '
                body { font-family: sans-serif; font-size: 7pt; }
                .tabla-dep { width: 100%; border-collapse: collapse; margin-top: 5px; }
                .tabla-dep th { background-color: #1a365d; color: #ffffff; border: 0.5pt solid #000; padding: 3px 1px; font-size: 6.5pt; text-align: center; }
                .tabla-dep td { border: 0.5pt solid #999; padding: 3px 2px; font-size: 6.5pt; text-align: center; }
                .tabla-dep tr:nth-child(even) { background-color: #f8fafc; }
                .text-left { text-align: left !important; }
                .text-right { text-align: right !important; }
                .font-bold { font-weight: bold; }
            ';

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

            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->Output('Dependencias_Responsable_' . date('Y_m_d') . '.pdf', 'I');
            exit;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando PDF dependencias: ' . $e->getMessage());
        }
    }

    private function executeGetPdfReqReport(array $datos, string $tituloReporte = 'Consolidado POA'): void
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'LETTER-L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 22,
                'margin_bottom' => 12,
                'margin_header' => 5,
                'margin_footer' => 5,
            ]);

            $mpdf->SetHTMLHeader('
                <div style="text-align: center; font-weight: bold; font-size: 10pt; font-family: sans-serif; border-bottom: 1px solid #000; padding-bottom: 4px;">
                    REPUBLICA BOLIVARIANA DE VENEZUELA<br>
                    ' . mb_strtoupper($tituloReporte, 'UTF-8') . ' - AÑO ' . date('Y') . '
                </div>
            ');

            $mpdf->SetHTMLFooter('
                <table width="100%" style="font-size: 7pt; font-family: sans-serif; border-top: 1px solid #ccc;">
                    <tr>
                        <td width="33%">Fecha de emisi&oacute;n: ' . date('d/m/Y h:i A') . '</td>
                        <td width="33%" align="center">P&aacute;gina {PAGENO} de {nbpg}</td>
                        <td width="33%" align="right">Sistema SIAP</td>
                    </tr>
                </table>
            ');

            $css = '
                body { font-family: sans-serif; font-size: 7pt; }
                .tabla-poa { width: 100%; border-collapse: collapse; margin-top: 5px; }
                .tabla-poa th { background-color: #1a365d; color: #ffffff; border: 0.5pt solid #000; padding: 3px 1px; font-size: 6.5pt; text-align: center; }
                .tabla-poa td { border: 0.5pt solid #999; padding: 3px 2px; font-size: 6.5pt; text-align: center; }
                .tabla-poa tr:nth-child(even) { background-color: #f8fafc; }
                .text-left { text-align: left !important; }
                .text-right { text-align: right !important; }
                .font-bold { font-weight: bold; }
            ';

            $fmt = function($valor, $esMonto = false) {
                if (empty($valor) || $valor == 0) return '';
                return $esMonto ? number_format($valor, 2, ',', '.') : number_format($valor, 0, ',', '.');
            };

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
                    $descr = $fila['Descripcion'];
                    if (strpos($descr, '|') !== false) {
                        $posicion = strpos($descr, '|') + 1;
                        $texto = substr($descr, $posicion);
                        $uMedida = substr($texto, 0, strpos($texto, '|'));
                        $descripcion = trim(str_replace('|' . $uMedida . '|', '', $descr));
                    } else {
                        $uMedida = 'UND';
                        $descripcion = $descr;
                    }

                    $html .= '<tr>
                        <td class="font-bold">' . htmlspecialchars($fila['codigo']) . '</td>
                        <td class="text-left">' . htmlspecialchars($descripcion) . '</td>
                        <td>' . htmlspecialchars($uMedida) . '</td>
                        <td class="text-right">' . $fmt($fila['precio'] ?? $fila['precio_dolares'], true) . '</td>
                        <td>' . $fmt($fila['Ene']) . '</td>
                        <td>' . $fmt($fila['Feb']) . '</td>
                        <td>' . $fmt($fila['Mar']) . '</td>
                        <td>' . $fmt($fila['Abr']) . '</td>
                        <td>' . $fmt($fila['May']) . '</td>
                        <td>' . $fmt($fila['Jun']) . '</td>
                        <td>' . $fmt($fila['Jul']) . '</td>
                        <td>' . $fmt($fila['Ago']) . '</td>
                        <td>' . $fmt($fila['Sep']) . '</td>
                        <td>' . $fmt($fila['Oct']) . '</td>
                        <td>' . $fmt($fila['Nov']) . '</td>
                        <td>' . $fmt($fila['Dic']) . '</td>
                        <td class="font-bold">' . $fmt($fila['cantidad_Total']) . '</td>
                        <td class="text-right font-bold">' . $fmt($fila['Total_precio'], true) . '</td>
                        <td class="text-right font-bold">' . $fmt($fila['Total_precio_dolares'], true) . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="19">No se encontraron registros para mostrar.</td></tr>';
            }

            $html .= '</tbody></table>';

            $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->Output('Reporte_POA_' . date('Y_m_d') . '.pdf', 'I');
            exit;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error generando PDF: ' . $e->getMessage());
        }
    }
}