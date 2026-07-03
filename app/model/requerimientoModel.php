<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use DateTime;
class requerimientoModel extends ConnectDB{
private $conex;

public function __construct()
{
    parent::__construct();
    $this->conex = $this->getConnection();
}

public function obtenerTodasLasDependencias() {
    $stmt = $this->conex->query("SELECT id_dep, nom_dep FROM dependencias");
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

public function getAll(){
    
    $result = $this->executeGetAll();
    return $result; 
}

private function validUser($id , $rol){
    if( $rol === 'Administrador' && $id === 'todos'){
        return " AND r.estado_envio = 1";
    }
    elseif($rol === 'Administrador'){
        return " AND r.estado_envio = 1  AND d.id_dep = " . $id ;
    }
    else{ 
        return " AND d.id_dep = " . $id . " AND r.estado_envio = 0 AND r.estado = 1";
    }
}

private function executeGetAll(){
    $todos = ' LEFT JOIN ';
    $checkReq = $this->conex->prepare("SELECT id_req FROM requerimientos WHERE id_dep = ? AND estado = 1 AND estado_envio = 0");
    $checkReq->execute([$_SESSION['id_dep']]);
    $reqExistente = $checkReq->fetch();

    // 2. Si NO hay requerimiento activo, devolvemos un array vacío inmediatamente
    // Esto hará que la tabla no muestre nada (o muestre el mensaje de "No data")
    if (!$reqExistente) {
        return []; 
    }
    $rol = $_SESSION['rol'];
    
    // DECISIÓN: ¿Quién solicita los datos?
    if ($rol === 'Administrador') {
        // Si es Admin, el ID viene del POST (el select)
        $id_dep_a_filtrar = isset($_POST['id_dep_filtro']) ? $_POST['id_dep_filtro'] : null;
    } else {
        // Si es usuario normal, siempre usamos su propia sesión
        $id_dep_a_filtrar = $_SESSION['id_dep'];
    }

    // Si es admin y no ha seleccionado nada todavía, devolvemos vacío
    if ($rol === 'Administrador' && !$id_dep_a_filtrar) {
        return [];
    }
    if ($id_dep_a_filtrar === 'todos') $todos = ' INNER JOIN '; 

    // Ahora llamamos a validUser pasándole el ID decidido
    $com = $this->validUser($id_dep_a_filtrar, $rol);
    $query = "SELECT 
    req_data.id_req as id_req,
    COALESCE(req_data.nom_dep, 'Sin solicitar') AS dependencia,
    p.cod_partida AS partida,
    i.nom_item AS producto,
    i.id_item as id_item,
    COALESCE(req_data.Ene, 0) AS Ene,
    COALESCE(req_data.Feb, 0) AS Feb,
    COALESCE(req_data.Mar, 0) AS Mar,
    COALESCE(req_data.Abr, 0) AS Abr,
    COALESCE(req_data.May, 0) AS May,
    COALESCE(req_data.Jun, 0) AS Jun,
    COALESCE(req_data.Jul, 0) AS Jul,
    COALESCE(req_data.Ago, 0) AS Ago,
    COALESCE(req_data.Sep, 0) AS Sep,
    COALESCE(req_data.Oct, 0) AS Oct,
    COALESCE(req_data.Nov, 0) AS Nov,
    COALESCE(req_data.Dic, 0) AS Dic,
    COALESCE(req_data.Total_Cantidad, 0) AS Total_Cantidad,
    i.precio AS precio_unit_usd,
    (COALESCE(req_data.Total_Cantidad, 0) * i.precio) AS total_usd,
    (COALESCE(req_data.Total_Cantidad, 0) * i.precio * COALESCE(req_data.tasa, 0)) AS total_bs
FROM items_partida i
JOIN partidas p ON i.id_partida = p.id_partida
" . $todos . " (
    -- Esta subconsulta calcula los meses solo para los items que tienen registro en detalle_req
    SELECT 
        dr.id_item,
        r.id_req,
        d.nom_dep,
        tb.tasa_bcv_usd AS tasa,
        SUM(CASE WHEN dr.mes = 1 THEN dr.cant_mes END) AS Ene,
        SUM(CASE WHEN dr.mes = 2 THEN dr.cant_mes END) AS Feb,
        SUM(CASE WHEN dr.mes = 3 THEN dr.cant_mes END) AS Mar,
        SUM(CASE WHEN dr.mes = 4 THEN dr.cant_mes END) AS Abr,
        SUM(CASE WHEN dr.mes = 5 THEN dr.cant_mes END) AS May,
        SUM(CASE WHEN dr.mes = 6 THEN dr.cant_mes END) AS Jun,
        SUM(CASE WHEN dr.mes = 7 THEN dr.cant_mes END) AS Jul,
        SUM(CASE WHEN dr.mes = 8 THEN dr.cant_mes END) AS Ago,
        SUM(CASE WHEN dr.mes = 9 THEN dr.cant_mes END) AS Sep,
        SUM(CASE WHEN dr.mes = 10 THEN dr.cant_mes END) AS Oct,
        SUM(CASE WHEN dr.mes = 11 THEN dr.cant_mes END) AS Nov,
        SUM(CASE WHEN dr.mes = 12 THEN dr.cant_mes END) AS Dic,
        SUM(dr.cant_mes) AS Total_Cantidad
    FROM detalle_req dr
    JOIN requerimientos r ON dr.id_req = r.id_req
    JOIN dependencias d ON r.id_dep = d.id_dep
    JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
    WHERE 1=1 " . $com . "
    GROUP BY dr.id_item, r.id_req, d.nom_dep, tb.tasa_bcv_usd
) AS req_data ON i.id_item = req_data.id_item
WHERE i.estado = 1 
ORDER BY p.cod_partida, i.nom_item;";
    $stmt = $this->conex->prepare($query);
    $stmt->execute();
    $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return ($resultados) ? $resultados : [];
}

public function getProductos(){
    $result = $this->executeGetProductos();
    return $result;
}
// REEMPLAZAR el método existente por este para que maneje la partida actual
private function executeGetProductos(){
    // Capturamos la partida que viene por POST desde DataTables
    $partida = isset($_POST['partida']) ? $_POST['partida'] : '401';
    
    // Traemos los ítems de la partida y simulamos los meses en 0 por defecto
    $query = "SELECT i.id_item, i.nom_item, 
              0 as ene, 0 as feb, 0 as mar, 0 as abr, 0 as may, 0 as jun,
              0 as jul, 0 as ago, 0 as sep, 0 as oct, 0 as nov, 0 as dic
              FROM items_partida i
              JOIN partidas p ON i.id_partida = p.id_partida
              WHERE p.cod_partida = :partida AND i.estado = 1";
              
    $stmt = $this->conex->prepare($query);
    $stmt->bindValue(':partida', $partida, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

public function saveReq($id, $part, $cant,$id_dep){
    $result = $this->executeSaveReq($id,$part,$cant,$id_dep);
    return $result;
}

// AGREGAR este nuevo método al final de la clase antes del último '}'
private function executeSaveReq($id_req, $partida, $cantidades, $id_dep) {
    try {
        $this->conex->beginTransaction();
        // 1. Si no existe un id_req (es la primera partida), creamos el requerimiento maestro
        //Siempre va a a ser cero la primera vez, por lo que aqui podria ser un buen lugar para aplicar el comprobante de
        //registro previo
        if (empty($id_req) || $id_req == 0) {
            $previusReq = $this->executeVerifyPreviusReq($id_dep);
            if(!$previusReq){
                // Buscamos una tasa activa de la tabla tasa_bcv
                $qTasa = "SELECT id_tasa FROM tasa_bcv WHERE estado = 1 LIMIT 1";
                $sTasa = $this->conex->prepare($qTasa);
                $sTasa->execute();
                $tasa = $sTasa->fetch(\PDO::FETCH_ASSOC);
                $id_tasa = $tasa ? $tasa['id_tasa'] : 1; // Respaldo por si no hay tasas registradas

                // Buscamos el año fiscal activo
                $qAnio = "SELECT id_aniof FROM anio_fiscal WHERE activo = 1 LIMIT 1";
                $sAnio = $this->conex->prepare($qAnio);
                $sAnio->execute();
                $anio = $sAnio->fetch(\PDO::FETCH_ASSOC);
                $id_aniof = $anio ? $anio['id_aniof'] : 1;

                $qReq = "INSERT INTO requerimientos (id_dep, id_tasa, id_aniof, estado_envio, fecha_env, estado) 
                        VALUES (:id_dep, :id_tasa, :id_aniof, 0, NOW(), 1)";
                $sReq = $this->conex->prepare($qReq);
                $sReq->bindValue(':id_dep', $_SESSION['id_dep'], \PDO::PARAM_INT);
                $sReq->bindValue(':id_tasa', $id_tasa, \PDO::PARAM_INT);
                $sReq->bindValue(':id_aniof', $id_aniof, \PDO::PARAM_INT);
                $sReq->execute();
                
                $id_req = $this->conex->lastInsertId();
            }
        }

        // 2. Procesar las cantidades enviadas por la partida
        if (!empty($cantidades) && is_array($cantidades)) {
            foreach ($cantidades as $id_item => $meses) {
                
                // Limpieza preventiva: eliminamos registros previos de este ítem en este requerimiento
                $qDel = "DELETE FROM detalle_req WHERE id_req = :id_req AND id_item = :id_item";
                $sDel = $this->conex->prepare($qDel);
                $sDel->bindValue(':id_req', $id_req, \PDO::PARAM_INT);
                $sDel->bindValue(':id_item', $id_item, \PDO::PARAM_INT);
                $sDel->execute();

                // Insertar los meses que tengan cantidad mayor a 0
                foreach ($meses as $mes => $cantidad) {
                    $cantidadInt = intval($cantidad);
                    if ($cantidadInt > 0) {
                        $qIns = "INSERT INTO detalle_req (id_item, id_req, mes, cant_mes, estado) 
                                 VALUES (:id_item, :id_req, :mes, :cant_mes, 1)";
                        $sIns = $this->conex->prepare($qIns);
                        $sIns->bindValue(':id_item', $id_item, \PDO::PARAM_INT);
                        $sIns->bindValue(':id_req', $id_req, \PDO::PARAM_INT);
                        $sIns->bindValue(':mes', $mes, \PDO::PARAM_INT);
                        $sIns->bindValue(':cant_mes', $cantidadInt, \PDO::PARAM_INT);
                        $sIns->execute();
                    }
                }
            }
        }

        // Lógica de navegación de partidas (puedes adaptarla a tus códigos de partida reales)
        $siguiente_partida = $partida;
            if ($partida == '401') $siguiente_partida = '402';
            elseif ($partida == '402') $siguiente_partida = '403';
            elseif ($partida == '403') $siguiente_partida = '404';
            elseif ($partida == '404') $siguiente_partida = '407';
            elseif ($partida == '407') $siguiente_partida = 'FINAL'; // Bandera para activar el botón definitivo
        $this->conex->commit();
        return [
            "status" => "success", 
            "id_req" => $id_req, 
            "siguiente_partida" => $siguiente_partida
        ];

    } catch (\PDOException $e) {
        $this->conex->rollBack();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

public function verifyPreviusReq($id_dep){
    $result = $this->executeVerifyPreviusReq($id_dep);
    return $result;
}

public function verifyPeriod(){
    $result = $this->executeVerifyPeriod();
    return $result;
}

private function executeVerifyPreviusReq($id_dep) {
    $query = "SELECT COUNT(*) as total 
              FROM  requerimientos r
              JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
              WHERE r.id_dep = :id_dep AND af.activo = 1";
              
    $stmt = $this->conex->prepare($query);
    $stmt->bindValue(':id_dep', $id_dep, \PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    return ($res['total'] > 0);
}


private function executeVerifyPeriod() {
    // Buscamos el período activo relacionado al año fiscal activo
    $query = "SELECT per_inicio, per_fin 
              FROM periodos_entrega pe
              JOIN anio_fiscal af ON pe.id_aniof = af.id_aniof
              WHERE pe.activo = 1 AND af.activo = 1 
              LIMIT 1";
              
    $stmt = $this->conex->prepare($query);
    $stmt->execute();
    $periodo = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$periodo) {
        return false; // No hay un período configurado o activo
    }
    
    $inicio = $periodo['per_inicio'];
    $fechaActual = new DateTime(date('Y-m-d'));
    $fin = new DateTime($periodo['per_fin']);
    $fechaRestante[] = date_diff($fechaActual, $fin)->days;
    $fechaRestante[] = $fin->format('d/m/Y');
    
    // Si la fecha actual es mayor que la fecha de fin, el periodo expiró.
    if ($fechaActual >= $inicio && $fechaActual <= $fin) {
        $fechaRestante[] = true; // Está a tiempo
        return $fechaRestante;
    }
    $fechaRestante[] = false;
    return $fechaRestante; // Fuera de rango o extemporáneo
}

public function timeleft(){
    $result = $this->executeTimeLeft();
    return $result;
}

private function executeTimeLeft(){
    $query = "SELECT per_fin FROM periodos_entrega
            WHERE activo = 1";
    $res = $this->conex->prepare($query);
    $res->execute();
    return $res->fetchall();
}

public function actualizarMatriz($id_req, $cantidades) {
    try {
        $this->conex->beginTransaction();

        // 1. Eliminamos todo lo que existe actualmente para este requerimiento
        $del = "DELETE FROM detalle_req WHERE id_req = :id_req";
        $stmtDel = $this->conex->prepare($del);
        $stmtDel->bindValue(':id_req', $id_req, \PDO::PARAM_INT);
        $stmtDel->execute();

        // 2. Insertamos la nueva matriz de datos
        // $cantidades viene estructurado como: [id_item][mes] = valor
        $ins = "INSERT INTO detalle_req (id_item, id_req, mes, cant_mes, estado) 
                VALUES (:id_item, :id_req, :mes, :cant_mes, 1)";
        $stmtIns = $this->conex->prepare($ins);

        foreach ($cantidades as $id_item => $meses) {
            foreach ($meses as $mes => $cantidad) {
                $cant = intval($cantidad);
                
                // Solo insertamos si la cantidad es mayor a 0
                if ($cant > 0) {
                    $stmtIns->bindValue(':id_item', $id_item, \PDO::PARAM_INT);
                    $stmtIns->bindValue(':id_req', $id_req, \PDO::PARAM_INT);
                    $stmtIns->bindValue(':mes', $mes, \PDO::PARAM_INT);
                    $stmtIns->bindValue(':cant_mes', $cant, \PDO::PARAM_INT);
                    $stmtIns->execute();
                }
            }
        }

        $this->conex->commit();
        return ["status" => "success", "message" => "Datos actualizados correctamente."];

    } catch (\PDOException $e) {
        $this->conex->rollBack();
        return ["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()];
    }
}

public function cambiarEstadoRequerimiento($id_req, $nuevo_estado) {
    // Ejemplo usando PDO, ajusta según tu base de datos
    $sql = "UPDATE requerimientos SET estado_envio = :estado WHERE id_req = :id";
    $stmt = $this->conex->prepare($sql);
    return $stmt->execute([
        ':estado' => $nuevo_estado,
        ':id' => $id_req
    ]);
}

}



?>