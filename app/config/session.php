<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_dep'])) {
    header("Location: ?url=loginDesing");
    exit();
}

?>