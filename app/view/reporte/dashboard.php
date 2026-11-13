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
            <div class="report-grid<?php echo count($buttons) === 1 ? ' report-grid-single' : ''; ?>">
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
                            <div class="field-group report-choice-group" style="margin-bottom:12px;">
                                <span class="field-label">Tipo de dependencias</span>
                                <div id="selFiltroDependencias" class="report-choice-list" role="radiogroup" aria-label="Tipo de dependencias">
                                    <label class="report-choice is-selected">
                                        <input type="radio" name="filtro_dependencias" value="activas_con_responsable" checked>
                                        <span>Activas con responsable</span>
                                    </label>
                                    <label class="report-choice">
                                        <input type="radio" name="filtro_dependencias" value="activas_sin_responsable">
                                        <span>Activas sin responsable</span>
                                    </label>
                                    <label class="report-choice">
                                        <input type="radio" name="filtro_dependencias" value="inactivas">
                                        <span>Inactivas (con o sin responsables)</span>
                                    </label>
                                </div>
                            </div>
                        <?php elseif ($button['code'] === 'req_individual'): ?>
                            <?php if (!empty($esAdminReportes)): ?>
                                <div class="field-group dependency-search" style="margin-bottom:12px;">
                                    <label class="field-label" for="selDepIndividual" style="font-size:.85rem;">Dependencia</label>
                                    <input
                                        type="search"
                                        id="selDepIndividual"
                                        class="field-input"
                                        placeholder="Escriba al menos 2 caracteres"
                                        autocomplete="off"
                                        aria-autocomplete="list"
                                        aria-controls="opcionesDepIndividual"
                                        aria-expanded="false">
                                    <input type="hidden" id="idDepIndividual">
                                    <div id="opcionesDepIndividual" class="dependency-search-results" role="listbox" aria-label="Coincidencias de dependencias" hidden></div>
                                    <small id="estadoBusquedaDep" class="partidas-selection-count" aria-live="polite"></small>
                                </div>
                            <?php endif; ?>
                            <div class="field-group report-choice-group" style="margin-bottom:12px;">
                                <span class="field-label">Estado del requerimiento</span>
                                <div id="selEstadoReq" class="report-choice-list" role="radiogroup" aria-label="Estado del requerimiento">
                                    <label class="report-choice is-selected">
                                        <input type="radio" name="estado_envio" value="enviado" checked>
                                        <span>Enviado</span>
                                    </label>
                                    <label class="report-choice">
                                        <input type="radio" name="estado_envio" value="sin_enviar">
                                        <span>Sin enviar</span>
                                    </label>
                                </div>
                            </div>
                        <?php elseif ($button['code'] === 'req_global'): ?>
                            <div class="field-group report-choice-group" style="margin-bottom:12px;">
                                <span class="field-label">Tipo de consolidado</span>
                                <div id="selModoGlobal" class="report-choice-list" role="radiogroup" aria-label="Tipo de consolidado">
                                    <label class="report-choice is-selected">
                                        <input type="radio" name="modo_global" value="consolidado" checked>
                                        <span>Consolidado detallado</span>
                                    </label>
                                    <label class="report-choice">
                                        <input type="radio" name="modo_global" value="cantidades">
                                        <span>Cantidades anuales por producto y partida</span>
                                    </label>
                                    <label class="report-choice">
                                        <input type="radio" name="modo_global" value="totales">
                                        <span>Totales por partida (USD y Bs.)</span>
                                    </label>
                                </div>
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
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
    gap: 28px;
}
.report-grid-single {
    grid-template-columns: minmax(0, min(100%, 760px));
    justify-content: start;
}
.report-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 22px;
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
.report-choice-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.report-choice-group .field-label {
    margin-bottom: 0;
}
.report-choice-list {
    display: grid;
    gap: 6px;
}
.report-choice-scroll {
    max-height: 180px;
    overflow-y: auto;
    padding: 2px;
}
.report-choice {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 9px 11px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--white);
    color: var(--text-dark);
    cursor: pointer;
    font-size: .84rem;
    line-height: 1.4;
    transition: border-color .2s, background-color .2s, color .2s;
}
.report-choice:hover {
    border-color: var(--blue);
    background: #f7faff;
}
.report-choice input {
    flex: 0 0 auto;
    margin: 3px 0 0;
    accent-color: var(--blue);
}
.report-choice.is-selected {
    border-color: var(--blue);
    background: #f0f6ff;
    color: #102a43;
    font-weight: 600;
}
.report-choice.is-selected span {
    color: #102a43;
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
.dependency-search {
    position: relative;
}
.dependency-search-results {
    position: absolute;
    top: calc(100% - 8px);
    left: 0;
    right: 0;
    z-index: 20;
    max-height: 200px;
    overflow-y: auto;
    padding: 5px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--white);
    box-shadow: var(--shadow-sm);
}
.dependency-search-results[hidden] {
    display: none;
}
.dependency-search-option {
    display: block;
    width: 100%;
    padding: 9px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--text-dark);
    font: inherit;
    text-align: left;
    cursor: pointer;
}
.dependency-search-option:hover,
.dependency-search-option:focus {
    outline: none;
    background: #f0f6ff;
    color: #102a43;
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
@media (max-width: 760px) {
    .report-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 20px;
    }
}
</style>

<?php include_once 'app/view/layout/foot.php'; ?>