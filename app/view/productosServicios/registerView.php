<?php
$pageTitle = "Registrar â€” Productos y Servicios";
$jsFile = "productosServicios.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">&#43; Registrar item</div>
    <div class="topbar-actions">
        <a href="?url=productosServicios&type=main" class="btn btn-outline btn-sm">&#8592; Volver</a>
    </div>
</div>

<div class="page-body">
    <div class="card" style="max-width:560px; margin:0 auto;">
        <div class="card-header">
            <span class="card-title">Nuevo registro &#8212; Producto o Servicio</span>
        </div>
        <div class="card-body">
            <?php if (empty($partidas) || empty($proveedores)): ?>
                <?php if (empty($partidas)): ?>
                    <div class="alert-banner-sub">No se encontraron partidas existentes. Cree primero las partidas 401, 402, 403, 404 o 407 en el catálogo correspondiente.</div>
                <?php endif; ?>
                <?php if (empty($proveedores)): ?>
                    <div class="alert-banner-sub">No se encontraron proveedores activos. Cree primero un proveedor en el catálogo correspondiente.</div>
                <?php endif; ?>
                <div class="flex gap-10" style="margin-top:24px;">
                    <a href="?url=productosServicios&type=main" class="btn btn-outline" style="flex:1; justify-content:center;">Volver</a>
                </div>
            <?php else: ?>
                <form id="formRegistroPS">
                    <div class="field-group">
                        <label class="field-label">Partida</label>
                        <select name="id_partida" class="field-input field-select" required>
                            <?php foreach ($partidas as $partida): ?>
                                <option value="<?php echo htmlspecialchars((string)$partida['id_partida']); ?>">
                                    <?php echo htmlspecialchars($partida['cod_partida'] . ' - ' . $partida['descripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Proveedor</label>
                        <select name="id_proveedor" class="field-input field-select" required>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo htmlspecialchars((string)$proveedor['id_proveedor']); ?>">
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Nombre del producto o servicio</label>
                        <input type="text" name="nom_item" class="field-input" placeholder="Ej. Resma de papel" required />
                    </div>

                    <div class="field-group">
                        <label class="field-label">Precio unitario (USD)</label>
                        <input type="number" name="precio" class="field-input" step="0.01" min="0" placeholder="Ej. 5.50" required />
                    </div>

                    <div class="flex gap-10" style="margin-top:24px;">
                        <button type="submit" class="btn btn-success" style="flex:1;">&#10003; Registrar</button>
                        <a href="?url=productosServicios&type=main" class="btn btn-outline" style="flex:1; justify-content:center;">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
