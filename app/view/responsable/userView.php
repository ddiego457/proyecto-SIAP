<?php
$pageTitle = "Responsables";
$jsFile    = "responsable.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Responsables</div>
    <div class="topbar-actions">
        <a href="?url=responsable&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
    </div>
</div>

<!-- BODY -->
<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Responsables</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Dependencia actual</th><th>Estado</th><th>Acciones</th></tr></thead>
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
            <h3>Editar responsable</h3>
            <button id="btnCerrarModal" class="modal-close">&times;</button>
        </div>
        <form id="formEditar">
            <input type="hidden" id="edit_idItem" name="idItem">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Nombre</label>
                    <input type="text" id="edit_nom_rep" name="nom_rep" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Contraseña (opcional)</label>
                    <input type="password" id="edit_contrasena" name="contrasena" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label">Rol</label>
                    <div class="field-select-wrap">
                        <select id="edit_id_rol" name="id_rol" class="field-input field-select" required>
                            <option value="">— Seleccione rol —</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['descripcion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ASIGNAR -->
<div id="modalAsignar" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Asignar Responsable</h3>
            <button id="btnCerrarModalAsignar" class="modal-close">&times;</button>
        </div>
        <form id="formAsignar">
            <input type="hidden" id="assign_id_responsable" name="id_responsable">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Dependencia</label>
                    <div class="field-select-wrap">
                        <select id="assign_id_dep" name="id_dep" class="field-input field-select" required>
                            <option value="">— Seleccione dependencia —</option>
                            <?php foreach ($dependencias as $dep): ?>
                                <option value="<?php echo $dep['id_dep']; ?>"><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha Inicio</label>
                    <input type="date" id="assign_fecha_inicio" name="fecha_inicio" class="field-input" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModalAsignar2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Asignar</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
