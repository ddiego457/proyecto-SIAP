<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\SessionTrait;
use EquipoSiap\Siap\helpers\ValidationTrait;
use DateTime;

class requerimientoModel extends ConnectDB {
    use SessionTrait;
    use ValidationTrait;

    private $conex;
    private $idDepAct;

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
        $this->idDepAct = isset($_SESSION['id_dep']) ? $_SESSION['id_dep'] : null;
    }

    // =========================================================================
    // MÉTODOS PÚBLICOS (validaciones sin BD + orquestación)
    // =========================================================================

    // Dependencias para el select de búsqueda (modo administrador)
    public function getAllDep() {
        $stmt = $this->conex->query("SELECT id_dep, nom_dep FROM dependencias");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Reporte principal del requerimiento según dependencia y rol del usuario
    public function getAll() {
        $idDepFiltrar = $this->esAdmin()
            ? (isset($_POST['id_dep_filtro']) ? $_POST['id_dep_filtro'] : null)
            : $this->idDepAct;

        if ($this->esAdmin() && empty($idDepFiltrar)) {
            return [];
        }

        if (!$this->obtenerBorradorRequerimiento()) {
            return [];
        }

        $joinType = ($idDepFiltrar === 'todos') ? ' INNER JOIN ' : ' LEFT JOIN ';
        $idReqActivo = $this->getRequerimientoActivoId($idDepFiltrar);
        if ($idReqActivo === 0) {
            return [];
        }

        $filtrosSQL = $this->buildQueryFilters($idDepFiltrar);

        return $this->fetchMainReport($joinType, $idReqActivo, $filtrosSQL);
    }

    // Productos de la partida recibida por POST, con columnas de meses en 0
    public function getProductos() {
        $partida = isset($_POST['partida']) ? $_POST['partida'] : '401';
        if ($this->validar('partida_actual', $partida) !== null) {
            return [];
        }

        $query = "SELECT pro.id_pres, pro.nom_pres, 
                0 as ene, 0 as feb, 0 as mar, 0 as abr, 0 as may, 0 as jun,
                0 as jul, 0 as ago, 0 as sep, 0 as oct, 0 as nov, 0 as dic
                FROM presupuesto pro
                JOIN partidas p ON pro.id_partida = p.id_partida
                WHERE p.cod_partida = ? AND pro.estado = 1";
        $stmt = $this->conex->prepare($query);
        $stmt->bindValue(1, $partida, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Valida período, requerimiento previo y formatos; guarda la partida en transacción
    public function saveReq($id, $part, $cant, $idDep) {
        if (!$this->obtenerEstadoPeriodo()) {
            return ["status" => "error", "message" => "El período de carga de requerimientos ha vencido o no está activo."];
        }

        if ($this->existeRequerimientoPrevio($idDep)) {
            return ["status" => "error", "message" => "Ya existe un requerimiento previo o no está activo."];
        }

        $error = $this->validar('partida_actual', $part);
        if ($error === null) {
            $error = $this->validar('cantidades', $cant);
        }
        if ($id > 0 && $error === null) {
            $error = $this->validar('id_requerimiento', $id);
        }
        if ($error !== null) {
            return ["status" => "error", "message" => $error];
        }

        try {
            $this->conex->beginTransaction();

            if (empty($id) || $id == 0) {
                $id = $this->createMasterRequirement($idDep);
                if ($id == 0) {
                    $this->conex->rollBack();
                    return ["status" => "error", "message" => "Ya existe un requerimiento previo activo para esta dependencia."];
                }
            }

            if (!empty($cant) && is_array($cant)) {
                $this->saveRequirementDetails($id, $cant);
            }

            $siguiente_partida = $this->calculateNextPartida($part);

            if ($siguiente_partida === 'FINAL') {
                $stmt = $this->conex->prepare("UPDATE requerimiento SET estado = 1 WHERE id_req = :idReq");
                $stmt->execute([
                    ":idReq" => $id
                ]);
            }

            $this->conex->commit();
            return [
                "status" => "success",
                "id_req" => $id,
                "siguiente_partida" => $siguiente_partida
            ];
        } catch (\PDOException $e) {
            $this->conex->rollBack();
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }

    // ¿Existe un requerimiento activo en el año fiscal vigente para la dependencia?
    public function existeRequerimientoPrevio($idDep) {
        $query = "SELECT COUNT(*) as total 
                FROM requerimiento r
                JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                WHERE r.id_dep = :id_dep AND af.activo = 1 AND r.estado = 1";
        $stmt = $this->conex->prepare($query);
        $stmt->bindValue(':id_dep', $idDep, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ($res['total'] > 0);
    }

    // Estado del período de entrega: [días restantes, fecha fin, activo] o false si no hay período
    public function obtenerEstadoPeriodo() {
        $periodo = $this->getActivePeriod();

        if (!$periodo) {
            return false;
        }

        $inicio = $periodo['per_inicio'];
        $fechaActual = new DateTime(date('Y-m-d'));
        $fin = new DateTime($periodo['per_fin']);
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

    // Valida y reemplaza la matriz completa de cantidades del requerimiento
    public function actualizarMatriz($idReq, $cantidades) {
        $error = $this->validar('cantidades', $cantidades);
        if ($error === null && $idReq > 0) {
            $error = $this->validar('id_requerimiento', $idReq);
        }
        if ($error !== null) {
            return ["status" => "error", "message" => $error];
        }

        if ($idReq <= 0) {
            return ["status" => "error", "message" => "No hay requerimiento activo para actualizar."];
        }

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

    // Baja lógica del requerimiento; requiere rol administrador
    public function eliminarRequerimiento($idReq) {
        if (!$this->esAdmin()) {
            return ['status' => 'error', 'message' => 'No tiene permisos para eliminar requerimientos.'];
        }

        $idReq = (int)$idReq;
        if ($idReq <= 0) {
            return ['status' => 'error', 'message' => 'ID de requerimiento no válido.'];
        }

        try {
            $sql = "UPDATE requerimiento SET estado = 0, estado_registro = 0 WHERE id_req = :id AND estado = 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':id' => $idReq]);

            if ($stmt->rowCount() > 0) {
                return ['status' => 'success', 'message' => 'Requerimiento eliminado correctamente.'];
            }

            return ['status' => 'error', 'message' => 'No se pudo eliminar el requerimiento (puede que ya esté eliminado).'];
        } catch (\PDOException $e) {
            error_log("Error eliminarRequerimiento: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'No se pudo eliminar el requerimiento (puede que ya esté eliminado).'];
        }
    }

    // Marca el requerimiento como registrado (estado_registro = 1)
    public function cambiarEstadoRequerimiento($idReq) {
        $idReq = (int)$idReq;
        if ($idReq <= 0) {
            return ['status' => 'error', 'message' => 'No hay requerimiento activo para registrar.'];
        }

        $sql = "UPDATE requerimiento SET estado_registro = 1 WHERE id_req = :id";
        $stmt = $this->conex->prepare($sql);

        if ($stmt->execute([':id' => $idReq])) {
            return ['status' => 'success'];
        }

        return ['status' => 'error', 'message' => 'No se pudo actualizar el estado.'];
    }

    // ID del requerimiento activo según dependencia y rol; 'todos' devuelve "0"
    public function getRequerimientoActivoId($idDepFiltrar) {
        if ($idDepFiltrar === 'todos') {
            return "0";
        }

        $estadoReg = ($this->esAdmin()) ? 1 : 0;

        $stmtCheck = $this->conex->prepare("SELECT r.id_req 
            FROM requerimiento r
            JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
            WHERE r.id_dep = ? AND r.estado = 1 AND r.estado_registro = ? AND af.activo = 1");
        $stmtCheck->execute([(int)$idDepFiltrar, (int)$estadoReg]);
        $reqExistente = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

        return $reqExistente ? $reqExistente['id_req'] : 0;
    }

    // Año fiscal activo
    public function getAnioFiscal() {
        $stmt = $this->conex->prepare("SELECT anio FROM anio_fiscal WHERE activo = 1");
        $stmt->execute();
        $anio = $stmt->fetch();
        return $anio["anio"];
    }

    // =========================================================================
    // MÉTODOS PRIVADOS (solo BD y helpers)
    // =========================================================================

    // ¿Tiene la dependencia en sesión un borrador sin registrar?
    private function obtenerBorradorRequerimiento() {
        if ($this->esAdmin()) return true;
        $checkReq = $this->conex->prepare("SELECT id_req FROM requerimiento WHERE id_dep = ? AND estado = 1 AND estado_registro = 0");
        $checkReq->execute([$this->idDepAct]);
        return $checkReq->fetch();
    }

    // Filtros extra de seguridad para la consulta principal
    private function buildQueryFilters($id) {
        if ($this->esAdmin() && $id === 'todos') {
            return " AND r.estado_registro = 1 AND r.estado = 1";
        } elseif ($this->esAdmin()) {
            return " AND r.estado_registro = 1 AND r.estado = 1 AND d.id_dep = " . (int)$id;
        } else {
            return " AND d.id_dep = " . (int)$id . " AND r.estado_registro = 0 AND r.estado = 1";
        }
    }

    // Reporte mensual con totales en USD y Bs según la tasa vigente
    private function fetchMainReport($joinType, $idReqActivo, $filtrosSQL) {
        $query = "SELECT 
            " . $idReqActivo . " as id_req,
            COALESCE(req_data.nom_dep, 'Sin solicitar') AS dependencia,
            p.cod_partida AS partida,
            p.descripcion AS partida_nombre,
            pro.nom_pres AS producto,
            pro.id_pres as id_pres,
            COALESCE(req_data.Ene, 0) AS Ene, COALESCE(req_data.Feb, 0) AS Feb,
            COALESCE(req_data.Mar, 0) AS Mar, COALESCE(req_data.Abr, 0) AS Abr,
            COALESCE(req_data.May, 0) AS May, COALESCE(req_data.Jun, 0) AS Jun,
            COALESCE(req_data.Jul, 0) AS Jul, COALESCE(req_data.Ago, 0) AS Ago,
            COALESCE(req_data.Sep, 0) AS Sep, COALESCE(req_data.Oct, 0) AS Oct,
            COALESCE(req_data.Nov, 0) AS Nov, COALESCE(req_data.Dic, 0) AS Dic,
            COALESCE(req_data.Total_Cantidad, 0) AS Total_Cantidad,
            pro.precio AS precio_unit_usd,
            (COALESCE(req_data.Total_Cantidad, 0) * pro.precio) AS total_usd,
            (COALESCE(req_data.Total_Cantidad, 0) * pro.precio * COALESCE(tasa_vigente.tasa, 0)) AS total_bs
        FROM presupuesto pro
        JOIN partidas p ON pro.id_partida = p.id_partida
        CROSS JOIN (SELECT tasa_bcv_usd AS tasa FROM tasa_bcv WHERE estado = 1 LIMIT 1) AS tasa_vigente
        " . $joinType . " (
            SELECT 
                dr.id_pres, r.id_req, d.nom_dep,
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
            JOIN requerimiento r ON dr.id_req = r.id_req
            JOIN dependencias d ON r.id_dep = d.id_dep
            JOIN anio_fiscal af ON r.id_aniof = af.id_aniof 
            WHERE af.activo = 1 " . $filtrosSQL . "         
            GROUP BY dr.id_pres, r.id_req, d.nom_dep
        ) AS req_data ON pro.id_pres = req_data.id_pres
        WHERE pro.estado = 1 
        ORDER BY p.cod_partida, pro.nom_pres;";

        $stmt = $this->conex->prepare($query);
        $stmt->execute();
        $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $resultados ?: [];
    }

    // Crea el requerimiento maestro si no existe uno activo para la dependencia
    private function createMasterRequirement($idDep) {
        if ($this->existeRequerimientoPrevio($idDep)) {
            return 0; 
        }

        $id_tasa = $this->getActiveTasaId();
        $id_aniof = $this->getActiveAnioFiscalId();

        $qReq = "INSERT INTO requerimiento (id_dep, id_tasa, id_aniof, estado_registro, fecha_reg, estado) 
                VALUES (:id_dep, :id_tasa, :id_aniof, 0, NOW(), 0)";
        $sReq = $this->conex->prepare($qReq);
        $sReq->execute([
            ':id_dep' => $idDep,
            ':id_tasa' => $id_tasa,
            ':id_aniof' => $id_aniof
        ]);
        
        return $this->conex->lastInsertId();
    }

    // Guarda las cantidades por mes, reemplazando las filas existentes del producto
    private function saveRequirementDetails($idReq, $cantidades) {
        $qDel = "DELETE FROM detalle_req WHERE id_req = :id_req AND id_pres = :id_pres";
        $sDel = $this->conex->prepare($qDel);

        $qIns = "INSERT INTO detalle_req (id_pres, id_req, mes, cant_mes) 
                VALUES (:id_pres, :id_req, :mes, :cant_mes)";
        $sIns = $this->conex->prepare($qIns);

        foreach ($cantidades as $idProd => $meses) {
            $sDel->execute([':id_req' => $idReq, ':id_pres' => $idProd]);

            foreach ($meses as $mes => $cantidad) {
                $cantidadInt = intval($cantidad);
                if ($cantidadInt > 0) {
                    $sIns->execute([
                        ':id_pres' => $idProd,
                        ':id_req' => $idReq,
                        ':mes' => $mes,
                        ':cant_mes' => $cantidadInt
                    ]);
                }
            }
        }
    }

    // Siguiente partida del asistente; 'FINAL' al terminar el último tramo
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

    // Tasa BCV vigente (por defecto 1)
    private function getActiveTasaId() {
        $qTasa = "SELECT id_tasa FROM tasa_bcv WHERE estado = 1 LIMIT 1";
        $sTasa = $this->conex->query($qTasa);
        $tasa = $sTasa->fetch(\PDO::FETCH_ASSOC);
        return $tasa ? $tasa['id_tasa'] : 1;
    }

    // Año fiscal activo (por defecto 1)
    private function getActiveAnioFiscalId() {
        $qAnio = "SELECT id_aniof FROM anio_fiscal WHERE activo = 1 LIMIT 1";
        $sAnio = $this->conex->query($qAnio);
        $anio = $sAnio->fetch(\PDO::FETCH_ASSOC);
        return $anio ? $anio['id_aniof'] : 1;
    }

    // Período de entrega activo del año fiscal vigente
    private function getActivePeriod() {
        $query = "SELECT per_inicio, per_fin 
                FROM periodos_entrega pe
                JOIN anio_fiscal af ON pe.id_aniof = af.id_aniof
                WHERE pe.activo = 1 AND af.activo = 1 LIMIT 1";
        $stmt = $this->conex->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Borra todos los detalles del requerimiento
    private function deleteAllDetailsFromReq($idReq) {
        $del = "DELETE FROM detalle_req WHERE id_req = :id_req";
        $stmtDel = $this->conex->prepare($del);
        $stmtDel->execute([':id_req' => $idReq]);
    }

    // Inserta la matriz completa de cantidades
    private function insertDetailsMatrix($idReq, $cantidades) {
        $ins = "INSERT INTO detalle_req (id_pres, id_req, mes, cant_mes) 
                VALUES (:id_pres, :id_req, :mes, :cant_mes)";
        $stmtIns = $this->conex->prepare($ins);

        foreach ($cantidades as $idProd => $meses) {
            foreach ($meses as $mes => $cantidad) {
                $cant = intval($cantidad);
                if ($cant > 0) {
                    $stmtIns->execute([
                        ':id_pres' => $idProd,
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
