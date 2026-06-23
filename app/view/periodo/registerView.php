<?php
$pageTitle = "Registrar - Periodo";
$jsFile    = "periodo.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Registrar Periodo</div>
    <div class="topbar-actions">
        <a href="?url=periodo&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">
    <div class="card" style="max-width:560px;margin:0 auto;">
        <div class="card-header">
            <span class="card-title">Nuevo registro — Periodo</span>
        </div>
        <div class="card-body">
            <form id="formRegistro">
                <input type="hidden" name="registerPeriodo" value="1">
                <div class="field-group">
                    <label class="field-label">Fecha Inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Fecha Fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Año Fiscal</label>
                    <select name="anio_fiscal_id" class="field-input field-select" required>
                        <?php if (empty($anioFiscales)): ?>
                            <option value="">No hay años fiscales disponibles</option>
                        <?php else: ?>
                            <?php foreach ($anioFiscales as $af): ?>
                                <option value="<?php echo (int)$af['id_aniof']; ?>">
                                    <?php echo htmlspecialchars($af['anio'] ?? $af['anio_fiscal'] ?? 'Año no disponible', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>>
                    </select>
                </div>
                <div class="field-group">
                    <p class="field-note">El periodo se desactivara automaticamente cuando la fecha fin haya pasado.</p>
                </div>

                <div class="flex gap-10" style="margin-top:24px;">
                    <button type="submit" class="btn btn-success" style="flex:1;">
                        &#10003; Registrar
                    </button>
                    <a href="?url=periodo&type=main" class="btn btn-outline" style="flex:1;justify-content:center;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>

