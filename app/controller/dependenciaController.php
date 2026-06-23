<?php

    use App\PracticaCrud\Model\dependenciaModel;
    $object = new dependenciaModel();


    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/dependencia/listView.php';

        } elseif ($_GET['type'] == 'register') {

            if (isset($_POST['registerDependencia'])) {
                if (isset($_POST['nombre_dep'])) {
                    $result = $object->add($_POST['nombre_dep']);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'redirect' => '?url=dependencia&type=main']);
                    die();
                }
            }
            include 'app/view/dependencia/registerView.php';

        } elseif ($_GET['type'] == 'main') {

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
                die();
            }
            if (isset($_POST['deleteItem'])) {
                $result = (bool)$object->delete((string)$_POST['idItem']);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => $result, 'message' => $result ? 'Registro eliminado' : 'Error al eliminar']);
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

                $result = $object->update($idItem, $nombreDep, $nuevoEstado);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Dependencia actualizada' : 'Error al actualizar']);
                die();
            }
            include 'app/view/dependencia/userView.php';

        } else {
            echo "Error: Tipo de vista no valido.";
        }

    } else {
        include 'app/view/welcomeView.php';
    }

?>