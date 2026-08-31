<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_dep'])) {
    header("Location: ?url=loginDesing");
    exit();
}

if (($_GET['url'] !== "reporte" && $_GET['url'] !== "requerimiento") && $_SESSION['rol'] != "Administrador"){
    header("Location: ?url=requerimiento&type=main");
    exit();
}

?>