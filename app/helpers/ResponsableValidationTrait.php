<?php
namespace EquipoSiap\Siap\helpers;

trait ResponsableValidationTrait {

    // Contraseña: definida una sola vez en UserValidationTrait
    use UserValidationTrait;

    // Nombre completo del responsable: letras (con acentos), espacios, apóstrofes y puntos
    public $expNomRep = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]{3,100}$/';
    // Correo electrónico
    public $expEmail = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    // Fecha ISO (asignación de dependencia)
    public $expFechaISO = '/^\d{4}-\d{2}-\d{2}$/';
    // Descripción de rol: texto corto, máximo 70 caracteres
    public $expRolDescripcion = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\_\/\(\)]{1,70}$/';

    public function validarNombreResponsable($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (!preg_match($this->expNomRep, $nombre)) {
            return 'El nombre del responsable solo puede contener letras, espacios, puntos, guiones y apóstrofes, entre 3 y 100 caracteres.';
        }
        return null;
    }

    public function validarEmail($email): ?string
    {
        $email = strtolower(trim((string)$email));
        if ($email === '') {
            return 'El correo electrónico no puede estar vacío.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($this->expEmail, $email)) {
            return 'El correo electrónico no es válido.';
        }
        return null;
    }

    public function validarFechaAsignacion($fecha): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return 'La fecha de asignación no puede estar vacía.';
        }
        if (!preg_match($this->expFechaISO, $fecha)) {
            return 'La fecha de asignación debe tener el formato AAAA-MM-DD.';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return 'La fecha de asignación no es una fecha válida.';
        }
        return null;
    }

    public function validarDescripcionRol($descripcion): ?string
    {
        $descripcion = trim((string)$descripcion);
        if ($descripcion === '') {
            return 'La descripción del rol no puede estar vacía.';
        }
        if (!preg_match($this->expRolDescripcion, $descripcion)) {
            return 'La descripción del rol solo puede contener letras, números, espacios y los caracteres . , - _ / ( ), máximo 70 caracteres.';
        }
        return null;
    }
}
?>
