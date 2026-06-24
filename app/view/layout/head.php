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
    <link rel="stylesheet" href="assets\js\DataTables\datatables.min.css">
    <style>
        .input-corto {
        width: 55px; /* Ajusta este valor a tus necesidades */
        padding: 8px 5px;
        font-size: 14px;
        text-align: center;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.2s ease;
}
    </style>
</head>
<body>
<div class="app-layout">
    <button id="sidebarToggleBtn" class="sidebar-toggle-global" aria-expanded="true" title="Mostrar u ocultar menú">
        &#9776;
    </button>
    <?php include_once 'app/view/layout/sidebar.php'; ?>
    <div class="main-content">
