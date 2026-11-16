$(function () {
    'use strict';

    const $form = $('#formRestablecerPassword');
    if (!$form.length) return;

    $form.siapValidar({
        nueva_password: {
            regla: 'contrasenia'
        },
        confirmar_password: {
            test: function (valor) {
                return String(valor) !== ''
                    && String(valor) === String($('#nueva_password').val());
            },
            mensaje: function () {
                return String($form.find('[name="confirmar_password"]').val()) === ''
                    ? 'Confirme la nueva contraseña.'
                    : 'Las contraseñas no coinciden.';
            }
        }
    });

    $form.on('input', '[name="nueva_password"]', function () {
        const $confirmacion = $form.find('[name="confirmar_password"]');
        if ($confirmacion.data('siap-tocado')) {
            $confirmacion.trigger('focusout');
        }
    });
});
