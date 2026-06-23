<?php
$pageTitle = "Periodos";
$jsFile    = "periodo.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Periodos</div>
    <div class="topbar-actions">
        <a href="?url=periodo&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
    </div>
</div>

<div class="page-body">
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
                    <label class="field-label">AÃ±o Fiscal</label>
                    <select id="edit_anio_fiscal_id" name="anio_fiscal_id" class="field-input field-select" required>
                        <?php foreach (($anioFiscales ?? []) as $af): ?>
                            <option value="<?php echo (int)$af['id_anioFis']; ?>"><?php echo htmlspecialchars($af['anio_fiscal'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <p class="field-note">El periodo se desactivarÃ¡ automÃ¡ticamente cuando la fecha fin haya pasado.</p>
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


