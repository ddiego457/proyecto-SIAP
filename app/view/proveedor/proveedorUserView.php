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
                <button type="button" id="btnNuevoRegistro" class="btn btn-success btn-sm">Registrar</button>
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

<!-- MODAL REGISTRO -->
<div id="modalRegistro" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Nuevo Proveedor</h3>
            <button id="btnCerrarModalRegistro" class="modal-close">&times;</button>
        </div>
        <form id="formRegistro">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Nombre del Proveedor</label>
                    <input type="text" name="nombre" class="field-input" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <textarea name="descripcion" class="field-input" rows="3" required></textarea>
                </div>
                <div class="field-group">
                    <label class="field-label">Documento PDF (opcional)</label>
                    <input type="file" name="archivoPdf" id="reg_archivoPdf" class="field-input"
                           accept=".pdf,application/pdf">
                    <div class="alert-banner-sub" style="margin-top:6px;">
                        Formato .pdf, m&aacute;ximo 10 MB. Si lo deja vac&iacute;o, puede subirlo
                        despu&eacute;s desde el bot&oacute;n de la tabla.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModalRegistro2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">&#10003; Registrar</button>
            </div>
        </form>
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
                    <textarea id="edit_descripcion" name="descripcion" class="field-input" rows="3" required></textarea>
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

<!-- MODAL CONTACTOS DEL PROVEEDOR -->
<div id="modalContactos" class="modal-backdrop" style="display:none;">
    <div class="modal-panel" style="max-width:720px;">
        <div class="modal-header">
            <h3>Contactos del proveedor</h3>
            <button id="btnCerrarModalContactos" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="field-group">
                <label class="field-label">Proveedor</label>
                <input type="text" id="contact_proveedorNombre" class="field-input" readonly style="background:#f8fafc;">
                <input type="hidden" id="contact_id_proveedor" value="">
            </div>
            <form id="formAgregarContacto">
                <div class="field-group">
                    <label class="field-label">Agregar teléfono</label>
                    <input type="text" id="contact_telefono" name="telefono" class="field-input" placeholder="Ej. 0414-1234567">
                    <button type="submit" id="btnAgregarContacto" class="btn btn-success btn-sm" style="margin-top:10px;">Agregar</button>
                </div>
            </form>
            <div class="table-wrapper" style="margin-top:20px;">
                <table id="tablaContactos" class="siap-table" style="width:100%">
                    <thead><tr><th>ID</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" id="btnCerrarModalContactos2" class="btn btn-outline">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL EDITAR CONTACTO -->
<div id="modalEditarContacto" class="modal-backdrop" style="display:none;">
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Editar contacto</h3>
            <button id="btnCerrarModalContacto" class="modal-close">&times;</button>
        </div>
        <form id="formEditarContacto">
            <input type="hidden" id="edit_idContacto" name="idContacto">
            <div class="modal-body">
                <div class="field-group">
                    <label class="field-label">Teléfono</label>
                    <input type="text" id="edit_telefono" name="telefono" class="field-input" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCerrarModalContacto2" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'app/view/layout/foot.php'; ?>
