<?php
namespace EquipoSiap\Siap\helpers;

trait TasaBCVValidationTrait {

    // Tasa: entero o decimal con hasta 2 decimales (decimal(12,2) en BD)
    public $expTasaBCV = '/^\d{1,10}(\.\d{1,2})?$/';
    // Fecha ISO (inputs type="date")
    public $expFechaTasa = '/^\d{4}-\d{2}-\d{2}$/';

    public function validarTasa($tasa): ?string
    {
        $tasa = trim((string)$tasa);
        if ($tasa === '') {
            return 'La tasa no puede estar vacía.';
        }
        if (!preg_match($this->expTasaBCV, $tasa)) {
            return 'La tasa debe ser un número positivo con hasta 2 decimales (ej. 69.00).';
        }
        if ((float)$tasa <= 0) {
            return 'La tasa debe ser mayor que cero.';
        }
        return null;
    }

    public function validarFechaRegistro($fecha): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return 'La fecha no puede estar vacía.';
        }
        if (!preg_match($this->expFechaTasa, $fecha)) {
            return 'La fecha debe tener el formato AAAA-MM-DD.';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return 'La fecha no es una fecha válida.';
        }
        return null;
    }
}
?>
