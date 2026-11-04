<?php
// ============================================================
// MENÚ DE USUARIO DEL TOPBAR
// Botón con avatar (foto o inicial) + nombre que despliega:
//   - Editar foto  -> abre el modal #modalFotoPerfil (sidebar.php)
//   - Cerrar sesión -> ?url=inicio&type=logout
// Se incluye dentro del <div class="topbar"> de cada vista.
// ============================================================

$rolMenu = isset($_SESSION['rol']) ? (string)$_SESSION['rol'] : 'usuario';
$usuarioMenu = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
$rolDisplayMenu = ($rolMenu === 'admin' || $rolMenu === 'administrador' || $rolMenu === 'Administrador') ? 'Administrador'
                : (($rolMenu === 'coordinador' || $rolMenu === 'Coordinador') ? 'Coordinador' : 'Usuario');

// Foto de perfil del responsable logueado (nombre del archivo en BD/sesión)
$fotoMenu = isset($_SESSION['foto']) ? (string)$_SESSION['foto'] : '';
$rutaFotoMenu = $fotoMenu !== '' ? dirname(__DIR__, 3) . '/assets/img/perfiles/' . basename($fotoMenu) : '';
$tieneFotoMenu = $rutaFotoMenu !== '' && file_exists($rutaFotoMenu);
?>
<div class="topbar-user">
    <button type="button" id="topbarUserBtn" class="topbar-user-btn" aria-haspopup="menu" aria-expanded="false" aria-label="Opciones de usuario">
        <span class="topbar-user-avatar">
            <?php if ($tieneFotoMenu): ?>
                <img src="assets/img/perfiles/<?php echo htmlspecialchars(basename($fotoMenu)); ?>?v=<?php echo filemtime($rutaFotoMenu); ?>" alt="Foto de perfil">
            <?php else: ?>
                <?php echo strtoupper(substr($usuarioMenu !== '' ? $usuarioMenu : $rolDisplayMenu, 0, 1)); ?>
            <?php endif; ?>
        </span>
        <span class="topbar-user-name"><?php echo $usuarioMenu !== '' ? htmlspecialchars($usuarioMenu) : 'Usuario'; ?></span>
        <i class="fa-solid fa-chevron-down topbar-user-chevron" aria-hidden="true"></i>
    </button>
    <div class="topbar-user-menu" id="topbarUserMenu" role="menu" style="display:none;">
        <div class="topbar-user-menu-header">
            <div class="topbar-user-menu-name"><?php echo $usuarioMenu !== '' ? htmlspecialchars($usuarioMenu) : 'Usuario'; ?></div>
            <div class="topbar-user-menu-role"><?php echo $rolDisplayMenu; ?></div>
        </div>
        <button type="button" class="topbar-user-menu-item" id="btnEditarFotoMenu" role="menuitem">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar foto
        </button>
        <a href="?url=inicio&type=logout" class="topbar-user-menu-item" role="menuitem">
            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Cerrar sesión
        </a>
    </div>
</div>
