-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-08-2025 a las 00:04:19
-- Versión del servidor: 10.6.15-MariaDB
-- Versión de PHP: 8.2.4

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
(1, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-03 23:14:34'),
(2, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-03 23:24:50'),
(3, 5, 'Usuario creado', '0', 13, NULL, '0', '::1', '2025-07-03 23:31:50'),
(4, 5, 'Cliente creado', '0', 1, NULL, '0', '::1', '2025-07-03 23:56:58'),
(5, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-04 00:03:05'),
(6, 5, 'Cliente creado', '0', 2, NULL, '0', '::1', '2025-07-04 00:03:59'),
(7, 5, 'Cliente creado', '0', 3, NULL, '0', '::1', '2025-07-04 00:04:25'),
(8, 5, 'Cliente creado', '0', 4, NULL, '0', '::1', '2025-07-04 00:05:10'),
(9, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-07 20:44:23'),
(10, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-07 22:48:40'),
(11, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-07 22:51:56'),
(12, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-07 22:53:13'),
(13, 5, 'Usuario eliminado', '0', 2, '{\"id\":2,\"nombre_usuario\":\"editor_test\",\"email\":\"new_editor_email@example.com\",\"rol\":\"administrador\",\"fecha_creacion\":\"2025-07-03 16:32:02\",\"activo\":0}', NULL, '::1', '2025-07-07 22:54:23'),
(14, 9, 'Inicio de sesión exitoso', '0', 9, NULL, NULL, '::1', '2025-07-07 22:54:43'),
(15, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-07 22:55:43'),
(16, NULL, 'Intento de inicio de sesión fallido (usuario no encontrado o inactivo)', '0', NULL, '{\"nombre_usuario\":\"root\"}', NULL, '::1', '2025-07-16 21:19:07'),
(17, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-16 21:19:11'),
(18, NULL, 'Intento de inicio de sesión fallido (contraseña incorrecta)', '0', NULL, '{\"nombre_usuario\":\"admin\"}', NULL, '::1', '2025-07-31 22:49:00'),
(19, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-31 22:49:07'),
(20, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-31 22:53:09'),
(21, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-31 22:57:31'),
(22, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-31 22:57:47'),
(23, 5, 'Cliente creado', '0', 5, NULL, '0', '::1', '2025-07-31 23:00:52'),
(24, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-07-31 23:15:57'),
(25, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-08-16 16:58:51'),
(26, NULL, 'Intento de inicio de sesión fallido (contraseña incorrecta)', '0', NULL, '{\"nombre_usuario\":\"admin\"}', NULL, '::1', '2025-08-16 17:09:12'),
(27, 5, 'Contraseña actualizada automáticamente a nuevo hash', '0', 5, NULL, NULL, '::1', '2025-08-16 21:58:51'),
(28, 5, 'Inicio de sesión exitoso', '0', 5, NULL, NULL, '::1', '2025-08-16 21:58:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `correo_electronico` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `cuotacable` decimal(10,2) DEFAULT 0.00,
  `cuotainternet` decimal(10,2) DEFAULT 0.00,
  `cuotacableinternet` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id`, `dni`, `nombre`, `apellido`, `direccion`, `correo_electronico`, `fecha_registro`, `cuotacable`, `cuotainternet`, `cuotacableinternet`) VALUES
(1, '28610711', 'claudio', 'lex', 'San Martin 2162 Posadas Misones', 'lexclaudio@gmail.com', '2025-07-03 23:56:58', 10000.00, 0.00, 0.00),
(2, '28610712', 'test', 'test', 'test', 'test@test.com', '2025-07-04 00:03:59', 0.00, 12000.00, 0.00),
(3, '28610713', 'test1', 'test1', 'tucuman 2266', 'test1@test.com', '2025-07-04 00:04:25', 0.00, 0.00, 20000.00),
(4, '28610714', 'test2', 'test', 'san juan 3472', 'test2@test.com', '2025-07-04 00:05:10', 10000.00, 0.00, 0.00),
(5, '42764703', 'adela', 'olivera', 'san juan 3472', 'adela0011@adela.com', '2025-07-31 23:00:52', 15000.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudas`
--

CREATE TABLE `deudas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto_original` decimal(10,2) NOT NULL,
  `monto_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagado','vencido','parcialmente_pagado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `usuario_id`, `monto`, `fecha_pago`, `metodo_pago`, `referencia_pago`, `descripcion`, `fecha_registro`) VALUES
(1, 13, 15000.00, '2025-07-04', 'efectivo', 'subcripcion mensual de cable', '', '2025-07-03 23:32:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` enum('administrador','editor','visor','cliente') NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombre_usuario`, `contrasena`, `email`, `rol`, `fecha_creacion`, `activo`) VALUES
(1, 'admin_test', '9b8769a4a742959a2d0298c36fb70623f2dfacda8436237df08d8dfd5b37374c', 'admin_test@example.com', 'administrador', '2025-07-03 19:32:02', 1),
(5, 'admin', '$2y$10$SGutfH2q57yhSVh7xffypeokHbyxUNfOvREBcLPezslouVpz97T.a', 'lexclaudio@gmail.com', 'administrador', '2025-07-03 19:34:35', 1),
(8, 'editor', 'ef5e5a1fb95055e0e56cccf98a41e784a132c14e7f6e1ba244302f0e72b29baf', 'editor@editor.com', 'editor', '2025-07-03 20:08:50', 1),
(9, 'visor', '8395107bccee912451ce2415d4617f4e7fe36fa77f802f8d4050f5e726fab8a7', 'visor@visor.com', 'visor', '2025-07-03 20:09:15', 1),
(13, 'cliente', 'a60b85d409a01d46023f90741e01b79543a3cb1ba048eaefbe5d7a63638043bf', 'cliente@cliente.com', 'cliente', '2025-07-03 23:31:50', 1),
(15, 'testing', '$2y$10$71jU2fzgptdRgyKA1CpMDO2tTcqRHkylHVOTFv3vWRdv4C9neONve', 'testing@test.com', 'cliente', '2025-07-31 22:51:25', 1);

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
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- Indices de la tabla `deudas`
--
ALTER TABLE `deudas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referencia_pago` (`referencia_pago`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `deudas`
--
ALTER TABLE `deudas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `deudas`
--
ALTER TABLE `deudas`
  ADD CONSTRAINT `deudas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
