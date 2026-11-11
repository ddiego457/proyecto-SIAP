<?php
$pageTitle = "Tasas BCV";
$jsFile    = "tasaBCV.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Tasas BCV</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<!-- BODY -->
<div class="page-body">

    <!-- INFO PORTAL -->
    <div class="alert-banner alert-info">
        <div class="alert-banner-left">
            <div class="alert-banner-title"> <strong>¿Qué puedo hacer en este portal?</strong></div>
            <div class="alert-banner-sub">
                Aqu&iacute; usted registra la tasa del d&oacute;lar (USD) publicada por el Banco Central de Venezuela (BCV)
                para la fecha en curso. El sistema tomar&aacute; autom&aacute;ticamente la &uacute;ltima tasa registrada
                para convertir los montos en d&oacute;lares de los requerimientos a bol&iacute;vares (VES),
                calculando el presupuesto de forma actualizada y sin necesidad de conversi&oacute;n manual.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Tasas BCV</span>
            <div class="card-header-actions">
                <button type="button" id="btnNuevoRegistro" class="btn btn-success btn-sm">Registrar</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Tasa BCV (USD)</th><th>Fecha</th><th>Estado</th><th>Origen</th><th>Acciones</th></tr></thead>
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
            <h3>Nuevo registro &mdash; Tasa BCV</h3>
            <button id="btnCerrarModalRegistro" class="modal-close">&times;</button>
        </div>
        <form id="formRegistro">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Tasa BCV (USD)</label>
                    <input type="number" name="tasa_bcv_usd" class="field-input"
                           step="0.01" min="0" placeholder="Ej. 36.52" required>
                    <button type="button" id="btnTasaApiRegistro" class="btn btn-blue btn-sm btn-api-tasa"
                            title="Cargar la tasa del día desde la API (dolarapi.com)">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Usar tasa de la API
                    </button>
                    <input type="hidden" name="estado" value="1">
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha de Registro</label>
                    <input type="date" name="fecha_reg" class="field-input" required>
                    <p class="field-note">Formato: d&iacute;a/mes/a&ntilde;o (dd/mm/aaaa)</p>
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
            <input type="hidden" id="edit_id_tasa" name="id_tasa">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Tasa BCV (USD)</label>
                    <input type="number" id="edit_tasa_bcv_usd" name="tasa_bcv_usd"
                           class="field-input" step="0.01" min="0" required>
                    <button type="button" id="btnTasaApiEdicion" class="btn btn-blue btn-sm btn-api-tasa"
                            title="Cargar la tasa del día desde la API (dolarapi.com)">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Usar tasa de la API
                    </button>
                    <input type="hidden" id="edit_estado" name="estado" value="1">
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha de Registro</label>
                    <input type="date" id="edit_fecha_reg" name="fecha_reg"
                           class="field-input" required>
                    <p class="field-note">Formato: d&iacute;a/mes/a&ntilde;o (dd/mm/aaaa)</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Año fiscal activo (anio_fiscal.activo = 1): lo consume tasaBCV.js
    // para impedir registrar o modificar fechas de otros años.
    window.SIAP_ANIO_FISCAL_ACTIVO = <?php echo (isset($anioFiscalActivo) && $anioFiscalActivo !== null) ? (int)$anioFiscalActivo : 'null'; ?>;
</script>

<?php include_once 'app/view/layout/foot.php'; ?>
