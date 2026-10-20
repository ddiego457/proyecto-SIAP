<?php
namespace EquipoSiap\Siap\helpers;

trait RequerimientoValidationTrait {

    // Código de partida actual: dígitos con un punto opcional entre grupos (ej. 401), máx. 10
    public $expPartidaActual = '/^[0-9]+(\.[0-9]+)?$/';
    // Cantidad por mes: entero sin decimales ni signo
    public $expCantidadMes = '/^\d{1,9}$/';
    // Identificadores numéricos (id_req, id_prod, id_dep)
    public $expIdRequerimiento = '/^\d{1,10}$/';

    public function validarPartidaActual($partida): ?string
    {
        $partida = trim((string)$partida);
        if ($partida === '') {
            return 'La partida actual no puede estar vacía.';
        }
        if (mb_strlen($partida) > 10 || !preg_match($this->expPartidaActual, $partida)) {
            return 'La partida actual debe contener solo dígitos y puntos, máximo 10 caracteres (ej. 401).';
        }
        return null;
    }

    // Valida la matriz de cantidades: cantidades[idProd][mes] = valor
    // Se acepta cadena vacía (el input number puede quedar vacío); el modelo la trata como 0 vía intval()
    public function validarCantidades($cantidades): ?string
    {
        if (!is_array($cantidades)) {
            return 'La matriz de cantidades no es válida.';
        }
        foreach ($cantidades as $idProd => $meses) {
            if (!preg_match($this->expIdRequerimiento, (string)$idProd)) {
                return 'Identificador de producto inválido en la matriz de cantidades.';
            }
            if (!is_array($meses)) {
                return 'Estructura de cantidades inválida para el producto ' . $idProd . '.';
            }
            foreach ($meses as $mes => $valor) {
                if (!preg_match('/^\d{1,2}$/', (string)$mes) || (int)$mes < 1 || (int)$mes > 12) {
                    return 'Mes inválido (' . htmlspecialchars((string)$mes, ENT_QUOTES, 'UTF-8') . ') en la matriz de cantidades.';
                }
                $valor = trim((string)$valor);
                if ($valor === '') {
                    continue;
                }
                if (!preg_match($this->expCantidadMes, $valor)) {
                    return 'Cantidad inválida para el producto ' . $idProd . ', mes ' . $mes . '. Debe ser un entero sin decimales.';
                }
            }
        }
        return null;
    }

    public function validarIdRequerimiento($id): ?string
    {
        if (!preg_match($this->expIdRequerimiento, (string)$id)) {
            return 'Identificador de requerimiento inválido.';
        }
        return null;
    }
}
?>
