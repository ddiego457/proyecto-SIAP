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
            { data: 'id_dep' },
            { data: 'nombre_dep' },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_dep}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                if ((d.estado|0) === 1) {
                    actions += ` <button value="${d.id_dep}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                } else {
                    actions += ` <button value="${d.id_dep}" class="btn btn-success btn-sm btn-activar" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }
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
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text('Guardando…');
        const formData = new FormData(e.target);
        formData.append('registerDependencia', true);
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
                        alert(res.message || 'Registro exitoso');
                        if (res.redirect) {
                            window.location.href = res.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert(res.message || 'Error al registrar. Verifique los datos.');
                        $btn.prop('disabled', false).text('✓ Registrar');
                    }
                } else {
                    alert('Error al registrar. Verifique los datos.');
                    $btn.prop('disabled', false).text('✓ Registrar');
                }
            },
            error: function() {
                alert('Error de conexión.');
                $btn.prop('disabled', false).text('✓ Registrar');
            }
        });
    });

    // Editar
    $(document).on('click', '.btn-modificar', function() {
        const row = tabla.row($(this).closest('tr'));
        const data = row.data();
        if (!data) {
            alert('No se pudo obtener la fila para editar. Intente recargar la página.');
            return;
        }
        $('#edit_idItem').val(data.id_dep);
        $('#edit_nombre_dep').val(data.nombre_dep);
        $('#edit_estado').val(data.estado);
        $('#modalEditar').show();
    });

    // Inactivar
    $(document).on('click', '.btn-inactivar', function() {
        if (!confirm('¿Inhabilitar esta dependencia?')) return;
        const idItem = this.value;
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: idItem, inactivateItem: true },
            success: function(res) {
                if (res && typeof res === 'object') {
                    alert(res.message || 'Registro inhabilitado.');
                } else if (res === true) {
                    alert('Registro inhabilitado.');
                } else {
                    alert('Error al inhabilitar.');
                }
                tabla.ajax.reload();
            }
        });
    });

    // Activar
    $(document).on('click', '.btn-activar', function() {
        if (!confirm('¿Activar esta dependencia?')) return;
        const idItem = this.value;
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: idItem, activateItem: true },
            success: function(res) {
                if (res && typeof res === 'object') {
                    alert(res.message || 'Registro activado.');
                } else if (res === true) {
                    alert('Registro activado.');
                } else {
                    alert('Error al activar.');
                }
                tabla.ajax.reload();
            }
        });
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
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                if (res && typeof res === 'object' && res.success) {
                    alert(res.message || 'Registro actualizado exitosamente');
                    $('#modalEditar').hide();
                    tabla.ajax.reload();
                } else {
                    alert(res.message || 'Error al actualizar.');
                }
            }
        });
    });

});