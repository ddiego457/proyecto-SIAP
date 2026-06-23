<?php

namespace App\PracticaCrud\Model;

use App\PracticaCrud\Config\Connect\ConnectDB;

class proveedorModel extends ConnectDB
{
    private $conex;
    private $id;
    private $nombre;
    private $descripcion;
    private $estado;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getAll()
    {
        return $this->executeGetAll();
    }

    private function executeGetAll()
    {
        $stmt = $this->conex->prepare("SELECT id_proveedor, nom_prov AS nombre, descripcion, estado FROM proveedores ORDER BY nom_prov ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActive()
    {
        return $this->executeGetActive();
    }

    private function executeGetActive()
    {
        $stmt = $this->conex->prepare("SELECT id_proveedor, nom_prov AS nombre, descripcion, estado FROM proveedores WHERE estado = 1 ORDER BY nom_prov ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        return $this->executeGetById($id);
    }

    private function executeGetById(int $id)
    {
        $stmt = $this->conex->prepare("SELECT id_proveedor, nom_prov AS nombre, descripcion, estado FROM proveedores WHERE id_proveedor = ?");
        $stmt->bindValue(1, $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }


    public function add(string $nombre, ?string $descripcion = null)
    {
        return $this->executeAdd($nombre, $descripcion);
    }

    private function executeAdd(string $nombre, ?string $descripcion = null)
    {
        $this->nombre = trim($nombre);
        $this->descripcion = trim($descripcion ?? '');
        return $this->executeInsert();
    }

    private function executeInsert()
    {
        try {
            $stmt = $this->conex->prepare("INSERT INTO proveedores (nom_prov, descripcion, estado) VALUES (?, ?, 1)");
            $stmt->bindValue(1, $this->nombre);
            $stmt->bindValue(2, $this->descripcion);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function update(int $id, string $nombre, ?string $descripcion, ?int $estado)
    {
        return $this->executeUpdatePublic($id, $nombre, $descripcion, $estado);
    }

    private function executeUpdatePublic(int $id, string $nombre, ?string $descripcion, ?int $estado)
    {
        $this->id = $id;
        $this->nombre = trim($nombre);
        $this->descripcion = trim($descripcion ?? '');
        $this->estado = $estado;
        return $this->executeUpdate();
    }

    private function executeUpdate()
    {
        try {
            $query = "UPDATE proveedores SET nom_prov = ?, descripcion = ?";
            $params = [$this->nombre, $this->descripcion];

            if ($this->estado !== null) {
                $query .= ", estado = ?";
                $params[] = $this->estado;
            }

            $query .= " WHERE id_proveedor = ?";
            $params[] = $this->id;

            $stmt = $this->conex->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function delete(int $id)
    {
        $this->id = $id;
        return $this->executeDelete();
    }

    private function executeDelete()
    {
        try {
            $stmt = $this->conex->prepare("UPDATE proveedores SET estado = 0 WHERE id_proveedor = ?");
            $stmt->bindValue(1, $this->id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function activate(int $id)
    {
        return $this->executeActivate($id);
    }

    private function executeActivate(int $id)
    {
        try {
            $stmt = $this->conex->prepare("UPDATE proveedores SET estado = 1 WHERE id_proveedor = ?");
            $stmt->bindValue(1, $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function inactivate(int $id)
    {
        return $this->executeInactivate($id);
    }

    private function executeInactivate(int $id)
    {
        return $this->delete($id);
    }


    public function getContacts(int $idProveedor)
    {
        return $this->executeGetContacts($idProveedor);
    }

    private function executeGetContacts(int $idProveedor)
    {
        try {
            $stmt = $this->conex->prepare("SELECT id_telf, telefono, estado FROM contactos WHERE id_proveedor = ? ORDER BY id_telf DESC");
            $stmt->bindValue(1, $idProveedor, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }


    public function addContact(int $idProveedor, string $telefono)
    {
        return $this->executeAddContact($idProveedor, $telefono);
    }

    private function executeAddContact(int $idProveedor, string $telefono)
    {
        try {
            $stmt = $this->conex->prepare("INSERT INTO contactos (id_proveedor, telefono, estado) VALUES (?, ?, 1)");
            $stmt->bindValue(1, $idProveedor, \PDO::PARAM_INT);
            $stmt->bindValue(2, trim($telefono));
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function updateContact(int $idContacto, string $telefono, ?int $estado)
    {
        return $this->executeUpdateContact($idContacto, $telefono, $estado);
    }

    private function executeUpdateContact(int $idContacto, string $telefono, ?int $estado)
    {
        try {
            $query = "UPDATE contactos SET telefono = ?";
            $params = [trim($telefono)];

            if ($estado !== null) {
                $query .= ", estado = ?";
                $params[] = $estado;
            }

            $query .= " WHERE id_telf = ?";
            $params[] = $idContacto;

            $stmt = $this->conex->prepare($query);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function deleteContact(int $idContacto)
    {
        return $this->executeDeleteContact($idContacto);
    }

    private function executeDeleteContact(int $idContacto)
    {
        try {
            $stmt = $this->conex->prepare("UPDATE contactos SET estado = 0 WHERE id_telf = ?");
            $stmt->bindValue(1, $idContacto, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function activateContact(int $idContacto)
    {
        return $this->executeActivateContact($idContacto);
    }

    private function executeActivateContact(int $idContacto)
    {
        try {
            $stmt = $this->conex->prepare("UPDATE contactos SET estado = 1 WHERE id_telf = ?");
            $stmt->bindValue(1, $idContacto, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function inactivateContact(int $idContacto)
    {
        return $this->executeInactivateContact($idContacto);
    }

    private function executeInactivateContact(int $idContacto)
    {
        return $this->deleteContact($idContacto);
    }
}

?>
