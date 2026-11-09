$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;
    console.log('periodo.js loaded', currentUrl);

    // ─── Validación de fechas (plugin siapValidar) ───────────────────
    // Cada formulario tiene su select de año fiscal (el <option> muestra el
    // año, p. ej. "2026"; su value es el id). El error se marca SIEMPRE en
    // el input que lo provoca:
    //   - fecha_inicio: formato, orden (inicio <= fin) y año fiscal.
    //   - fecha_fin:    formato y año fiscal.
    var selAnioReg = $('#formRegistro select[name="anio_fiscal_id"]');
    var selAnioEdit = $('#edit_anio_fiscal_id');

    // Año fiscal seleccionado (número) o null si no hay uno reconocible
    function anioFiscalDe($select) {
        var texto = $select.find('option:selected').text().trim();
        return /^\d{4}$/.test(texto) ? parseInt(texto, 10) : null;
    }

    // Motivo de error de fecha_inicio (null si es válida). El orden de
    // fechas se evalúa aquí y no en fecha_fin.
    function motivoFechaInicio(valor, valorFin, $selAnio) {
        if (!ValidacionesSIAP.validarFechaISO(valor)) return 'Use el formato AAAA-MM-DD.';
        if (ValidacionesSIAP.validarFechaISO(valorFin) && valor > valorFin) {
            return 'La fecha de inicio no puede ser superior a la fecha fin.';
        }
        var anio = anioFiscalDe($selAnio);
        if (anio !== null) {
            var anioFecha = parseInt(valor.substring(0, 4), 10);
            if (anioFecha > anio) return 'La fecha de inicio no puede ser posterior al año fiscal seleccionado (' + anio + ').';
            if (anioFecha < anio) return 'La fecha de inicio debe corresponder al año fiscal seleccionado (' + anio + ').';
        }
        return null;
    }

    // Motivo de error de fecha_fin (null si es válida): formato y año fiscal
    // (el servidor exige que ambas fechas pertenezcan al año fiscal activo).
    function motivoFechaFin(valor, $selAnio) {
        if (!ValidacionesSIAP.validarFechaISO(valor)) return 'Use el formato AAAA-MM-DD.';
        var anio = anioFiscalDe($selAnio);
        if (anio !== null) {
            var anioFecha = parseInt(valor.substring(0, 4), 10);
            if (anioFecha > anio) return 'La fecha fin no puede ser posterior al año fiscal seleccionado (' + anio + ').';
            if (anioFecha < anio) return 'La fecha fin debe corresponder al año fiscal seleccionado (' + anio + ').';
        }
        return null;
    }

    // Configuración de validación de un formulario de fechas
    function configFechas($selAnio, $inicio, $fin) {
        return {
            fecha_inicio: {
                test: function(valor) {
                    return motivoFechaInicio(valor, $fin.val(), $selAnio) === null;
                },
                mensaje: function() {
                    return motivoFechaInicio($inicio.val(), $fin.val(), $selAnio) || 'La fecha de inicio no es válida.';
                }
            },
            fecha_fin: {
                test: function(valor) {
                    return motivoFechaFin(valor, $selAnio) === null;
                },
                mensaje: function() {
                    return motivoFechaFin($fin.val(), $selAnio) || 'La fecha fin no es válida.';
                }
            },
            anio_fiscal_id: { regla: 'requerido', mensaje: 'Seleccione un año fiscal.' }
        };
    }

    $('#formRegistro').siapValidar(configFechas(selAnioReg, $('#fecha_inicio'), $('#fecha_fin')));
    $('#formEditar').siapValidar(configFechas(selAnioEdit, $('#edit_fecha_inicio'), $('#edit_fecha_fin')));

    $('#formAnioFiscal').siapValidar({
        anio_fiscal: { regla: 'anio' }
    });

    $('#formAnioFiscalEditar').siapValidar({
        anio_fiscal: { regla: 'anio' }
    });

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
            { data: 'anio_fiscal_id', defaultContent: '—' },
            { data: 'fecha_inicio', defaultContent: '—' },
            { data: 'fecha_fin', defaultContent: '—' },
            { data: 'activo', defaultContent: 0, render: (d) => (d == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>') },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_periodo}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                if ((d.activo|0) === 1) {
                    actions += ` <button value="${d.id_periodo}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                } else {
                    actions += ` <button value="${d.id_periodo}" class="btn btn-success btn-sm btn-activar" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }
                return actions;
            }}
        ],
        autoWidth: false,
        language: {
            url: "assets/js/DataTables/spanish.json"
        }
    });

    // ─── Re-validación en vivo ──────────────────────────────────────
    // Al cambiar cualquier fecha o el año fiscal se re-evalúan de inmediato
    // los campos afectados para mostrar/ocultar el error rojo existente
    // (.field-error / .is-invalid), sin esperar al blur ni al envío.
    // (Al enviar, el plugin ya bloquea el formulario con estos mismos errores.)
    function revalidarCampo($campo) {
        if ($campo.val()) $campo.trigger('focusout');
    }

    // Registro: al cambiar fecha_fin puede cambiar el orden de la fecha
    // de inicio; al cambiar el año fiscal, ambas fechas.
    $('#fecha_inicio').on('change', function() { $(this).trigger('focusout'); });
    $('#fecha_fin').on('change', function() {
        $(this).trigger('focusout');
        revalidarCampo($('#fecha_inicio'));
    });
    selAnioReg.on('change', function() {
        revalidarCampo($('#fecha_inicio'));
        revalidarCampo($('#fecha_fin'));
    });

    // Edición: mismas reglas con los campos del modal de edición.
    $('#edit_fecha_inicio').on('change', function() { $(this).trigger('focusout'); });
    $('#edit_fecha_fin').on('change', function() {
        $(this).trigger('focusout');
        revalidarCampo($('#edit_fecha_inicio'));
    });
    selAnioEdit.on('change', function() {
        revalidarCampo($('#edit_fecha_inicio'));
        revalidarCampo($('#edit_fecha_fin'));
    });

    // ─── Modal de registro ─────────────────────────────────────────
    $('#btnNuevoRegistro').on('click', function() {
        $('#formRegistro')[0].reset();
        $('#formRegistro').siapValidar('limpiar');
        $('#modalRegistro').show();
        $('#fecha_inicio').focus();
    });

    $('#btnCerrarModalRegistro, #btnCerrarModalRegistro2').on('click', function() {
        $('#modalRegistro').hide();
    });

    $('#modalRegistro').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text('Guardando…');
        const formData = new FormData(e.target);
        formData.append('registerPeriodo', true);
        $.ajax({
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).text('✓ Registrar');
                if (res && res.success) {
                    // 1) Alerta de éxito y, al aceptarla, 2) cierre de la modal
                    alert(res.message || 'Periodo registrado exitosamente');
                    $('#modalRegistro').hide();
                    $('#formRegistro')[0].reset();
                    $('#formRegistro').siapValidar('limpiar');
                    tabla.ajax.reload();
                } else {
                    alert((res && res.message) || 'Error al registrar. Verifique los datos.');
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).text('✓ Registrar');
                console.error('AJAX register error:', status, error, xhr.responseText);
                alert('Error en la petición de registro. Revise la consola para más detalles.');
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
        $('#formEditar').siapValidar('limpiar');
        $('#modalEditar').show();
    });

    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
        $('#modalEditar').hide();
    });

    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
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

    // ─── Años fiscales (modal dentro de Periodos) ──────────────────────
    const tablaAnios = $('#tablaAniosFiscales').length ? $('#tablaAniosFiscales').DataTable({
        ajax: {
            url: currentUrl,
            method: 'POST',
            data: { getAllAniosFiscales: true },
            dataSrc: function(json) {
                return (json && Array.isArray(json)) ? json : [];
            }
        },
        columns: [
            { data: 'id_anioFis' },
            { data: 'anio_fiscal' },
            { data: 'activo', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
            { data: null, render: (d) => {
                let acciones;
                if (Number(d.tiene_periodo) === 1) {
                    // El año fiscal ya tiene periodo(s) asociado(s): no aparece la opción modificar
                    acciones = `<span class="btn btn-sm" role="img" aria-label="No editable" title="Este año fiscal ya tiene periodos asociados; no se puede modificar." style="cursor:default; opacity:.65;"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>`;
                } else {
                    acciones = `<button value="${d.id_anioFis}" class="btn btn-sm btn-edit btn-modificar-anio" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                }
                if (Number(d.activo) === 1) {
                    acciones += ` <button value="${d.id_anioFis}" class="btn btn-warning btn-sm btn-inactivar-anio" aria-label="Inactivar" title="Inactivar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
                } else {
                    acciones += ` <button value="${d.id_anioFis}" class="btn btn-success btn-sm btn-activar-anio" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }
                return acciones;
            }}
        ],
        autoWidth: false,
        language: {
            url: "assets/js/DataTables/spanish.json"
        }
    }) : null;

    function recargarAnios() {
        if (tablaAnios) tablaAnios.ajax.reload();
    }

    // Abrir listado de años fiscales
    $('#btnAniosFiscales').on('click', function() {
        $('#modalAniosFiscales').show();
        recargarAnios();
    });

    $('#btnCerrarModalAnios, #btnCerrarModalAnios2').on('click', function() {
        $('#modalAniosFiscales').hide();
    });

    $('#modalAniosFiscales').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    // Cerrar con la tecla ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#modalAniosFiscales, #modalAnioFiscal, #modalEditarAnioFiscal').hide();
        }
    });

    // Desde el listado, abrir el formulario de registro
    $('#btnNuevoAnioFiscal').on('click', function() {
        $('#formAnioFiscal')[0].reset();
        $('#modalAnioFiscal').show();
        $('#aniof_anio').focus();
    });

    $('#btnCerrarModalAnioFiscal, #btnCerrarModalAnioFiscal2').on('click', function() {
        $('#modalAnioFiscal').hide();
    });

    $('#modalAnioFiscal').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    $('#btnCerrarModalEditarAnioFiscal, #btnCerrarModalEditarAnioFiscal2').on('click', function() {
        $('#modalEditarAnioFiscal').hide();
    });

    $('#modalEditarAnioFiscal').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    // Registrar año fiscal
    $('#formAnioFiscal').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text('Guardando…');

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
                if (res && res.success) {
                    alert(res.message || 'Año fiscal registrado');
                    // Recargar la página para actualizar el select de Año Fiscal del modal Editar periodo (se renderiza en PHP)
                    window.location.reload();
                } else {
                    alert((res && res.message) || 'Error al registrar el año fiscal.');
                    $btn.prop('disabled', false).text('✓ Guardar año fiscal');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX año fiscal error:', status, error, xhr.responseText);
                alert('Error en la petición. Revise la consola.');
                $btn.prop('disabled', false).text('✓ Guardar año fiscal');
            }
        });
    });

    // Editar año fiscal
    $(document).on('click', '.btn-modificar-anio', function() {
        if (!tablaAnios) return;
        const data = tablaAnios.row($(this).closest('tr')).data();
        $('#editAniof_id').val(data.id_anioFis);
        $('#editAniof_anio').val(data.anio_fiscal);
        $('#formAnioFiscalEditar').siapValidar('limpiar');
        $('#modalEditarAnioFiscal').show();
    });

    $('#formAnioFiscalEditar').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        if ($btn.prop('disabled')) return;

        const formData = new FormData(e.target);
        formData.append('updateAnioFiscal', true);

        $btn.prop('disabled', true).text('Guardando…');

        $.ajax({
            url: currentUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                if (res && res.success) {
                    alert(res.message || 'Año fiscal actualizado');
                    $('#modalEditarAnioFiscal').hide();
                    recargarAnios();
                } else {
                    alert((res && res.message) || 'Error al actualizar el año fiscal.');
                    $btn.prop('disabled', false).text('✓ Guardar cambios');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX update año fiscal error:', status, error, xhr.responseText);
                alert('Error en la petición de actualización. Revise la consola.');
                $btn.prop('disabled', false).text('✓ Guardar cambios');
            }
        });
    });

    // Inactivar año fiscal
    $(document).on('click', '.btn-inactivar-anio', function() {
        if (!confirm('¿Inhabilitar este año fiscal?')) return;
        const idItem = this.value;
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: idItem, inactivateAnioFiscal: true },
            success: function(res) {
                alert((res && res.message) || 'Año fiscal inhabilitado');
                recargarAnios();
            },
            error: function(xhr, status, error) {
                console.error('AJAX inactivate año fiscal error:', status, error, xhr.responseText);
                alert('Error en la petición. Revise la consola.');
            }
        });
    });

    // Activar año fiscal
    $(document).on('click', '.btn-activar-anio', function() {
        if (!confirm('¿Activar este año fiscal y desactivar otros?')) return;
        const idItem = this.value;
        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idItem: idItem, activateAnioFiscal: true },
            success: function(res) {
                alert((res && res.message) || 'Año fiscal activado');
                recargarAnios();
            },
            error: function(xhr, status, error) {
                console.error('AJAX activate año fiscal error:', status, error, xhr.responseText);
                alert('Error en la petición. Revise la consola.');
            }
        });
    });

});

