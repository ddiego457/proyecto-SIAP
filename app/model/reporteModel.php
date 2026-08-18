<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class reporteModel extends ConnectDB
{
    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

// antigua funcion que se encarga de "pintar" las opciones para la descarga --considero quitarlo
// Me gustan como se ven las opciones para la descarga, aunque ahora hay que adaptarlo al nuevo modelo
    public function getReportButtons(): array
    {
        return [
            [
                'code' => '401',
                'title' => 'Informe Partida 401',
                'description' => 'Personal y Honorarios',
            ],
            [
                'code' => '402',
                'title' => 'Informe Partida 402',
                'description' => 'Materiales e Insumos',
            ],
            [
                'code' => '403',
                'title' => 'Informe Partida 403',
                'description' => 'Servicios',
            ],
            [
                'code' => '404',
                'title' => 'Informe Partida 404',
                'description' => 'Bienes o Muebles',
            ],
            [
                'code' => '407',
                'title' => 'Informe Partida 407',
                'description' => 'Ayudas y Becas',
            ],
            [
                'code' => 'dependencia_individual',
                'title' => 'informe de dependencia',
                'description' => 'Requerimiento individual por cada dependencia',
            ],
            [
                'code' => 'requerimiento_individual',
                'title' => 'Informe Requerimiento Individual',
                'description' => 'Reporte de requerimiento individual',
            ],
            [
                'code' => 'anteproyecto_global',
                'title' => 'Informe Anteproyecto Global',
                'description' => 'Consolidado de anteproyecto global',
            ],
        ];
    }

    public function getReqReport($id = ''){
        $data = $this->executeGetReqReport($id);
        $result = $this->executeGetXlxsReqReport($data);
        return $result;        
    }
    public function getProReport($cond = ''){
        $data = $this->executeGetProReport();
    }

    public function getExcelReport(array $report){
        $result = $this->executeGetXlxsReqReport($report);
        return $result;
    }

    //executeGetReport es una funcion que obtiene el tipo de consulta a ejecutar mas una/niguna condición 
    private function executeGetReqReport($id = ''){ 
    $query = "";
    
        // consulta para una dependencia en especifico, funciona para la creacion del archivo excel actual
        if (!empty($id)) {
            $query = "SELECT 
                d.nom_dep AS dependencia,
                p.cod_partida AS partida,
                pro.nom_prod AS producto,
                SUM(CASE WHEN dr.mes = 1 THEN dr.cant_mes ELSE 0 END) AS Ene,
                SUM(CASE WHEN dr.mes = 2 THEN dr.cant_mes ELSE 0 END) AS Feb,
                SUM(CASE WHEN dr.mes = 3 THEN dr.cant_mes ELSE 0 END) AS Mar,
                SUM(CASE WHEN dr.mes = 4 THEN dr.cant_mes ELSE 0 END) AS Abr,
                SUM(CASE WHEN dr.mes = 5 THEN dr.cant_mes ELSE 0 END) AS May,
                SUM(CASE WHEN dr.mes = 6 THEN dr.cant_mes ELSE 0 END) AS Jun,
                SUM(CASE WHEN dr.mes = 7 THEN dr.cant_mes ELSE 0 END) AS Jul,
                SUM(CASE WHEN dr.mes = 8 THEN dr.cant_mes ELSE 0 END) AS Ago,
                SUM(CASE WHEN dr.mes = 9 THEN dr.cant_mes ELSE 0 END) AS Sep,
                SUM(CASE WHEN dr.mes = 10 THEN dr.cant_mes ELSE 0 END) AS Oct,
                SUM(CASE WHEN dr.mes = 11 THEN dr.cant_mes ELSE 0 END) AS Nov,
                SUM(CASE WHEN dr.mes = 12 THEN dr.cant_mes ELSE 0 END) AS Dic,
                SUM(dr.cant_mes) AS total_cantidad,
                pro.precio AS precio_unit_usd,
                (pro.precio * tb.tasa_bcv_usd) AS precio_unit_bs,
                (SUM(dr.cant_mes) * pro.precio) AS total_usd,
                (SUM(dr.cant_mes) * pro.precio * tb.tasa_bcv_usd) AS total_bs
            FROM detalle_req dr
            JOIN requerimientos r ON dr.id_req = r.id_req
            JOIN dependencias d ON r.id_dep = d.id_dep
            JOIN productos pro ON dr.id_prod = pro.id_prod
            JOIN partidas p ON pro.id_partida = p.id_partida
            JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
            JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
            WHERE r.estado = 1 
              AND r.estado_envio = 1 
              AND af.activo = 1
              AND d.id_dep = ? 
                     GROUP BY  d.nom_dep, p.cod_partida, pro.nom_prod, pro.precio 
                     ORDER BY p.cod_partida ASC;";
        }
        $query = 'SELECT p.cod_partida as codigo, prod.nom_prod as Descripcion,
        SUM(CASE WHEN dr.mes = 1 THEN dr.cant_mes ELSE 0 END) AS Ene,
        SUM(CASE WHEN dr.mes = 2 THEN dr.cant_mes ELSE 0 END) AS Feb,
        SUM(CASE WHEN dr.mes = 3 THEN dr.cant_mes ELSE 0 END) AS Mar,
        SUM(CASE WHEN dr.mes = 4 THEN dr.cant_mes ELSE 0 END) AS Abr,
        SUM(CASE WHEN dr.mes = 5 THEN dr.cant_mes ELSE 0 END) AS May,
        SUM(CASE WHEN dr.mes = 6 THEN dr.cant_mes ELSE 0 END) AS Jun,
        SUM(CASE WHEN dr.mes = 7 THEN dr.cant_mes ELSE 0 END) AS Jul,
        SUM(CASE WHEN dr.mes = 8 THEN dr.cant_mes ELSE 0 END) AS Ago,
        SUM(CASE WHEN dr.mes = 9 THEN dr.cant_mes ELSE 0 END) AS Sep,
        SUM(CASE WHEN dr.mes = 10 THEN dr.cant_mes ELSE 0 END) AS Oct,
        SUM(CASE WHEN dr.mes = 11 THEN dr.cant_mes ELSE 0 END) AS Nov,
        SUM(CASE WHEN dr.mes = 12 THEN dr.cant_mes ELSE 0 END) AS Dic,
        (prod.precio * tb.tasa_bcv_usd) as precio,
        prod.precio as precio_dolares,
        COALESCE(SUM(dr.cant_mes),0) as cantidad_Total,
        COALESCE((SUM(dr.cant_mes) * prod.precio * tb.tasa_bcv_usd), 0) as Total_precio,
        COALESCE((SUM(dr.cant_mes) * prod.precio), 0) as Total_precio_dolares
        FROM detalle_req as dr
        JOIN requerimientos as r ON dr.id_req = r.id_req
        JOIN productos AS prod ON dr.id_prod = prod.id_prod 
        JOIN partidas as p ON prod.id_partida = p.id_partida
        JOIN tasa_bcv as tb ON r.id_tasa = tb.id_tasa
        JOIN anio_fiscal as af ON r.id_aniof = af.id_aniof
        WHERE r.estado = 1
        AND r.estado_envio = 1
        AND af.activo = 1
        GROUP BY p.cod_partida , prod.nom_prod
        ORDER BY p.cod_partida ASC , prod.nom_prod ASC; 
        ';

    $stmt = $this->conex->prepare($query);
    if (!empty($id) && $id != '') {
        $stmt->bindValue(1, $id);
    }
    $stmt->execute();
    return $stmt->fetchAll();
    }


    private function executeGetProReport($cond = ''){
        $query = "SELECT p.cod_partida as partidas, pro.nom_prod as producto, pro.precio as precio FROM productos as pro
                JOIN partidas as p ON p.id_partida = pro.id_partida";
        if (!empty($cond) && $cond != ''){
            $query = $query . " WHERE p.cod_partida = ? ORDER BY pro.precio DESC";
        }
        $stmt = $this->conex->prepare($query);
        if (!empty($cond) && $cond != ''){
            $stmt->bindValue(1, $cond);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }


    private function executeGetXlxsReqReport(array $datos){
    // 2. Cargar la plantilla existente
    $rutaPlantilla = __DIR__ . '/../template/TOTAL_Todas_las_Dependencias.xlsx';
    if (!file_exists($rutaPlantilla)) {
        die("Error: No se encontró la plantilla.");
    }
    
    $documento = IOFactory::load($rutaPlantilla);

    // 3. Array para llevar el conteo de filas independientes por cada pestaña (Empiezan en la fila 21)
    $filasHojas = [
        '4,01' => 21,
        '4,02' => 21,
        '4,03' => 21,
        '4,04' => 21,
        '4,07' => 21
    ];

    if (!empty($datos)) {
        foreach ($datos as $fila) {
            // Normalizar el código de partida para que coincida con el nombre de las hojas ('4,01', '4,02', etc.)
            $partidaFormateada = str_replace('.', ',', (string)$fila['codigo']);
            
            // Si en la BD la partida está como '401', la convertimos a '4,01'
            if (strlen($partidaFormateada) === 3 && is_numeric($partidaFormateada)) {
                $partidaFormateada = substr($partidaFormateada, 0, 1) . ',' . substr($partidaFormateada, 1);
            }

            // Verificar si la hoja existe en el Excel
            if ($documento->sheetNameExists($partidaFormateada)) {
                $hoja = $documento->getSheetByName($partidaFormateada);
                $numFila = $filasHojas[$partidaFormateada];

                // Inyección de Datos
                $hoja->setCellValue('A' . $numFila, $fila['codigo']);
                $hoja->setCellValue('B' . $numFila, $fila['Descripcion']);
                $hoja->setCellValue('C' . $numFila, $fila['unidad_medida'] ?? 'UND'); // Ajustar si tienes este campo en BD
                $hoja->setCellValue('D' . $numFila, $fila['precio'] ?? 0);

                // Meses (Ene = Columna E, Feb = F, ..., Dic = P)
                $hoja->setCellValue('E' . $numFila, $fila['Ene']);
                $hoja->setCellValue('F' . $numFila, $fila['Feb']);
                $hoja->setCellValue('G' . $numFila, $fila['Mar']);
                $hoja->setCellValue('H' . $numFila, $fila['Abr']);
                $hoja->setCellValue('I' . $numFila, $fila['May']);
                $hoja->setCellValue('J' . $numFila, $fila['Jun']);
                $hoja->setCellValue('K' . $numFila, $fila['Jul']);
                $hoja->setCellValue('L' . $numFila, $fila['Ago']);
                $hoja->setCellValue('M' . $numFila, $fila['Sep']);
                $hoja->setCellValue('N' . $numFila, $fila['Oct']);
                $hoja->setCellValue('O' . $numFila, $fila['Nov']);
                $hoja->setCellValue('P' . $numFila, $fila['Dic']);

                // Totales
                $hoja->setCellValue('Q' . $numFila, $fila['cantidad_Total']);
                $hoja->setCellValue('R' . $numFila, $fila['Total_precio']);

                // Incrementar el contador de fila para esa pestaña en específico
                $filasHojas[$partidaFormateada]++;
            }
        }
    }

    // 4. Descargar el archivo procesado
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Consolidado_POA_' . date('Y') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
    $writer->save('php://output');
    exit;
}
    
}

