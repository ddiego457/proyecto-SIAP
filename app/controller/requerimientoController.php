<?php


use EquipoSiap\Siap\model\requerimientoModel;
$this->requireLogin();
$this->requireModule('requerimiento');

$object = new requerimientoModel();
// $items = new productosServiciosModel();
$idDep = $_SESSION['id_dep'];

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'register') {
    
    // La vista de registro es un modal dentro de la vista principal (type=main).
    // Esta ruta solo atiende las peticiones AJAX del asistente de partidas.

        // 1. Petición para listar productos en la tabla
        if(isset($_POST['getProductos'])){
            $info = $object->getProductos();
            echo json_encode(["data" => $info]);
            die();
        }
        
        // 2. Petición AJAX para guardar los detalles de la partida actual
        if(isset($_POST['guardarPartida'])){
            // Priorizar id_req del POST (enviado por JS), fallback a sesión
            $idReq = isset($_POST['id_req']) && $_POST['id_req'] > 0 ? (int)$_POST['id_req'] : (isset($_SESSION['id_req']) ? $_SESSION['id_req'] : 0);
            $partida = isset($_POST['partida_actual']) ? $_POST['partida_actual'] : '401';
            $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
            $idDep = $_SESSION['id_dep'];

            $respuesta = $object->saveReq($idReq, $partida, $cantidades,$idDep);
            if ($respuesta['status'] === 'success' && $respuesta['id_req']) {
                $_SESSION['id_req'] = $respuesta['id_req'];
            }
            echo json_encode($respuesta);
            die();
        }
    
        // Petición normal (URL directa o enlace guardado): volver a la vista principal
        header('Location: ?url=requerimiento&type=main');
        exit;
    }
    elseif ($_GET['type'] == 'get_estados') {
        // Listado de estados de envío por dependencia: lo consume el botón
        // "Ver" del cuadro de supervisión y el refresco del banner del usuario
        // (carga diferida, sin recargar la página).
        header('Content-Type: application/json; charset=utf-8');
        $esAdmin = $this->esAdmin();
        $filas = $esAdmin
            ? $object->getEstadosEnvioDependencias()
            : [$object->getEstadoEnvioDependencia($idDep)];
        echo json_encode([
            'success' => true,
            'esAdmin' => $esAdmin,
            'data'    => $filas,
        ]);
        exit;
    }
    elseif ($_GET['type'] == 'main') {
        $time = $object->obtenerEstadoPeriodo();
        // Validar que obtenerEstadoPeriodo retornó un array antes de acceder a sus índices
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

        $prevReq = !$object->existeRequerimientoPrevio($idDep);
        
        if (isset($_POST['getAll'])) {
            $reporte = $object->getAll();
            
            // Almacenar id_req en sesión para uso posterior sin exponerlo en el HTML
            $idDepFiltrar = ($this->esAdmin() && isset($_POST['id_dep_filtro'])) ? $_POST['id_dep_filtro'] : $_SESSION['id_dep'];
            $activeReqId = $object->getRequerimientoActivoId($idDepFiltrar);
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

        $respuesta = $object->actualizarMatriz($idReq, $cantidades);
        echo json_encode($respuesta);
        die();
    }

    if (isset($_POST['cambiarEstado'])) {
        $idDep = $_SESSION['id_dep'];
        // Priorizar id_req del POST, fallback a consulta de sesión
        $idReq = (int)($_POST['id_req'] ?? $object->getRequerimientoActivoId($idDep));

        echo json_encode($object->cambiarEstadoRequerimiento($idReq));
        exit;
    }

    if (isset($_POST['eliminarRequerimiento'])) {
        // Priorizar id_req del POST, fallback a sesión
        $idReq = isset($_POST['id_req']) && $_POST['id_req'] > 0 ? (int)$_POST['id_req'] : ($_SESSION['id_req'] ?? 0);

        echo json_encode($object->eliminarRequerimiento($idReq));
        die();
    }

        // ... (Resto del código)
        $dependencias = $object->getAllDep();

        // Inicializar $_SESSION['id_req'] para la vista si no existe
        // Solo dentro del bloque main para no interferir con el registro
        if (empty($_SESSION['id_req'] ?? null)) {
            $idDepFiltrar = ($this->esAdmin() && isset($_POST['id_dep_filtro'])) ? $_POST['id_dep_filtro'] : $_SESSION['id_dep'];
            $activeReqId = $object->getRequerimientoActivoId($idDepFiltrar);
            if ($activeReqId > 0) {
                $_SESSION['id_req'] = $activeReqId;
            }
        }

        // Estados del período para los avisos del modal de registro
        $periodExpired = false;
        $prevReqError = false;
        if (!$object->obtenerEstadoPeriodo()) {
            $periodExpired = true;
        }
        if ($object->existeRequerimientoPrevio($idDep)) {
            $prevReqError = true;
        }

        // Estado de envío/registro del usuario (banner superior). El listado
        // por dependencia del admin se carga bajo demanda con type=get_estados.
        $estadoEnvio = null;
        if (!$this->esAdmin()) {
            $estadoEnvio = $object->getEstadoEnvioDependencia($idDep);
        }

        include 'app/view/requerimiento/requerimientoUserView.php';

    } else {
        http_response_code(400);
        $failDescript = "Tipo de vista no válido en el módulo 'requerimiento': '" . $_GET['type'] . "'.";
        require_once dirname(__DIR__) . '/view/errorView.php';
        exit;
    }

} 
else {
    header('Location: ?url=requerimiento&type=main');
    exit;
}

?>
