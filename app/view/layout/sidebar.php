<?php
// Detectar modulo activo desde la URL
$urlActual = isset($_GET['url']) ? $_GET['url'] : '';
$esAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador';
$esCoordinador = isset($_SESSION['rol']) && $_SESSION['rol'] === 'Coordinador';

if (!function_exists('sidebarLink')) {
    function sidebarLink($url, $icon, $label, $urlActual) {
        $active = ($urlActual === $url) ? 'active' : '';
        echo "<a href='?url={$url}&type=main' class='sidebar-link {$active}' data-nav='1'>";
        echo "<span class='icon'>{$icon}</span> <span class='sidebar-link-label'>{$label}</span>";
        echo "</a>";
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">
            <img src="assets/img/SIAPlogo.png" alt="SIAP" >
        </div>
        <div class="sidebar-brand-eyebrow">SIAP &bull; UPTAEB</div>
        <div class="sidebar-brand-name">Portal de Planificaci&oacute;n</div>
        <div class="sidebar-brand-sub">Anteproyecto Presupuestario</div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($esAdmin): ?>
            <div class="sidebar-section-title">M&oacute;dulos</div>
            <?php sidebarLink('dependencia',         '&#127970;', 'Dependencias',               $urlActual); ?>
            <?php sidebarLink('responsable',        '&#128100;', 'Responsables',               $urlActual); ?>
            <?php sidebarLink('proveedor',           '&#128230;', 'Proveedores',                $urlActual); ?>
            <?php sidebarLink('tasaBCV',             '&#128178;', 'Tasa BCV',                    $urlActual); ?>
            <?php sidebarLink('requerimiento',       '&#128203;', 'Requerimientos',              $urlActual); ?>
            <?php sidebarLink('productosServicios', '&#128230;', 'Productos y Servicios',       $urlActual); ?>
            <?php sidebarLink('periodo',             '&#128197;', 'Periodo',                    $urlActual); ?>
            <?php sidebarLink('reporte',             '&#128202;', 'Informes',                   $urlActual); ?>
        <?php elseif ($esCoordinador): ?>
            <div class="sidebar-section-title">M&oacute;dulos</div>
            <?php sidebarLink('proveedor',           '&#128230;', 'Proveedores',                $urlActual); ?>
            <?php sidebarLink('productosServicios', '&#128230;', 'Productos y Servicios',       $urlActual); ?>
            <?php sidebarLink('periodo',             '&#128197;', 'Periodo',                    $urlActual); ?>
            <?php sidebarLink('reporte',             '&#128202;', 'Informes',                   $urlActual); ?>
        <?php else: ?>
            <div class="sidebar-section-title">Mi &Aacute;rea</div>
            <?php sidebarLink('requerimiento',       '&#128203;', 'Mi Requerimiento',           $urlActual); ?>
            <?php sidebarLink('reporte',             '&#128202;', 'Informes',                   $urlActual); ?>
        <?php endif; ?>

        <!-- Botón Salir: destruye sesión y redirige al login (logout en loginDesingController) -->
        <a href="?url=inicio&type=logout" class="sidebar-link">
            <span class="icon">&#10162;</span> <span class="sidebar-link-label">Salir</span>
        </a>
    </nav>

    <div class="sidebar-user" id="sidebarUserBox" role="button" tabindex="0" title="Cambiar foto de perfil" aria-label="Cambiar foto de perfil">
        <?php
            $rol = isset($_SESSION['rol']) ? (string)$_SESSION['rol'] : 'usuario';
            $dependenciaId = isset($_SESSION['id_dep']) ? $_SESSION['id_dep'] : null;
            $usuario = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
            $rolDisplay = ($rol === 'admin' || $rol === 'administrador' || $rol === 'Administrador') ? 'Administrador'
                        : (($rol === 'coordinador' || $rol === 'Coordinador') ? 'Coordinador' : 'Usuario');

            // Foto de perfil del responsable logueado (nombre del archivo en BD/sesión)
            $fotoSesion = isset($_SESSION['foto']) ? (string)$_SESSION['foto'] : '';
            $rutaFoto = $fotoSesion !== '' ? dirname(__DIR__, 3) . '/assets/img/perfiles/' . basename($fotoSesion) : '';
            $tieneFoto = $rutaFoto !== '' && file_exists($rutaFoto);
        ?>
        <div class="sidebar-avatar">
            <?php if ($tieneFoto): ?>
                <img src="assets/img/perfiles/<?php echo htmlspecialchars(basename($fotoSesion)); ?>?v=<?php echo filemtime($rutaFoto); ?>" alt="Foto de perfil">
            <?php else: ?>
                <?php echo strtoupper(substr($usuario !== '' ? $usuario : $rolDisplay, 0, 1)); ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo $usuario !== '' ? htmlspecialchars($usuario) : 'TIC'; ?></div>
            <div class="sidebar-user-role"><?php echo $rolDisplay; ?></div>
        </div>
    </div>
</aside>

<!-- MODAL: FOTO DE PERFIL (se abre al presionar el bloque de usuario en la sidebar) -->
<div id="modalFotoPerfil" class="modal-backdrop" style="display:none;">
    <div class="modal-panel modal-perfil-panel">
        <div class="modal-header">
            <h3>Foto de perfil</h3>
            <button type="button" id="btnCerrarFotoPerfil" class="modal-close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body">
            <div class="perfil-preview" id="perfilPreview"></div>
            <p class="perfil-hint">Formatos admitidos: JPG, PNG o WEBP. M&aacute;ximo 2 MB.<br>La imagen se recorta autom&aacute;ticamente en cuadrado.</p>
            <input type="file" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" style="display:none;">
        </div>
        <div class="modal-footer">
            <button type="button" id="btnEliminarFoto" class="btn btn-outline">Eliminar foto</button>
            <button type="button" id="btnCancelarFoto" class="btn btn-outline">Cancelar</button>
            <button type="button" id="btnElegirFoto" class="btn btn-success">Elegir foto</button>
            <button type="button" id="btnGuardarFoto" class="btn btn-success" style="display:none;">Guardar</button>
        </div>
    </div>
</div>


