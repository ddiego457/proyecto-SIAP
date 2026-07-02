$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;

    const tabla = $('#tablaMain').DataTable({
        ajax: {
            // Usar el mismo controlador actual (no un archivo ajax_anio_fiscal.php)
            url: currentUrl,
            method: 'POST',
            data: { getAll: true },
            dataSrc: ''
        },
        columns: [
            { data: 'id_anioFis' },
            { data: 'anio_fiscal' },
            { data: 'activo', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_anioFis}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                if (Number(d.activo) === 1) {
                    actions += ` <button value="${d.id_anioFis}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar">🚫</button>`;
                } else {
                    actions += ` <button value="${d.id_anioFis}" class="btn btn-success btn-sm btn-activar" aria-label="Activar">✅</button>`;
                }
                // Eliminación removida: delete e inactivate hacían lo mismo
                return actions;
            }}
        ],
        autoWidth: false,
        language: {
            url: "assets/js/DataTables/spanish.json"
        }
    });

    // Registrar
    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('registerAnioFiscal', true);
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
                            alert(res.message || 'Año fiscal registrado');
                            if (res.redirect) window.location.href = res.redirect; else window.location.href = '?url=anioFiscal&type=list';
                        } else {
                            alert(res.message || 'Error al registrar. Verifique los datos.');
                        }
                    } else if (res == true) {
                        alert('Registro exitoso');
                        window.location.href = '?url=anioFiscal&type=list';
                    } else {
                        alert('Error al registrar. Verifique los datos.');
                    }
                }
            });
    });



    // Inactivar
    $(document).on('click', '.btn-inactivar', function() {
        if (!confirm('¿Inhabilitar este año fiscal?')) return;
        const idItem = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json', data: { idItem: idItem, inactivateItem: true },
            success: function(res) { if (res == true) { alert('Año inhabilitado'); } else { alert('Error'); } tabla.ajax.reload(); }
        });
    });

    // Activar
    $(document).on('click', '.btn-activar', function() {
        if (!confirm('¿Activar este año fiscal y desactivar otros?')) return;
        const idItem = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json', data: { idItem: idItem, activateItem: true },
            success: function(res) { if (res == true) { alert('Año activado'); } else { alert('Error'); } tabla.ajax.reload(); }
        });
    });

    // Abrir modal edición
    $(document).on('click', '.btn-modificar', function() {
        const data = tabla.row($(this).closest('tr')).data();
        $('#edit_idItem').val(data.id_anioFis);
        $('#edit_anio_fiscal').val(data.anio_fiscal);
        $('#edit_estado').prop('checked', data.estado == 1);
        $('#modalEditar').show();
    });

    // Cerrar modal
    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
        $('#modalEditar').hide();
    });

    // Guardar edición
    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('updateItem', true);
        $.ajax({
            url: currentUrl, method: 'POST', data: formData,
            dataType: 'json', processData: false, contentType: false,
            success: function(res) {
                if (res == true) {
                    alert('Registro actualizado exitosamente');
                    $('#modalEditar').hide();
                    tabla.ajax.reload();
                } else {
                    alert('Error al actualizar.');
                }
            }
        });
    });

});