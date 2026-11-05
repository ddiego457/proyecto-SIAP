<?php

    // ============================================================
    // Módulo PERFIL: cambio de foto de perfil del responsable
    // logueado. La foto se guarda como archivo en
    // assets/img/perfiles/ y la BD solo almacena el nombre.
    // Accesible también para usuarios no administradores
    // (ver 'perfil' en SessionTrait::$publicModules).
    //
    // Toda la validación del archivo, el proceso de imagen
    // (recorte + redimensionado) y el manejo de archivos/BD
    // viven en responsableModel::subirFoto() / eliminarFoto().
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
        $archivo = isset($_FILES['foto']) && is_array($_FILES['foto']) ? $_FILES['foto'] : [];
        sendJson($object->subirFoto($idResponsable, $archivo));
    }

    // ─── Eliminar foto (vuelve a mostrarse la letra inicial) ──────
    if (isset($_POST['deleteFoto'])) {
        sendJson($object->eliminarFoto($idResponsable));
    }

    // Cualquier otra petición a este módulo no tiene acciones válidas
    sendJson(['success' => false, 'message' => 'Acción no válida.']);

?>
