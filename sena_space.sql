-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-05-2026 a las 02:56:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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
CREATE DATABASE IF NOT EXISTS `sena_space` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sena_space`;

-- --------------------------------------------------------

---- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-10-2026 a las 02:09:04
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
(1037633897, 'sebas.mazo444@gmail.com', 'sebastian', 'bedoya', 'Administrador', 7, 'Usuario', 'Cédula', NULL, '00000', '$2y$10$hkP7XnNS2fLUp/oCEP7mLeK6241yokVQXdRVgShUTz9XUFyhsz7va');

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
) ;

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

--
-- AUTO_INCREMENT de la tabla `recursos`
--
ALTER TABLE `recursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reposiciones`
--
ALTER TABLE `reposiciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  MODIFY `id_tipo_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acceso`
--
ALTER TABLE `acceso`
  ADD CONSTRAINT `acceso_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `admin` (`cedula`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ambiente`
--
ALTER TABLE `ambiente`
  ADD CONSTRAINT `ambiente_ibfk_1` FOREIGN KEY (`id_acceso`) REFERENCES `acceso` (`id_acceso`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_recursos`
--
ALTER TABLE `historial_recursos`
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin` (`cedula`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD CONSTRAINT `fk_notificacion_admin` FOREIGN KEY (`cedula`) REFERENCES `admin` (`cedula`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD CONSTRAINT `ranking_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `rap` (`cedula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rap`
--
ALTER TABLE `rap`
  ADD CONSTRAINT `rap_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `admin` (`cedula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recursos_favoritos`
--
ALTER TABLE `recursos_favoritos`
  ADD CONSTRAINT `fk_favoritos_recurso` FOREIGN KEY (`recurso_id`) REFERENCES `recursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin` (`cedula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reposiciones`
--
ALTER TABLE `reposiciones`
  ADD CONSTRAINT `fk_reposiciones_recurso` FOREIGN KEY (`recurso_id`) REFERENCES `recursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `fk_solicitudes_recurso` FOREIGN KEY (`recurso_id`) REFERENCES `recursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin` (`cedula`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD CONSTRAINT `tipo_usuario_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `rap` (`cedula`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Estructura de tabla para la tabla `acceso`
--

CREATE TABLE `acceso` (
  `id_acceso` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` int(11) DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `contraseña` varchar(100) DEFAULT NULL,
  `fecha_ingreso_ambiente` date DEFAULT NULL,
  `fecha_ingreso_complejo` date DEFAULT NULL,
  `fecha_salida_complejo` date DEFAULT NULL,
  PRIMARY KEY (`id_acceso`)
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
  `tipo_documento` varchar(50) DEFAULT NULL,
  `numero_ficha` varchar(30) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `correo` varchar(254) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_correo` (`correo`),
  KEY `idx_password_resets_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`cedula`, `correo`, `nombre`, `tipo_usuario`, `tipo_documento`, `contraseña`) VALUES
(12345698, 'kd@gmail.com', 'Kevin Jaramillo', 'Aprendiz', 'Cedula', '0'),
(12398745, 'iv@gmail.com', 'Ivan cepeda', 'Visitante', 'Cedula', '0'),
(123456989, 'tbmz@gmail.com', 'Sebas bedoya', 'Aprendiz', 'Cedula', '0'),
(135832168, 'dd@gmail.com', 'Dani D', 'Varios', 'Cedula', '0');

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
  `rap` varchar(100) DEFAULT NULL,
  `cedula` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL
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
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `fk_notificacion_admin` (`cedula`);

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
  ADD KEY `cedula` (`cedula`);

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
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acceso`
--
ALTER TABLE `acceso`
  ADD CONSTRAINT `acceso_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `admin` (`cedula`);

--
-- Filtros para la tabla `ambiente`
--
ALTER TABLE `ambiente`
  ADD CONSTRAINT `ambiente_ibfk_1` FOREIGN KEY (`id_acceso`) REFERENCES `acceso` (`id_acceso`);

--
-- Filtros para la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD CONSTRAINT `fk_notificacion_admin` FOREIGN KEY (`cedula`) REFERENCES `admin` (`cedula`);

--
-- Filtros para la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD CONSTRAINT `ranking_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `rap` (`cedula`);

--
-- Filtros para la tabla `rap`
--
ALTER TABLE `rap`
  ADD CONSTRAINT `rap_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `admin` (`cedula`);

--
-- Filtros para la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD CONSTRAINT `tipo_usuario_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `rap` (`cedula`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- MÓDULO INTEGRADO DE RECURSOS / PRÉSTAMOS
-- Añadido al proyecto LOG-IN
-- ============================================================
USE sena_space;
ALTER TABLE admin
  ADD COLUMN IF NOT EXISTS apellido VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS rol_id INT(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS numero_ficha VARCHAR(30) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS telefono VARCHAR(30) DEFAULT NULL,
  MODIFY correo VARCHAR(254) DEFAULT NULL,
  MODIFY nombre VARCHAR(100) DEFAULT NULL,
  MODIFY contraseña VARCHAR(255) DEFAULT NULL;
ALTER TABLE admin
  ADD COLUMN IF NOT EXISTS rol_sistema ENUM('Usuario','Almacen','Administrador') NOT NULL DEFAULT 'Usuario',
  MODIFY rol_sistema ENUM('Usuario','Almacen','Administrador') NOT NULL DEFAULT 'Usuario';
UPDATE admin
SET rol_sistema = CASE
  WHEN rol_sistema = 'Administrador' OR tipo_usuario = 'Administrador' OR rol_id = 7 OR correo = 'kd@gmail.com' THEN 'Administrador'
  WHEN rol_sistema = 'Almacen' OR tipo_usuario = 'Almacen' OR rol_id = 6 THEN 'Almacen'
  ELSE 'Usuario'
END;

CREATE TABLE IF NOT EXISTS recursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  categoria VARCHAR(50) NOT NULL,
  codigo VARCHAR(60) DEFAULT NULL,
  ubicacion VARCHAR(150) DEFAULT NULL,
  descripcion TEXT DEFAULT NULL,
  stock_total INT NOT NULL DEFAULT 1,
  estado ENUM('Disponible','Mantenimiento','Baja') NOT NULL DEFAULT 'Disponible',
  stock_minimo INT NOT NULL DEFAULT 1,
  tipo ENUM('Objeto','Servicio') NOT NULL DEFAULT 'Objeto',
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_recursos_estado (estado), INDEX idx_recursos_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS solicitudes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  recurso_id INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_devolucion DATE NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  modalidad ENUM('Prestamo','Almacen') NOT NULL DEFAULT 'Prestamo',
  tipo_apoyo VARCHAR(100) DEFAULT NULL,
  prioridad ENUM('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
  nombre_archivo VARCHAR(255) DEFAULT NULL,
  archivo_disco VARCHAR(40) DEFAULT NULL,
  estado ENUM('Pendiente','Aprobado','Rechazado','Devuelto') NOT NULL DEFAULT 'Pendiente',
  observacion TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_aprobacion DATETIME NULL,
  fecha_devolucion_real DATETIME NULL,
  devolucion_solicitada TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_solicitudes_usuario (usuario_id), INDEX idx_solicitudes_recurso (recurso_id), INDEX idx_solicitudes_estado (estado),
  INDEX idx_solicitudes_disponibilidad (recurso_id, estado, fecha_inicio, fecha_devolucion),
  CONSTRAINT chk_solicitudes_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_solicitudes_fechas CHECK (fecha_devolucion >= fecha_inicio),
  CONSTRAINT fk_solicitudes_usuario FOREIGN KEY (usuario_id) REFERENCES admin(cedula) ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE solicitudes
  ADD INDEX IF NOT EXISTS idx_solicitudes_disponibilidad (recurso_id, estado, fecha_inicio, fecha_devolucion);
ALTER TABLE recursos
  ADD COLUMN IF NOT EXISTS codigo VARCHAR(60) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS descripcion TEXT DEFAULT NULL;
ALTER TABLE solicitudes
  ADD COLUMN IF NOT EXISTS modalidad ENUM('Prestamo','Almacen') NOT NULL DEFAULT 'Prestamo',
  ADD COLUMN IF NOT EXISTS tipo_apoyo VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS prioridad ENUM('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
  ADD COLUMN IF NOT EXISTS nombre_archivo VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS archivo_disco VARCHAR(40) DEFAULT NULL;
ALTER TABLE acceso MODIFY id_acceso INT(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS reposiciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recurso_id INT NOT NULL,
  cantidad_solicitada INT NOT NULL,
  estado ENUM('Solicitada','Atendida','Cancelada') NOT NULL DEFAULT 'Solicitada',
  observacion TEXT,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reposiciones_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recursos_favoritos (
  usuario_id INT NOT NULL,
  recurso_id INT NOT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, recurso_id),
  CONSTRAINT fk_favoritos_usuario FOREIGN KEY (usuario_id) REFERENCES admin(cedula) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_favoritos_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS historial_recursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  accion VARCHAR(80) NOT NULL,
  detalle TEXT,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_historial_fecha (fecha),
  CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES admin(cedula) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO recursos (nombre,categoria,stock_total,estado,stock_minimo,tipo)
SELECT 'Proyector Epson EB-X05','Audiovisual',3,'Disponible',1,'Objeto' WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre='Proyector Epson EB-X05');
INSERT INTO recursos (nombre,categoria,stock_total,estado,stock_minimo,tipo)
SELECT 'Laptop Dell Latitude','Cómputo',5,'Disponible',2,'Objeto' WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre='Laptop Dell Latitude');
INSERT INTO recursos (nombre,categoria,stock_total,estado,stock_minimo,tipo)
SELECT 'Cámara Canon EOS','Fotografía',2,'Disponible',1,'Objeto' WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre='Cámara Canon EOS');
INSERT INTO recursos (nombre,categoria,stock_total,estado,stock_minimo,tipo)
SELECT 'Cables HDMI','Accesorios',8,'Disponible',3,'Objeto' WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre='Cables HDMI');
INSERT INTO recursos (nombre,categoria,stock_total,estado,stock_minimo,tipo)
SELECT 'Servicio de soporte audiovisual','Servicios',1,'Disponible',1,'Servicio' WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre='Servicio de soporte audiovisual');
