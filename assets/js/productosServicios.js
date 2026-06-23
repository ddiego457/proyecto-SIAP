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
      { data: 'id_item' },
      { data: 'cod_partida' },
      { data: 'proveedor' },
      { data: 'nom_item' },
      { data: 'precio' },
      { data: null, render: (d) => {
          return (d.estado == 1)
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-danger">Inactivo</span>';
        }
      },
      { data: null, render: (d) => {
          return `
            <button value="${d.id_item}" class="btn btn-sm btn-modificar text-white" style="margin-right:6px; background-color:#5bc0de; border-color:#46b8da;">✏️</button>
            <button value="${d.id_item}" class="btn btn-danger btn-sm btn-eliminar" aria-label="Inhabilitar">🗑️</button>
          `;
        }
      }
    ],
    autoWidth: false,
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
    }
  });

  $('#selectPartida').on('change', function() {
    tabla.ajax.reload();
  });

  $('#formRegistroPS').on('submit', function(e) {
    e.preventDefault();
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
            }
          } else if (res === true) {
            alert('Registro guardado correctamente');
            window.location.href = '?url=productosServicios&type=main';
          } else {
            alert(res.message || 'Error al registrar el item.');
          }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', status, error, xhr.responseText);
        alert('Error en la petición. Revise la consola.');
      }
    });
  });

  $(document).on('click', '.btn-modificar', function() {
    const data = tabla.row($(this).closest('tr')).data();
    $('#edit_idItem').val(data.id_item);
    $('#edit_partida_id').val(data.id_partida);
    $('#edit_proveedor_id').val(data.id_proveedor);
    $('#edit_nom_item').val(data.nom_item);
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
