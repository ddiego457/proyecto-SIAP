<?php
$pageTitle = "Productos y Servicios";
$jsFile = "productosServicios.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Productos y Servicios</div>
    <div class="topbar-actions">
        <a href="?url=productosServicios&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
    </div>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Items asignados por partida</span>
        </div>
        <div class="card-body">
            <div class="field-group" style="max-width:360px; margin-bottom:18px;">
                <label class="field-label">Partida</label>
                <select id="selectPartida" class="field-input field-select">
                    <?php if (!empty($partidas)): ?>
                        <?php foreach ($partidas as $partida): ?>
                            <option value="<?php echo htmlspecialchars((string)$partida['id_partida']); ?>" <?php echo ((int)$partida['id_partida'] === (int)$partidaSeleccionada) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($partida['cod_partida'] . ' - ' . $partida['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <?php if (empty($partidas)): ?>
                <div class="alert-banner-sub">No se encontraron partidas activas para 401, 402, 403, 404 o 407.</div>
            <?php endif; ?>

            <div class="table-wrap">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Partida</th>
                        <th>Proveedor</th>
                        <th>Nombre</th>
                        <th>Precio unitario (USD)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalEditar" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar item</h3>
            <button id="btnCerrarModal" class="modal-close">&times;</button>
        </div>

        <form id="formEditar">
            <input type="hidden" id="edit_idItem" name="idItem">

            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Partida</label>
                    <select id="edit_partida_id" name="id_partida" class="field-input field-select" required>
                        <?php foreach ($partidas as $partida): ?>
                            <option value="<?php echo htmlspecialchars((string)$partida['id_partida']); ?>">
                                <?php echo htmlspecialchars($partida['cod_partida'] . ' - ' . $partida['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label">Proveedor</label>
                    <select id="edit_proveedor_id" name="id_proveedor" class="field-input field-select" required>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?php echo htmlspecialchars((string)$proveedor['id_proveedor']); ?>">
                                <?php echo htmlspecialchars($proveedor['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label">Nombre del producto o servicio</label>
                    <input type="text" id="edit_nom_item" name="nom_item" class="field-input" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Precio unitario (USD)</label>
                    <input type="number" id="edit_precio" name="precio" class="field-input" step="0.01" min="0" required>
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
