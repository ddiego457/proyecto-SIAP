<?php
// Detectar modulo activo desde la URL
$urlActual = isset($_GET['url']) ? $_GET['url'] : '';

if (!function_exists('sidebarLink')) {
    function sidebarLink($url, $icon, $label, $urlActual) {
        $active = ($urlActual === $url) ? 'active' : '';
        echo "<a href='?url={$url}&type=main' class='sidebar-link {$active}'>";
        echo "<span class='icon'>{$icon}</span> {$label}";
        echo "</a>";
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-eyebrow">SIAP &bull; UPTAEB</div>
        <div class="sidebar-brand-name">Portal de Planificaci&oacute;n</div>
        <div class="sidebar-brand-sub">Anteproyecto Presupuestario</div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Mòdulos</div>
        <?php if($_SESSION['rol'] !== 'Administrador'){                          ?>
        <?php sidebarLink('requerimiento',       '&#128203;', 'Requerimientos',              $urlActual); ?>
        <?php               }else{            ?>
        <?php sidebarLink('requerimiento',       '&#128203;', 'Requerimientos',              $urlActual); ?>
        <?php sidebarLink('anioFiscal',         '&#128197;', 'A&ntilde;o Fiscal',           $urlActual); ?>
        <?php sidebarLink('dependencia',         '&#127970;', 'Dependencias',               $urlActual); ?>
        <?php sidebarLink('responsable',        '&#128100;', 'Responsables',               $urlActual); ?>
        <?php sidebarLink('proveedor',           '&#128230;', 'Proveedores',                $urlActual); ?>
        <?php sidebarLink('tasaBCV',             '&#128178;', 'Tasa BCV',                    $urlActual); ?>
        <?php sidebarLink('productosServicios', '&#128230;', 'Productos y Servicios',       $urlActual); ?>
        <?php sidebarLink('periodo',             '&#128197;', 'Periodo',                    $urlActual); ?>
        <?php }?>
        <!-- Botón Salir: destruye sesión y redirige al login -->
        <a href="?url=logout" class="sidebar-link">
            <span class="icon">&#10162;</span> Salir
        </a>

        <div class="sidebar-section-title" style="margin-top:10px;">Informaci&oacute;n</div>
        <a href="?url=informe&type=dashboard" class="sidebar-link <?php echo ($urlActual === 'informe') ? 'active' : ''; ?>">
            <span class="icon">&#128202;</span> Informes
        </a>
    </nav>

    <div class="sidebar-user">
        <?php
            $rol = isset($_SESSION['rol']) ? (string)$_SESSION['rol'] : 'usuario';
            $dependenciaId = isset($_SESSION['dependencia']) ? $_SESSION['dependencia'] : null;
            $usuario = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
        ?>
        <div class="sidebar-avatar"><?php echo $rol === 'Administrador' ? 'A' : 'U';?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo $usuario !== '' ? htmlspecialchars($usuario) : ($rol === 'admin' || $rol === 'administrador' ? 'Administrador' : 'Usuario'); ?></div>
            <div class="sidebar-user-name"><?php echo $rol === 'Administrador' ? (string)$rol : 'Usuario';?></div>
        </div>
    </div>
</aside>


