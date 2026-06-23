<?php

namespace App\PracticaCrud\Model;

use App\PracticaCrud\Config\Connect\ConnectDB;

class responsableModel extends ConnectDB
{
    private $conex;
    private $id;
    private $id_rol;
    private $nom_rep;
    private $password;
    private $estado;

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
        $query = "SELECT r.id_responsable,
                         r.nom_rep,
                         r.id_rol,
                         ro.descripcion AS rol,
                         r.estado,
                         COALESCE(d.nom_dep, 'Sin asignar') AS dependencia_actual
                  FROM responsables r
                  LEFT JOIN roles ro ON r.id_rol = ro.id_rol
                  LEFT JOIN cargo_responsable cr ON r.id_responsable = cr.id_responsable AND cr.estado = 1
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
        $stmt = $this->conex->prepare("SELECT id_rol, descripcion FROM roles ORDER BY descripcion ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                   SELECT 1
                   FROM cargo_responsable cr
                   WHERE cr.id_dep = d.id_dep
                     AND cr.estado = 1
               )
             ORDER BY d.nom_dep ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // CRUD básico
    public function add(string $nomRep, string $password, int $idRol, int $idDep)
    {
        return $this->executeAdd($nomRep, $password, $idRol, $idDep);
    }

    private function executeAdd(string $nomRep, string $password, int $idRol, int $idDep)
    {
        try {
            $this->conex->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conex->prepare("INSERT INTO responsables (id_rol, nom_rep, password, estado) VALUES (?, ?, ?, 1)");
            $stmt->bindValue(1, $idRol, \PDO::PARAM_INT);
            $stmt->bindValue(2, $nomRep);
            $stmt->bindValue(3, $hash);
            $stmt->execute();

            $idResponsable = (int)$this->conex->lastInsertId();
            $fechaInicio = date('Y-m-d');

            $stmt2 = $this->conex->prepare("INSERT INTO cargo_responsable (id_responsable, id_dep, fecha_inicio, estado) VALUES (?, ?, ?, 1)");
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

    public function update(int $id, ?string $nomRep, ?string $password, ?int $estado, ?int $idRol)
    {
        return $this->executeUpdate($id, $nomRep, $password, $estado, $idRol);
    }

    private function executeUpdate(int $id, ?string $nomRep, ?string $password, ?int $estado, ?int $idRol)
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
            if ($estado !== null) {
                $parts[] = "estado = ?";
                $params[] = $estado;
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

            // Cerrar cargo actual activo para la dependencia
            $stmt = $this->conex->prepare("UPDATE cargo_responsable SET estado = 0, fecha_fin = ? WHERE id_dep = ? AND estado = 1");
            $stmt->bindValue(1, $fechaInicio);
            $stmt->bindValue(2, $idDep, \PDO::PARAM_INT);
            $stmt->execute();

            // Insertar nuevo cargo
            $stmt2 = $this->conex->prepare("INSERT INTO cargo_responsable (id_responsable, id_dep, fecha_inicio, estado) VALUES (?, ?, ?, 1)");
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
            $stmt = $this->conex->prepare("SELECT cr.id_cargo, cr.id_responsable, r.nom_rep, cr.fecha_inicio, cr.fecha_fin, cr.estado FROM cargo_responsable cr LEFT JOIN responsables r ON cr.id_responsable = r.id_responsable WHERE cr.id_dep = ? ORDER BY cr.fecha_inicio DESC");
            $stmt->execute([$idDep]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }


}

?>