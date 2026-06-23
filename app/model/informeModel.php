<?php
namespace App\PracticaCrud\Model;

use App\PracticaCrud\Config\Connect\ConnectDB;

class InformeModel extends ConnectDB
{
    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getDependenciasActivas(): array
    {
        $stmt = $this->conex->prepare("SELECT dependencia_id, nombre_dep FROM dependencia WHERE estado = 1 ORDER BY nombre_dep");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPartidas(): array
    {
        return [
            401 => 'Personal y Honorarios',
            402 => 'Materiales e Insumos',
            403 => 'Servicios',
            404 => 'Bienes o Muebles',
            407 => 'Ayudas o Becas',
        ];
    }

    public function getReportButtons(): array
    {
        return [
            [
                'code' => '401',
                'title' => 'Informe Partida 401',
                'description' => 'Personal y Honorarios',
            ],
            [
                'code' => '402',
                'title' => 'Informe Partida 402',
                'description' => 'Materiales e Insumos',
            ],
            [
                'code' => '403',
                'title' => 'Informe Partida 403',
                'description' => 'Servicios',
            ],
            [
                'code' => '404',
                'title' => 'Informe Partida 404',
                'description' => 'Bienes o Muebles',
            ],
            [
                'code' => '407',
                'title' => 'Informe Partida 407',
                'description' => 'Ayudas y Becas',
            ],
            [
                'code' => 'dependencia_individual',
                'title' => 'informe de dependencia',
                'description' => 'Requerimiento individual por cada dependencia',
            ],
            [
                'code' => 'requerimiento_individual',
                'title' => 'Informe Requerimiento Individual',
                'description' => 'Reporte de requerimiento individual',
            ],
            [
                'code' => 'anteproyecto_global',
                'title' => 'Informe Anteproyecto Global',
                'description' => 'Consolidado de anteproyecto global',
            ],
        ];
    }

    public function getRequerimientosPorPartida(int $partida): array
    {
        $stmt = $this->conex->prepare(
            "SELECT r.id_requerimiento, r.codigo, r.descripcion, d.nombre_dep AS dependencia, r.partida_presupuestaria, af.anio_fiscal, p.nombre AS periodo, r.estado, r.fecha_envio
             FROM requerimientos r
             LEFT JOIN dependencia d ON r.dependencia_id = d.dependencia_id
             LEFT JOIN periodos p ON r.periodo_id = p.id_periodo
             LEFT JOIN anio_fiscal af ON r.anio_fiscal_id = af.id_anioFis
             WHERE r.estado != 'Eliminado' AND r.partida_presupuestaria = ?
             ORDER BY r.id_requerimiento DESC"
        );
        $stmt->bindValue(1, $partida, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getReporteRequerimientoIndividual(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT r.id_requerimiento, r.codigo, r.descripcion, d.nombre_dep AS dependencia, r.partida_presupuestaria, af.anio_fiscal, p.nombre AS periodo, r.estado, r.fecha_envio
             FROM requerimientos r
             LEFT JOIN dependencia d ON r.dependencia_id = d.dependencia_id
             LEFT JOIN periodos p ON r.periodo_id = p.id_periodo
             LEFT JOIN anio_fiscal af ON r.anio_fiscal_id = af.id_anioFis
             WHERE r.estado != 'Eliminado'
             ORDER BY r.id_requerimiento DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getResumenPorDependencia(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT d.nombre_dep AS dependencia, COUNT(r.id_requerimiento) AS total_requerimientos
             FROM requerimientos r
             LEFT JOIN dependencia d ON r.dependencia_id = d.dependencia_id
             WHERE r.estado != 'Eliminado'
             GROUP BY d.nombre_dep
             ORDER BY d.nombre_dep"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAnteproyectoGlobal(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT r.partida_presupuestaria AS partida, COUNT(r.id_requerimiento) AS total_requerimientos
             FROM requerimientos r
             WHERE r.estado != 'Eliminado'
             GROUP BY r.partida_presupuestaria
             ORDER BY r.partida_presupuestaria"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
