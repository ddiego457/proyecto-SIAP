<?php
$pageTitle = "Tasas BCV";
$jsFile    = "tasaBCV.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Tasas BCV</div>
    <div class="topbar-actions">
        <a href="?url=tasaBCV&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
    </div>
</div>

<!-- BODY -->
<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Tasas BCV</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Tasa BCV (USD)</th><th>Fecha</th><th>Acciones</th></tr></thead>
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
            <input type="hidden" id="edit_id_tasa" name="id_tasa">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Tasa BCV (USD)</label>
                          <input type="number" id="edit_tasa_bcv_usd" name="tasa_bcv_usd"
                              class="field-input" step="0.01" min="0" required>
                    <input type="hidden" id="edit_estado" name="estado" value="1">
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha de Registro</label>
                    <input type="date" id="edit_fecha_reg" name="fecha_reg"
                           class="field-input" required>
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
