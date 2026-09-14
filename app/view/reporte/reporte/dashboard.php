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
            <span class="card-title">Exportar Informes</span>
        </div>
        <div class="card-body">
            <p style="margin-bottom:18px; color: var(--text-muted);">
                Selecciona un reporte y formato. Los archivos se generan y descargan directamente.
            </p>
            <div class="report-grid">
                <?php foreach ($buttons as $button): ?>
                    <div class="report-card">
                        <div class="report-card-title"><?php echo htmlspecialchars((string)$button['title']); ?></div>
                        <div class="report-card-text"><?php echo htmlspecialchars((string)$button['description']); ?></div>

                        <?php if (!empty($button['requiresDep'])): ?>
                            <div class="field-group" style="margin-bottom:12px;">
                                <label class="field-label" style="font-size:.85rem;">Dependencia</label>
                                <select id="selDepIndividual" class="field-input field-select" disabled>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-success btn-full" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'excel')" id="btnExpIndExcel">
                                <span>&#128190;</span> Exportar Excel
                            </button>
                            <button type="button" class="btn btn-primary btn-full" style="margin-top:8px;" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'pdf')" id="btnExpIndPdf">
                                <span>&#128221;</span> Exportar PDF
                            </button>
                        <?php else: ?>
                            <div class="flex gap-10">
                                <button type="button" class="btn btn-success" style="flex:1;" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'excel')">
                                    <span>&#128190;</span> Excel
                                </button>
                                <button type="button" class="btn btn-primary" style="flex:1;" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'pdf')">
                                    <span>&#128221;</span> PDF
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
}
.report-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px;
    background: var(--white);
    box-shadow: var(--shadow-sm);
    min-height: 190px;
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
.btn-full {
    width: 100%;
    justify-content: center;
}
.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #fff;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 6px;
    vertical-align: middle;
}
@keyframes spin { to { transform: rotate(360deg); } }
.toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 14px 20px;
    border-radius: 6px;
    color: #fff;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    z-index: 9999;
    animation: slideIn 0.3s ease;
}
.toast-success { background: #28a745; }
.toast-error { background: #dc3545; }
@keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php include_once 'app/view/layout/foot.php'; ?>

<script src="assets/js/reporte.js"></script>