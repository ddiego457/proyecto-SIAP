<?php

namespace App\PracticaCrud\Model;

use App\PracticaCrud\Config\Connect\ConnectDB;

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
        $sql = "SELECT i.id_item, i.id_partida, i.id_proveedor, p.cod_partida, p.descripcion AS partida_descripcion,
                       pr.nom_prov AS proveedor, i.nom_item, i.precio, i.estado
                FROM items_partida i
                JOIN partidas p ON i.id_partida = p.id_partida
                JOIN proveedores pr ON i.id_proveedor = pr.id_proveedor
                WHERE i.estado = 1";

        if ($partidaId !== null) {
            $sql .= " AND i.id_partida = ?";
            $stmt = $this->conex->prepare($sql . " ORDER BY p.cod_partida, i.nom_item");
            $stmt->execute([$partidaId]);
        } else {
            $stmt = $this->conex->prepare($sql . " ORDER BY p.cod_partida, i.nom_item");
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
                "INSERT INTO items_partida (id_partida, id_proveedor, nom_item, precio, estado)
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
                "UPDATE items_partida
                 SET id_partida = ?, id_proveedor = ?, nom_item = ?, precio = ?
                 WHERE id_item = ?"
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
                "UPDATE items_partida
                 SET estado = 0
                 WHERE id_item = ?"
            );
            return $stmt->execute([$idItem]);
        } catch (\PDOException $e) {
            return false;
        }
    }
}
