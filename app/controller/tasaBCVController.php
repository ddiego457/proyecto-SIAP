<?php
    use EquipoSiap\Siap\model\tasaBCVModel;
    $this->requireLogin();
    $this->requireModule('tasaBCV');
    $object = new tasaBCVModel();

    if (isset($_GET['type'])) {

        if ($_GET['type'] == 'list') {

            $result = $object->getAll();
            include 'app/view/tasaBCV/listView.php';

        } elseif ($_GET['type'] == 'register') {

            // El registro ahora es un modal dentro de la vista principal
            header('Location: ?url=tasaBCV&type=main');
            exit;

        } elseif ($_GET['type'] == 'main') {

            if (isset($_POST['registerTasa'])) {
                if (isset($_POST['tasa_bcv_usd']) && isset($_POST['fecha_reg'])) {
                    // El formulario usa los nombres exactos de columnas de tasa_bcv.
                    // Las validaciones (formato y duplicado) viven en el modelo.
                    // Origen del valor: 'automatica' si el JS confirmó que
                    // coincide con la API dolarapi.com, si no 'manual'.
                    $origen = (isset($_POST['origen']) && in_array($_POST['origen'], ['manual', 'automatica'], true))
                        ? (string)$_POST['origen']
                        : 'manual';
                    $resultado = $object->add(
                        (string)$_POST['tasa_bcv_usd'],
                        (string)$_POST['fecha_reg'],
                        1,
                        $origen
                    );
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($resultado);
                    die();
                }
            }

            if (isset($_POST['getAll'])) {
                echo json_encode($object->getAll());
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
                // Valores crudos: el modelo valida antes de convertir
                $result = $object->update(
                    (int)$_POST['id_tasa'],
                    (string)($_POST['tasa_bcv_usd'] ?? ''),
                    (string)($_POST['fecha_reg'] ?? ''),
                    $estado
                );
                echo json_encode($result);
                die();
            }
            // Año fiscal activo: se pasa a la vista para la validación en JS
            $anioFiscalActivo = $object->getAnioFiscalActivo();
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
