<?php


use EquipoSiap\Siap\model\requerimientoModel;
$this->requireLogin();
$this->requireModule('requerimiento');

$object = new requerimientoModel();
// $items = new productosServiciosModel();
$idDep = $_SESSION['id_dep'];

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'register') {
    
    $periodExpired = false;
    $prevReqError = false;
    
    // VALIDACIÓN 1: Verificar si el período de entrega sigue vigente
    if (!$object->verifyPeriod()) {
        $periodExpired = true;
    }
    if ($object->verifyPreviusReq($idDep)){
        $prevReqError = true;
    }
    
    // Si hay error de período, no continuar con el registro
    if ($periodExpired || $prevReqError) {
        // Si es petición AJAX con guardarPartida, responder JSON
        header("Location: ?url=requerimiento&type=main");
        if (isset($_POST['guardarPartida'])) {
            $msg = $periodExpired ? "El período de carga de requerimientos ha vencido o no está activo." : "Ya existe un requerimiento previo o no está activo.";
            echo json_encode(["status" => "error", "message" => $msg]);
            die();
        }
        // Para petición normal (URL directa), continuaremos al incluir la vista
        // que mostrará los mensajes correspondientes
    }


        // 1. Petición para listar productos en la tabla
        if(isset($_POST['getProductos'])){
            $info = $object->getProductos();
            echo json_encode(["data" => $info]);
            die();
        }
        
        // 2. NUEVO: Petición AJAX para guardar los detalles de la partida actual
        if(isset($_POST['guardarPartida'])){
            // Priorizar id_req del POST (enviado por JS), fallback a sesión
            $idReq = isset($_POST['id_req']) && $_POST['id_req'] > 0 ? (int)$_POST['id_req'] : (isset($_SESSION['id_req']) ? $_SESSION['id_req'] : 0);
            $partida = isset($_POST['partida_actual']) ? $_POST['partida_actual'] : '401';
            $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
            $idDep = $_SESSION['id_dep'];

            $errorValidacion = $this->validarPartidaActual($partida);
            if ($errorValidacion === null) {
                $errorValidacion = $this->validarCantidades($cantidades);
            }
            if ($idReq > 0 && $errorValidacion === null) {
                $errorValidacion = $this->validarIdRequerimiento($idReq);
            }
            if ($errorValidacion !== null) {
                echo json_encode(["status" => "error", "message" => $errorValidacion]);
                die();
            }
            
            $respuesta = $object->saveReq($idReq, $partida, $cantidades,$idDep);
            if ($respuesta['status'] === 'success' && $respuesta['id_req']) {
                $_SESSION['id_req'] = $respuesta['id_req'];
            }
            echo json_encode($respuesta);
            die();
        }
    
        // Inicializamos la variable por si la vista la requiere vacía al principio
        $canRegister = false; 
        include 'app/view/requerimiento/registerView.php';
    }
    elseif ($_GET['type'] == 'main') {
        $time = $object->verifyPeriod();
        // Validar que verifyPeriod retornó un array antes de acceder a sus índices
        if ($time && is_array($time)) {
            $timeLeft = $time[1];
            $dias = $time[0];
            $perAct = $time[2];
        } else {
            // No hay período activo
            $timeLeft = 0;
            $dias = 0;
            $perAct = 0;
        }

        $anioFiscal = $object->getAnioFiscal();

        $idDep = $_SESSION['id_dep'];

        $prevReq = !$object->verifyPreviusReq($idDep);
        
        if (isset($_POST['getAll'])) {
            $reporte = $object->getAll();
            
            // Almacenar id_req en sesión para uso posterior sin exponerlo en el HTML
            $rol = $_SESSION['rol'] ?? 'Usuario';
            $idDepFiltrar = ($rol === 'Administrador' && isset($_POST['id_dep_filtro'])) ? $_POST['id_dep_filtro'] : $_SESSION['id_dep'];
            $activeReqId = $object->getActiveReqIdBySession($idDepFiltrar, $rol);
            if ($activeReqId > 0) {
                $_SESSION['id_req'] = $activeReqId;
            }
            
            // Si $reporte es false o vacío, enviamos un array vacío dentro de 'data'
            // Esto evita el error de DataTables
            echo json_encode(["data" => $reporte ? $reporte : []]);
            die();
        }

        // ... (Código anterior)

        // Nuevo bloque para recibir la actualización completa de la matriz
    if (isset($_POST['actualizarMatriz'])) {
        $rol = $_SESSION['rol'] ?? 'Usuario';
        
        // Usar id_req almacenado en sesión, con fallback al POST
        $idReq = (int)($_SESSION['id_req'] ?? ($_POST['id_req'] ?? 0));
        
        $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];

        $errorValidacion = $this->validarCantidades($cantidades);
        if ($errorValidacion === null && $idReq > 0) {
            $errorValidacion = $this->validarIdRequerimiento($idReq);
        }
        if ($errorValidacion !== null) {
            echo json_encode(["status" => "error", "message" => $errorValidacion]);
            die();
        }
        
        if ($idReq > 0) {
            $respuesta = $object->actualizarMatriz($idReq, $cantidades);
            echo json_encode($respuesta);
        } else {
            echo json_encode(["status" => "error", "message" => "No hay requerimiento activo para actualizar."]);
        }
        die();
    }

    if (isset($_POST['cambiarEstado'])) {
        $rol = $_SESSION['rol'] ?? 'Usuario';
        $idDep = $_SESSION['id_dep'];
        // Priorizar id_req del POST, fallback a consulta de sesión
        $idReq = (int)($_POST['id_req'] ?? $object->getActiveReqIdBySession($idDep, $rol));
        
        if ($idReq <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'No hay requerimiento activo para enviar.']);
            exit;
        }
        
        $resultado = $object->cambiarEstadoRequerimiento($idReq); 
        
        if ($resultado) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado.']);
        }
        exit;
    }

    if (isset($_POST['eliminarRequerimiento'])) {
        // Solo administradores
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para eliminar requerimientos.']);
            die();
        }

        // Priorizar id_req del POST, fallback a sesión
        $idReq = isset($_POST['id_req']) && $_POST['id_req'] > 0 ? (int)$_POST['id_req'] : ($_SESSION['id_req'] ?? 0);

        if ($idReq > 0) {
            $resultado = $object->eliminarRequerimiento($idReq);
            if ($resultado) {
                echo json_encode(['status' => 'success', 'message' => 'Requerimiento eliminado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el requerimiento (puede que ya esté eliminado).']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de requerimiento no válido.']);
        }
        die();
    }

        // ... (Resto del código)
        $dependencias = $object->getAllDep();

        // Inicializar $_SESSION['id_req'] para la vista si no existe
        // Solo dentro del bloque main para no interferir con el registro
        if (empty($_SESSION['id_req'] ?? null)) {
            $rol = $_SESSION['rol'] ?? 'Usuario';
            $idDepFiltrar = ($rol === 'Administrador' && isset($_POST['id_dep_filtro'])) ? $_POST['id_dep_filtro'] : $_SESSION['id_dep'];
            $activeReqId = $object->getActiveReqIdBySession($idDepFiltrar, $rol);
            if ($activeReqId > 0) {
                $_SESSION['id_req'] = $activeReqId;
            }
        }

        include 'app/view/requerimiento/userView.php';

    } else {
        echo "Error: Tipo de vista no valido.";
    }

} 
else {
    include 'app/view/requerimiento/userView.php';
}

?>

