<?php


use EquipoSiap\Siap\model\periodoModel;
$this->requireLogin();
$this->requireModule('periodo');

$object = new periodoModel();

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (isset($_GET['type'])) {
    if ($_GET['type'] == 'list') {
        $result = $object->getAll();
        include 'app/view/periodo/listView.php';
    } elseif ($_GET['type'] == 'register') {
        // El registro ahora es un modal dentro de la vista principal
        header('Location: ?url=periodo&type=main');
        exit;
    } elseif ($_GET['type'] == 'main') {
        if (isset($_POST['registerPeriodo'])) {
            if (isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin']) && isset($_POST['anio_fiscal_id'])) {
                // Validaciones (fechas, año fiscal, solapamiento) viven en el modelo
                $resultado = $object->add(
                    $_POST['fecha_inicio'],
                    $_POST['fecha_fin'],
                    (int)$_POST['anio_fiscal_id']
                );
                sendJsonResponse($resultado);
            }
        }

        if (isset($_POST['getAll'])) {
            sendJsonResponse($object->getAll());
        }
        if (isset($_POST['activateItem'])) {
            sendJsonResponse(['success' => $object->activate((int)$_POST['idItem'])]);
        }
        if (isset($_POST['inactivateItem'])) {
            sendJsonResponse(['success' => $object->inactivate((int)$_POST['idItem'])]);
        }

        if (isset($_POST['updateItem'])) {
            // Validaciones (fechas, año fiscal, solapamiento) viven en el modelo
            $resultado = $object->update(
                (int)$_POST['idItem'],
                $_POST['fecha_inicio'],
                $_POST['fecha_fin'],
                (int)$_POST['anio_fiscal_id']
            );
            sendJsonResponse($resultado);
        }

        // ─── CRUD de Años Fiscales (gestionado desde el modal en Periodos) ───
        if (isset($_POST['getAllAniosFiscales'])) {
            sendJsonResponse($object->getAllAniosFiscales());
        }

        if (isset($_POST['registerAnioFiscal'])) {
            $anio = isset($_POST['anio_fiscal']) ? (string)$_POST['anio_fiscal'] : '';
            // Validación y duplicado viven en el modelo
            sendJsonResponse($object->addAnioFiscal($anio));
        }

        if (isset($_POST['updateAnioFiscal'])) {
            $anio = isset($_POST['anio_fiscal']) ? (string)$_POST['anio_fiscal'] : '';
            // El estado activo ya no se envía desde el formulario de edición; se conserva el actual
            $estado = isset($_POST['estado']) ? 1 : null;
            // Validación y duplicado viven en el modelo
            sendJsonResponse($object->updateAnioFiscal((int)$_POST['idItem'], $anio, $estado));
        }

        if (isset($_POST['activateAnioFiscal'])) {
            sendJsonResponse($object->activateAnioFiscal((int)$_POST['idItem']));
        }

        if (isset($_POST['inactivateAnioFiscal'])) {
            sendJsonResponse($object->inactivateAnioFiscal((int)$_POST['idItem']));
        }

        $anioFiscales = $object->getAniosFiscalesActivos();
        include 'app/view/periodo/userView.php';
    } else {
        echo "Error: Tipo de vista no valido.";
    }
} else {
    // Endpoint para obtener periodo activo
    if (isset($_GET['type']) && $_GET['type'] === 'current') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($object->getLatestActive());
        die();
    }
    include 'app/view/welcomeView.php';
}

?>