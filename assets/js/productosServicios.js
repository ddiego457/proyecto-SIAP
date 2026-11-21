$(document).ready(function() {
  const currentUrl = window.location.pathname + window.location.search;

  // Validaciones en el cliente (plugin siapValidar).
  $('#formRegistroPS').siapValidar({
    id_partida: { regla: 'requerido', mensaje: 'Seleccione una partida presupuestaria.' },
    id_proveedor: { regla: 'requerido', mensaje: 'Seleccione un proveedor.' },
    id_unidad: { regla: 'requerido', mensaje: 'Seleccione una unidad de medida.' },
    nom_item: { regla: 'nombreProducto' },
    precio: { regla: 'precio' }
  });

  $('#formEditar').siapValidar({
    id_partida: { regla: 'requerido', mensaje: 'Seleccione una partida presupuestaria.' },
    id_proveedor: { regla: 'requerido', mensaje: 'Seleccione un proveedor.' },
    id_unidad: { regla: 'requerido', mensaje: 'Seleccione una unidad de medida.' },
    nom_item: { regla: 'nombreProducto' },
    precio: { regla: 'precio' }
  });

  $('#formPartida').siapValidar({
    cod_partida: { regla: 'codigoPartida' },
    descripcion: { regla: 'descrPresupuesto' }
  });

  $('#formPartidaEditar').siapValidar({
    cod_partida: { regla: 'codigoPartida' },
    descripcion: { regla: 'descrPresupuesto' },
    estado: { regla: 'requerido', mensaje: 'Seleccione un estado.' }
  });

  $('#formUnidad').siapValidar({
    unidad: { regla: 'unidadMedida' }
  });

  $('#formUnidadEditar').siapValidar({
    unidad: { regla: 'unidadMedida' },
    estado: { regla: 'requerido', mensaje: 'Seleccione un estado.' }
  });

  const tabla = $('#tablaMain').DataTable({
    ajax: {
      url: currentUrl,
      method: 'POST',
      data: function() {
        return {
          getAll: true,
          partidaId: $('#selectPartida').val()
        };
      },
      dataSrc: function(json) {
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
      { data: 'id_pres' },
      { data: 'cod_partida' },
      { data: 'proveedor' },
      { data: 'nom_pres' },
      { data: 'unidad' },
      { data: 'precio' },
      { data: null, render: (d) => {
          return (d.estado == 1)
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-danger">Inactivo</span>';
        }
      },
      { data: null, render: (d) => {
          let acciones = `<button value="${d.id_pres}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
          if ((d.estado|0) === 1) {
            acciones += ` <button value="${d.id_pres}" class="btn btn-warning btn-sm btn-inactivar" aria-label="Inhabilitar" title="Inhabilitar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
          } else {
            acciones += ` <button value="${d.id_pres}" class="btn btn-success btn-sm btn-activar" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
          }
          return acciones;
        }
      }
    ],
    autoWidth: false,
    language: {
      url: "assets/js/DataTables/spanish.json"
    }
  });

  $('#selectPartida').on('change', function() {
    tabla.ajax.reload();
  });

  // ─── Modal de registro ───────────────────────────────────────────
  $('#btnNuevoRegistro').on('click', function() {
    const $form = $('#formRegistroPS');
    if ($form.length) {
      $form[0].reset();
      $form.siapValidar('limpiar');
      $form.find('input[name="nom_item"]').focus();
    }
    $('#modalRegistro').show();
  });

  $('#btnCerrarModalRegistro, #btnCerrarModalRegistro2').on('click', function() {
    $('#modalRegistro').hide();
  });

  $('#modalRegistro').on('click', function(e) {
    if (e.target === this) $(this).hide();
  });

  $('#formRegistroPS').on('submit', function(e) {
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]');
    if ($btn.prop('disabled')) return;
    $btn.prop('disabled', true).text('Guardando…');
    const formData = new FormData(e.target);
    formData.append('registerProductosServicios', '1');

    $.ajax({
      url: currentUrl,
      method: 'POST',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType: false,
      success: function(res) {
          $btn.prop('disabled', false).text('✓ Registrar');
          if (res && typeof res === 'object' && res.success) {
            // 1) Alerta de éxito y, al aceptarla, 2) cierre de la modal
            alert(res.message || 'Producto o servicio registrado');
            $('#modalRegistro').hide();
            $('#formRegistroPS')[0].reset();
            $('#formRegistroPS').siapValidar('limpiar');
            tabla.ajax.reload();
          } else if (res === true) {
            alert('Producto o servicio registrado');
            $('#modalRegistro').hide();
            $('#formRegistroPS')[0].reset();
            $('#formRegistroPS').siapValidar('limpiar');
            tabla.ajax.reload();
          } else {
            alert((res && res.message) || 'Error al registrar el item.');
          }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', status, error, xhr.responseText);
        $btn.prop('disabled', false).text('✓ Registrar');
        alert('Error en la petición. Revise la consola.');
      }
    });
  });

  $(document).on('click', '.btn-modificar', function() {
    const data = tabla.row($(this).closest('tr')).data();
    $('#edit_idItem').val(data.id_pres);
    $('#edit_partida_id').val(data.id_partida);
    $('#edit_proveedor_id').val(data.id_proveedor);
    // La unidad puede estar inhabilitada: si no está en el select (que solo
    // muestra activas) se agrega para no perder la referencia al guardar.
    const $selUnidad = $('#edit_unidad_id');
    const idUnidad = data.id_unidad || '';
    if (idUnidad && !$selUnidad.find('option[value="' + idUnidad + '"]').length) {
      $selUnidad.append($('<option>', { value: idUnidad, text: (data.unidad || '—') + ' (inactiva)' }));
    }
    $selUnidad.val(String(idUnidad));
    $('#edit_nom_prod').val(data.nom_pres);
    $('#edit_precio').val(data.precio);
    $('#formEditar').siapValidar('limpiar');
    $('#modalEditar').show();
  });

  $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
    $('#modalEditar').hide();
  });

  $('#modalEditar').on('click', function(e) {
    if (e.target === this) $(this).hide();
  });

  $('#formEditar').on('submit', function(e) {
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]');
    if ($btn.prop('disabled')) return;
    const textoOriginal = $btn.text();
    $btn.prop('disabled', true).text('Guardando…');
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
        $btn.prop('disabled', false).text(textoOriginal);
        if (res && res.success) {
          alert(res.message || 'Registro actualizado exitosamente');
          $('#modalEditar').hide();
          tabla.ajax.reload();
        } else {
          alert(res.message || 'Error al actualizar.');
        }
      },
      error: function(xhr, status, error) {
        $btn.prop('disabled', false).text(textoOriginal);
        console.error('AJAX update error:', status, error, xhr.responseText);
        alert('Error en la petición de actualización. Revise la consola.');
      }
    });
  });

  // ─── Nueva partida presupuestaria ──────────────────────────────
  $('#btnCerrarModalPartida, #btnCerrarModalPartida2').on('click', function() {
    $('#modalPartida').hide();
  });

  // Cerrar haciendo clic fuera del panel
  $('#modalPartida').on('click', function(e) {
    if (e.target === this) $('#modalPartida').hide();
  });

  // Cerrar con la tecla ESC
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
      $('#modalPartida, #modalPartidas, #modalEditarPartida, #modalUnidad, #modalUnidades, #modalEditarUnidad').hide();
    }
  });

  $('#formPartida').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    if ($btn.prop('disabled')) return;

    const formData = new FormData(e.target);
    formData.append('registerPartida', true);

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
          alert(res.message || 'Partida registrada correctamente');
          // Recarga la página para actualizar #selectPartida (se renderiza en PHP)
          window.location.reload();
        } else {
          alert((res && res.message) || 'Error al registrar la partida.');
          $btn.prop('disabled', false).text('✓ Guardar partida');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX partida error:', status, error, xhr.responseText);
        alert('Error en la petición. Revise la consola.');
        $btn.prop('disabled', false).text('✓ Guardar partida');
      }
    });
  });

  // ─── Mantenimiento de partidas presupuestarias ─────────────────────
  const tablaPartidas = $('#tablaPartidas').length ? $('#tablaPartidas').DataTable({
    ajax: {
      url: currentUrl,
      method: 'POST',
      data: { getAllPartidas: true },
      dataSrc: function(json) {
        return (json && Array.isArray(json)) ? json : [];
      }
    },
    columns: [
      { data: 'id_partida' },
      { data: 'cod_partida' },
      { data: 'descripcion' },
      { data: 'estado', render: function(d) {
          return (parseInt(d, 10) === 1)
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-danger">Inactivo</span>';
        }
      },
      { data: null, render: function(d) {
          let acciones = `<button value="${d.id_partida}" class="btn btn-sm btn-edit btn-modificar-partida" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
          if (parseInt(d.estado, 10) === 1) {
            acciones += `<button value="${d.id_partida}" class="btn btn-warning btn-sm btn-inactivar-partida" aria-label="Inhabilitar" title="Inhabilitar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
          } else {
            acciones += `<button value="${d.id_partida}" class="btn btn-success btn-sm btn-activar-partida" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
          }
          return acciones;
        }
      }
    ],
    autoWidth: false,
    language: {
      url: "assets/js/DataTables/spanish.json"
    }
  }) : null;

  function recargarPartidas() {
    if (tablaPartidas) tablaPartidas.ajax.reload();
    // La tabla principal muestra el código/descripción de la partida
    tabla.ajax.reload();
  }

  // Topbar: abrir primero el listado de partidas presupuestarias
  $('#btnPartidas').on('click', function() {
    $('#modalPartidas').show();
    if (tablaPartidas) tablaPartidas.ajax.reload();
  });

  // Desde el listado, abrir el formulario de registro
  $('#btnNuevaPartida').on('click', function() {
    $('#formPartida')[0].reset();
    $('#modalPartida').show();
    $('#partida_cod').focus();
  });

  $('#btnCerrarModalPartidas, #btnCerrarModalPartidas2').on('click', function() {
    $('#modalPartidas').hide();
  });

  $('#modalPartidas').on('click', function(e) {
    if (e.target === this) $('#modalPartidas').hide();
  });

  $('#btnCerrarModalEditarPartida, #btnCerrarModalEditarPartida2').on('click', function() {
    $('#modalEditarPartida').hide();
  });

  $('#modalEditarPartida').on('click', function(e) {
    if (e.target === this) $('#modalEditarPartida').hide();
  });

  // Editar partida
  $(document).on('click', '.btn-modificar-partida', function() {
    if (!tablaPartidas) return;
    const data = tablaPartidas.row($(this).closest('tr')).data();
    $('#editPartida_id').val(data.id_partida);
    $('#editPartida_cod').val(data.cod_partida);
    $('#editPartida_descripcion').val(data.descripcion);
    $('#editPartida_estado').val(parseInt(data.estado, 10) === 1 ? '1' : '0');
    $('#formPartidaEditar').siapValidar('limpiar');
    $('#modalEditarPartida').show();
  });

  // Eliminar partida (estado = 0)
  $(document).on('click', '.btn-inactivar-partida', function() {
    if (!confirm('¿Eliminar (inhabilitar) esta partida presupuestaria?')) return;
    const idPartida = this.value;

    $.ajax({
      url: currentUrl,
      method: 'POST',
      dataType: 'json',
      data: { idPartida: idPartida, deletePartida: true },
      success: function(res) {
        if (res && res.success) {
          alert(res.message || 'Partida presupuestaria eliminada');
          recargarPartidas();
        } else {
          alert((res && res.message) || 'Error al eliminar la partida.');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX delete partida error:', status, error, xhr.responseText);
        alert('Error en la petición de eliminación. Revise la consola.');
      }
    });
  });

  // Activar partida (estado = 1)
  $(document).on('click', '.btn-activar-partida', function() {
    const idPartida = this.value;

    $.ajax({
      url: currentUrl,
      method: 'POST',
      dataType: 'json',
      data: { idPartida: idPartida, activatePartida: true },
      success: function(res) {
        if (res && res.success) {
          alert(res.message || 'Partida presupuestaria activada');
          recargarPartidas();
        } else {
          alert((res && res.message) || 'Error al activar la partida.');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX activate partida error:', status, error, xhr.responseText);
        alert('Error en la petición de activación. Revise la consola.');
      }
    });
  });

  $('#formPartidaEditar').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    if ($btn.prop('disabled')) return;

    const formData = new FormData($form[0]);
    formData.append('updatePartida', true);

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
          alert(res.message || 'Partida actualizada exitosamente');
          $('#modalEditarPartida').hide();
          recargarPartidas();
        } else {
          alert((res && res.message) || 'Error al actualizar la partida.');
          $btn.prop('disabled', false).text('✓ Guardar cambios');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX update partida error:', status, error, xhr.responseText);
        alert('Error en la petición de actualización. Revise la consola.');
        $btn.prop('disabled', false).text('✓ Guardar cambios');
      }
    });
  });

  // ─── Mantenimiento de unidades de medida ──────────────────────────
  const tablaUnidades = $('#tablaUnidades').length ? $('#tablaUnidades').DataTable({
    ajax: {
      url: currentUrl,
      method: 'POST',
      data: { getAllUnidadesMedida: true },
      dataSrc: function(json) {
        return (json && Array.isArray(json)) ? json : [];
      }
    },
    columns: [
      { data: 'id_unidad' },
      { data: 'unidad' },
      { data: 'estado', render: function(d) {
          return (parseInt(d, 10) === 1)
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-danger">Inactivo</span>';
        }
      },
      { data: null, render: function(d) {
          let acciones = `<button value="${d.id_unidad}" class="btn btn-sm btn-edit btn-modificar-unidad" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>`;
          if (parseInt(d.estado, 10) === 1) {
            acciones += `<button value="${d.id_unidad}" class="btn btn-warning btn-sm btn-inactivar-unidad" aria-label="Inhabilitar" title="Inhabilitar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>`;
          } else {
            acciones += `<button value="${d.id_unidad}" class="btn btn-success btn-sm btn-activar-unidad" aria-label="Activar" title="Activar"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></button>`;
          }
          return acciones;
        }
      }
    ],
    autoWidth: false,
    language: {
      url: "assets/js/DataTables/spanish.json"
    }
  }) : null;

  function recargarUnidades() {
    if (tablaUnidades) tablaUnidades.ajax.reload();
    // La tabla principal muestra la unidad de medida del producto
    tabla.ajax.reload();
  }

  // Topbar: abrir el listado de unidades de medida
  $('#btnUnidades').on('click', function() {
    $('#modalUnidades').show();
    if (tablaUnidades) tablaUnidades.ajax.reload();
  });

  // Desde el listado, abrir el formulario de registro
  $('#btnNuevaUnidad').on('click', function() {
    $('#formUnidad')[0].reset();
    $('#formUnidad').siapValidar('limpiar');
    $('#modalUnidad').show();
    $('#unidad_nombre').focus();
  });

  $('#btnCerrarModalUnidades, #btnCerrarModalUnidades2').on('click', function() {
    $('#modalUnidades').hide();
  });

  $('#modalUnidades').on('click', function(e) {
    if (e.target === this) $('#modalUnidades').hide();
  });

  $('#btnCerrarModalUnidad, #btnCerrarModalUnidad2').on('click', function() {
    $('#modalUnidad').hide();
  });

  $('#modalUnidad').on('click', function(e) {
    if (e.target === this) $('#modalUnidad').hide();
  });

  $('#btnCerrarModalEditarUnidad, #btnCerrarModalEditarUnidad2').on('click', function() {
    $('#modalEditarUnidad').hide();
  });

  $('#modalEditarUnidad').on('click', function(e) {
    if (e.target === this) $('#modalEditarUnidad').hide();
  });

  $('#formUnidad').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    if ($btn.prop('disabled')) return;

    const formData = new FormData(e.target);
    formData.append('registerUnidadMedida', true);

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
          alert(res.message || 'Unidad de medida registrada correctamente');
          // Recarga la página para actualizar los selects de producto (se renderizan en PHP)
          window.location.reload();
        } else {
          alert((res && res.message) || 'Error al registrar la unidad de medida.');
          $btn.prop('disabled', false).text('✓ Guardar unidad');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX unidad error:', status, error, xhr.responseText);
        alert('Error en la petición. Revise la consola.');
        $btn.prop('disabled', false).text('✓ Guardar unidad');
      }
    });
  });

  // Editar unidad de medida
  $(document).on('click', '.btn-modificar-unidad', function() {
    if (!tablaUnidades) return;
    const data = tablaUnidades.row($(this).closest('tr')).data();
    $('#editUnidad_id').val(data.id_unidad);
    $('#editUnidad_nombre').val(data.unidad);
    $('#editUnidad_estado').val(parseInt(data.estado, 10) === 1 ? '1' : '0');
    $('#formUnidadEditar').siapValidar('limpiar');
    $('#modalEditarUnidad').show();
  });

  // Inhabilitar unidad (estado = 0)
  $(document).on('click', '.btn-inactivar-unidad', function() {
    if (!confirm('¿Inhabilitar esta unidad de medida? Los productos que la usan mantienen la referencia.')) return;
    const idUnidad = this.value;

    $.ajax({
      url: currentUrl,
      method: 'POST',
      dataType: 'json',
      data: { idUnidad: idUnidad, deleteUnidadMedida: true },
      success: function(res) {
        if (res && res.success) {
          alert(res.message || 'Unidad de medida inhabilitada');
          recargarUnidades();
        } else {
          alert((res && res.message) || 'Error al inhabilitar la unidad de medida.');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX delete unidad error:', status, error, xhr.responseText);
        alert('Error en la petición de inhabilitación. Revise la consola.');
      }
    });
  });

  // Activar unidad (estado = 1)
  $(document).on('click', '.btn-activar-unidad', function() {
    const idUnidad = this.value;

    $.ajax({
      url: currentUrl,
      method: 'POST',
      dataType: 'json',
      data: { idUnidad: idUnidad, activateUnidadMedida: true },
      success: function(res) {
        if (res && res.success) {
          alert(res.message || 'Unidad de medida activada');
          recargarUnidades();
        } else {
          alert((res && res.message) || 'Error al activar la unidad de medida.');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX activate unidad error:', status, error, xhr.responseText);
        alert('Error en la petición de activación. Revise la consola.');
      }
    });
  });

  $('#formUnidadEditar').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    if ($btn.prop('disabled')) return;

    const formData = new FormData($form[0]);
    formData.append('updateUnidadMedida', true);

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
          alert(res.message || 'Unidad de medida actualizada exitosamente');
          $('#modalEditarUnidad').hide();
          // Recarga para refrescar los selects de producto (PHP) y el listado
          window.location.reload();
        } else {
          alert((res && res.message) || 'Error al actualizar la unidad de medida.');
          $btn.prop('disabled', false).text('✓ Guardar cambios');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX update unidad error:', status, error, xhr.responseText);
        alert('Error en la petición de actualización. Revise la consola.');
        $btn.prop('disabled', false).text('✓ Guardar cambios');
      }
    });
  });

  $(document).on('click', '.btn-inactivar', function() {
    if (!confirm('¿Inhabilitar este item?')) return;
    const idItem = this.value;

    $.ajax({
      url: currentUrl,
      method: 'POST',
      dataType: 'json',
      data: { idItem: idItem, deleteItem: true },
      success: function(res) {
        if (res && res.success) {
          alert(res.message || 'Registro inhabilitado');
          tabla.ajax.reload();
        } else {
          alert(res.message || 'Error al inhabilitar.');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX delete error:', status, error, xhr.responseText);
        alert('Error en la petición de inhabilitación. Revise la consola.');
      }
    });
  });

  // Activar item (estado = 1)
  $(document).on('click', '.btn-activar', function() {
    if (!confirm('¿Activar este item?')) return;
    const idItem = this.value;

    $.ajax({
      url: currentUrl,
      method: 'POST',
      dataType: 'json',
      data: { idItem: idItem, activateItem: true },
      success: function(res) {
        if (res && res.success) {
          alert(res.message || 'Registro activado');
          tabla.ajax.reload();
        } else {
          alert(res.message || 'Error al activar.');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX activate error:', status, error, xhr.responseText);
        alert('Error en la petición de activación. Revise la consola.');
      }
    });
  });

  // ─── Importar Excel ─────────────────────────────────────────────
  $('#btnImportarExcel').on('click', function() {
    $('#formImportar')[0].reset();
    $('#formImportar').siapValidar('limpiar');
    $('#modalImportar').show();
    $('#archivoExcel').focus();
  });

  $('#btnCerrarModalImportar, #btnCerrarModalImportar2').on('click', function() {
    $('#modalImportar').hide();
  });

  $('#modalImportar').on('click', function(e) {
    if (e.target === this) $('#modalImportar').hide();
  });

  // Cerrar con la tecla ESC (agrega modalImportar y modalResultadoImportar a los existentes)
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
      $('#modalImportar').hide();
      $('#modalResultadoImportar').hide();
    }
  });

  // Cerrar modal de resultados
  $('#btnCerrarModalResultado, #btnCerrarModalResultado2').on('click', function() {
    $('#modalResultadoImportar').hide();
  });

  $('#modalResultadoImportar').on('click', function(e) {
    if (e.target === this) $('#modalResultadoImportar').hide();
  });

  // Mostrar el resultado de la importación en el modal
  function mostrarResultadoImportar(res) {
    const r = res.resumen || {};
    const insertados = r.insertados || 0;
    const omitidos = r.omitidos || 0;
    const errores = r.errores || 0;
    const detalles = Array.isArray(r.detalles_error) ? r.detalles_error : [];
    const hojas = Array.isArray(r.hojas) ? r.hojas : [];

    // Título según el resultado
    let titulo = 'Importación completada';
    let subtitulo = res.message || '';
    let claseBanner = 'alert-banner alert-info';

    if (!res.success) {
      titulo = 'Error en la importación';
      claseBanner = 'alert-banner alert-warning';
    } else if (insertados === 0 && errores > 0) {
      titulo = 'Importación con errores';
      claseBanner = 'alert-banner alert-warning';
    } else if (insertados === 0 && omitidos > 0) {
      titulo = 'Sin registros nuevos';
      subtitulo = 'Todos los registros del archivo ya existían en la base de datos.';
    }

    $('#resultadoImportarMensaje').attr('class', claseBanner).show();
    $('#resultadoImportarTitulo').text(titulo);
    $('#resultadoImportarSubtitulo').text(subtitulo);

    // Contadores
    $('#resultadoInsertados').text(insertados);
    $('#resultadoOmitidos').text(omitidos);
    $('#resultadoErrores').text(errores);

    // Hojas procesadas
    if (hojas.length > 0) {
      $('#resultadoHojas').text(hojas.join(', '));
      $('#resultadoHojasWrap').show();
    } else {
      $('#resultadoHojasWrap').hide();
    }

    // Detalles de errores
    if (detalles.length > 0) {
      const $lista = $('#resultadoDetalles').empty();
      detalles.forEach(function(detalle) {
        $lista.append($('<li>').text(detalle));
      });
      $('#resultadoDetallesWrap').show();
    } else {
      $('#resultadoDetallesWrap').hide();
    }

    $('#modalResultadoImportar').show();
  }

  $('#formImportar').on('submit', function(e) {
    e.preventDefault();

    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    if ($btn.prop('disabled')) return;

    const archivo = $('#archivoExcel')[0].files[0];

    if (!archivo) {
      mostrarResultadoImportar({ success: false, message: 'Seleccione un archivo Excel.' });
      return;
    }

    if (!/\.(xlsx|xls)$/i.test(archivo.name)) {
      mostrarResultadoImportar({ success: false, message: 'Solo se permiten archivos con extensión .xlsx o .xls.' });
      return;
    }

    if (archivo.size > 50 * 1024 * 1024) {
      mostrarResultadoImportar({ success: false, message: 'El archivo no debe superar los 50 MB.' });
      return;
    }

    const formData = new FormData(this);
    formData.append('importarProductos', '1');

    $btn.prop('disabled', true).text('Importando…');

    $.ajax({
      url: '?url=productosServicios&type=import',
      method: 'POST',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType: false,
      success: function(res) {
        mostrarResultadoImportar(res);

        if (res && res.success) {
          $('#modalImportar').hide();
          tabla.ajax.reload();
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX import error:', status, error, xhr.responseText);
        mostrarResultadoImportar({
          success: false,
          message: 'Error en la petición de importación. Revise la consola.'
        });
      },
      complete: function() {
        $btn.prop('disabled', false).text('Importar');
      }
    });
  });
});
