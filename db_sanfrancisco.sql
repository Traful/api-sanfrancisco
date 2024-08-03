-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 03-08-2024 a las 13:00:04
-- Versión del servidor: 10.11.8-MariaDB-cll-lve
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u823308621_mp`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historiapagos`
--

CREATE TABLE `historiapagos` (
  `id` int(11) NOT NULL,
  `iduser` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `idpago` varchar(50) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `detalle` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `domicilio` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `contacto_emergencia_nombre` varchar(100) DEFAULT NULL,
  `contacto_emergencia_apellido` varchar(100) DEFAULT NULL,
  `contacto_emergencia_telefono` varchar(20) DEFAULT NULL,
  `talle_remera` varchar(20) DEFAULT NULL,
  `team_agrupacion` varchar(255) DEFAULT NULL,
  `categoria_edad` varchar(100) DEFAULT NULL,
  `codigo_descuento` varchar(50) DEFAULT NULL,
  `certificado_medico` blob DEFAULT NULL,
  `tipo_mime` varchar(250) NOT NULL,
  `nombre_archivo` varchar(250) NOT NULL,
  `acepta_promocion` tinyint(1) DEFAULT NULL,
  `idItem` int(11) DEFAULT NULL,
  `idPago` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` double NOT NULL,
  `idmp` varchar(250) NOT NULL,
  `initpoint` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items`
--

INSERT INTO `items` (`id`, `titulo`, `cantidad`, `precio`, `idmp`, `initpoint`) VALUES
(1, 'Quiero correr los 5k', 1, 12000, '57883365-73d78f05-2b0a-4b87-9425-5d5ebfee5125', 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=57883365-73d78f05-2b0a-4b87-9425-5d5ebfee5125'),
(2, 'Quiero correr los 10k', 1, 15000, '57883365-4addc732-db7d-4d00-b376-50f8a17f6ae3', 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=57883365-4addc732-db7d-4d00-b376-50f8a17f6ae3');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `notificacion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `email`, `firstname`, `lastname`, `password`) VALUES
(1, 'hansjal@gmail.com', 'Hans', 'Araujo', '$2y$10$0rBkCUaq8sClyMlBwsye4OKfbYDfesoA5L0vafQw2YnE1wGS2GD6m'),
(18, 'federiconj@gmail.com', 'Federico', 'jaime', '$2y$10$ntQAGStlNBdznWkyO7pDs.IWXz7BdlcQRmQ7zBxG6sEcaShusTRVS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `userstemp`
--

CREATE TABLE `userstemp` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `token` varchar(15) NOT NULL,
  `fecha` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `userstemp`
--

INSERT INTO `userstemp` (`id`, `email`, `firstname`, `lastname`, `password`, `token`, `fecha`) VALUES
(13, 'juan@juan.com', 'Federico', 'Jaime', '$2y$10$iM4doTTIRHaCheoSd4Uc0.6H4VRLZXWBdlwiSBELHzhJdohGVFD3i', 'DYxrk96sTo', '2024-08-01'),
(14, 'visorahomestudio@gmail.com', 'Pablo', '888', '$2y$10$n/wY98SnbOFcjXPdnusWZ.tBmCKKn19XjCCNlFqVpfFMboyOnWnYq', 'lbqrFCjgIM', '2024-08-01');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `historiapagos`
--
ALTER TABLE `historiapagos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `userstemp`
--
ALTER TABLE `userstemp`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `historiapagos`
--
ALTER TABLE `historiapagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `userstemp`
--
ALTER TABLE `userstemp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
