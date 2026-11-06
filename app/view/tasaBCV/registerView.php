<?php
$pageTitle = "Registrar Tasa BCV";
$jsFile    = "tasaBCV.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">Registrar Tasa BCV</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<div class="page-body">

    <!-- Step wizard -->
    <div class="step-wizard">
        <div class="step-item done">
            <div class="step-circle">&#10003;</div>
            <div class="step-label">Informaci&oacute;n</div>
        </div>
        <div class="step-item active">
            <div class="step-circle">2</div>
            <div class="step-label">Datos</div>
        </div>
        <div class="step-item">
            <div class="step-circle">3</div>
            <div class="step-label">Confirmar</div>
        </div>
    </div>

    <div class="card" style="max-width:560px;margin:0 auto;">
        <div class="card-header">
            <span class="card-title">Nuevo registro — Tasa BCV</span>
            <div class="card-header-actions">
                <a href="?url=tasaBCV&type=main" class="btn btn-color btn-sm">&#8592; Volver</a>
            </div>
        </div>
        <div class="card-body">
            <form id="formRegistro">
                <div class="field-group">
                    <label class="field-label">Tasa BCV (USD)</label>
                          <input type="number" name="tasa_bcv_usd" class="field-input"
                              step="0.01" min="0" placeholder="Ej. 36.52" required>
                    <input type="hidden" name="estado" value="1">
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha de Registro</label>
                    <input type="date" name="fecha_reg" class="field-input" required>
                </div>
                <div class="flex gap-10" style="margin-top:24px;">
                    <button type="submit" class="btn btn-success" style="flex:1;">
                        &#10003; Registrar
                    </button>
                    <a href="?url=tasaBCV&type=main" class="btn btn-outline" style="flex:1;justify-content:center;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
