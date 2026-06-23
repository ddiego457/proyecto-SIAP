<?php
$pageTitle = "Contactos del Proveedor";
$jsFile    = "proveedor.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Contactos</div>
    <div class="topbar-actions">
        <a href="?url=proveedor&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Contactos del proveedor</span>
        </div>
        <div class="card-body">
            <div class="card-subtitle">Proveedor: <?php echo htmlspecialchars($proveedorNombre); ?></div>
            <div class="field-group">
                <label class="field-label">Agregar teléfono</label>
                <div class="field-group">
                    <input type="hidden" id="contact_id_proveedor" value="<?php echo $idProveedor; ?>">
                    <input type="text" id="contact_telefono" class="field-input" placeholder="Ej. 0414-1234567">
                </div>
                <button id="btnAgregarContacto" class="btn btn-success btn-sm" style="margin-top:10px;">Agregar</button>
            </div>
            <div class="table-wrap" style="margin-top:20px;">
                <table id="tablaContactos" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalEditarContacto" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar contacto</h3>
            <button id="btnCerrarModalContacto" class="modal-close">&times;</button>
        </div>
        <form id="formEditarContacto">
            <input type="hidden" id="edit_idContacto" name="idContacto">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Teléfono</label>
                    <input type="text" id="edit_telefono" name="telefono" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Estado</label>
                    <select id="edit_estadoContacto" name="estado" class="field-input field-select" required>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModalContacto2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
