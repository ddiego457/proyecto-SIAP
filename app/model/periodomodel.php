<?php
namespace App\PracticaCrud\Model;
use App\PracticaCrud\Config\Connect\ConnectDB;

class periodoModel extends ConnectDB
{
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
            // Para que el DataTables (periodo.js) reciba las claves esperadas:
            // id_periodo, id_aniof, per_ini, per_fin y activo.
            $stmt = $this->conex->prepare("SELECT id_periodo, id_aniof, per_inicio AS per_ini, per_fin AS per_fin, activo FROM periodos_entrega WHERE activo = 1 OR activo = 0");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }


    public function add($fechaInicio, $fechaFin, $anioFiscalId) {
        return $this->executeAdd($fechaInicio, $fechaFin, $anioFiscalId);
    }

    private function executeAdd($fechaInicio, $fechaFin, $anioFiscalId) {
        try {
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

}
?>