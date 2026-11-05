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

    public function __construct() {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    // ==================================================================
    // MÉTODOS PÚBLICOS (Controladores de flujo)
    // Regla: solo validan (ValidationTrait) y delegan en privados.
    // Los privados son los únicos que tocan la BD.
    // ==================================================================

    // LOGIN: valida usuario y contraseña con el trait y delega la consulta
    public function getLoginSistema($usuario, $password) {
        $usuario = trim((string)$usuario);
        $password = (string)$password;

        if ($this->validar('usuario', $usuario) !== null) {
            return 0;
        }

        if ($this->validar('password', $password) !== null) {
            return 0;
        }

        return $this->executeLoginSistema($usuario, $password);
    }

    // Busca usuario activo por correo O por usuario (nom_rep) para la
    // recuperación de contraseña. Sin resultados (o formato inválido) -> null.
    public function getByEmailOUsuario($dato) {
        $dato = trim((string)$dato);

        // El campo acepta correo O usuario: se valida según lo que parezca ser
        $error = (strpos($dato, '@') !== false)
            ? $this->validar('email', $dato)
            : $this->validar('usuario', $dato);

        if ($error !== null) {
            return null;
        }

        return $this->executeGetByEmailOUsuario($dato);
    }

    // Crea un token de recuperación firmado (HMAC, sin estado).
    // Devuelve null si falla.
    public function createResetToken($idResponsable) {
        return $this->executeCreateResetToken($idResponsable);
    }

    // Valida el token. Devuelve el id del responsable o null.
    public function validateResetToken($token) {
        return $this->executeValidateResetToken($token);
    }

    // Cambia la contraseña (hash). Devuelve {success, message}.
    public function resetPassword($idResponsable, $nuevaPassword): array {
        $error = $this->validar('password', $nuevaPassword);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        return $this->executeResetPassword((int)$idResponsable, $nuevaPassword);
    }

    // Obtiene datos completos del usuario por ID
    public function getUser($id) {
        return $this->executeGetUser($id);
    }

    // ==================================================================
    // MÉTODOS PRIVADOS (implementación y acceso a BD)
    // ==================================================================

    // Busca usuario activo en BD, compara contraseña (hash) y retorna status
    private function executeLoginSistema(string $usuario, string $password) {
        try {
            $query = $this->conex->prepare("SELECT d.nom_dep as dependencia, r.id_responsable,
                r.nom_rep as responsable, r.id_rol, rl.descripcion as rol, password, r.foto, d.id_dep as id_dep
                FROM responsables as r
                JOIN cargo as cr ON cr.id_responsable = r.id_responsable AND cr.estado = 1
                JOIN dependencias as d ON d.id_dep = cr.id_dep
                JOIN roles as rl on rl.id_rol = r.id_rol
                WHERE r.nom_rep = ? AND r.estado = 1");
            $query->bindValue(1, $usuario);
            $query->execute();
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                if (password_verify($password, $data['password'])) {
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

    private function executeGetByEmailOUsuario($dato) {
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

    // Lee el hash vigente para meter su huella en el payload del token
    // (invalida enlaces antiguos si la contraseña cambia).
    private function executeCreateResetToken($idResponsable) {
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

    // Firma correcta, sin vencer y huella coincidente con la contraseña ACTUAL
    // (así, al cambiar la contraseña, el enlace deja de servir solo).
    private function executeValidateResetToken($token) {
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

    private function executeResetPassword(int $idResponsable, string $nuevaPassword): array {
        try {
            $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            $stmt = $this->conex->prepare("UPDATE responsables SET password = ? WHERE id_responsable = ?");
            $stmt->bindValue(1, $hash);
            $stmt->bindValue(2, $idResponsable, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return ['success' => false, 'message' => 'No se pudo actualizar la contraseña. Intente de nuevo.'];
            }

            return ['success' => true, 'message' => 'Contraseña actualizada.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'No se pudo actualizar la contraseña. Intente de nuevo.'];
        }
    }

    private function executeGetUser($id) {
        try {
            $consulta = $this->conex->prepare("SELECT r.id_responsable, r.nom_rep, r.id_rol,
                rl.descripcion as rol, d.id_dep, d.nom_dep as dependencia
                FROM responsables as r
                JOIN cargo as cr ON cr.id_responsable = r.id_responsable
                JOIN dependencias as d ON d.id_dep = cr.id_dep
                JOIN roles as rl ON rl.id_rol = r.id_rol
                WHERE r.id_responsable = ? AND r.estado = 1");
            $consulta->bindValue(1, (int)$id);
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
