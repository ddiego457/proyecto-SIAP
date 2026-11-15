<?php

namespace EquipoSiap\Siap\helpers;

trait ReporteGeneratorTrait
{
    private function buildIndividualReportTitle(array $context): string
    {
        return 'Requerimiento ' . $context['estado'] . ' - ' . $context['dependencia'];
    }

    private function buildIndividualReportFilename(array $context): string
    {
        $parts = [$context['dependencia'], 'Requerimiento', $context['estado'], (string)$context['anio']];
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', implode('_', $parts));
        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $filename);
        return trim((string)$filename, '_') . '_' . date('Ymd');
    }

    private function getGlobalReportRows(array $data, string $modo): array
    {
        if ($modo === 'cantidades') {
            $rows = [];
            foreach ($data as $row) {
                if ((float)$row['cantidad_Total'] <= 0) {
                    continue;
                }
                [$descripcion, $unidad] = $this->parseProductDescription((string)$row['Descripcion']);
                $rows[] = [
                    'codigo' => $row['codigo'],
                    'partida' => $row['partida'],
                    'producto' => $descripcion,
                    'unidad' => $unidad,
                    'cantidad' => (float)$row['cantidad_Total'],
                ];
            }
            return $rows;
        }

        if ($modo === 'totales') {
            $totales = [];
            foreach ($data as $row) {
                $codigo = (string)$row['codigo'];
                if (!isset($totales[$codigo])) {
                    $totales[$codigo] = [
                        'codigo' => $codigo,
                        'partida' => $row['partida'],
                        'total_usd' => 0.0,
                        'total_bs' => 0.0,
                    ];
                }
                $totales[$codigo]['total_usd'] += (float)$row['Total_precio_dolares'];
                $totales[$codigo]['total_bs'] += (float)$row['Total_precio'];
            }
            return array_values($totales);
        }

        throw new \InvalidArgumentException('Modo de consolidado no válido.');
    }

    private function parseProductDescription(string $value, string $defaultUnit = 'UND'): array
    {
        $separatorStart = strpos($value, '|');
        if ($separatorStart === false) {
            return [$value, $defaultUnit];
        }

        $separatorEnd = strpos($value, '|', $separatorStart + 1);
        if ($separatorEnd === false) {
            return [trim(substr($value, 0, $separatorStart)), trim(substr($value, $separatorStart + 1)) ?: $defaultUnit];
        }

        $unit = substr($value, $separatorStart + 1, $separatorEnd - $separatorStart - 1);
        $description = trim(substr($value, 0, $separatorStart) . substr($value, $separatorEnd + 1));
        return [$description, $unit !== '' ? $unit : $defaultUnit];
    }

    private function formatReportNumber($value, bool $isAmount = false): string
    {
        if (!is_numeric($value) || (float)$value == 0.0) {
            return '';
        }

        return number_format((float)$value, $isAmount ? 2 : 0, ',', '.');
    }

    private function safeReportFilenamePart(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $part = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ascii === false ? $value : $ascii);
        return trim((string)$part, '_') ?: 'reporte';
    }

    private function getPartidaReportLabel(array $idPartidas): string
    {
        if (!$idPartidas) {
            return 'Todas las partidas';
        }
        $labels = [];
        foreach ($this->getPartidasParaReporte() as $partida) {
            if (in_array((int)$partida['id_partida'], $idPartidas, true)) {
                $labels[] = (string)$partida['cod_partida'];
            }
        }
        return implode(', ', $labels);
    }

    private function formatPartidaSheetName(string $codigo): string
    {
        $formatted = str_replace('.', ',', $codigo);
        if (strlen($formatted) === 3 && ctype_digit($formatted)) {
            $formatted = substr($formatted, 0, 1) . ',' . substr($formatted, 1);
        }
        return $formatted;
    }

    private function getDependencyFilterLabel(string $filtro): string
    {
        return match ($filtro) {
            'activas_con_responsable' => 'Dependencias activas con responsable',
            'activas_sin_responsable' => 'Dependencias activas sin responsable',
            'inactivas' => 'Dependencias inactivas (con o sin responsable)',
            default => throw new \InvalidArgumentException('Filtro de dependencias no válido.'),
        };
    }
}
