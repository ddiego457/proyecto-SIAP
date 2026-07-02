$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;

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
                let actions = `<button value="${d.id_proveedor}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                if ((d.estado|0) === 1) {
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-sm btn-secondary btn-contactos" aria-label="Contactos">☎️</button>`;
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar">🚫</button>`;
                } else {
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-sm btn-secondary btn-contactos" aria-label="Contactos">☎️</button>`;
                    actions += ` <button value="${d.id_proveedor}" class="btn btn-success btn-sm btn-activar" aria-label="Activar">✅</button>`;
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
                        alert(res.message || 'Proveedor registrado exitosamente');
                        if (res.redirect) window.location.href = res.redirect; else window.location.href = '?url=proveedor&type=main';
                    } else {
                        alert(res.message || 'Error al registrar. Verifique los datos.');
                    }
                } else if (res == true) {
                    alert('Proveedor registrado exitosamente');
                    window.location.href = '?url=proveedor&type=main';
                } else {
                    alert('Error al registrar. Verifique los datos.');
                }
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
                    let actions = `<button value="${d.id_telf}" class="btn btn-sm btn-modificar-contacto text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                    if ((d.estado|0) === 1) {
                        actions += ` <button value="${d.id_telf}" class="btn btn-warning btn-sm btn-inactivar-contacto" aria-label="Inactivar">🚫</button>`;
                    } else {
                        actions += ` <button value="${d.id_telf}" class="btn btn-success btn-sm btn-activar-contacto" aria-label="Activar">✅</button>`;
                    }
                    actions += ` <button value="${d.id_telf}" class="btn btn-danger btn-sm btn-eliminar-contacto" aria-label="Eliminar">🗑️</button>`;
                    return actions;
                }}
            ],
            autoWidth: false,
            language: { url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" }
        });

        $('#btnAgregarContacto').on('click', function() {
            const telefono = $('#contact_telefono').val().trim();
            if (!telefono) {
                alert('Ingrese un teléfono válido.');
                return;
            }
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
