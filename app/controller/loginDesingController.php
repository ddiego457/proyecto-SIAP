<?php


use EquipoSiap\Siap\model\loginDesingModel;


$object = new loginDesingModel();
$error = false;

if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre =  isset($_POST['usuario']) ? $_POST['usuario'] : '';
        $contra = isset($_POST['contrasena']) ?$_POST['contrasena'] : '';

        if (empty($nombre) || empty($contra)) {
            $error = "usuario o contraseña incorrectos";
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
            $error = "usuario o contraseña incorrectos";
        }
        
}
include 'app/view/loginDesign.php';


?>