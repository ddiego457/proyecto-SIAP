<?php


use EquipoSiap\Siap\model\requerimientoModel;

$object = new requerimientoModel();

if (isset($_GET['type'])) {

    if ($_GET['type'] == 'register') {

       // Capturar datos enviados por POST
       $id_req  = isset($_POST['id_req']) ? intval($_POST['id_req']) : 0;
       $partida = isset($_POST['partida']) ? $_POST['partida'] : '';

       if ($id_req === 0 || empty($partida)) {
           echo json_encode(['data' => []]);
           return;
       }

       // Llamar al modelo para traer los ítems cruzados con lo que ya esté guardado en borrador
       $items = $object->getAll();

        $id_req         = isset($_POST['id_req']) ? intval($_POST['id_req']) : 0;
        $partida_actual = isset($_POST['partida']) ? $_POST['partida'] : '';
        $cantidades     = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];

        if ($id_req === 0 || empty($partida_actual)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos de cabecera incompletos.']);
            return;
        }

        // 1. Ordenar al modelo que procese el guardado de la matriz multidimensional
        $guardadoExitoso = $this->modelo->guardarDetallesPartida($id_req, $cantidades);

        if ($guardadoExitoso) {
            // 2. Lógica para determinar cuál es la siguiente partida presupuestaria en el orden
            $siguiente_partida = $this->calcularSiguientePartida($partida_actual);

            echo json_encode([
                'status' => 'success',
                'siguiente_partida' => $siguiente_partida
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudieron almacenar los registros en la base de datos.']);
        }
       // DataTables requiere obligatoriamente que la data viaje dentro de una clave llamada "data"
       echo json_encode(['data' => $items]);


        include 'app/view/requerimiento/registerView.php';

    } elseif ($_GET['type'] == 'main') {
        
        if (isset($_POST['getAll'])) {
            $reporte = $object->getAll();
            if(!$reporte){
                echo json_decode("NULL");
                return;
            }
            echo json_encode(["data" => $reporte]);
            die();
        }
        //if (isset($_POST['deleteItem'])) {
        //    echo json_encode($object->delete((int)$_POST['idItem']));
        //    die();
        //}
        //if (isset($_POST['updateItem'])) {
        //    $result = $object->update(
        //        (int)$_POST['idItem'],
        //        (int)$_POST['id_anioFis'],
        //        (int)$_POST['id_tasa'],
        //        $_POST['fecha_envio'],
        //        (float)$_POST['cantidad_por_mes']
        //    );
//
        //    echo json_encode($result);
        //    die();
        //}

        // Nota: Enviar/Inhabilitar del Bloque 4 se implementó en el nuevo esquema,
        // usando app/controller/requerimiento401to407Controller.php.

        // Pasar listas auxiliares para el modal de edicion
        //$aniosFiscales = $object->getAniosFiscales();
        //$tasas         = $object->getTasas();
        include 'app/view/requerimiento/userView.php';

    } else {
        echo "Error: Tipo de vista no valido.";
    }

} else {
    include 'app/view/welcomeView.php';
}

?>

