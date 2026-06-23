<?php
$pageTitle = isset($pageTitle) ? $pageTitle . ' — SIAP' : 'SIAP';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="assets/css/siap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
</head>
<body>
<div class="app-layout">
    <button id="sidebarToggleBtn" class="sidebar-toggle-global" aria-expanded="true" title="Mostrar u ocultar menú">
        &#9776;
    </button>
    <?php include_once 'app/view/layout/sidebar.php'; ?>
    <div class="main-content">
