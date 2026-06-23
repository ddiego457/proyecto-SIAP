<?php

    use App\PracticaCrud\Model\proveedorModel;
    $object = new proveedorModel();

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/proveedor/userView.php';

        } elseif ($_GET['type'] == 'register') {

            if (isset($_POST['registerProveedor'])) {
                if (isset($_POST['nombre']) && isset($_POST['descripcion'])) {
                    $result = $object->add($_POST['nombre'], $_POST['descripcion']);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'redirect' => '?url=proveedor&type=main']);
                    die();
                }
            }
            include 'app/view/proveedor/registerView.php';

        } elseif ($_GET['type'] == 'contacts') {
            if (isset($_POST['getContacts'])) {
                echo json_encode($object->getContacts((int)$_POST['idProveedor']));
                die();
            }
            if (isset($_POST['addContact'])) {
                echo json_encode($object->addContact((int)$_POST['idProveedor'], (string)$_POST['telefono']));
                die();
            }
            if (isset($_POST['updateContact'])) {
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

            $idProveedor = isset($_GET['idProveedor']) ? (int)$_GET['idProveedor'] : 0;
            if ($idProveedor <= 0) {
                echo "Proveedor no válido.";
                die();
            }
            $proveedorInfo = $object->getById($idProveedor);
            if (!$proveedorInfo) {
                echo "Proveedor no encontrado.";
                die();
            }
            $idProveedor = $proveedorInfo['id_proveedor'];
            $proveedorNombre = $proveedorInfo['nombre'];
            include 'app/view/proveedor/providerContactsView.php';
            die();

        } elseif ($_GET['type'] == 'main') {

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
                die();
            }

            if (isset($_POST['updateItem'])) {
                $result = $object->update(
                    (int)$_POST['idItem'],
                    (string)$_POST['nombre'],
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
                echo json_encode($object->addContact((int)$_POST['idProveedor'], (string)$_POST['telefono']));
                die();
            }
            if (isset($_POST['updateContact'])) {
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
            include 'app/view/proveedor/userView.php';

        } else {
            echo "Error: Tipo de vista no valido.";
        }

    } else {
        include 'app/view/welcomeView.php';
    }

?>
