-- phpMyAdmin SQL Dump
-- version 5.2.3-1.el10_1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 09-01-2026 a las 19:03:37
-- Versión del servidor: 10.11.11-MariaDB
-- Versión de PHP: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cable_santana`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `tabla_afectada` varchar(100) DEFAULT NULL,
  `registro_afectado_id` int(11) DEFAULT NULL,
  `detalle_anterior` text DEFAULT NULL,
  `detalle_nuevo` text DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `tabla_afectada`, `registro_afectado_id`, `detalle_anterior`, `detalle_nuevo`, `direccion_ip`, `fecha_accion`) VALUES
(1, 1, 'Cliente creado', '0', 1, NULL, '0', '::1', '2025-09-15 23:56:47'),
(2, 1, 'Cliente creado', '0', 2, NULL, '0', '::1', '2025-09-16 00:10:28'),
(3, 1, 'Cliente creado', '0', 3, NULL, '0', '::1', '2025-09-16 00:15:37'),
(4, 1, 'Cliente eliminado', '0', 1, '{\"id\":1,\"dni\":\"28610711\",\"nombre\":\"claudio\",\"apellido\":\"lex\",\"direccion\":\"tucuman 2266\",\"correo_electronico\":\"lexclaudio@gmail.com\",\"cuotacable\":\"15000.00\",\"cuotainternet\":\"0.00\",\"cuotacableinternet\":\"0.00\",\"notas_cliente\":\"\",\"fecha_registro\":\"2025-09-15 20:56:47\"}', NULL, '::1', '2025-09-16 00:16:32'),
(5, 1, 'Cliente eliminado', '0', 3, '{\"id\":3,\"dni\":\"28610712\",\"nombre\":\"claudio\",\"apellido\":\"lexx\",\"direccion\":\"San Martin 2162 Posadas Misones\",\"correo_electronico\":\"lexclaudio@gmail.comm\",\"cuotacable\":\"0.00\",\"cuotainternet\":\"25000.00\",\"cuotacableinternet\":\"0.00\",\"notas_cliente\":\"\",\"fecha_registro\":\"2025-09-15 21:15:37\"}', NULL, '::1', '2025-09-16 00:16:35'),
(6, 1, 'Cliente eliminado', '0', 2, '{\"id\":2,\"dni\":\"42764703\",\"nombre\":\"test\",\"apellido\":\"test\",\"direccion\":\"tucuman 2266\",\"correo_electronico\":\"test@test.com\",\"cuotacable\":\"0.00\",\"cuotainternet\":\"0.00\",\"cuotacableinternet\":\"30000.00\",\"notas_cliente\":\"\",\"fecha_registro\":\"2025-09-15 21:10:28\"}', NULL, '::1', '2025-09-16 00:16:38'),
(7, 1, 'Cliente creado', '0', 4, NULL, '0', '::1', '2025-09-16 00:16:49'),
(8, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 00:29:09'),
(9, 1, 'Cliente creado', '0', 5, NULL, '0', '::1', '2025-09-16 00:30:34'),
(10, 1, 'Usuario creado', '0', 2, NULL, '0', '::1', '2025-09-16 00:34:10'),
(11, 2, 'Inicio de sesión exitoso', '0', 2, NULL, NULL, '::1', '2025-09-16 00:34:20'),
(12, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 00:40:10'),
(13, 2, 'Inicio de sesión exitoso', '0', 2, NULL, NULL, '::1', '2025-09-16 00:40:23'),
(14, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 00:40:41'),
(15, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 19:50:37'),
(16, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 20:38:51'),
(17, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 20:48:06'),
(18, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 20:51:07'),
(19, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-16 21:12:27'),
(20, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-18 02:18:54'),
(21, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-18 02:19:15'),
(22, 2, 'Inicio de sesión exitoso', '0', 2, NULL, NULL, '::1', '2025-09-18 02:19:36'),
(23, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '::1', '2025-09-18 02:24:19'),
(24, 1, 'Usuario creado', '0', 4, NULL, '0', '::1', '2025-09-18 02:29:22'),
(25, 4, 'Inicio de sesión exitoso', '0', 4, NULL, NULL, '::1', '2025-09-18 02:34:05'),
(26, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 12:42:08'),
(27, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 12:50:10'),
(28, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 13:01:21'),
(29, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 13:39:08'),
(30, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 13:43:57'),
(31, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 14:10:10'),
(32, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 17:24:48'),
(33, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-22 20:33:56'),
(34, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-23 10:29:08'),
(35, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-24 10:58:32'),
(36, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-09-25 15:34:58'),
(37, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-10-08 13:16:20'),
(38, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-10-26 16:40:06'),
(39, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-11-08 16:35:05'),
(40, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-11-27 13:17:47'),
(41, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-11-27 13:19:14'),
(42, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-12-04 12:58:54'),
(43, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-12-04 13:01:48'),
(44, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2025-12-05 18:53:16'),
(45, NULL, 'Intento de inicio de sesión fallido', '0', NULL, '{\"nombre_usuario\":\"admin\"}', NULL, '192.168.10.2', '2026-01-02 11:23:21'),
(46, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 11:23:24'),
(47, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 11:58:24'),
(48, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 12:19:27'),
(49, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 12:31:36'),
(50, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 12:36:29'),
(51, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 12:43:59'),
(52, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 12:51:00'),
(53, 1, 'Cliente eliminado', '0', 4, '{\"id\":4,\"dni\":\"28610711\",\"nombre\":\"claudio\",\"apellido\":\"lex\",\"direccion\":\"San Martin 2162 Posadas Misones\",\"correo_electronico\":\"lexclaudio@gmail.com\",\"notas_cliente\":\"\",\"fecha_registro\":\"2025-09-15 21:16:49\"}', NULL, '192.168.10.2', '2026-01-02 13:19:00'),
(54, 1, 'Cliente eliminado', '0', 5, '{\"id\":5,\"dni\":\"42764703\",\"nombre\":\"adela\",\"apellido\":\"olivera\",\"direccion\":\"san juan 3472\",\"correo_electronico\":\"adela@adela.com\",\"notas_cliente\":\"\",\"fecha_registro\":\"2025-09-15 21:30:34\"}', NULL, '192.168.10.2', '2026-01-02 13:19:03'),
(55, 1, 'Cliente eliminado', '0', 7, '{\"id\":7,\"dni\":\"12345678\",\"nombre\":\"test\",\"apellido\":\"test\",\"direccion\":\"test\",\"correo_electronico\":\"test@test.com\",\"notas_cliente\":\"\",\"fecha_registro\":\"2026-01-02 08:56:00\"}', NULL, '192.168.10.2', '2026-01-02 13:19:07'),
(56, 1, 'Cliente eliminado', '0', 14, '{\"id\":14,\"dni\":\"87654321\",\"nombre\":\"testest\",\"apellido\":\"testetes\",\"direccion\":\"testestset\",\"correo_electronico\":\"test2@test.com\",\"notas_cliente\":\"\",\"fecha_registro\":\"2026-01-02 09:20:42\"}', NULL, '192.168.10.2', '2026-01-02 13:19:10'),
(57, 1, 'Cliente creado', '0', 15, NULL, '0', '192.168.10.2', '2026-01-02 13:19:30'),
(58, 1, 'Cliente creado', '0', 17, NULL, '0', '192.168.10.2', '2026-01-02 13:20:25'),
(59, 1, 'Cliente creado', '0', 18, NULL, '0', '192.168.10.2', '2026-01-02 13:20:49'),
(60, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 13:36:45'),
(61, 1, 'Cliente creado', '0', 19, NULL, '0', '192.168.10.2', '2026-01-02 14:15:45'),
(62, 1, 'Cliente creado', '0', 20, NULL, '0', '192.168.10.2', '2026-01-02 14:18:11'),
(63, 1, 'Cliente actualizado', '0', 20, '{\"id\":20,\"dni\":\"99999999\",\"nombre\":\"TestEdit\",\"apellido\":\"User\",\"direccion\":\"Direccion Test\",\"correo_electronico\":\"testedit@example.com\",\"notas_cliente\":null,\"fecha_registro\":\"2026-01-02 11:18:11\"}', '0', '192.168.10.2', '2026-01-02 14:18:11'),
(64, 1, 'Cliente eliminado', '0', 20, '{\"id\":20,\"dni\":\"99999999\",\"nombre\":\"TestEditUpdated\",\"apellido\":\"User\",\"direccion\":\"Nueva Direccion\",\"correo_electronico\":\"testedit@example.com\",\"notas_cliente\":null,\"fecha_registro\":\"2026-01-02 11:18:11\"}', NULL, '192.168.10.2', '2026-01-02 14:18:11'),
(65, 1, 'Cliente creado', '0', 21, NULL, '0', '192.168.10.2', '2026-01-02 14:20:04'),
(66, 1, 'Cliente creado', '0', 22, NULL, '0', '192.168.10.2', '2026-01-02 14:23:56'),
(67, 1, 'Usuario creado', '0', 11, NULL, '0', '192.168.10.2', '2026-01-02 14:23:56'),
(68, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 14:25:58'),
(69, 1, 'Cliente creado', '0', 23, NULL, '0', '192.168.10.2', '2026-01-02 14:42:10'),
(70, 1, 'Usuario eliminado', '0', 11, '{\"id\":11,\"cliente_id\":22,\"nombre_usuario\":\"userpasado1\",\"contrasena\":\"$2y$10$D59z9RpE3oI40iUveaumweNqZ98xjbykbyhAan6S8Zqib8Cs8A8Yu\",\"email\":\"clientepasado1@test.com\",\"rol\":\"cliente\",\"fecha_creacion\":\"2026-01-02 11:23:56\",\"activo\":1}', NULL, '192.168.10.2', '2026-01-02 14:43:16'),
(71, 1, 'Usuario creado', '0', 12, NULL, '0', '192.168.10.2', '2026-01-02 14:43:39'),
(72, 12, 'Inicio de sesión exitoso', '0', 12, NULL, NULL, '192.168.10.2', '2026-01-02 14:43:52'),
(73, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 14:50:12'),
(74, 1, 'Cliente eliminado', '0', 22, '{\"id\":22,\"dni\":\"TEST24681\",\"nombre\":\"ClientePasado1\",\"apellido\":\"Test\",\"direccion\":\"Direccion Test 1\",\"correo_electronico\":\"clientepasado1@test.com\",\"notas_cliente\":null,\"fecha_registro\":\"2025-12-02 14:23:56\"}', NULL, '192.168.10.2', '2026-01-02 14:50:29'),
(75, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 19:54:17'),
(76, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 21:04:31'),
(77, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-05 11:25:18'),
(78, 1, 'Cliente creado', '0', 24, NULL, '0', '192.168.10.2', '2026-01-05 11:26:04'),
(79, NULL, 'Intento de inicio de sesión fallido', '0', NULL, '{\"nombre_usuario\":\"admin\"}', NULL, '186.138.152.253', '2026-01-08 22:00:45'),
(80, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '186.138.152.253', '2026-01-08 22:00:51'),
(81, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '186.138.152.253', '2026-01-08 22:02:51'),
(82, 1, 'Cliente creado', '0', 25, NULL, '0', '186.138.152.253', '2026-01-08 23:57:18'),
(83, 1, 'Cliente creado', '0', 26, NULL, '0', '186.138.152.253', '2026-01-08 23:57:51'),
(84, 1, 'Registro de Pago', '0', 16, NULL, '0', '186.138.152.253', '2026-01-08 23:59:02'),
(85, 1, 'Registro de Pago', '0', 17, NULL, '0', '186.138.152.253', '2026-01-08 23:59:26'),
(86, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '138.117.78.146', '2026-01-09 11:59:19'),
(87, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '138.117.78.146', '2026-01-09 13:37:56'),
(88, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '186.138.152.253', '2026-01-09 18:52:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `correo_electronico` varchar(100) NOT NULL,
  `notas_cliente` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `apellido`, `dni`, `direccion`, `correo_electronico`, `notas_cliente`, `fecha_registro`) VALUES
(15, 'claudio', 'lex', '28610711', 'San Martin 2162 Posadas Misones', 'lexclaudio@gmail.com', '', '2026-01-02 13:19:30'),
(17, 'test', 'test', '22835736', 'testestset', 'test@test.com', '', '2026-01-02 13:20:25'),
(18, 'test2', 'test2', '42764702', 'test2test2', 'test2@test.com', '', '2026-01-02 13:20:49'),
(19, 'testdeltest', 'testestestest', '34366060', 'testdeltest', 'test1@test.com', '', '2026-01-02 14:15:45'),
(21, 'maria celia', 'mercado', '10001933', 'san juan 3472', 'cory45890@gmail.com', '', '2026-01-02 14:20:04'),
(23, 'ivan ', 'bratko', '22835733', 'notiene', 'notiene@notiene.com', '', '2026-01-02 14:42:10'),
(24, 'roberto', 'aguirre', '18745454', 'falsa 123', 'roberte23232@gsmail.com', '', '2026-01-05 11:26:04'),
(25, 'Florencia', 'Olivera', '44951371', 'Pincen 820', 'flor@florqueteimporta.com', '', '2026-01-08 23:57:18'),
(26, 'Adela', 'Olivera', '42764703', 'Calle San juan 3472', 'adela@adela.com', '', '2026-01-08 23:57:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'churn_rate', '5', 'Tasa de cancelación mensual estimada (%)', '2026-01-05 11:27:05'),
(2, 'recargo_mora', '1000', 'Monto fijo de recargo para clientes con deuda', '2026-01-08 22:52:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudas`
--

CREATE TABLE `deudas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagado','vencido') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `suscripcion_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `estado` enum('pendiente','pagada','fallida','vencida','anulada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `suscripcion_id`, `cliente_id`, `monto`, `fecha_emision`, `fecha_vencimiento`, `fecha_pago`, `estado`) VALUES
(2, 3, 15, 25000.00, '2026-01-02', '2026-02-02', NULL, 'pagada'),
(3, 4, 17, 17000.00, '2026-01-02', '2026-02-02', NULL, 'pagada'),
(4, 5, 18, 13000.00, '2026-01-02', '2026-02-02', NULL, 'pagada'),
(5, 6, 19, 25000.00, '2026-01-02', '2026-02-02', NULL, 'pagada'),
(6, 7, 21, 17000.00, '2026-01-02', '2026-02-02', NULL, 'pendiente'),
(7, 8, 23, 13000.00, '2026-01-02', '2026-02-02', NULL, 'pagada'),
(8, 9, 24, 25000.00, '2026-01-05', '2026-02-05', NULL, 'pagada'),
(9, 3, 15, 25000.00, '2025-10-08', '2025-10-08', NULL, 'vencida'),
(10, 3, 15, 25000.00, '2025-11-08', '2025-11-08', NULL, 'vencida'),
(11, 4, 17, 17000.00, '2025-09-08', '2025-09-08', NULL, 'vencida'),
(12, 4, 17, 17000.00, '2025-10-08', '2025-10-08', NULL, 'vencida'),
(13, 4, 17, 17000.00, '2025-11-08', '2025-11-08', NULL, 'vencida'),
(14, 3, 15, 25000.00, '2025-08-08', '2025-08-08', NULL, 'vencida'),
(15, 3, 15, 25000.00, '2025-07-08', '2025-07-08', NULL, 'vencida'),
(16, 4, 17, 17000.00, '2025-08-08', '2025-08-08', NULL, 'vencida'),
(17, 4, 17, 17000.00, '2025-07-08', '2025-07-08', NULL, 'vencida'),
(18, 10, 25, 17000.00, '2026-01-08', '2026-02-08', NULL, 'pagada'),
(19, 11, 26, 25000.00, '2026-01-08', '2026-02-08', NULL, 'pagada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`id`, `nombre`) VALUES
(1, 'Efectivo'),
(2, 'Transferencia'),
(3, 'Mercado Pago'),
(4, 'Tarjeta de Débito'),
(5, 'Tarjeta de Crédito');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago_archivado`
--

CREATE TABLE `metodos_pago_archivado` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `gateway` enum('sim_tarjeta','sim_mercadopago') NOT NULL,
  `token_gateway` varchar(255) NOT NULL COMMENT 'ID del cliente/tarjeta en la pasarela de pago',
  `descripcion` varchar(100) DEFAULT NULL COMMENT 'Ej: Visa terminada en 4242',
  `es_predeterminado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('exitoso','fallido') NOT NULL,
  `metodo_pago_id` int(11) NOT NULL,
  `gateway_transaccion_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `factura_id`, `monto`, `fecha_pago`, `estado`, `metodo_pago_id`, `gateway_transaccion_id`) VALUES
(10, 2, 25000.00, '2026-01-02 03:00:00', 'exitoso', 1, ''),
(11, 3, 17000.00, '2026-01-02 03:00:00', 'exitoso', 3, ''),
(12, 4, 13000.00, '2026-01-02 03:00:00', 'exitoso', 2, ''),
(14, 7, 13000.00, '2026-01-02 14:42:26', 'exitoso', 1, ''),
(15, 8, 25000.00, '2026-01-05 11:26:41', 'exitoso', 1, 'subcripcion mensual de cable'),
(16, 18, 18000.00, '2026-01-08 23:59:02', 'exitoso', 1, 'subcripcion mensual'),
(17, 19, 26000.00, '2026-01-08 23:59:26', 'exitoso', 3, 'subcripcion mensual ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` int(11) NOT NULL,
  `nombre_plan` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_mensual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo_tarifa` enum('fija','por_uso') NOT NULL DEFAULT 'fija',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_facturacion` varchar(50) NOT NULL DEFAULT 'fija'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `nombre_plan`, `descripcion`, `precio_mensual`, `tipo_tarifa`, `activo`, `fecha_creacion`, `tipo_facturacion`) VALUES
(1, 'Plan Básico', 'Plan de servicio básico para nuevos clientes', 0.00, 'fija', 0, '2025-09-16 00:30:34', 'fija'),
(2, 'Plan Solo Internet', 'Ideal para uso doméstico ligero y redes sociales.', 17000.00, 'fija', 1, '2025-09-16 20:33:11', 'fija'),
(3, 'Solo Cable ', 'Perfecto para familias, teletrabajo.', 13000.00, 'fija', 1, '2025-09-16 20:33:11', 'fija'),
(4, 'Cable e Internet ', 'Internet + Television Full HD', 25000.00, 'fija', 1, '2025-09-16 20:33:11', 'fija');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

CREATE TABLE `suscripciones` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `estado` enum('activa','pausada','cancelada','en_prueba') NOT NULL DEFAULT 'activa',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_proximo_cobro` date NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `cliente_id`, `plan_id`, `estado`, `fecha_inicio`, `fecha_fin`, `fecha_proximo_cobro`, `fecha_creacion`) VALUES
(3, 15, 4, 'activa', '2026-01-02', NULL, '2026-02-02', '2026-01-02 13:19:30'),
(4, 17, 2, 'activa', '2026-01-02', NULL, '2026-02-02', '2026-01-02 13:20:25'),
(5, 18, 3, 'activa', '2026-01-02', NULL, '2026-02-02', '2026-01-02 13:20:49'),
(6, 19, 4, 'activa', '2026-01-02', NULL, '2026-02-02', '2026-01-02 14:15:45'),
(7, 21, 2, 'activa', '2026-01-02', NULL, '2026-02-02', '2026-01-02 14:20:04'),
(8, 23, 3, 'activa', '2026-01-02', NULL, '2026-02-02', '2026-01-02 14:42:10'),
(9, 24, 4, 'activa', '2026-01-05', NULL, '2026-02-05', '2026-01-05 11:26:04'),
(10, 25, 2, 'activa', '2026-01-08', NULL, '2026-02-08', '2026-01-08 23:57:18'),
(11, 26, 4, 'activa', '2026-01-08', NULL, '2026-02-08', '2026-01-08 23:57:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripcion_cupon`
--

CREATE TABLE `suscripcion_cupon` (
  `suscripcion_id` int(11) NOT NULL,
  `cupon_id` int(11) NOT NULL,
  `fecha_aplicacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` enum('administrador','editor','cliente') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `cliente_id`, `nombre_usuario`, `contrasena`, `email`, `rol`, `activo`, `fecha_creacion`) VALUES
(1, NULL, 'admin', '$2y$10$FFNbCSUg43YxqnrI9zN2MeNz5bTSEPvl.fwiB8BiLPyepPrNwADrK', NULL, 'administrador', 1, '2025-09-15 23:55:51'),
(2, NULL, 'test', '$2y$10$S/tVnBDeaO//ddb8ri7mTecJFNmnbO8zXYZsGzXM2AgX3ulKhQfKS', 'tests@test.com', 'cliente', 1, '2025-09-16 00:34:10'),
(4, NULL, 'editor', '$2y$10$QjQWggMVH9A6jI2RbYZVJOvTjlJzPrSv0yNpTD83F28cudDzCJjxK', 'editor@editor.com', 'editor', 1, '2025-09-18 02:29:22'),
(12, NULL, 'gestion', '$2y$10$588Xu/Oz5ByMX6aioQCiRexWTBchovL/1sgm5e8JYUToem7lCOHG6', 'gestion@gestion.com', 'editor', 1, '2026-01-02 14:43:39');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_cortes_servicio`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_cortes_servicio` (
`cliente_id` int(11)
,`dni` varchar(20)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`direccion` varchar(255)
,`correo_electronico` varchar(100)
,`facturas_adeudadas` bigint(21)
,`total_deuda` decimal(32,2)
,`fecha_vencimiento_mas_antigua` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_deudores`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_deudores` (
`cliente_id` int(11)
,`dni` varchar(20)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`direccion` varchar(255)
,`correo_electronico` varchar(100)
,`facturas_adeudadas` bigint(21)
,`total_deuda` decimal(32,2)
,`fecha_vencimiento_mas_antigua` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_servicio_cortado`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_servicio_cortado` (
`cliente_id` int(11)
,`dni` varchar(20)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`direccion` varchar(255)
,`correo_electronico` varchar(100)
,`facturas_adeudadas` bigint(21)
,`total_deuda_acumulada` decimal(32,2)
,`fecha_vencimiento_mas_antigua` date
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `deudas`
--
ALTER TABLE `deudas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_deudas_usuario` (`usuario_id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suscripcion_id` (`suscripcion_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metodos_pago_archivado`
--
ALTER TABLE `metodos_pago_archivado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `metodo_pago_id` (`metodo_pago_id`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indices de la tabla `suscripcion_cupon`
--
ALTER TABLE `suscripcion_cupon`
  ADD PRIMARY KEY (`suscripcion_id`,`cupon_id`),
  ADD KEY `cupon_id` (`cupon_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `deudas`
--
ALTER TABLE `deudas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `metodos_pago_archivado`
--
ALTER TABLE `metodos_pago_archivado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_cortes_servicio`
--
DROP TABLE IF EXISTS `vista_cortes_servicio`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_cortes_servicio`  AS SELECT `c`.`id` AS `cliente_id`, `c`.`dni` AS `dni`, `c`.`nombre` AS `nombre`, `c`.`apellido` AS `apellido`, `c`.`direccion` AS `direccion`, `c`.`correo_electronico` AS `correo_electronico`, count(`f`.`id`) AS `facturas_adeudadas`, sum(`f`.`monto`) AS `total_deuda`, min(`f`.`fecha_vencimiento`) AS `fecha_vencimiento_mas_antigua` FROM (`clientes` `c` join `facturas` `f` on(`c`.`id` = `f`.`cliente_id`)) WHERE `f`.`estado` in ('pendiente','vencida') GROUP BY `c`.`id` HAVING count(`f`.`id`) >= 2 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_deudores`
--
DROP TABLE IF EXISTS `vista_deudores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_deudores`  AS SELECT `c`.`id` AS `cliente_id`, `c`.`dni` AS `dni`, `c`.`nombre` AS `nombre`, `c`.`apellido` AS `apellido`, `c`.`direccion` AS `direccion`, `c`.`correo_electronico` AS `correo_electronico`, count(`f`.`id`) AS `facturas_adeudadas`, sum(`f`.`monto`) AS `total_deuda`, min(`f`.`fecha_vencimiento`) AS `fecha_vencimiento_mas_antigua` FROM (`clientes` `c` join `facturas` `f` on(`c`.`id` = `f`.`cliente_id`)) WHERE `f`.`estado` in ('pendiente','vencida') GROUP BY `c`.`id` HAVING count(`f`.`id`) >= 1 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_servicio_cortado`
--
DROP TABLE IF EXISTS `vista_servicio_cortado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_servicio_cortado`  AS SELECT `c`.`id` AS `cliente_id`, `c`.`dni` AS `dni`, `c`.`nombre` AS `nombre`, `c`.`apellido` AS `apellido`, `c`.`direccion` AS `direccion`, `c`.`correo_electronico` AS `correo_electronico`, count(`f`.`id`) AS `facturas_adeudadas`, sum(`f`.`monto`) AS `total_deuda_acumulada`, min(`f`.`fecha_vencimiento`) AS `fecha_vencimiento_mas_antigua` FROM (`clientes` `c` join `facturas` `f` on(`c`.`id` = `f`.`cliente_id`)) WHERE `f`.`estado` in ('pendiente','vencida') GROUP BY `c`.`id` HAVING count(`f`.`id`) > 3 ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `deudas`
--
ALTER TABLE `deudas`
  ADD CONSTRAINT `fk_deudas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_facturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facturas_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `metodos_pago_archivado`
--
ALTER TABLE `metodos_pago_archivado`
  ADD CONSTRAINT `fk_metodos_pago_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_metodo` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodos_pago` (`id`);

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `fk_suscripciones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_suscripciones_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `suscripcion_cupon`
--
ALTER TABLE `suscripcion_cupon`
  ADD CONSTRAINT `fk_suscripcion_cupon_cupon` FOREIGN KEY (`cupon_id`) REFERENCES `cupones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_suscripcion_cupon_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
