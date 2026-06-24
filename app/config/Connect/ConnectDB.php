<?php

    namespace EquipoSiap\Siap\config\Connect;

    use PDO;
    use PDOException;

    class ConnectDB {

        private $host   = 'localhost';
        private $dbname = 'siap_db';
        private $user   = 'root';
        private $pass   = '';
        private $connection;

        public function __construct() {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $this->connection = new PDO($dsn, $this->user, $this->pass);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Asegurar el charset de la conexión para evitar problemas tipo "AÃ±os"
                $this->connection->exec("SET NAMES utf8mb4");
            } catch (PDOException $e) {
                die("Error de conexion: " . $e->getMessage());
            }
        }

        protected function getConnection() {
            return $this->connection;
        }
    }

?>