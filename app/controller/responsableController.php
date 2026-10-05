<?php

    use EquipoSiap\Siap\model\responsableModel;
    $this->requireLogin();
    $this->requireModule('responsable');
    $object = new responsableModel();

    // Rol crítico: su descripción se compara como texto en session.php,
    // sidebar.php y requerimientoController.php, por lo que no se puede eliminar.
    if (!defined('ROL_PROTEGIDO')) {
        define('ROL_PROTEGIDO', 'Administrador');
    }

    if (!function_exists('sendJson')) {
        function sendJson($payload)
        {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload);
            die();
        }
    }

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'register') {

            $roles = $object->getRoles();
            $dependenciasDisponibles = $object->getAvailableDependencias();

            if (isset($_POST['registerResponsable'])) {
                if (isset($_POST['nom_rep']) && isset($_POST['contrasena']) && isset($_POST['id_rol']) && isset($_POST['email'])) {
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
                    // Validar correo electrónico
                    $email = strtolower(trim((string)$_POST['email']));
                    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($object->expEmail, $email)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
                        die();
                    }
                    // Evitar duplicados por correo
                    if ($object->existsByEmail($email)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Ya existe un responsable con ese correo.']);
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
                        $idDep,
                        $email
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
                $idRol = isset($_POST['id_rol']) ? (int)$_POST['id_rol'] : null;
                $email = isset($_POST['email']) ? trim((string)$_POST['email']) : null;
                if ($email !== null && $email !== '') {
                    $email = strtolower($email);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($object->expEmail, $email)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
                        die();
                    }
                    if ($object->existsByEmail($email, $id)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Ya existe un responsable con ese correo.']);
                        die();
                    }
                }
                $result = $object->update($id, $nom, $pass, $idRol, $email);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Responsable actualizado' : 'Error al actualizar']);
                die();
            }
            if (isset($_POST['toggleEstado'])) {
                $id = (int)$_POST['idItem'];
                $newEstado = isset($_POST['newState']) ? (int)$_POST['newState'] : null;
                $result = false;
                if ($newEstado !== 1) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Para dar de baja un responsable utilice la opción Eliminar, que cierra su cargo y libera la dependencia.']);
                    die();
                }
                $result = $object->activate($id);
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

            if (isset($_POST['getAvailableDependenciasJson'])) {
                echo json_encode($object->getAvailableDependencias());
                die();
            }

            if (isset($_POST['assignDepToResponsable'])) {
                $idResponsable = isset($_POST['id_responsable']) ? (int)$_POST['id_responsable'] : 0;
                $idDep = isset($_POST['id_dep']) ? (int)$_POST['id_dep'] : 0;
                $fechaInicio = isset($_POST['fecha_inicio']) ? trim((string)$_POST['fecha_inicio']) : date('Y-m-d');

                if ($idResponsable <= 0 || $idDep <= 0) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Datos inválidos para asignar dependencia.']);
                    die();
                }

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                    $fechaInicio = date('Y-m-d');
                }

                // Actualizar datos básicos si vienen
                $nom = (string)($_POST['nom_rep'] ?? '');
                $pass = isset($_POST['contrasena']) ? (string)$_POST['contrasena'] : null;
                $idRol = isset($_POST['id_rol']) ? (int)$_POST['id_rol'] : null;
                $email = isset($_POST['email']) ? trim((string)$_POST['email']) : null;
                if ($email !== null && $email !== '') {
                    $email = strtolower($email);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($object->expEmail, $email)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
                        die();
                    }
                    if ($object->existsByEmail($email, $idResponsable)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Ya existe un responsable con ese correo.']);
                        die();
                    }
                }
                $object->update($idResponsable, $nom, $pass, $idRol, $email);

                // Asignar dependencia
                $res = $object->assignToDependencia($idResponsable, $idDep, $fechaInicio);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$res, 'message' => $res ? 'Responsable actualizado y dependencia asignada.' : 'Error al asignar la dependencia.']);
                die();
            }

            // ─── Roles: alta, edición y eliminación lógica ─────────────
            if (isset($_POST['getAllRoles'])) {
                sendJson($object->getAllRoles());
            }

            if (isset($_POST['registerRol'])) {
                $descripcion = isset($_POST['descripcion']) ? trim((string)$_POST['descripcion']) : '';

                if ($descripcion === '') {
                    sendJson(['success' => false, 'message' => 'La descripción del rol no puede estar vacía.']);
                }

                if (mb_strlen($descripcion) > 70) {
                    sendJson(['success' => false, 'message' => 'La descripción del rol excede el máximo de 70 caracteres.']);
                }

                if ($object->existsRol($descripcion)) {
                    sendJson(['success' => false, 'message' => 'Ya existe un rol con esa descripción.']);
                }

                $result = $object->addRol($descripcion);
                sendJson(['success' => (bool)$result, 'message' => $result ? 'Rol registrado exitosamente' : 'Error al registrar el rol.']);
            }

            if (isset($_POST['updateRol'])) {
                $idRol = isset($_POST['idRol']) ? (int)$_POST['idRol'] : 0;
                $descripcion = isset($_POST['descripcion']) ? trim((string)$_POST['descripcion']) : '';

                if ($idRol <= 0) {
                    sendJson(['success' => false, 'message' => 'Rol no válido.']);
                }

                if ($descripcion === '') {
                    sendJson(['success' => false, 'message' => 'La descripción del rol no puede estar vacía.']);
                }

                if (mb_strlen($descripcion) > 70) {
                    sendJson(['success' => false, 'message' => 'La descripción del rol excede el máximo de 70 caracteres.']);
                }

                if ($object->existsRol($descripcion, $idRol)) {
                    sendJson(['success' => false, 'message' => 'Ya existe otro rol con esa descripción.']);
                }

                $result = $object->updateRol($idRol, $descripcion);
                sendJson(['success' => (bool)$result, 'message' => $result ? 'Rol actualizado' : 'Error al actualizar el rol.']);
            }

            if (isset($_POST['deleteRol'])) {
                $idRol = isset($_POST['idRol']) ? (int)$_POST['idRol'] : 0;

                if ($idRol <= 0) {
                    sendJson(['success' => false, 'message' => 'Rol no válido.']);
                }

                $rol = $object->getRolById($idRol);
                if (!$rol) {
                    sendJson(['success' => false, 'message' => 'El rol no existe.']);
                }

                if ($rol['descripcion'] === ROL_PROTEGIDO) {
                    sendJson(['success' => false, 'message' => 'No se puede eliminar el rol ' . ROL_PROTEGIDO . '.']);
                }

                $responsables = $object->countResponsablesActivos($idRol);
                if ($responsables > 0) {
                    sendJson([
                        'success' => false,
                        'message' => 'El rol tiene ' . $responsables . ' responsable(s) activo(s). Reasígnelos antes de eliminar el rol.'
                    ]);
                }

                $res = $object->deleteRol($idRol);
                sendJson(['success' => (bool)$res, 'message' => $res ? 'Rol eliminado' : 'Error al eliminar el rol.']);
            }

            if (isset($_POST['activateRol'])) {
                $idRol = isset($_POST['idRol']) ? (int)$_POST['idRol'] : 0;

                if ($idRol <= 0) {
                    sendJson(['success' => false, 'message' => 'Rol no válido.']);
                }

                $res = $object->activateRol($idRol);
                sendJson(['success' => (bool)$res, 'message' => $res ? 'Rol activado' : 'Error al activar el rol.']);
            }

            include 'app/view/responsable/userView.php';

        } else {
            echo "Error: Tipo de vista no valido.";
        }

    } else {
        include 'app/view/welcomeView.php';
    }

?>
