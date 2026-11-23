/*
 * Constantes de validación SIAP.
 * Se definen como RegExp reales para poder usarlos directamente con .test().
 * Coinciden con las expresiones de app/helpers/ValidationTrait.php para
 * evitar errores falsos en el cliente.
 */

const regexEmail = /^(?=.{1,150}$)[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

const regexNombre = /^[A-Za-záéíóúüÁÉÍÓÚÜñÑ\s.'-]{3,100}$/;

// Contraseña (alta/cambio): mín. 8 caracteres SIN espacios, al menos un
// número y al menos un carácter especial. Coincide con
// ValidationTrait::$expPassword.
const regexContrasenia = /^\S{8,100}$/u;

const regexTelf = /^\+?[0-9][0-9\s-]{6,18}$/;

const regexNomPresupuesto = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s]{5,150}\|[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s]{2,30}\|$/;

const regexCantidad = /^\d{4}$/;

const regexAnio = /^(20\d{2}|2100)$/;

const regexFechaISO = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[0-1])$/;

const regexPeriodo = regexFechaISO;

const regexPresupuesto = /^\d{3,10}$/;

const regexDescrPresupuesto = /^[^<>\x00-\x1F\x7F]{1,150}$/;

const regexPrecios = /^(?!0+(?:\.0+)?$)\d{1,9}(?:\.\d{1,2})?$/;

const regexDescripcionRol = /^[A-Za-z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,\-_\/()]{1,70}$/;

const regexNombreDependencia = /^(?!\d)[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.\-&()]{3,100}$/;

const regexNombreProveedor = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.\-&()]{3,100}$/;

// Descripción del proveedor: obligatoria, mínimo 3 caracteres y con al menos
// una letra (texto real). Sin etiquetas HTML ni caracteres de control, máx. 150.
const regexDescripcionProveedor = /^(?=[\s\S]*[A-Za-záéíóúüÁÉÍÓÚÜñÑ])[^\x00-\x1F\x7F<>]{3,150}$/;

const regexNombreProducto = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.,\-()/"%|]{2,150}$/;

const regexCodigoPartida = /^(?=.{1,10}$)[0-9]+(?:\.[0-9]+)?$/;

// Unidad de medida del catálogo: letras, números, espacios y . , - ( ) /,
// entre 2 y 40 caracteres. Coincide con ValidationTrait::$expUnidadMedida.
const regexUnidadMedida = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.,\-()/]{2,40}$/;

const regexTasa = /^(?!0+(?:\.0+)?$)\d{1,10}(?:\.\d{1,2})?$/;

const regexCantidadMes = /^\d{1,9}$/;

/*
 * Restablece un formulario tras un alta exitosa para poder registrar otro
 * item sin salir de la vista. Limpia también los estados de validación
 * del plugin siapValidar (clase .is-invalid y mensajes <small class="field-error">).
 *
 *  siapResetFormulario('#formRegistro', 'input[name="nombre"]');
 *  - selectorForm: selector del formulario.
 *  - selectorFoco: (opcional) campo donde dejar el foco tras limpiar.
 */
function siapResetFormulario(selectorForm, selectorFoco) {
    const form = document.querySelector(selectorForm);
    if (!form) return;

    form.reset();

    // Quitar marcas de error del plugin siapValidar
    form.querySelectorAll('.field-input, .field-select').forEach(function (campo) {
        campo.classList.remove('is-invalid');
        campo.removeAttribute('aria-invalid');
        campo.removeAttribute('aria-describedby');
    });
    form.querySelectorAll('small.field-error').forEach(function (msg) {
        msg.remove();
    });

    let foco = selectorFoco ? form.querySelector(selectorFoco) : null;
    if (!foco) foco = form.querySelector('input:not([type="hidden"]), select, textarea');
    if (foco) foco.focus();
}
