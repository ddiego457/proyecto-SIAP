<?php

namespace EquipoSiap\Siap\helpers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Trait ReporteExcelGeneratorTrait
 *
 * Encapsula toda la generación de archivos Excel para los reportes del módulo reporte.
 * Esta clase solo se encarga de escribir datos en una plantilla y forzar la descarga.
 *
 * Uso:
 *  - El modelo llama a los métodos públicos.
 *  - Los métodos privados generan el Excel y terminan la ejecución.
 */
trait ReporteExcelGeneratorTrait
{
    /**
     * Valida que la plantilla Excel exista.
     */
    private function validateTemplate(string $templateName): void
    {
        $ruta = __DIR__ . '/../template/' . $templateName;
        if (!is_file($ruta)) {
            throw new \RuntimeException("La plantilla Excel '{$templateName}' no fue encontrada.");
        }
    }

    /**
     * Genera un archivo Excel desde una plantilla y datos proporcionados.
     *
     * @param array $data Datos preparados para la hoja
     * @param string $template Nombre del archivo de plantilla
     * @param string $filename Nombre base del archivo a descargar
     * @param string $tituloFinal Título del reporte (opcional)
     */
    private function generateExcelFromTemplate(
        array $data,
        string $template,
        string $filename,
        string $tituloFinal = ''
    ): void {
        $this->validateTemplate($template);

        $rutaPlantilla = __DIR__ . '/../template/' . $template;
        $spreadsheet = IOFactory::load($rutaPlantilla);

        $hoja = $spreadsheet->getActiveSheet();

        // Limpiar el contenido previo de la hoja para evitar duplicados
        // (solo valores; conserva el formato de la plantilla)
        foreach ($hoja->getRowIterator() as $fila) {
            foreach ($fila->getCellIterator() as $celda) {
                $celda->setValue(null);
            }
        }

        // Escribir datos en la plantilla (esto se adapta según la estructura de cada plantilla)
        foreach ($data as $key => $row) {
            $rowNum = $key + 1;
            foreach ($row as $col => $value) {
                $hoja->setCellValue("{$col}{$rowNum}", $value);
            }
        }

        // Título del reporte (si se proporciona)
        if ($tituloFinal !== '') {
            $hoja->setCellValue('A1', $tituloFinal);
        }

        $writer = new Xlsx($spreadsheet);

        // Forzar descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: must-revalidate');
        header('Expires: 0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Genera un archivo Excel para el reporte "Productos por Partida".
     */
    public function exportProductosExcel(): void
    {
        $data = $this->executeGetProReport();
        $this->generateExcelFromTemplate(
            $data,
            'TOTAL_Productos.xlsx',
            'Reporte_Productos_Por_Partida',
            'Productos por Partida Presupuestaria'
        );
    }

    /**
     * Genera un archivo Excel para el reporte "Dependencias con Responsable".
     */
    public function exportDependenciasResponsableExcel(): void
    {
        $data = $this->getDependenciasConResponsable();
        $this->generateExcelFromTemplate(
            $data,
            'Dependencias_Responsable.xlsx',
            'Reporte_Dependencias_Responsable',
            'Dependencias Activas y Responsables'
        );
    }

    /**
     * Genera un archivo Excel para el reporte "Requerimientos Individuales".
     */
    public function exportReqIndividualExcel(int $idDep): void
    {
        $data = $this->executeGetReqReport($idDep);
        $this->generateExcelFromTemplate(
            $data,
            'TOTAL_Dependencia.xlsx',
            'Reporte_Requerimientos_Individuales',
            "Requerimientos Individuales - Dependencia $idDep"
        );
    }

    /**
     * Genera un archivo Excel para el reporte "Requerimientos Global".
     */
    public function exportReqGlobalExcel(): void
    {
        $data = $this->executeGetReqReport();
        $this->generateExcelFromTemplate(
            $data,
            'TOTAL_Todas_las_Dependencias.xlsx',
            'Reporte_Requerimientos_Global',
            'Requerimientos Globales (Consolidado)'
        );
    }
}
