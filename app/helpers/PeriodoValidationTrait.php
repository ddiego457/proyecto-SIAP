<?php
namespace EquipoSiap\Siap\helpers;

trait PeriodoValidationTrait {

    // Fecha ISO (los inputs del formulario usan type="date")
    public $expFechaPeriodo = '/^\d{4}-\d{2}-\d{2}$/';
    // Año fiscal de 4 dígitos (rango 1900-2100 validado por separado)
    public $expAnioFiscal = '/^\d{4}$/';

    public function validarFechaPeriodo($fecha, $campo = 'La fecha'): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return $campo . ' no puede estar vacía.';
        }
        if (!preg_match($this->expFechaPeriodo, $fecha)) {
            return $campo . ' debe tener el formato AAAA-MM-DD.';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            return $campo . ' no es una fecha válida.';
        }
        return null;
    }

    public function validarAnioFiscal($anio): ?string
    {
        $anio = trim((string)$anio);
        if ($anio === '') {
            return 'El año fiscal no puede estar vacío.';
        }
        if (!preg_match($this->expAnioFiscal, $anio)) {
            return 'El año fiscal debe ser un número de 4 dígitos (ej. 2026).';
        }
        if ((int)$anio < 2000 || (int)$anio > 2100) {
            return 'El año fiscal debe estar entre 1900 y 2100.';
        }
        return null;
    }
}
?>
