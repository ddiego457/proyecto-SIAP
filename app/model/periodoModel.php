<?php
namespace EquipoSiap\Siap\model;
use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;

class periodoModel extends ConnectDB
{
    use ValidationTrait;

    private $conex;

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    // ==================================================================
    // MÉTODOS PÚBLICOS (Controladores de flujo)
    // Regla: solo validan (ValidationTrait) y delegan en privados.
    // Los privados son los únicos que tocan la BD.
    // ==================================================================

    // Método para obtener años fiscales activos
    public function getAniosFiscalesActivos() {
        return $this->executeGetAniosFiscalesActivos();
    }

    // Devuelve el año fiscal por id (útil para diagnósticos)
    public function getAnioFiscalById(int $id)
    {
        return $this->executeGetAnioFiscalById($id);
    }

    // Valida que el rango de fechas sea válido y pertenezca al año fiscal
    public function isValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)
    {
        return $this->executeIsValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin);
    }

    public function getAll() {
        return $this->executeGetAll();
    }

    // Validación pública para evitar solapamiento de periodos en el mismo año fiscal
    public function existsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, ?int $excludeId = null): bool
    {
        return $this->executeExistsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, $excludeId);
    }

    // Validación: ¿ya hay un periodo ACTIVO para este año fiscal?
    public function hasActivePeriodForYear(int $anioFiscalId): bool
    {
        return $this->executeHasActivePeriodForYear($anioFiscalId);
    }

    // Validación: ¿el año fiscal tiene periodos de entrega asociados
    // (activos o inactivos)? Si es así no se puede modificar el año.
    public function hasPeriodsForYear(int $anioFiscalId): bool
    {
        return $this->executeHasPeriodsForYear($anioFiscalId);
    }

    // [C] - CREAR PERIODO
    // Devuelve {success, message[, diagnostic]}: el controlador lo reenvía tal cual.
    public function add($fechaInicio, $fechaFin, $anioFiscalId): array
    {
        $error = $this->validar('fecha_periodo', $fechaInicio, ['campo' => 'La fecha de inicio']);
        if ($error === null) {
            $error = $this->validar('fecha_periodo', $fechaFin, ['campo' => 'La fecha de fin']);
        }
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        return $this->executeAdd($fechaInicio, $fechaFin, $anioFiscalId);
    }

    // [U] - ACTUALIZAR PERIODO
    // Devuelve {success, message}: el controlador lo reenvía tal cual.
    public function update($id, $fechaInicio, $fechaFin, $anioFiscalId): array
    {
        $error = $this->validar('fecha_periodo', $fechaInicio, ['campo' => 'La fecha de inicio']);
        if ($error === null) {
            $error = $this->validar('fecha_periodo', $fechaFin, ['campo' => 'La fecha de fin']);
        }
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        return $this->executeUpdate($id, $fechaInicio, $fechaFin, $anioFiscalId);
    }

    // Activar un periodo y desactivar otros del mismo año fiscal
    public function activate($id) {
        return $this->executeActivate($id);
    }

    public function inactivate($id) {
        return $this->executeInactivate($id);
    }

    // Obtener el periodo activo más reciente
    public function getLatestActive() {
        return $this->executeGetLatestActive();
    }

    public function getAllAniosFiscales() {
        return $this->executeGetAllAniosFiscales();
    }

    // Validación pública para evitar duplicados por año
    public function existsAnioFiscalByAnio(string $anioFiscal, ?int $excludeId = null): bool
    {
        return $this->executeExistsAnioFiscalByAnio($anioFiscal, $excludeId);
    }

    // [C] - CREAR AÑO FISCAL
    // El nuevo año fiscal se crea SIEMPRE como activo y se desactivan los demás.
    // Devuelve {success, message}: el controlador lo reenvía tal cual.
    public function addAnioFiscal(string $anioFiscal): array
    {
        $anioFiscal = trim($anioFiscal);

        $error = $this->validar('anio_fiscal', $anioFiscal);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        return $this->executeAddAnioFiscal($anioFiscal);
    }

    // [U] - ACTUALIZAR AÑO FISCAL ($activo = null conserva el estado actual)
    // Devuelve {success, message}: el controlador lo reenvía tal cual.
    public function updateAnioFiscal(int $id, string $anioFiscal, ?int $activo = null): array
    {
        $anioFiscal = trim($anioFiscal);

        $error = $this->validar('anio_fiscal', $anioFiscal);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        // Un año fiscal con periodos de entrega asociados no se puede modificar
        // (en la lista se oculta el botón de editar y aquí se bloquea el endpoint)
        if ($this->executeHasPeriodsForYear($id)) {
            return ['success' => false, 'message' => 'Este año fiscal ya tiene periodos asociados y no se puede modificar.'];
        }

        return $this->executeUpdateAnioFiscal($id, $anioFiscal, $activo);
    }

    // Activar un año fiscal (desactiva los demás)
    // Devuelve {success, message}
    public function activateAnioFiscal(int $id): array
    {
        $result = $this->executeActivateAnioFiscal($id);
        return [
            'success' => $result,
            'message' => $result ? 'Año fiscal activado' : 'Error al activar el año fiscal.'
        ];
    }

    // Inhabilitar un año fiscal (marcar activo = 0)
    // Devuelve {success, message}
    public function inactivateAnioFiscal(int $id): array
    {
        $result = $this->executeInactivateAnioFiscal($id);
        return [
            'success' => $result,
            'message' => $result ? 'Año fiscal inhabilitado' : 'Error al inhabilitar el año fiscal.'
        ];
    }

    // ==================================================================
    // MÉTODOS PRIVADOS (implementación y acceso a BD)
    // ==================================================================

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

    private function executeIsValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin): bool
    {
        try {
            $inicio = $this->parseDate((string)$fechaInicio);
            $fin = $this->parseDate((string)$fechaFin);

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

    private function executeHasActivePeriodForYear(int $anioFiscalId): bool
    {
        $stmt = $this->conex->prepare("SELECT 1 FROM periodos_entrega WHERE id_aniof = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$anioFiscalId]);
        return (bool)$stmt->fetchColumn();
    }

    private function executeHasPeriodsForYear(int $anioFiscalId): bool
    {
        try {
            $stmt = $this->conex->prepare("SELECT 1 FROM periodos_entrega WHERE id_aniof = ? LIMIT 1");
            $stmt->execute([$anioFiscalId]);
            return (bool)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            // Ante la duda se bloquea la edición (protección de datos)
            return true;
        }
    }

    private function executeAdd($fechaInicio, $fechaFin, $anioFiscalId): array
    {
        // Año fiscal válido, fechas en orden y ambas dentro del año fiscal
        if (!$this->executeIsValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)) {
            // Diagnóstico para entender por qué falla la validación
            $startYear = null;
            $endYear = null;
            try { $sd = new \DateTime((string)$fechaInicio); $startYear = $sd->format('Y'); } catch (\Exception $e) { }
            try { $ed = new \DateTime((string)$fechaFin); $endYear = $ed->format('Y'); } catch (\Exception $e) { }

            return [
                'success' => false,
                'message' => 'Fechas inválidas: la fecha de inicio debe ser anterior o igual a la fecha fin, y ambas deben corresponder al año fiscal seleccionado.',
                'diagnostic' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'startYear' => $startYear,
                    'endYear' => $endYear,
                    'fiscalYearRecord' => $this->executeGetAnioFiscalById((int)$anioFiscalId)
                ]
            ];
        }

        // Evitar solapamiento de periodos en el mismo año fiscal
        if ($this->executeExistsOverlapping($fechaInicio, $fechaFin, $anioFiscalId)) {
            return ['success' => false, 'message' => 'Ya existe un periodo que se solapa con esas fechas en el mismo año fiscal.'];
        }

        // Si ya hay un periodo activo en ese año, se desactivará automáticamente
        // Avisamos al usuario para que lo sepa
        $yaHabiaActivo = $this->executeHasActivePeriodForYear((int)$anioFiscalId);

        try {
            // Si ya hay un periodo activo en ese año, desactivarlo antes de insertar el nuevo
            if ($yaHabiaActivo) {
                $stmt = $this->conex->prepare("UPDATE periodos_entrega SET activo = 0 WHERE id_aniof = ? AND activo = 1");
                $stmt->execute([$anioFiscalId]);
            }

            // CORREGIDO: Los campos deben coincidir con tu tabla
            $query = "INSERT INTO periodos_entrega (per_inicio, per_fin, id_aniof, activo) VALUES (?, ?, ?, 1)";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $fechaInicio);
            $stmt->bindValue(2, $fechaFin);
            $stmt->bindValue(3, $anioFiscalId, \PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return ['success' => false, 'message' => 'Error al guardar el periodo (duplicado o error BD).'];
            }

            $msg = 'Periodo registrado';
            if ($yaHabiaActivo) {
                $msg .= ' (Se desactivó el periodo activo anterior de ese año fiscal).';
            }
            return ['success' => true, 'message' => $msg];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Error al guardar el periodo (duplicado o error BD).'];
        }
    }

    private function executeUpdate($id, $fechaInicio, $fechaFin, $anioFiscalId): array
    {
        // Año fiscal válido, fechas en orden y ambas dentro del año fiscal
        if (!$this->executeIsValidFiscalYearRange($anioFiscalId, $fechaInicio, $fechaFin)) {
            return [
                'success' => false,
                'message' => 'Fechas inválidas: la fecha de inicio debe ser anterior o igual a la fecha fin, y ambas deben corresponder al año fiscal seleccionado.'
            ];
        }

        // Evitar solapamiento (excluyendo el propio periodo que se edita)
        if ($this->executeExistsOverlapping($fechaInicio, $fechaFin, $anioFiscalId, (int)$id)) {
            return ['success' => false, 'message' => 'Ya existe un periodo que se solapa con esas fechas en el mismo año fiscal.'];
        }

        try {
            // Nota: el modelo no tiene lógica de auto-desactivar en update, solo en add y activate
            $query = "UPDATE periodos_entrega SET per_inicio = ?, per_fin = ?, id_aniof = ? WHERE id_periodo = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $fechaInicio);
            $stmt->bindValue(2, $fechaFin);
            $stmt->bindValue(3, $anioFiscalId, \PDO::PARAM_INT);
            $stmt->bindValue(4, $id, \PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return ['success' => false, 'message' => 'Error al actualizar el periodo.'];
            }

            return ['success' => true, 'message' => 'Periodo actualizado'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Error al actualizar el periodo.'];
        }
    }

    // Activar un periodo y desactivar otros del mismo año fiscal
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
        return $stmt->execute();
    }

    private function executeInactivate($id) {
        // delete e inactivate hacen lo mismo: dejamos una sola operación en executeDelete
        return $this->executeDelete($id);
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

    private function executeGetAllAniosFiscales() {
        try {
            // Mantener compatibilidad con el JS: el JS espera 'id_anioFis' y 'anio_fiscal'
            // 'tiene_periodo' = 1 si el año ya tiene periodo(s) de entrega asociado(s)
            // (activos o inactivos): en ese caso el JS oculta la opción de modificar.
            $stmt = $this->conex->prepare(
                "SELECT a.id_aniof AS id_anioFis,
                        a.anio AS anio_fiscal,
                        a.activo,
                        EXISTS(SELECT 1 FROM periodos_entrega p WHERE p.id_aniof = a.id_aniof) AS tiene_periodo
                 FROM anio_fiscal a
                 ORDER BY a.anio DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
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

    private function executeAddAnioFiscal(string $anioFiscal): array
    {
        try {
            // Evitar duplicados del mismo año fiscal (solo 1 registro por año)
            if ($this->executeExistsAnioFiscalByAnio($anioFiscal)) {
                return ['success' => false, 'message' => 'Ya existe un año fiscal con ese valor.'];
            }

            $this->conex->beginTransaction();

            // El nuevo año fiscal queda como el único activo: se desactivan los demás
            $stmt = $this->conex->prepare("UPDATE anio_fiscal SET activo = 0 WHERE activo = 1");
            $stmt->execute();

            $stmt2 = $this->conex->prepare("INSERT INTO anio_fiscal (anio, activo) VALUES (?, 1)");
            $stmt2->bindValue(1, $anioFiscal);
            $result = $stmt2->execute();

            $this->conex->commit();

            if (!$result) {
                return ['success' => false, 'message' => 'Error al registrar (duplicado o error BD).'];
            }

            return ['success' => true, 'message' => 'Año fiscal registrado y activado'];
        } catch (\PDOException $e) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return ['success' => false, 'message' => 'Error al registrar (duplicado o error BD).'];
        }
    }

    private function executeUpdateAnioFiscal(int $id, string $anioFiscal, ?int $activo): array
    {
        try {
            // Evitar duplicados por año (excluyendo el propio registro)
            if ($this->executeExistsAnioFiscalByAnio($anioFiscal, $id)) {
                return ['success' => false, 'message' => 'Ya existe un año fiscal con ese valor.'];
            }

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

            if (!$result) {
                return ['success' => false, 'message' => 'Error al actualizar el año fiscal.'];
            }

            return ['success' => true, 'message' => 'Año fiscal actualizado'];
        } catch (\PDOException $e) {
            if ($this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            return ['success' => false, 'message' => 'Error al actualizar el año fiscal.'];
        }
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