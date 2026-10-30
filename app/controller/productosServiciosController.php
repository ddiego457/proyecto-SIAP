<?php

use EquipoSiap\Siap\model\productosServiciosModel;
use EquipoSiap\Siap\model\proveedorModel;
$this->requireLogin();
$this->requireModule('productosServicios');


$object = new productosServiciosModel();
$proveedorModel = new proveedorModel();
$partidas = $object->getPartidas();
$proveedores = $proveedorModel->getActive();
$partidaSeleccionada = null;

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (isset($_GET['type'])) {
    if ($_GET['type'] === 'list' || $_GET['type'] === 'main') {
        if (!empty($partidas)) {
            $partidaSeleccionada = (int)$partidas[0]['id_partida'];
        }

        //if (isset($_GET['partida'])) {
        //    $partidaSeleccionada = (int)$_GET['partida'];
        //}

        if (isset($_POST['partidaId'])) {
            $partidaSeleccionada = (int)$_POST['partidaId'];
        }

        if (isset($_POST['getAll'])) {
            sendJsonResponse($object->getAll($partidaSeleccionada));
        }

        if (isset($_POST['deleteItem'])) {
            $res = $object->inhabilitar((int)$_POST['idItem']);
            sendJsonResponse(['success' => (bool)$res, 'message' => $res ? 'Registro inhabilitado' : 'Error al inhabilitar']);
        }

        if (isset($_POST['getAllPartidas'])) {
            sendJsonResponse($object->getAllPartidas());
        }

        if (isset($_POST['registerPartida'])) {
            $codPartida = isset($_POST['cod_partida']) ? trim((string)$_POST['cod_partida']) : '';
            $descripcion = isset($_POST['descripcion']) ? trim((string)$_POST['descripcion']) : '';

            $errorValidacion = $object->validar('codigo_partida', $codPartida);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }
            $errorValidacion = $object->validar('descripcion_partida', $descripcion);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }

            // Evitar partidas duplicadas por codigo
            if ($object->existsPartida($codPartida)) {
                sendJsonResponse(['success' => false, 'message' => 'Ya existe una partida presupuestaria con ese codigo.']);
            }

            $result = $object->addPartida($codPartida, $descripcion);
            sendJsonResponse([
                'success' => (bool)$result,
                'message' => $result ? 'Partida presupuestaria registrada' : 'Error al guardar la partida (codigo duplicado o error BD).'
            ]);
        }

        if (isset($_POST['updatePartida'])) {
            $idPartida = isset($_POST['idPartida']) ? (int)$_POST['idPartida'] : 0;
            $codPartida = isset($_POST['cod_partida']) ? trim((string)$_POST['cod_partida']) : '';
            $descripcion = isset($_POST['descripcion']) ? trim((string)$_POST['descripcion']) : '';
            $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;

            if ($idPartida <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Partida presupuestaria no valida.']);
            }

            $errorValidacion = $object->validar('codigo_partida', $codPartida);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }
            $errorValidacion = $object->validar('descripcion_partida', $descripcion);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }

            // El código no puede pertenecer a otra partida
            if ($object->existsPartida($codPartida, $idPartida)) {
                sendJsonResponse(['success' => false, 'message' => 'Ya existe otra partida presupuestaria con ese codigo.']);
            }

            $result = $object->updatePartida($idPartida, $codPartida, $descripcion, $estado);
            sendJsonResponse([
                'success' => (bool)$result,
                'message' => $result ? 'Partida presupuestaria actualizada' : 'Error al actualizar la partida (codigo duplicado o error BD).'
            ]);
        }

        if (isset($_POST['deletePartida'])) {
            $idPartida = isset($_POST['idPartida']) ? (int)$_POST['idPartida'] : 0;

            if ($idPartida <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Partida presupuestaria no valida.']);
            }

            $res = $object->deletePartida($idPartida);
            sendJsonResponse(['success' => (bool)$res, 'message' => $res ? 'Partida presupuestaria eliminada' : 'Error al eliminar la partida']);
        }

        if (isset($_POST['activatePartida'])) {
            $idPartida = isset($_POST['idPartida']) ? (int)$_POST['idPartida'] : 0;

            if ($idPartida <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Partida presupuestaria no valida.']);
            }

            $res = $object->activatePartida($idPartida);
            sendJsonResponse(['success' => (bool)$res, 'message' => $res ? 'Partida presupuestaria activada' : 'Error al activar la partida']);
        }

        if (isset($_POST['loadData'])) {
            $res = $object->loadData();
        }

        if (isset($_POST['updateItem'])) {
            $idProveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
            $validProveedorIds = array_map('intval', array_column($proveedores, 'id_proveedor'));

            if ($idProveedor <= 0 || !in_array($idProveedor, $validProveedorIds, true)) {
                sendJsonResponse(['success' => false, 'message' => 'Proveedor inválido.']);
            }

            $nomItemUpdate = isset($_POST['nom_item']) ? trim((string)$_POST['nom_item']) : '';
            $precioUpdate = isset($_POST['precio']) ? trim((string)$_POST['precio']) : '';
            $errorValidacion = $object->validar('nombre_producto', $nomItemUpdate);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }
            $errorValidacion = $object->validar('precio', $precioUpdate);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }

            $result = $object->update(
                (int)$_POST['idItem'],
                (int)$_POST['id_partida'],
                $idProveedor,
                $nomItemUpdate,
                (float)$precioUpdate
            );
            sendJsonResponse(['success' => (bool)$result, 'message' => $result ? 'Registro actualizado' : 'Error al actualizar']);
        }

        include 'app/view/productosServicios/userView.php';
        return;
    }

    if ($_GET['type'] === 'register') {
        if (isset($_POST['registerProductosServicios'])) {
            $idPartida = isset($_POST['id_partida']) ? (int)$_POST['id_partida'] : 0;
            $idProveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
            $nombre = isset($_POST['nom_item']) ? trim((string)$_POST['nom_item']) : '';
            $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : null;

            $validPartidaIds = array_map('intval', array_column($partidas, 'id_partida'));
            $validProveedorIds = array_map('intval', array_column($proveedores, 'id_proveedor'));
            if ($idPartida <= 0 || !in_array($idPartida, $validPartidaIds, true) ||
                $idProveedor <= 0 || !in_array($idProveedor, $validProveedorIds, true)
            ) {
                sendJsonResponse(['success' => false, 'message' => 'Faltan campos obligatorios, partida o proveedor inválido.']);
            }

            $errorValidacion = $object->validar('nombre_producto', $nombre);
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }
            $errorValidacion = $object->validar('precio', (string)($_POST['precio'] ?? ''));
            if ($errorValidacion !== null) {
                sendJsonResponse(['success' => false, 'message' => $errorValidacion]);
            }

            // Evitar duplicados (misma partida + proveedor + nombre)
            if ($object->existsByKey($idPartida, $idProveedor, $nombre)) {
                sendJsonResponse(['success' => false, 'message' => 'Ya existe un producto/servicio con ese nombre para la misma partida y proveedor.']);
            }

            $result = $object->add($idPartida, $idProveedor, $nombre, $precio);
            $payload = ['success' => (bool)$result, 'message' => $result ? 'Producto o servicio registrado' : 'Error al guardar el registro (duplicado o error BD).'];
            if ($result) $payload['redirect'] = '?url=productosServicios&type=main';
            sendJsonResponse($payload);
        }

        include 'app/view/productosServicios/registerView.php';
        return;
    }

    echo "Error: Tipo de vista no valido.";
    return;
}

echo "Error: ruta no valida para productosServicios.";
