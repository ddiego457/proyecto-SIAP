<?php

    use EquipoSiap\Siap\model\proveedorModel;
    $this->requireLogin();
    $this->requireModule('proveedor');
    $object = new proveedorModel();

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/proveedor/proveedorUserView.php';

        } elseif ($_GET['type'] == 'register') {

            // El registro ahora es un modal dentro de la vista principal
            header('Location: ?url=proveedor&type=main');
            exit;

        } elseif ($_GET['type'] == 'pdf') {

            if (isset($_POST['subirPdf'])) {
                $idProveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
                $archivo = isset($_FILES['archivoPdf']) ? $_FILES['archivoPdf'] : [];

                $resultado = $object->subirPdf($idProveedor, $archivo);

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($resultado);
                die();
            }

            if (isset($_POST['obtenerPdf'])) {
                $idProveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
                $pdf = $idProveedor > 0 ? $object->obtenerPdf($idProveedor) : null;

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'pdf' => $pdf]);
                die();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            die();

        } elseif ($_GET['type'] == 'main') {

            if (isset($_POST['registerProveedor'])) {
                if (isset($_POST['nombre']) && isset($_POST['descripcion'])) {
                    $nombre = trim($_POST['nombre']);
                    $errorValidacion = $object->validar('nombre_proveedor', $nombre);
                    if ($errorValidacion !== null) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => $errorValidacion]);
                        die();
                    }
                    $errorValidacion = $object->validar('descripcion_proveedor', $_POST['descripcion']);
                    if ($errorValidacion !== null) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => $errorValidacion]);
                        die();
                    }
                    // Evitar duplicados por nombre (case-insensitive)
                    if ($object->existsByNombre($nombre)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Ya existe un proveedor con ese nombre.']);
                        die();
                    }
                    // PDF opcional adjunto al registro. Se valida ANTES de crear
                    // el proveedor para no dejarlo a medias si el archivo falla.
                    $archivoPdf = isset($_FILES['archivoPdf']) ? $_FILES['archivoPdf'] : [];
                    $tienePdf = !empty($archivoPdf)
                        && (int)($archivoPdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    if ($tienePdf) {
                        $errorPdf = $object->validarArchivoPdf($archivoPdf);
                        if ($errorPdf !== null) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'message' => $errorPdf]);
                            die();
                        }
                    }

                    $result = $object->add($nombre, $_POST['descripcion']);

                    // Mensaje según el resultado (y según el PDF, si se adjuntó)
                    $mensaje = $result ? 'Proveedor registrado' : 'Error al registrar (duplicado o error BD).';
                    if ($result && $tienePdf) {
                        $pdfResultado = $object->subirPdf($object->lastInsertId(), $archivoPdf);
                        if (!empty($pdfResultado['success'])) {
                            $mensaje = 'Proveedor y documento PDF registrados';
                        } else {
                            $mensaje = 'Proveedor registrado, pero el PDF no se pudo guardar: '
                                . ($pdfResultado['message'] ?? 'error desconocido.');
                        }
                    }

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'message' => $mensaje]);
                    die();
                }
            }

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
                die();
            }

            if (isset($_POST['updateItem'])) {
                $nombreUpdate = isset($_POST['nombre']) ? (string)$_POST['nombre'] : '';
                $errorValidacion = $object->validar('nombre_proveedor', $nombreUpdate);
                if ($errorValidacion === null && isset($_POST['descripcion']) && $_POST['descripcion'] !== null) {
                    $errorValidacion = $object->validar('descripcion_proveedor', (string)$_POST['descripcion']);
                }
                if ($errorValidacion !== null) {
                    echo json_encode(['success' => false, 'message' => $errorValidacion]);
                    die();
                }
                $result = $object->update(
                    (int)$_POST['idItem'],
                    $nombreUpdate,
                    isset($_POST['descripcion']) ? (string)$_POST['descripcion'] : null,
                    isset($_POST['estado']) ? (int)$_POST['estado'] : null
                );
                echo json_encode($result);
                die();
            }
            if (isset($_POST['inactivateItem'])) {
                echo json_encode($object->inactivate((int)$_POST['idItem']));
                die();
            }
            if (isset($_POST['activateItem'])) {
                echo json_encode($object->activate((int)$_POST['idItem']));
                die();
            }
            if (isset($_POST['getContacts'])) {
                echo json_encode($object->getContacts((int)$_POST['idProveedor']));
                die();
            }
            if (isset($_POST['addContact'])) {
                $errorValidacion = $object->validar('telefono', $_POST['telefono'] ?? '');
                if ($errorValidacion !== null) {
                    echo json_encode(false);
                    die();
                }
                echo json_encode($object->addContact((int)$_POST['idProveedor'], (string)$_POST['telefono']));
                die();
            }
            if (isset($_POST['updateContact'])) {
                $errorValidacion = $object->validar('telefono', $_POST['telefono'] ?? '');
                if ($errorValidacion !== null) {
                    echo json_encode(false);
                    die();
                }
                echo json_encode($object->updateContact((int)$_POST['idContacto'], (string)$_POST['telefono'], isset($_POST['estado']) ? (int)$_POST['estado'] : null));
                die();
            }
            if (isset($_POST['deleteContact'])) {
                echo json_encode($object->deleteContact((int)$_POST['idContacto']));
                die();
            }
            if (isset($_POST['activateContact'])) {
                echo json_encode($object->activateContact((int)$_POST['idContacto']));
                die();
            }
            if (isset($_POST['inactivateContact'])) {
                echo json_encode($object->inactivateContact((int)$_POST['idContacto']));
                die();
            }
            include 'app/view/proveedor/proveedorUserView.php';

        } else {
            http_response_code(400);
            $failDescript = "Tipo de vista no válido en el módulo 'proveedor': '" . $_GET['type'] . "'.";
            require_once dirname(__DIR__) . '/view/errorView.php';
            exit;
        }

    } else {
        header('Location: ?url=proveedor&type=main');
        exit;
    }

?>
