<?php


use EquipoSiap\Siap\model\requerimientoModel;
require_once "app/config/session.php";

$object = new requerimientoModel();
// $items = new productosServiciosModel();
$idDep = $_SESSION['id_dep'];

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'register') {
    
    // VALIDACIÓN 1: Verificar si el período de entrega sigue vigente
    if (!$object->verifyPeriod()) {
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
    if (!$object->verifyPreviusReq($idDep)){
        if (isset($_POST['guardarPartida'])) {
            echo json_encode(["status" => "error", "message" => "Ya existe un requerimiento previo o no está activo."]);
            die();
        }
        // Opción B: Si entra por URL normal, lo rebotamos o mostramos un mensaje estático
        echo "<h3>Error: Ya existe un requerimiento previo o no está activo.</h3>";
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
            $idReq = isset($_POST['id_req']) ? $_POST['id_req'] : null;
            $partida = isset($_POST['partida_actual']) ? $_POST['partida_actual'] : '401';
            $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
            $idDep = $_SESSION['id_dep'];
            
            $respuesta = $object->saveReq($idReq, $partida, $cantidades,$idDep);
            echo json_encode($respuesta);
            die();
        }
    
        // Inicializamos la variable por si la vista la requiere vacía al principio
        $idReq = 0; 
        include 'app/view/requerimiento/registerView.php';
    }
    elseif ($_GET['type'] == 'main') {
        $time = $object->verifyPeriod();
        $timeLeft = $time[1];
        $dias = $time[0];

        $idDep;

        $idReq = 0; 
        
        $prevReq = !$object->verifyPreviusReq($idDep);
        $perAct = $time[2];
        
        if (isset($_POST['getAll'])) {
            $reporte = $object->getAll();
            
            // Si $reporte es false o vacío, enviamos un array vacío dentro de 'data'
            // Esto evita el error de DataTables
            echo json_encode(["data" => $reporte ? $reporte : []]);
            die();
        }

// ... (Código anterior)

// Nuevo bloque para recibir la actualización completa de la matriz
if (isset($_POST['actualizarMatriz'])) {
    $idReq = isset($_POST['id_req']) ? $_POST['id_req'] : 0;
    $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
    
    if ($idReq > 0) {
        $respuesta = $object->actualizarMatriz($idReq, $cantidades);
        echo json_encode($respuesta);
    } else {
        echo json_encode(["status" => "error", "message" => "ID de requerimiento no válido."]);
    }
    die();
}

if (isset($_POST['cambiarEstado'])) {
    $idReq = $_POST['id_req'];
    
    // Llamada a tu modelo
    $resultado = $object->cambiarEstadoRequerimiento($idReq, 1); 
    
    if ($resultado) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado.']);
    }
    exit; // Importante para que no devuelva HTML adicional
}

// ... (Resto del código)
$dependencias = $object->getAllDep();

        include 'app/view/requerimiento/userView.php';

    } else {
        echo "Error: Tipo de vista no valido.";
    }

} 
else {
    include 'app/view/requerimiento/userView.php';
}

?>

