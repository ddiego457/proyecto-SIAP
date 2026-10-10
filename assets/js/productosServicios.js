$(document).ready(function() {
  const currentUrl = window.location.pathname + window.location.search;
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
      { data: 'id_prod' },
      { data: 'cod_partida' },
      { data: 'proveedor' },
      { data: 'nom_prod' },
      { data: 'precio' },
      { data: null, render: (d) => {
          return (d.estado == 1)
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-danger">Inactivo</span>';
        }
      },
      { data: null, render: (d) => {
          return `
            <button value="${d.id_prod}" class="btn btn-sm btn-edit btn-modificar" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            <button value="${d.id_prod}" class="btn btn-danger btn-sm btn-eliminar" aria-label="Eliminar" title="Eliminar"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
          `;
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
          if (res && typeof res === 'object') {
            if (res.success) {
              alert(res.message || 'Registro guardado correctamente');
              if (res.redirect) window.location.href = res.redirect; else window.location.href = '?url=productosServicios&type=main';
            } else {
              alert(res.message || 'Error al registrar el item.');
              $btn.prop('disabled', false).text('✓ Registrar');
            }
          } else if (res === true) {
            alert('Registro guardado correctamente');
            window.location.href = '?url=productosServicios&type=main';
          } else {
            alert(res.message || 'Error al registrar el item.');
            $btn.prop('disabled', false).text('✓ Registrar');
          }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', status, error, xhr.responseText);
        alert('Error en la petición. Revise la consola.');
        $btn.prop('disabled', false).text('✓ Registrar');
      }
    });
  });

  $(document).on('click', '.btn-modificar', function() {
    const data = tabla.row($(this).closest('tr')).data();
    $('#edit_idItem').val(data.id_prod);
    $('#edit_partida_id').val(data.id_partida);
    $('#edit_proveedor_id').val(data.id_proveedor);
    $('#edit_nom_prod').val(data.nom_prod);
    $('#edit_precio').val(data.precio);
    $('#modalEditar').show();
  });

  $('#btnCerrarModal, #btnCerrarModal2').on('click', function() {
    $('#modalEditar').hide();
  });

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
        if (res && res.success) {
          alert(res.message || 'Registro actualizado exitosamente');
          $('#modalEditar').hide();
          tabla.ajax.reload();
        } else {
          alert(res.message || 'Error al actualizar.');
        }
      },
      error: function(xhr, status, error) {
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
      $('#modalPartida, #modalPartidas, #modalEditarPartida').hide();
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

  $(document).on('click', '.btn-eliminar', function() {
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
});
