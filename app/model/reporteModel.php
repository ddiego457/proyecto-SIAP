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

    public function getReqReport($id = '', $plantilla, $prefijo){
        $data = $this->executeGetReqReport($id);
        $result = $this->executeGetXlxsReqReport($data,$plantilla,$prefijo);
        return $result;        
    }
    public function getProReport(){
        $data = $this->executeGetProReport();
        $result = $this->executeGetXlsxProReport($data);
        return $result;
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
        p.cod_partida AS codigo,
        prod.nom_prod AS Descripcion,
        prod.precio AS precio,
        COALESCE(SUM(CASE WHEN req_valido.mes = 1 THEN req_valido.cant_mes ELSE '' END), '') AS Ene,
        COALESCE(SUM(CASE WHEN req_valido.mes = 2 THEN req_valido.cant_mes ELSE '' END), '') AS Feb,
        COALESCE(SUM(CASE WHEN req_valido.mes = 3 THEN req_valido.cant_mes ELSE '' END), '') AS Mar,
        COALESCE(SUM(CASE WHEN req_valido.mes = 4 THEN req_valido.cant_mes ELSE '' END), '') AS Abr,
        COALESCE(SUM(CASE WHEN req_valido.mes = 5 THEN req_valido.cant_mes ELSE '' END), '') AS May,
        COALESCE(SUM(CASE WHEN req_valido.mes = 6 THEN req_valido.cant_mes ELSE '' END), '') AS Jun,
        COALESCE(SUM(CASE WHEN req_valido.mes = 7 THEN req_valido.cant_mes ELSE '' END), '') AS Jul,
        COALESCE(SUM(CASE WHEN req_valido.mes = 8 THEN req_valido.cant_mes ELSE '' END), '') AS Ago,
        COALESCE(SUM(CASE WHEN req_valido.mes = 9 THEN req_valido.cant_mes ELSE '' END), '') AS Sep,
        COALESCE(SUM(CASE WHEN req_valido.mes = 10 THEN req_valido.cant_mes ELSE '' END), '') AS Oct,
        COALESCE(SUM(CASE WHEN req_valido.mes = 11 THEN req_valido.cant_mes ELSE '' END), '') AS Nov,
        COALESCE(SUM(CASE WHEN req_valido.mes = 12 THEN req_valido.cant_mes ELSE '' END), '') AS Dic,
        COALESCE(SUM(req_valido.cant_mes), '') AS cantidad_Total,
        COALESCE(SUM(req_valido.cant_mes * prod.precio), '') AS Total_precio_dolares,
        COALESCE(SUM(req_valido.cant_mes * prod.precio * req_valido.tasa_bcv_usd), '') AS Total_precio
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
            prod.nom_prod ASC;";
        } 


        else{
        $query = "SELECT 
        p.cod_partida AS codigo,
        prod.nom_prod AS Descripcion,
        COALESCE(SUM(CASE WHEN req_valido.mes = 1 THEN req_valido.cant_mes ELSE '' END), '') AS Ene,
        COALESCE(SUM(CASE WHEN req_valido.mes = 2 THEN req_valido.cant_mes ELSE '' END), '') AS Feb,
        COALESCE(SUM(CASE WHEN req_valido.mes = 3 THEN req_valido.cant_mes ELSE '' END), '') AS Mar,
        COALESCE(SUM(CASE WHEN req_valido.mes = 4 THEN req_valido.cant_mes ELSE '' END), '') AS Abr,
        COALESCE(SUM(CASE WHEN req_valido.mes = 5 THEN req_valido.cant_mes ELSE '' END), '') AS May,
        COALESCE(SUM(CASE WHEN req_valido.mes = 6 THEN req_valido.cant_mes ELSE '' END), '') AS Jun,
        COALESCE(SUM(CASE WHEN req_valido.mes = 7 THEN req_valido.cant_mes ELSE '' END), '') AS Jul,
        COALESCE(SUM(CASE WHEN req_valido.mes = 8 THEN req_valido.cant_mes ELSE '' END), '') AS Ago,
        COALESCE(SUM(CASE WHEN req_valido.mes = 9 THEN req_valido.cant_mes ELSE '' END), '') AS Sep,
        COALESCE(SUM(CASE WHEN req_valido.mes = 10 THEN req_valido.cant_mes ELSE '' END), '') AS Oct,
        COALESCE(SUM(CASE WHEN req_valido.mes = 11 THEN req_valido.cant_mes ELSE '' END), '') AS Nov,
        COALESCE(SUM(CASE WHEN req_valido.mes = 12 THEN req_valido.cant_mes ELSE '' END), '') AS Dic,
        (prod.precio * req_valido.tasa_bcv_usd) as precio,
        prod.precio as precio_dolares,
        COALESCE((SUM(req_valido.cant_mes) * prod.precio), '') as Total_precio_dolares,
        COALESCE(SUM(req_valido.cant_mes), '') AS cantidad_Total,
        COALESCE(SUM(req_valido.cant_mes * prod.precio * req_valido.tasa_bcv_usd), '') AS Total_precio
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
        prod.nom_prod ASC;"; 
    }

    $stmt = $this->conex->prepare($query);
    if (!empty($id) && $id != '') {
        $stmt->bindValue(1, $id);
    }
    $stmt->execute();
    return $stmt->fetchAll();
    }


    private function executeGetProReport(){
        $query = "SELECT 
    p.cod_partida AS codigo,
    prod.nom_prod AS Descripcion,
    prod.precio AS precio_dolares,
    COALESCE(prod.precio * tb.tasa_bcv_usd, prod.precio) AS precio_bolivares
FROM productos prod
JOIN partidas p ON prod.id_partida = p.id_partida
LEFT JOIN tasa_bcv tb ON tb.estado = 1
ORDER BY 
    p.cod_partida ASC, 
    prod.nom_prod ASC;";
        $stmt = $this->conex->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    private function executeGetXlxsReqReport(array $datos,string $plantilla,string $prefijo){
    // 2. Cargar la plantilla existente
    $rutaPlantilla = __DIR__ . '/../template/' . $plantilla;
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
            
            $descr = $fila['Descripcion'];

            if (strpos($descr, '|') !== false) {
            $posicion = (strpos($descr, '|') + 1);
            $texto = substr($descr, $posicion);
            $uMedida = substr($texto, 0, -1);

            $descripcion = str_replace('|' . $uMedida . '|', '', $descr);
            }
            else{
                $uMedida = 'UNIDAD';
                $descripcion = $descr;
            }

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
                $hoja->setCellValue('B' . $numFila, $descripcion);
                $hoja->setCellValue('C' . $numFila, $uMedida); // Ajustar si tienes este campo en BD
                $hoja->setCellValue('D' . $numFila, $fila['precio'] ?? 0);

                $val = function($valor){
                    return (!empty($valor) && $valor > 0) ? $valor : '';
                };

                // Meses (Ene = Columna E, Feb = F, ..., Dic = P)
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

                // Totales
                $hoja->setCellValue('Q' . $numFila, $fila['cantidad_Total']);
                $hoja->setCellValue('R' . $numFila, $fila['Total_precio']);
                $hoja->setCellValue('S' . $numFila, $fila['precio_dolares']);
                $hoja->setCellValue('T' . $numFila, $fila['Total_precio_dolares']);

                // Incrementar el contador de fila para esa pestaña en específico
                $filasHojas[$partidaFormateada]++;
            }
        }
    }

    // 4. Descargar el archivo procesado
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' .$prefijo. '' . date('Y') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
    $writer->save('php://output');
    exit;
}


private function executeGetXlsxProReport(array $datos) {
    $rutaPlantilla = __DIR__ . '/../template/TOTAL_Productos.xlsx';
    if (!file_exists($rutaPlantilla)) {
        die("Error: No se encontró la plantilla en: " . $rutaPlantilla);
    }

    $documento = IOFactory::load($rutaPlantilla);

    // Contadores de fila por pestaña (inicio en fila 21)
    $filasHojas = [
        '4,01' => 21,
        '4,02' => 21,
        '4,03' => 21,
        '4,04' => 21,
        '4,07' => 21
    ];

    if (!empty($datos)) {
        foreach ($datos as $fila) {
            $partidaFormateada = str_replace('.', ',', (string)$fila['codigo']);
            if (strlen($partidaFormateada) === 3 && is_numeric($partidaFormateada)) {
                $partidaFormateada = substr($partidaFormateada, 0, 1) . ',' . substr($partidaFormateada, 1);
            }

            // Extracción de Unidad de Medida y Limpieza de Descripción
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

                // Llenado de las 5 columnas especificadas
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
}

    
}

