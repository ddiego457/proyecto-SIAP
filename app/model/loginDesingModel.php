<?php
// Archivo: app/model/LoginDesingModel.php
namespace EquipoSiap\Siap\model;   // <--- Namespace de Modelo

use EquipoSiap\Siap\config\Connect\ConnectDB;
use Exception;
use PDO;

class LoginDesingModel extends ConnectDB
{
    private $conex;
    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function login($username, $password)
{
    try {
        // 1. Consulta solo por nombre de usuario (o email)
        $stmt = $this->conex->prepare("SELECT id_responsable, nom_rep, id_rol, password 
                                       FROM responsables 
                                       WHERE nom_rep = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Si el usuario existe y la contraseña coincide con el hash
        if ($user !== null && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user; 
        }

        // 3. Si no coincide o no existe, retornamos false o null
        return false;
    } catch (Exception $e) {
        // Manejo del error (puedes loguearlo o lanzar una excepción personalizada)
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}
}

?>