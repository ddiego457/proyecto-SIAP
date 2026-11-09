<?php

namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;
use EquipoSiap\Siap\helpers\ValidationTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;

class productosServiciosModel extends ConnectDB
{
    use ValidationTrait;

    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getPartidas(): array
    {
        return $this->executeGetPartidas();
    }

    private function executeGetPartidas(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT id_partida, cod_partida, descripcion
             FROM partidas
             WHERE cod_partida IN ('401', '402', '403', '404', '407')
             ORDER BY cod_partida"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll(?int $partidaId = null): array
    {
        return $this->executeGetAll($partidaId);
    }
    
    private function executeGetAll(?int $partidaId = null): array
    {
        $sql = "SELECT pro.id_prod, pro.id_partida, pro.id_proveedor, p.cod_partida, p.descripcion AS partida_descripcion,
                       pr.nom_prov AS proveedor, pro.nom_prod, pro.precio, pro.estado
                FROM productos pro
                JOIN partidas p ON pro.id_partida = p.id_partida
                JOIN proveedores pr ON pro.id_proveedor = pr.id_proveedor
                ";

        if ($partidaId !== null) {
            $sql .= " AND pro.id_partida = ?";
            $stmt = $this->conex->prepare($sql . " ORDER BY p.cod_partida, pro.nom_prod");
            $stmt->execute([$partidaId]);
        } else {
            $stmt = $this->conex->prepare($sql . " ORDER BY p.cod_partida, pro.nom_prod");
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    // Validación pública para evitar duplicados (misma partida + proveedor + nombre)
    public function existsByKey(int $idPartida, int $idProveedor, string $nomItem, ?int $excludeId = null): bool
    {
        return $this->executeExistsByKey($idPartida, $idProveedor, $nomItem, $excludeId);
    }

    private function executeExistsByKey(int $idPartida, int $idProveedor, string $nomItem, ?int $excludeId = null): bool
    {
        $query = "SELECT 1 FROM productos
                  WHERE id_partida = ? AND id_proveedor = ? AND LOWER(TRIM(nom_prod)) = LOWER(TRIM(?))";
        $params = [$idPartida, $idProveedor, $nomItem];
        if ($excludeId !== null) {
            $query .= " AND id_prod <> ?";
            $params[] = $excludeId;
        }
        $stmt = $this->conex->prepare($query);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function add(int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        return $this->executeAdd($idPartida, $idProveedor, $nomItem, $precio);
    }

    private function executeAdd(int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        try {
            // Evitar duplicados por combinación única
            if ($this->existsByKey($idPartida, $idProveedor, $nomItem)) {
                return false;
            }

            $stmt = $this->conex->prepare(
                "INSERT INTO productos (id_partida, id_proveedor, nom_prod, precio, estado)
                 VALUES (?, ?, ?, ?, 1)"
            );
            return $stmt->execute([$idPartida, $idProveedor, $nomItem, $precio]);
        } catch (\PDOException $e) {
            // Se deja el detalle real en el log para poder diagnosticar;
            // al usuario solo se le muestra un mensaje genérico.
            error_log('[productosServiciosModel::executeAdd] Error BD: ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $idItem, int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        return $this->executeUpdate($idItem, $idPartida, $idProveedor, $nomItem, $precio);
    }

    private function executeUpdate(int $idItem, int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "UPDATE productos
                 SET id_partida = ?, id_proveedor = ?, nom_prod = ?, precio = ?
                 WHERE id_prod = ?"
            );
            return $stmt->execute([$idPartida, $idProveedor, $nomItem, $precio, $idItem]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function inhabilitar(int $idItem): bool
    {
        return $this->executeInhabilitar($idItem);
    }

    private function executeInhabilitar(int $idItem): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "UPDATE productos
                 SET estado = 0
                 WHERE id_prod = ?"
            );
            return $stmt->execute([$idItem]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function activar(int $idItem): bool
    {
        return $this->executeActivar($idItem);
    }

    private function executeActivar(int $idItem): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "UPDATE productos
                 SET estado = 1
                 WHERE id_prod = ?"
            );
            return $stmt->execute([$idItem]);
        } catch (\PDOException $e) {
            return false;
        }
    }
    // ─── Partidas presupuestarias ───────────────────────────────────────
    // Verifica si la partida ya existe (validación pública)
    public function existsPartida(string $codPartida, ?int $excludeId = null): bool
    {
        return $this->executeExistsPartida($codPartida, $excludeId);
    }

    private function executeExistsPartida(string $codPartida, ?int $excludeId = null): bool
    {
        $query = "SELECT 1 FROM partidas WHERE cod_partida = ?";
        $params = [trim($codPartida)];

        if ($excludeId !== null) {
            $query .= " AND id_partida <> ?";
            $params[] = $excludeId;
        }

        $stmt = $this->conex->prepare($query . " LIMIT 1");
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function addPartida(string $codPartida, string $descripcion): bool
    {
        if (trim($codPartida) === '' || trim($descripcion) === '') {
            return false;
        }
        return $this->executeAddPartida($codPartida, $descripcion);
    }

    private function executeAddPartida(string $codPartida, string $descripcion): bool
    {
        try {
            // Evitar partidas duplicadas por código
            if ($this->executeExistsPartida($codPartida)) {
                return false;
            }

            $stmt = $this->conex->prepare(
                "INSERT INTO partidas (cod_partida, descripcion, estado)
                 VALUES (?, ?, 1)"
            );
            return $stmt->execute([trim($codPartida), trim($descripcion)]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Listado completo de partidas (incluye las inhabilitadas)
    public function getAllPartidas(): array
    {
        return $this->executeGetAllPartidas();
    }

    private function executeGetAllPartidas(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT id_partida, cod_partida, descripcion, estado
             FROM partidas
             ORDER BY cod_partida"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updatePartida(int $idPartida, string $codPartida, string $descripcion, int $estado): bool
    {
        return $this->executeUpdatePartida($idPartida, $codPartida, $descripcion, $estado);
    }

    private function executeUpdatePartida(int $idPartida, string $codPartida, string $descripcion, int $estado): bool
    {
        try {
            // El código no puede pertenecer a otra partida
            if ($this->executeExistsPartida($codPartida, $idPartida)) {
                return false;
            }

            $stmt = $this->conex->prepare(
                "UPDATE partidas
                 SET cod_partida = ?, descripcion = ?, estado = ?
                 WHERE id_partida = ?"
            );
            return $stmt->execute([trim($codPartida), trim($descripcion), $estado === 1 ? 1 : 0, $idPartida]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Eliminar lógicamente una partida (estado = 0)
    public function deletePartida(int $idPartida): bool
    {
        return $this->executeDeletePartida($idPartida);
    }

    private function executeDeletePartida(int $idPartida): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE partidas SET estado = 0 WHERE id_partida = ?");
            return $stmt->execute([$idPartida]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function activatePartida(int $idPartida): bool
    {
        return $this->executeActivatePartida($idPartida);
    }

    private function executeActivatePartida(int $idPartida): bool
    {
        try {
            $stmt = $this->conex->prepare("UPDATE partidas SET estado = 1 WHERE id_partida = ?");
            return $stmt->execute([$idPartida]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    // ─── Importación de productos desde Excel ─────────────────────────
    // Límite de tamaño del archivo (50 MB)
    private $maximoBytesImportacion = 50 * 1024 * 1024;

    // ==================================================================
    // MÉTODOS PÚBLICOS (verificaciones sin BD)
    // ==================================================================

    /**
     * Valida un archivo subido antes de importarlo.
     * Verifica: código de subida de PHP, tamaño máximo (50 MB),
     * extensión .xlsx/.xls y tipo real del archivo (no el MIME del navegador).
     *
     * @param array $archivo Elemento de $_FILES (ej. $_FILES['archivoExcel'])
     * @return null|string null si es válido, o mensaje de error si no lo es
     */
    public function validarArchivoImportacion(array $archivo): ?string
    {
        return $this->executeValidarArchivoImportacion($archivo);
    }

    /**
     * Verifica contra la BD que el proveedor exista y esté activo.
     */
    public function validarProveedorActivo(int $idProveedor): bool
    {
        return $this->executeValidarProveedorActivo($idProveedor);
    }

    /**
     * Punto de entrada principal de importación:
     * 1) valida el archivo
     * 2) lo guarda en la carpeta de importaciones
     * 3) valida el proveedor
     * 4) recorre el Excel e inserta los productos en la BD (transacción)
     *
     * @param array $archivo Elemento de $_FILES
     * @param int   $idProveedor Proveedor activo asignado a los productos importados
     * @return array Payload listo para JSON: success, message, resumen, archivo
     */
    public function importarProductosExcel(array $archivo, int $idProveedor): array
    {
        // 1) Validar archivo (extensión, tamaño y tipo real)
        $error = $this->validarArchivoImportacion($archivo);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        // 2) Validar proveedor
        if ($idProveedor <= 0 || !$this->validarProveedorActivo($idProveedor)) {
            return ['success' => false, 'message' => 'Proveedor inválido o inactivo.'];
        }

        // 3) Importar directamente desde el archivo temporal de PHP
        //    (no se guarda en disco: PHP lo elimina al terminar la petición)
        try {
            $resumen = $this->executeImportarProductosExcel($archivo['tmp_name'], $idProveedor);
        } catch (\Throwable $e) {
            error_log('[productosServiciosModel::importarProductosExcel] Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el archivo Excel: ' . $e->getMessage()
            ];
        }

        $totalInsertados = $resumen['insertados'];
        $totalOmitidos   = $resumen['omitidos'];
        $totalErrores    = $resumen['errores'];

        return [
            'success' => true,
            'message' => "Importación completada: {$totalInsertados} insertados, {$totalOmitidos} omitidos, {$totalErrores} con error.",
            'resumen' => $resumen
        ];
    }

    // ==================================================================
    // MÉTODOS PRIVADOS (verificaciones con BD e implementación interna)
    // ==================================================================

    private function executeValidarArchivoImportacion(array $archivo): ?string
    {
        // 1) ¿Llegó algún archivo?
        if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Seleccione un archivo Excel.';
        }

        // 2) Error de subida reportado por PHP
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo (código ' . (int)$archivo['error'] . '). Intente de nuevo.';
        }

        // 3) Tamaño máximo
        if (!isset($archivo['size']) || (int)$archivo['size'] <= 0) {
            return 'El archivo está vacío.';
        }
        if ((int)$archivo['size'] > $this->maximoBytesImportacion) {
            return 'El archivo no debe superar los 50 MB.';
        }

        // 4) Debe ser un archivo subido realmente por HTTP
        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return 'Archivo no válido.';
        }

        // 5) Extensión permitida
        $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return 'Solo se permiten archivos con extensión .xlsx o .xls.';
        }

        // 6) Tipo real (no se confía en el MIME declarado por el navegador)
        try {
            $tipoReal = IOFactory::identify($archivo['tmp_name']);
            if (!in_array($tipoReal, ['Xlsx', 'Xls'], true)) {
                return 'El archivo no es un Excel válido.';
            }
        } catch (\Throwable $e) {
            return 'No se pudo leer el archivo Excel. Verifique que no esté dañado.';
        }

        return null;
    }

    private function executeValidarProveedorActivo(int $idProveedor): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "SELECT 1 FROM proveedores WHERE id_proveedor = ? AND estado = 1 LIMIT 1"
            );
            $stmt->execute([$idProveedor]);
            return (bool)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('[productosServiciosModel::executeValidarProveedorActivo] Error BD: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapa de partidas activas: código normalizado (solo dígitos) => id_partida.
     * "4,02" y "4.02" se normalizan a "402".
     */
    private function executeObtenerMapaPartidas(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT id_partida, cod_partida
             FROM partidas
             WHERE estado = 1"
        );
        $stmt->execute();

        $mapa = [];
        foreach ($stmt->fetchAll() as $fila) {
            $codigo = preg_replace('/\D+/', '', (string)$fila['cod_partida']);
            if ($codigo !== '') {
                $mapa[$codigo] = (int)$fila['id_partida'];
            }
        }
        return $mapa;
    }

    /**
     * Precarga las claves existentes (partida|nombre) para un proveedor,
     * permitiendo detectar duplicados sin una consulta por fila.
     */
    private function executeObtenerClavesExistentes(int $idProveedor): array
    {
        $stmt = $this->conex->prepare(
            "SELECT id_partida, LOWER(TRIM(nom_prod)) AS nombre
             FROM productos
             WHERE id_proveedor = ?"
        );
        $stmt->execute([$idProveedor]);

        $claves = [];
        foreach ($stmt->fetchAll() as $fila) {
            $claves[$fila['id_partida'] . '|' . $fila['nombre']] = true;
        }
        return $claves;
    }

    private function executeInsertarProducto(int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        $stmt = $this->conex->prepare(
            "INSERT INTO productos (id_partida, id_proveedor, nom_prod, precio, estado)
             VALUES (?, ?, ?, ?, 1)"
        );
        return $stmt->execute([$idPartida, $idProveedor, $nomItem, $precio]);
    }

    /**
     * Detecta la fila de encabezado y las columnas de descripción, unidad
     * y precio de una hoja. Devuelve:
     * ['fila_encabezado' => ?int, 'col_desc' => 'B', 'col_unidad' => 'C', 'col_precio' => 'S']
     */
    private function detectarEstructuraHoja(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja): array
    {
        $estructura = [
            'fila_encabezado' => null,
            'col_desc'        => 'B',
            'col_unidad'      => 'C',
            'col_precio'      => 'S'
        ];

        $ultimaFila = min(50, (int)$hoja->getHighestDataRow());

        for ($fila = 1; $fila <= $ultimaFila; $fila++) {
            $valores = $hoja->rangeToArray(
                'A' . $fila . ':' . $hoja->getHighestColumn() . $fila,
                null,
                true,
                true,
                true
            )[$fila] ?? [];

            $encontradoDesc = false;

            foreach ($valores as $columna => $valor) {
                $texto = mb_strtoupper(trim((string)$valor), 'UTF-8');

                if ($texto === '') {
                    continue;
                }

                if (!$encontradoDesc && str_contains($texto, 'DESCRIPCION')) {
                    $estructura['fila_encabezado'] = $fila;
                    $estructura['col_desc'] = $columna;
                    $encontradoDesc = true;
                } elseif ($encontradoDesc && str_contains($texto, 'UNIDAD') && !str_contains($texto, 'DESCRIPCION')) {
                    $estructura['col_unidad'] = $columna;
                } elseif ($encontradoDesc && str_contains($texto, 'PRECIO UNIT')) {
                    $estructura['col_precio'] = $columna;
                }
            }

            if ($encontradoDesc && $estructura['col_precio'] !== 'S') {
                break;
            }
        }

        return $estructura;
    }

    /**
     * Formatea el nombre del producto como "DESCRIPCION|UNIDAD|",
     * truncando la descripción para no exceder los 150 caracteres de la BD.
     */
    private function formatearNombreProducto(string $descripcion, string $unidad): string
    {
        // Eliminar caracteres de control y normalizar espacios
        $descripcion = preg_replace('/[\x00-\x1F\x7F]/u', '', $descripcion);
        $descripcion = preg_replace('/\s+/u', ' ', trim($descripcion));

        $unidad = mb_strtoupper(trim($unidad), 'UTF-8');

        if ($unidad === '') {
            return mb_substr($descripcion, 0, 150, 'UTF-8');
        }

        $maxDescripcion = 150 - mb_strlen($unidad, 'UTF-8') - 2;
        if ($maxDescripcion < 1) {
            $unidad = mb_substr($unidad, 0, 40, 'UTF-8');
            $maxDescripcion = 150 - mb_strlen($unidad, 'UTF-8') - 2;
        }
        $descripcion = mb_substr($descripcion, 0, max(0, $maxDescripcion), 'UTF-8');

        return $descripcion . '|' . $unidad . '|';
    }

    /**
     * Convierte un valor de celda (numérico o texto) a precio float con 2 decimales.
     * Devuelve null si no es un precio válido.
     */
    private function parsearPrecio($valor): ?float
    {
        if (is_int($valor) || is_float($valor)) {
            return round((float)$valor, 2);
        }

        $texto = trim((string)$valor);
        if ($texto === '') {
            return null;
        }

        // Eliminar todo salvo dígitos, puntos, comas y signo menos
        $texto = preg_replace('/[^\d,.\-]/u', '', $texto);

        $tienePunto = strpos($texto, '.') !== false;
        $tieneComa  = strpos($texto, ',') !== false;

        if ($tienePunto && $tieneComa) {
            // Formato según el ÚLTIMO separador decimal que aparezca:
            //   "1.234,56" -> coma al final => latino (punto = miles)
            //   "1,234.56" -> punto al final => inglés (coma = miles)
            if (strrpos($texto, ',') > strrpos($texto, '.')) {
                // Latino: quitar puntos, coma -> punto decimal
                $texto = str_replace('.', '', $texto);
                $texto = str_replace(',', '.', $texto);
            } else {
                // Inglés: quitar comas, se conserva el punto decimal
                $texto = str_replace(',', '', $texto);
            }
        } elseif ($tieneComa && !$tienePunto) {
            // Solo coma: se asume coma decimal
            $texto = str_replace(',', '.', $texto);
        }
        // Solo punto: se asume punto decimal

        if (!is_numeric($texto)) {
            return null;
        }

        $precio = (float)$texto;
        if ($precio <= 0) {
            return null;
        }

        return round($precio, 2);
    }

    /**
     * Recorre el Excel y guarda los productos en la BD dentro de una transacción.
     */
    private function executeImportarProductosExcel(string $rutaArchivo, int $idProveedor): array
    {
        if (!file_exists($rutaArchivo) || !is_readable($rutaArchivo)) {
            throw new \RuntimeException('El archivo guardado no existe o no es legible.');
        }

        $lector = IOFactory::createReaderForFile($rutaArchivo);
        $lector->setReadDataOnly(true);
        $documento = $lector->load($rutaArchivo);

        $mapaPartidas = $this->executeObtenerMapaPartidas();
        $clavesExistentes = $this->executeObtenerClavesExistentes($idProveedor);

        $resumen = [
            'insertados'    => 0,
            'omitidos'      => 0,
            'errores'       => 0,
            'hojas'         => [],
            'detalles_error' => []
        ];

        $agregarError = function (string $detalle) use (&$resumen) {
            $resumen['errores']++;
            if (count($resumen['detalles_error']) < 20) {
                $resumen['detalles_error'][] = $detalle;
            }
        };

        $this->conex->beginTransaction();

        try {
            foreach ($documento->getWorksheetIterator() as $hoja) {
                $titulo = trim($hoja->getTitle());
                $codigoHoja = preg_replace('/\D+/', '', $titulo);

                // Hojas que no corresponden a una partida activa (TITULO, TOTAL POA, etc.)
                if ($codigoHoja === '' || !isset($mapaPartidas[$codigoHoja])) {
                    continue;
                }

                $idPartida = $mapaPartidas[$codigoHoja];
                $estructura = $this->detectarEstructuraHoja($hoja);

                if ($estructura['fila_encabezado'] === null) {
                    $agregarError("Hoja {$titulo}: no se encontró la fila de encabezados.");
                    continue;
                }

                $colDesc   = $estructura['col_desc'];
                $colUnidad = $estructura['col_unidad'];
                $colPrecio = $estructura['col_precio'];

                $filaInicial = $estructura['fila_encabezado'] + 1;
                $filaFinal   = (int)$hoja->getHighestDataRow();

                $hojaConDatos = false;

                for ($fila = $filaInicial; $fila <= $filaFinal; $fila++) {
                    $celdaDesc = $hoja->getCell($colDesc . $fila);
                    $descripcion = trim((string)$celdaDesc->getCalculatedValue());

                    if ($descripcion === '') {
                        continue;
                    }

                    // Filas de totales
                    if (preg_match('/^(TOTAL|SUBTOTAL)\b/i', $descripcion)) {
                        continue;
                    }

                    $celdaUnidad = $hoja->getCell($colUnidad . $fila);
                    $unidad = trim((string)$celdaUnidad->getCalculatedValue());

                    $celdaPrecio = $hoja->getCell($colPrecio . $fila);
                    $precio = $this->parsearPrecio($celdaPrecio->getCalculatedValue());

                    if ($precio === null) {
                        $agregarError("Hoja {$titulo}, fila {$fila}: precio vacío o inválido.");
                        continue;
                    }

                    $nombre = $this->formatearNombreProducto($descripcion, $unidad);

                    // Validar longitud y contenido
                    if (mb_strlen($nombre, 'UTF-8') > 150) {
                        $nombre = mb_substr($nombre, 0, 150, 'UTF-8');
                    }
                    if (trim($nombre, "| \t") === '') {
                        $agregarError("Hoja {$titulo}, fila {$fila}: nombre vacío.");
                        continue;
                    }

                    // Evitar duplicados (partida + proveedor + nombre)
                    $clave = $idPartida . '|' . mb_strtolower($nombre, 'UTF-8');
                    if (isset($clavesExistentes[$clave])) {
                        $resumen['omitidos']++;
                        continue;
                    }

                    $this->executeInsertarProducto($idPartida, $idProveedor, $nombre, $precio);

                    // Registrar en claves para detectar duplicados dentro del mismo archivo
                    $clavesExistentes[$clave] = true;
                    $resumen['insertados']++;
                    $hojaConDatos = true;
                }

                if ($hojaConDatos) {
                    $resumen['hojas'][] = $titulo;
                }
            }

            $this->conex->commit();
        } catch (\Throwable $e) {
            $this->conex->rollBack();
            throw $e;
        } finally {
            $documento->disconnectWorksheets();
            unset($documento);
        }

        if ($resumen['insertados'] === 0 && $resumen['omitidos'] === 0 && $resumen['errores'] === 0) {
            $resumen['detalles_error'][] = 'No se encontraron filas de datos válidas en el archivo.';
        }

        return $resumen;
    }
}
