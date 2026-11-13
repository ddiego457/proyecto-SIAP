<?php
// ============================================================
// MENÚ DE USUARIO DEL TOPBAR
// Botón con avatar (foto o inicial) + nombre que despliega:
//   - Editar foto  -> abre el modal #modalFotoPerfil (sidebar.php)
//   - Cerrar sesión -> ?url=inicio&type=logout
// Se incluye dentro del <div class="topbar"> de cada vista.
// ============================================================

$rolMenu = isset($_SESSION['rol']) ? (string)$_SESSION['rol'] : 'usuario';
// Nombre del responsable logueado + dependencia (ambos guardados en sesión
// al iniciar sesión; ver loginDesingController::setSession)
$nombreMenu = isset($_SESSION['nombre']) ? trim((string)$_SESSION['nombre']) : '';
$usuarioMenu = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
$rolDisplayMenu = ($rolMenu === 'admin' || $rolMenu === 'administrador' || $rolMenu === 'Administrador') ? 'Administrador'
                : (($rolMenu === 'coordinador' || $rolMenu === 'Coordinador') ? 'Coordinador' : 'Usuario');

// Texto del perfil: "NombreResponsable — Dependencia" (si falta alguno, solo el otro)
if ($nombreMenu !== '' && $usuarioMenu !== '') {
    $textoPerfilMenu = htmlspecialchars($nombreMenu, ENT_QUOTES, 'UTF-8') . ' &mdash; ' . htmlspecialchars($usuarioMenu, ENT_QUOTES, 'UTF-8');
} else {
    $textoPerfilMenu = htmlspecialchars($nombreMenu !== '' ? $nombreMenu : $usuarioMenu, ENT_QUOTES, 'UTF-8');
}
// Inicial del avatar: sale del nombre del responsable si existe
$inicialMenu = ($nombreMenu !== '' ? $nombreMenu : ($usuarioMenu !== '' ? $usuarioMenu : $rolDisplayMenu));

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
                <?php echo strtoupper(substr($inicialMenu, 0, 1)); ?>
            <?php endif; ?>
        </span>
        <span class="topbar-user-name"><?php echo $textoPerfilMenu !== '' ? $textoPerfilMenu : 'Usuario'; ?></span>
        <i class="fa-solid fa-chevron-down topbar-user-chevron" aria-hidden="true"></i>
    </button>
    <div class="topbar-user-menu" id="topbarUserMenu" role="menu" style="display:none;">
        <div class="topbar-user-menu-header">
            <div class="topbar-user-menu-name"><?php echo $textoPerfilMenu !== '' ? $textoPerfilMenu : 'Usuario'; ?></div>
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
