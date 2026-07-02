$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;
    console.log('periodo.js loaded', currentUrl);

    const tabla = $('#tablaMain').DataTable({
        ajax: {
            url: currentUrl,
            method: 'POST',
            data: { getAll: true },
            dataSrc: function(json) {
                console.log('DataTables getAll response:', json);
                if (Array.isArray(json)) {
                    return json;
                }
                if (json && Array.isArray(json.data)) {
                    return json.data;
                }
                return [];
            }
        },
        columns: [
            { data: 'id_periodo' },
            // compatibilidad con distintos alias de backend
            { data: 'id_aniof', defaultContent: '—' },
            { data: 'per_ini', defaultContent: '—' },
            { data: 'per_fin', defaultContent: '—' },
            { data: 'activo', defaultContent: 0, render: (d) => (d == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>') },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_periodo}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                if ((d.activo|0) === 1) {
                    actions += ` <button value="${d.id_periodo}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar">🚫</button>`;
                } else {
                    actions += ` <button value="${d.id_periodo}" class="btn btn-success btn-sm btn-activar" aria-label="Activar">✅</button>`;
                }
                return actions;
            }}
        ],
        autoWidth: false,
        language: {
            url: "assets/js/DataTables/spanish.json"
        }
    });

    function validatePeriodoDates(start, end) {
        return start && end && start <= end;
    }

    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const start = $('#fecha_inicio').val();
        const end = $('#fecha_fin').val();
        if (!validatePeriodoDates(start, end)) {
            alert('La fecha de inicio no puede ser posterior a la fecha fin.');
            return;
        }
        const formData = new FormData(e.target);
        formData.append('registerPeriodo', true);
        console.log('Registrar periodo:', Array.from(formData.entries()));
        $.ajax({
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                console.log('Respuesta registrar periodo:', res);
                if (res && res.success) {
                    alert(res.message || 'Registro exitoso');
                    window.location.href = '?url=periodo&type=main';
                } else {
                    alert(res.message || 'Error al registrar. Verifique los datos.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error en la petición de registro. Revise la consola para más detalles.');
                console.error('AJAX register error:', status, error, xhr.responseText);
            }
        });
    });



    // Inactivar
    $(document).on('click', '.btn-inactivar', function() {
        if (!confirm('¿Inhabilitar este periodo?')) return;
        const idItem = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json', data: { idItem: idItem, inactivateItem: true },
            success: function(res) { alert(res.message || 'Periodo inhabilitado'); tabla.ajax.reload(); }
        });
    });

    // Activar
    $(document).on('click', '.btn-activar', function() {
        if (!confirm('¿Activar este periodo y desactivar otros del mismo año?')) return;
        const idItem = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json', data: { idItem: idItem, activateItem: true },
            success: function(res) { alert(res.message || 'Periodo activado'); tabla.ajax.reload(); }
        });
    });

    $(document).on('click', '.btn-modificar', function() {
        const data = tabla.row($(this).closest('tr')).data();
        $('#edit_idItem').val(data.id_periodo);
        $('#edit_fecha_inicio').val(data.fecha_inicio);
        $('#edit_fecha_fin').val(data.fecha_fin);
        $('#edit_anio_fiscal_id').val(data.anio_fiscal_id ?? '');
        $('#modalEditar').show();
    });

    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
        $('#modalEditar').hide();
    });

    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        const start = $('#edit_fecha_inicio').val();
        const end = $('#edit_fecha_fin').val();
        if (!validatePeriodoDates(start, end)) {
            alert('La fecha de inicio no puede ser posterior a la fecha fin.');
            return;
        }
        const formData = new FormData(e.target);
        formData.append('updateItem', true);
        console.log('Actualizar periodo:', Array.from(formData.entries()));
        $.ajax({
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                console.log('Respuesta actualizar periodo:', res);
                if (res && res.success) {
                    alert(res.message || 'Registro actualizado exitosamente');
                    $('#modalEditar').hide();
                    tabla.ajax.reload();
                } else {
                    alert(res.message || 'Error al actualizar.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error en la petición de actualización. Revise la consola para más detalles.');
                console.error('AJAX update error:', status, error, xhr.responseText);
            }
        });
    });

});

