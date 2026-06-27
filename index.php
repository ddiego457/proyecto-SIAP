<?php
session_start();

require __DIR__ . '/vendor/autoload.php';

use EquipoSiap\Siap\controller\FrontController as FrontController;

$front = new FrontController();
?>
