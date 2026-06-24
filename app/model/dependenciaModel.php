<?php

namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;

class dependenciaModel extends ConnectDB
{
    // Conexión interna
    private $conex;

    // === PROPIEDADES PRIVADAS DE LA ENTIDAD (Exigidas para seguridad/encapsulación) ===
    private $id;
    private $nombreDep;
    private $contrasena;
    private $rol;
    private $estado;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    // ==========================================================
    // [R] - LEER: El controlador llama a getAll()
    // ==========================================================
    public function getAll()
    {
        return $this->executeSelectAll();
    }
    
    
    // Validación pública (API del modelo) que delega en el método privado.
    // Mantiene el encapsulamiento: el SQL real sigue siendo privado.
    public function existsByName(string $nombreDep, ?string $excludeId = null)
    {
        return $this->executeExistsByName($nombreDep, $excludeId);
    }

    // Método privado encargado del SQL de validación
    private function executeExistsByName(string $nombreDep, ?string $excludeId = null)
    {
        $query = "SELECT 1 FROM dependencias WHERE LOWER(TRIM(nom_dep)) = LOWER(TRIM(?))";
        $params = [$nombreDep];

        if ($excludeId !== null) {
            $query .= " AND id_dep <> ?";
            $params[] = $excludeId;
        }

        $stmt = $this->conex->prepare($query);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    // El método privado que de verdad ejecuta la consulta SQL
    private function executeSelectAll()
    {
        $stmt = $this->conex->prepare(
            "SELECT id_dep, nom_dep AS nombre_dep, estado
            FROM dependencias
            ORDER BY nom_dep ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ==========================================================
    // [C] - CREAR: El controlador llama a add(...)
    // ==========================================================
    public function add(string $nombreDep)
    {
        $this->nombreDep = trim($nombreDep);
        if ($this->existsByName($this->nombreDep)) {
            return false;
        }
        return $this->executeInsert();
    }

    // El método privado que de verdad toca la Base de Datos
    private function executeInsert()
    {
        try {
            $query = "INSERT INTO dependencias (nom_dep, estado) VALUES (?, 1)";
            $stmt = $this->conex->prepare($query);
            $stmt->bindValue(1, $this->nombreDep);
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // ==========================================================
    // [U] - ACTUALIZAR: El controlador llama a update(...)
    // ==========================================================
    public function update(string $id, string $nombreDep, ?int $nuevoEstado = null)
    {
        $this->id = $id;
        $this->nombreDep = trim($nombreDep);
        if ($this->existsByName($this->nombreDep, $this->id)) {
            return false;
        }
        $this->estado = $nuevoEstado;
        return $this->executeUpdate();
    }

    // El método privado que procesa la lógica dinámica de actualización SQL
    private function executeUpdate()
    {
        try {
            $query = "UPDATE dependencias SET nom_dep = ?";
            $params = [$this->nombreDep];

            if ($this->estado !== null) {
                $query .= ", estado = ?";
                $params[] = $this->estado;
            }

            $query .= " WHERE id_dep = ?";
            $params[] = $this->id;

            $stmt = $this->conex->prepare($query);
            $stmt->execute($params);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // ==========================================================
    // [D] - ELIMINAR: El controlador llama a delete(...)
    // ==========================================================
    public function delete(string $id)
    {
        // Guardamos el identificador de forma interna
        $this->id = $id;

        // Ejecutamos el borrado lógico en privado
        return $this->executeDelete();
    }

    // El método privado encargado del borrado lógico
    private function executeDelete()
    {
        try {
            $stmt = $this->conex->prepare("UPDATE dependencias SET estado = 0 WHERE id_dep = ?");
            $stmt->bindValue(1, $this->id);
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function activate(string $id)
    {
    $this->id = $id;

        // Ejecutamos el borrado lógico en privado
        return $this->executeActivate($id);
        }

    // Activar una dependencia
    private function executeActivate()
    {
        try {
            $stmt = $this->conex->prepare("UPDATE dependencias SET estado = 1 WHERE id_dep = ?");
            $stmt->bindValue(1, $this->id);
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
?>