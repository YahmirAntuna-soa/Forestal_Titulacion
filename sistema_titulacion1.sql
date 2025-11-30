-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 06:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistema_titulacion1`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_estadisticas_dashboard` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE roles = (SELECT id FROM roles WHERE nombre = 'alumno')) as total_alumnos,
        (SELECT COUNT(*) FROM usuarios WHERE roles = (SELECT id FROM roles WHERE nombre = 'revisor')) as total_revisores,
        (SELECT COUNT(*) FROM usuarios_documentos) as total_documentos,
        (SELECT COUNT(*) FROM revisiones WHERE estado IN ('pendiente', 'en_revision')) as revisiones_pendientes,
        (SELECT COUNT(*) FROM chat WHERE DATE(fecha_enviado) = CURDATE()) as mensajes_hoy,
        (SELECT COUNT(*) FROM usuarios_documentos WHERE DATE(fecha_subida) = CURDATE()) as documentos_hoy;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_notificaciones_usuario` (IN `p_usuario_id` INT)   BEGIN
    SELECT * FROM notificaciones 
    WHERE id_usuario = p_usuario_id AND leida = 0
    ORDER BY fecha_creacion DESC
    LIMIT 10;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `catalogo_documentos`
--

CREATE TABLE `catalogo_documentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catalogo_documentos`
--

INSERT INTO `catalogo_documentos` (`id`, `nombre`) VALUES
(1, 'Tesis'),
(2, 'Proyecto de Titulación');

-- --------------------------------------------------------

--
-- Table structure for table `catalogo_programa_educativo`
--

CREATE TABLE `catalogo_programa_educativo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catalogo_programa_educativo`
--

INSERT INTO `catalogo_programa_educativo` (`id`, `nombre`) VALUES
(1, 'Ingeniería Forestal'),
(2, 'Licenciatura en Ingeniería Forestal'),
(3, 'Ingeniería en Manejo Ambiental'),
(4, 'Ingeniería en Manejo Ambiental de Recursos Naturales'),
(5, 'Maestría en Geomática Aplicada a Recursos Forestales y Ambientales');

-- --------------------------------------------------------

--
-- Table structure for table `catalogo_comite`
--

CREATE TABLE `catalogo_comite` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `director_tesis` int(11) DEFAULT NULL,
  `codirector` int(11) DEFAULT NULL,
  `asesor1` int(11) DEFAULT NULL,
  `asesor2` int(11) DEFAULT NULL,
  `asesor3` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catalogo_comite`
--

INSERT INTO `catalogo_comite` (`id`, `nombre`, `director_tesis`, `codirector`, `asesor1`, `asesor2`, `asesor3`) VALUES
(1, 'Facultad de Ciencias Forestales y Ambientales', NULL, NULL, NULL, NULL, NULL),
(2, 'Otro', NULL, NULL, NULL, NULL, NULL),
(4, 'Departamento de Ciencias Ambientales', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE `chat` (
  `id` int(11) NOT NULL,
  `id_usuario_fuente` int(11) NOT NULL,
  `id_usuario_destino` int(11) NOT NULL,
  `fecha_leido` datetime DEFAULT NULL,
  `fecha_enviado` datetime NOT NULL,
  `mensaje` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Mensajes de chat entre usuarios del sistema';

--
-- Dumping data for table `chat`
--

INSERT INTO `chat` (`id`, `id_usuario_fuente`, `id_usuario_destino`, `fecha_leido`, `fecha_enviado`, `mensaje`) VALUES
(113, 2, 12, NULL, '2025-11-28 19:42:10', 'El revisor Carlos Mendoza ha revisado tu documento \'Proyecto de Titulación\'. Estado: correcciones_minimas. Puedes descargar el documento revisado desde tu panel.'),
(114, 12, 2, NULL, '2025-11-28 19:44:38', 'Esta bien, ya subi el docuemnto corregido'),
(115, 2, 12, NULL, '2025-11-28 22:09:28', 'El revisor Carlos Mendoza ha revisado tu documento \'Proyecto de Titulación\'. Estado: aprobado. Puedes descargar el documento revisado desde tu panel.'),
(116, 2, 12, NULL, '2025-11-28 22:33:59', 'El revisor Carlos Mendoza ha revisado tu documento \'Tesis\'. Estado: correcciones_sustanciales. Puedes descargar el documento revisado desde tu panel.');

-- --------------------------------------------------------

--
-- Table structure for table `documentos_revisados`
--

CREATE TABLE `documentos_revisados` (
  `id` int(11) NOT NULL,
  `id_revision` int(11) NOT NULL,
  `id_revisor` int(11) NOT NULL,
  `nombre_documento` varchar(255) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_revision` timestamp NOT NULL DEFAULT current_timestamp(),
  `tamano_archivo` int(11) DEFAULT NULL,
  `estado` enum('correcciones_minimas','correcciones_sustanciales','aprobado') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documentos_revisados`
--

INSERT INTO `documentos_revisados` (`id`, `id_revision`, `id_revisor`, `nombre_documento`, `nombre_original`, `comentarios`, `fecha_revision`, `tamano_archivo`, `estado`) VALUES
(5, 13, 2, 'revisado_13_1764380530_12_1764380421_ficha_tecnica_AVR__5_.docx', '12_1764380421_ficha_tecnica_AVR__5_.docx', 'le falta algo', '2025-11-29 01:42:10', 17704, 'correcciones_minimas'),
(6, 13, 2, 'revisado_13_1764389368_Actividad_5.docx', 'Actividad 5.docx', 'Todo Correcto', '2025-11-29 04:09:28', 194298, 'aprobado');

-- --------------------------------------------------------

--
-- Table structure for table `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('info','success','warning','error') DEFAULT 'info',
  `leida` tinyint(4) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_leida` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `id_usuario`, `titulo`, `mensaje`, `tipo`, `leida`, `fecha_creacion`, `fecha_leida`) VALUES
(1, 4, 'Nuevo revisor asignado', 'El Dr. Carlos Mendoza ha sido asignado como revisor de tu tesis', 'info', 0, '2025-11-20 23:19:26', NULL),
(2, 5, 'Recordatorio de entrega', 'Tu proyecto de tesis está próximo a vencer', 'warning', 0, '2025-11-20 23:19:26', NULL),
(3, 2, 'Nuevo documento para revisar', 'Juan Pérez ha subido un nuevo avance de tesis', 'info', 0, '2025-11-20 23:19:26', NULL),
(3, 3, 'Mensaje nuevo', 'Tienes un nuevo mensaje de María López', 'info', 0, '2025-11-20 23:19:26', NULL),
(5, 10, 'Nuevo revisor asignado', 'El Roberto Silva ha sido asignado para revisar tu Artículo Científico', 'info', 0, '2025-11-20 23:22:45', NULL),
(6, 10, 'Nuevo revisor asignado', 'El Ana García ha sido asignado para revisar tu Borrador Final', 'info', 0, '2025-11-21 00:21:12', NULL),
(7, 6, 'Nuevo revisor asignado', 'El Roberto Silva ha sido asignado para revisar tu Reporte de Investigación', 'info', 0, '2025-11-27 17:32:00', NULL),
(8, 10, 'Nuevo revisor asignado', 'El Roberto Silva ha sido asignado para revisar tu Reporte de Investigación', 'info', 0, '2025-11-27 17:34:28', NULL),
(9, 12, 'Nuevo revisor asignado', 'El Carlos Mendoza ha sido asignado para revisar tu Proyecto de Titulación', 'info', 0, '2025-11-29 01:39:05', NULL),
(10, 12, 'Nuevo revisor asignado', 'El Carlos Mendoza ha sido asignado para revisar tu Tesis', 'info', 0, '2025-11-29 04:32:12', NULL);

--
-- Triggers `notificaciones`
--
DELIMITER $$
CREATE TRIGGER `tr_notificacion_leida` BEFORE UPDATE ON `notificaciones` FOR EACH ROW BEGIN
    IF NEW.leida = 1 AND OLD.leida = 0 THEN
        SET NEW.fecha_leida = NOW();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permisos`
--

INSERT INTO `permisos` (`id`, `nombre`) VALUES
(1, 'gestion_usuarios'),
(2, 'gestion_documentos'),
(3, 'revision_tesis'),
(4, 'chat_alumnos'),
(5, 'reportes'),
(6, 'gestion_roles'),
(7, 'configuracion_sistema');

-- --------------------------------------------------------

--
-- Table structure for table `retroalimentacion`
--

CREATE TABLE `retroalimentacion` (
  `id` int(11) NOT NULL,
  `id_revision` int(11) NOT NULL,
  `id_revisor` int(11) NOT NULL,
  `comentarios` text NOT NULL,
  `estado` enum('aprobado','correcciones_minimas','correcciones_sustanciales','rechazado') NOT NULL,
  `fecha_retroalimentacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revisiones`
--

CREATE TABLE `revisiones` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_documento` int(11) NOT NULL,
  `fecha_asignada` datetime NOT NULL,
  `id_revisor` int(11) NOT NULL,
  `estado` enum('pendiente','en_revision','completada') DEFAULT 'pendiente',
  `fecha_completada` datetime DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `intentos` int(11) DEFAULT 1,
  `ultima_revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Asignaciones de revisión de documentos';

--
-- Dumping data for table `revisiones`
--

INSERT INTO `revisiones` (`id`, `id_usuario`, `id_documento`, `fecha_asignada`, `id_revisor`, `estado`, `fecha_completada`, `comentarios`, `intentos`, `ultima_revision`) VALUES
(13, 12, 2, '2025-11-28 19:39:05', 2, 'completada', NULL, NULL, 3, '2025-11-28 22:09:28');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'admin'),
(2, 'revisor'),
(3, 'alumno');

-- --------------------------------------------------------

--
-- Table structure for table `roles_permisos`
--

CREATE TABLE `roles_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles_permisos`
--

INSERT INTO `roles_permisos` (`id_rol`, `id_permiso`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(2, 3),
(2, 4),
(3, 2),
(3, 4);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `carrera` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `roles` int(11) NOT NULL,
  `unidad_academica` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla de usuarios del sistema con información personal y académica';

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `matricula`, `password`, `nombre`, `apellido_paterno`, `apellido_materno`, `carrera`, `email`, `roles`, `unidad_academica`, `fecha_creacion`, `activo`) VALUES
(1, 'admin001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', '', 1, 'admin@forestales.edu.mx', 1, 1, '2025-11-20 23:19:26', 1),
(2, 'rev001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos', 'Mendoza', 'López', 1, 'cmendoza@forestales.edu.mx', 2, 2, '2025-11-20 23:19:26', 1),
(12, '1115192', '$2y$10$w.0QCW7t1YfRh4sZLZD0o.57wuzwm8gd7xayFX/lHapNSkzjhJRE2', 'Marco Yahmir', 'Antuna', 'Leyva', 1, 'yahmir@gmail.com', 3, 4, '2025-11-29 01:34:46', 1),
(14, '1115193', '$2y$10$o7vswH/t71N9bqTjerEHO.Jr6/OlQ0zT/zo8Gx.WgTQ0a1pqS7q7S', 'Ernesto', 'Ortega', 'Adame', 5, 'ernesto@gmail.com', 3, 1, '2025-11-29 05:20:38', 1);

--
-- Triggers `usuarios`
--
DELIMITER $$
CREATE TRIGGER `tr_usuario_creado` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN
    SET NEW.fecha_creacion = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios_documentos`
--

CREATE TABLE `usuarios_documentos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_documento` int(11) NOT NULL,
  `nombre_documento` varchar(255) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `fecha_subida` datetime NOT NULL,
  `tamano_archivo` int(11) DEFAULT NULL,
  `estado` enum('pendiente','revisado','aprobado','rechazado') DEFAULT 'pendiente',
  `comentarios` text DEFAULT NULL,
  `retroalimentacion_id` int(11) DEFAULT NULL,
  `fecha_ultima_revision` datetime DEFAULT NULL,
  `subido_por` enum('alumno','revisor') DEFAULT 'alumno',
  `id_revision` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Documentos subidos por los usuarios';

--
-- Dumping data for table `usuarios_documentos`
--

INSERT INTO `usuarios_documentos` (`id`, `id_usuario`, `id_documento`, `nombre_documento`, `nombre_original`, `fecha_entrega`, `fecha_subida`, `tamano_archivo`, `estado`, `comentarios`, `retroalimentacion_id`, `fecha_ultima_revision`, `subido_por`, `id_revision`) VALUES
(10, 12, 2, '12_1764388594_ACT_7.docx', 'ACT 7.docx', '2025-11-28 00:00:00', '2025-11-28 21:56:34', 212646, 'aprobado', 'Todo Correcto', NULL, NULL, 'alumno', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vista_actividad_reciente`
-- (See below for the actual view)
--
CREATE TABLE `vista_actividad_reciente` (
`tipo` varchar(9)
,`fecha` datetime
,`usuario` varchar(201)
,`accion` varchar(272)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vista_documentos_estado`
-- (See below for the actual view)
--
CREATE TABLE `vista_documentos_estado` (
`tipo_documento` varchar(255)
,`total` bigint(21)
,`pendientes` bigint(21)
,`revisados` bigint(21)
,`aprobados` bigint(21)
,`rechazados` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vista_estadisticas_usuarios`
-- (See below for the actual view)
--
CREATE TABLE `vista_estadisticas_usuarios` (
`rol` varchar(255)
,`total_usuarios` bigint(21)
,`activos` bigint(21)
,`inactivos` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vista_revisiones_pendientes`
-- (See below for the actual view)
--
CREATE TABLE `vista_revisiones_pendientes` (
`id` int(11)
,`alumno_nombre` varchar(100)
,`alumno_apellido` varchar(100)
,`revisor_nombre` varchar(100)
,`revisor_apellido` varchar(100)
,`documento` varchar(255)
,`fecha_asignada` datetime
,`estado` enum('pendiente','en_revision','completada')
);

-- --------------------------------------------------------

--
-- Structure for view `vista_actividad_reciente`
--
DROP TABLE IF EXISTS `vista_actividad_reciente`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_actividad_reciente`  AS SELECT 'documento' AS `tipo`, `ud`.`fecha_subida` AS `fecha`, concat(`u`.`nombre`,' ',`u`.`apellido_paterno`) AS `usuario`, concat('Subió: ',`cd`.`nombre`) AS `accion` FROM ((`usuarios_documentos` `ud` join `usuarios` `u` on(`ud`.`id_usuario` = `u`.`id`)) join `catalogo_documentos` `cd` on(`ud`.`id_documento` = `cd`.`id`))union all select 'mensaje' AS `tipo`,`c`.`fecha_enviado` AS `fecha`,concat(`u`.`nombre`,' ',`u`.`apellido_paterno`) AS `usuario`,'Envió un mensaje' AS `accion` from (`chat` `c` join `usuarios` `u` on(`c`.`id_usuario_fuente` = `u`.`id`)) union all select 'revision' AS `tipo`,`r`.`fecha_asignada` AS `fecha`,concat(`u`.`nombre`,' ',`u`.`apellido_paterno`) AS `usuario`,concat('Asignó revisión: ',`cd`.`nombre`) AS `accion` from ((`revisiones` `r` join `usuarios` `u` on(`r`.`id_revisor` = `u`.`id`)) join `catalogo_documentos` `cd` on(`r`.`id_documento` = `cd`.`id`)) order by `fecha` desc  ;

-- --------------------------------------------------------

--
-- Structure for view `vista_documentos_estado`
--
DROP TABLE IF EXISTS `vista_documentos_estado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_documentos_estado`  AS SELECT `cd`.`nombre` AS `tipo_documento`, count(`ud`.`id`) AS `total`, count(case when `ud`.`estado` = 'pendiente' then 1 end) AS `pendientes`, count(case when `ud`.`estado` = 'revisado' then 1 end) AS `revisados`, count(case when `ud`.`estado` = 'aprobado' then 1 end) AS `aprobados`, count(case when `ud`.`estado` = 'rechazado' then 1 end) AS `rechazados` FROM (`catalogo_documentos` `cd` left join `usuarios_documentos` `ud` on(`cd`.`id` = `ud`.`id_documento`)) GROUP BY `cd`.`id`, `cd`.`nombre` ;

-- --------------------------------------------------------

--
-- Structure for view `vista_estadisticas_usuarios`
--
DROP TABLE IF EXISTS `vista_estadisticas_usuarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estadisticas_usuarios`  AS SELECT `r`.`nombre` AS `rol`, count(`u`.`id`) AS `total_usuarios`, count(case when `u`.`activo` = 1 then 1 end) AS `activos`, count(case when `u`.`activo` = 0 then 1 end) AS `inactivos` FROM (`usuarios` `u` join `roles` `r` on(`u`.`roles` = `r`.`id`)) GROUP BY `r`.`nombre` ;

-- --------------------------------------------------------

--
-- Structure for view `vista_revisiones_pendientes`
--
DROP TABLE IF EXISTS `vista_revisiones_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_revisiones_pendientes`  AS SELECT `r`.`id` AS `id`, `u_alumno`.`nombre` AS `alumno_nombre`, `u_alumno`.`apellido_paterno` AS `alumno_apellido`, `u_revisor`.`nombre` AS `revisor_nombre`, `u_revisor`.`apellido_paterno` AS `revisor_apellido`, `cd`.`nombre` AS `documento`, `r`.`fecha_asignada` AS `fecha_asignada`, `r`.`estado` AS `estado` FROM (((`revisiones` `r` join `usuarios` `u_alumno` on(`r`.`id_usuario` = `u_alumno`.`id`)) join `usuarios` `u_revisor` on(`r`.`id_revisor` = `u_revisor`.`id`)) join `catalogo_documentos` `cd` on(`r`.`id_documento` = `cd`.`id`)) WHERE `r`.`estado` in ('pendiente','en_revision') ORDER BY `r`.`fecha_asignada` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `catalogo_documentos`
--
ALTER TABLE `catalogo_documentos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalogo_programa_educativo`
--
ALTER TABLE `catalogo_programa_educativo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalogo_comite`
--
ALTER TABLE `catalogo_comite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `director_tesis` (`director_tesis`),
  ADD KEY `codirector` (`codirector`),
  ADD KEY `asesor1` (`asesor1`),
  ADD KEY `asesor2` (`asesor2`),
  ADD KEY `asesor3` (`asesor3`);

--
-- Indexes for table `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_destino` (`id_usuario_destino`),
  ADD KEY `idx_chat_fuente_destino` (`id_usuario_fuente`,`id_usuario_destino`),
  ADD KEY `idx_chat_fecha` (`fecha_enviado`);

--
-- Indexes for table `documentos_revisados`
--
ALTER TABLE `documentos_revisados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_revision` (`id_revision`),
  ADD KEY `id_revisor` (`id_revisor`);

--
-- Indexes for table `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notificaciones_usuario` (`id_usuario`,`leida`);

--
-- Indexes for table `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `retroalimentacion`
--
ALTER TABLE `retroalimentacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_revision` (`id_revision`),
  ADD KEY `id_revisor` (`id_revisor`);

--
-- Indexes for table `revisiones`
--
ALTER TABLE `revisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_documento` (`id_documento`),
  ADD KEY `idx_revisiones_revisor` (`id_revisor`),
  ADD KEY `idx_revisiones_usuario` (`id_usuario`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `carrera` (`carrera`),
  ADD KEY `unidad_academica` (`unidad_academica`),
  ADD KEY `idx_usuarios_roles` (`roles`),
  ADD KEY `idx_usuarios_matricula` (`matricula`),
  ADD KEY `idx_usuarios_email` (`email`);

--
-- Indexes for table `usuarios_documentos`
--
ALTER TABLE `usuarios_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_documento` (`id_documento`),
  ADD KEY `idx_usuarios_documentos_usuario` (`id_usuario`),
  ADD KEY `idx_usuarios_documentos_fecha` (`fecha_subida`),
  ADD KEY `retroalimentacion_id` (`retroalimentacion_id`),
  ADD KEY `id_revision` (`id_revision`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `catalogo_documentos`
--
ALTER TABLE `catalogo_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `catalogo_programa_educativo`
--
ALTER TABLE `catalogo_programa_educativo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `catalogo_comite`
--
ALTER TABLE `catalogo_comite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `documentos_revisados`
--
ALTER TABLE `documentos_revisados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `retroalimentacion`
--
ALTER TABLE `retroalimentacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revisiones`
--
ALTER TABLE `revisiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `usuarios_documentos`
--
ALTER TABLE `usuarios_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat`
--
ALTER TABLE `chat`
  ADD CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`id_usuario_fuente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `catalogo_comite`
--
ALTER TABLE `catalogo_comite`
  ADD CONSTRAINT `catalogo_comite_ibfk_1` FOREIGN KEY (`director_tesis`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `catalogo_comite_ibfk_2` FOREIGN KEY (`codirector`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `catalogo_comite_ibfk_3` FOREIGN KEY (`asesor1`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `catalogo_comite_ibfk_4` FOREIGN KEY (`asesor2`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `catalogo_comite_ibfk_5` FOREIGN KEY (`asesor3`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `documentos_revisados`
--
ALTER TABLE `documentos_revisados`
  ADD CONSTRAINT `documentos_revisados_ibfk_1` FOREIGN KEY (`id_revision`) REFERENCES `revisiones` (`id`),
  ADD CONSTRAINT `documentos_revisados_ibfk_2` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `retroalimentacion`
--
ALTER TABLE `retroalimentacion`
  ADD CONSTRAINT `retroalimentacion_ibfk_1` FOREIGN KEY (`id_revision`) REFERENCES `revisiones` (`id`),
  ADD CONSTRAINT `retroalimentacion_ibfk_2` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `revisiones`
--
ALTER TABLE `revisiones`
  ADD CONSTRAINT `revisiones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `revisiones_ibfk_2` FOREIGN KEY (`id_documento`) REFERENCES `catalogo_documentos` (`id`),
  ADD CONSTRAINT `revisiones_ibfk_3` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD CONSTRAINT `roles_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`carrera`) REFERENCES `catalogo_programa_educativo` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`unidad_academica`) REFERENCES `catalogo_comite` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`roles`) REFERENCES `roles` (`id`);

--
-- Constraints for table `usuarios_documentos`
--
ALTER TABLE `usuarios_documentos`
  ADD CONSTRAINT `usuarios_documentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `usuarios_documentos_ibfk_2` FOREIGN KEY (`id_documento`) REFERENCES `catalogo_documentos` (`id`),
  ADD CONSTRAINT `usuarios_documentos_ibfk_3` FOREIGN KEY (`retroalimentacion_id`) REFERENCES `retroalimentacion` (`id`),
  ADD CONSTRAINT `usuarios_documentos_ibfk_4` FOREIGN KEY (`id_revision`) REFERENCES `revisiones` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;