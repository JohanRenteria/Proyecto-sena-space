-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-10-2026 a las 18:38:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sena_space`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acceso`
--

CREATE TABLE `acceso` (
  `id_acceso` int(11) NOT NULL,
  `cedula` int(11) DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `contraseña` varchar(100) DEFAULT NULL,
  `fecha_ingreso_ambiente` date DEFAULT NULL,
  `fecha_ingreso_complejo` date DEFAULT NULL,
  `fecha_salida_complejo` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `cedula` int(11) NOT NULL,
  `correo` varchar(254) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `tipo_usuario` varchar(50) DEFAULT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `rol_sistema` enum('Usuario','Almacen','Administrador') NOT NULL DEFAULT 'Usuario',
  `tipo_documento` varchar(50) DEFAULT NULL,
  `numero_ficha` varchar(30) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`cedula`, `correo`, `nombre`, `apellido`, `tipo_usuario`, `rol_id`, `rol_sistema`, `tipo_documento`, `numero_ficha`, `telefono`, `contraseña`) VALUES
(12345698, 'kd@gmail.com', 'Kevin Jaramillo', NULL, 'Aprendiz', NULL, 'Administrador', 'Cedula', NULL, NULL, '0'),
(12398745, 'iv@gmail.com', 'Ivan cepeda', NULL, 'Visitante', NULL, 'Usuario', 'Cedula', NULL, NULL, '0'),
(123456989, 'tbmz@gmail.com', 'Sebas bedoya', NULL, 'Aprendiz', NULL, 'Usuario', 'Cedula', NULL, NULL, '0'),
(135832168, 'dd@gmail.com', 'Dani D', NULL, 'Varios', NULL, 'Usuario', 'Cedula', NULL, NULL, '0'),
(1037633897, 'sebas.mazo444@gmail.com', 'sebastian', 'bedoya', 'Administrador', 7, 'Administrador', 'Cédula', NULL, '0000', '$2y$10$.vi4zBmevscQJsnHMRLW6uV79l6obDocOpVLMKFpZ98FYje7E30eC');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ambiente`
--

CREATE TABLE `ambiente` (
  `id_ambiente` int(11) NOT NULL,
  `id_acceso` int(11) DEFAULT NULL,
  `cedula` int(11) DEFAULT NULL,
  `piso` int(11) DEFAULT NULL,
  `bloque` varchar(50) DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_recursos`
--

CREATE TABLE `historial_recursos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(80) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion`
--

CREATE TABLE `notificacion` (
  `id_notificacion` int(11) NOT NULL,
  `Hora_notificacion` date DEFAULT NULL,
  `cedula` int(11) DEFAULT NULL,
  `descripcion` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `correo` varchar(254) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `correo`, `token_hash`, `created_at`, `expires_at`) VALUES
(1, 'sebas.mazo444@gmail.com', '584d3614d5a90e02b3b7aa93b3ec5b4d18685f0142c75d3fc0fd10ab6e2d09ad', '2026-10-13 23:02:37', '2026-10-13 19:02:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ranking`
--

CREATE TABLE `ranking` (
  `id_ranking` int(11) NOT NULL,
  `cedula` int(11) DEFAULT NULL,
  `calculo` float DEFAULT NULL,
  `fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rap`
--

CREATE TABLE `rap` (
  `rap` varchar(100) NOT NULL,
  `cedula` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recursos`
--

CREATE TABLE `recursos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `codigo` varchar(60) DEFAULT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `stock_total` int(11) NOT NULL DEFAULT 1,
  `estado` enum('Disponible','Mantenimiento','Baja') NOT NULL DEFAULT 'Disponible',
  `stock_minimo` int(11) NOT NULL DEFAULT 1,
  `tipo` enum('Objeto','Servicio') NOT NULL DEFAULT 'Objeto',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recursos`
--

INSERT INTO `recursos` (`id`, `nombre`, `categoria`, `codigo`, `ubicacion`, `descripcion`, `stock_total`, `estado`, `stock_minimo`, `tipo`, `fecha_creacion`) VALUES
(1, 'Proyector Epson EB-X05', 'Audiovisual', NULL, NULL, NULL, 3, 'Disponible', 1, 'Objeto', '2026-10-13 22:59:55'),
(2, 'Laptop Dell Latitude', 'Cómputo', NULL, NULL, NULL, 5, 'Disponible', 2, 'Objeto', '2026-10-13 22:59:55'),
(3, 'Cámara Canon EOS', 'Fotografía', NULL, NULL, NULL, 2, 'Disponible', 1, 'Objeto', '2026-10-13 22:59:55'),
(4, 'Cables HDMI', 'Accesorios', NULL, NULL, NULL, 8, 'Disponible', 3, 'Objeto', '2026-10-13 22:59:55'),
(5, 'Servicio de soporte audiovisual', 'Servicios', NULL, NULL, NULL, 1, 'Disponible', 1, 'Servicio', '2026-10-13 22:59:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recursos_favoritos`
--

CREATE TABLE `recursos_favoritos` (
  `usuario_id` int(11) NOT NULL,
  `recurso_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reposiciones`
--

CREATE TABLE `reposiciones` (
  `id` int(11) NOT NULL,
  `recurso_id` int(11) NOT NULL,
  `cantidad_solicitada` int(11) NOT NULL,
  `estado` enum('Solicitada','Atendida','Cancelada') NOT NULL DEFAULT 'Solicitada',
  `observacion` text DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `recurso_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_devolucion` date NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `modalidad` enum('Prestamo','Almacen') NOT NULL DEFAULT 'Prestamo',
  `tipo_apoyo` varchar(100) DEFAULT NULL,
  `prioridad` enum('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `archivo_disco` varchar(40) DEFAULT NULL,
  `estado` enum('Pendiente','Aprobado','Rechazado','Devuelto') NOT NULL DEFAULT 'Pendiente',
  `observacion` text DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_aprobacion` datetime DEFAULT NULL,
  `fecha_devolucion_real` datetime DEFAULT NULL,
  `devolucion_solicitada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_usuario`
--

CREATE TABLE `tipo_usuario` (
  `id_tipo_usuario` int(11) NOT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `cedula` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acceso`
--
ALTER TABLE `acceso`
  ADD PRIMARY KEY (`id_acceso`),
  ADD KEY `cedula` (`cedula`);

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`cedula`);

--
-- Indices de la tabla `ambiente`
--
ALTER TABLE `ambiente`
  ADD PRIMARY KEY (`id_ambiente`),
  ADD KEY `id_acceso` (`id_acceso`);

--
-- Indices de la tabla `historial_recursos`
--
ALTER TABLE `historial_recursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historial_fecha` (`fecha`),
  ADD KEY `fk_historial_usuario` (`usuario_id`);

--
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `fk_notificacion_admin` (`cedula`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_password_resets_correo` (`correo`),
  ADD KEY `idx_password_resets_expiry` (`expires_at`);

--
-- Indices de la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD PRIMARY KEY (`id_ranking`),
  ADD KEY `cedula` (`cedula`);

--
-- Indices de la tabla `rap`
--
ALTER TABLE `rap`
  ADD PRIMARY KEY (`cedula`,`rap`),
  ADD KEY `cedula` (`cedula`);

--
-- Indices de la tabla `recursos`
--
ALTER TABLE `recursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recursos_estado` (`estado`),
  ADD KEY `idx_recursos_categoria` (`categoria`);

--
-- Indices de la tabla `recursos_favoritos`
--
ALTER TABLE `recursos_favoritos`
  ADD PRIMARY KEY (`usuario_id`,`recurso_id`),
  ADD KEY `fk_favoritos_recurso` (`recurso_id`);

--
-- Indices de la tabla `reposiciones`
--
ALTER TABLE `reposiciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reposiciones_recurso` (`recurso_id`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitudes_usuario` (`usuario_id`),
  ADD KEY `idx_solicitudes_recurso` (`recurso_id`),
  ADD KEY `idx_solicitudes_estado` (`estado`),
  ADD KEY `idx_solicitudes_disponibilidad` (`recurso_id`,`estado`,`fecha_inicio`,`fecha_devolucion`);

--
-- Indices de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD PRIMARY KEY (`id_tipo_usuario`),
  ADD KEY `cedula` (`cedula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acceso`
--
ALTER TABLE `acceso`
  MODIFY `id_acceso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ambiente`
--
ALTER TABLE `ambiente`
  MODIFY `id_ambiente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_recursos`
--
ALTER TABLE `historial_recursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ranking`
--
ALTER TABLE `ranking`
  MODIFY `id_ranking` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
