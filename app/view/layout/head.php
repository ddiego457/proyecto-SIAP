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
    <link rel="stylesheet" href="assets/css/siap.css?v=<?php echo file_exists('assets/css/siap.css') ? filemtime('assets/css/siap.css') : time(); ?>">
    <link rel="stylesheet" href="assets/js/DataTables/datatables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
</head>
<body>
<div class="app-layout">
    <button
        id="sidebarToggleBtn"
        class="sidebar-toggle-btn-global"
        type="button"
        aria-label="Colapsar menú"
        aria-expanded="true">
    </button>
    <?php include_once 'app/view/layout/sidebar.php'; ?>
    <div class="main-content">