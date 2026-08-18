<?php
$pageTitle = 'Informe Individual (Excel)';
include_once 'app/view/layout/head.php';

$mensaje = isset($mensaje) ? $mensaje : '';
?>

<div class="topbar">
    <div class="topbar-title">Informe de requerimiento</div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header"><span class="card-title">Exportación a Excel</span></div>
        <div class="card-body">
            <p style="margin:0; color:var(--text-muted);">
                Botón no funcional aun (placeholder). <?php echo $mensaje ? htmlspecialchars($mensaje) : ''; ?>
            </p>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>

