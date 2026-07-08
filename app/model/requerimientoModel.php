<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use DateTime;

class requerimientoModel extends ConnectDB {
    private $conex;
    private $idDepAct;

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
        $this->idDepAct = isset($_SESSION['id_dep']) ? $_SESSION['id_dep'] : null;
    }

    // =========================================================================
    // MÉTODOS PÚBLICOS (Controladores de flujo)
    // =========================================================================

    public function getAllDep() {
        return $this->executeGetAllDep();
    }

    public function getAll() {
        return $this->executeGetAll();
    }

    public function getProductos() {
        return $this->executeGetProductos();
    }

    public function saveReq($id, $part, $cant, $idDep) {
        return $this->executeSaveReq($id, $part, $cant, $idDep);
    }

    public function verifyPreviusReq($idDep) {
        return $this->executeVerifyPreviusReq($idDep);
    }

    public function verifyPeriod() {
        return $this->executeVerifyPeriod();
    }

    public function timeleft() {
        return $this->executeTimeLeft();
    }

    public function actualizarMatriz($idReq, $cantidades){
        return $this->executeActualizarMatriz($idReq, $cantidades);
    }

    public function cambiarEstadoRequerimiento($idReq, $nuevo_estado){
        return $this->executeCambiarEstadoRequerimiento($idReq, $nuevo_estado);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS PRINCIPALES (Ejecución de Lógica)
    // =========================================================================

    //metodo que me devuelve las dependencias existentes para poder eligir cual buscar
    //dentro del select en el modo administrador
    private function executeGetAllDep() {
        $stmt = $this->conex->query("SELECT id_dep, nom_dep FROM dependencias");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    //devuelve los detalles del requerimiento para el usuario que haya ingresado
    // si no hay datos entonces devuelve nada para que la tabla no mande un error

    //de igual modo se hace una verificacion para saber a cual dependencia pertenece el usuario
    // y el rol que tiene
    private function executeGetAll() {
        $reqExistente = $this->validReq();
        if (!$reqExistente) {
            return [];
        }

        $rol = $_SESSION['rol'] ?? 'Usuario';
        // si es admin y ya se selecciono el id de la dependencia que va a revisar, el valor se almacena
        $idDepFiltrar = $this->resolveTargetDependency($rol);

        // si aun no ha seleccionada nada devuelve vacio para que la tabla no lance un error
        if ($rol === 'Administrador' && empty($idDepFiltrar)) {
            return [];
        }

        
        $joinType = ($idDepFiltrar === 'todos') ? ' INNER JOIN ' : ' LEFT JOIN ';
        // funciona como buscador de requerimientos
        $idReqActivo = $this->getActiveReqId($idDepFiltrar,$rol); 
        if($idReqActivo === 0){
            return [];
        }
        //filtros extra de seguridad para la consulta
        $filtrosSQL = $this->buildQueryFilters($idDepFiltrar, $rol);

        //finalmente devuelve el reporte con todos los datos
        return $this->fetchMainReport($joinType, $idReqActivo, $filtrosSQL);
    }

    //metodo encargado de traer todos los prodictos de la base de datos para el registro
    private function executeGetProductos() {
        $partida = isset($_POST['partida']) ? $_POST['partida'] : '401';
        //cada numero es pintado con un mes para la selecion de la cantidad en la tabla
        $query = "SELECT pro.id_prod, pro.nom_prod, 
                  0 as ene, 0 as feb, 0 as mar, 0 as abr, 0 as may, 0 as jun,
                  0 as jul, 0 as ago, 0 as sep, 0 as oct, 0 as nov, 0 as dic
                  FROM productos pro
                  JOIN partidas p ON pro.id_partida = p.id_partida
                  WHERE p.cod_partida = ? AND pro.estado = 1";
                  
        $stmt = $this->conex->prepare($query);
        //cada consulta a los productos se divide por partidad presupuestaria
        $stmt->bindValue(1 , $partida, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

//metodo que se encargar de guardar las cantidades y los meses del producto en la tabla detalle_req
    private function executeSaveReq($idReq, $partida, $cantidades, $idDep) {
        try {
            $this->conex->beginTransaction();
            //primero crea una transaccion para encapsular todas las consultas en una sola. Asi se evitan errores 

            //si no existe un requerimiento, se crea uno para empezar a guardar la info ahi
            if (empty($idReq) || $idReq == 0) {
                $idReq = $this->createMasterRequirement($idDep);
            }
            //verifica que existan las cantidades para los meses selecionados para insertar datos
            if (!empty($cantidades) && is_array($cantidades)) {
                $this->saveRequirementDetails($idReq, $cantidades);
            }

//carga en la vista la siguiente partida, para que el metodo de consulta a los productos carga la siguiente
            $siguiente_partida = $this->calculateNextPartida($partida);
            //si todo sale bien, se ejecutan todas las consultas y se envia un mensaje de exito
            $this->conex->commit();
            return [
                "status" => "success", 
                "id_req" => $idReq, 
                "siguiente_partida" => $siguiente_partida
            ];
//si algo sale mal se devuelve y envia un mensaje (por lo general si esto sucede te envia a la vista main) 
        } catch (\PDOException $e) {
            $this->conex->rollBack();
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }

    private function executeVerifyPreviusReq($idDep) {
        $query = "SELECT COUNT(*) as total 
                  FROM requerimientos r
                  JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                  WHERE r.id_dep = :id_dep AND af.activo = 1";
                  
        $stmt = $this->conex->prepare($query);
        $stmt->bindValue(':id_dep', $idDep, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return ($res['total'] > 0);
    }

    private function executeVerifyPeriod() {
        $periodo = $this->getActivePeriod();
        
        if (!$periodo) {
            return false;
        }
        
        return $this->calculatePeriodStatus($periodo['per_inicio'], $periodo['per_fin']);
    }

    private function executeTimeLeft() {
        $query = "SELECT per_fin FROM periodos_entrega WHERE activo = 1";
        $res = $this->conex->prepare($query);
        $res->execute();
        return $res->fetchall();
    }

    private function executeActualizarMatriz($idReq, $cantidades) {
        try {
            $this->conex->beginTransaction();
            $this->deleteAllDetailsFromReq($idReq);
            $this->insertDetailsMatrix($idReq, $cantidades);
            $this->conex->commit();
            
            return ["status" => "success", "message" => "Datos actualizados correctamente."];
        } catch (\PDOException $e) {
            $this->conex->rollBack();
            return ["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()];
        }
    }

    private function executeCambiarEstadoRequerimiento($idReq, $nuevo_estado) {
        $sql = "UPDATE requerimientos SET estado_envio = :estado WHERE id_req = :id";
        $stmt = $this->conex->prepare($sql);
        return $stmt->execute([
            ':estado' => $nuevo_estado,
            ':id' => $idReq
        ]);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS AUXILIARES (Responsabilidad Única)
    // =========================================================================

    //valid req se encarga especialmente de trabajar con las dependencias para el borrador
    private function validReq() {
        if($_SESSION['rol'] === 'Administrador') return true;
        $checkReq = $this->conex->prepare("SELECT id_req FROM requerimientos WHERE id_dep = ? AND estado = 1 AND estado_envio = 0");
        $checkReq->execute([$this->idDepAct]);
        return $checkReq->fetch();
    }

    private function resolveTargetDependency($rol) {
        if ($rol === 'Administrador') {
            return isset($_POST['id_dep_filtro']) ? $_POST['id_dep_filtro'] : null;
        }
        return $this->idDepAct;
    }

    private function getActiveReqId($idDepFiltrar, $rol) {
        if ($idDepFiltrar === 'todos') {
            return "0";
        }

        $estadoEnv = ($rol === 'Administrador') ? 1 : 0;

        $stmtCheck = $this->conex->prepare("SELECT r.id_req 
              FROM requerimientos r
              JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
              WHERE r.id_dep = ? AND r.estado = 1 AND r.estado_envio = ? AND af.activo = 1");
        $stmtCheck->execute([(int)$idDepFiltrar,(int)$estadoEnv]);
        $reqExistente = $stmtCheck->fetch(\PDO::FETCH_ASSOC);
        
        return $reqExistente ? $reqExistente['id_req'] : 0;
    }

    private function buildQueryFilters($id, $rol) {
        if ($rol === 'Administrador' && $id === 'todos') {
            return " AND r.estado_envio = 1";
        } elseif ($rol === 'Administrador') {
            return " AND r.estado_envio = 1 AND d.id_dep = " . (int)$id;
        } else {
            return " AND d.id_dep = " . (int)$id . " AND r.estado_envio = 0 AND r.estado = 1";
        }
    }

    private function fetchMainReport($joinType, $idReqActivo, $filtrosSQL) {
        $query = "SELECT 
            " . $idReqActivo . " as id_req,
            COALESCE(req_data.nom_dep, 'Sin solicitar') AS dependencia,
            p.cod_partida AS partida,
            pro.nom_prod AS producto,
            pro.id_prod as id_prod,
            COALESCE(req_data.Ene, 0) AS Ene, COALESCE(req_data.Feb, 0) AS Feb,
            COALESCE(req_data.Mar, 0) AS Mar, COALESCE(req_data.Abr, 0) AS Abr,
            COALESCE(req_data.May, 0) AS May, COALESCE(req_data.Jun, 0) AS Jun,
            COALESCE(req_data.Jul, 0) AS Jul, COALESCE(req_data.Ago, 0) AS Ago,
            COALESCE(req_data.Sep, 0) AS Sep, COALESCE(req_data.Oct, 0) AS Oct,
            COALESCE(req_data.Nov, 0) AS Nov, COALESCE(req_data.Dic, 0) AS Dic,
            COALESCE(req_data.Total_Cantidad, 0) AS Total_Cantidad,
            pro.precio AS precio_unit_usd,
            (COALESCE(req_data.Total_Cantidad, 0) * pro.precio) AS total_usd,
            (COALESCE(req_data.Total_Cantidad, 0) * pro.precio * COALESCE(req_data.tasa, 0)) AS total_bs
        FROM productos pro
        JOIN partidas p ON pro.id_partida = p.id_partida
        " . $joinType . " (
            SELECT 
                dr.id_prod, r.id_req, d.nom_dep, tb.tasa_bcv_usd AS tasa,
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
            JOIN anio_fiscal af ON r.id_aniof = af.id_aniof 
            WHERE af.activo = 1 " . $filtrosSQL . "         
            GROUP BY dr.id_prod, r.id_req, d.nom_dep, tb.tasa_bcv_usd
        ) AS req_data ON pro.id_prod = req_data.id_prod
        WHERE pro.estado = 1 
        ORDER BY p.cod_partida, pro.nom_prod;";

        $stmt = $this->conex->prepare($query);
        $stmt->execute();
        $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $resultados ?: [];
    }

    private function createMasterRequirement($idDep) {
        if ($this->executeVerifyPreviusReq($idDep)) {
            return 0; 
        }

        $id_tasa = $this->getActiveTasaId();
        $id_aniof = $this->getActiveAnioFiscalId();

        $qReq = "INSERT INTO requerimientos (id_dep, id_tasa, id_aniof, estado_envio, fecha_env, estado) 
                 VALUES (:id_dep, :id_tasa, :id_aniof, 0, NOW(), 1)";
        $sReq = $this->conex->prepare($qReq);
        $sReq->execute([
            ':id_dep' => $this->idDepAct,
            ':id_tasa' => $id_tasa,
            ':id_aniof' => $id_aniof
        ]);
        
        return $this->conex->lastInsertId();
    }

    private function saveRequirementDetails($idReq, $cantidades) {
        $qDel = "DELETE FROM detalle_req WHERE id_req = :id_req AND id_prod = :id_prod";
        $sDel = $this->conex->prepare($qDel);

        $qIns = "INSERT INTO detalle_req (id_prod, id_req, mes, cant_mes, estado) 
                 VALUES (:id_prod, :id_req, :mes, :cant_mes, 1)";
        $sIns = $this->conex->prepare($qIns);

        foreach ($cantidades as $idProd => $meses) {
            $sDel->execute([':id_req' => $idReq, ':id_prod' => $idProd]);

            foreach ($meses as $mes => $cantidad) {
                $cantidadInt = intval($cantidad);
                if ($cantidadInt > 0) {
                    $sIns->execute([
                        ':id_prod' => $idProd,
                        ':id_req' => $idReq,
                        ':mes' => $mes,
                        ':cant_mes' => $cantidadInt
                    ]);
                }
            }
        }
    }

    private function calculateNextPartida($partidaActual) {
        $mapaPartidas = [
            '401' => '402',
            '402' => '403',
            '403' => '404',
            '404' => '407',
            '407' => 'FINAL'
        ];
        return array_key_exists($partidaActual, $mapaPartidas) ? $mapaPartidas[$partidaActual] : $partidaActual;
    }

    private function getActiveTasaId() {
        $qTasa = "SELECT id_tasa FROM tasa_bcv WHERE estado = 1 LIMIT 1";
        $sTasa = $this->conex->query($qTasa);
        $tasa = $sTasa->fetch(\PDO::FETCH_ASSOC);
        return $tasa ? $tasa['id_tasa'] : 1;
    }

    private function getActiveAnioFiscalId() {
        $qAnio = "SELECT id_aniof FROM anio_fiscal WHERE activo = 1 LIMIT 1";
        $sAnio = $this->conex->query($qAnio);
        $anio = $sAnio->fetch(\PDO::FETCH_ASSOC);
        return $anio ? $anio['id_aniof'] : 1;
    }

    private function getActivePeriod() {
        $query = "SELECT per_inicio, per_fin 
                  FROM periodos_entrega pe
                  JOIN anio_fiscal af ON pe.id_aniof = af.id_aniof
                  WHERE pe.activo = 1 AND af.activo = 1 LIMIT 1";
        $stmt = $this->conex->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function calculatePeriodStatus($fechaInicio, $fechaFin) {
        $inicio = $fechaInicio;
        $fechaActual = new DateTime(date('Y-m-d'));
        $fin = new DateTime($fechaFin);
        $intervalo = date_diff($fechaActual, $fin);
        
        $resultado = [
            $intervalo->format('%r%a'),
            $fin->format('d/m/Y')
        ];
        
        if ($fechaActual >= $inicio && $fechaActual <= $fin) {
            $resultado[] = true; 
        } else {
            $resultado[] = false; 
        }
        
        return $resultado;
    }

    private function deleteAllDetailsFromReq($idReq) {
        $del = "DELETE FROM detalle_req WHERE id_req = :id_req";
        $stmtDel = $this->conex->prepare($del);
        $stmtDel->execute([':id_req' => $idReq]);
    }

    private function insertDetailsMatrix($idReq, $cantidades) {
        $ins = "INSERT INTO detalle_req (id_prod, id_req, mes, cant_mes, estado) 
                VALUES (:id_prod, :id_req, :mes, :cant_mes, 1)";
        $stmtIns = $this->conex->prepare($ins);

        foreach ($cantidades as $idProd => $meses) {
            foreach ($meses as $mes => $cantidad) {
                $cant = intval($cantidad);
                if ($cant > 0) {
                    $stmtIns->execute([
                        ':id_prod' => $idProd,
                        ':id_req' => $idReq,
                        ':mes' => $mes,
                        ':cant_mes' => $cant
                    ]);
                }
            }
        }
    }
}
?>