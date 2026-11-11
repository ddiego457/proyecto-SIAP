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
            { data: 'origen', render: (d) => {
                return (d === 'automatica')
                    ? '<span class="badge badge-blue">Automática</span>'
                    : '<span class="badge badge-gray">Manual</span>';
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

    // ─── API dolarapi.com: valor de la tasa del día ─────────────────
    // Valor consultado a la API (fuente oficial/BCV). null mientras no
    // haya consulta exitosa → el registro queda en 'manual'.
    let tasaApiFormateada = null;

    // Fecha de hoy en formato local AAAA-MM-DD (sin el desfase UTC de toISOString)
    function fechaHoyISO() {
        const d = new Date();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + dia;
    }

    // Consulta la tasa BCV del día – API dolarapi.com (enzonotario/dolarapi.com).
    // onValor recibe el número redondeado a 2 decimales si hay resultado;
    // notificar muestra un alert si la consulta falla (para el botón manual).
    function consultarTasaBCV(onValor, notificar) {
        tasaApiFormateada = null;
        return $.ajax({
            url: 'https://ve.dolarapi.com/v1/dolares/oficial',
            method: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(res) {
                const bruto = Number(res && (res.promedio ?? res.venta ?? res.compra));
                if (isFinite(bruto) && bruto > 0) {
                    tasaApiFormateada = Number(bruto.toFixed(2));
                    if (typeof onValor === 'function') onValor(tasaApiFormateada);
                } else if (notificar) {
                    alert('La API no devolvió un valor de tasa válido. Intente de nuevo.');
                }
            },
            error: function(xhr, status, error) {
                console.error('No se pudo consultar la tasa BCV (dolarapi.com):', status, error);
                if (notificar) {
                    alert('No se pudo consultar la tasa de la API. Verifique su conexión e intente de nuevo.');
                }
            }
        });
    }

    // Botón "Usar tasa de la API": consulta el valor del día y lo coloca en
    // el campo indicado (para cuando el usuario cambió el valor y quiere
    // volver al de la API). Se deshabilita durante la consulta.
    function pedirTasaApi($boton, $destino) {
        const contenido = $boton.html();
        $boton.prop('disabled', true)
              .html('<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Consultando…');
        consultarTasaBCV(function(valor) {
            $destino.val(valor.toFixed(2));
        }, true).always(function() {
            $boton.prop('disabled', false).html(contenido);
        });
    }

    // Registro: recarga la tasa de la API en el campo si el usuario la cambió
    $('#btnTasaApiRegistro').on('click', function() {
        pedirTasaApi($(this), $('#formRegistro [name="tasa_bcv_usd"]'));
    });

    // Edición: mismo botón para cargar la tasa del día en el campo
    $('#btnTasaApiEdicion').on('click', function() {
        pedirTasaApi($(this), $('#edit_tasa_bcv_usd'));
    });

    $('#btnNuevoRegistro').on('click', function() {
        $('#formRegistro')[0].reset();
        $('#formRegistro').siapValidar('limpiar');
        // Fecha del día automáticamente en el campo de fecha
        $('#formRegistro [name="fecha_reg"]').val(fechaHoyISO());
        // Tasa del día desde la API (si falla, se registra como manual);
        // el botón "Usar tasa de la API" permite recargarla después.
        consultarTasaBCV(function(valor) {
            $('#formRegistro [name="tasa_bcv_usd"]').val(valor.toFixed(2));
        });
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
        // Origen del valor: 'automatica' si coincide con el valor de la API
        // (tolerancia 0.005 por el redondeo a 2 decimales), si no 'manual'.
        const valorIngresado = parseFloat(input.val()) || 0;
        const origen = (tasaApiFormateada !== null && Math.abs(valorIngresado - tasaApiFormateada) < 0.005)
            ? 'automatica'
            : 'manual';
        formData.append('origen', origen);
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
        // Solo se modifica la tasa activa: se rechazan años fiscales
        // distintos al activo y tasas inhabilitadas, con mensajito.
        const anioTasa = parseInt(String(data.fecha_reg || '').substring(0, 4), 10);
        if (anioFiscalActivo !== null && !Number.isNaN(anioTasa) && anioTasa !== anioFiscalActivo) {
            alert('No se puede modificar una tasa de un año fiscal que no está activo (año activo: ' + anioFiscalActivo + ').');
            return;
        }
        if (Number(data.estado) === 0) {
            alert('No se puede modificar una tasa inhabilitada: solo se permite modificar la tasa activa. Actívela primero (botón Activar) para editarla.');
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