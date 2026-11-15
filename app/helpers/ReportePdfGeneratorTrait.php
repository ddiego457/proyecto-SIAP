<?php

namespace EquipoSiap\Siap\helpers;

use Mpdf\Mpdf;

trait ReportePdfGeneratorTrait
{
    private function createReportPdf(string $title, ?string $year = null): Mpdf
    {
        ini_set('pcre.backtrack_limit', '10000000');
        $year = $year ?? date('Y');

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

        $safeTitle = htmlspecialchars(mb_strtoupper($title, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $mpdf->SetHTMLHeader(
            '<div style="text-align:center;font-weight:bold;font-size:10pt;font-family:sans-serif;border-bottom:1px solid #000;padding-bottom:4px;">'
            . 'REPUBLICA BOLIVARIANA DE VENEZUELA<br>' . $safeTitle . ' - A&Ntilde;O ' . htmlspecialchars($year, ENT_QUOTES, 'UTF-8')
            . '</div>'
        );
        $mpdf->SetHTMLFooter(
            '<table width="100%" style="font-size:7pt;font-family:sans-serif;border-top:1px solid #ccc;">'
            . '<tr><td width="33%">Fecha de emisi&oacute;n: ' . date('d/m/Y h:i A') . '</td>'
            . '<td width="33%" align="center">P&aacute;gina {PAGENO} de {nbpg}</td>'
            . '<td width="33%" align="right">Sistema SIAP</td></tr></table>'
        );

        return $mpdf;
    }

    private function outputReportPdf(Mpdf $mpdf, string $css, string $html, string $filename): void
    {
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output($filename, 'I');
        exit;
    }

    private function reportTableCss(string $tableClass): string
    {
        return '
            body { font-family: sans-serif; font-size: 7pt; }
            .' . $tableClass . ' { width: 100%; border-collapse: collapse; margin-top: 5px; }
            .' . $tableClass . ' th { background-color: #1a365d; color: #ffffff; border: 0.5pt solid #000; padding: 3px 1px; font-size: 6.5pt; text-align: center; }
            .' . $tableClass . ' td { border: 0.5pt solid #999; padding: 3px 2px; font-size: 6.5pt; text-align: center; }
            .' . $tableClass . ' tr:nth-child(even) { background-color: #f8fafc; }
            .text-left { text-align: left !important; }
            .text-right { text-align: right !important; }
            .font-bold { font-weight: bold; }
        ';
    }

    public function exportReqIndividualPDF(int $idDep, bool $incluirNoEnviados = false): void
    {
        $data = $this->executeGetReqReport($idDep, $incluirNoEnviados);
        $context = $this->getReqReportContext($idDep, $incluirNoEnviados);
        $context['estado'] = $incluirNoEnviados ? 'No enviados' : 'Enviados';
        $title = $this->buildIndividualReportTitle($context);
        $filename = $this->buildIndividualReportFilename($context) . '.pdf';
        $this->executeGetPdfReqReport($data, $title, $filename, (string)$context['anio']);
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

    public function exportProductosPDF(array $idPartidas = []): void
    {
        $data = $this->executeGetProReport($idPartidas);
        $this->executeGetPdfProReport($data, $idPartidas);
    }

    public function exportDependenciasResponsablePDF(
        string $filtro = 'activas_con_responsable',
        bool $ocultarCoordinadores = false
    ): void
    {
        $data = $this->getDependenciasConResponsable($filtro, $ocultarCoordinadores);
        $this->executeGetPdfDependenciasResponsable($data, $filtro);
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
