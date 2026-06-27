<?php


use EquipoSiap\Siap\model\LoginDesingModel as loginModel;

$object = new loginModel();

if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre = $_POST['usuario'];
        $contra = $_POST['contrasena'];

        $result =  $object->login($nombre,$contra);
        if($result != false || $result != null) {
            $_SESSION['rol'] = $result['rol'];
            $_SESSION['id_dep'] = $result['id_dep'];
            $_SESSION['usuario'] = $result['dependencia'];
            header('location: ?url=requerimiento&type=main');
            die();
        }
        
}
include 'app\view\loginDesign.php';


?>