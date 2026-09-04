<?php

    use EquipoSiap\Siap\model\responsableModel;
    $object = new responsableModel();
    require_once "app/config/session.php";

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/responsable/listView.php';

        } elseif ($_GET['type'] == 'register') {

            $roles = $object->getRoles();
            $dependenciasDisponibles = $object->getAvailableDependencias();

            if (isset($_POST['registerResponsable'])) {
                if (isset($_POST['nom_rep']) && isset($_POST['contrasena']) && isset($_POST['id_rol'])) {
                    $nombre = trim($_POST['nom_rep']);
                    if ($nombre === '') {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'El nombre no puede estar vacío.']);
                        die();
                    }
                    // Evitar duplicados por nombre (case-insensitive)
                    if ($object->existsByName($nombre)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Ya existe un responsable con ese nombre.']);
                        die();
                    }
                    $idDep = isset($_POST['id_dep']) ? (int)$_POST['id_dep'] : 0;
                    // Respaldo: si el navegador tiene la página vieja en caché (datalist),
                    // resolvemos el ID por el nombre escrito, sin importar mayúsculas.
                    if ($idDep <= 0 && isset($_POST['dependencia_search'])) {
                        $buscada = strtolower(trim((string)$_POST['dependencia_search']));
                        foreach ($dependenciasDisponibles as $dep) {
                            if (strtolower(trim($dep['nom_dep'])) === $buscada) {
                                $idDep = (int)$dep['id_dep'];
                                break;
                            }
                        }
                    }
                    if ($idDep <= 0) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Dependencia inválida. Recargue la página con Ctrl+F5, escriba el nombre y seleccione la dependencia de la lista.']);
                        die();
                    }
                    $result = $object->add(
                        $nombre,
                        $_POST['contrasena'],
                        (int)$_POST['id_rol'],
                        $idDep
                    );
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Responsable registrado exitosamente' : 'Error al registrar en la base de datos.', 'redirect' => '?url=responsable&type=main']);
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
            if (isset($_POST['deleteResponsable'])) {
                $id = (int)$_POST['idItem'];
                $result = $object->delete($id);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Responsable eliminado' : 'Error al eliminar']);
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
