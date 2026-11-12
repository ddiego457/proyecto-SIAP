<?php

namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;

class proveedorModel extends ConnectDB
{
    use ValidationTrait;

    private $conex;
    private $id;
    private $nombre;
    private $descripcion;
    private $estado;
    // Carpeta destino de documentos PDF (misma usada antiguamente por la importación Excel)
    private $directorioDocumentos;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
        // Se resuelve desde app/model/ hacia la raíz del proyecto
        $this->directorioDocumentos = dirname(__DIR__, 2) . '/assets/uploads/productos';
    }

    public function getAll()
    {
        return $this->executeGetAll();
    }

    private function executeGetAll()
    {
        $stmt = $this->conex->prepare("SELECT id_proveedor, nom_prov AS nombre, descripcion, estado, archivo_pdf FROM proveedores ORDER BY nom_prov ASC");
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
            // Evitar duplicados por nombre (case-insensitive)
            if ($this->existsByNombre($this->nombre)) {
                return false;
            }

            $stmt = $this->conex->prepare("INSERT INTO proveedores (nom_prov, descripcion, estado) VALUES (?, ?, 1)");
            $stmt->bindValue(1, $this->nombre);
            $stmt->bindValue(2, $this->descripcion);
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Validación pública para evitar duplicados por nombre
    public function existsByNombre(string $nombre): bool
    {
        return $this->executeExistsByNombre($nombre);
    }

    private function executeExistsByNombre(string $nombre): bool
    {
        $stmt = $this->conex->prepare("SELECT 1 FROM proveedores WHERE LOWER(TRIM(nom_prov)) = LOWER(TRIM(?))");
        $stmt->execute([$nombre]);
        return (bool)$stmt->fetchColumn();
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
            $stmt = $this->conex->prepare("SELECT id_telf, telefono, estado FROM telefonos WHERE id_proveedor = ? ORDER BY id_telf DESC");
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

    // Validación de formato de teléfono: solo dígitos, + y -
    private static function validarTelefono(string $telefono): bool
    {
        // Permite: dígitos, + (al inicio), - (en medio), espacios opcionales
        // Ejemplos válidos: +58-212-5551234, 0212-5551234, +58 212 555 1234
        return (bool)preg_match('/^\+?[0-9][0-9\-\s]{6,}$/', trim($telefono));
    }

    private function executeAddContact(int $idProveedor, string $telefono)
    {
        $tel = trim($telefono);
        if (!self::validarTelefono($tel)) {
            return false;
        }
        try {
            $stmt = $this->conex->prepare("INSERT INTO telefonos (id_proveedor, telefono, estado) VALUES (?, ?, 1)");
            $stmt->bindValue(1, $idProveedor, \PDO::PARAM_INT);
            $stmt->bindValue(2, $tel);
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
        $tel = trim($telefono);
        if (!self::validarTelefono($tel)) {
            return false;
        }
        try {
            $query = "UPDATE telefonos SET telefono = ?";
            $params = [$tel];

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
            $stmt = $this->conex->prepare("UPDATE telefonos SET estado = 0 WHERE id_telf = ?");
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
            $stmt = $this->conex->prepare("UPDATE telefonos SET estado = 1 WHERE id_telf = ?");
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

    // ─── Documentos PDF del proveedor ─────────────────────────────────
    // Límite de tamaño del PDF (10 MB)
    private $maximoBytesPdf = 10 * 1024 * 1024;

    // ==================================================================
    // MÉTODOS PÚBLICOS
    // ==================================================================

    /**
     * Valida un archivo subido antes de guardarlo como PDF del proveedor.
     * Verifica: código de subida de PHP, tamaño máximo (10 MB),
     * extensión .pdf y tipo real del archivo (no el MIME del navegador).
     *
     * @param array $archivo Elemento de $_FILES (ej. $_FILES['archivoPdf'])
     * @return null|string null si es válido, o mensaje de error si no lo es
     */
    public function validarArchivoPdf(array $archivo): ?string
    {
        return $this->executeValidarArchivoPdf($archivo);
    }

    /**
     * Guarda el PDF del proveedor: valida, mueve el archivo a la carpeta
     * de documentos y actualiza la BD. Si la BD falla, elimina el archivo.
     *
     * @param int   $idProveedor
     * @param array $archivo Elemento de $_FILES
     * @return array ['success' => bool, 'message' => string]
     */
    public function subirPdf(int $idProveedor, array $archivo): array
    {
        return $this->executeSubirPdf($idProveedor, $archivo);
    }

    /**
     * Devuelve el PDF asociado a un proveedor.
     *
     * @return array|null ['archivo' => string, 'nombre' => string] o null si no tiene
     */
    public function obtenerPdf(int $idProveedor): ?array
    {
        return $this->executeObtenerPdf($idProveedor);
    }

    // ==================================================================
    // MÉTODOS PRIVADOS (verificaciones con BD e implementación interna)
    // ==================================================================

    private function executeValidarArchivoPdf(array $archivo): ?string
    {
        // 1) Debe venir un archivo
        if (empty($archivo) || !isset($archivo['error'])) {
            return 'Seleccione un archivo PDF.';
        }

        // 2) Sin errores de subida de PHP
        if ((int)$archivo['error'] !== UPLOAD_ERR_OK) {
            if ((int)$archivo['error'] === UPLOAD_ERR_INI_SIZE || (int)$archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
                return 'El archivo no debe superar los 10 MB.';
            }
            return 'No se pudo subir el archivo. Verifique que no supere los 10 MB.';
        }

        // 3) Tamaño máximo
        if ((int)$archivo['size'] > $this->maximoBytesPdf) {
            return 'El archivo no debe superar los 10 MB.';
        }

        // 4) Debe ser un archivo subido realmente por HTTP
        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return 'Archivo no válido.';
        }

        // 5) Extensión permitida
        $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return 'Solo se permiten archivos con extensión .pdf.';
        }

        // 6) Tipo real (no se confía en el MIME declarado por el navegador)
        $mimeReal = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeReal = (string)finfo_file($finfo, $archivo['tmp_name']);
                finfo_close($finfo);
            }
        }
        if ($mimeReal === '' && function_exists('mime_content_type')) {
            $mimeReal = (string)mime_content_type($archivo['tmp_name']);
        }

        if ($mimeReal !== '' && $mimeReal !== 'application/pdf') {
            return 'El archivo no es un PDF válido.';
        }

        return null;
    }

    private function executeSubirPdf(int $idProveedor, array $archivo): array
    {
        if ($idProveedor <= 0) {
            return ['success' => false, 'message' => 'Proveedor inválido.'];
        }

        // 1) Validar archivo
        $error = $this->executeValidarArchivoPdf($archivo);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        // 2) Verificar proveedor
        $proveedor = $this->executeGetById($idProveedor);
        if (!$proveedor) {
            return ['success' => false, 'message' => 'Proveedor no encontrado.'];
        }

        // 3) Mover el archivo a la carpeta de documentos
        $movido = $this->executeMoverArchivoPdf($idProveedor, $archivo);
        if (!$movido['success']) {
            return ['success' => false, 'message' => $movido['message']];
        }

        // 4) Registrar en la BD (si falla, se elimina el archivo recién movido)
        $guardado = $this->executeActualizarPdfProveedor(
            $idProveedor,
            $movido['archivo'],
            $movido['nombre']
        );

        if (!$guardado['success']) {
            @unlink($movido['ruta']);
            return ['success' => false, 'message' => 'No se pudo registrar el PDF en la base de datos.'];
        }

        return ['success' => true, 'message' => 'PDF guardado correctamente.'];
    }

    /**
     * Verifica/crea la carpeta de documentos y mueve el archivo subido.
     * El nombre lo genera el servidor (evita path traversal y colisiones).
     */
    private function executeMoverArchivoPdf(int $idProveedor, array $archivo): array
    {
        $ruta = $this->directorioDocumentos;

        // Crear carpeta si no existe
        if (!is_dir($ruta)) {
            if (!@mkdir($ruta, 0755, true)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo crear la carpeta de documentos. Verifique permisos en assets/uploads/.'
                ];
            }
        }

        if (!is_writable($ruta)) {
            return [
                'success' => false,
                'message' => 'La carpeta de documentos no tiene permisos de escritura.'
            ];
        }

        $nombreOriginal = $archivo['name'] ?? 'documento.pdf';
        $nombreArchivo  = 'proveedor_' . $idProveedor . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.pdf';
        $rutaDestino    = $ruta . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return [
                'success' => false,
                'message' => 'No se pudo guardar el archivo en el servidor.'
            ];
        }

        return [
            'success'  => true,
            'archivo'  => $nombreArchivo,
            'nombre'   => $nombreOriginal,
            'ruta'     => $rutaDestino
        ];
    }

private function executeActualizarPdfProveedor(
    int $idProveedor,
    string $archivo): array
    {       
        try {
            // Obtener el PDF anterior para eliminarlo (evita archivos huérfanos)
            $anterior = $this->executeObtenerPdf($idProveedor);

            $stmt = $this->conex->prepare(
                "UPDATE proveedores SET archivo_pdf = ? WHERE id_proveedor = ?"
            );
            $stmt->bindValue(1, $archivo);
            $stmt->bindValue(2, $idProveedor, \PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return ['success' => false];
            }

            // Borrar el archivo anterior solo tras confirmar el UPDATE
            if ($anterior !== null && !empty($anterior['archivo'])) {
                $rutaAnterior = $this->directorioDocumentos . '/' . basename($anterior['archivo']);
                if (is_file($rutaAnterior)) {
                    @unlink($rutaAnterior);
                }
            }

            return ['success' => true];
        } catch (\PDOException $e) {
            error_log('[proveedorModel::executeActualizarPdfProveedor] Error BD: ' . $e->getMessage());
            return ['success' => false];
        }
    }

    private function executeObtenerPdf(int $idProveedor): ?array
    {
        try {
            $stmt = $this->conex->prepare(
            "SELECT archivo_pdf FROM proveedores WHERE id_proveedor = ?"
            );

        $stmt->bindValue(1, $idProveedor, \PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$fila || empty($fila['archivo_pdf'])) {
            return null;
        }

        return [
            'archivo' => $fila['archivo_pdf'],
            'nombre'  => $fila['archivo_pdf']   
        ];

    } catch (\PDOException $e) {
        error_log('[proveedorModel::executeObtenerPdf] Error BD: ' . $e->getMessage());
        return null;
        }
    }
}

?>
