<?php
$pageTitle = "Periodos";
$jsFile    = "periodo.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Periodos</div>
    <div class="topbar-actions">
        <a href="?url=periodo&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
        <button type="button" id="btnAniosFiscales" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> A&ntilde;os fiscales</button>
    </div>
</div>

<div class="page-body">

    <!-- INFO PORTAL -->
    <div class="alert-banner alert-info">
        <div class="alert-banner-left">
            <div class="alert-banner-title"> <strong>&iquest;Qu&eacute; puedo hacer en este portal?</strong></div>
            <div class="alert-banner-sub">
                En esta secci&oacute;n usted define el periodo de env&iacute;o de los requerimientos: las fechas de
                inicio y fin durante las cuales cada dependencia puede registrar y enviar sus solicitudes. Cada
                periodo se asocia a un a&ntilde;o fiscal, por lo que debe existir al menos un a&ntilde;o fiscal
                registrado  antes de crear un periodo.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Periodos</span>
        </div>
        <div class="card-body">
            <div class="table-wrap">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Año Fiscal</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th><th>Acciones</th></tr></thead>
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
            <input type="hidden" name="updateItem" value="1">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Fecha Inicio</label>
                    <input type="date" id="edit_fecha_inicio" name="fecha_inicio" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha Fin</label>
                    <input type="date" id="edit_fecha_fin" name="fecha_fin" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Año Fiscal</label>
                    <select id="edit_anio_fiscal_id" name="anio_fiscal_id" class="field-input field-select" required>
                        <?php if (empty($anioFiscales)): ?>
                            <option value="">No hay años fiscales disponibles</option>
                        <?php else: ?>
                            <?php foreach ($anioFiscales as $af): ?>
                                <option value="<?php echo (int)$af['id_aniof']; ?>">
                                    <?php echo htmlspecialchars($af['anio'] ?? $af['anio_fiscal'] ?? 'Año no disponible', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="field-group">
                    <p class="field-note">El periodo se desactivara automaticamente cuando la fecha fin haya pasado.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL LISTADO AÑOS FISCALES -->
<div id="modalAniosFiscales" class="modal-backdrop" style="display:none; z-index:210;">
    <div class="modal-panel" style="max-width:760px;">
        <div class="modal-header">
            <h3>A&ntilde;os fiscales</h3>
            <button id="btnCerrarModalAnios" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <div class="table-wrap">
                <table id="tablaAniosFiscales" class="siap-table" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Año Fiscal</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" id="btnNuevoAnioFiscal" class="btn btn-outline" style="margin-right:auto;">&#43; Nuevo a&ntilde;o fiscal</button>
            <button type="button" id="btnCerrarModalAnios2" class="btn btn-success">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL NUEVO AÑO FISCAL -->
<div id="modalAnioFiscal" class="modal-backdrop" style="display:none; z-index:220;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Nuevo a&ntilde;o fiscal</h3>
            <button id="btnCerrarModalAnioFiscal" class="modal-close">&times;</button>
        </div>

        <form id="formAnioFiscal">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Año Fiscal</label>
                    <input type="number" id="aniof_anio" name="anio_fiscal" class="field-input" min="1900" max="2100" placeholder="Ej. 2026" required>
                </div>

                <div class="field-group">
                    <p class="field-note">El a&ntilde;o fiscal se creará como activo y se desactivarán los dem&aacute;s autom&aacute;ticamente.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalAnioFiscal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Guardar a&ntilde;o fiscal</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR AÑO FISCAL -->
<div id="modalEditarAnioFiscal" class="modal-backdrop" style="display:none; z-index:230;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar a&ntilde;o fiscal</h3>
            <button id="btnCerrarModalEditarAnioFiscal" class="modal-close">&times;</button>
        </div>

        <form id="formAnioFiscalEditar">
            <input type="hidden" id="editAniof_id" name="idItem">

            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Año Fiscal</label>
                    <input type="number" id="editAniof_anio" name="anio_fiscal" class="field-input" min="1900" max="2100" required>
                </div>

                <div class="field-group">
                    <p class="field-note">El estado activo no se puede modificar desde este formulario.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalEditarAnioFiscal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>


