<?php


use EquipoSiap\Siap\model\requerimientoModel;
require_once "app/config/session.php";

$object = new requerimientoModel();
// $items = new productosServiciosModel();

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'register') {
        if(isset($_POST['getProductos'])){
        $info = $object->getProductos();
        echo json_encode(["data" => $info]);
        die();
        }
    include 'app/view/requerimiento/registerView.php';
    }
    elseif ($_GET['type'] == 'main') {
        
        if (isset($_POST['getAll'])) {
            $reporte = $object->getAll();
            if(!$reporte){
                echo json_decode("NULL");
                return;
            }
            echo json_encode(["data" => $reporte]);
            die();
        }

        include 'app/view/requerimiento/userView.php';

    } else {
        echo "Error: Tipo de vista no valido.";
    }

} else {
    include 'app/view/welcomeView.php';
}

?>

