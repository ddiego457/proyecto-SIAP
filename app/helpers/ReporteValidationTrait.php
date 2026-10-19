<?php
namespace EquipoSiap\Siap\helpers;

trait ReporteValidationTrait {

    // Formato de exportación permitido
    public $expFormatoReporte = '/^(excel|pdf)$/';
    // Nombre interno del reporte (identificadores del switch del controller)
    public $expNombreReporte = '/^[a-zA-Z0-9_\-]{1,50}$/';
    // Identificador numérico de dependencia
    public $expIdDepReporte = '/^\d{1,10}$/';

    public function validarFormatoReporte($formato): ?string
    {
        if (!preg_match($this->expFormatoReporte, (string)$formato)) {
            return 'Formato de reporte inválido. Use excel o pdf.';
        }
        return null;
    }

    public function validarNombreReporte($reporte): ?string
    {
        $reporte = trim((string)$reporte);
        if ($reporte === '') {
            return 'Debe seleccionar un reporte.';
        }
        if (!preg_match($this->expNombreReporte, $reporte)) {
            return 'Nombre de reporte inválido.';
        }
        return null;
    }

    public function validarIdDep($idDep): ?string
    {
        if (!preg_match($this->expIdDepReporte, (string)$idDep)) {
            return 'Dependencia inválida.';
        }
        return null;
    }
}
?>
