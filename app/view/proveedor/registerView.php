<?php
$pageTitle = "Registrar — Proveedor";
$jsFile    = "proveedor.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Registrar Proveedor</div>
    <div class="topbar-actions">
        <a href="?url=proveedor&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">
    <div class="card" style="max-width:560px;margin:0 auto;">
        <div class="card-header">
            <span class="card-title">Nuevo Proveedor</span>
        </div>
        <div class="card-body">
            <form id="formRegistro">
                <div class="field-group">
                    <label class="field-label">Nombre del Proveedor</label>
                    <input type="text" name="nombre" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <textarea name="descripcion" class="field-input" rows="3"></textarea>
                </div>
                <div class="flex gap-10" style="margin-top:24px;">
                    <button type="submit" class="btn btn-success" style="flex:1;">&#10003; Registrar</button>
                    <a href="?url=proveedor&type=main" class="btn btn-outline" style="flex:1;justify-content:center;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
