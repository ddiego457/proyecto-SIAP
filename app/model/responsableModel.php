<?php

namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;

class responsableModel extends ConnectDB
{
    use ValidationTrait;

    private $conex;
    private $id;
    private $id_rol;
    private $nom_rep;
    private $password;
    private $estado;

    // Carpeta destino de la foto de perfil (se resuelve desde app/model/ hacia la raíz)
    private $directorioPerfiles;
    // Límite de la foto de perfil (regla del negocio: 2 MB)
    private $maximoBytesFoto = 2 * 1024 * 1024;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
        $this->directorioPerfiles = dirname(__DIR__, 2) . '/assets/img/perfiles';
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

    // ─── Foto de perfil (nombre del archivo en assets/img/perfiles/) ──
    // Devuelve el nombre del archivo o null si no tiene foto
    public function getFoto(int $idResponsable): ?string
    {
        return $this->executeGetFoto($idResponsable);
    }

    private function executeGetFoto(int $idResponsable): ?string
    {
        try {
            $stmt = $this->conex->prepare("SELECT foto FROM responsables WHERE id_responsable = ? LIMIT 1");
            $stmt->execute([$idResponsable]);
            $foto = $stmt->fetchColumn();
            return ($foto !== false && $foto !== null && $foto !== '') ? (string)$foto : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    // Guarda la foto (nombre del archivo) o la limpia (null) si $archivo es null
    public function setFoto(int $idResponsable, ?string $archivo): bool
    {
        return $this->executeSetFoto($idResponsable, $archivo);
    }

    private function executeSetFoto(int $idResponsable, ?string $archivo): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE responsables SET foto = ? WHERE id_responsable = ?");
            $stmt->bindValue(1, $archivo);
            $stmt->bindValue(2, $idResponsable, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Sube o cambia la foto de perfil del responsable.
     * Valida el archivo, lo procesa (recorte cuadrado + 256x256), lo guarda
     * en assets/img/perfiles/, actualiza la BD, borra la foto anterior y
     * refresca $_SESSION['foto'].
     *
     * @param int   $idResponsable Sesión del responsable (nunca del formulario)
     * @param array $archivo       Elemento de $_FILES
     * @return array ['success' => bool, 'message' => string, 'foto'?, 'url'?]
     */
    public function subirFoto(int $idResponsable, array $archivo): array
    {
        return $this->executeSubirFoto($idResponsable, $archivo);
    }

    /**
     * Elimina la foto de perfil (vuelve a mostrarse la letra inicial).
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function eliminarFoto(int $idResponsable): array
    {
        return $this->executeEliminarFoto($idResponsable);
    }

    // ==================================================================
    // MÉTODOS PRIVADOS (foto de perfil: validación, proceso y archivo)
    // ==================================================================

    private function executeSubirFoto(int $idResponsable, array $archivo): array
    {
        if ($idResponsable <= 0) {
            return ['success' => false, 'message' => 'Sesión no válida. Inicie sesión de nuevo.'];
        }

        // 1) Validar el archivo subido
        $error = $this->executeValidarArchivoFoto($archivo);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        // 2) Recorte cuadrado centrado + redimensionado a 256x256 (patrón uniforme)
        $procesada = $this->executeProcesarImagenFoto($archivo);
        if (!$procesada['success']) {
            return ['success' => false, 'message' => $procesada['message']];
        }
        $destino = $procesada['imagen'];

        // 3) Crear carpeta si no existe
        if (!is_dir($this->directorioPerfiles) && !@mkdir($this->directorioPerfiles, 0755, true)) {
            imagedestroy($destino);
            return ['success' => false, 'message' => 'No se pudo crear la carpeta de imágenes. Verifique permisos en assets/img/.'];
        }

        // 4) Nombre generado por el servidor (nunca el del cliente): evita
        //    path traversal y colisiones. Se guarda siempre como JPEG.
        $nombreNuevo = 'resp_' . $idResponsable . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $rutaNueva = $this->directorioPerfiles . '/' . $nombreNuevo;

        if (!@imagejpeg($destino, $rutaNueva, 85)) {
            imagedestroy($destino);
            return ['success' => false, 'message' => 'No se pudo guardar la imagen. Verifique permisos de escritura en assets/img/perfiles/.'];
        }
        imagedestroy($destino);

        // 5) Actualizar la BD (nombre del archivo) y borrar la foto anterior
        $fotoAnterior = $this->executeGetFoto($idResponsable);

        if (!$this->executeSetFoto($idResponsable, $nombreNuevo)) {
            @unlink($rutaNueva);
            return ['success' => false, 'message' => 'No se pudo actualizar la foto en la base de datos.'];
        }

        if ($fotoAnterior !== null && $fotoAnterior !== $nombreNuevo) {
            @unlink($this->directorioPerfiles . '/' . basename($fotoAnterior));
        }

        // 6) Refrescar la sesión para que la sidebar la muestre al instante
        $_SESSION['foto'] = $nombreNuevo;

        return [
            'success' => true,
            'message' => 'Foto de perfil actualizada.',
            'foto'    => $nombreNuevo,
            'url'     => 'assets/img/perfiles/' . $nombreNuevo . '?v=' . time(),
        ];
    }

    private function executeEliminarFoto(int $idResponsable): array
    {
        if ($idResponsable <= 0) {
            return ['success' => false, 'message' => 'Sesión no válida. Inicie sesión de nuevo.'];
        }

        $fotoAnterior = $this->executeGetFoto($idResponsable);

        if (!$this->executeSetFoto($idResponsable, null)) {
            return ['success' => false, 'message' => 'No se pudo eliminar la foto de la base de datos.'];
        }

        if ($fotoAnterior !== null) {
            @unlink($this->directorioPerfiles . '/' . basename($fotoAnterior));
        }

        unset($_SESSION['foto']);

        return ['success' => true, 'message' => 'Foto de perfil eliminada.'];
    }

    /**
     * Valida un archivo subido antes de procesarlo como foto de perfil.
     * Verifica: que venga un archivo, código de subida de PHP, tamaño
     * máximo (2 MB), que sea una subida HTTP real, la extensión GD y el
     * tipo REAL de la imagen (no se confía en el MIME del navegador).
     *
     * @return null|string null si es válido, o mensaje de error si no lo es
     */
    private function executeValidarArchivoFoto(array $archivo): ?string
    {
        // 1) Debe venir un archivo
        if (empty($archivo) || !isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Seleccione una imagen primero.';
        }

        // 2) Sin errores de subida de PHP
        if ((int)$archivo['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo (código ' . (int)$archivo['error'] . '). Intente de nuevo.';
        }

        // 3) Límite de 2 MB (el servidor admite más; es una regla del negocio)
        if ((int)$archivo['size'] > $this->maximoBytesFoto) {
            return 'La imagen no debe superar los 2 MB.';
        }

        // 4) Debe ser un archivo subido realmente por HTTP
        if (empty($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return 'Archivo no válido.';
        }

        // 5) Verificar que la extensión GD esté disponible para procesar la imagen
        if (!function_exists('imagecreatetruecolor')) {
            return 'El servidor no tiene habilitada la extensión GD para procesar imágenes. Contacte al administrador.';
        }

        // 6) Tipo REAL de la imagen (no se confía en el MIME declarado por el navegador)
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false) {
            return 'El archivo seleccionado no es una imagen válida.';
        }

        // 7) Formatos admitidos
        $mime = $info['mime'] ?? '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Formato no admitido. Use JPG, PNG o WEBP.';
        }

        return null;
    }

    /**
     * Abre la imagen, la recorta en cuadrado centrado y la redimensiona
     * a 256x256 (patrón uniforme para todos los avatares).
     *
     * @return array ['success' => bool, 'message'?, 'imagen'? (GD resource)]
     */
    private function executeProcesarImagenFoto(array $archivo): array
    {
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false) {
            return ['success' => false, 'message' => 'El archivo seleccionado no es una imagen válida.'];
        }

        switch ($info['mime']) {
            case 'image/jpeg':
                $origen = @imagecreatefromjpeg($archivo['tmp_name']);
                break;
            case 'image/png':
                $origen = @imagecreatefrompng($archivo['tmp_name']);
                break;
            case 'image/webp':
                $origen = @imagecreatefromwebp($archivo['tmp_name']);
                break;
            default:
                return ['success' => false, 'message' => 'Formato no admitido. Use JPG, PNG o WEBP.'];
        }

        if ($origen === false) {
            return ['success' => false, 'message' => 'No se pudo procesar la imagen. Verifique que el archivo no esté dañado.'];
        }

        $ladoOrigen = min($info[0], $info[1]);
        $sx = (int)(($info[0] - $ladoOrigen) / 2);
        $sy = (int)(($info[1] - $ladoOrigen) / 2);
        $destino = imagecreatetruecolor(256, 256);
        imagecopyresampled($destino, $origen, 0, 0, $sx, $sy, 256, 256, $ladoOrigen, $ladoOrigen);
        imagedestroy($origen);

        return ['success' => true, 'imagen' => $destino];
    }


}

?>