<?php


use EquipoSiap\Siap\model\requerimientoModel;
require_once "app/config/session.php";

$object = new requerimientoModel();
// $items = new productosServiciosModel();

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'register') {
    
    // VALIDACIÓN 1: Verificar si el período de entrega sigue vigente
    if (!$object->verifyPer()) {
        // Opción A: Si es una petición AJAX, respondemos con error JSON
        if (isset($_POST['guardarPartida'])) {
            echo json_encode(["status" => "error", "message" => "El período de carga de requerimientos ha vencido o no está activo."]);
            die();
        }
        // Opción B: Si entra por URL normal, lo rebotamos o mostramos un mensaje estático
        echo "<h3>Error: El período de recepción de requerimientos para este año fiscal ha culminado o está cerrado.</h3>";
        echo "<a href='?url=requerimiento&type=main'>Volver al inicio</a>";
        die();
    }


        // 1. Petición para listar productos en la tabla
        if(isset($_POST['getProductos'])){
            $info = $object->getProductos();
            echo json_encode(["data" => $info]);
            die();
        }
        
        // 2. NUEVO: Petición AJAX para guardar los detalles de la partida actual
        if(isset($_POST['guardarPartida'])){
            $id_req = isset($_POST['id_req']) ? $_POST['id_req'] : null;
            $partida = isset($_POST['partida_actual']) ? $_POST['partida_actual'] : '401';
            $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
            
            $respuesta = $object->saveReq($id_req, $partida, $cantidades);
            echo json_encode($respuesta);
            die();
        }
    
        // Inicializamos la variable por si la vista la requiere vacía al principio
        $id_req = 0; 
        include 'app/view/requerimiento/registerView.php';
    }
    elseif ($_GET['type'] == 'main') {
        $tl = $object->timeleft();
        $d = date('D, d M Y H:i:s');
        $dias = strtotime($d) - $tl['per_fin'];
        $rek = true;
        if (isset($_POST['getAll'])) {
            $reporte = $object->getAll();
            if(!$reporte){
                echo json_decode("NULL");
                return;
            }
            echo json_encode(["data" => $reporte]);
            die();
        }
        $id_dep = $_SESSION['id_dep'];
        if ($object->verifyYear($id_dep)) {
            $rek = false;
        }

        include 'app/view/requerimiento/userView.php';

    } else {
        echo "Error: Tipo de vista no valido.";
    }

} 
else {
    include 'app/view/welcomeView.php';
}

?>

