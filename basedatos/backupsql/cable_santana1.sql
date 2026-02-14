-- phpMyAdmin SQL Dump
-- version 5.2.3-1.el9
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 02-01-2026 a las 12:48:25
-- Versión del servidor: 8.0.43
-- Versión de PHP: 8.2.29

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
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tabla_afectada` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registro_afectado_id` int DEFAULT NULL,
  `detalle_anterior` text COLLATE utf8mb4_general_ci,
  `detalle_nuevo` text COLLATE utf8mb4_general_ci,
  `direccion_ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
(51, 1, 'Inicio de sesión exitoso', '0', 1, NULL, NULL, '192.168.10.2', '2026-01-02 12:43:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo_electronico` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `notas_cliente` text COLLATE utf8mb4_general_ci,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `apellido`, `dni`, `direccion`, `correo_electronico`, `notas_cliente`, `fecha_registro`) VALUES
(4, 'claudio', 'lex', '28610711', 'San Martin 2162 Posadas Misones', 'lexclaudio@gmail.com', '', '2025-09-16 00:16:49'),
(5, 'adela', 'olivera', '42764703', 'san juan 3472', 'adela@adela.com', '', '2025-09-16 00:30:34'),
(7, 'test', 'test', '12345678', 'test', 'test@test.com', '', '2026-01-02 11:56:00'),
(14, 'testest', 'testetes', '87654321', 'testestset', 'test2@test.com', '', '2026-01-02 12:20:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id` int NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `tipo_descuento` enum('porcentaje','monto_fijo') COLLATE utf8mb4_general_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudas`
--

CREATE TABLE `deudas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `monto_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagado','vencido') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int NOT NULL,
  `suscripcion_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `estado` enum('pendiente','pagada','fallida','vencida','anulada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `suscripcion_id`, `cliente_id`, `monto`, `fecha_emision`, `fecha_vencimiento`, `fecha_pago`, `estado`) VALUES
(1, 2, 5, 20000.00, '2025-09-16', '2025-10-16', NULL, 'pagada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `gateway` enum('sim_tarjeta','sim_mercadopago') COLLATE utf8mb4_general_ci NOT NULL,
  `token_gateway` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID del cliente/tarjeta en la pasarela de pago',
  `descripcion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ej: Visa terminada en 4242',
  `es_predeterminado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int NOT NULL,
  `factura_id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('exitoso','fallido') COLLATE utf8mb4_general_ci NOT NULL,
  `metodo_pago_id` int NOT NULL,
  `gateway_transaccion_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` int NOT NULL,
  `nombre_plan` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `precio_mensual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tipo_tarifa` enum('fija','por_uso') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'fija',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_facturacion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'fija'
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
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `estado` enum('activa','pausada','cancelada','en_prueba') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'activa',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_proximo_cobro` date NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `cliente_id`, `plan_id`, `estado`, `fecha_inicio`, `fecha_fin`, `fecha_proximo_cobro`, `fecha_creacion`) VALUES
(2, 5, 1, 'activa', '2025-09-16', NULL, '2025-10-16', '2025-09-16 00:30:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripcion_cupon`
--

CREATE TABLE `suscripcion_cupon` (
  `suscripcion_id` int NOT NULL,
  `cupon_id` int NOT NULL,
  `fecha_aplicacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `nombre_usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rol` enum('administrador','editor','cliente') COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `cliente_id`, `nombre_usuario`, `contrasena`, `email`, `rol`, `activo`, `fecha_creacion`) VALUES
(1, NULL, 'admin', '$2y$10$FFNbCSUg43YxqnrI9zN2MeNz5bTSEPvl.fwiB8BiLPyepPrNwADrK', NULL, 'administrador', 1, '2025-09-15 23:55:51'),
(2, NULL, 'test', '$2y$10$S/tVnBDeaO//ddb8ri7mTecJFNmnbO8zXYZsGzXM2AgX3ulKhQfKS', 'tests@test.com', 'cliente', 1, '2025-09-16 00:34:10'),
(4, NULL, 'editor', '$2y$10$QjQWggMVH9A6jI2RbYZVJOvTjlJzPrSv0yNpTD83F28cudDzCJjxK', 'editor@editor.com', 'editor', 1, '2025-09-18 02:29:22');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `deudas`
--
ALTER TABLE `deudas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- Filtros para la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD CONSTRAINT `fk_metodos_pago_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_metodo` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodos_pago` (`id`) ON UPDATE CASCADE;

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
