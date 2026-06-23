<?php
    use App\PracticaCrud\Model\tasaBCVModel;
    $object = new tasaBCVModel();

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/tasaBCV/listView.php';

        } elseif ($_GET['type'] == 'register') {

            if (isset($_POST['registerTasa'])) {
                if (isset($_POST['tasa_bcv_usd']) && isset($_POST['fecha_reg'])) {
                    // El formulario usa los nombres exactos de columnas de tasa_bcv
                    $result = $object->add((float)$_POST['tasa_bcv_usd'], $_POST['fecha_reg'], 1);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => (bool)$result, 'redirect' => '?url=tasaBCV&type=main']);
                    die();
                }
            }
            include 'app/view/tasaBCV/registerView.php';

        } elseif ($_GET['type'] == 'main') {

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
                die();
            }
            if (isset($_POST['deleteItem'])) {
                echo json_encode($object->delete((int)$_POST['id_tasa']));
                die();
            }
            if (isset($_POST['inactivateItem'])) {
                echo json_encode($object->inactivate((int)$_POST['id_tasa']));
                die();
            }
            if (isset($_POST['activateItem'])) {
                echo json_encode($object->activate((int)$_POST['id_tasa']));
                die();
            }
            if (isset($_POST['updateItem'])) {
                $estado = 1;
                if (isset($_POST['estado'])) {
                    $estado = (int)$_POST['estado'];
                }
                $result = $object->update(
                    (int)$_POST['id_tasa'],
                    (float)$_POST['tasa_bcv_usd'],
                    $_POST['fecha_reg'],
                    $estado
                );
                echo json_encode($result);
                die();
            }
            include 'app/view/tasaBCV/userView.php';

        } elseif ($_GET['type'] == 'current') {
            // Devuelve la tasa activa más reciente (JSON)
            header('Content-Type: application/json');
            echo json_encode($object->getLatestActive());
            die();

        } else {
            echo "Error: Tipo de vista no valido.";
        }

    } else {
        include 'app/view/welcomeView.php';
    }

?>

