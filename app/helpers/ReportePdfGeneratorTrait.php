<?php

namespace EquipoSiap\Siap\helpers;

use Mpdf\Mpdf;

/**
 * Trait ReportePdfGeneratorTrait
 *
 * Encapsula la generación de archivos PDF para los reportes del módulo reporte.
 * Los métodos públicos preparan los datos y delegan en generatePdfFromData(),
 * que construye la tabla HTML y fuerza la descarga con mPDF.
 */
trait ReportePdfGeneratorTrait
{
    private function generatePdfFromData(array $data, string $htmlCss, string $filename, string $tituloFinal = ''): void
    {
        // Los reportes grandes superan el límite por defecto de PCRE (1.000.000)
        // que mPDF usa para analizar el HTML; se amplía antes de escribir.
        ini_set('pcre.backtrack_limit', '10000000');

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15,
            'default_font' => 'dejavu',
        ]);

        if ($htmlCss !== '') {
            $mpdf->WriteHTML($htmlCss, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        $html = '<h1>' . htmlspecialchars($tituloFinal) . '</h1>';

        if (!empty($data)) {
            $html .= $this->buildReportTableHtml($data);
        } else {
            $html .= '<p>No se encontraron registros.</p>';
        }

        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output($filename . '.pdf', 'I');
        exit;
    }

    private function buildReportTableHtml(array $data): string
    {
        $html = '<table class="tabla-poa">';
        $html .= '<thead><tr>';
        $html .= '<th width="4%">Part.</th><th width="18%">Descripción</th><th width="4%">U.M.</th>';
        $html .= '<th width="5%">P. U. ($)</th>';
        $html .= '<th width="3%">Ene</th><th width="3%">Feb</th><th width="3%">Mar</th>';
        $html .= '<th width="3%">Abr</th><th width="3%">May</th><th width="3%">Jun</th>';
        $html .= '<th width="3%">Jul</th><th width="3%">Ago</th><th width="3%">Sep</th>';
        $html .= '<th width="3%">Oct</th><th width="3%">Nov</th><th width="3%">Dic</th>';
        $html .= '<th width="4%">Total Cant.</th><th width="7%">Total (Bs.)</th><th width="7%">Total ($)</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($data as $fila) {
            $descr = $fila['Descripcion'] ?? '';
            if (strpos($descr, '|') !== false) {
                $posicion = strpos($descr, '|') + 1;
                $texto = substr($descr, $posicion);
                $uMedida = substr($texto, 0, strpos($texto, '|'));
                $descripcion = trim(str_replace('|' . $uMedida . '|', '', $descr));
            } else {
                $uMedida = 'UND';
                $descripcion = $descr;
            }

            $html .= '<tr>';
            $html .= '<td class="font-bold">' . htmlspecialchars($fila['codigo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="text-left">' . htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($uMedida, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="text-right">' . $this->formatNumber($fila['precio'] ?? ($fila['precio_dolares'] ?? 0), true) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Ene'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Feb'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Mar'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Abr'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['May'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Jun'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Jul'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Ago'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Sep'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Oct'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Nov'] ?? 0) . '</td>';
            $html .= '<td>' . $this->formatNumber($fila['Dic'] ?? 0) . '</td>';

            $html .= '<td class="font-bold">' . $this->formatNumber($fila['cantidad_Total'] ?? 0) . '</td>';
            $html .= '<td class="text-right font-bold">' . $this->formatNumber($fila['Total_precio'] ?? 0, true) . '</td>';
            $html .= '<td class="text-right font-bold">' . $this->formatNumber($fila['Total_precio_dolares'] ?? 0, true) . '</td>';

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    public function exportProductosPDF(): void
    {
        $data = $this->executeGetProReport();
        $css = 'table.tabla-poa { width: 100%; border-collapse: collapse; }';
        $this->generatePdfFromData($data, $css, 'Reporte_Productos_Por_Partida', 'Productos por Partida Presupuestaria');
    }

    public function exportDependenciasResponsablePDF(): void
    {
        $data = $this->getDependenciasConResponsable();
        $css = 'table.tabla-poa { width: 100%; border-collapse: collapse; }';
        $this->generatePdfFromData($data, $css, 'Reporte_Dependencias_Responsable', 'Dependencias Activas y Responsables');
    }

    public function exportReqIndividualPDF(int $idDep): void
    {
        $data = $this->executeGetReqReport($idDep);
        $css = 'table.tabla-poa { width: 100%; border-collapse: collapse; }';
        $this->generatePdfFromData($data, $css, 'Reporte_Requerimientos_Individuales', "Requerimientos Individuales - Dependencia $idDep");
    }

    public function exportReqGlobalPDF(): void
    {
        $data = $this->executeGetReqReport();
        $css = 'table.tabla-poa { width: 100%; border-collapse: collapse; }';
        $this->generatePdfFromData($data, $css, 'Reporte_Requerimientos_Global', 'Requerimientos Globales (Consolidado)');
    }

    private function formatNumber(float $valor, bool $esMonto = false): string
    {
        return $esMonto ? number_format($valor, 2, ',', '.') : number_format($valor, 0, ',', '.');
    }
}
