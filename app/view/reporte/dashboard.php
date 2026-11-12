<?php
$pageTitle = 'Dashboard de Informes';
$jsFile = 'reporte.js';
$buttons = isset($buttons) ? $buttons : [];
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Dashboard de Informes</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<div class="page-body">
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <span class="card-title">Exportar Informes</span>
        </div>
        <div class="card-body">
            <p style="margin-bottom:18px; color: var(--text-muted);">
                <?php if (!empty($esAdminReportes)): ?>
                    Selecciona cualquiera de los cuatro informes y el formato. Los archivos se generan y descargan directamente.
                <?php else: ?>
                    Solo puedes generar un informe de tu propio requerimiento. Si está vacío, el sistema te indicará qué debes completar.
                <?php endif; ?>
            </p>
            <div class="report-grid">
                <?php foreach ($buttons as $button): ?>
                    <div class="report-card">
                        <div class="report-card-title"><?php echo htmlspecialchars((string)$button['title']); ?></div>
                        <div class="report-card-text"><?php echo htmlspecialchars((string)$button['description']); ?></div>

                        <?php if ($button['code'] === 'productos_partida'): ?>
                            <div class="field-group partidas-filter" style="margin-bottom:12px;">
                                <span class="field-label" style="font-size:.85rem;">Partidas presupuestarias</span>
                                <label class="partidas-all-option">
                                    <input type="checkbox" id="chkTodasPartidasReport" checked>
                                    <span>
                                        <strong>Todas las partidas</strong>
                                        <small>Incluye el catálogo completo</small>
                                    </span>
                                </label>
                                <div id="selPartidasReport" class="partidas-options" aria-label="Partidas presupuestarias">
                                    <div class="partidas-loading">Cargando partidas...</div>
                                </div>
                                <small id="lblPartidasSeleccionadas" class="partidas-selection-count" aria-live="polite">
                                    Todas las partidas seleccionadas
                                </small>
                            </div>
                        <?php elseif ($button['code'] === 'dependencias_responsable'): ?>
                            <div class="field-group" style="margin-bottom:12px;">
                                <label class="field-label" for="selFiltroDependencias" style="font-size:.85rem;">Tipo de dependencias</label>
                                <select id="selFiltroDependencias" class="field-input field-select">
                                    <option value="activas_con_responsable">Activas con responsable</option>
                                    <option value="activas_sin_responsable">Activas sin responsable</option>
                                    <option value="inactivas">Inactivas (con o sin responsable)</option>
                                </select>
                            </div>
                        <?php elseif ($button['code'] === 'req_individual'): ?>
                            <?php if (!empty($esAdminReportes)): ?>
                                <div class="field-group" style="margin-bottom:12px;">
                                    <label class="field-label" for="selDepIndividual" style="font-size:.85rem;">Dependencia</label>
                                    <select id="selDepIndividual" class="field-input field-select" disabled>
                                        <option value="">Cargando dependencias...</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="field-group" style="margin-bottom:12px;">
                                <label class="field-label" for="selEstadoReq" style="font-size:.85rem;">Estado del requerimiento</label>
                                <select id="selEstadoReq" class="field-input field-select">
                                    <option value="enviado">Enviado</option>
                                    <option value="sin_enviar">Sin enviar</option>
                                </select>
                            </div>
                        <?php elseif ($button['code'] === 'req_global'): ?>
                            <div class="field-group" style="margin-bottom:12px;">
                                <label class="field-label" for="selModoGlobal" style="font-size:.85rem;">Tipo de consolidado</label>
                                <select id="selModoGlobal" class="field-input field-select">
                                    <option value="consolidado">Consolidado detallado</option>
                                    <option value="cantidades">Cantidades anuales por producto y partida</option>
                                    <option value="totales">Totales por partida (USD y Bs.)</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($button['code'] === 'req_individual'): ?>
                            <button type="button" class="btn btn-success btn-full" data-report-export onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'excel', this)" id="btnExpIndExcel">
                                <span>&#128190;</span> Exportar Excel
                            </button>
                            <button type="button" class="btn btn-primary btn-full" data-report-export style="margin-top:8px;" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'pdf', this)" id="btnExpIndPdf">
                                <span>&#128221;</span> Exportar PDF
                            </button>
                        <?php else: ?>
                            <div class="flex gap-10">
                                <button type="button" class="btn btn-success" data-report-export style="flex:1;" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'excel', this)">
                                    <span>&#128190;</span> Excel
                                </button>
                                <button type="button" class="btn btn-primary" data-report-export style="flex:1;" onclick="exportReport('<?php echo htmlspecialchars((string)$button['code'], ENT_QUOTES); ?>', 'pdf', this)">
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
.partidas-filter {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.partidas-filter .field-label {
    margin-bottom: 0;
}
.partidas-all-option,
.partida-option {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--white);
    cursor: pointer;
    transition: border-color .2s, background-color .2s;
}
.partidas-all-option:hover,
.partida-option:hover {
    border-color: var(--blue);
    background: #f7faff;
}
.partidas-all-option input,
.partida-option input {
    flex: 0 0 auto;
    margin: 3px 0 0;
    accent-color: var(--blue);
}
.partidas-all-option span {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.partidas-all-option strong {
    color: var(--text-dark);
    font-size: .9rem;
}
.partidas-all-option small,
.partidas-selection-count {
    color: var(--text-muted);
    font-size: .78rem;
}
.partidas-options {
    display: grid;
    gap: 6px;
    max-height: 180px;
    overflow-y: auto;
    padding: 2px;
}
.partida-option {
    align-items: center;
    padding: 8px 10px;
    font-size: .84rem;
    color: var(--text-dark);
}
.partida-option.is-selected {
    border-color: var(--blue);
    background: #f0f6ff;
    color: #102a43;
    font-weight: 600;
}
.partida-option.is-selected span {
    color: #102a43;
}
.partidas-loading {
    padding: 10px;
    color: var(--text-muted);
    font-size: .84rem;
}
.partidas-selection-count {
    min-height: 1em;
}
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