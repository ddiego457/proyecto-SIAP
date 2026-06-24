<?php
$pageTitle = "Requerimientos - Gestionar";
$jsFile    = "requerimiento.js";
include_once 'app/view/layout/head.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">&#128203; Requerimientos POA</div>
    <div class="topbar-actions">
        <a href="?url=requerimiento&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
    </div>
</div>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Requerimientos Consolidados</h2>
    
    <div class="table-responsive">
        <table id="tabla-consulta" class="table table-bordered table-striped w-100">
            <thead class="table-dark">
                <tr>
                    <th>dependencias</th>
                    <th>Partida</th>
                    <th>Producto</th>
                    <th>Ene</th><th>Feb</th><th>Mar</th><th>Abr</th><th>May</th><th>Jun</th>
                    <th>Jul</th><th>Ago</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dic</th>
                    <th class="bg-primary">Total Físico</th>
                    <th class="bg-success">Total USD</th>
                    <th class="bg-info">Total BS</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar requerimiento</h3>
            <button id="btnCerrarModal" class="modal-close">&times;</button>
        </div>
        <form id="formEditar">
            <input type="hidden" id="edit_idItem" name="idItem">
            <div class="modal-body">

                <div class="field-group">

                    <label class="field-label">Año Fiscal</label>
                    <div class="field-select-wrap">
                        <select id="edit_id_anioFis" name="id_anioFis" class="field-input field-select" required>
                            <?php foreach ($aniosFiscales as $a): ?>
                            <option value="<?php echo $a['id_anioFis']; ?>">
                                <?php echo $a['periodo_ini'].' al '.$a['periodo_fin']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Tasa BCV</label>
                    <div class="field-select-wrap">
                        <select id="edit_id_tasa" name="id_tasa" class="field-input field-select" required>
                            <?php foreach ($tasas as $t): ?>
                            <option value="<?php echo $t['id_tasa']; ?>">
                                Bs. <?php echo number_format($t['valor_usd'], 4); ?>
                                (<?php echo $t['fecha_registro']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Fecha de Envío</label>
                    <input type="date" id="edit_fecha_envio" name="fecha_envio" class="field-input" required>
                </div>

                    <div class="field-group">
                        <label class="field-label">Cantidad por Mes</label>
                        <input type="number" id="edit_cantidad_por_mes" name="cantidad_por_mes"
                               class="field-input" step="0.01" min="0" required>
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
