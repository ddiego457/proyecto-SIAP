<?php

namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;

class responsableModel extends ConnectDB
{
    private $conex;
    private $id;
    private $id_rol;
    private $nom_rep;
    private $password;
    private $estado;
    // Expresión regular de correo (acepta dominios como .com.ve)
    public $expEmail = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    // Obtener todos los responsables con rol y dependencia actual
    public function getAll()
    {
        return $this->executeGetAll();
    }

    private function executeGetAll()
    {
        $query = "SELECT r.id_responsable, r.nom_rep, r.email, r.id_rol, ro.descripcion AS rol, r.estado, COALESCE(d.nom_dep, 'Sin asignar') AS dependencia_actual
                FROM responsables r
                LEFT JOIN roles ro ON r.id_rol = ro.id_rol
                LEFT JOIN cargo cr ON r.id_responsable = cr.id_responsable AND cr.estado = 1
                LEFT JOIN dependencias d ON cr.id_dep = d.id_dep
                ORDER BY r.nom_rep ASC";
        $stmt = $this->conex->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRoles()
    {
        return $this->executeGetRoles();
    }

    private function executeGetRoles()
    {
        $stmt = $this->conex->prepare("SELECT id_rol, descripcion, estado FROM roles WHERE estado = 1 ORDER BY descripcion ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ─── Roles (mantenimiento) ─────────────────────────────────────────
    // Listado completo de roles con la cantidad de responsables activos
    public function getAllRoles()
    {
        return $this->executeGetAllRoles();
    }

    private function executeGetAllRoles()
    {
        $stmt = $this->conex->prepare(
            "SELECT ro.id_rol,
                    ro.descripcion,
                    ro.estado,
                    COUNT(CASE WHEN r.estado = 1 THEN 1 END) AS responsables
             FROM roles ro
             LEFT JOIN responsables r ON r.id_rol = ro.id_rol
             GROUP BY ro.id_rol, ro.descripcion, ro.estado
             ORDER BY ro.descripcion ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRolById(int $idRol)
    {
        return $this->executeGetRolById($idRol);
    }

    private function executeGetRolById(int $idRol)
    {
        $stmt = $this->conex->prepare("SELECT id_rol, descripcion, estado FROM roles WHERE id_rol = ? LIMIT 1");
        $stmt->execute([$idRol]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    // Verificación pública para evitar descripciones duplicadas
    public function existsRol(string $descripcion, ?int $excludeId = null): bool
    {
        return $this->executeExistsRol($descripcion, $excludeId);
    }

    private function executeExistsRol(string $descripcion, ?int $excludeId = null): bool
    {
        $query = "SELECT 1 FROM roles WHERE LOWER(TRIM(descripcion)) = LOWER(TRIM(?))";
        $params = [$descripcion];

        if ($excludeId !== null) {
            $query .= " AND id_rol <> ?";
            $params[] = $excludeId;
        }

        $stmt = $this->conex->prepare($query . " LIMIT 1");
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // Responsables activos que tienen este rol asignado
    public function countResponsablesActivos(int $idRol): int
    {
        return $this->executeCountResponsablesActivos($idRol);
    }

    private function executeCountResponsablesActivos(int $idRol): int
    {
        $stmt = $this->conex->prepare("SELECT COUNT(*) FROM responsables WHERE id_rol = ? AND estado = 1");
        $stmt->execute([$idRol]);
        return (int)$stmt->fetchColumn();
    }

    public function addRol(string $descripcion): bool
    {
        if (trim($descripcion) === '') {
            return false;
        }
        return $this->executeAddRol($descripcion);
    }

    private function executeAddRol(string $descripcion): bool
    {
        try {
            if ($this->executeExistsRol($descripcion)) {
                return false;
            }

            $stmt = $this->conex->prepare("INSERT INTO roles (descripcion, estado) VALUES (?, 1)");
            return $stmt->execute([trim($descripcion)]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function updateRol(int $idRol, string $descripcion): bool
    {
        if ($idRol <= 0 || trim($descripcion) === '') {
            return false;
        }
        return $this->executeUpdateRol($idRol, $descripcion);
    }

    private function executeUpdateRol(int $idRol, string $descripcion): bool
    {
        try {
            if ($this->executeExistsRol($descripcion, $idRol)) {
                return false;
            }

            $stmt = $this->conex->prepare("UPDATE roles SET descripcion = ? WHERE id_rol = ?");
            return $stmt->execute([trim($descripcion), $idRol]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Eliminar lógicamente un rol (estado = 0)
    public function deleteRol(int $idRol): bool
    {
        return $this->executeDeleteRol($idRol);
    }

    private function executeDeleteRol(int $idRol): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE roles SET estado = 0 WHERE id_rol = ?");
            return $stmt->execute([$idRol]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function activateRol(int $idRol): bool
    {
        return $this->executeActivateRol($idRol);
    }

    private function executeActivateRol(int $idRol): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE roles SET estado = 1 WHERE id_rol = ?");
            return $stmt->execute([$idRol]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getDependencias()
    {
        return $this->executeGetDependencias();
    }

    private function executeGetDependencias()
    {
        $stmt = $this->conex->prepare("SELECT id_dep, nom_dep FROM dependencias WHERE estado = 1 ORDER BY nom_dep ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAvailableDependencias()
    {
        return $this->executeGetAvailableDependencias();
    }

    private function executeGetAvailableDependencias()
    {
        $stmt = $this->conex->prepare(
            "SELECT d.id_dep, d.nom_dep
            FROM dependencias d
            WHERE d.estado = 1
            AND NOT EXISTS (
                SELECT 1 FROM cargo cr
                WHERE cr.id_dep = d.id_dep AND cr.estado = 1
            )
            ORDER BY d.nom_dep ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Validación pública para evitar duplicados por nombre
    public function existsByName(string $nomRep): bool
    {
        return $this->executeExistsByName($nomRep);
    }

    private function executeExistsByName(string $nomRep): bool
    {
        $stmt = $this->conex->prepare("SELECT 1 FROM responsables WHERE LOWER(TRIM(nom_rep)) = LOWER(TRIM(?))");
        $stmt->execute([$nomRep]);
        return (bool)$stmt->fetchColumn();
    }

    // Validación pública para evitar duplicados por correo (excluye un id, para editar)
    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        return $this->executeExistsByEmail($email, $excludeId);
    }

    private function executeExistsByEmail(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->conex->prepare("SELECT 1 FROM responsables WHERE email = ? AND id_responsable != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->conex->prepare("SELECT 1 FROM responsables WHERE email = ?");
            $stmt->execute([$email]);
        }
        return (bool)$stmt->fetchColumn();
    }

    // CRUD básico
    public function add(string $nomRep, string $password, int $idRol, int $idDep, string $email)
    {
        return $this->executeAdd($nomRep, $password, $idRol, $idDep, $email);
    }

    private function executeAdd(string $nomRep, string $password, int $idRol, int $idDep, string $email)
    {
        try {
            $this->conex->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conex->prepare("INSERT INTO responsables (id_rol, nom_rep, email, password, estado) VALUES (?, ?, ?, ?, 1)");
            $stmt->bindValue(1, $idRol, \PDO::PARAM_INT);
            $stmt->bindValue(2, $nomRep);
            $stmt->bindValue(3, $email);
            $stmt->bindValue(4, $hash);
            $stmt->execute();

            $idResponsable = (int)$this->conex->lastInsertId();
            $fechaInicio = date('Y-m-d');

            $stmt2 = $this->conex->prepare("INSERT INTO cargo (id_responsable, id_dep, fecha_inicio, estado) VALUES (?, ?, ?, 1)");
            $stmt2->bindValue(1, $idResponsable, \PDO::PARAM_INT);
            $stmt2->bindValue(2, $idDep, \PDO::PARAM_INT);
            $stmt2->bindValue(3, $fechaInicio);
            $res = $stmt2->execute();

            $this->conex->commit();
            return $res;
        } catch (\PDOException $e) {
            $this->conex->rollBack();
            return false;
        }
    }

    public function update(int $id, ?string $nomRep, ?string $password, ?int $idRol, ?string $email = null)
    {
        return $this->executeUpdate($id, $nomRep, $password, $idRol, $email);
    }

    private function executeUpdate(int $id, ?string $nomRep, ?string $password, ?int $idRol, ?string $email = null)
    {
        try {
            $query = "UPDATE responsables SET ";
            $params = [];
            $parts = [];

            if ($nomRep !== null && trim($nomRep) !== '') {
                $parts[] = "nom_rep = ?";
                $params[] = $nomRep;
            }
            if ($password !== null && trim($password) !== '') {
                $parts[] = "password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($idRol !== null) {
                $parts[] = "id_rol = ?";
                $params[] = $idRol;
            }
            if ($email !== null && trim($email) !== '') {
                $parts[] = "email = ?";
                $params[] = $email;
            }

            if (empty($parts)) {
                return false;
            }

            $query .= implode(', ', $parts);
            $query .= " WHERE id_responsable = ?";
            $params[] = $id;

            $stmt = $this->conex->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function activate(int $idResponsable): bool
    {
        return $this->executeActivate($idResponsable);
    }

    private function executeActivate(int $idResponsable): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE responsables SET estado = 1 WHERE id_responsable = ?");
            return $stmt->execute([$idResponsable]);
        } catch (\PDOException $e) {
            return false;
        }
    }



    // Eliminar responsable: primero cierra su cargo activo (si existe), luego elimina el responsable
    public function delete(int $idResponsable)
    {
        return $this->executeDelete($idResponsable);
    }

    private function executeDelete(int $idResponsable)
    {
        try {
            $this->conex->beginTransaction();
            $fechaDeSalida = date("Y-m-d");
            // 1. Primero, cerrar cargo activo para este responsable (setear estado a 0)
            $stmt = $this->conex->prepare("UPDATE cargo SET estado = 0, fecha_fin = ? WHERE id_responsable = ? AND estado = 1");
            $stmt->execute([$fechaDeSalida, $idResponsable]);

            // 2. Eliminar el responsable
            $stmt2 = $this->conex->prepare("UPDATE responsables SET estado = 0 WHERE id_responsable = ?;");
            $stmt2->bindValue(1, $idResponsable, \PDO::PARAM_INT);
            $res = $stmt2->execute();

            $this->conex->commit();
            // 3. Retornar éxito (el cargo ya fue cerrado en paso 1, la dependencia quedará disponible)
            return $res;
        } catch (\PDOException $e) {
            $this->conex->rollBack();
            return false;
        }
    }

    // Asignar responsable a una dependencia: cierra cargo anterior y crea uno nuevo
    // (Método público delega a método privado con el SQL real)
    public function assignToDependencia(int $idResponsable, int $idDep, string $fechaInicio)
    {
        return $this->executeAssignToDependencia($idResponsable, $idDep, $fechaInicio);
    }

    private function executeAssignToDependencia(int $idResponsable, int $idDep, string $fechaInicio)
    {
        try {
            $this->conex->beginTransaction();

            // Cerrar cargos activos del responsable (garantiza un único cargo activo por responsable)
            $stmtResp = $this->conex->prepare("UPDATE cargo SET estado = 0, fecha_fin = ? WHERE id_responsable = ? AND estado = 1");
            $stmtResp->bindValue(1, $fechaInicio);
            $stmtResp->bindValue(2, $idResponsable, \PDO::PARAM_INT);
            $stmtResp->execute();

            // Cerrar cargo actual activo para la dependencia (libera a quien la ocupe)
            $stmtDep = $this->conex->prepare("UPDATE cargo SET estado = 0, fecha_fin = ? WHERE id_dep = ? AND estado = 1");
            $stmtDep->bindValue(1, $fechaInicio);
            $stmtDep->bindValue(2, $idDep, \PDO::PARAM_INT);
            $stmtDep->execute();

            // Insertar nuevo cargo
            $stmt2 = $this->conex->prepare("INSERT INTO cargo (id_responsable, id_dep, fecha_inicio, estado) VALUES (?, ?, ?, 1)");
            $stmt2->bindValue(1, $idResponsable, \PDO::PARAM_INT);
            $stmt2->bindValue(2, $idDep, \PDO::PARAM_INT);
            $stmt2->bindValue(3, $fechaInicio);
            $res = $stmt2->execute();

            $this->conex->commit();
            return $res;
        } catch (\PDOException $e) {
            $this->conex->rollBack();
            return false;
        }
    }

    // Obtener cargos (historial) de una dependencia
    // (Método público delega a método privado con el SQL real)
    public function getCargosByDependencia(int $idDep)
    {
        return $this->executeGetCargosByDependencia($idDep);
    }

    private function executeGetCargosByDependencia(int $idDep)
    {
        try {
            $stmt = $this->conex->prepare("SELECT cr.id_cargo, cr.id_responsable, r.nom_rep, cr.fecha_inicio, cr.fecha_fin, cr.estado FROM cargo cr LEFT JOIN responsables r ON cr.id_responsable = r.id_responsable WHERE cr.id_dep = ? ORDER BY cr.fecha_inicio DESC");
            $stmt->execute([$idDep]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }


}

?>