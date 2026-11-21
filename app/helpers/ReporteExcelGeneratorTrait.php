<?php

namespace EquipoSiap\Siap\helpers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait ReporteExcelGeneratorTrait
{
    private function validateTemplate(string $templateName): void
    {
        $path = __DIR__ . '/../template/' . $templateName;
        if (!is_file($path)) {
            throw new \RuntimeException("Plantilla no encontrada: {$templateName}");
        }
    }

    private function loadReportTemplate(string $templateName): Spreadsheet
    {
        $this->validateTemplate($templateName);
        return IOFactory::load(__DIR__ . '/../template/' . $templateName);
    }

    private function downloadReportExcel(
        Spreadsheet $document,
        string $filename,
        bool $preCalculateFormulas = true
    ): void {
        $writer = new Xlsx($document);
        $writer->setPreCalculateFormulas($preCalculateFormulas);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function exportReqIndividualExcel(int $idDep, bool $incluirNoEnviados = false, ?int $anio = null): void
    {
        $anio ??= (int)$this->getActiveFiscalYear();
        $data = $this->executeGetReqReport($idDep, $incluirNoEnviados, $anio);
        $context = $this->getReqReportContext($idDep, $incluirNoEnviados, $anio);
        $context['estado'] = $incluirNoEnviados ? 'No enviados' : 'Enviados';
        $this->executeGetXlxsReqReport(
            $data,
            'TOTAL_Dependencia.xlsx',
            '',
            $context,
            $this->buildIndividualReportFilename($context) . '.xlsx'
        );
    }

    public function exportReqGlobalExcel(string $modo = 'consolidado', ?int $anio = null): void
    {
        $anio ??= (int)$this->getActiveFiscalYear();
        $data = $this->executeGetReqReport('', false, $anio);
        if ($modo === 'consolidado') {
            $year = (string)$anio;
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
        $this->executeGetXlsxGlobalResumen($data, $modo, (string)$anio);
    }

    public function exportProductosExcel(
        array $idPartidas = [],
        array $unidades = [],
        ?float $precioMin = null,
        ?float $precioMax = null
    ): void {
        $data = $this->executeGetProReport($idPartidas, $unidades, $precioMin, $precioMax);
        $this->executeGetXlsxProReport($data, $idPartidas);
    }

    public function exportDependenciasResponsableExcel(
        string $filtro = 'activas_con_responsable',
        bool $ocultarCoordinadores = false
    ): void
    {
        $data = $this->getDependenciasConResponsable($filtro, $ocultarCoordinadores);
        $this->executeGetXlsxDependenciasResponsable($data, $filtro);
    }

    private function executeGetXlsxGlobalResumen(array $data, string $modo, string $anio): void
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
        $sheet->setCellValue('A1', $title . ' - Año ' . $anio);
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
            'POA_Global_' . $modo . '_' . $anio . '.xlsx',
            false
        );
    }

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
                    [$descripcion, $uMedidaParseada] = $this->parseProductDescription((string)$fila['Descripcion'], 'UNIDAD');
                    $uMedida = $this->resolveUnidadFila($fila, $uMedidaParseada);

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

                    [$descripcion, $uMedidaParseada] = $this->parseProductDescription((string)$fila['Descripcion'], 'UNIDAD');
                    $uMedida = $this->resolveUnidadFila($fila, $uMedidaParseada);

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
}
