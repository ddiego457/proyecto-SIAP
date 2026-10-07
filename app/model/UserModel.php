<?php

namespace EquipoSiap\Siap\model;
use EquipoSiap\Siap\config\Connect\ConnectDB;
use PDO;
use Exception;

class UserModel extends ConnectDB {
    private $conex;
    private $id;
    private $usuario;
    private $password;
    // Expresiones regulares
    private $expUsuario = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ_\.\-\s]{2,100}$/';
    public $expPassword = '/^[a-zA-Z0-9_\.\-]{4,}$/';

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getLoginSistema($usuario, $password) {
        $usuario = trim((string)$usuario);
        $password = (string)$password;

        if (!preg_match($this->expUsuario, $usuario)) {
            return 0;
        }

        if (!preg_match($this->expPassword, $password)) {
            return 0;
        }

        $this->usuario = $usuario;
        $this->password = $password;

        return $this->loginSistema();
    }
    // Busca usuario activo en BD, compara contraseña (hash) y retorna status
    private function loginSistema() {
        try {
            $query = $this->conex->prepare("SELECT d.nom_dep as dependencia, r.id_responsable,
                r.nom_rep as responsable, r.id_rol, rl.descripcion as rol, password, d.id_dep as id_dep
                FROM responsables as r
                JOIN cargo as cr ON cr.id_responsable = r.id_responsable AND cr.estado = 1
                JOIN dependencias as d ON d.id_dep = cr.id_dep
                JOIN roles as rl on rl.id_rol = r.id_rol
                WHERE r.nom_rep = ? AND r.estado = 1");
            $query->bindValue(1, $this->usuario);
            $query->execute();
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                if (password_verify($this->password, $data['password'])) {
                    unset($data['password']);
                    return array('status' => 1, 'data' => array($data));
                } else {
                    return array('status' => 2, 'user' => $data['responsable']);
                }
            } else {
                return 3;
            }
        } catch (Exception $error) {
            return array('status' => 'error', 'message' => $error->getMessage());
        }
    }
    // Busca un responsable activo por email (para recuperación de contraseña)
    public function getByEmail($email) {
        try {
            $stmt = $this->conex->prepare("SELECT id_responsable, nom_rep, email
                FROM responsables
                WHERE email = ? AND estado = 1");
            $stmt->bindValue(1, trim((string)$email));
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? $data : null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Crea un token de recuperación válido por 30 minutos (reemplaza tokens previos sin usar)
    public function createResetToken($idResponsable) {
        try {
            $this->conex->prepare("DELETE FROM password_resets WHERE id_responsable = ? AND usado = 0")
                ->execute([$idResponsable]);

            $token = bin2hex(random_bytes(20));
            $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $stmt = $this->conex->prepare("INSERT INTO password_resets (id_responsable, token, expira_en, usado) VALUES (?, ?, ?, 0)");
            $stmt->bindValue(1, (int)$idResponsable, PDO::PARAM_INT);
            $stmt->bindValue(2, $token);
            $stmt->bindValue(3, $expira);
            $stmt->execute();
            return $token;
        } catch (Exception $e) {
            return null;
        }
    }

    // Elimina un token sin usar (si falla el envío del correo)
    public function deleteResetToken($token) {
        try {
            $stmt = $this->conex->prepare("DELETE FROM password_resets WHERE token = ? AND usado = 0");
            $stmt->bindValue(1, (string)$token);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    // Valida el token: existe, sin vencer, sin usar y con responsable activo. Devuelve el id o null
    public function validateResetToken($token) {        try {
            $stmt = $this->conex->prepare("SELECT pr.id_responsable
                FROM password_resets pr
                JOIN responsables r ON r.id_responsable = pr.id_responsable AND r.estado = 1
                WHERE pr.token = ? AND pr.usado = 0 AND pr.expira_en > NOW()");
            $stmt->bindValue(1, (string)$token);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            return $id !== false ? (int)$id : null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Cambia la contraseña (hash) y consume el token. Devuelve true/false
    public function resetPassword($idResponsable, $nuevaPassword, $token) {
        try {
            $this->conex->beginTransaction();

            $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            $stmt = $this->conex->prepare("UPDATE responsables SET password = ? WHERE id_responsable = ?");
            $stmt->bindValue(1, $hash);
            $stmt->bindValue(2, (int)$idResponsable, PDO::PARAM_INT);
            $stmt->execute();

            $stmt2 = $this->conex->prepare("UPDATE password_resets SET usado = 1 WHERE token = ?");
            $stmt2->bindValue(1, (string)$token);
            $stmt2->execute();

            $this->conex->prepare("DELETE FROM password_resets WHERE id_responsable = ? AND usado = 0")
                ->execute([$idResponsable]);

            $this->conex->commit();
            return true;
        } catch (Exception $e) {
            $this->conex->rollBack();
            return false;
        }
    }

    // Obtiene datos completos del usuario por ID
    public function getUser($id) {        $this->id = $id;

        try {
            $consulta = $this->conex->prepare("SELECT r.id_responsable, r.nom_rep, r.id_rol,
                rl.descripcion as rol, d.id_dep, d.nom_dep as dependencia
                FROM responsables as r
                JOIN cargo as cr ON cr.id_responsable = r.id_responsable
                JOIN dependencias as d ON d.id_dep = cr.id_dep
                JOIN roles as rl ON rl.id_rol = r.id_rol
                WHERE r.id_responsable = ? AND r.estado = 1");
            $consulta->bindValue(1, $this->id);
            $consulta->execute();
            $usuario = $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array("status" => "error", "message" => $e->getMessage());
        }

        $resp = array();

        foreach ($usuario as $user) {
            $resp[] = array(
                'id_responsable' => $user["id_responsable"],
                'nombre'         => $user["nom_rep"],
                'rol'            => $user["rol"],
                'id_dep'         => $user["id_dep"],
                'dependencia'    => $user["dependencia"]
            );
        }

        return $resp;
    }

}

?>
