<?php

namespace EquipoSiap\Siap\model;
use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;
use EquipoSiap\Siap\helpers\ResetTokenHelper;
use PDO;
use Exception;

class UserModel extends ConnectDB {
    use ValidationTrait;

    private $conex;
    private $id;
    private $usuario;
    private $password;

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getLoginSistema($usuario, $password) {
        $usuario = trim((string)$usuario);
        $password = (string)$password;

        $errorValidacion = $this->validar('usuario', $usuario);
        if ($errorValidacion !== null) {
            return 0;
        }

        $errorValidacion = $this->validar('password', $password);
        if ($errorValidacion !== null) {
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
                r.nom_rep as responsable, r.id_rol, rl.descripcion as rol, password, r.foto, d.id_dep as id_dep
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
    // Busca un responsable activo por correo O por usuario (nom_rep)
    // para la recuperación de contraseña. Sin resultados -> null.
    public function getByEmailOUsuario($dato) {
        try {
            $stmt = $this->conex->prepare("SELECT id_responsable, nom_rep, email
                FROM responsables
                WHERE (email = ? OR nom_rep = ?) AND estado = 1
                LIMIT 1");
            $dato = trim((string)$dato);
            $stmt->bindValue(1, $dato);
            $stmt->bindValue(2, $dato);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? $data : null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Crea un token de recuperación firmado (HMAC, sin estado).
    // No escribe nada en la BD: solo lee el hash vigente para
    // meter su huella en el payload (invalida enlaces antiguos
    // si la contraseña cambia). Devuelve null si falla.
    public function createResetToken($idResponsable) {
        try {
            $stmt = $this->conex->prepare("SELECT password FROM responsables WHERE id_responsable = ? AND estado = 1");
            $stmt->bindValue(1, (int)$idResponsable, PDO::PARAM_INT);
            $stmt->execute();
            $hashActual = $stmt->fetchColumn();

            if ($hashActual === false || $hashActual === null || $hashActual === '') {
                return null;
            }

            return ResetTokenHelper::firmar(
                (int)$idResponsable,
                ResetTokenHelper::huella((string)$hashActual)
            );
        } catch (Exception $e) {
            return null;
        } catch (\Throwable $e) {
            // p.ej. no se pudo crear app/config/reset_secret.php
            return null;
        }
    }

    // Valida el token: firma correcta, sin vencer y que su huella
    // coincida con la contraseña ACTUAL del responsable (así, al
    // cambiar la contraseña, el enlace deja de servir solo).
    // Devuelve el id del responsable o null.
    public function validateResetToken($token) {
        try {
            $datos = ResetTokenHelper::verificar((string)$token);
            if ($datos === null) {
                return null;
            }

            $stmt = $this->conex->prepare("SELECT password FROM responsables WHERE id_responsable = ? AND estado = 1");
            $stmt->bindValue(1, $datos['id'], PDO::PARAM_INT);
            $stmt->execute();
            $hashActual = $stmt->fetchColumn();

            if ($hashActual === false || $hashActual === null) {
                return null;
            }

            if (!hash_equals(ResetTokenHelper::huella((string)$hashActual), $datos['pw'])) {
                return null; // la contraseña cambió después de emitirse el enlace
            }

            return $datos['id'];
        } catch (Exception $e) {
            return null;
        }
    }

    // Cambia la contraseña (hash). Devuelve true/false.
    // El enlace usado queda inválido automáticamente: la huella
    // guardada en tokens anteriores ya no coincide con el nuevo hash.
    public function resetPassword($idResponsable, $nuevaPassword) {
        try {
            $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            $stmt = $this->conex->prepare("UPDATE responsables SET password = ? WHERE id_responsable = ?");
            $stmt->bindValue(1, $hash);
            $stmt->bindValue(2, (int)$idResponsable, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
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
