<?php

namespace App\PracticaCrud\Model;

use App\PracticaCrud\Config\Connect\ConnectDB;

class anioFiscalModel extends ConnectDB
{
    private $conex;

    // === PROPIEDADES PRIVADAS DE LA ENTIDAD ===
    private $id;
    private $anio;
    private $activo;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    // [R] - LEER
    public function getAll()
    {
        return $this->executeSelectAll();
    }

    private function executeSelectAll()
    {
        // Mantener compatibilidad con el JS: el JS espera 'anio_fiscal'
        $stmt = $this->conex->prepare(
            "SELECT id_aniof AS id_anioFis, anio AS anio_fiscal, activo
             FROM anio_fiscal
             ORDER BY anio DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // [C] - CREAR
    public function add(string $anioFiscal, int $activo = 1)
    {
        $this->anio = $anioFiscal; // mapea al campo real 'anio'
        $this->activo = $activo;
        return $this->executeInsert();
    }

    private function executeInsert()
    {
        try {
            // Evitar duplicados del mismo año fiscal (solo 1 registro por año)
            $check = $this->conex->prepare("SELECT COUNT(*) AS total FROM anio_fiscal WHERE anio = ?");
            $check->execute([$this->anio]);
            $row = $check->fetch(\PDO::FETCH_ASSOC);
            if ($row && (int)$row['total'] > 0) {
                return false;
            }

            $query = "INSERT INTO anio_fiscal (anio, activo) VALUES (?, ?)";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $this->anio);
            $stmt->bindValue(2, $this->activo, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // [U] - ACTUALIZAR
    public function update(int $id, string $anioFiscal, int $activo)
    {
        $this->id = $id;
        $this->anio = $anioFiscal;
        $this->activo = $activo;
        return $this->executeUpdate();
    }

    private function executeUpdate()
    {
        try {
            $query = "UPDATE anio_fiscal SET anio = ?, activo = ? WHERE id_aniof = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $this->anio);
            $stmt->bindValue(2, $this->activo, \PDO::PARAM_INT);
            $stmt->bindValue(3, $this->id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Obtener el año fiscal activo (si hay uno)
    public function getActive()
    {
        return $this->executeGetActive();
    }

    private function executeGetActive()
    {
        $stmt = $this->conex->prepare("SELECT id_aniof AS id_anioFis, anio AS anio_fiscal, activo FROM anio_fiscal WHERE activo = 1 ORDER BY anio DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Activar un año fiscal (desactiva otros)
    public function activate(int $id)
    {
        return $this->executeActivate($id);
    }

    private function executeActivate(int $id)
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

    // Inhabilitar (marcar activo = 0)
    public function inactivate(int $id)
    {
        return $this->executeInactivate($id);
    }

    private function executeInactivate(int $id)
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
