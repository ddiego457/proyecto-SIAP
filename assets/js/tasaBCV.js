$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;

    // ─── Año fiscal activo (lo inyecta la vista desde anio_fiscal) ─────
    const anioFiscalActivo = (typeof window.SIAP_ANIO_FISCAL_ACTIVO === 'number')
        ? window.SIAP_ANIO_FISCAL_ACTIVO
        : null;

    // Motivo de error de la fecha de registro (null si es válida):
    // formato AAAA-MM-DD y año exactamente igual al fiscal activo
    // (se rechazan años anteriores y posteriores).
    function motivoFechaTasa(valor) {
        const fecha = String(valor || '').trim();
        if (!fecha) return 'La fecha de registro es obligatoria.';
        if (!ValidacionesSIAP.validarFechaISO(fecha)) return 'Use el formato AAAA-MM-DD.';
        if (anioFiscalActivo === null) return 'No hay un año fiscal activo configurado.';
        const anio = parseInt(fecha.substring(0, 4), 10);
        if (anio < anioFiscalActivo) {
            return 'La fecha no puede ser anterior al año fiscal activo (' + anioFiscalActivo + ').';
        }
        if (anio > anioFiscalActivo) {
            return 'La fecha no puede ser posterior al año fiscal activo (' + anioFiscalActivo + ').';
        }
        return null;
    }

    // Validaciones en el cliente (plugin siapValidar). Los errores de año
    // fiscal se muestran dentro del propio campo (small.field-error).
    $('#formRegistro').siapValidar({
        tasa_bcv_usd: { regla: 'tasa' },
        fecha_reg: {
            test: function(valor) { return motivoFechaTasa(valor) === null; },
            mensaje: function() {
                return motivoFechaTasa($('#formRegistro [name="fecha_reg"]').val()) || 'La fecha no es válida.';
            }
        }
    });

    $('#formEditar').siapValidar({
        tasa_bcv_usd: { regla: 'tasa' },
        fecha_reg: {
            test: function(valor) { return motivoFechaTasa(valor) === null; },
            mensaje: function() {
                return motivoFechaTasa($('#edit_fecha_reg').val()) || 'La fecha no es válida.';
            }
        }
    });

    // Restringir el selector de fechas al año fiscal activo (si existe)
    if (anioFiscalActivo !== null) {
        $('input[name="fecha_reg"]').attr({
            min: anioFiscalActivo + '-01-01',
            max: anioFiscalActivo + '-12-31'
        });
    }

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
            { data: 'estado', render: (d) => {
                return (d == 1)
                    ? '<span class="badge badge-success">Activo</span>'
                    : '<span class="badge badge-danger">Inactivo</span>';
            } },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_tasa}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                if ((d.estado|0) === 1) {
                    actions += ` <button value="${d.id_tasa}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                } else {
                    actions += ` <button value="${d.id_tasa}" class="btn btn-success btn-sm btn-activar" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }
                return actions;
            }}
        ],
        order: [],
        autoWidth: false,
        language: {
            url: "assets/js/DataTables/spanish.json"
        }
    });

    // ─── Modal de registro ─────────────────────────────────────────
    $('#btnNuevoRegistro').on('click', function() {
        $('#formRegistro')[0].reset();
        $('#formRegistro').siapValidar('limpiar');
        $('#modalRegistro').show();
        $('#formRegistro').find('input[name="tasa_bcv_usd"]').focus();
    });

    $('#btnCerrarModalRegistro, #btnCerrarModalRegistro2').on('click', function() {
        $('#modalRegistro').hide();
    });

    $('#modalRegistro').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    // Registrar
    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        // Forzar 2 decimales en el campo antes de enviar
        const input = $(this).find('input[name="tasa_bcv_usd"]');
        if (input && input.length) {
            const v = parseFloat(input.val()) || 0;
            input.val(v.toFixed(2));
        }
        $btn.prop('disabled', true).text('Guardando…');
        const formData = new FormData(e.target);
        formData.append('registerTasa', true);
        $.ajax({
            url: currentUrl, method: 'POST', data: formData,
            dataType: 'json', processData: false, contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).text('✓ Registrar');
                if (res && typeof res === 'object' && res.success) {
                    // 1) Alerta de éxito y, al aceptarla, 2) cierre de la modal
                    alert(res.message || 'Tasa BCV registrada exitosamente');
                    $('#modalRegistro').hide();
                    $('#formRegistro')[0].reset();
                    $('#formRegistro').siapValidar('limpiar');
                    tabla.ajax.reload();
                } else if (res === true) {
                    alert('Tasa BCV registrada exitosamente');
                    $('#modalRegistro').hide();
                    $('#formRegistro')[0].reset();
                    $('#formRegistro').siapValidar('limpiar');
                    tabla.ajax.reload();
                } else {
                    const mensaje = (res && res.message) || 'Error al registrar. Verifique los datos.';
                    alert(mensaje);
                    // Si el motivo es la fecha/año fiscal, marcarlo dentro del campo
                    if (/fecha|año/i.test(mensaje)) {
                        ValidacionesSIAP.marcarError($('#formRegistro [name="fecha_reg"]'), mensaje);
                    }
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('✓ Registrar');
                alert('Error de conexión.');
            }
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
        // No permitir modificar tasas de un año fiscal que no sea el activo
        const anioTasa = parseInt(String(data.fecha_reg || '').substring(0, 4), 10);
        if (anioFiscalActivo !== null && !Number.isNaN(anioTasa) && anioTasa !== anioFiscalActivo) {
            alert('No se puede modificar una tasa de un año fiscal que no está activo (año activo: ' + anioFiscalActivo + ').');
            return;
        }
        $('#edit_id_tasa').val(data.id_tasa);
        // Asegurar visualización con 2 decimales
        $('#edit_tasa_bcv_usd').val(Number(data.tasa_bcv_usd || 0).toFixed(2));
        $('#edit_fecha_reg').val(formatDateForInput(data.fecha_reg));
        $('#formEditar').siapValidar('limpiar');
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
                if (res === true || (res && res.success)) {
                    alert((res && res.message) || 'Registro actualizado exitosamente');
                    $('#modalEditar').hide();
                    tabla.ajax.reload();
                } else {
                    const mensaje = (res && res.message) || 'Error al actualizar.';
                    alert(mensaje);
                    // Si el motivo es la fecha/año fiscal, marcarlo dentro del campo
                    if (/fecha|año/i.test(mensaje)) {
                        ValidacionesSIAP.marcarError($('#edit_fecha_reg'), mensaje);
                    }
                }
            }
        });
    });

});