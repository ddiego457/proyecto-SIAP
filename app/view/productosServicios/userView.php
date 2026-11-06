<?php
$pageTitle = "Productos y Servicios";
$jsFile = "productosServicios.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Productos y Servicios</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Items asignados por partida</span>
            <div class="card-header-actions">
                <a href="?url=productosServicios&type=register" class="btn btn-success btn-sm">Registrar</a>
                <button type="button" id="btnPartidas" class="btn btn-outline btn-sm"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Partidas presupuestaria</button>
            </div>
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

            <div class="table-wrapper">
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
                    <input type="text" id="edit_nom_prod" name="nom_item" class="field-input" required>
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

<div id="modalPartidas" class="modal-backdrop" style="display:none; z-index:210;">
    <div class="modal-panel" style="max-width:760px;">
        <div class="modal-header">
            <h3>Partidas presupuestarias</h3>
            <button id="btnCerrarModalPartidas" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <div class="table-wrap">
                <table id="tablaPartidas" class="siap-table" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" id="btnNuevaPartida" class="btn btn-outline" style="margin-right:auto;">&#43; Nueva partida</button>
            <button type="button" id="btnCerrarModalPartidas2" class="btn btn-success">Cerrar</button>
            <button type="button" id="btnImportarExcel" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Importar Excel</button>
        </div>
    </div>
</div>

<div id="modalPartida" class="modal-backdrop" style="display:none; z-index:220;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Nueva partida presupuestaria</h3>
            <button id="btnCerrarModalPartida" class="modal-close">&times;</button>
        </div>

        <form id="formPartida">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Código de partida</label>
                    <input type="text" id="partida_cod" name="cod_partida" class="field-input" maxlength="10" placeholder="Ej. 401" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <input type="text" id="partida_descripcion" name="descripcion" class="field-input" maxlength="150" placeholder="Ej. Productos (Insumos, materiales, suministros)" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalPartida2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Guardar partida</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditarPartida" class="modal-backdrop" style="display:none; z-index:230;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar partida presupuestaria</h3>
            <button id="btnCerrarModalEditarPartida" class="modal-close">&times;</button>
        </div>

        <form id="formPartidaEditar">
            <input type="hidden" id="editPartida_id" name="idPartida">

            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Código de partida</label>
                    <input type="text" id="editPartida_cod" name="cod_partida" class="field-input" maxlength="10" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <input type="text" id="editPartida_descripcion" name="descripcion" class="field-input" maxlength="150" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Estado</label>
                    <select id="editPartida_estado" name="estado" class="field-input field-select" required>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalEditarPartida2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<div id="modalImportar" class="modal-backdrop" style="display:none; z-index:240;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Importar productos desde Excel</h3>
            <button id="btnCerrarModalImportar" class="modal-close">&times;</button>
        </div>

        <form id="formImportar" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Archivo Excel (.xlsx o .xls)</label>
                    <input type="file" name="archivoExcel" id="archivoExcel" class="field-input" accept=".xlsx,.xls" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Proveedor asignado</label>
                    <select name="id_proveedor" class="field-input field-select" required>
                        <?php if (!empty($proveedores)): ?>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo htmlspecialchars((string)$proveedor['id_proveedor']); ?>">
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="alert-banner-sub" style="margin-top:8px;">
                    El archivo debe contener hojas por partida (4,01 / 4,02 / 4,03 / 4,04 / 4,07),
                    con la fila de encabezados y las columnas de descripción, unidad de medida y
                    precio unitario en dólares. Tamaño máximo: 50 MB.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCerrarModalImportar2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Importar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalResultadoImportar" class="modal-backdrop" style="display:none; z-index:250;">
    <div class="modal-panel" style="max-width:520px;">
        <div class="modal-header">
            <h3>Resultado de la importación</h3>
            <button id="btnCerrarModalResultado" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Mensaje general -->
            <div id="resultadoImportarMensaje" class="alert-banner alert-info" style="display:none; align-items:flex-start; flex-direction:column; gap:4px;">
                <div class="alert-banner-title"><strong id="resultadoImportarTitulo"></strong></div>
                <div class="alert-banner-sub" id="resultadoImportarSubtitulo"></div>
            </div>

            <!-- Contadores -->
            <div style="display:flex; gap:12px; margin:18px 0 8px; flex-wrap:wrap;">
                <div class="badge badge-success" style="padding:10px 16px; font-size:14px;">
                    Insertados: <strong id="resultadoInsertados" style="font-size:16px; margin-left:4px;">0</strong>
                </div>
                <div class="badge badge-warning" style="padding:10px 16px; font-size:14px;">
                    Omitidos: <strong id="resultadoOmitidos" style="font-size:16px; margin-left:4px;">0</strong>
                </div>
                <div class="badge badge-danger" style="padding:10px 16px; font-size:14px;">
                    Errores: <strong id="resultadoErrores" style="font-size:16px; margin-left:4px;">0</strong>
                </div>
            </div>

            <!-- Hojas procesadas -->
            <div id="resultadoHojasWrap" style="display:none; margin-top:12px;">
                <div class="field-label" style="margin-bottom:4px;">Hojas procesadas</div>
                <div id="resultadoHojas" class="alert-banner-sub"></div>
            </div>

            <!-- Detalles de errores -->
            <div id="resultadoDetallesWrap" style="display:none; margin-top:16px;">
                <div class="field-label" style="margin-bottom:6px; color:var(--danger);">Detalles de errores</div>
                <ul id="resultadoDetalles" style="margin:0; padding-left:20px; font-size:13px; color:var(--text-dark); max-height:180px; overflow-y:auto; list-style:disc;"></ul>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" id="btnCerrarModalResultado2" class="btn btn-success">Cerrar</button>
        </div>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?><!---->
