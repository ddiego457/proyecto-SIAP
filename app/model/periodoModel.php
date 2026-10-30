<?php
namespace EquipoSiap\Siap\model;
use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;

class periodoModel extends ConnectDB
{
    use ValidationTrait;

    private $conex;
    private $id;
    private $fechaInicio;
    private $fechaFin;
    private $anioFiscalId;

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
    }
    // Método para obtener años fiscales activos (CORREGIDO)
    public function getAniosFiscalesActivos() {
        return $this->executeGetAniosFiscalesActivos();
    }

    private function executeGetAniosFiscalesActivos(): array
    {

        try {
            $sql = "SELECT id_aniof, anio FROM anio_fiscal WHERE activo = 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }


    // Devuelve el año fiscal por id (útil para diagnósticos)
    public function getAnioFiscalById(int $id)
    {
        return $this->executeGetAnioFiscalById($id);
    }

    private function executeGetAnioFiscalById(int $id): ?array
    {

        try {
            $stmt = $this->conex->prepare("SELECT id_aniof, anio FROM anio_fiscal WHERE id_aniof = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }


    // Método auxiliar para parsear fechas válidas en formatos esperados
    private function parseDate(string $fecha): ?\DateTime
    {
        $formats = ['Y-m-d', 'd/m/Y', 'j/n/Y', 'j/m/Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $fecha);
            if ($dateTime && $dateTime->format($format) === $fecha) {
                return $dateTime;
            }
        }

        return null;
    }

    // Método para validar rangos de fechas
    public function isValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)
    {
        return $this->executeIsValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin);
    }

    private function executeIsValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin): bool
    {

        try {
            $inicio = $this->parseDate($fechaInicio);
            $fin = $this->parseDate($fechaFin);

            if (!$inicio || !$fin) {
                return false;
            }

            if ($inicio > $fin) {
                return false;
            }

            $sql = "SELECT anio FROM anio_fiscal WHERE id_aniof = ? AND activo = 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$anioFiscalId]);
            $anioFiscal = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$anioFiscal) {
                return false;
            }

            $anio = (string)$anioFiscal['anio'];
            return ($inicio->format('Y') === $anio && $fin->format('Y') === $anio);
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function getAll() {
        return $this->executeGetAll();
    }

    private function executeGetAll() {
        try {
            // Columnas que el JS (periodo.js) espera:
            // id_periodo, anio_fiscal_id, fecha_inicio, fecha_fin, activo
            $stmt = $this->conex->prepare("SELECT id_periodo, id_aniof AS anio_fiscal_id, per_inicio AS fecha_inicio, per_fin AS fecha_fin, activo FROM periodos_entrega WHERE activo = 1 OR activo = 0");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }


    // Validación pública para evitar solapamiento de periodos en el mismo año fiscal
    public function existsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, ?int $excludeId = null): bool
    {
        return $this->executeExistsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, $excludeId);
    }

    private function executeExistsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, ?int $excludeId = null): bool
    {
        $query = "SELECT 1 FROM periodos_entrega
                  WHERE id_aniof = ?
                    AND per_inicio <= ?
                    AND per_fin >= ?";
        $params = [$anioFiscalId, $fechaFin, $fechaInicio];
        if ($excludeId !== null) {
            $query .= " AND id_periodo <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->conex->prepare($query);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // Validación: ¿ya hay un periodo ACTIVO para este año fiscal?
    public function hasActivePeriodForYear(int $anioFiscalId): bool
    {
        $stmt = $this->conex->prepare("SELECT 1 FROM periodos_entrega WHERE id_aniof = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$anioFiscalId]);
        return (bool)$stmt->fetchColumn();
    }

    public function add($fechaInicio, $fechaFin, $anioFiscalId) {
        return $this->executeAdd($fechaInicio, $fechaFin, $anioFiscalId);
    }

    private function executeAdd($fechaInicio, $fechaFin, $anioFiscalId) {
        try {
            // Evitar solapamiento de periodos en el mismo año fiscal
            if ($this->existsOverlapping($fechaInicio, $fechaFin, $anioFiscalId)) {
                return false;
            }

            // Si ya hay un periodo activo en ese año, desactivarlo antes de insertar el nuevo
            if ($this->hasActivePeriodForYear($anioFiscalId)) {
                $stmt = $this->conex->prepare("UPDATE periodos_entrega SET activo = 0 WHERE id_aniof = ? AND activo = 1");
                $stmt->execute([$anioFiscalId]);
            }

            // CORREGIDO: Los campos deben coincidir con tu tabla
            $query = "INSERT INTO periodos_entrega (per_inicio, per_fin, id_aniof, activo) VALUES (?, ?, ?, 1)";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $fechaInicio);
            $stmt->bindValue(2, $fechaFin);
            $stmt->bindValue(3, $anioFiscalId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function update($id, $fechaInicio, $fechaFin, $anioFiscalId) {
        return $this->executeUpdate($id, $fechaInicio, $fechaFin, $anioFiscalId);
    }

    private function executeUpdate($id, $fechaInicio, $fechaFin, $anioFiscalId) {
        try {
            $query = "UPDATE periodos_entrega SET per_inicio = ?, per_fin = ?, id_aniof = ? WHERE id_periodo = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $fechaInicio);
            $stmt->bindValue(2, $fechaFin);
            $stmt->bindValue(3, $anioFiscalId, \PDO::PARAM_INT);
            $stmt->bindValue(4, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }



    // Activar un periodo y desactivar otros del mismo año fiscal
    public function activate($id) {
        return $this->executeActivate($id);
    }

    private function executeActivate($id) {
        try {
            // Obtener id_aniof del periodo
            $stmt = $this->conex->prepare("SELECT id_aniof FROM periodos_entrega WHERE id_periodo = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return false;
            $anioId = (int)$row['id_aniof'];

            // Desactivar otros periodos activos en ese año
            $stmt2 = $this->conex->prepare("UPDATE periodos_entrega SET activo = 0 WHERE id_aniof = ? AND activo = 1");
            $stmt2->bindValue(1, $anioId, \PDO::PARAM_INT);
            $stmt2->execute();

            // Activar el seleccionado
            $stmt3 = $this->conex->prepare("UPDATE periodos_entrega SET activo = 1 WHERE id_periodo = ?");
            $stmt3->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt3->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function executeDelete($id){
        $stmt = $this->conex->prepare("UPDATE periodos_entrega SET activo = 0
                WHERE id_periodo = ?");
        $stmt->bindValue(1, $id);
        $stmt->execute();
    }

    public function inactivate($id) {
        return $this->executeInactivate($id);
    }

    private function executeInactivate($id) {
        // delete e inactivate hacen lo mismo: dejamos una sola operación en executeDelete
        return $this->executeDelete($id);
    }

    // Obtener el periodo activo más reciente
    public function getLatestActive() {
        return $this->executeGetLatestActive();
    }

    private function executeGetLatestActive() {
        try {
            $stmt = $this->conex->prepare("SELECT id_periodo, id_aniof, per_inicio AS per_ini, per_fin AS per_fin, activo FROM periodos_entrega WHERE activo = 1 ORDER BY per_inicio DESC LIMIT 1");
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return null;
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // AÑOS FISCALES (antes en anioFiscalModel; gestionados desde el modal
    // de Periodos: ?url=periodo&type=main)
    // ════════════════════════════════════════════════════════════════════

    public function getAllAniosFiscales() {
        return $this->executeGetAllAniosFiscales();
    }

    private function executeGetAllAniosFiscales() {
        try {
            // Mantener compatibilidad con el JS: el JS espera 'id_anioFis' y 'anio_fiscal'
            $stmt = $this->conex->prepare(
                "SELECT id_aniof AS id_anioFis, anio AS anio_fiscal, activo
                 FROM anio_fiscal
                 ORDER BY anio DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    // Validación pública para evitar duplicados por año
    public function existsAnioFiscalByAnio(string $anioFiscal, ?int $excludeId = null): bool
    {
        return $this->executeExistsAnioFiscalByAnio($anioFiscal, $excludeId);
    }

    private function executeExistsAnioFiscalByAnio(string $anioFiscal, ?int $excludeId = null): bool
    {
        try {
            $query = "SELECT 1 FROM anio_fiscal WHERE anio = ?";
            $params = [$anioFiscal];
            if ($excludeId !== null) {
                $query .= " AND id_aniof <> ?";
                $params[] = $excludeId;
            }
            $stmt = $this->conex->prepare($query);
            $stmt->execute($params);
            return (bool)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // [C] - CREAR AÑO FISCAL
    // El nuevo año fiscal se crea SIEMPRE como activo y se desactivan los demás.
    public function addAnioFiscal(string $anioFiscal) {
        return $this->executeAddAnioFiscal($anioFiscal);
    }

    private function executeAddAnioFiscal(string $anioFiscal): bool
    {
        try {
            // Evitar duplicados del mismo año fiscal (solo 1 registro por año)
            if ($this->existsAnioFiscalByAnio($anioFiscal)) {
                return false;
            }

            $this->conex->beginTransaction();

            // El nuevo año fiscal queda como el único activo: se desactivan los demás
            $stmt = $this->conex->prepare("UPDATE anio_fiscal SET activo = 0 WHERE activo = 1");
            $stmt->execute();

            $stmt2 = $this->conex->prepare("INSERT INTO anio_fiscal (anio, activo) VALUES (?, 1)");
            $stmt2->bindValue(1, $anioFiscal);
            $result = $stmt2->execute();

            $this->conex->commit();
            return $result;
        } catch (\PDOException $e) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return false;
        }
    }

    // [U] - ACTUALIZAR AÑO FISCAL ($activo = null conserva el estado actual)
    public function updateAnioFiscal(int $id, string $anioFiscal, ?int $activo = null) {
        return $this->executeUpdateAnioFiscal($id, $anioFiscal, $activo);
    }

    private function executeUpdateAnioFiscal(int $id, string $anioFiscal, ?int $activo): bool
    {
        try {
            $this->conex->beginTransaction();

            // Si se marca como activo, quita el activo a los demás (solo 1 activo a la vez)
            if ($activo === 1) {
                $stmt = $this->conex->prepare("UPDATE anio_fiscal SET activo = 0 WHERE activo = 1 AND id_aniof <> ?");
                $stmt->bindValue(1, $id, \PDO::PARAM_INT);
                $stmt->execute();
            }

            if ($activo === null) {
                $stmt2 = $this->conex->prepare("UPDATE anio_fiscal SET anio = ? WHERE id_aniof = ?");
                $stmt2->bindValue(1, $anioFiscal);
                $stmt2->bindValue(2, $id, \PDO::PARAM_INT);
            } else {
                $stmt2 = $this->conex->prepare("UPDATE anio_fiscal SET anio = ?, activo = ? WHERE id_aniof = ?");
                $stmt2->bindValue(1, $anioFiscal);
                $stmt2->bindValue(2, $activo, \PDO::PARAM_INT);
                $stmt2->bindValue(3, $id, \PDO::PARAM_INT);
            }
            $result = $stmt2->execute();

            $this->conex->commit();
            return $result;
        } catch (\PDOException $e) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return false;
        }
    }

    // Activar un año fiscal (desactiva los demás)
    public function activateAnioFiscal(int $id) {
        return $this->executeActivateAnioFiscal($id);
    }

    private function executeActivateAnioFiscal(int $id): bool
    {
        try {
            // Desactivar todos
            $stmt = $this->conex->prepare("UPDATE anio_fiscal SET activo = 0 WHERE activo = 1");
            $stmt->execute();
            // Activar el seleccionado
            $stmt2 = $this->conex->prepare("UPDATE anio_fiscal SET activo = 1 WHERE id_aniof = ?");
            $stmt2->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt2->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Inhabilitar un año fiscal (marcar activo = 0)
    public function inactivateAnioFiscal(int $id) {
        return $this->executeInactivateAnioFiscal($id);
    }

    private function executeInactivateAnioFiscal(int $id): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE anio_fiscal SET activo = 0 WHERE id_aniof = ?");
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

}
?>