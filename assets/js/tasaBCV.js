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
        if (!ValidacionesSIAP.validarFechaISO(fecha)) return 'Use el formato DD-MM-AAAA.';
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

    // Extrae AAAA-MM-DD (con hora opcional) de la cadena sin usar new Date().
    // Motivo: new Date('2026-11-15') se interpreta en UTC y getDate() devuelve
    // el día en la hora local, por lo que en zonas UTC-x (Venezuela, UTC-4)
    // la fecha se mostraba un día hacia atrás (el 15 aparecía como 14).
    function partesFechaISO(value) {
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value).trim());
        return match ? { anio: match[1], mes: match[2], dia: match[3] } : null;
    }

    function formatDateForDisplay(value) {
        if (!value) return '';
        const iso = partesFechaISO(value);
        if (iso) return `${iso.dia}/${iso.mes}/${iso.anio}`;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function formatDateForInput(value) {
        if (!value) return '';
        // Misma regla: la fecha se toma de la cadena, sin conversión UTC.
        const iso = partesFechaISO(value);
        if (iso) return `${iso.anio}-${iso.mes}-${iso.dia}`;
        const date = new Date(value);
        if (!Number.isNaN(date.getTime())) {
            return date.toISOString().slice(0, 10);
        }
        return value;
    }

    // Filas cargadas en la tabla (se actualiza en cada recarga del ajax).
    // Se usa para saber cuántas tasas están activas: el sistema siempre
    // necesita una tasa activa para realizar los cálculos, por lo que la
    // única tasa activa no se puede inhabilitar.
    let datosTasas = [];

    // true si el id corresponde a la única tasa activa del listado.
    function esUnicaTasaActiva(id) {
        const activas = datosTasas.filter((t) => (t.estado | 0) === 1);
        if (activas.length !== 1) return false;
        return String(activas[0].id_tasa) === String(id);
    }

    const tabla = $('#tablaMain').DataTable({
        ajax: {
            url: currentUrl,
            method: 'POST',
            data: { getAll: true },
            dataSrc: function(json) {
                datosTasas = Array.isArray(json) ? json : [];
                return datosTasas;
            }
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
                let actions = '';
                if ((d.estado|0) === 1) {
                    // Solo la tasa activa se puede editar
                    actions += `<button value="${d.id_tasa}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                    if (esUnicaTasaActiva(d.id_tasa)) {
                        // Única tasa activa: botón deshabilitado porque el
                        // sistema la necesita para realizar los cálculos.
                        actions += ` <button value="${d.id_tasa}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar" title="No se puede inhabilitar: es la única tasa activa" disabled><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                    } else {
                        actions += ` <button value="${d.id_tasa}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                    }
                } else {
                    // Tasa inactiva: no se puede editar, solo activar
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
    // Función asíncrona (async/await + fetch): resuelve con el número
    // redondeado a 2 decimales si hay resultado; rechaza (throw) si la
    // consulta falla o si la API no devuelve un valor válido. Cada llamador
    // decide si notifica el error (alert) o lo maneja en silencio.
    async function consultarTasaBCV() {
        tasaApiFormateada = null;
        try {
            const ctrl = new AbortController();
            const timer = setTimeout(() => ctrl.abort(), 10000); // timeout 10s
            const respuesta = await fetch('https://ve.dolarapi.com/v1/dolares/oficial', {
                method: 'GET',
                signal: ctrl.signal
            });
            clearTimeout(timer);
            if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
            const res = await respuesta.json();
            const bruto = Number(res && (res.promedio ?? res.venta ?? res.compra));
            if (!isFinite(bruto) || bruto <= 0) throw new Error('La API no devolvió un valor de tasa válido.');
            tasaApiFormateada = Number(bruto.toFixed(2));
            return tasaApiFormateada;
        } catch (error) {
            console.error('No se pudo consultar la tasa BCV (dolarapi.com):', error);
            throw error;
        }
    }

    // Botón "Usar tasa de la API": consulta el valor del día y lo coloca en
    // el campo indicado (para cuando el usuario cambió el valor y quiere
    // volver al de la API). Se deshabilita durante la consulta y se
    // reactiva al terminar (éxito o error).
    async function pedirTasaApi($boton, $destino) {
        const contenido = $boton.html();
        $boton.prop('disabled', true)
              .html('<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Consultando…');
        try {
            const valor = await consultarTasaBCV();
            $destino.val(valor.toFixed(2));
        } catch (error) {
            if (error && error.name === 'AbortError') {
                alert('La consulta a la API tardó demasiado. Intente de nuevo.');
            } else if (error && /válido/i.test(error.message || '')) {
                alert('La API no devolvió un valor de tasa válido. Intente de nuevo.');
            } else {
                alert('No se pudo consultar la tasa de la API. Verifique su conexión e intente de nuevo.');
            }
        } finally {
            $boton.prop('disabled', false).html(contenido);
        }
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
        // Se dispara sin await para no bloquear la apertura de la modal.
        consultarTasaBCV()
            .then(function(valor) {
                $('#formRegistro [name="tasa_bcv_usd"]').val(valor.toFixed(2));
            })
            .catch(function() { /* silencioso: el registro queda como 'manual' */ });
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
        // Respaldo del botón deshabilitado: la única tasa activa no se
        // puede inhabilitar (el sistema la necesita para los cálculos).
        if (esUnicaTasaActiva(this.value)) {
            alert('No se puede inhabilitar la única tasa activa: el sistema siempre necesita una tasa activa para realizar los cálculos.');
            return;
        }
        if (!confirm('¿Inhabilitar esta tasa?')) return;
        const idTasa = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json',
            data: { id_tasa: idTasa, inactivateItem: true },
            success: function(res) {
                if (res === true || (res && res.success)) {
                    alert((res && res.message) || 'Tasa inhabilitada');
                } else {
                    alert((res && res.message) || 'Error');
                }
                tabla.ajax.reload();
            },
            error: function() { alert('Error de conexión.'); }
        });
    });

    // Activar
    $(document).on('click', '.btn-activar', function() {
        if (!confirm('¿Activar esta tasa? La tasa activa actual será desactivada automáticamente (solo puede haber una tasa activa).')) return;
        const idTasa = this.value;
        $.ajax({ url: currentUrl, method: 'POST', dataType: 'json',
            data: { id_tasa: idTasa, activateItem: true },
            success: function(res) { if (res == true) { alert('Tasa activada'); } else { alert('Error'); } tabla.ajax.reload(); },
            error: function() { alert('Error de conexión.'); }
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

    $('#modalEditar').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    // Guardar edición
    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        const textoOriginal = $btn.text();
        $btn.prop('disabled', true).text('Guardando…');
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
                $btn.prop('disabled', false).text(textoOriginal);
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
            },
            error: function() {
                $btn.prop('disabled', false).text(textoOriginal);
                alert('Error de conexión.');
            }
        });
    });

});