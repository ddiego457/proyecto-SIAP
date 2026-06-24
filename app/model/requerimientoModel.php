<?php
namespace EquipoSiap\Siap\model;

use EquipoSiap\Siap\config\Connect\ConnectDB;

class requerimientoModel extends ConnectDB{
private $conex;

public function __construct()
{
    parent::__construct();
    $this->conex = $this->getConnection();
}

public function getAll(){
    
    $result = $this->executeGetAll();
    return $result; 
}

private function executeGetAll(){
    $query = "SELECT d.nom_dep as dependencia,
    p.cod_partida AS partida,
    i.nom_item AS producto,
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
    SUM(dr.cant_mes) AS Total_Cantidad,
    i.precio AS precio_unit_usd,
    (SUM(dr.cant_mes) * i.precio) AS total_usd,
    SUM(dr.cant_mes * i.precio * tb.tasa_bcv_usd) AS total_bs
    FROM requerimientos r
    JOIN detalle_req dr ON r.id_req = dr.id_req
    JOIN dependencias d ON d.id_dep = r.id_dep
    JOIN items_partida i ON dr.id_item = i.id_item
    JOIN partidas p ON i.id_partida = p.id_partida
    JOIN tasa_bcv tb ON r.id_tasa = tb.id_tasa
    -- WHERE r.estado = 'enviado'
    GROUP BY d.id_dep, p.cod_partida, i.id_item, i.nom_item, i.precio
    ORDER BY p.cod_partida, d.nom_dep;";
    $stmt = $this->conex->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

}


?>