<?php
$pageTitle = "Años Fiscales";
$jsFile    = "anioFiscal.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Años Fiscales</div>
    <div class="topbar-actions">
        <a href="?url=anioFiscal&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
    </div>
</div>

<!-- BODY -->
<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Años Fiscales</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Año Fiscal</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar registro</h3>
            <button id="btnCerrarModal" class="modal-close">&times;</button>
        </div>
        <form id="formEditar">
            <input type="hidden" id="edit_idItem" name="idItem">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Año Fiscal</label>
                    <input type="number" id="edit_anio_fiscal" name="anio_fiscal" class="field-input" min="1900" max="2100" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Activo</label>
                    <label class="field-switch">
                        <input type="checkbox" id="edit_estado" name="estado">
                        <span class="switch-slider"></span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
