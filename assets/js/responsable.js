$(document).ready(function() {
    const currentUrl = window.location.pathname + window.location.search;

    const dependenciaOptions = {};
    const dependenciaKeysLower = {};
    $('#dependenciasList option').each(function() {
        const value = $(this).val();
        const id = $(this).data('id');
        if (value) {
            dependenciaOptions[value] = id;
            dependenciaKeysLower[value.toLowerCase()] = id;
        }
    });

    $('#register_dependencia_search').on('input change', function() {
        const currentValue = $(this).val();
        if (dependenciaOptions.hasOwnProperty(currentValue)) {
            $('#register_id_dep').val(dependenciaOptions[currentValue]);
        } else if (dependenciaKeysLower.hasOwnProperty(currentValue.toLowerCase())) {
            $('#register_id_dep').val(dependenciaKeysLower[currentValue.toLowerCase()]);
        } else {
            $('#register_id_dep').val('');
        }
    });

    const tabla = $('#tablaMain').DataTable({
        ajax: { url: currentUrl, method: 'POST', data: { getAll: true }, dataSrc: '' },
        columns: [
            { data: 'id_responsable' },
            { data: 'nom_rep' },
            { data: 'email', render: (d) => d ? d : '<span style="color:#999;">—</span>' },
            { data: 'rol' },
            { data: 'dependencia_actual' },
            { data: 'estado', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_responsable}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                if (Number(d.estado) === 1) {
                    actions += ` <button value="${d.id_responsable}" class="btn btn-danger btn-sm btn-eliminar" aria-label="Eliminar" title="Eliminar"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>`;
                } else {
                    actions += ` <button value="${d.id_responsable}" class="btn btn-success btn-sm btn-toggle-estado" data-new-state="1" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }
                return actions;
            }}
        ],
        autoWidth: false,
        language: { url: "assets/js/DataTables/spanish.json" }
    });

    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return;
        const selectedDepId = $('#register_id_dep').val();
        if (!selectedDepId) {
            alert('Seleccione una dependencia válida de la lista.');
            return;
        }
        $btn.prop('disabled', true).text('Guardando…');
        const formData = new FormData(e.target);
        formData.set('id_dep', selectedDepId);
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



    $(document).on('click', '.btn-toggle-estado', function() {
        const id = this.value;
        const newState = $(this).data('new-state');
        if (Number(newState) !== 1) return;
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

    $(document).on('click', '.btn-eliminar', function() {
        const id = this.value;
        if (confirm('¿Está seguro de eliminar este responsable? Esto cambiará el estado de su cargo a 0 y lo dará como disponible para reasignación.')) {
            $.ajax({
                url: currentUrl,
                method: 'POST',
                dataType: 'json',
                data: { idItem: id, deleteResponsable: true },
                success: function(res) {
                    if (res && typeof res === 'object') {
                        if (res.success) {
                            alert(res.message || 'Responsable eliminado');
                        } else {
                            alert(res.message || 'Error al eliminar. ' + res);
                            console.log(res);
                        }
                    } else {
                        alert('Error al eliminar. ' + res);
                        console.log(res);
                    }
                    tabla.ajax.reload();
                }
            });
        }
    });

    $(document).on('click', '.btn-modificar', function() {
        const data = tabla.row($(this).closest('tr')).data();
        $('#edit_idItem').val(data.id_responsable);
        $('#edit_nom_rep').val(data.nom_rep);
        $('#edit_email').val(data.email || '');
        $('#edit_id_rol').val(data.id_rol);
        $('#edit_contrasena').val('');
        $('#edit_id_dep').val('');
        $('#editDependenciaGroup').hide();

        const sinDependencia = !data.dependencia_actual || data.dependencia_actual === 'Sin asignar';
        const activo = Number(data.estado) === 1;

        if (activo && sinDependencia) {
            $('#editDependenciaGroup').show();
            $('#edit_id_dep').prop('disabled', true).html('<option value="">Cargando dependencias disponibles...</option>');
            $.ajax({
                url: currentUrl,
                method: 'POST',
                dataType: 'json',
                data: { getAvailableDependenciasJson: true },
                success: function(deps) {
                    let options = '<option value="">-- Seleccione una dependencia disponible --</option>';
                    if (Array.isArray(deps)) {
                        deps.forEach(function(dep) {
                            options += `<option value="${dep.id_dep}">${dep.nom_dep}</option>`;
                        });
                    }
                    $('#edit_id_dep').html(options).prop('disabled', false);
                    if (deps && deps.length === 0) {
                        $('#edit_id_dep').html('<option value="">No hay dependencias disponibles</option>').prop('disabled', true);
                    }
                },
                error: function() {
                    $('#edit_id_dep').html('<option value="">Error al cargar dependencias</option>').prop('disabled', true);
                }
            });
        }

        $('#modalEditar').show();
    });

    $('#btnCerrarModal, #btnCerrarModal2').on('click', function() { $('#modalEditar').hide(); });

    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const idDepSeleccionada = $('#edit_id_dep').val();

        if (idDepSeleccionada) {
            const idResponsable = $('#edit_idItem').val();
            const hoy = new Date().toISOString().slice(0, 10);
            formData.delete('updateItem');
            formData.append('assignDepToResponsable', true);
            formData.append('id_responsable', idResponsable);
            formData.append('id_dep', idDepSeleccionada);
            formData.append('fecha_inicio', hoy);
        } else {
            formData.append('updateItem', true);
        }

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

    // ─── Roles: alta, edición y eliminación lógica ───────────────────
    const ROL_PROTEGIDO = 'Administrador';

    const tablaRoles = $('#tablaRoles').length ? $('#tablaRoles').DataTable({
        ajax: { url: currentUrl, method: 'POST', data: { getAllRoles: true }, dataSrc: function(json) {
            return (json && Array.isArray(json)) ? json : [];
        }},
        columns: [
            { data: 'id_rol' },
            { data: 'descripcion' },
            { data: 'responsables' },
            { data: 'estado', render: (d) => Number(d) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' },
            { data: null, render: (d) => {
                let actions = `<button value="${d.id_rol}" class="btn btn-sm btn-edit btn-editar-rol" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
                if (d.descripcion === ROL_PROTEGIDO) {
                    actions += ` <span class="badge badge-gray">protegido</span>`;
                } else if (Number(d.estado) === 1) {
                    actions += ` <button value="${d.id_rol}" class="btn btn-danger btn-sm btn-inactivar-rol" aria-label="Eliminar" title="Eliminar"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>`;
                } else {
                    actions += ` <button value="${d.id_rol}" class="btn btn-success btn-sm btn-activar-rol" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
                }
                return actions;
            }}
        ],
        autoWidth: false,
        language: { url: "assets/js/DataTables/spanish.json" }
    }) : null;

    function recargarRoles() {
        if (tablaRoles) tablaRoles.ajax.reload();
        // La tabla principal muestra la descripción del rol
        tabla.ajax.reload();
    }

    $('#btnRoles').on('click', function() {
        $('#modalRoles').show();
        if (tablaRoles) tablaRoles.ajax.reload();
    });

    $('#btnNuevoRol').on('click', function() {
        $('#formRol')[0].reset();
        $('#modalRol').show();
        $('#rol_descripcion').focus();
    });

    $('#btnCerrarModalRoles, #btnCerrarModalRoles2').on('click', function() { $('#modalRoles').hide(); });
    $('#btnCerrarModalRol, #btnCerrarModalRol2').on('click', function() { $('#modalRol').hide(); });
    $('#btnCerrarModalEditarRol, #btnCerrarModalEditarRol2').on('click', function() { $('#modalEditarRol').hide(); });

    $('#modalRoles, #modalRol, #modalEditarRol').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') $('#modalRoles, #modalRol, #modalEditarRol').hide();
    });

    // Editar rol
    $(document).on('click', '.btn-editar-rol', function() {
        if (!tablaRoles) return;
        const data = tablaRoles.row($(this).closest('tr')).data();
        $('#editRol_id').val(data.id_rol);
        $('#editRol_descripcion').val(data.descripcion);
        $('#modalEditarRol').show();
    });

    // Eliminar rol (estado = 0)
    $(document).on('click', '.btn-inactivar-rol', function() {
        if (!tablaRoles) return;
        const data = tablaRoles.row($(this).closest('tr')).data();
        if (!confirm('¿Eliminar el rol "' + data.descripcion + '"?')) return;

        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idRol: data.id_rol, deleteRol: true },
            success: function(res) {
                alert((res && res.message) || (res && res.success ? 'Rol eliminado' : 'Error al eliminar el rol.'));
                recargarRoles();
            },
            error: function(xhr, status, error) {
                console.error('AJAX delete rol error:', status, error, xhr.responseText);
                alert('Error en la petición de eliminación. Revise la consola.');
            }
        });
    });

    // Activar rol (estado = 1)
    $(document).on('click', '.btn-activar-rol', function() {
        const idRol = this.value;

        $.ajax({
            url: currentUrl,
            method: 'POST',
            dataType: 'json',
            data: { idRol: idRol, activateRol: true },
            success: function(res) {
                alert((res && res.message) || (res && res.success ? 'Rol activado' : 'Error al activar el rol.'));
                recargarRoles();
            },
            error: function(xhr, status, error) {
                console.error('AJAX activate rol error:', status, error, xhr.responseText);
                alert('Error en la petición de activación. Revise la consola.');
            }
        });
    });

    $('#formRol').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        if ($btn.prop('disabled')) return;

        const formData = new FormData($form[0]);
        formData.append('registerRol', true);

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
                    alert(res.message || 'Rol registrado exitosamente');
                    $('#modalRol').hide();
                    recargarRoles();
                } else {
                    alert((res && res.message) || 'Error al registrar el rol.');
                    $btn.prop('disabled', false).text('✓ Guardar rol');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX register rol error:', status, error, xhr.responseText);
                alert('Error en la petición. Revise la consola.');
                $btn.prop('disabled', false).text('✓ Guardar rol');
            }
        });
    });

    $('#formEditarRol').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        if ($btn.prop('disabled')) return;

        const formData = new FormData($form[0]);
        formData.append('updateRol', true);

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
                    alert(res.message || 'Rol actualizado');
                    $('#modalEditarRol').hide();
                    recargarRoles();
                } else {
                    alert((res && res.message) || 'Error al actualizar el rol.');
                    $btn.prop('disabled', false).text('✓ Guardar cambios');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX update rol error:', status, error, xhr.responseText);
                alert('Error en la petición de actualización. Revise la consola.');
                $btn.prop('disabled', false).text('✓ Guardar cambios');
            }
        });
    });

});
