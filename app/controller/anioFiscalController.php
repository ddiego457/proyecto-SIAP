<?php

namespace App\PracticaCrud\Controller;

use App\PracticaCrud\Model\anioFiscalModel;

$object = new anioFiscalModel();

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'list') {
        $result = $object->getAll();
        include 'app/view/anioFiscal/listView.php';

    } elseif ($_GET['type'] == 'register') {

        if (isset($_POST['registerAnioFiscal'])) {
            if (isset($_POST['anio_fiscal'])) {
                $anio = (string)$_POST['anio_fiscal'];
                $estado = isset($_POST['estado']) ? 1 : 0;
                $result = $object->add($anio, (int)$estado);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => (bool)$result, 'redirect' => '?url=anioFiscal&type=main']);
                die();
            }
        }

        include 'app/view/anioFiscal/registerView.php';

    } elseif ($_GET['type'] == 'main') {

        if (isset($_POST['getAll'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($object->getAll());
            die();
        }

        if (isset($_POST['activateItem'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($object->activate((int)$_POST['idItem']));
            die();
        }


        if (isset($_POST['inactivateItem'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($object->inactivate((int)$_POST['idItem']));
            die();
        }

        if (isset($_POST['updateItem'])) {
            $estado = isset($_POST['estado']) ? 1 : 0;
            $result = $object->update(
                (int)$_POST['idItem'],
                (string)$_POST['anio_fiscal'],
                (int)$estado
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            die();
        }

        include 'app/view/anioFiscal/userView.php';

    } else {
        echo "Error: Tipo de vista no valido.";
    }

} else {
    // Si se solicita la tasa actual vía GET
    if (isset($_GET['type']) && $_GET['type'] === 'current') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($object->getActive());
        die();
    }
    include 'app/view/welcomeView.php';
}

?>
