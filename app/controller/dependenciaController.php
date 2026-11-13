<?php

    use EquipoSiap\Siap\model\dependenciaModel;
    $this->requireLogin();
    $this->requireModule('dependencia');
    $object = new dependenciaModel();


    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            header('Location: ?url=dependencia&type=main');
            exit;

        } elseif ($_GET['type'] == 'register') {

            // El registro ahora es un modal dentro de la vista principal
            header('Location: ?url=dependencia&type=main');
            exit;

        } elseif ($_GET['type'] == 'main') {

            if (isset($_POST['registerDependencia'])) {
                if (isset($_POST['nombre_dep'])) {
                    $nombre = trim($_POST['nombre_dep']);
                    $errorValidacion = $object->validar('nombre_dependencia', $nombre);
                    if ($errorValidacion !== null) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => $errorValidacion]);
                        die();
                    }
                    // Evitar duplicados por nombre (case-insensitive)
                    $existe = $object->existsByName($nombre);
                    if ($existe) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Ya existe una dependencia con ese nombre.']);
                        die();
                    }
                    $result = $object->add($nombre);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Dependencia registrada' : 'Error al registrar']);
                    die();
                }
            }

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
                die();
            }
            if (isset($_POST['inactivateItem'])) {
                $result = (bool)$object->delete((string)$_POST['idItem']);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => $result, 'message' => $result ? 'Registro inhabilitado' : 'Error al inhabilitar']);
                die();
            }
            if (isset($_POST['activateItem'])) {
                $result = (bool)$object->activate((string)$_POST['idItem']);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => $result, 'message' => $result ? 'Registro activado' : 'Error al activar']);
                die();
            }
            if (isset($_POST['updateItem'])) {
                $idItem = (string)($_POST['idItem'] ?? '');
                $nombreDep = (string)($_POST['nombre_dep'] ?? '');
                $nuevoEstado = isset($_POST['estado']) ? (int)$_POST['estado'] : null;

                $errorValidacion = $object->validar('nombre_dependencia', $nombreDep);
                if ($errorValidacion !== null) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errorValidacion]);
                    die();
                }

                $result = $object->update($idItem, $nombreDep, $nuevoEstado);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Dependencia actualizada' : 'Error al actualizar']);
                die();
            }
            include 'app/view/dependencia/dependenciaUserView.php';

        } else {
            http_response_code(400);
            $failDescript = "Tipo de vista no válido en el módulo 'dependencia': '" . $_GET['type'] . "'.";
            require_once dirname(__DIR__) . '/view/errorView.php';
            exit;
        }

    } else {
        header('Location: ?url=dependencia&type=main');
        exit;
    }

?>