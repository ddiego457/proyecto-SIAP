$(function () {
    'use strict';

    const $form = $('#formRecuperar');
    if (!$form.length || typeof $form.siapValidar !== 'function') return;

    $form.siapValidar({
        dato: {
            // El campo admite correo o usuario: el formato de correo solo se
            // exige cuando el valor contiene "@" (regexEmail de validaciones.js).
            test: function (valor) {
                const valorRecortado = String(valor === null || valor === undefined ? '' : valor)
                    .replace(/^\s+|\s+$/g, '');

                if (valorRecortado === '') return false;
                if (valorRecortado.indexOf('@') === -1) return true;

                return ValidacionesSIAP.validarEmail(valorRecortado);
            },
            mensaje: function () {
                const valor = String($form.find('[name="dato"]').val() || '')
                    .replace(/^\s+|\s+$/g, '');
                return valor === ''
                    ? 'Este campo es obligatorio.'
                    : ValidacionesSIAP.mensajes.email;
            }
        }
    });
});
