<?php
$pageTitle = "Registrar Dependencia";
$jsFile    = "dependencia.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title"> Registrar Dependencia</div>
    <div class="topbar-actions">
        <a href="?url=dependencia&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">

    <!-- Step wizard -->
    <div class="step-wizard">
        <div class="step-item done">
            <div class="step-circle">&#10003;</div>
            <div class="step-label">Informaciòn</div>
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
            <span class="card-title">Nuevo registro — Dependencia</span>
        </div>
        <div class="card-body">
            <form id="formRegistro">
                <div class="field-group">
                    <label class="field-label">Nombre de la Dependencia</label>
                    <input type="text" name="nombre_dep" class="field-input"
                           placeholder="Ej. Coordinación de Caja" required>
                </div>
                <div class="flex gap-10" style="margin-top:24px;">
                    <button type="submit" class="btn btn-success" style="flex:1;">
                        &#10003; Registrar
                    </button>
                    <a href="?url=dependencia&type=main" class="btn btn-outline" style="flex:1;justify-content:center;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
