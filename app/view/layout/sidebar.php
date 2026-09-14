<?php
// Detectar modulo activo desde la URL
$urlActual = isset($_GET['url']) ? $_GET['url'] : '';
$esAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador';

if (!function_exists('sidebarLink')) {
    function sidebarLink($url, $icon, $label, $urlActual) {
        $active = ($urlActual === $url) ? 'active' : '';
        echo "<a href='?url={$url}&type=main' class='sidebar-link {$active}'>";
        echo "<span class='icon'>{$icon}</span> <span class='sidebar-link-label'>{$label}</span>";
        echo "</a>";
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">
            <img src="assets/img/SIAPlogo.png" alt="SIAP" style="width:48px;height:48px;object-fit:contain;">
        </div>
        <div class="sidebar-brand-eyebrow">SIAP &bull; UPTAEB</div>
        <div class="sidebar-brand-name">Portal de Planificaci&oacute;n</div>
        <div class="sidebar-brand-sub">Anteproyecto Presupuestario</div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($esAdmin): ?>
            <div class="sidebar-section-title">M&oacute;dulos</div>
            <?php sidebarLink('anioFiscal',         '&#128197;', 'A&ntilde;o Fiscal',           $urlActual); ?>
            <?php sidebarLink('dependencia',         '&#127970;', 'Dependencias',               $urlActual); ?>
            <?php sidebarLink('responsable',        '&#128100;', 'Responsables',               $urlActual); ?>
            <?php sidebarLink('proveedor',           '&#128230;', 'Proveedores',                $urlActual); ?>
            <?php sidebarLink('tasaBCV',             '&#128178;', 'Tasa BCV',                    $urlActual); ?>
            <?php sidebarLink('requerimiento',       '&#128203;', 'Requerimientos',              $urlActual); ?>
            <?php sidebarLink('productosServicios', '&#128230;', 'Productos y Servicios',       $urlActual); ?>
            <?php sidebarLink('periodo',             '&#128197;', 'Periodo',                    $urlActual); ?>
            <?php sidebarLink('reporte',             '&#128202;', 'Informes',                   $urlActual); ?>
        <?php else: ?>
            <div class="sidebar-section-title">Mi &Aacute;rea</div>
            <?php sidebarLink('requerimiento',       '&#128203;', 'Mi Requerimiento',           $urlActual); ?>
        <?php endif; ?>

        <!-- Botón Salir: destruye sesión y redirige al login -->
        <a href="?url=logout" class="sidebar-link">
            <span class="icon">&#10162;</span> <span class="sidebar-link-label">Salir</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <?php
            $rol = isset($_SESSION['rol']) ? (string)$_SESSION['rol'] : 'usuario';
            $dependenciaId = isset($_SESSION['id_dep']) ? $_SESSION['id_dep'] : null;
            $usuario = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
            $rolDisplay = ($rol === 'admin' || $rol === 'administrador' || $rol === 'Administrador') ? 'Administrador' : 'Usuario';
        ?>
        <div class="sidebar-avatar"><?php echo strtoupper(substr($usuario !== '' ? $usuario : $rolDisplay, 0, 1)); ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo $usuario !== '' ? htmlspecialchars($usuario) : 'TIC'; ?></div>
            <div class="sidebar-user-role"><?php echo $rolDisplay; ?></div>
        </div>
    </div>
</aside>


