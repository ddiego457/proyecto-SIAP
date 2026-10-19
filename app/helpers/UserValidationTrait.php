<?php
namespace EquipoSiap\Siap\helpers;

// Validaciones de usuario/contraseña (login y reset de contraseña)
// Fuente única de verdad: UserModel y loginDesingController consumen este trait.
trait UserValidationTrait {

    // Usuario: letras (con acentos), números, espacios y _ . -
    public $expUsuario = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ_\.\-\s]{2,100}$/';
    // Contraseña: letras, números y _ . - (mínimo 4 caracteres)
    public $expPassword = '/^[a-zA-Z0-9_\.\-]{4,}$/';

    public function validarUsuario($usuario): ?string
    {
        $usuario = trim((string)$usuario);
        if ($usuario === '') {
            return 'El usuario no puede estar vacío.';
        }
        if (!preg_match($this->expUsuario, $usuario)) {
            return 'El usuario solo puede contener letras, números, espacios y los caracteres _ . - (entre 2 y 100 caracteres).';
        }
        return null;
    }

    public function validarPassword($password): ?string
    {
        if ((string)$password === '') {
            return 'La contraseña no puede estar vacía.';
        }
        if (!preg_match($this->expPassword, $password)) {
            return 'La contraseña solo puede contener letras, números y los caracteres _ . - (mínimo 4 caracteres).';
        }
        return null;
    }
}
?>
