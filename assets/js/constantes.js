/*
 * Constantes de validación SIAP.
 * Se definen como RegExp reales para poder usarlos directamente con .test().
 * Coinciden con las expresiones de app/helpers/ValidationTrait.php para
 * evitar errores falsos en el cliente.
 */

const regexEmail = /^(?=.{1,150}$)[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

const regexNombre = /^[A-Za-záéíóúüÁÉÍÓÚÜñÑ\s.'-]{3,100}$/;

const regexContrasenia = /^[A-Za-z0-9_.-]{4,}$/;

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

const regexNombreDependencia = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.\-&()]{3,100}$/;

const regexNombreProveedor = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.\-&()]{3,100}$/;

const regexDescripcionProveedor = /^[^<>\x00-\x1F\x7F]{0,150}$/;

const regexNombreProducto = /^[A-Za-z0-9áéíóúüÁÉÍÓÚÜñÑ\s.,\-()/"%|]{2,150}$/;

const regexCodigoPartida = /^(?=.{1,10}$)[0-9]+(?:\.[0-9]+)?$/;

const regexTasa = /^(?!0+(?:\.0+)?$)\d{1,10}(?:\.\d{1,2})?$/;

const regexCantidadMes = /^\d{1,9}$/;
