<?php
$pageTitle = "Reportes - Globales";
$jsFile    = "report.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">📊 Reportes</div>
    <div class="topbar-actions">
        <button id="btnExportExcel" class="btn btn-success btn-sm">Exportar Excel</button>
        <button id="btnExportPdf" class="btn btn-outline btn-sm">Exportar PDF (HTML imprimible)</button>
    </div>
</div>

<div class="page-body">
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title">Filtro de Dependencia</span></div>
        <div class="card-body">
            <label class="field-label" for="dependenciaFilter">Seleccione dependencia para reporte local</label>
            <select id="dependenciaFilter" class="field-input field-select" style="max-width:360px;">
                <option value="">-- Mostrar todas las dependencias --</option>
                <?php foreach ($dependencias as $dep): ?>
                    <option value="<?php echo (int)$dep['id_dep']; ?>"><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                <?php endforeach; ?>
            </select>
            <button id="btnClearFilter" class="btn btn-outline btn-sm" style="margin-left:10px;">Limpiar filtro</button>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Resumen por Partidas</span></div>
        <div class="card-body">
            <div id="partidasWrap"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Resumen por Dependencia</span></div>
        <div class="card-body">
            <div id="dependenciasWrap"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Totales Globales</span></div>
        <div class="card-body">
            <div id="globalWrap"></div>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
