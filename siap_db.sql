-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-07-2026 a las 03:01:48
-- Versión del servidor: 10.1.38-MariaDB
-- Versión de PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `siap_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anio_fiscal`
--

CREATE TABLE `anio_fiscal` (
  `id_aniof` int(11) NOT NULL,
  `anio` year(4) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `anio_fiscal`
--

INSERT INTO `anio_fiscal` (`id_aniof`, `anio`, `activo`) VALUES
(1, 2024, 0),
(2, 2025, 0),
(3, 2026, 1),
(4, 2028, 0);

--
-- Disparadores `anio_fiscal`
--
DELIMITER $$
CREATE TRIGGER `sincronizar_estado_req_con_anio` AFTER UPDATE ON `anio_fiscal` FOR EACH ROW BEGIN
    UPDATE requerimientos
    SET estado = NEW.activo
    WHERE id_aniof = NEW.id_aniof;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargo`
--

CREATE TABLE `cargo` (
  `id_cargo` int(11) NOT NULL,
  `id_responsable` int(11) NOT NULL,
  `id_dep` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `cargo`
--

INSERT INTO `cargo` (`id_cargo`, `id_responsable`, `id_dep`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
(1, 1, 1, '2024-01-01', NULL, 1),
(2, 2, 2, '2024-01-01', NULL, 1),
(3, 3, 3, '2024-01-15', NULL, 1),
(4, 4, 4, '2024-02-01', NULL, 1),
(5, 5, 5, '2024-03-01', NULL, 1),
(6, 6, 6, '2024-03-15', NULL, 1),
(7, 7, 7, '2024-04-01', NULL, 1),
(8, 8, 1, '2025-01-01', NULL, 1),
(9, 9, 2, '2025-01-15', NULL, 1),
(10, 10, 3, '2025-02-01', NULL, 1),
(11, 11, 8, '2026-06-21', NULL, 1),
(12, 12, 9, '2026-06-27', NULL, 1),
(13, 13, 10, '2026-06-27', NULL, 1),
(14, 14, 11, '2026-06-27', NULL, 1),
(15, 15, 12, '2026-06-27', NULL, 1),
(16, 16, 13, '2026-06-27', NULL, 1),
(17, 17, 14, '2026-06-27', NULL, 1),
(18, 18, 15, '2026-06-27', NULL, 1),
(19, 19, 15, '2026-06-27', NULL, 1),
(20, 20, 15, '2026-06-27', NULL, 1),
(21, 21, 16, '2026-07-01', NULL, 1),
(22, 22, 17, '2026-07-01', NULL, 1),
(23, 23, 18, '2026-07-01', NULL, 1),
(24, 24, 19, '2026-07-02', NULL, 1),
(25, 25, 20, '2026-07-03', NULL, 1),
(26, 26, 21, '2026-07-03', NULL, 1),
(27, 27, 21, '2026-07-03', NULL, 1),
(28, 28, 22, '2026-07-05', NULL, 1),
(29, 29, 23, '2026-07-07', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dependencias`
--

CREATE TABLE `dependencias` (
  `id_dep` int(11) NOT NULL,
  `nom_dep` varchar(80) COLLATE utf8_spanish_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `dependencias`
--

INSERT INTO `dependencias` (`id_dep`, `nom_dep`, `estado`) VALUES
(1, 'Finanzas y Presupuesto', 1),
(2, 'Dirección de Compras', 1),
(3, 'Tecnología de la Información', 1),
(4, 'Recursos Humanos', 1),
(5, 'Logística y Almacenes', 1),
(6, 'Mantenimiento y Servicios', 1),
(7, 'Planificación Estratégica', 1),
(8, 'tralalero', 1),
(9, 'TIC', 1),
(10, 'hola', 1),
(11, 'tung tung', 1),
(12, 'chamuco', 1),
(13, 'wwww', 1),
(14, 'Soccer', 1),
(15, 'abc', 1),
(16, 'abcdefg', 1),
(17, 'pistear', 1),
(18, 'becerro', 1),
(19, 'ñame', 1),
(20, 'Saramambiche', 1),
(21, 'Saramambiche2', 1),
(22, 'Saramambiche3', 1),
(23, '12345', 1),
(24, '6789', 1),
(25, '101112', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_req`
--

CREATE TABLE `detalle_req` (
  `id_prod` int(11) DEFAULT NULL,
  `id_req` int(11) NOT NULL,
  `mes` tinyint(4) NOT NULL,
  `cant_mes` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `detalle_req`
--

INSERT INTO `detalle_req` (`id_prod`, `id_req`, `mes`, `cant_mes`, `estado`) VALUES
(3, 2, 2, 200, 1),
(4, 2, 2, 10, 1),
(5, 3, 3, 5, 1),
(6, 3, 3, 8, 1),
(7, 4, 4, 2, 1),
(8, 4, 4, 3, 1),
(9, 5, 1, 1, 1),
(10, 5, 1, 1, 1),
(11, 6, 2, 4, 1),
(12, 6, 2, 4, 1),
(13, 7, 3, 20, 1),
(14, 7, 3, 30, 1),
(15, 8, 4, 5, 1),
(16, 9, 5, 2, 1),
(17, 10, 6, 1, 1),
(5, 12, 2, 4, 1),
(6, 12, 2, 6, 1),
(9, 13, 3, 2, 1),
(11, 13, 3, 5, 1),
(13, 14, 4, 25, 1),
(14, 14, 4, 40, 1),
(4, 2, 2, 8, 1),
(7, 4, 4, 1, 1),
(12, 6, 2, 3, 1),
(16, 9, 5, 1, 1),
(21, 24, 7, 11, 1),
(1, 24, 1, 5, 1),
(2, 24, 5, 8, 1),
(3, 24, 8, 11, 1),
(14, 24, 8, 9, 1),
(5, 24, 3, 5, 1),
(19, 24, 1, 8, 1),
(20, 38, 1, 5, 1),
(21, 39, 1, 2, 1),
(1, 39, 2, 3, 1),
(3, 39, 8, 5, 1),
(18, 39, 8, 6, 1),
(21, 43, 3, 14, 1),
(21, 43, 11, 5, 1),
(1, 43, 1, 5, 1),
(2, 43, 12, 6, 1),
(13, 43, 9, 5, 1),
(14, 43, 9, 4, 1),
(21, 44, 1, 1, 1),
(21, 44, 12, 1, 1),
(1, 44, 12, 2, 1),
(2, 44, 6, 3, 1),
(13, 44, 5, 3, 1),
(14, 44, 4, 2, 1),
(14, 44, 11, 10, 1),
(7, 44, 6, 9, 1),
(7, 44, 8, 6, 1),
(8, 44, 8, 6, 1),
(9, 44, 8, 4, 1),
(12, 44, 4, 6, 1),
(15, 44, 9, 6, 1),
(16, 44, 9, 7, 1),
(5, 44, 3, 3, 1),
(6, 44, 3, 7, 1),
(21, 47, 11, 7, 1),
(8, 47, 4, 10, 1),
(10, 47, 7, 10, 1),
(12, 47, 7, 7, 1),
(15, 47, 4, 7, 1),
(5, 47, 2, 10, 1),
(6, 47, 12, 10, 1),
(19, 15, 1, 3, 1),
(19, 15, 8, 3, 1),
(6, 15, 1, 1, 1),
(3, 15, 2, 4, 1),
(14, 15, 4, 4, 1),
(18, 15, 3, 4, 1),
(1, 15, 1, 5, 1),
(21, 15, 1, 3, 1),
(21, 15, 7, 3, 1),
(3, 1, 1, 100, 1),
(2, 1, 1, 85, 1),
(1, 1, 1, 80, 1),
(12, 1, 2, 7, 1),
(11, 1, 1, 3, 1),
(11, 1, 2, 4, 1),
(19, 48, 1, 3, 1),
(19, 16, 1, 3, 1),
(19, 16, 8, 3, 1),
(2, 16, 5, 6, 1),
(21, 16, 1, 3, 1),
(21, 16, 7, 3, 1),
(1, 16, 1, 5, 1),
(15, 16, 1, 4, 1),
(3, 16, 2, 4, 1),
(14, 16, 4, 4, 1),
(18, 16, 3, 4, 1),
(6, 16, 1, 1, 1),
(5, 16, 1, 1, 1),
(5, 16, 4, 1, 1),
(19, 51, 1, 5, 1),
(6, 49, 2, 7, 1),
(19, 49, 1, 5, 1),
(19, 49, 4, 6, 1),
(5, 50, 1, 5, 1),
(5, 50, 4, 8, 1),
(5, 50, 8, 6, 1),
(19, 50, 1, 5, 1),
(6, 50, 1, 4, 1),
(11, 50, 1, 2, 1),
(19, 40, 1, 1, 1),
(10, 11, 1, 3, 1),
(10, 11, 2, 3, 1),
(3, 11, 1, 100, 1),
(2, 11, 1, 85, 1),
(1, 11, 1, 80, 1),
(11, 11, 2, 4, 1),
(12, 11, 2, 7, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `cod_partida` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `partidas`
--

INSERT INTO `partidas` (`id_partida`, `cod_partida`, `descripcion`, `estado`) VALUES
(1, '401', 'Personal (Sueldos, bonos, prestaciones)', 1),
(2, '402', 'Productos (Insumos, materiales, suministros)', 1),
(3, '403', 'Servicios (Consultoría, mantenimiento, transporte)', 1),
(4, '404', 'Bienes Muebles (Equipos, mobiliario, herramientas)', 1),
(5, '407', 'Ayudas y Becas (Subsidios, becas educativas)', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos_entrega`
--

CREATE TABLE `periodos_entrega` (
  `id_periodo` int(11) NOT NULL,
  `id_aniof` int(11) NOT NULL,
  `per_inicio` date NOT NULL,
  `per_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `periodos_entrega`
--

INSERT INTO `periodos_entrega` (`id_periodo`, `id_aniof`, `per_inicio`, `per_fin`, `activo`) VALUES
(1, 1, '2024-01-01', '2024-06-30', 0),
(2, 1, '2024-07-01', '2024-12-31', 0),
(3, 2, '2025-01-01', '2025-06-30', 0),
(4, 2, '2025-07-01', '2025-12-31', 0),
(5, 3, '2026-01-01', '2026-06-30', 0),
(6, 3, '2026-07-01', '2026-12-31', 0),
(7, 3, '2026-06-01', '2026-07-31', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_prod` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_partida` int(11) NOT NULL,
  `nom_prod` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_prod`, `id_proveedor`, `id_partida`, `nom_prod`, `precio`, `estado`) VALUES
(0, 1, 2, 'cocina electrica (unidad)', '22.00', 1),
(1, 1, 2, 'Cable eléctrico AWG 12', '15.50', 1),
(2, 1, 2, 'Interruptor termomagnético 20A', '8.75', 1),
(3, 2, 2, 'Resma de papel bond 80g', '12.00', 1),
(4, 2, 2, 'Tóner para impresora HP', '45.00', 1),
(5, 3, 4, 'Computadora portátil Dell', '850.00', 1),
(6, 3, 4, 'Monitor LCD 24 pulgadas', '120.00', 1),
(7, 4, 3, 'Mantenimiento de aire acondicionado', '200.00', 1),
(8, 4, 3, 'Reparación de motor eléctrico', '150.00', 1),
(9, 5, 3, 'Consultoría en gestión de proyectos', '500.00', 1),
(10, 5, 3, 'Auditoría de procesos', '600.00', 1),
(11, 6, 3, 'Servicio de transporte de carga', '300.00', 1),
(12, 6, 3, 'Almacenaje mensual', '250.00', 1),
(13, 7, 2, 'Guantes quirúrgicos (caja)', '25.00', 1),
(14, 7, 2, 'Jeringas desechables (100 u)', '18.00', 1),
(15, 8, 3, 'Póliza de seguro de vida', '100.00', 1),
(16, 9, 3, 'Asesoría en impuestos municipales', '350.00', 1),
(17, 10, 3, 'Campaña publicitaria en redes', '700.00', 1),
(18, 1, 2, 'cocina electrica (caja)', '45.90', 1),
(19, 3, 5, 'lskdjfoisf', '0.42', 1),
(20, 2, 3, 'cocina electrica (caja)', '70000.00', 1),
(21, 5, 1, 'obreros', '200.00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nom_prov` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nom_prov`, `descripcion`, `estado`) VALUES
(1, 'Distribuidora Eléctrica C.A.', 'Material eléctrico y equipos', 1),
(2, 'Papelería Central', 'Útiles de oficina y papelería', 1),
(3, 'TecnoSoluciones 2025', 'Equipos de cómputo y redes', 1),
(4, 'Mantenimientos Industriales', 'Servicios de mantenimiento', 1),
(5, 'Consultores Asociados', 'Servicios de consultoría', 1),
(6, 'Logística Rápida', 'Transporte y almacenaje', 1),
(7, 'Suministros Médicos', 'Insumos de salud', 1),
(8, 'Seguros del Centro', 'Pólizas de seguros', 1),
(9, 'Impuestos Express', 'Asesoría fiscal', 1),
(10, 'Publicidad Creativa', 'Diseño y campañas publicitarias', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requerimientos`
--

CREATE TABLE `requerimientos` (
  `id_req` int(11) NOT NULL,
  `id_dep` int(11) NOT NULL,
  `id_tasa` int(11) NOT NULL,
  `id_aniof` int(11) NOT NULL,
  `estado_envio` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_env` date NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `requerimientos`
--

INSERT INTO `requerimientos` (`id_req`, `id_dep`, `id_tasa`, `id_aniof`, `estado_envio`, `fecha_env`, `estado`) VALUES
(1, 1, 1, 1, 1, '2024-01-10', 0),
(2, 2, 2, 1, 1, '2024-02-15', 0),
(3, 3, 3, 1, 0, '2024-03-20', 0),
(4, 4, 4, 1, 1, '2024-04-05', 0),
(5, 5, 5, 2, 0, '2025-01-25', 0),
(6, 1, 6, 2, 1, '2025-02-28', 0),
(7, 2, 7, 2, 1, '2025-03-15', 0),
(8, 3, 8, 2, 0, '2025-04-10', 0),
(9, 6, 9, 2, 0, '2025-05-12', 0),
(10, 7, 10, 2, 1, '2025-06-18', 0),
(11, 1, 11, 3, 1, '2026-01-20', 1),
(12, 2, 12, 3, 0, '2026-02-14', 1),
(13, 3, 13, 3, 1, '2026-03-22', 1),
(14, 4, 14, 3, 0, '2026-04-05', 1),
(15, 8, 5, 1, 1, '2026-06-30', 0),
(16, 8, 10, 4, 1, '2026-06-27', 0),
(17, 8, 10, 4, 0, '2026-06-27', 0),
(18, 8, 10, 4, 0, '2026-06-27', 0),
(19, 8, 10, 4, 0, '2026-06-27', 0),
(20, 8, 10, 4, 0, '2026-06-27', 0),
(21, 8, 10, 4, 0, '2026-06-27', 0),
(22, 8, 10, 4, 0, '2026-06-27', 0),
(23, 8, 10, 4, 0, '2026-06-27', 0),
(24, 10, 10, 4, 0, '2026-06-27', 0),
(25, 11, 10, 3, 0, '2026-06-27', 1),
(26, 11, 10, 3, 0, '2026-06-27', 1),
(27, 11, 10, 3, 0, '2026-06-27', 1),
(28, 12, 10, 3, 0, '2026-06-27', 1),
(29, 13, 10, 3, 0, '2026-06-27', 1),
(30, 13, 10, 3, 0, '2026-06-27', 1),
(31, 13, 10, 3, 0, '2026-06-27', 1),
(32, 13, 10, 3, 0, '2026-06-27', 1),
(33, 13, 10, 3, 0, '2026-06-27', 1),
(34, 13, 10, 3, 0, '2026-06-27', 1),
(35, 13, 10, 3, 0, '2026-06-27', 1),
(36, 13, 10, 3, 0, '2026-06-27', 1),
(37, 14, 10, 3, 0, '2026-06-27', 1),
(38, 14, 10, 3, 0, '2026-06-27', 1),
(39, 15, 10, 3, 0, '2026-06-27', 1),
(40, 8, 10, 3, 0, '2026-06-28', 1),
(41, 8, 10, 3, 0, '2026-06-28', 1),
(42, 9, 10, 3, 0, '2026-06-30', 1),
(43, 16, 10, 3, 0, '2026-06-30', 1),
(44, 17, 10, 3, 0, '2026-06-30', 1),
(47, 18, 10, 3, 1, '2026-06-30', 1),
(48, 19, 10, 3, 1, '2026-07-01', 1),
(49, 20, 10, 3, 1, '2026-07-03', 1),
(50, 21, 10, 3, 0, '2026-07-03', 1),
(51, 22, 10, 3, 0, '2026-07-05', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `responsables`
--

CREATE TABLE `responsables` (
  `id_responsable` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nom_rep` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `password` varchar(120) COLLATE utf8_spanish_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `responsables`
--

INSERT INTO `responsables` (`id_responsable`, `id_rol`, `nom_rep`, `password`, `estado`) VALUES
(1, 1, 'Ana María Pérez', 'admin123', 1),
(2, 2, 'Luis Rodríguez', 'jefe123', 0),
(3, 2, 'Carlos Gómez', 'analista1', 1),
(4, 2, 'María Fernández', 'coord1', 1),
(5, 2, 'José Ramírez', 'director1', 1),
(6, 2, 'Laura Martínez', 'asist1', 1),
(7, 2, 'Pedro Sánchez', 'superv1', 1),
(8, 2, 'Sofía Herrera', 'jefe2', 1),
(9, 2, 'Jorge Díaz', 'analista2', 1),
(10, 2, 'Elena Castro', 'coord2', 1),
(11, 2, 'pepe', '$2y$10$gBndiZQSy3Rp5MT2fTmFh.2euhEmWnw/VkiMIX5yJ2XFND8bJ1ENK', 1),
(12, 1, 'diego', '$2y$10$5BkT1/Pj06r7e8vcyV67v.Tujh8xx4iu0w5JMxamEMrb3F3EJ0nhK', 1),
(13, 2, 'ronaldo', '$2y$10$iwY6wy/yEbZ/OOjdsTd9n.aL4c4Jpg6iMloIjN49nch52d1E9GnhO', 1),
(14, 2, 'che', '$2y$10$G6Ku9B8HgEAhSH/FJmToteads3OL2fXcf6HVJhTkbfZ3Qc77x2Sge', 1),
(15, 2, 'JV', '$2y$10$sl2fdBuAEomUhylJCi15/.9zdoRduQb/cENpOPQakl3RS7ohr1Cbm', 1),
(16, 2, 'pedro', '$2y$10$wa1GsdLxLSm8/ehAkxU.VuD2aWFMSIOH4r30cO.aKAS1SQ5RI5qpy', 1),
(17, 2, 'steve', '$2y$10$XQsd//P0vaV.s8wiZ9vCye40T5e.bS2Jl0JkNGfwYoJ9UA361M8Je', 1),
(18, 2, 'abc', '$2y$10$vR9/uO2hkbu9tKraBS05runSC92Gsd1pMAtbzO3K/n4e0s7erI5Ke', 1),
(19, 2, 'abc', '$2y$10$Ug0yClzlD.I7mZE3GoKPs.uFJwcI66YfQ2NESB5tso0DOI2Mpp5Ea', 1),
(20, 2, 'abc', '$2y$10$FjZ1oh3UlfWXLkgg0658xOl/QrxwJIIzvhiJ1FaPaYa8LF9BaYU6u', 1),
(21, 2, 'master', '$2y$10$jiCIlK6Ro1rMFy6N04DsI.HpwXX2ChPhWtfWMjvuA20uHigrKSyL2', 1),
(22, 2, 'nashe', '$2y$10$BsatO0goLoCtHPCy8gDjB.xKJk10K1ZLkSCOTVhZ5YciWZCMJb/ji', 1),
(23, 2, 'chavo', '$2y$10$jWrWFG1Jx/l9Qh33TInbVO2Rzi7w7uyMLzsrOtdNjMto3MlQWVX7W', 1),
(24, 2, '1234', '$2y$10$R2ojrVRq2gvJoHwuPA0m5.Mw2.72QaCIFE0lkxlTy4lQXiLUyuDcu', 1),
(25, 2, 'baki', '$2y$10$T.vMUh0HijGEMVPXnCWB1.uu9S3Zsr03L27dtqPj8rC5ejHTE18l2', 1),
(26, 2, 'Yujiro', '$2y$10$7z4wE.99FXkb/f4W3f8GZ.zIYin1LoDal4dhgBtseJdCPKc7mwO6a', 1),
(27, 2, 'Yujiro', '$2y$10$E5CUKr0B.pPz7nXXx85aa.etDz4Karz0tQFDuwxsEPyj/cSP4wMja', 1),
(28, 2, 'yuchiro', '$2y$10$Hl.t/CM4DD2WW8v/VtmGQOLRY6vSCh60VytKOpofJ7qUepcXn4Lq.', 1),
(29, 2, 'ronaldiño', '$2y$10$MZ/S0ZlRBZywZxuzLDP29Ous2h54/hkC4CFKu2ElsJ/UKJfbrOB2C', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `descripcion` varchar(70) COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `descripcion`) VALUES
(1, 'Administrador'),
(2, 'Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasa_bcv`
--

CREATE TABLE `tasa_bcv` (
  `id_tasa` int(11) NOT NULL,
  `fecha_reg` date NOT NULL,
  `tasa_bcv_usd` decimal(12,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tasa_bcv`
--

INSERT INTO `tasa_bcv` (`id_tasa`, `fecha_reg`, `tasa_bcv_usd`, `estado`) VALUES
(1, '2025-01-15', '60.25', 0),
(2, '2025-02-15', '61.10', 0),
(3, '2025-03-15', '62.45', 0),
(4, '2025-04-15', '63.00', 0),
(5, '2025-05-15', '64.20', 0),
(6, '2025-06-15', '65.50', 0),
(7, '2025-07-15', '66.75', 0),
(8, '2025-08-15', '67.90', 0),
(9, '2025-09-15', '68.30', 0),
(10, '2025-10-15', '69.00', 1),
(11, '2025-11-15', '70.10', 0),
(12, '2025-12-15', '71.25', 0),
(13, '2026-01-15', '72.40', 0),
(14, '2026-02-15', '73.50', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefonos`
--

CREATE TABLE `telefonos` (
  `id_telf` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `telefono` varchar(20) COLLATE utf8_spanish_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `telefonos`
--

INSERT INTO `telefonos` (`id_telf`, `id_proveedor`, `telefono`, `estado`) VALUES
(1, 1, '+58-212-555-1001', 1),
(2, 1, '+58-212-555-1002', 1),
(3, 2, '+58-212-555-2001', 1),
(4, 3, '+58-212-555-3001', 1),
(5, 3, '+58-212-555-3002', 1),
(6, 4, '+58-212-555-4001', 1),
(7, 5, '+58-212-555-5001', 1),
(8, 6, '+58-212-555-6001', 1),
(9, 7, '+58-212-555-7001', 1),
(10, 8, '+58-212-555-8001', 1),
(11, 9, '+58-212-555-9001', 1),
(12, 10, '+58-212-555-0001', 1),
(13, 1, 'fasdf', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `anio_fiscal`
--
ALTER TABLE `anio_fiscal`
  ADD PRIMARY KEY (`id_aniof`);

--
-- Indices de la tabla `cargo`
--
ALTER TABLE `cargo`
  ADD PRIMARY KEY (`id_cargo`),
  ADD KEY `id_responsable` (`id_responsable`),
  ADD KEY `id_dep` (`id_dep`);

--
-- Indices de la tabla `dependencias`
--
ALTER TABLE `dependencias`
  ADD PRIMARY KEY (`id_dep`);

--
-- Indices de la tabla `detalle_req`
--
ALTER TABLE `detalle_req`
  ADD KEY `id_req` (`id_req`),
  ADD KEY `id_prod` (`id_prod`);

--
-- Indices de la tabla `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id_partida`);

--
-- Indices de la tabla `periodos_entrega`
--
ALTER TABLE `periodos_entrega`
  ADD PRIMARY KEY (`id_periodo`),
  ADD KEY `id_aniof` (`id_aniof`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_prod`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_partida` (`id_partida`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `requerimientos`
--
ALTER TABLE `requerimientos`
  ADD PRIMARY KEY (`id_req`),
  ADD KEY `id_dep` (`id_dep`),
  ADD KEY `id_tasa` (`id_tasa`),
  ADD KEY `id_aniof` (`id_aniof`);

--
-- Indices de la tabla `responsables`
--
ALTER TABLE `responsables`
  ADD PRIMARY KEY (`id_responsable`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tasa_bcv`
--
ALTER TABLE `tasa_bcv`
  ADD PRIMARY KEY (`id_tasa`);

--
-- Indices de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  ADD PRIMARY KEY (`id_telf`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `anio_fiscal`
--
ALTER TABLE `anio_fiscal`
  MODIFY `id_aniof` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cargo`
--
ALTER TABLE `cargo`
  MODIFY `id_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `dependencias`
--
ALTER TABLE `dependencias`
  MODIFY `id_dep` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `periodos_entrega`
--
ALTER TABLE `periodos_entrega`
  MODIFY `id_periodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `requerimientos`
--
ALTER TABLE `requerimientos`
  MODIFY `id_req` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `responsables`
--
ALTER TABLE `responsables`
  MODIFY `id_responsable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tasa_bcv`
--
ALTER TABLE `tasa_bcv`
  MODIFY `id_tasa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  MODIFY `id_telf` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cargo`
--
ALTER TABLE `cargo`
  ADD CONSTRAINT `cargo_ibfk_1` FOREIGN KEY (`id_responsable`) REFERENCES `responsables` (`id_responsable`),
  ADD CONSTRAINT `cargo_ibfk_2` FOREIGN KEY (`id_dep`) REFERENCES `dependencias` (`id_dep`);

--
-- Filtros para la tabla `detalle_req`
--
ALTER TABLE `detalle_req`
  ADD CONSTRAINT `detalle_req_ibfk_2` FOREIGN KEY (`id_req`) REFERENCES `requerimientos` (`id_req`),
  ADD CONSTRAINT `id_prod` FOREIGN KEY (`id_prod`) REFERENCES `productos` (`id_prod`);

--
-- Filtros para la tabla `periodos_entrega`
--
ALTER TABLE `periodos_entrega`
  ADD CONSTRAINT `periodos_entrega_ibfk_1` FOREIGN KEY (`id_aniof`) REFERENCES `anio_fiscal` (`id_aniof`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`);

--
-- Filtros para la tabla `requerimientos`
--
ALTER TABLE `requerimientos`
  ADD CONSTRAINT `requerimientos_ibfk_1` FOREIGN KEY (`id_dep`) REFERENCES `dependencias` (`id_dep`),
  ADD CONSTRAINT `requerimientos_ibfk_2` FOREIGN KEY (`id_tasa`) REFERENCES `tasa_bcv` (`id_tasa`),
  ADD CONSTRAINT `requerimientos_ibfk_3` FOREIGN KEY (`id_aniof`) REFERENCES `anio_fiscal` (`id_aniof`);

--
-- Filtros para la tabla `responsables`
--
ALTER TABLE `responsables`
  ADD CONSTRAINT `responsables_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `telefonos`
--
ALTER TABLE `telefonos`
  ADD CONSTRAINT `telefonos_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
