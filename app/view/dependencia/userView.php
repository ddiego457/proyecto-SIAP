<?php
$pageTitle = "Dependencias";
$jsFile    = "dependencia.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Dependencias</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<!-- BODY -->
<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Dependencias</span>
            <div class="card-header-actions">
                <button type="button" id="btnNuevoRegistro" class="btn btn-success btn-sm">Registrar</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Nombre Dependencia</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO -->
<div id="modalRegistro" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Nuevo registro &mdash; Dependencia</h3>
            <button id="btnCerrarModalRegistro" class="modal-close">&times;</button>
        </div>
        <form id="formRegistro">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Nombre de la Dependencia</label>
                    <input type="text" name="nombre_dep" class="field-input"
                           placeholder="Ej. Coordinación de Caja" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModalRegistro2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Registrar</button>
            </div>
        </form>
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
                    <label class="field-label">Nombre Dependencia</label>
                    <input type="text" id="edit_nombre_dep" name="nombre_dep" class="field-input" required>
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
