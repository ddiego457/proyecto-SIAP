<?php

use EquipoSiap\Siap\model\productosServiciosModel;
use EquipoSiap\Siap\model\proveedorModel;

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

        if (isset($_POST['updateItem'])) {
            $idProveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
            $validProveedorIds = array_map('intval', array_column($proveedores, 'id_proveedor'));

            if ($idProveedor <= 0 || !in_array($idProveedor, $validProveedorIds, true)) {
                sendJsonResponse(['success' => false, 'message' => 'Proveedor inválido.']);
            }

            $result = $object->update(
                (int)$_POST['idItem'],
                (int)$_POST['id_partida'],
                $idProveedor,
                trim((string)$_POST['nom_item']),
                (float)$_POST['precio']
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
                $idProveedor <= 0 || !in_array($idProveedor, $validProveedorIds, true) ||
                $nombre === '' || $precio === null
            ) {
                sendJsonResponse(['success' => false, 'message' => 'Faltan campos obligatorios, partida o proveedor inválido.']);
            }

            $result = $object->add($idPartida, $idProveedor, $nombre, $precio);
            $payload = ['success' => (bool)$result, 'message' => $result ? 'Producto o servicio registrado' : 'Error al guardar el registro.'];
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
