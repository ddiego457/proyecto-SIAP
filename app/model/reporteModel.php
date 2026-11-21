<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;
use EquipoSiap\Siap\helpers\ReporteGeneratorTrait;
use EquipoSiap\Siap\helpers\ReporteExcelGeneratorTrait;
use EquipoSiap\Siap\helpers\ReportePdfGeneratorTrait;

class reporteModel extends ConnectDB
{
    use ValidationTrait;
    use ReporteGeneratorTrait;
    use ReporteExcelGeneratorTrait;
    use ReportePdfGeneratorTrait;

    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getPartidasParaReporte(): array
    {
        try {
            $stmt = $this->conex->query(
                "SELECT id_partida, cod_partida, descripcion
                 FROM partidas
                 WHERE estado = 1
                 ORDER BY cod_partida"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error cargando partidas para el reporte: ' . $e->getMessage());
        }
    }

    public function getAniosParaReporte(): array
    {
        try {
            $stmt = $this->conex->query(
                "SELECT id_aniof, anio, activo
                 FROM anio_fiscal
                 ORDER BY anio DESC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error cargando años fiscales para el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Unidades de medida activas del catálogo exclusivo (unidades_medida).
     */
    public function getUnidadesParaReporte(): array
    {
        try {
            $stmt = $this->conex->query(
                "SELECT UPPER(TRIM(unidad)) AS unidad
                 FROM unidades_medida
                 WHERE estado = 1
                 ORDER BY unidad"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error cargando unidades de medida para el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Expresión SQL que extrae la unidad de medida del formato
     * "DESCRIPCION|UNIDAD|" de un campo nom_pres (sin unidad => 'UND').
     */
    private function exprUnidad(string $campo): string
    {
        return "COALESCE(NULLIF(CASE WHEN LOCATE('|', {$campo}) > 0 "
            . "THEN SUBSTRING_INDEX(SUBSTRING_INDEX({$campo}, '|', 2), '|', -1) "
            . "ELSE '' END, ''), 'UND')";
    }

    public function getDependenciasConResponsable(
        string $filtro = 'activas_con_responsable',
        bool $ocultarCoordinadores = false
    ): array
    {
        try {
            $where = match ($filtro) {
                'activas_con_responsable' => 'd.estado = 1 AND r.id_responsable IS NOT NULL',
                'activas_sin_responsable' => 'd.estado = 1 AND r.id_responsable IS NULL',
                'inactivas' => 'd.estado = 0',
                default => throw new \InvalidArgumentException('Filtro de dependencias no válido.'),
            };
            $cargoSelect = $ocultarCoordinadores
                ? "CASE
                    WHEN LOWER(TRIM(ro.descripcion)) = 'coordinador' THEN 'SIN CARGO'
                    ELSE COALESCE(ro.descripcion, 'SIN CARGO')
                END"
                : "COALESCE(ro.descripcion, 'SIN CARGO')";
            $query = "
                SELECT 
                    d.id_dep,
                    d.nom_dep AS dependencia,
                    COALESCE(r.nom_resp, 'SIN ASIGNAR') AS responsable,
                    COALESCE(ro.descripcion, 'SIN CARGO') AS cargo,
                    COALESCE(t.telefono, 'SIN TELÉFONO') AS telefono,
                    CASE WHEN d.estado = 1 THEN 'ACTIVA' ELSE 'INACTIVA' END AS estado_dependencia,
                    CASE
                        WHEN r.id_responsable IS NULL THEN 'SIN ASIGNAR'
                        WHEN r.estado = 1 THEN 'ACTIVO'
                        ELSE 'INACTIVO'
                    END AS estado_responsable
                FROM dependencias d
                LEFT JOIN cargo c ON c.id_dep = d.id_dep AND c.estado = 1
                LEFT JOIN responsables r ON r.id_responsable = c.id_responsable AND r.estado = 1
                LEFT JOIN roles ro ON ro.id_rol = r.id_rol
                LEFT JOIN telefonos t ON t.id_proveedor = r.id_responsable AND t.estado = 1
                WHERE {$where}
                ORDER BY d.nom_dep ASC
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando dependencias para el reporte: ' . $e->getMessage());
        }
    }

    public function buscarDependenciasParaReporte(int $estadoRegistro, string $nombre, int $anio): array
    {
        try {
            $nombre = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($nombre));
            $query = "
                SELECT d.id_dep, d.nom_dep
                FROM dependencias d
                WHERE d.estado = 1
                AND d.nom_dep LIKE :nombre ESCAPE '!'
                AND EXISTS (
                    SELECT 1 FROM requerimiento r
                    JOIN anio_fiscal af ON af.id_aniof = r.id_aniof
                    JOIN detalle_req dr ON dr.id_req = r.id_req AND dr.cant_mes > 0
                    WHERE r.id_dep = d.id_dep
                    AND r.estado_registro = :estado_registro
                    AND af.anio = :anio
                    AND (af.activo = 0 OR r.estado = 1)
                )
                ORDER BY d.nom_dep ASC
                LIMIT 20
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute([
                ':nombre' => '%' . $nombre . '%',
                ':estado_registro' => $estadoRegistro,
                ':anio' => $anio,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error buscando dependencias para el reporte: ' . $e->getMessage());
        }
    }

    public function hasReqReportData(int $idDep, bool $incluirNoEnviados = false, int $anio = 0): bool
    {
        try {
            $estadoRegistro = $incluirNoEnviados ? 0 : 1;
            $stmt = $this->conex->prepare(
                "SELECT 1
                FROM requerimiento r
                JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                JOIN detalle_req dr ON dr.id_req = r.id_req
                WHERE r.id_dep = ?
                AND dr.cant_mes > 0
                AND af.anio = ?
                AND (af.activo = 0 OR r.estado = 1)
                AND r.estado_registro = ?
                LIMIT 1"
            );
            $stmt->execute([$idDep, $anio, $estadoRegistro]);
            return (bool)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error verificando datos del requerimiento: ' . $e->getMessage());
        }
    }

    private function getReqReportContext(int $idDep, bool $incluirNoEnviados, int $anio = 0): array
    {
        $estadoRegistro = $incluirNoEnviados ? 0 : 1;
        $stmt = $this->conex->prepare(
            "SELECT d.nom_dep AS dependencia, af.anio AS anio
            FROM dependencias d
            JOIN requerimiento r ON r.id_dep = d.id_dep
            JOIN anio_fiscal af ON af.id_aniof = r.id_aniof
            JOIN detalle_req dr ON dr.id_req = r.id_req AND dr.cant_mes > 0
            WHERE d.id_dep = ?
            AND r.estado_registro = ?
            AND af.anio = ?
            AND (af.activo = 0 OR r.estado = 1)
            LIMIT 1"
        );
        $stmt->execute([$idDep, $estadoRegistro, $anio]);
        $context = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$context) {
            throw new \RuntimeException('No se encontró el año fiscal y requerimiento para generar el informe.');
        }
        return $context;
    }

    // ===================== CONSULTAS BASE =====================

    private function executeGetReqReport($id = '', bool $incluirNoEnviados = false, ?int $anio = null)
    {
        $anio = $anio !== null ? (int)$anio : (int)$this->getActiveFiscalYear();
        // Año no activo: los cálculos se hacen con la tasa BCV vigente (más reciente).
        $esAnioActivo = $anio === (int)$this->getActiveFiscalYear();
        $joinTasa = $esAnioActivo
            ? 'JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa'
            : 'JOIN tasa_bcv tb ON tb.id_tasa = COALESCE('
                . '(SELECT t.id_tasa FROM tasa_bcv t WHERE t.estado = 1 ORDER BY t.fecha_reg DESC, t.id_tasa DESC LIMIT 1)'
                . ', (SELECT t.id_tasa FROM tasa_bcv t ORDER BY t.fecha_reg DESC, t.id_tasa DESC LIMIT 1))';
        $filtroAnio = "af.anio = {$anio} AND (af.activo = 0 OR r.estado = 1)";
        $exprUnidad = $this->exprUnidad('prod.nom_pres');

        $params = [];
        if (!empty($id)) {
            $estadoRegistro = $incluirNoEnviados ? 0 : 1;
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
                    p.descripcion AS partida,
                    prod.nom_pres AS Descripcion,
                    COALESCE(um.unidad, {$exprUnidad}) AS unidad,
                    prod.precio AS precio,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 1 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ene,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 2 THEN req_valido.cant_mes ELSE 0 END), 0) AS Feb,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 3 THEN req_valido.cant_mes ELSE 0 END), 0) AS Mar,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 4 THEN req_valido.cant_mes ELSE 0 END), 0) AS Abr,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 5 THEN req_valido.cant_mes ELSE 0 END), 0) AS May,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 6 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jun,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 7 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jul,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 8 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ago,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 9 THEN req_valido.cant_mes ELSE 0 END), 0) AS Sep,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 10 THEN req_valido.cant_mes ELSE 0 END), 0) AS Oct,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 11 THEN req_valido.cant_mes ELSE 0 END), 0) AS Nov,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 12 THEN req_valido.cant_mes ELSE 0 END), 0) AS Dic,
                    COALESCE(SUM(req_valido.cant_mes), 0) AS cantidad_Total,
                    (prod.precio * req_valido.tasa_bcv_usd) as precio,
                    COALESCE(SUM(req_valido.cant_mes * prod.precio), 0) AS Total_precio_dolares,
                    COALESCE(SUM(req_valido.cant_mes * prod.precio * req_valido.tasa_bcv_usd), 0) AS Total_precio
                FROM presupuesto prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN unidades_medida um ON um.id_unidad = prod.id_unidad
                LEFT JOIN (
                    SELECT 
                        dr.id_pres, 
                        dr.mes, 
                        dr.cant_mes, 
                        tb.tasa_bcv_usd
                    FROM detalle_req dr
                    JOIN requerimiento r ON dr.id_req = r.id_req
                    JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                    {$joinTasa}
                    WHERE {$filtroAnio} AND r.estado_registro = {$estadoRegistro} AND r.id_dep = ?
                ) AS req_valido ON prod.id_pres = req_valido.id_pres
                GROUP BY 
                    p.cod_partida, 
                    p.descripcion,
                    prod.id_pres, 
                    prod.nom_pres, 
                    prod.precio,
                    um.unidad
                HAVING COALESCE(SUM(req_valido.cant_mes), 0) > 0
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_pres ASC";
            $params = [$id];
        } else {
            $query = "
                SELECT 
                    p.cod_partida AS codigo,
                    p.descripcion AS partida,
                    prod.nom_pres AS Descripcion,
                    COALESCE(um.unidad, {$exprUnidad}) AS unidad,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 1 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ene,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 2 THEN req_valido.cant_mes ELSE 0 END), 0) AS Feb,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 3 THEN req_valido.cant_mes ELSE 0 END), 0) AS Mar,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 4 THEN req_valido.cant_mes ELSE 0 END), 0) AS Abr,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 5 THEN req_valido.cant_mes ELSE 0 END), 0) AS May,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 6 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jun,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 7 THEN req_valido.cant_mes ELSE 0 END), 0) AS Jul,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 8 THEN req_valido.cant_mes ELSE 0 END), 0) AS Ago,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 9 THEN req_valido.cant_mes ELSE 0 END), 0) AS Sep,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 10 THEN req_valido.cant_mes ELSE 0 END), 0) AS Oct,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 11 THEN req_valido.cant_mes ELSE 0 END), 0) AS Nov,
                    COALESCE(SUM(CASE WHEN req_valido.mes = 12 THEN req_valido.cant_mes ELSE 0 END), 0) AS Dic,
                    (prod.precio * req_valido.tasa_bcv_usd) as precio,
                    prod.precio as precio_dolares,
                    COALESCE((SUM(req_valido.cant_mes) * prod.precio), 0) as Total_precio_dolares,
                    COALESCE(SUM(req_valido.cant_mes), 0) AS cantidad_Total,
                    COALESCE(SUM(req_valido.cant_mes * prod.precio * req_valido.tasa_bcv_usd), 0) AS Total_precio
                FROM presupuesto prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN unidades_medida um ON um.id_unidad = prod.id_unidad
                LEFT JOIN (
                    SELECT 
                        dr.id_pres, 
                        dr.mes, 
                        dr.cant_mes, 
                        tb.tasa_bcv_usd
                    FROM detalle_req dr
                    JOIN requerimiento r ON dr.id_req = r.id_req
                    JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
                    {$joinTasa}
                    WHERE {$filtroAnio} AND r.estado_registro = 1
                ) AS req_valido ON prod.id_pres = req_valido.id_pres
                GROUP BY 
                    p.cod_partida, 
                    p.descripcion,
                    prod.id_pres, 
                    prod.nom_pres, 
                    prod.precio,
                    um.unidad
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_pres ASC";
        }

        try {
            $stmt = $this->conex->prepare($query);
            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando requerimientos: ' . $e->getMessage());
        }
    }

    private function executeGetProReport(
        array $idPartidas = [],
        array $unidades = [],
        ?float $precioMin = null,
        ?float $precioMax = null
    ) {
        try {
            $condiciones = [];
            $params = [];
            if ($idPartidas) {
                $placeholders = implode(',', array_fill(0, count($idPartidas), '?'));
                $condiciones[] = "prod.id_partida IN ({$placeholders})";
                $params = array_merge($params, $idPartidas);
            }

            $exprUnidad = $this->exprUnidad('prod.nom_pres');
            if ($unidades) {
                $placeholders = implode(',', array_fill(0, count($unidades), '?'));
                $condiciones[] = "UPPER(TRIM(COALESCE(um.unidad, {$exprUnidad}))) IN ({$placeholders})";
                $params = array_merge($params, array_values($unidades));
            }

            if ($precioMin !== null) {
                $condiciones[] = 'prod.precio >= ?';
                $params[] = $precioMin;
            }
            if ($precioMax !== null) {
                $condiciones[] = 'prod.precio <= ?';
                $params[] = $precioMax;
            }

            $whereSql = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
            $query = "
                SELECT 
                    p.descripcion AS partida,
                    p.cod_partida AS codigo,
                    prod.nom_pres AS Descripcion,
                    COALESCE(um.unidad, {$exprUnidad}) AS unidad,
                    prod.precio AS precio_dolares,
                    COALESCE(prod.precio * tb.tasa_bcv_usd, prod.precio) AS precio_bolivares
                FROM presupuesto prod
                JOIN partidas p ON prod.id_partida = p.id_partida
                LEFT JOIN unidades_medida um ON um.id_unidad = prod.id_unidad
                LEFT JOIN tasa_bcv tb ON tb.estado = 1
                {$whereSql}
                ORDER BY 
                    p.cod_partida ASC, 
                    prod.nom_pres ASC";
            $stmt = $this->conex->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error consultando productos: ' . $e->getMessage());
        }
    }

    private function getActiveFiscalYear(): string
    {
        $year = $this->conex->query('SELECT anio FROM anio_fiscal WHERE activo = 1 LIMIT 1')->fetchColumn();
        if (!$year) {
            throw new \RuntimeException('No hay un año fiscal activo para generar el reporte.');
        }
        return (string)$year;
    }
}