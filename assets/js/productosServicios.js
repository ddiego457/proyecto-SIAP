$(document).ready(function() {
  const currentUrl = window.location.pathname + window.location.search;

  // Notificación flotante (reutiliza los estilos .toast de siap.css)
  function showToast(message, type = 'success') {
    const $old = $('.toast');
    if ($old.length) $old.remove();

    const $toast = $('<div>', { class: 'toast toast-' + type, text: message });
    $('body').append($toast);

    setTimeout(() => {
      $toast.css({ opacity: '0', transition: 'opacity 0.3s' });
      setTimeout(() => $toast.remove(), 300);
    }, 4000);
  }

  // Validaciones en el cliente (plugin siapValidar).
  $('#formRegistroPS').siapValidar({
    id_partida: { regla: 'requerido', mensaje: 'Seleccione una partida presupuestaria.' },
    id_proveedor: { regla: 'requerido', mensaje: 'Seleccione un proveedor.' },
    nom_item: { regla: 'nombreProducto' },
    precio: { regla: 'precio' }
  });

  $('#formEditar').siapValidar({
    id_partida: { regla: 'requerido', mensaje: 'Seleccione una partida presupuestaria.' },
    id_proveedor: { regla: 'requerido', mensaje: 'Seleccione un proveedor.' },
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
              // Permanece en la vista de registro: se limpia el formulario
              // para poder registrar otro item sin salir de la página.
              showToast(res.message || 'Producto o servicio registrado', 'success');
              limpiarFormularioRegistro();
            } else {
              showToast(res.message || 'Error al registrar el item.', 'error');
            }
          } else if (res === true) {
            showToast('Registro guardado correctamente', 'success');
            limpiarFormularioRegistro();
          } else {
            showToast('Error al registrar el item.', 'error');
          }
          $btn.prop('disabled', false).text('✓ Registrar');
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', status, error, xhr.responseText);
        showToast('Error en la petición. Revise la consola.', 'error');
        $btn.prop('disabled', false).text('✓ Registrar');
      }
    });
  });

  // Restablece el formulario de registro tras un alta exitosa,
  // limpiando también los estados de validación (is-invalid / field-error).
  function limpiarFormularioRegistro() {
    const form = $('#formRegistroPS')[0];
    if (form) form.reset();

    // Quitar marcas de error del plugin siapValidar
    $('#formRegistroPS').find('.field-input, .field-select').each(function() {
      $(this).removeClass('is-invalid').removeAttr('aria-invalid').removeAttr('aria-describedby');
    });
    $('#formRegistroPS').find('small.field-error').remove();

    // Foco en el primer campo para un siguiente registro rápido
    $('#formRegistroPS').find('input[name="nom_item"]').trigger('focus');
  }

  $(document).on('click', '.btn-modificar', function() {
    const data = tabla.row($(this).closest('tr')).data();
    $('#edit_idItem').val(data.id_prod);
    $('#edit_partida_id').val(data.id_partida);
    $('#edit_proveedor_id').val(data.id_proveedor);
    $('#edit_nom_prod').val(data.nom_prod);
    $('#edit_precio').val(data.precio);
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
