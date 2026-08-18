<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;

class reporteModel extends ConnectDB
{
    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

// antigua funcion que se encarga de "pintar" las opciones para la descarga --considero quitarlo
// Me gustan como se ven las opciones para la descarga, aunque ahora hay que adaptarlo al nuevo modelo
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

    public function getAll($id, $typeReport, $cond){
        $result = $this->executeGetAll($id, $typeReport, $cond);
        return $result;
    }

    //executeGetAll es una funcion que obtiene el tipo de consulta a ejecutar mas una/niguna condición 
    private function executeGetAll($id, $typeReport, $cond){ 
    $query = "";
    
    switch ($typeReport) {
        case 'req':
            // 1. LA CONSULTA BASE: Contiene los cálculos de meses, totales y los JOINs obligatorios.
            // Exigimos af.activo = 1 (Año fiscal actual) y r.estado_envio = 1 (Solo requerimientos enviados/consolidados)
            $baseSelect = "SELECT 
                d.nom_dep AS dependencia,
                p.cod_partida AS partida,
                pro.nom_prod AS producto,
                SUM(CASE WHEN dr.mes = 1 THEN dr.cant_mes ELSE 0 END) AS Ene,
                SUM(CASE WHEN dr.mes = 2 THEN dr.cant_mes ELSE 0 END) AS Feb,
                SUM(CASE WHEN dr.mes = 3 THEN dr.cant_mes ELSE 0 END) AS Mar,
                SUM(CASE WHEN dr.mes = 4 THEN dr.cant_mes ELSE 0 END) AS Abr,
                SUM(CASE WHEN dr.mes = 5 THEN dr.cant_mes ELSE 0 END) AS May,
                SUM(CASE WHEN dr.mes = 6 THEN dr.cant_mes ELSE 0 END) AS Jun,
                SUM(CASE WHEN dr.mes = 7 THEN dr.cant_mes ELSE 0 END) AS Jul,
                SUM(CASE WHEN dr.mes = 8 THEN dr.cant_mes ELSE 0 END) AS Ago,
                SUM(CASE WHEN dr.mes = 9 THEN dr.cant_mes ELSE 0 END) AS Sep,
                SUM(CASE WHEN dr.mes = 10 THEN dr.cant_mes ELSE 0 END) AS Oct,
                SUM(CASE WHEN dr.mes = 11 THEN dr.cant_mes ELSE 0 END) AS Nov,
                SUM(CASE WHEN dr.mes = 12 THEN dr.cant_mes ELSE 0 END) AS Dic,
                SUM(dr.cant_mes) AS total_cantidad,
                pro.precio AS precio_unit_usd,
                (pro.precio * tb.tasa_bcv_usd) AS precio_unit_bs,
                (SUM(dr.cant_mes) * pro.precio) AS total_usd,
                (SUM(dr.cant_mes) * pro.precio * tb.tasa_bcv_usd) AS total_bs
            FROM detalle_req dr
            JOIN requerimientos r ON dr.id_req = r.id_req
            JOIN dependencias d ON r.id_dep = d.id_dep
            JOIN productos pro ON dr.id_item = pro.id_item
            JOIN partidas p ON pro.id_partida = p.id_partida
            JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
            JOIN anio_fiscal af ON r.id_aniof = af.id_aniof
            WHERE r.estado = 1 
              AND r.estado_envio = 1 
              AND af.activo = 1 ";

            if (empty($id)) {
                // 2A. SI EL ID ESTÁ VACÍO: Traer todas las dependencias.
                // Agrupamos también por dependencia para que no se mezclen productos iguales de distintos departamentos.
                $query = $baseSelect . " 
                         GROUP BY d.id_dep, d.nom_dep, p.cod_partida, pro.nom_prod, pro.precio, tb.tasa_bcv_usd 
                         ORDER BY d.nom_dep ASC, p.cod_partida ASC;";
            } else {
                // 2B. SI HAY UN ID ESPECÍFICO: Filtramos por esa dependencia en particular.
                $query = $baseSelect . " AND d.id_dep = " . (int)$id . " 
                         GROUP BY d.id_dep, d.nom_dep, p.cod_partida, pro.nom_prod, pro.precio, tb.tasa_bcv_usd 
                         ORDER BY p.cod_partida ASC;";
            }
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
            break;
            case 'prod':
                $query = "SELECT p.cod_partida as partidas, pro.nom_prod as producto, pro.precio as precio FROM productos as pro
                    JOIN partidas as p ON p.id_partida = pro.id_partida";
                if (!empty($cond) && $cond != ''){
                    $query = $query . " WHERE p.cod_partida = " . $cond . "ORDER BY pro.precio DESC";
                }
                    $stmt = $this->conex->prepare($query);
                    $stmt->execute();
                    return $stmt->fetchAll();
                break;
        default:
            # code...
            break;
    }
    return $query;
    }
}
