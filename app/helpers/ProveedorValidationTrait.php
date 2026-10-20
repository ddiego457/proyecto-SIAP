<?php
namespace EquipoSiap\Siap\helpers;

trait ProveedorValidationTrait {

    // Nombre del proveedor: letras (con acentos), números, espacios y . - & ( )
    public $expNombreProv = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-\&\(\)]{3,100}$/';
    // Descripción (opcional): texto libre sin etiquetas HTML ni caracteres de control, máx. 150 (varchar(150) en BD)
    public $expDescripcionProv = '/^[^<>\x00-\x1F\x7F]{0,150}$/';
    // Teléfono: dígitos con opcional "+" al inicio, guiones y espacios (misma expresión que proveedorModel/proveedor.js)
    public $expTelefono = '/^\+?[0-9][0-9\-\s]{6,}$/';

    public function validarNombreProveedor($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (!preg_match($this->expNombreProv, $nombre)) {
            return 'El nombre del proveedor solo puede contener letras, números, espacios y los caracteres . - & ( ), entre 3 y 100 caracteres.';
        }
        return null;
    }

    public function validarDescripcionProveedor($descripcion): ?string
    {
        // La descripción es opcional en el formulario y DEFAULT NULL en BD
        $descripcion = trim((string)$descripcion);
        if ($descripcion === '') {
            return null;
        }
        if (!preg_match($this->expDescripcionProv, $descripcion)) {
            return 'La descripción solo puede contener texto (sin etiquetas HTML) de hasta 150 caracteres.';
        }
        return null;
    }

    public function validarTelefono($telefono): ?string
    {
        $telefono = trim((string)$telefono);
        if ($telefono === '') {
            return 'El teléfono no puede estar vacío.';
        }
        if (!preg_match($this->expTelefono, $telefono)) {
            return 'Formato de teléfono inválido. Solo se permiten dígitos, "+" al inicio y "-". Ej: +58-212-5551234';
        }
        return null;
    }
}
?>
