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
                let actions = `<button value="${d.id_dep}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                if ((d.estado|0) === 1) {
                    actions += ` <button value="${d.id_dep}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar">🚫</button>`;
                } else {
                    actions += ` <button value="${d.id_dep}" class="btn btn-success btn-sm btn-activar" aria-label="Activar">✅</button>`;
                }
                return actions;
            }}
        ],
        autoWidth: false,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });

    // Registrar
    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
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
                    }
                } else {
                    alert('Error al registrar. Verifique los datos.');
                }
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