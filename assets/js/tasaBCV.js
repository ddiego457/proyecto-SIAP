$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;

    function formatDateForDisplay(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            // Try manually parse YYYY-MM-DD HH:MM:SS
            const parts = value.split(' ')[0].split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return value;
        }
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function formatDateForInput(value) {
        if (!value) return '';
        const date = new Date(value);
        if (!Number.isNaN(date.getTime())) {
            return date.toISOString().slice(0, 10);
        }
        const parts = value.split(' ')[0].split('-');
        if (parts.length === 3) {
            return `${parts[0]}-${parts[1]}-${parts[2]}`;
        }
        return value;
    }

    const tabla = $('#tablaMain').DataTable({
        ajax: {
            url: currentUrl,
            method: 'POST',
            data: { getAll: true },
            dataSrc: ''
        },
        columns: [
            { data: 'id_tasa' },
            { data: 'tasa_bcv_usd', render: (data) => {
                const n = Number(data || 0);
                return n.toFixed(2);
            } },
            { data: 'fecha_reg', render: (data) => formatDateForDisplay(data) },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_tasa}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;" aria-label="Editar">✏️</button>`;
                if ((d.estado|0) === 1) {
                    actions += ` <button value="${d.id_tasa}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar">🚫</button>`;
                } else {
                    actions += ` <button value="${d.id_tasa}" class="btn btn-success btn-sm btn-activar" aria-label="Activar">✅</button>`;
                }
                actions += ` <button value="${d.id_tasa}" class="btn btn-danger btn-sm btn-eliminar" aria-label="Eliminar">🗑️</button>`;
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
        // Forzar 2 decimales en el campo antes de enviar
        const input = $(this).find('input[name="tasa_bcv_usd"]');
        if (input && input.length) {
            const v = parseFloat(input.val()) || 0;
            input.val(v.toFixed(2));
        }
        const formData = new FormData(e.target);
        formData.append('registerTasa', true);
        $.ajax({
            url: currentUrl, method: 'POST', data: formData,
            dataType: 'json', processData: false, contentType: false,
            success: function(res) {
                if (res && typeof res === 'object') {
                    if (res.success) {
                        alert(res.message || 'Registro exitoso');
                        if (res.redirect) window.location.href = res.redirect; else window.location.reload();
                    } else {
                        alert(res.message || 'Error al registrar. Verifique los datos.');
                    }
                } else if (res == true) {
                    alert('Registro exitoso');
                    window.location.reload();
                } else {
                    alert('Error al registrar. Verifique los datos.');
                }
            }
        });
    });

    // Eliminar
    $(document).on('click', '.btn-eliminar', function() {
        if (!confirm('¿Está seguro de eliminar este registro?')) return;
        const idTasa = this.value;
        $.ajax({
            url: currentUrl, method: 'POST', dataType: 'json',
            data: { id_tasa: idTasa, deleteItem: true },
            success: function(res) { alert(res); tabla.ajax.reload(); }
        });
    });

    // Inactivar
    $(document).on('click', '.btn-inactivar', function() {
        if (!confirm('¿Inhabilitar esta tasa?')) return;
        const idTasa = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json',
            data: { id_tasa: idTasa, inactivateItem: true },
            success: function(res) { if (res == true) { alert('Tasa inhabilitada'); } else { alert('Error'); } tabla.ajax.reload(); }
        });
    });

    // Activar
    $(document).on('click', '.btn-activar', function() {
        if (!confirm('¿Activar esta tasa y desactivar otras?')) return;
        const idTasa = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json',
            data: { id_tasa: idTasa, activateItem: true },
            success: function(res) { if (res == true) { alert('Tasa activada'); } else { alert('Error'); } tabla.ajax.reload(); }
        });
    });

    // Abrir modal edición
    $(document).on('click', '.btn-modificar', function() {
        const data = tabla.row($(this).closest('tr')).data();
        $('#edit_id_tasa').val(data.id_tasa);
        // Asegurar visualización con 2 decimales
        $('#edit_tasa_bcv_usd').val(Number(data.tasa_bcv_usd || 0).toFixed(2));
        $('#edit_fecha_reg').val(formatDateForInput(data.fecha_reg));
        $('#modalEditar').show();
    });

    // Cerrar modal
    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
        $('#modalEditar').hide();
    });

    // Guardar edición
    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        // Forzar 2 decimales antes de enviar
        const input = $(this).find('input[name="tasa_bcv_usd"]');
        if (input && input.length) {
            const v = parseFloat(input.val()) || 0;
            input.val(v.toFixed(2));
        }
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