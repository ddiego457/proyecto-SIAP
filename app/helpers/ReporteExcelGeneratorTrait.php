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
}
