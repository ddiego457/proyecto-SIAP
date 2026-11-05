<?php

namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;

class tasaBCVModel extends ConnectDB 
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

    // [R] - LEER
    public function getAll() {
        return $this->executeSelectAll();
    }

    // Validación pública para evitar duplicados por fecha
    public function existsByFecha(string $fechaRegistro): bool
    {
        return $this->executeExistsByFecha($fechaRegistro);
    }

    // [C] - CREAR
    // Devuelve {success, message}: el controlador lo reenvía tal cual al JS.
    public function add(string $valorUsd, string $fechaRegistro, int $estado = 1): array
    {
        $error = $this->validar('tasa', $valorUsd);
        if ($error === null) {
            $error = $this->validar('fecha_registro', $fechaRegistro);
        }
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        return $this->executeInsert(round((float)$valorUsd, 2), $fechaRegistro, $estado);
    }

    // [U] - ACTUALIZAR
    // Devuelve bool: el controlador descarta el mensaje y el JS lee res == true.
    public function update(int $id, string $valorUsd, string $fechaRegistro, int $estado): bool
    {
        $error = $this->validar('tasa', $valorUsd);
        if ($error === null) {
            $error = $this->validar('fecha_registro', $fechaRegistro);
        }
        if ($error !== null) {
            return false;
        }

        return $this->executeUpdate($id, round((float)$valorUsd, 2), $fechaRegistro, $estado);
    }

    // [D] - ELIMINAR (Físico, tal cual tu base original)
    public function delete(int $id): bool
    {
        return $this->executeDelete($id);
    }

    // Obtener la última tasa activa (por fecha de registro)
    public function getLatestActive() {
        return $this->executeGetLatestActive();
    }

    // Inhabilitar una tasa por id (marcar estado = 0)
    public function inactivate(int $id): bool
    {
        return $this->executeInactivate($id);
    }

    // Reactivar una tasa por id (marcar estado = 1) -> inhabilita otras activas
    public function activate(int $id): bool
    {
        return $this->executeActivate($id);
    }

    // ==================================================================
    // MÉTODOS PRIVADOS (implementación y acceso a BD)
    // ==================================================================

    private function executeSelectAll() {
        // Campos reales según siap_db.sql: id_tasa, fecha_reg, tasa_bcv_usd, estado
        $stmt = $this->conex->prepare("SELECT id_tasa, fecha_reg, tasa_bcv_usd, estado FROM tasa_bcv ORDER BY fecha_reg DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function executeExistsByFecha(string $fechaRegistro): bool
    {
        $stmt = $this->conex->prepare("SELECT 1 FROM tasa_bcv WHERE fecha_reg = ?");
        $stmt->execute([$fechaRegistro]);
        return (bool)$stmt->fetchColumn();
    }

    private function executeInsert(float $valorUsd, string $fechaRegistro, int $estado): array
    {
        try {
            // Evitar duplicados para la misma fecha de registro (antes de
            // desactivar las demás: así un duplicado no deja la BD sin tasa activa)
            if ($this->executeExistsByFecha($fechaRegistro)) {
                return ['success' => false, 'message' => 'Ya existe una tasa registrada para esa fecha.'];
            }

            // Si la tasa que se registra viene como activa, desactivar otras activas
            if ($estado === 1) {
                $this->deactivateAllActive();
            }

            $query = "INSERT INTO tasa_bcv (tasa_bcv_usd, fecha_reg, estado) VALUES (?, ?, ?)";
            $stmt  = $this->conex->prepare($query);
            $stmt->bindValue(1, $valorUsd);
            $stmt->bindValue(2, $fechaRegistro);
            $stmt->bindValue(3, $estado, \PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return ['success' => false, 'message' => 'Error al registrar (duplicado o error BD).'];
            }

            return ['success' => true, 'message' => 'Tasa BCV registrada'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Error al registrar (duplicado o error BD).'];
        }
    }

    private function executeUpdate(int $id, float $valorUsd, string $fechaRegistro, int $estado): bool
    {
        try {
            // Si se marca como activa, inhabilitar otras
            if ($estado === 1) {
                $this->deactivateAllActive();
            }

            $query = "UPDATE tasa_bcv SET tasa_bcv_usd = ?, fecha_reg = ?, estado = ? WHERE id_tasa = ?";
            $stmt  = $this->conex->prepare($query);
            $stmt->bindValue(1, $valorUsd);
            $stmt->bindValue(2, $fechaRegistro);
            $stmt->bindValue(3, $estado, \PDO::PARAM_INT);
            $stmt->bindValue(4, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function executeDelete(int $id): bool
    {
        try {
            $stmt = $this->conex->prepare("DELETE FROM tasa_bcv WHERE id_tasa = ?");
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function executeGetLatestActive() {
        $stmt = $this->conex->prepare("SELECT id_tasa, fecha_reg, tasa_bcv_usd, estado FROM tasa_bcv WHERE estado = 1 ORDER BY fecha_reg DESC, id_tasa DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function executeInactivate(int $id): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE tasa_bcv SET estado = 0 WHERE id_tasa = ?");
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function executeActivate(int $id): bool
    {
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
    private function deactivateAllActive(): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE tasa_bcv SET estado = 0 WHERE estado = 1");
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }
}
