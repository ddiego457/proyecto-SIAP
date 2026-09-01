<?php


use EquipoSiap\Siap\model\loginDesingModel;


$object = new loginDesingModel();
$error = false;

if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre =  trim($_POST['usuario']);
        $contra = $_POST['contrasena'];

        if (empty($nombre) || empty($contra)) {
            $error = true;
            include 'app/view/loginDesign.php';
            die();
        }

        $result =  $object->login($nombre,$contra);
        
        if($result != false || $result != null) {
            $_SESSION['rol'] = $result['rol'];
            $_SESSION['id_dep'] = $result['id_dep'];
            $_SESSION['usuario'] = $result['dependencia'];
            header('location: ?url=requerimiento&type=main');
            die();
        }
        else{
            $error = true;
        }
        
}
include 'app/view/loginDesign.php';


?>