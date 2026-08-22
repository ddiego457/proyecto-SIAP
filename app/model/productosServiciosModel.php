<?php

namespace EquipoSiap\Siap\model;

use PhpOffice\PhpSpreadsheet\IOFactory;
use EquipoSiap\Siap\config\Connect\ConnectDB;

class productosServiciosModel extends ConnectDB
{
    private $conex;

    public function __construct()
    {
        parent::__construct();
        $this->conex = $this->getConnection();
    }

    public function getPartidas(): array
    {
        return $this->executeGetPartidas();
    }

    private function executeGetPartidas(): array
    {
        $stmt = $this->conex->prepare(
            "SELECT id_partida, cod_partida, descripcion
             FROM partidas
             WHERE cod_partida IN ('401', '402', '403', '404', '407')
             ORDER BY cod_partida"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll(?int $partidaId = null): array
    {
        return $this->executeGetAll($partidaId);
    }

    private function executeGetAll(?int $partidaId = null): array
    {
        $sql = "SELECT pro.id_prod, pro.id_partida, pro.id_proveedor, p.cod_partida, p.descripcion AS partida_descripcion,
                       pr.nom_prov AS proveedor, pro.nom_prod, pro.precio, pro.estado
                FROM productos pro
                JOIN partidas p ON pro.id_partida = p.id_partida
                JOIN proveedores pr ON pro.id_proveedor = pr.id_proveedor
                WHERE pro.estado = 1";

        if ($partidaId !== null) {
            $sql .= " AND pro.id_partida = ?";
            $stmt = $this->conex->prepare($sql . " ORDER BY p.cod_partida, pro.nom_prod");
            $stmt->execute([$partidaId]);
        } else {
            $stmt = $this->conex->prepare($sql . " ORDER BY p.cod_partida, pro.nom_prod");
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    public function add(int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        return $this->executeAdd($idPartida, $idProveedor, $nomItem, $precio);
    }

    private function executeAdd(int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "INSERT INTO productos (id_partida, id_proveedor, nom_prod, precio, estado)
                 VALUES (?, ?, ?, ?, 1)"
            );
            return $stmt->execute([$idPartida, $idProveedor, $nomItem, $precio]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function update(int $idItem, int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        return $this->executeUpdate($idItem, $idPartida, $idProveedor, $nomItem, $precio);
    }

    private function executeUpdate(int $idItem, int $idPartida, int $idProveedor, string $nomItem, float $precio): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "UPDATE productos
                 SET id_partida = ?, id_proveedor = ?, nom_prod = ?, precio = ?
                 WHERE id_prod = ?"
            );
            return $stmt->execute([$idPartida, $idProveedor, $nomItem, $precio, $idItem]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function inhabilitar(int $idItem): bool
    {
        return $this->executeInhabilitar($idItem);
    }

    private function executeInhabilitar(int $idItem): bool
    {
        try {
            $stmt = $this->conex->prepare(
                "UPDATE productos
                 SET estado = 0
                 WHERE id_prod = ?"
            );
            return $stmt->execute([$idItem]);
        } catch (\PDOException $e) {
            return false;
        }
    }
    public function loadData(){
        $result = $this->executeLoadData();
    }
    private function executeLoadData(){
        $partidas = [
           2 => '4,02',
           3 => '4,03',
           4 => '4,04',
           5 => '4,07'
        ];

        //======== Incluir archivo para hacer la carga de datos ========
        $rutaPlantilla = __DIR__ . '/../template/TOTAL_Todas_las_Dependencias_Formato_Requerimientos_POA_2027.xlsx';
        $documento = IOFactory::load($rutaPlantilla);
        $idProd = 22;
        $codPart = 2;

        $sql = "INSERT INTO productos (id_prod, id_partida, nom_prod, precio, id_proveedor) VALUES (:id_prod, :partida, :nombre, :precio, :idProveedor)";
        $stmt = $this->conex->prepare($sql);


        foreach($partidas as $partida){

            $hoja = $documento->getSheetByName($partida)->toArray(null, true, true, true);
            foreach($hoja as $index => $row){
                if($index < 21){
                    continue;
                }
                if( !(isset($row['B'])) && (trim($row['B']) != '')){
                    $idProd++;
                    continue;
                }
                $nombre = $row['B'];
                $nombre .= "|" . $row['C'] . "|";
                $precio = intval($row['S']);
                if(!empty($nombre) && !empty($precio)){
                    $stmt->execute([
                        ':id_prod' => $idProd,
                        ':partida' => $codPart,
                        ':nombre' => $nombre,
                        ':precio' => $precio,
                        'idProveedor' => 1
                    ]);
                    $idProd++;
                }
            }
            $codPart++;
        }
    }
}
