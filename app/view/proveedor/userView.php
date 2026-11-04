<?php
$pageTitle = "Proveedores";
$jsFile    = "proveedor.js";
include_once 'app/view/layout/head.php';
?>

<div class="topbar">
    <div class="topbar-title">Proveedores</div>
    <?php include_once 'app/view/layout/userMenu.php'; ?>
</div>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Proveedores</span>
            <div class="card-header-actions">
                <a href="?url=proveedor&type=register" class="btn btn-success btn-sm">&#43; Registrar</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table id="tablaMain" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalEditar" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar Proveedor</h3>
            <button id="btnCerrarModal" class="modal-close">&times;</button>
        </div>
        <form id="formEditar">
            <input type="hidden" id="edit_idItem" name="idItem">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Nombre</label>
                    <input type="text" id="edit_nombre" name="nombre" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <textarea id="edit_descripcion" name="descripcion" class="field-input" rows="3"></textarea>
                </div>
                <div class="field-group">
                    <label class="field-label">Estado</label>
                    <select id="edit_estado" name="estado" class="field-input field-select" required>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModal2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<div id="modalPdf" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Documento PDF del proveedor</h3>
            <button id="btnCerrarModalPdf" class="modal-close">&times;</button>
        </div>
        <form id="formSubirPdf" enctype="multipart/form-data">
            <input type="hidden" id="pdf_idProveedor" name="id_proveedor">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Proveedor</label>
                    <input type="text" id="pdf_proveedorNombre" class="field-input" readonly style="background:#f8fafc;">
                </div>

                <div class="field-group">
                    <label class="field-label">Documento actual</label>
                    <div id="pdfActualWrap" style="display:none;">
                        <a id="pdfActualLink" href="#" target="_blank" rel="noopener"
                           class="btn btn-outline btn-sm" style="text-decoration:none;">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Ver PDF
                        </a>
                        <div id="pdfActualNombre" class="alert-banner-sub" style="margin-top:6px; word-break:break-all;"></div>
                    </div>
                    <div id="pdfSinDocumento" class="alert-banner-sub">Este proveedor aún no tiene un PDF guardado.</div>
                </div>

                <div class="field-group">
                    <label class="field-label">Subir nuevo PDF</label>
                    <input type="file" id="pdf_archivo" name="archivoPdf" class="field-input" accept=".pdf,application/pdf">
                    <div class="alert-banner-sub" style="margin-top:6px;">Formato .pdf, máximo 10 MB. Al subir uno nuevo se reemplaza el actual.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModalPdf2" class="btn btn-outline">Cancelar</button>
                <button type="submit" id="btnGuardarPdf" class="btn btn-success">Guardar PDF</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
