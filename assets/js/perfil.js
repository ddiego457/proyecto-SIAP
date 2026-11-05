/* ============================================================
   FOTO DE PERFIL + MENÚ DE USUARIO DEL TOPBAR
   Se carga en todas las páginas (ver foot.php).

   1) Menú de usuario: #topbarUserBtn despliega #topbarUserMenu
      con "Editar foto" (abre el modal) y "Cerrar sesión".
   2) Modal de foto: al presionar "Editar foto" se abre
      #modalFotoPerfil para cambiar o eliminar la foto del
      responsable logueado.
   El servidor recibe la imagen en ?url=perfil&type=main
   ============================================================ */
$(document).ready(function() {
    const $btnUser = $('#topbarUserBtn');
    const $menu = $('#topbarUserMenu');
    const $modal = $('#modalFotoPerfil');
    const $avatar = $('.topbar-user-avatar').first();
    const $preview = $('#perfilPreview');
    const $input = $('#inputFotoPerfil');
    const $btnGuardar = $('#btnGuardarFoto');
    const $btnEliminar = $('#btnEliminarFoto');

    const urlPerfil = '?url=perfil&type=main';
    const maximoBytes = 2 * 1024 * 1024;
    const tiposAdmitidos = ['image/jpeg', 'image/png', 'image/webp'];

    // HTML original del avatar (imagen o letra) para restaurarlo al eliminar la foto
    const avatarOriginalHtml = $avatar.html();
    let archivoSeleccionado = null;

    // ─── MENÚ DESPLEGABLE DEL TOPBAR ──────────────────────────
    function abrirMenu() {
        $menu.show();
        $btnUser.attr('aria-expanded', 'true');
    }

    function cerrarMenu() {
        $menu.hide();
        $btnUser.attr('aria-expanded', 'false');
    }

    $btnUser.on('click', function(e) {
        e.stopPropagation();
        if ($menu.is(':visible')) {
            cerrarMenu();
        } else {
            abrirMenu();
        }
    });

    // Cerrar al hacer clic fuera del menú
    $(document).on('click', function(e) {
        if ($menu.is(':visible') && !$(e.target).closest('.topbar-user').length) {
            cerrarMenu();
        }
    });

    // Cerrar con Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            if ($modal.is(':visible')) {
                cerrarModal();
            }
            cerrarMenu();
        }
    });

    // La opción "Editar foto" cierra el menú y abre el modal
    $('#btnEditarFotoMenu').on('click', function() {
        cerrarMenu();
        abrirModal();
    });

    // ─── MODAL DE FOTO DE PERFIL ──────────────────────────────
    // ¿Tiene foto actualmente? (el server-side ya decide img vs letra)
    function tieneFoto() {
        return $avatar.find('img').length > 0;
    }

    // Rellena el preview con la foto actual o con la letra inicial
    function previewActual() {
        if (tieneFoto()) {
            $preview.html('<img src="' + $avatar.find('img').attr('src') + '" alt="Foto actual">');
        } else {
            $preview.text($avatar.text().trim());
        }
    }

    function abrirModal() {
        previewActual();
        archivoSeleccionado = null;
        $input.val('');
        $btnGuardar.hide();
        $btnEliminar.toggle(tieneFoto());
        $modal.show();
    }

    function cerrarModal() {
        $modal.hide();
        archivoSeleccionado = null;
        $input.val('');
        $btnGuardar.hide();
    }

    // Cerrar: botón X, Cancelar o clic fuera del panel
    $('#btnCerrarFotoPerfil, #btnCancelarFoto').on('click', cerrarModal);
    $modal.on('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    // Seleccionar imagen desde el equipo
    $('#btnElegirFoto').on('click', function() { $input.trigger('click'); });

    $input.on('change', function() {
        const file = this.files && this.files[0];
        if (!file) return;

        if (tiposAdmitidos.indexOf(file.type) === -1) {
            alert('Formato no admitido. Use JPG, PNG o WEBP.');
            this.value = '';
            return;
        }
        if (file.size > maximoBytes) {
            alert('La imagen no debe superar los 2 MB.');
            this.value = '';
            return;
        }

        // La imagen debe poder decodificarse (detecta archivos corruptos
        // en el cliente, igual que hace getimagesize() en el servidor)
        const input = this;
        const urlPreview = URL.createObjectURL(file);
        const prueba = new Image();

        prueba.onload = function() {
            archivoSeleccionado = file;
            $preview.html('<img src="' + urlPreview + '" alt="Vista previa">');
            $btnGuardar.show();
        };

        prueba.onerror = function() {
            URL.revokeObjectURL(urlPreview);
            alert('El archivo seleccionado no es una imagen válida.');
            input.value = '';
            archivoSeleccionado = null;
            $btnGuardar.hide();
        };

        prueba.src = urlPreview;
    });

    // Guardar (subir) la foto
    $btnGuardar.on('click', function() {
        if (!archivoSeleccionado) {
            alert('Seleccione una imagen primero.');
            return;
        }
        if ($btnGuardar.prop('disabled')) return;
        $btnGuardar.prop('disabled', true).text('Guardando…');

        const formData = new FormData();
        formData.append('updateFoto', true);
        formData.append('foto', archivoSeleccionado);

        $.ajax({
            url: urlPerfil,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(res) {
                if (res && typeof res === 'object' && res.success) {
                    // Actualizar el avatar del topbar sin recargar la página
                    $avatar.html('<img src="' + res.url + '" alt="Foto de perfil">');
                    alert(res.message || 'Foto de perfil actualizada.');
                    cerrarModal();
                } else {
                    alert((res && res.message) || 'Error al guardar la foto.');
                }
                $btnGuardar.prop('disabled', false).text('Guardar');
            },
            error: function() {
                alert('Error de conexión. Intente de nuevo.');
                $btnGuardar.prop('disabled', false).text('Guardar');
            }
        });
    });

    // Eliminar la foto (vuelve a mostrarse la letra inicial)
    $btnEliminar.on('click', function() {
        if (!confirm('¿Eliminar su foto de perfil?')) return;
        if ($btnEliminar.prop('disabled')) return;
        $btnEliminar.prop('disabled', true).text('Eliminando…');

        $.ajax({
            url: urlPerfil,
            method: 'POST',
            dataType: 'json',
            data: { deleteFoto: true },
            success: function(res) {
                if (res && typeof res === 'object' && res.success) {
                    $avatar.html(avatarOriginalHtml);
                    alert(res.message || 'Foto de perfil eliminada.');
                    cerrarModal();
                } else {
                    alert((res && res.message) || 'Error al eliminar la foto.');
                }
                $btnEliminar.prop('disabled', false).text('Eliminar foto');
            },
            error: function() {
                alert('Error de conexión. Intente de nuevo.');
                $btnEliminar.prop('disabled', false).text('Eliminar foto');
            }
        });
    });

});
