-- ============================================================================
-- SIAP (UPTAEB) - Script de base de datos "limpio"
-- Generado: 2026-11-22 desde la estructura real de `siap_db` (MySQL local)
--
-- Contiene SOLO datos en:
--   * dependencias : id_dep 30  -> "Coordinación de Proyectos Institucionales"
--   * responsables : id_responsable 1 -> "Elennys" (rol Coordinador)
--                   (antes id 100005, renumerado para que el AUTO_INCREMENT
--                    arranque limpio en 2 y no siga creciendo en 100000+)
--   * roles        : id_rol 3 -> "Coordinador"      (obligatorio: FK de responsables)
--   * cargo        : id_cargo 1 -> vínculo responsable 1 <-> dependencia 30 (FK)
--   * TODA las demás tablas quedan VACÍAS (solo estructura).
--
-- Notas:
--   * El responsable se renumeró de id 100005 a id 1 (y su cargo de
--     100005 a 1) para que el AUTO_INCREMENT arranque limpio en 2.
--     Si algo externo referencia el id 100005, ahora es 1.
--   * Se reinició el AUTO_INCREMENT de todas las tablas vacías a 2
--     (antes heredaban valores de la BD viva: presupuesto=2180,
--     requerimiento=60, tasa_bcv=24, etc.).
--
-- Uso:
--   mysql -uroot -e "CREATE DATABASE IF NOT EXISTS siap_db CHARACTER SET utf8mb4;"
--   mysql -uroot siap_db < siap_db_vacio.sql
--
-- Nota: se desactivan las claves foráneas al inicio y se restauran al final,
-- por lo que el orden de los INSERT no es crítico.
-- ============================================================================

-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: siap_db
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `anio_fiscal`
--

DROP TABLE IF EXISTS `anio_fiscal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anio_fiscal` (
  `id_aniof` int(11) NOT NULL AUTO_INCREMENT,
  `anio` year(4) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_aniof`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `sincronizar_estado_req_con_anio` AFTER UPDATE ON `anio_fiscal` FOR EACH ROW BEGIN
    UPDATE requerimiento
    SET estado = NEW.activo
    WHERE id_aniof = NEW.id_aniof;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `cargo`
--

DROP TABLE IF EXISTS `cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargo` (
  `id_cargo` int(11) NOT NULL AUTO_INCREMENT,
  `id_responsable` int(11) NOT NULL,
  `id_dep` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cargo`),
  KEY `id_responsable` (`id_responsable`),
  KEY `id_dep` (`id_dep`),
  CONSTRAINT `cargo_ibfk_1` FOREIGN KEY (`id_responsable`) REFERENCES `responsables` (`id_responsable`),
  CONSTRAINT `cargo_ibfk_2` FOREIGN KEY (`id_dep`) REFERENCES `dependencias` (`id_dep`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dependencias`
--

DROP TABLE IF EXISTS `dependencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dependencias` (
  `id_dep` int(11) NOT NULL AUTO_INCREMENT,
  `nom_dep` varchar(80) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_dep`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detalle_req`
--

DROP TABLE IF EXISTS `detalle_req`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_req` (
  `id_pres` int(11) DEFAULT NULL,
  `id_req` int(11) NOT NULL,
  `mes` tinyint(4) NOT NULL,
  `cant_mes` int(11) NOT NULL,
  KEY `id_req` (`id_req`),
  KEY `id_pres` (`id_pres`),
  CONSTRAINT `detalle_req_ibfk_2` FOREIGN KEY (`id_req`) REFERENCES `requerimiento` (`id_req`),
  CONSTRAINT `id_pres` FOREIGN KEY (`id_pres`) REFERENCES `presupuesto` (`id_pres`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partidas`
--

DROP TABLE IF EXISTS `partidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL AUTO_INCREMENT,
  `cod_partida` varchar(10) NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_partida`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `periodos_entrega`
--

DROP TABLE IF EXISTS `periodos_entrega`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `periodos_entrega` (
  `id_periodo` int(11) NOT NULL AUTO_INCREMENT,
  `id_aniof` int(11) NOT NULL,
  `per_inicio` date NOT NULL,
  `per_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_periodo`),
  KEY `id_aniof` (`id_aniof`),
  CONSTRAINT `periodos_entrega_ibfk_1` FOREIGN KEY (`id_aniof`) REFERENCES `anio_fiscal` (`id_aniof`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `presupuesto`
--

DROP TABLE IF EXISTS `presupuesto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presupuesto` (
  `id_pres` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `id_partida` int(11) NOT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `nom_pres` varchar(150) NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_pres`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_partida` (`id_partida`),
  KEY `id_unidad` (`id_unidad`),
  CONSTRAINT `presupuesto_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `presupuesto_ibfk_2` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`),
  CONSTRAINT `presupuesto_ibfk_3` FOREIGN KEY (`id_unidad`) REFERENCES `unidades_medida` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(100) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `nombre_pdf` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requerimiento`
--

DROP TABLE IF EXISTS `requerimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requerimiento` (
  `id_req` int(11) NOT NULL AUTO_INCREMENT,
  `id_dep` int(11) NOT NULL,
  `id_tasa` int(11) NOT NULL,
  `id_aniof` int(11) NOT NULL,
  `estado_registro` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_reg` date NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_req`),
  KEY `id_dep` (`id_dep`),
  KEY `id_tasa` (`id_tasa`),
  KEY `id_aniof` (`id_aniof`),
  CONSTRAINT `requerimiento_ibfk_1` FOREIGN KEY (`id_dep`) REFERENCES `dependencias` (`id_dep`),
  CONSTRAINT `requerimiento_ibfk_2` FOREIGN KEY (`id_tasa`) REFERENCES `tasa_bcv` (`id_tasa`),
  CONSTRAINT `requerimiento_ibfk_3` FOREIGN KEY (`id_aniof`) REFERENCES `anio_fiscal` (`id_aniof`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `responsables`
--

DROP TABLE IF EXISTS `responsables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `responsables` (
  `id_responsable` int(11) NOT NULL AUTO_INCREMENT,
  `id_rol` int(11) NOT NULL,
  `nom_resp` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(120) NOT NULL,
  `foto` varchar(150) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_responsable`),
  UNIQUE KEY `uq_resp_email` (`email`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `responsables_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(70) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasa_bcv`
--

DROP TABLE IF EXISTS `tasa_bcv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasa_bcv` (
  `id_tasa` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_reg` date NOT NULL,
  `tasa_bcv_usd` decimal(12,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `origen` varchar(10) NOT NULL DEFAULT 'manual',
  PRIMARY KEY (`id_tasa`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telefonos`
--

DROP TABLE IF EXISTS `telefonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telefonos` (
  `id_telf` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_telf`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `telefonos_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unidades_medida`
--

DROP TABLE IF EXISTS `unidades_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades_medida` (
  `id_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `unidad` varchar(40) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_unidad`),
  UNIQUE KEY `unidad` (`unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

-- ----------------------------------------------------------------------------
-- Datos mínimos (roles -> dependencia -> responsable -> cargo)
-- ----------------------------------------------------------------------------
INSERT INTO `roles` (`id_rol`, `descripcion`, `estado`) VALUES
(3, 'Coordinador', 1);

INSERT INTO `dependencias` (`id_dep`, `nom_dep`, `estado`) VALUES
(30, 'Coordinación de Proyectos Institucionales', 1);

-- El responsable Elennys estaba en la BD viva con id 100005 (y su cargo
-- también): se renumeró a 1 para que los próximos altas sean 2, 3, ...
INSERT INTO `responsables` (`id_responsable`, `id_rol`, `nom_resp`, `email`, `password`, `foto`, `estado`) VALUES
(1, 3, 'Elennys', 'ejemplo@gmail.com', '$2y$10$vxSbKFPOZ5vTQiZNAz0EsOMGnSjzcqU3MFHkVHmpgBA7WLHpWvPsi', NULL, 1);

INSERT INTO `cargo` (`id_cargo`, `id_responsable`, `id_dep`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
(1, 1, 30, '2026-11-22', NULL, 1);

-- Opcional: descomenta si además quieres los demás roles (necesario para
-- poder iniciar sesión como Administrador/Usuario/Superadmin).
-- INSERT INTO `roles` (`id_rol`, `descripcion`, `estado`) VALUES
-- (1, 'Administrador', 1),
-- (2, 'Usuario', 1),
-- (4, 'Superadmin', 1);

-- ----------------------------------------------------------------------------
-- Restauración de configuración de sesión
-- ----------------------------------------------------------------------------
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
