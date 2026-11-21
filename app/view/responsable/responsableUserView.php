<?php
$pageTitle = "Responsables";
$jsFile    = "responsable.js";
include_once 'app/view/layout/head.php';
?>
<script>
    window.SIAP_ES_ADMINISTRADOR = <?php echo !empty($esAdministradorUsuarios) ? 'true' : 'false'; ?>;
</script>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Responsables</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<!-- BODY -->
<div class="page-body">

    <!-- INFO PORTAL -->
    <div class="alert-banner alert-info">
        <div class="alert-banner-left">
            <div class="alert-banner-title"> <strong>&iquest;Qu&eacute; puedo hacer en este portal?</strong></div>
            <div class="alert-banner-sub">
                En esta secci&oacute;n usted registra a la persona responsable de una dependencia: es el usuario
                encargado de administrar los requerimientos de esa dependencia. Para poder asignar un responsable,
                la dependencia debe existir previamente en el sistema y no puede tener ya otra persona a cargo.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Responsables</span>
            <div class="card-header-actions">
                <button type="button" id="btnNuevoRegistro" class="btn btn-success btn-sm">Registrar</button>
                <?php if ($this->puedeGestionarRoles()): ?>
                    <button type="button" id="btnRoles" class="btn btn-outline btn-sm"><i class="fa-solid fa-users" aria-hidden="true"></i> Roles</button>
                <?php endif; ?>
            </div>
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

<!-- MODAL REGISTRO -->
<div id="modalRegistro" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Nuevo Responsable</h3>
            <button id="btnCerrarModalRegistro" class="modal-close">&times;</button>
        </div>
        <form id="formRegistro">
            <div class="modal-body">
                <?php if (empty($dependenciasDisponibles)): ?>
                    <div class="alert-banner alert-info" style="margin-bottom:15px;">
                        <div class="alert-banner-sub">
                            <strong>Atención:</strong> No hay dependencias disponibles. Todas tienen un responsable asignado. Para reutilizar una, primero elimine o inactive su responsable actual.
                        </div>
                    </div>
                <?php endif; ?>
                <div class="field-group">
                    <label class="field-label">Nombre</label>
                    <input type="text" name="nom_rep" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Correo electrónico</label>
                    <input type="email" name="email" class="field-input" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Contraseña</label>
                    <input type="password" name="contrasena" class="field-input" required>
                    <small class="field-hint">Mínimo 8 caracteres, con al menos un número y un carácter especial.</small>
                </div>
                <div class="field-group">
                    <label class="field-label">Rol</label>
                    <div class="field-select-wrap">
                        <select name="id_rol" class="field-input field-select" required>
                            <option value="">— Seleccione rol —</option>
                            <?php foreach ($roles as $rol): ?>
                                <?php if (empty($esAdministradorUsuarios) || mb_strtolower(trim((string)$rol['descripcion']), 'UTF-8') !== 'coordinador'): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>" data-role="<?php echo htmlspecialchars(mb_strtolower(trim((string)$rol['descripcion']), 'UTF-8'), ENT_QUOTES); ?>"><?php echo htmlspecialchars($rol['descripcion']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Dependencia</label>
                    <input list="dependenciasList" id="register_dependencia_search" name="dependencia_search" class="field-input" placeholder="Escriba para buscar dependencia" autocomplete="off" required>
                    <datalist id="dependenciasList">
                        <?php foreach ($dependenciasDisponibles as $dep): ?>
                            <option value="<?php echo htmlspecialchars($dep['nom_dep']); ?>" data-id="<?php echo $dep['id_dep']; ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" id="register_id_dep" name="id_dep">
                    <p class="field-hint">Escriba y seleccione una dependencia existente de la lista.</p>
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
                    <small class="field-hint">Mínimo 8 caracteres, con al menos un número y un carácter especial.</small>
                </div>
                <div class="field-group">
                    <label class="field-label">Rol</label>
                    <div class="field-select-wrap">
                        <select id="edit_id_rol" name="id_rol" class="field-input field-select" required>
                            <option value="">— Seleccione rol —</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id_rol']; ?>" data-role="<?php echo htmlspecialchars(mb_strtolower(trim((string)$rol['descripcion']), 'UTF-8'), ENT_QUOTES); ?>"><?php echo htmlspecialchars($rol['descripcion']); ?></option>
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

<?php if ($this->puedeGestionarRoles()): ?>
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
                        <th>Acceso</th>
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

<!-- MODAL ACCESO A MÓDULOS DEL ROL (personalización de permisos) -->
<div id="modalModulosRol" class="modal-backdrop" style="display:none; z-index:240;">
    <div class="modal-panel" style="max-width:620px;">
        <div class="modal-header">
            <h3 id="modulosRolTitulo">Acceso a m&oacute;dulos</h3>
            <button id="btnCerrarModalModulosRol" class="modal-close">&times;</button>
        </div>

        <form id="formModulosRol">
            <input type="hidden" id="modulosRol_id" name="idRol">

            <div class="modal-body">
                <div id="modulosRolAviso" class="alert-banner alert-info" style="display:none;">
                    <div class="alert-banner-left">
                        <div class="alert-banner-title"><strong>Rol protegido del sistema</strong></div>
                        <div class="alert-banner-sub">
                            Los roles Administrador y Coordinador siempre tienen acceso total a todos los
                            m&oacute;dulos; su configuraci&oacute;n no puede modificarse.
                        </div>
                    </div>
                </div>

                <label class="modulos-todos">
                    <input type="checkbox" id="modulosRolTodos">
                    <span>Acceso a todos los m&oacute;dulos</span>
                </label>

                <div class="field-group" style="margin-top:14px; margin-bottom:0;">
                    <label class="field-label">M&oacute;dulos individuales</label>
                    <div id="modulosRolLista" class="modulos-checklist"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalModulosRol2" class="btn btn-outline">Cancelar</button>
                <button type="submit" id="btnGuardarModulosRol" class="btn btn-success">&#10003; Guardar acceso</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include_once 'app/view/layout/foot.php'; ?>
