/*
 * Validaciones SIAP.
 * Requiere jQuery y debe cargarse después de constantes.js.
 *
 * Expone:
 *   - ValidacionesSIAP.validarEmail(...) y demás funciones booleanas.
 *   - El plugin jQuery $.fn.siapValidar para formularios.
 *
 * Ejemplo:
 *   $('#formRegistro').siapValidar({
 *       email: { regla: 'email' }
 *   });
 */
(function ($) {
    'use strict';

    if (!$) {
        if (window.console && console.error) {
            console.error('validaciones.js: jQuery no está disponible.');
        }
        return;
    }

    if (typeof regexEmail === 'undefined' || typeof regexNombre === 'undefined') {
        if (window.console && console.error) {
            console.error('validaciones.js: cargue constantes.js antes de este archivo.');
        }
        return;
    }

    // ─── Helpers ───────────────────────────────────────────────
    function textoDe(valor) {
        if (valor === null || typeof valor === 'undefined') return '';
        return String(valor);
    }

    function recortar(valor) {
        return textoDe(valor).replace(/^\s+|\s+$/g, '');
    }

    // ─── Validadores booleanos ─────────────────────────────────
    function validarEmail(valor) {
        return regexEmail.test(recortar(valor));
    }

    function validarNombre(valor) {
        return regexNombre.test(recortar(valor));
    }

    function validarContrasenia(valor) {
        return regexContrasenia.test(textoDe(valor));
    }

    function validarTelefono(valor) {
        return regexTelf.test(recortar(valor));
    }

    function validarNombrePresupuesto(valor) {
        return regexNomPresupuesto.test(recortar(valor));
    }

    function validarCantidad(valor) {
        return regexCantidad.test(recortar(valor));
    }

    function validarAnio(valor) {
        return regexAnio.test(recortar(valor));
    }

    function validarPeriodo(valor) {
        return regexPeriodo.test(recortar(valor));
    }

    function validarPresupuesto(valor) {
        return regexPresupuesto.test(recortar(valor));
    }

    function validarDescripcionPresupuesto(valor) {
        return regexDescrPresupuesto.test(recortar(valor));
    }

    function validarPrecio(valor) {
        return regexPrecios.test(recortar(valor));
    }

    function validarDescripcionRol(valor) {
        return regexDescripcionRol.test(recortar(valor));
    }

    function validarRequerido(valor) {
        return recortar(valor).length > 0;
    }

    // ─── Validadores por módulo (ValidationTrait) ──────────────
    function validarNombreDependencia(valor) {
        return regexNombreDependencia.test(recortar(valor));
    }

    function validarNombreProveedor(valor) {
        return regexNombreProveedor.test(recortar(valor));
    }

    function validarDescripcionProveedor(valor) {
        var rec = recortar(valor);
        // Campo opcional: vacío siempre es válido.
        if (rec === '') return true;
        return regexDescripcionProveedor.test(rec);
    }

    function validarNombreProducto(valor) {
        return regexNombreProducto.test(recortar(valor));
    }

    function validarCodigoPartida(valor) {
        return regexCodigoPartida.test(recortar(valor));
    }

    function validarTasa(valor) {
        return regexTasa.test(recortar(valor));
    }

    function validarCantidadMes(valor) {
        var rec = recortar(valor);
        // Campo opcional: vacío siempre es válido.
        if (rec === '') return true;
        return regexCantidadMes.test(rec);
    }

    function validarFechaISO(valor) {
        return regexFechaISO.test(recortar(valor));
    }

    // ─── Mensajes por regla ────────────────────────────────────
    var mensajes = {
        email: 'Ingrese un correo electrónico válido (ej. usuario@dominio.com).',
        nombre: 'El nombre solo puede contener letras, espacios, puntos, guiones y apóstrofes, entre 3 y 100 caracteres.',
        contrasenia: 'La contraseña debe tener al menos 4 caracteres y solo puede contener letras, números y los caracteres _ . -',
        telefono: 'Formato de teléfono inválido. Solo se permiten dígitos, "+" al inicio y "-". Ej: +58-212-5551234',
        nomPresupuesto: 'Formato esperado: nombre|unidad|',
        cantidad: 'La cantidad debe ser un número de 4 dígitos.',
        anio: 'El año debe tener 4 dígitos entre 2000 y 2100.',
        periodo: 'Use el formato AAAA-MM-DD.',
        presupuesto: 'El presupuesto debe tener entre 3 y 10 dígitos.',
        descrPresupuesto: 'La descripción no puede contener etiquetas HTML ni caracteres de control, máximo 150 caracteres.',
        precio: 'El precio debe ser un número positivo con hasta 2 decimales (ej. 5.50).',
        rol: 'La descripción del rol solo puede contener letras, números, espacios y los caracteres . , - _ / ( ), máximo 70 caracteres.',
        requerido: 'Este campo es obligatorio.',
        nombreDependencia: 'El nombre de la dependencia debe tener entre 3 y 100 caracteres y solo letras, números, espacios, puntos, guiones y paréntesis.',
        nombreProveedor: 'El nombre del proveedor debe tener entre 3 y 100 caracteres y solo letras, números, espacios, puntos, guiones y paréntesis.',
        descripcionProveedor: 'La descripción no puede contener etiquetas HTML ni caracteres de control, máximo 150 caracteres.',
        nombreProducto: 'El nombre del producto solo puede contener letras, números, espacios y los caracteres . , - ( ) / % " |, entre 2 y 150 caracteres.',
        codigoPartida: 'El código de la partida solo puede contener números y puntos, máximo 10 caracteres. Ej: 401 o 4.01',
        tasa: 'La tasa debe ser un número positivo con hasta 2 decimales (ej. 36.50).',
        cantidadMes: 'La cantidad debe ser un número entero positivo, máximo 9 dígitos.',
        fecha: 'Use el formato AAAA-MM-DD.'
    };

    var reglas = {
        email: { validar: validarEmail, mensaje: mensajes.email },
        nombre: { validar: validarNombre, mensaje: mensajes.nombre },
        contrasenia: { validar: validarContrasenia, mensaje: mensajes.contrasenia },
        telefono: { validar: validarTelefono, mensaje: mensajes.telefono },
        nomPresupuesto: { validar: validarNombrePresupuesto, mensaje: mensajes.nomPresupuesto },
        cantidad: { validar: validarCantidad, mensaje: mensajes.cantidad },
        anio: { validar: validarAnio, mensaje: mensajes.anio },
        periodo: { validar: validarPeriodo, mensaje: mensajes.periodo },
        presupuesto: { validar: validarPresupuesto, mensaje: mensajes.presupuesto },
        descrPresupuesto: { validar: validarDescripcionPresupuesto, mensaje: mensajes.descrPresupuesto },
        precio: { validar: validarPrecio, mensaje: mensajes.precio },
        rol: { validar: validarDescripcionRol, mensaje: mensajes.rol },
        requerido: { validar: validarRequerido, mensaje: mensajes.requerido },
        nombreDependencia: { validar: validarNombreDependencia, mensaje: mensajes.nombreDependencia },
        nombreProveedor: { validar: validarNombreProveedor, mensaje: mensajes.nombreProveedor },
        descripcionProveedor: { validar: validarDescripcionProveedor, mensaje: mensajes.descripcionProveedor },
        nombreProducto: { validar: validarNombreProducto, mensaje: mensajes.nombreProducto },
        codigoPartida: { validar: validarCodigoPartida, mensaje: mensajes.codigoPartida },
        tasa: { validar: validarTasa, mensaje: mensajes.tasa },
        cantidadMes: { validar: validarCantidadMes, mensaje: mensajes.cantidadMes },
        fecha: { validar: validarFechaISO, mensaje: mensajes.fecha }
    };

    // ─── Plugin jQuery ─────────────────────────────────────────
    function generarIdError($campo) {
        var $form = $campo.closest('form');
        var idForm = ($form.attr('id') || 'siap-form').replace(/[^A-Za-z0-9_-]/g, '');
        var idCampo = ($campo.attr('id') || $campo.attr('name') || 'campo').replace(/[^A-Za-z0-9_-]/g, '');
        return 'siap-error-' + idForm + '-' + idCampo;
    }

    function mostrarError($campo, mensaje) {
        var $grupo = $campo.closest('.field-group');
        var id = $campo.data('siap-error-id');

        if (!id) {
            id = generarIdError($campo);
            $campo.data('siap-error-id', id);
        }

        $campo.addClass('is-invalid').attr('aria-invalid', 'true').attr('aria-describedby', id);

        var $error = $grupo.find('small.field-error[data-siap-error-id="' + id + '"]');
        if (!$error.length) {
            $error = $('<small>', {
                class: 'field-error',
                id: id,
                'data-siap-error-id': id,
                'aria-live': 'polite'
            });
            if ($grupo.length) {
                $grupo.append($error);
            } else {
                $campo.after($error);
            }
        }
        $error.text(mensaje);
    }

    function limpiarError($campo) {
        var id = $campo.data('siap-error-id');
        $campo.removeClass('is-invalid').removeAttr('aria-invalid').removeAttr('aria-describedby');

        if (id) {
            var $grupo = $campo.closest('.field-group');
            $grupo.find('small.field-error[data-siap-error-id="' + id + '"]').remove();
            $campo.removeData('siap-error-id');
        }
    }

    function mensajeDe(opciones) {
        if (opciones && opciones.mensaje) return opciones.mensaje;
        var nombreRegla = (opciones && opciones.regla) || 'requerido';
        var regla = reglas[nombreRegla];
        return (regla && regla.mensaje) || 'El valor ingresado no es válido.';
    }

    function validarCampo($campo, opciones, mostrar) {
        var valor = $campo.val();

        if (opciones.omitirVacio && recortar(valor) === '') {
            limpiarError($campo);
            return true;
        }

        var valido;
        if (typeof opciones.test === 'function') {
            valido = !!opciones.test(valor, $campo);
        } else {
            var regla = reglas[opciones.regla || 'requerido'];
            valido = !!(regla && regla.validar(valor));
        }

        if (valido) {
            limpiarError($campo);
        } else if (mostrar) {
            mostrarError($campo, mensajeDe(opciones));
        }

        return valido;
    }

    function instanciar($form, configuracion) {
        var config = {};

        function actualizar(nueva) {
            config = $.extend(true, {}, nueva || {});
        }

        function limpiarTodo() {
            $form.find(':input').each(function () {
                var $campo = $(this);
                $campo.removeClass('is-invalid').removeAttr('aria-invalid').removeAttr('aria-describedby');
                $campo.removeData('siap-error-id');
                $campo.removeData('siap-tocado');
            });
            $form.find('small.field-error').remove();
        }

        function validarTodo() {
            var $invalido = null;
            $.each(config, function (nombre, opciones) {
                var $campo = $form.find('[name="' + nombre + '"]').first();
                if (!$campo.length) return;
                var valido = validarCampo($campo, opciones, true);
                if (!valido && !$invalido) {
                    $invalido = $campo;
                }
            });
            return !$invalido;
        }

        actualizar(configuracion);

        // Evita que el navegador muestre sus mensajes nativos y envíe el formulario.
        $form.prop('novalidate', true);

        // Validación al salir del campo.
        $form.on('focusout.siapValidar', ':input[name]', function () {
            var $campo = $(this);
            var opciones = config[$campo.attr('name')];
            if (!opciones) return;
            $campo.data('siap-tocado', true);
            validarCampo($campo, opciones, true);
        });

        // Después del primer blur, limpia el error en cuanto el valor cambia.
        $form.on('input.siapValidar change.siapValidar', ':input[name]', function () {
            var $campo = $(this);
            var opciones = config[$campo.attr('name')];
            if (!opciones || !$campo.data('siap-tocado')) return;
            validarCampo($campo, opciones, true);
        });

        $form.on('reset.siapValidar', function () {
            limpiarTodo();
        });

        $form.on('submit.siapValidar', function (e) {
            var $invalido = null;
            $.each(config, function (nombre, opciones) {
                var $campo = $form.find('[name="' + nombre + '"]').first();
                if (!$campo.length) return;
                var valido = validarCampo($campo, opciones, true);
                if (!valido && !$invalido) {
                    $invalido = $campo;
                }
            });

            if ($invalido) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $invalido.trigger('focus');
            }
        });

        var api = {
            configurar: actualizar,
            limpiar: limpiarTodo,
            validarTodo: validarTodo
        };

        $form.data('siapValidacion', api);
        return api;
    }

    $.fn.siapValidar = function (opcion) {
        if (typeof opcion === 'string') {
            return this.each(function () {
                var api = $(this).data('siapValidacion');
                if (!api) return;
                if (opcion === 'limpiar') api.limpiar();
            });
        }

        return this.each(function () {
            var $form = $(this);
            var api = $form.data('siapValidacion');
            if (api) {
                api.configurar(opcion);
            } else {
                instanciar($form, opcion);
            }
        });
    };

    // ─── API pública ───────────────────────────────────────────
    window.ValidacionesSIAP = Object.freeze({
        mensajes: mensajes,
        reglas: reglas,
        validarEmail: validarEmail,
        validarNombre: validarNombre,
        validarContrasenia: validarContrasenia,
        validarTelefono: validarTelefono,
        validarNombrePresupuesto: validarNombrePresupuesto,
        validarCantidad: validarCantidad,
        validarAnio: validarAnio,
        validarPeriodo: validarPeriodo,
        validarFechaISO: validarFechaISO,
        validarPresupuesto: validarPresupuesto,
        validarDescripcionPresupuesto: validarDescripcionPresupuesto,
        validarPrecio: validarPrecio,
        validarDescripcionRol: validarDescripcionRol,
        validarRequerido: validarRequerido,
        validarNombreDependencia: validarNombreDependencia,
        validarNombreProveedor: validarNombreProveedor,
        validarDescripcionProveedor: validarDescripcionProveedor,
        validarNombreProducto: validarNombreProducto,
        validarCodigoPartida: validarCodigoPartida,
        validarTasa: validarTasa,
        validarCantidadMes: validarCantidadMes,
        validarCampo: function (campo, valor) {
            var regla = reglas[campo];
            return !!(regla && regla.validar(valor));
        },
        // Utilidades para campos dinámicos (ej. inputs generados por DataTables).
        marcarError: function ($campo, mensaje) {
            if ($campo && $campo.length) mostrarError($campo, mensaje);
        },
        limpiarError: function ($campo) {
            if ($campo && $campo.length) limpiarError($campo);
        }
    });
})(window.jQuery);
