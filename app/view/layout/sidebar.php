<?php
// Detectar modulo activo desde la URL
$urlActual = isset($_GET['url']) ? $_GET['url'] : '';

// Iconos por módulo (mismas claves que SessionTrait::$catalogoModulos)
$iconosModulos = [
    'dependencia'        => '&#127970;',
    'responsable'        => '&#128100;',
    'proveedor'          => '&#128230;',
    'tasaBCV'            => '&#128178;',
    'requerimiento'      => '&#128203;',
    'productosServicios' => '&#128230;',
    'periodo'            => '&#128197;',
    'reporte'            => '&#128202;',
];

// Solo se muestran los módulos permitidos para el rol actual
// (misma regla que usa SessionTrait::requireModule)
$modulosVisibles = [];
foreach ($this->getCatalogoModulos() as $claveModulo => $etiquetaModulo) {
    if ($this->puedeAccederModulo($claveModulo)) {
        $modulosVisibles[$claveModulo] = $etiquetaModulo;
    }
}

// Si solo ve Requerimientos/Informes se mantiene el título de "Mi Área" (comportamiento original)
$esSoloAreaPersonal = !empty($modulosVisibles)
    && !array_diff_key($modulosVisibles, ['requerimiento' => true, 'reporte' => true]);

if (!function_exists('sidebarLink')) {
    function sidebarLink($url, $icon, $label, $urlActual) {
        $active = ($urlActual === $url) ? 'active' : '';
        echo "<a href='?url={$url}&type=main' class='sidebar-link {$active}' data-nav='1'>";
        echo "<span class='icon'>{$icon}</span> <span class='sidebar-link-label'>{$label}</span>";
        echo "</a>";
    }
}
?>
<aside id="sidebarPanel" class="sidebar" data-open="false">
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">
            <img src="assets/img/SIAPlogo.png" alt="SIAP" >
        </div>
        <div class="sidebar-brand-eyebrow">SIAP &bull; UPTAEB</div>
        <div class="sidebar-brand-name">Portal de Planificaci&oacute;n</div>
        <div class="sidebar-brand-sub">Anteproyecto Presupuestario</div>
    </div>

    <nav class="sidebar-nav">
        <?php if (empty($modulosVisibles)): ?>
            <div class="sidebar-section-title">M&oacute;dulos</div>
            <p class="sidebar-vacio">Sin m&oacute;dulos asignados a su rol.<br>Contacte al coordinador del sistema.</p>
        <?php else: ?>
            <div class="sidebar-section-title"><?php echo $esSoloAreaPersonal ? 'Mi &Aacute;rea' : 'M&oacute;dulos'; ?></div>
            <?php foreach ($modulosVisibles as $claveModulo => $etiquetaModulo):
                $etiquetaLink = ($esSoloAreaPersonal && $claveModulo === 'requerimiento') ? 'Mi Requerimiento' : $etiquetaModulo;
                $iconoLink = isset($iconosModulos[$claveModulo]) ? $iconosModulos[$claveModulo] : '&#9679;';
                sidebarLink($claveModulo, $iconoLink, $etiquetaLink, $urlActual);
            endforeach; ?>
        <?php endif; ?>
    </nav>
</aside>

<!-- MODAL: FOTO DE PERFIL (se abre desde "Editar foto" en el menú del topbar — ver userMenu.php) -->
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

