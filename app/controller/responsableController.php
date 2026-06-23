<?php

    use App\PracticaCrud\Model\responsableModel;
    $object = new responsableModel();

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/responsable/listView.php';

        } elseif ($_GET['type'] == 'register') {

            $roles = $object->getRoles();
            $dependenciasDisponibles = $object->getAvailableDependencias();

            if (isset($_POST['registerResponsable'])) {
                if (isset($_POST['nom_rep']) && isset($_POST['contrasena']) && isset($_POST['id_rol']) && isset($_POST['id_dep'])) {
                    $idDep = (int)$_POST['id_dep'];
                    if ($idDep <= 0) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Dependencia inválida.']);
                        die();
                    }
                    $result = $object->add(
                        $_POST['nom_rep'],
                        $_POST['contrasena'],
                        (int)$_POST['id_rol'],
                        $idDep
                    );
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'redirect' => '?url=responsable&type=main']);
                    die();
                }
            }
            include 'app/view/responsable/registerView.php';

        } elseif ($_GET['type'] == 'main') {

            $dependencias = $object->getDependencias();
            $roles = $object->getRoles();

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
                die();
            }

            if (isset($_POST['updateItem'])) {
                $id = (int)$_POST['idItem'];
                $nom = (string)($_POST['nom_rep'] ?? '');
                $pass = isset($_POST['contrasena']) ? (string)$_POST['contrasena'] : null;
                $estado = null; // estado se maneja solo desde el botón de activar/inactivar
                $idRol = isset($_POST['id_rol']) ? (int)$_POST['id_rol'] : null;
                $result = $object->update($id, $nom, $pass, $estado, $idRol);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Responsable actualizado' : 'Error al actualizar']);
                die();
            }
            if (isset($_POST['toggleEstado'])) {
                $id = (int)$_POST['idItem'];
                $newEstado = isset($_POST['newState']) ? (int)$_POST['newState'] : null;
                $result = false;
                if ($newEstado !== null) {
                    $result = $object->update($id, (string)($_POST['nom_rep'] ?? ''), null, $newEstado, null);
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Estado actualizado' : 'Error al cambiar estado']);
                die();
            }
            if (isset($_POST['assignCargo'])) {
                $res = $object->assignToDependencia((int)$_POST['id_responsable'], (int)$_POST['id_dep'], (string)$_POST['fecha_inicio']);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$res, 'message' => $res ? 'Responsable asignado' : 'Error al asignar dependencia']);
                die();
            }
            if (isset($_POST['getCargosByDep'])) {
                echo json_encode($object->getCargosByDependencia((int)$_POST['id_dep']));
                die();
            }

            include 'app/view/responsable/userView.php';

        } else {
            echo "Error: Tipo de vista no valido.";
        }

    } else {
        include 'app/view/welcomeView.php';
    }

?>
