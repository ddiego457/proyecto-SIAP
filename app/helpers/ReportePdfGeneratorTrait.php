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
}
