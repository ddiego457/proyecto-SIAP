<?php
namespace EquipoSiap\Siap\helpers;

trait DependenciaValidationTrait {

    // Nombre de dependencia: letras (con acentos), números, espacios y . - & ( )
    public $expNombreDep = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-\&\(\)]{3,100}$/';

    public function validarNombreDependencia($nombre): ?string
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (!preg_match($this->expNombreDep, $nombre)) {
            return 'El nombre de la dependencia solo puede contener letras, números, espacios y los caracteres . - & ( ), entre 3 y 100 caracteres.';
        }
        return null;
    }
}
?>
