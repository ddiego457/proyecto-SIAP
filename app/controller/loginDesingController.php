<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\PracticaCrud\Model\LoginDesingModel as loginModel;

$object = new loginModel();

if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $nombre = $_POST['usuario'];
        $contra = $_POST['contrasena'];

        $result =  $object->login($nombre,$contra);
        if($result != false || $result != null) {
            header('location: ?url=requerimiento&type=main');
            die();
        }
        
}
include 'app\view\loginDesign.php';


?>