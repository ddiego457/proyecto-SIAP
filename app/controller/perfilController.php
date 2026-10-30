<?php

    // ============================================================
    // Módulo PERFIL: cambio de foto de perfil del responsable
    // logueado. La foto se guarda como archivo en
    // assets/img/perfiles/ y la BD solo almacena el nombre.
    // Accesible también para usuarios no administradores
    // (ver 'perfil' en SessionTrait::$publicModules).
    //
    // POST esperados:
    //   updateFoto  (+ $_FILES['foto'])  -> subir/cambiar foto
    //   deleteFoto                      -> eliminar foto (vuelve la letra)
    // Respuesta: JSON { success, message[, foto, url] }
    // ============================================================

    use EquipoSiap\Siap\model\responsableModel;
    $this->requireLogin();

    $object = new responsableModel();

    // El id SIEMPRE sale de la sesión: cada responsable solo puede
    // modificar su propia foto (nunca se recibe del formulario).
    $idResponsable = isset($_SESSION['id_responsable']) ? (int)$_SESSION['id_responsable'] : 0;

    // Carpeta de destino (ruta real del proyecto, relativa a este archivo)
    $dirPerfiles = dirname(__DIR__, 2) . '/assets/img/perfiles';

    if (!function_exists('sendJson')) {
        function sendJson($payload)
        {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload);
            die();
        }
    }

    if ($idResponsable <= 0) {
        sendJson(['success' => false, 'message' => 'Sesión no válida. Inicie sesión de nuevo.']);
    }

    // ─── Subir / cambiar foto ─────────────────────────────────────
    if (isset($_POST['updateFoto'])) {

        if (!isset($_FILES['foto']) || !is_array($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            sendJson(['success' => false, 'message' => 'Seleccione una imagen primero.']);
        }

        $archivo = $_FILES['foto'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            sendJson(['success' => false, 'message' => 'Error al subir el archivo (código ' . (int)$archivo['error'] . '). Intente de nuevo.']);
        }

        // Límite de 2 MB (el servidor admite más; es una regla del negocio)
        $maximoBytes = 2 * 1024 * 1024;
        if ((int)$archivo['size'] > $maximoBytes) {
            sendJson(['success' => false, 'message' => 'La imagen no debe superar los 2 MB.']);
        }

        if (!is_uploaded_file($archivo['tmp_name'])) {
            sendJson(['success' => false, 'message' => 'Archivo no válido.']);
        }

        // Tipo REAL de la imagen (no se confía en el MIME declarado por el navegador)
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false) {
            sendJson(['success' => false, 'message' => 'El archivo seleccionado no es una imagen válida.']);
        }

        // Verificar que la extensión GD esté disponible para procesar la imagen
        if (!function_exists('imagecreatetruecolor')) {
            sendJson(['success' => false, 'message' => 'El servidor no tiene habilitada la extensión GD para procesar imágenes. Contacte al administrador.']);
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
                sendJson(['success' => false, 'message' => 'Formato no admitido. Use JPG, PNG o WEBP.']);
        }

        if ($origen === false) {
            sendJson(['success' => false, 'message' => 'No se pudo procesar la imagen. Verifique que el archivo no esté dañado.']);
        }

        // Recorte cuadrado centrado + redimensionado a 256x256 (patrón uniforme)
        $ladoOrigen = min($info[0], $info[1]);
        $sx = (int)(($info[0] - $ladoOrigen) / 2);
        $sy = (int)(($info[1] - $ladoOrigen) / 2);
        $destino = imagecreatetruecolor(256, 256);
        imagecopyresampled($destino, $origen, 0, 0, $sx, $sy, 256, 256, $ladoOrigen, $ladoOrigen);
        imagedestroy($origen);

        // Crear carpeta si no existe
        if (!is_dir($dirPerfiles) && !@mkdir($dirPerfiles, 0755, true)) {
            imagedestroy($destino);
            sendJson(['success' => false, 'message' => 'No se pudo crear la carpeta de imágenes. Verifique permisos en assets/img/.']);
        }

        // Nombre generado por el servidor (nunca el del cliente): evita
        // path traversal y colisiones. Se guarda siempre como JPEG.
        $nombreNuevo = 'resp_' . $idResponsable . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $rutaNueva = $dirPerfiles . '/' . $nombreNuevo;

        if (!@imagejpeg($destino, $rutaNueva, 85)) {
            imagedestroy($destino);
            sendJson(['success' => false, 'message' => 'No se pudo guardar la imagen. Verifique permisos de escritura en assets/img/perfiles/.']);
        }
        imagedestroy($destino);

        // Actualizar la BD (nombre del archivo) y borrar la foto anterior
        $fotoAnterior = $object->getFoto($idResponsable);

        if (!$object->setFoto($idResponsable, $nombreNuevo)) {
            @unlink($rutaNueva);
            sendJson(['success' => false, 'message' => 'No se pudo actualizar la foto en la base de datos.']);
        }

        if ($fotoAnterior !== null && $fotoAnterior !== $nombreNuevo) {
            @unlink($dirPerfiles . '/' . basename($fotoAnterior));
        }

        // Refrescar la sesión para que la sidebar la muestre al instante
        $_SESSION['foto'] = $nombreNuevo;

        sendJson([
            'success' => true,
            'message' => 'Foto de perfil actualizada.',
            'foto'    => $nombreNuevo,
            'url'     => 'assets/img/perfiles/' . $nombreNuevo . '?v=' . time()
        ]);
    }

    // ─── Eliminar foto (vuelve a mostrarse la letra inicial) ──────
    if (isset($_POST['deleteFoto'])) {

        $fotoAnterior = $object->getFoto($idResponsable);

        if (!$object->setFoto($idResponsable, null)) {
            sendJson(['success' => false, 'message' => 'No se pudo eliminar la foto de la base de datos.']);
        }

        if ($fotoAnterior !== null) {
            @unlink($dirPerfiles . '/' . basename($fotoAnterior));
        }

        unset($_SESSION['foto']);

        sendJson(['success' => true, 'message' => 'Foto de perfil eliminada.']);
    }

    // Cualquier otra petición a este módulo no tiene acciones válidas
    sendJson(['success' => false, 'message' => 'Acción no válida.']);

?>
