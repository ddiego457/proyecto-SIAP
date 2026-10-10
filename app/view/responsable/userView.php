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
        <button type="button" id="btnRoles" class="btn btn-outline btn-sm"><i class="fa-solid fa-users" aria-hidden="true"></i> Roles</button>
    </div>
</div>

<!-- BODY -->
<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Responsables</span>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Dependencia actual</th><th>Estado</th><th>Acciones</th></tr></thead>
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
                    <label class="field-label">Correo electrónico</label>
                    <input type="email" id="edit_email" name="email" class="field-input" placeholder="correo@ejemplo.com" required>
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
                <div class="field-group" id="editDependenciaGroup" style="display:none; margin-top:15px;">
                    <label class="field-label">Asignar Dependencia</label>
                    <div class="field-select-wrap">
                        <select class="field-input field-select" id="edit_id_dep" name="id_dep">
                            <option value="">-- Seleccione una dependencia disponible --</option>
                        </select>
                    </div>
                    <small class="text-muted">Solo disponible para responsables activos sin dependencia asignada.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ROLES (listado) -->
<div id="modalRoles" class="modal-backdrop" style="display:none; z-index:210;">
    <div class="modal-panel" style="max-width:720px;">
        <div class="modal-header">
            <h3>Roles</h3>
            <button id="btnCerrarModalRoles" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <div class="table-wrap">
                <table id="tablaRoles" class="siap-table" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Responsables</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" id="btnNuevoRol" class="btn btn-outline" style="margin-right:auto;">&#43; Nuevo rol</button>
            <button type="button" id="btnCerrarModalRoles2" class="btn btn-success">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL NUEVO ROL -->
<div id="modalRol" class="modal-backdrop" style="display:none; z-index:220;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Nuevo rol</h3>
            <button id="btnCerrarModalRol" class="modal-close">&times;</button>
        </div>

        <form id="formRol">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <input type="text" id="rol_descripcion" name="descripcion" class="field-input" maxlength="70" placeholder="Ej. Analista" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalRol2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Guardar rol</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR ROL -->
<div id="modalEditarRol" class="modal-backdrop" style="display:none; z-index:230;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar rol</h3>
            <button id="btnCerrarModalEditarRol" class="modal-close">&times;</button>
        </div>

        <form id="formEditarRol">
            <input type="hidden" id="editRol_id" name="idRol">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <input type="text" id="editRol_descripcion" name="descripcion" class="field-input" maxlength="70" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalEditarRol2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
