<?php
$pageTitle = 'Dashboard de Informes';
$jsFile = 'reporte.js';
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
            <p id="reportIntro" style="margin-bottom:18px; color: var(--text-muted);"></p>
            <div id="reportGrid" class="report-grid" data-is-admin="<?php echo !empty($esAdminReportes) ? 'true' : 'false'; ?>"></div>
        </div>
    </div>
</div>

<div id="exportModal" class="modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="exportModalTitle">
    <div class="modal-panel export-modal-panel">
        <div class="modal-header">
            <h3 id="exportModalTitle">Descargar informe</h3>
            <button type="button" id="btnCloseExportModal" class="modal-close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body" id="exportModalBody"></div>
        <div class="modal-footer">
            <button type="button" id="btnCancelExport" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmExport" class="btn btn-success">Descargar</button>
        </div>
    </div>
</div>

<style>
.export-modal-panel {
    max-width: 560px;
}
#exportModalBody .field-group:last-child {
    margin-bottom: 0 !important;
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 350px), 1fr));
    gap: 28px;
    display: grid;
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
    min-height: 150px;
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
.report-card-actions {
    margin-top: auto;
}
.report-card-actions .btn {
    flex: 1;
    justify-content: center;
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
.price-range {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.report-note {
    display: block;
    min-height: 1em;
    margin-top: 4px;
    color: var(--text-muted);
    font-size: .78rem;
    line-height: 1.4;
}
.report-note:not(:empty) {
    color: #b45309;
    font-weight: 600;
}
.dependency-search {
    position: relative;
}
.dependency-search-toggle {
    margin-top: 6px;
    padding: 4px 0;
    border: 0;
    background: transparent;
    color: var(--blue);
    font: inherit;
    font-size: .84rem;
    cursor: pointer;
}
.dependency-search-toggle:hover,
.dependency-search-toggle:focus {
    color: #102a43;
    text-decoration: underline;
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