<?php

namespace App\PracticaCrud\Model;

use App\PracticaCrud\Config\Connect\ConnectDB;

class tasaBCVModel extends ConnectDB 
{
    private $conex;

    // === PROPIEDADES PRIVADAS DE LA ENTIDAD ===
    private $id;
    private $valorUsd;
    private $fechaRegistro;
    private $estado;


    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    // [R] - LEER
    public function getAll() {
        return $this->executeSelectAll();
    }

    private function executeSelectAll() {
        // Campos reales según siap_db.sql: id_tasa, fecha_reg, tasa_bcv_usd, estado
        $stmt = $this->conex->prepare("SELECT id_tasa, fecha_reg, tasa_bcv_usd, estado FROM tasa_bcv ORDER BY fecha_reg DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // [C] - CREAR
    public function add(float $valorUsd, string $fechaRegistro, int $estado = 1) {
        // Asegurar sólo 2 decimales al guardar
        $this->valorUsd = round($valorUsd, 2);
        $this->fechaRegistro = $fechaRegistro;
        // en siap_db la columna se llama 'estado'
        $this->estado = $estado;
        // Si la tasa que se registra viene como activa, desactivar otras activas
        if ($this->estado === 1) {
            $this->deactivateAllActive();
        }
        return $this->executeInsert();
    }

    private function executeInsert() {
        try {
            // Evitar duplicados para la misma fecha de registro (según lógica habitual)
            $check = $this->conex->prepare("SELECT COUNT(*) AS total FROM tasa_bcv WHERE fecha_reg = ?");
            $check->execute([$this->fechaRegistro]);
            $row = $check->fetch(\PDO::FETCH_ASSOC);
            if ($row && (int)$row['total'] > 0) {
                return false;
            }

            $query = "INSERT INTO tasa_bcv (tasa_bcv_usd, fecha_reg, estado) VALUES (?, ?, ?)";
            $stmt  = $this->conex->prepare($query);
            $stmt->bindValue(1, $this->valorUsd);
            $stmt->bindValue(2, $this->fechaRegistro);
            $stmt->bindValue(3, $this->estado, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // [U] - ACTUALIZAR
    public function update(int $id, float $valorUsd, string $fechaRegistro, int $estado) {
        $this->id = $id;
        // Asegurar sólo 2 decimales al actualizar
        $this->valorUsd = round($valorUsd, 2);
        $this->fechaRegistro = $fechaRegistro;
        $this->estado = $estado;
        // Si se marca como activa, inhabilitar otras
        if ($this->estado === 1) {
            $this->deactivateAllActive();
        }
        return $this->executeUpdate();
    }

    private function executeUpdate() {
        try {
            $query = "UPDATE tasa_bcv SET tasa_bcv_usd = ?, fecha_reg = ?, estado = ? WHERE id_tasa = ?";
            $stmt  = $this->conex->prepare($query);
            $stmt->bindValue(1, $this->valorUsd);
            $stmt->bindValue(2, $this->fechaRegistro);
            $stmt->bindValue(3, $this->estado, \PDO::PARAM_INT);
            $stmt->bindValue(4, $this->id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // [D] - ELIMINAR (Físico, tal cual tu base original)
    public function delete(int $id) {
        $this->id = $id;
        return $this->executeDelete();
    }

    private function executeDelete() {
        try {
            $stmt = $this->conex->prepare("DELETE FROM tasa_bcv WHERE id_tasa = ?");
            $stmt->bindValue(1, $this->id);
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Obtener la última tasa activa (por fecha de registro)
    public function getLatestActive() {
        $stmt = $this->conex->prepare("SELECT id_tasa, fecha_reg, tasa_bcv_usd, estado FROM tasa_bcv WHERE estado = 1 ORDER BY fecha_reg DESC, id_tasa DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Inhabilitar una tasa por id (marcar estado = 0)
    public function inactivate(int $id) {
        try {
            $stmt = $this->conex->prepare("UPDATE tasa_bcv SET estado = 0 WHERE id_tasa = ?");
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Reactivar una tasa por id (marcar estado = 1) -> inhabilita otras activas
    public function activate(int $id) {
        try {
            $this->deactivateAllActive();
            $stmt = $this->conex->prepare("UPDATE tasa_bcv SET estado = 1 WHERE id_tasa = ?");
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Desactivar todas las tasas activas (helper interno)
    private function deactivateAllActive() {
        try {
            $stmt = $this->conex->prepare("UPDATE tasa_bcv SET estado = 0 WHERE estado = 1");
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }
}