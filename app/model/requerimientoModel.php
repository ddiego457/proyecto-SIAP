<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
class requerimientoModel extends ConnectDB{
private $conex;

public function __construct()
{
    parent::__construct();
    $this->conex = $this->getConnection();
}

public function getAll(){
    
    $result = $this->executeGetAll();
    return $result; 
}

private function validUser($id , $rol){
    $res = "";
    if($rol === 'Administrador') return;
    $res = "WHERE d.id_dep = " . $id;
    return $res;
}

private function executeGetAll(){
    $com = $this->validUser($_SESSION['id_dep'], $_SESSION['rol']);
    $query = "SELECT d.nom_dep as dependencia,
    p.cod_partida AS partida,
    i.nom_item AS producto,
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
    SUM(dr.cant_mes) AS Total_Cantidad,
    SUM(i.precio) AS precio_unit_usd,
    (SUM(dr.cant_mes) * i.precio) AS total_usd,
    SUM(dr.cant_mes * i.precio * tb.tasa_bcv_usd) AS total_bs
    FROM requerimientos r
    JOIN detalle_req dr ON r.id_req = dr.id_req
    JOIN dependencias d ON d.id_dep = r.id_dep
    JOIN items_partida i ON dr.id_item = i.id_item
    JOIN partidas p ON i.id_partida = p.id_partida
    JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
    -- WHERE r.estado = 'enviado'
    "
    . $com .
    "
    GROUP BY d.nom_dep, p.cod_partida, i.id_item, i.nom_item, i.precio
    ORDER BY p.cod_partida, d.nom_dep;";
    $stmt = $this->conex->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

public function saveReq($id, $part, $cant){
    $result = $this->executeSaveReq($id,$part,$cant);
    return $result;
}

// AGREGAR este nuevo método al final de la clase antes del último '}'
public function executeSaveReq($id_req, $partida, $cantidades) {
    try {
        $this->conex->beginTransaction();

        // 1. Si no existe un id_req (es la primera partida), creamos el requerimiento maestro
        if (empty($id_req) || $id_req == 0) {
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

public function verifyYear($id_dep){
    $result = $this->verificarRegistroPrevio($id_dep);
    return $result;
}

public function verifyPer(){
    $result = $this->verificarPeriodoValido();
    return $result;
}

private function verificarRegistroPrevio($id_dep) {
    $query = "SELECT COUNT(*) as total 
              FROM detalle_req dr
              JOIN requerimientos r ON r.id_req = dr.id_req
              JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
              WHERE r.id_dep = :id_dep AND af.activo = 1";
              
    $stmt = $this->conex->prepare($query);
    $stmt->bindValue(':id_dep', $id_dep, \PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    return ($res['total'] > 0);
}


private function verificarPeriodoValido() {
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
    
    $fechaActual = date('Y-m-d');
    $inicio = $periodo['per_inicio'];
    $fin = $periodo['per_fin'];
    
    // Si la fecha actual es mayor que la fecha de fin, el periodo expiró.
    if ($fechaActual >= $inicio && $fechaActual <= $fin) {
        return true; // Está a tiempo
    }
    
    return false; // Fuera de rango o extemporáneo
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

}


?>