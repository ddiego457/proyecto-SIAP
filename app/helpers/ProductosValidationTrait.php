<?php
namespace EquipoSiap\Siap\helpers;

trait ProductosValidationTrait {

    // Nombre de producto/servicio: letras (con acentos), números, espacios y . , - ( ) / %
    public $expNomItem = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\(\)\/\%]{2,150}$/';
    // Precio: entero o decimal con hasta 2 decimales
    public $expPrecio = '/^\d{1,9}(\.\d{1,2})?$/';
    // Código de partida: dígitos con un punto opcional entre grupos (ej. 401 o 4.01), máx. 10
    public $expCodPartida = '/^[0-9]+(\.[0-9]+)?$/';
    // Descripción de partida: texto libre sin etiquetas HTML ni caracteres de control, máx. 150
    public $expDescripcionPartida = '/^[^<>\x00-\x1F\x7F]{1,150}$/';

    public function validarNombreProducto($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre del producto no puede estar vacío.';
        }
        if (!preg_match($this->expNomItem, $nombre)) {
            return 'El nombre del producto solo puede contener letras, números, espacios y los caracteres . , - ( ) / %, entre 2 y 150 caracteres.';
        }
        return null;
    }

    public function validarPrecio($precio): ?string
    {
        $precio = trim((string)$precio);
        if ($precio === '') {
            return 'El precio no puede estar vacío.';
        }
        if (!preg_match($this->expPrecio, $precio)) {
            return 'El precio debe ser un número positivo con hasta 2 decimales (ej. 5.50).';
        }
        if ((float)$precio <= 0) {
            return 'El precio debe ser mayor que cero.';
        }
        return null;
    }

    public function validarCodigoPartida($codigo): ?string
    {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return 'El código de la partida no puede estar vacío.';
        }
        if (mb_strlen($codigo) > 10 || !preg_match($this->expCodPartida, $codigo)) {
            return 'El código de la partida debe contener solo dígitos y puntos, máximo 10 caracteres (ej. 401).';
        }
        return null;
    }

    public function validarDescripcionPartida($descripcion): ?string
    {
        $descripcion = trim((string)$descripcion);
        if ($descripcion === '') {
            return 'La descripción de la partida no puede estar vacía.';
        }
        if (!preg_match($this->expDescripcionPartida, $descripcion)) {
            return 'La descripción de la partida solo puede contener texto (sin etiquetas HTML) de hasta 150 caracteres.';
        }
        return null;
    }
}
?>
