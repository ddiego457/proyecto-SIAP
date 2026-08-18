<?php
$pageTitle = 'Dashboard de Informes';
$jsFile = 'reporte.js';
$buttons = isset($buttons) ? $buttons : [];
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Dashboard de Informes</div>
</div>

<div class="page-body">
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <span class="card-title">Exportar informes a Excel</span>
        </div>
        <div class="card-body">
            <p style="margin-bottom:18px; color: var(--text-muted);">Selecciona un botón para descargar el reporte en formato Excel. El sistema generará el archivo directamente desde el servidor.</p>
            <div class="report-grid">
                <?php foreach ($buttons as $button): ?>
                    <div class="report-card">
                        <div class="report-card-title"><?php echo htmlspecialchars((string)$button['title']); ?></div>
                        <div class="report-card-text"><?php echo htmlspecialchars((string)$button['description']); ?></div>
                        <button type="button" class="btn btn-success btn-full" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>')">
                            <span>&#128190;</span> Exportar Excel
                        </button>
                    </div>
                <?php endforeach; ?>
                <div class="card-body report-card-title">
                    <form action='?url=reporte&type=descarga' method='post' id="requerimientoExc">
                        <h3>Requerimientos</h3>
                        <input type="hidden" name='requerimientoExc' id='requerimiento' > 
                        
                        <!-- cambiar el valor de "value" por un selector con el id de la dependencia. por defecto, deberia ser "req" para descargar el archivo excel consolidado-->
                        <!-- -->
                        <button type="submit" id="btn-requerimiento-exc">exportar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 18px;
}
.report-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px;
    background: var(--white);
    box-shadow: var(--shadow-sm);
    min-height: 175px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.report-card-title {
    font-weight: 700;
    margin-bottom: 8px;
}
.report-card-text {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-bottom: 16px;
}
</style>

<script>
function exportReport(reportCode) {
    window.location.href = '?url=informe&type=export&report=' + encodeURIComponent(reportCode);
}
</script>

<?php include_once 'app/view/layout/foot.php'; ?>
