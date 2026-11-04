$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;
    // Endpoint dedicado a los documentos PDF del proveedor
    const pdfUrl = currentUrl.replace(/([?].*)?$/, '') + '?url=proveedor&type=pdf';
    // Límite de tamaño del PDF en el cliente (10 MB)
    const maximoBytesPdf = 10 * 1024 * 1024;

    // Validaciones en el cliente (plugin siapValidar).
    $('#formRegistro').siapValidar({
        nombre: { regla: 'nombreProveedor' },
        descripcion: { regla: 'descripcionProveedor' }
    });

    $('#formEditar').siapValidar({
        nombre: { regla: 'nombreProveedor' },
        descripcion: { regla: 'descripcionProveedor' },
        estado: { regla: 'requerido', mensaje: 'Seleccione un estado.' }
    });

    $('#formAgregarContacto').siapValidar({
        telefono: { regla: 'telefono' }
    });

    $('#formEditarContacto').siapValidar({
        telefono: { regla: 'telefono' },
        estado: { regla: 'requerido', mensaje: 'Seleccione un estado.' }
    });

    const tabla = $('#tablaMain').DataTable({
        ajax: {
            url: currentUrl,
            method: 'POST',
            data: { getAll: true },
            dataSrc: ''
        },
        columns: [
            { data: 'id_proveedor' },
            { data: 'nombre' },
            { data: 'descripcion' },
            { data: 'estado', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_proveedor}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                actions += ` <button value="${d.id_proveedor}" class="btn btn-sm btn-secondary btn-pdf" aria-label="Documento PDF" title="Documento PDF"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i></button>`;
                if ((d.estado|0) === 1) {
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-sm btn-secondary btn-contactos" aria-label="Contactos" title="Contactos"><i class="fa-solid fa-phone" aria-hidden="true"></i></button>`;
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                } else {
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-sm btn-secondary btn-contactos" aria-label="Contactos" title="Contactos"><i class="fa-solid fa-phone" aria-hidden="true"></i></button>`;
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-success btn-sm btn-activar" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }

                return actions;
            }}
        ],
        autoWidth: false,
        language: {
            url: "assets/js/DataTables/spanish.json"
        }
    });

    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text('Guardando…');
        const formData = new FormData(e.target);
        formData.append('registerProveedor', true);
        $.ajax({
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                if (res && typeof res === 'object') {
                    if (res.success) {
                        // Permanece en la vista de registro para dar de alta otro proveedor
                        siapToast(res.message || 'Proveedor registrado exitosamente', 'success');
                        siapResetFormulario('#formRegistro', 'input[name="nombre"]');
                    } else {
                        siapToast(res.message || 'Error al registrar. Verifique los datos.', 'error');
                    }
                } else if (res == true) {
                    siapToast('Proveedor registrado exitosamente', 'success');
                    siapResetFormulario('#formRegistro', 'input[name="nombre"]');
                } else {
                    siapToast('Error al registrar. Verifique los datos.', 'error');
                }
                $btn.prop('disabled', false).text('✓ Registrar');
            },
            error: function() {
                siapToast('Error de conexión.', 'error');
                $btn.prop('disabled', false).text('✓ Registrar');
            }
        });
    });



    $(document).on('click', '.btn-inactivar', function() {
        if (!confirm('¿Inhabilitar este proveedor?')) return;
        const idItem = this.value;
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: idItem, inactivateItem: true },
            success: function(res) {
                alert(res ? 'Proveedor inhabilitado' : 'Error al inhabilitar');
                tabla.ajax.reload();
            }
        });
    });

    $(document).on('click', '.btn-activar', function() {
        if (!confirm('¿Activar este proveedor?')) return;
        const idItem = this.value;
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: idItem, activateItem: true },
            success: function(res) {
                alert(res ? 'Proveedor activado' : 'Error al activar');
                tabla.ajax.reload();
            }
        });
    });

    $(document).on('click', '.btn-modificar', function() {
        const data = tabla.row($(this).closest('tr')).data();
        $('#edit_idItem').val(data.id_proveedor);
        $('#edit_nombre').val(data.nombre);
        $('#edit_descripcion').val(data.descripcion);
        $('#edit_estado').val(data.estado);
        $('#formEditar').siapValidar('limpiar');
        $('#modalEditar').show();
    });

    $(document).on('click', '.btn-contactos', function() {
        const idProveedor = this.value;
        window.location.href = currentUrl.replace(/([?].*)?$/, '') + '?url=proveedor&type=contacts&idProveedor=' + idProveedor;
    });

    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
        $('#modalEditar').hide();
    });

    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('updateItem', true);
        $.ajax({
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                if (res == true) {
                    alert('Proveedor actualizado exitosamente');
                    $('#modalEditar').hide();
                    tabla.ajax.reload();
                } else {
                    alert('Error al actualizar.');
                }
            }
        });
    });

    // ─── Documento PDF del proveedor ─────────────────────────────────
    $(document).on('click', '.btn-pdf', function() {
        const data = tabla.row($(this).closest('tr')).data();
        if (!data) return;

        $('#pdf_idProveedor').val(data.id_proveedor);
        $('#pdf_proveedorNombre').val(data.nombre);
        $('#pdf_archivo').val('');

        if (data.archivo_pdf) {
            const nombre = data.nombre_pdf || data.archivo_pdf;
            $('#pdfActualLink').attr('href', 'assets/uploads/productos/' + encodeURI(data.archivo_pdf));
            $('#pdfActualNombre').text(nombre);
            $('#pdfActualWrap').show();
            $('#pdfSinDocumento').hide();
        } else {
            $('#pdfActualWrap').hide();
            $('#pdfSinDocumento').show();
        }

        $('#btnGuardarPdf').prop('disabled', false).text('Guardar PDF');
        $('#modalPdf').show();
    });

    $('#btnCerrarModalPdf, #btnCerrarModalPdf2').on('click', function() {
        $('#modalPdf').hide();
    });

    $('#modalPdf').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') $('#modalPdf').hide();
    });

    $('#formSubirPdf').on('submit', function(e) {
        e.preventDefault();

        const archivo = $('#pdf_archivo')[0].files[0];
        if (!archivo) {
            siapToast('Seleccione un archivo PDF.', 'error');
            return;
        }
        if (!/\.pdf$/i.test(archivo.name)) {
            siapToast('Solo se permiten archivos con extensión .pdf.', 'error');
            return;
        }
        if (archivo.size > maximoBytesPdf) {
            siapToast('El archivo no debe superar los 10 MB.', 'error');
            return;
        }

        const $btn = $('#btnGuardarPdf');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text('Guardando…');

        const formData = new FormData(e.target);
        formData.append('subirPdf', true);

        $.ajax({
            url: pdfUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).text('Guardar PDF');
                if (res && res.success) {
                    siapToast(res.message || 'PDF guardado correctamente.', 'success');
                    $('#modalPdf').hide();
                    tabla.ajax.reload();
                } else {
                    siapToast((res && res.message) || 'Error al guardar el PDF.', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Guardar PDF');
                siapToast('Error de conexión.', 'error');
            }
        });
    });

    if ($('#tablaContactos').length) {
        const idProveedor = Number($('#contact_id_proveedor').val());
        const contactosTabla = $('#tablaContactos').DataTable({
            ajax: {
                url: currentUrl,
                method: 'POST',
                data: { getContacts: true, idProveedor: idProveedor },
                dataSrc: ''
            },
            columns: [
                { data: 'id_telf' },
                { data: 'telefono' },
                { data: 'estado', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
                { data: null, render: (d) => {
                    let actions = `<button value="${d.id_telf}" class="btn btn-sm btn-edit btn-modificar-contacto" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                    if ((d.estado|0) === 1) {
                        actions += ` <button value="${d.id_telf}" class="btn btn-warning btn-sm btn-inactivar-contacto" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                    } else {
                        actions += ` <button value="${d.id_telf}" class="btn btn-success btn-sm btn-activar-contacto" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                    }
                    actions += ` <button value="${d.id_telf}" class="btn btn-danger btn-sm btn-eliminar-contacto" aria-label="Eliminar" title="Eliminar"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>`;
                    return actions;
                }}
            ],
            autoWidth: false,
            language: { url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" }
        });

        // Validación de teléfono delegada a siapValidar (focusout + bloqueo de submit).
        $('#formAgregarContacto').on('submit', function(e) {
            e.preventDefault();
            const telefono = $('#contact_telefono').val().trim();
            $.ajax({
                url: currentUrl,
                method: 'POST',
                dataType: 'json',
                data: { addContact: true, idProveedor: idProveedor, telefono: telefono },
                success: function(res) {
                    if (res == true) {
                        alert('Contacto agregado.');
                        $('#contact_telefono').val('');
                        contactosTabla.ajax.reload();
                    } else {
                        alert('Error al agregar contacto.');
                    }
                }
            });
        });

        $(document).on('click', '.btn-modificar-contacto', function() {
            const idContacto = this.value;
            const row = contactosTabla.row($(this).closest('tr')).data();
            $('#edit_idContacto').val(idContacto);
            $('#edit_telefono').val(row.telefono);
            $('#edit_estadoContacto').val(row.estado);
            $('#formEditarContacto').siapValidar('limpiar');
            $('#modalEditarContacto').show();
        });

        $(document).on('click', '.btn-eliminar-contacto', function() {
            if (!confirm('¿Está seguro de eliminar este contacto?')) return;
            const idContacto = this.value;
            $.ajax({
                url: currentUrl,
                method: 'POST',
                dataType: 'json',
                data: { deleteContact: true, idContacto: idContacto },
                success: function(res) {
                    alert(res ? 'Contacto eliminado' : 'Error al eliminar');
                    contactosTabla.ajax.reload();
                }
            });
        });

        $(document).on('click', '.btn-inactivar-contacto', function() {
            if (!confirm('¿Inactivar este contacto?')) return;
            const idContacto = this.value;
            $.ajax({
                url: currentUrl,
                method: 'POST',
                dataType: 'json',
                data: { inactivateContact: true, idContacto: idContacto },
                success: function(res) {
                    alert(res ? 'Contacto inactivado' : 'Error al inactivar');
                    contactosTabla.ajax.reload();
                }
            });
        });

        $(document).on('click', '.btn-activar-contacto', function() {
            if (!confirm('¿Activar este contacto?')) return;
            const idContacto = this.value;
            $.ajax({
                url: currentUrl,
                method: 'POST',
                dataType: 'json',
                data: { activateContact: true, idContacto: idContacto },
                success: function(res) {
                    alert(res ? 'Contacto activado' : 'Error al activar');
                    contactosTabla.ajax.reload();
                }
            });
        });

        $('#btnCerrarModalContacto, #btnCerrarModalContacto2').on('click', function() {
            $('#modalEditarContacto').hide();
        });

        $('#formEditarContacto').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('updateContact', true);
            $.ajax({
                url: currentUrl,
                method: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res == true) {
                        alert('Contacto actualizado.');
                        $('#modalEditarContacto').hide();
                        contactosTabla.ajax.reload();
                    } else {
                        alert('Error al actualizar contacto.');
                    }
                }
            });
        });
    }
});
