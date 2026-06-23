$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;

    const dependenciaOptions = {};
    $('#dependenciasList option').each(function() {
        const value = $(this).val();
        const id = $(this).data('id');
        if (value) {
            dependenciaOptions[value] = id;
        }
    });

    $('#register_dependencia_search').on('input change', function() {
        const currentValue = $(this).val();
        if (dependenciaOptions.hasOwnProperty(currentValue)) {
            $('#register_id_dep').val(dependenciaOptions[currentValue]);
        } else {
            $('#register_id_dep').val('');
        }
    });

    const tabla = $('#tablaMain').DataTable({
        ajax: { url: currentUrl, method: 'POST', data: { getAll: true }, dataSrc: '' },
        columns: [
            { data: 'id_responsable' },
            { data: 'nom_rep' },
            { data: 'rol' },
            { data: 'dependencia_actual' },
            { data: 'estado', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_responsable}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                if (Number(d.estado) === 1) {
                    actions += ` <button value="${d.id_responsable}" class="btn btn-warning btn-sm btn-toggle-estado" data-new-state="0" aria-label="Inactivar">🚫</button>`;
                } else {
                    actions += ` <button value="${d.id_responsable}" class="btn btn-success btn-sm btn-toggle-estado" data-new-state="1" aria-label="Activar">✅</button>`;
                }
                return actions;
            }}
        ],
        autoWidth: false,
        language: { url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" }
    });

    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const selectedDepId = $('#register_id_dep').val();
        if (!selectedDepId) {
            alert('Seleccione una dependencia válida de la lista.');
            return;
        }
        const formData = new FormData(e.target);
        formData.append('id_dep', selectedDepId);
        formData.append('registerResponsable', true);
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
                        alert(res.message || 'Responsable registrado exitosamente');
                        if (res.redirect) window.location.href = res.redirect; else window.location.href = '?url=responsable&type=main';
                    } else {
                        alert(res.message || 'Error al registrar. Verifique los datos.');
                    }
                } else {
                    alert('Error al registrar. Verifique los datos.');
                }
            }
        });
    });



    $(document).on('click', '.btn-toggle-estado', function() {
        const id = this.value;
        const newState = $(this).data('new-state');
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: id, toggleEstado: true, newState: newState },
            success: function(res) {
                if (res && typeof res === 'object') {
                    alert(res.message || 'Estado actualizado.');
                } else {
                    alert('Error al cambiar estado.');
                }
                tabla.ajax.reload();
            }
        });
    });

    $(document).on('click', '.btn-modificar', function() {
        const data = tabla.row($(this).closest('tr')).data();
        $('#edit_idItem').val(data.id_responsable);
        $('#edit_nom_rep').val(data.nom_rep);
        $('#edit_id_rol').val(data.id_rol);
        $('#edit_contrasena').val('');
        $('#modalEditar').show();
    });

    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() { $('#modalEditar').hide(); });

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
                    alert(res.message || 'Actualizado');
                    $('#modalEditar').hide();
                    tabla.ajax.reload();
                } else {
                    alert(res.message || 'Error al actualizar.');
                }
            }
        });
    });

    // Asignar
    $(document).on('click', '.btn-asignar', function() {
        const id = this.value;
        $('#assign_id_responsable').val(id);
        $('#assign_fecha_inicio').val(new Date().toISOString().slice(0,10));
        $('#modalAsignar').show();
    });

    $('#btnCerrarModalAsignar, #btnCerrarModalAsignar2').on('click', function() { $('#modalAsignar').hide(); });

    $('#formAsignar').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('assignCargo', true);
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
                        alert(res.message || 'Asignado');
                        $('#modalAsignar').hide();
                        tabla.ajax.reload();
                    } else {
                        alert(res.message || 'Error al asignar');
                    }
                } else {
                    alert('Error al asignar');
                }
            }
        });
    });

});
