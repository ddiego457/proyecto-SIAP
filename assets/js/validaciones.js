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
        // Campo obligatorio: debe contener texto real (al menos una letra).
        return regexDescripcionProveedor.test(recortar(valor));
    }

    function validarNombreProducto(valor) {
        return regexNombreProducto.test(recortar(valor));
    }

    function validarCodigoPartida(valor) {
        return regexCodigoPartida.test(recortar(valor));
    }

    function validarUnidadMedida(valor) {
        return regexUnidadMedida.test(recortar(valor));
    }

    function validarTasa(valor) {
        return regexTasa.test(recortar(valor));
    }

    function validarCantidadMes(valor) {
        const rec = recortar(valor);
        // Campo opcional: vacío siempre es válido.
        if (rec === '') return true;
        return regexCantidadMes.test(rec);
    }

    function validarFechaISO(valor) {
        return regexFechaISO.test(recortar(valor));
    }

    // ─── Mensajes por regla ────────────────────────────────────
    const mensajes = {
        email: 'Ingrese un correo electrónico válido (ej. usuario@dominio.com).',
        nombre: 'El nombre solo puede contener letras, espacios, puntos, guiones y apóstrofes, entre 3 y 100 caracteres.',
        contrasenia: 'La contraseña debe tener al menos 8 caracteres (letras, números, con o sin caracteres especiales, sin espacios).',
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
        descripcionProveedor: 'La descripción es obligatoria y debe contener texto (al menos una letra), sin etiquetas HTML ni caracteres de control, entre 3 y 150 caracteres.',
        nombreProducto: 'El nombre del producto solo puede contener letras, números, espacios y los caracteres . , - ( ) / % " |, entre 2 y 150 caracteres.',
        codigoPartida: 'El código de la partida solo puede contener números y puntos, máximo 10 caracteres. Ej: 401 o 4.01',
        unidadMedida: 'La unidad de medida solo puede contener letras, números, espacios y los caracteres . , - ( ) /, entre 2 y 40 caracteres.',
        tasa: 'La tasa debe ser un número positivo con hasta 2 decimales (ej. 36.50).',
        cantidadMes: 'La cantidad debe ser un número entero positivo, máximo 9 dígitos.',
        fecha: 'Use el formato AAAA-MM-DD.'
    };

    const reglas = {
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
        unidadMedida: { validar: validarUnidadMedida, mensaje: mensajes.unidadMedida },
        tasa: { validar: validarTasa, mensaje: mensajes.tasa },
        cantidadMes: { validar: validarCantidadMes, mensaje: mensajes.cantidadMes },
        fecha: { validar: validarFechaISO, mensaje: mensajes.fecha }
    };

    // ─── Plugin jQuery ─────────────────────────────────────────
    function generarIdError($campo) {
        const $form = $campo.closest('form');
        const idForm = ($form.attr('id') || 'siap-form').replace(/[^A-Za-z0-9_-]/g, '');
        const idCampo = ($campo.attr('id') || $campo.attr('name') || 'campo').replace(/[^A-Za-z0-9_-]/g, '');
        return 'siap-error-' + idForm + '-' + idCampo;
    }

    // Ubica el <small> con el mensaje de un campo. El mensaje se coloca dentro
    // de .field-group (formularios con etiqueta) o fuera del bloque .field
    // (vistas tipo login/recuperación) para no desplazar el icono: en ambos
    // casos se busca por su id, que es único dentro del formulario.
    function elementoError($campo, id) {
        const $form = $campo.closest('form');
        const $busqueda = $form.length ? $form : $(document);
        return $busqueda.find('small.field-error[data-siap-error-id="' + id + '"]');
    }

    function mostrarError($campo, mensaje) {
        let id = $campo.data('siap-error-id');

        if (!id) {
            id = generarIdError($campo);
            $campo.data('siap-error-id', id);
        }

        $campo.addClass('is-invalid').attr('aria-invalid', 'true').attr('aria-describedby', id);

        let $error = elementoError($campo, id);
        if (!$error.length) {
            $error = $('<small>', {
                class: 'field-error',
                id: id,
                'data-siap-error-id': id,
                'aria-live': 'polite'
            });
            const $grupo = $campo.closest('.field-group');
            if ($grupo.length) {
                $grupo.append($error);
            } else {
                // Se coloca FUERA del bloque .field para que al aparecer el mensaje
                // no crezca su altura y se desplace el icono absoluto (top:50%).
                const $bloque = $campo.closest('.field');
                ($bloque.length ? $bloque : $campo).after($error);
            }
        }
        $error.text(mensaje);
    }

    function limpiarError($campo) {
        const id = $campo.data('siap-error-id');
        $campo.removeClass('is-invalid').removeAttr('aria-invalid').removeAttr('aria-describedby');

        if (id) {
            elementoError($campo, id).remove();
            $campo.removeData('siap-error-id');
        }
    }

    function mensajeDe(opciones) {
        // 'mensaje' puede ser una cadena o una función que devuelva la cadena:
        // útil cuando el mismo campo puede fallar por varios motivos
        // (formato, orden, año fiscal, etc.) y cada uno tiene su mensaje.
        if (opciones && typeof opciones.mensaje === 'function') return opciones.mensaje();
        if (opciones && opciones.mensaje) return opciones.mensaje;
        const nombreRegla = (opciones && opciones.regla) || 'requerido';
        const regla = reglas[nombreRegla];
        return (regla && regla.mensaje) || 'El valor ingresado no es válido.';
    }

    function validarCampo($campo, opciones, mostrar) {
        const valor = $campo.val();

        if (opciones.omitirVacio && recortar(valor) === '') {
            limpiarError($campo);
            return true;
        }

        let valido;
        if (typeof opciones.test === 'function') {
            valido = !!opciones.test(valor, $campo);
        } else {
            const regla = reglas[opciones.regla || 'requerido'];
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
        let config = {};

        function actualizar(nueva) {
            config = $.extend(true, {}, nueva || {});
        }

        function limpiarTodo() {
            $form.find(':input').each(function () {
                const $campo = $(this);
                $campo.removeClass('is-invalid').removeAttr('aria-invalid').removeAttr('aria-describedby');
                $campo.removeData('siap-error-id');
                $campo.removeData('siap-tocado');
            });
            $form.find('small.field-error').remove();
        }

        function validarTodo() {
            let $invalido = null;
            $.each(config, function (nombre, opciones) {
                const $campo = $form.find('[name="' + nombre + '"]').first();
                if (!$campo.length) return;
                const valido = validarCampo($campo, opciones, true);
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
            const $campo = $(this);
            const opciones = config[$campo.attr('name')];
            if (!opciones) return;
            $campo.data('siap-tocado', true);
            validarCampo($campo, opciones, true);
        });

        // Después del primer blur, limpia el error en cuanto el valor cambia.
        $form.on('input.siapValidar change.siapValidar', ':input[name]', function () {
            const $campo = $(this);
            const opciones = config[$campo.attr('name')];
            if (!opciones || !$campo.data('siap-tocado')) return;
            validarCampo($campo, opciones, true);
        });

        $form.on('reset.siapValidar', function () {
            limpiarTodo();
        });

        $form.on('submit.siapValidar', function (e) {
            let $invalido = null;
            $.each(config, function (nombre, opciones) {
                const $campo = $form.find('[name="' + nombre + '"]').first();
                if (!$campo.length) return;
                const valido = validarCampo($campo, opciones, true);
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

        const api = {
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
                const api = $(this).data('siapValidacion');
                if (!api) return;
                if (opcion === 'limpiar') api.limpiar();
            });
        }

        return this.each(function () {
            const $form = $(this);
            const api = $form.data('siapValidacion');
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
            const regla = reglas[campo];
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
