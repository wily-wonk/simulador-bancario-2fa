-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 19, 2026 at 12:19 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `login_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_logs`
--

CREATE TABLE `auditoria_logs` (
  `id` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `accion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `detalles` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auditoria_logs`
--

INSERT INTO `auditoria_logs` (`id`, `fecha`, `usuario`, `rol`, `ip`, `modulo`, `accion`, `detalles`) VALUES
(1, '2026-05-18 19:00:22', 'jeral', 'user', '::1', 'Cuentas Bancarias', 'Crear', 'Se creó la cuenta: 50005 titular: Jeraldine Jimenez propietario: jeral saldo_inicial: 60000'),
(2, '2026-05-18 19:00:51', 'jeral', 'user', '::1', 'Transferencias', 'Transferir', 'Transfirió $10000 desde cuenta ID 5 hacia cuenta ID 4. Concepto: Transferencia interna'),
(3, '2026-05-18 19:01:13', 'eduardo', 'admin', '::1', 'ABM Usuarios', 'Editar', 'Se actualizó el usuario: ccuenca a: ccuenca con rol: admin'),
(4, '2026-05-18 19:40:07', 'eduardo', 'admin', '::1', 'ABM Usuarios', 'Editar', 'Se actualizó el usuario: ccuenca a: ccuenca con rol: user'),
(5, '2026-05-18 19:45:52', 'juan', 'user', '::1', 'Cuentas Bancarias', 'Crear', 'Se creó la cuenta: 60006 titular: Juan Perez propietario: juan saldo_inicial: 40000'),
(6, '2026-05-18 19:46:20', 'juan', 'user', '::1', 'Transferencias', 'Transferir', 'Transfirió $10000 desde cuenta ID 6 hacia cuenta ID 3. Concepto: Transferencia interna'),
(7, '2026-05-18 19:46:54', 'eduardo', 'admin', '::1', 'ABM Usuarios', 'Editar', 'Se actualizó el usuario: ccuenca a: ccuenca con rol: admin'),
(8, '2026-05-18 19:58:41', 'jeral', 'user', '::1', 'Transferencias', 'Transferir', 'Transfirió $10000 desde cuenta ID 4 hacia cuenta ID 5. Concepto: Transferencia'),
(9, '2026-05-18 19:59:50', 'eduardo', 'admin', '::1', 'ABM Usuarios', 'Eliminar', 'Se eliminó el usuario: juan'),
(10, '2026-05-18 20:01:34', 'jeral', 'user', '::1', 'Transferencias', 'Transferir', 'Transfirió $60000 desde cuenta ID 5 hacia cuenta ID 4. Concepto: Transferencia'),
(11, '2026-05-18 20:15:39', 'jeral', 'user', '::1', 'Cuentas Bancarias', 'Crear', 'Se creó la cuenta: 80008 titular: Jeraldine Jimenez propietario: jeral saldo_inicial: 2400'),
(12, '2026-05-18 20:16:05', 'jeral', 'user', '::1', 'Transferencias', 'Transferir', 'Transfirió $30000 desde cuenta ID 4 hacia cuenta ID 7. Concepto: Transferencia'),
(13, '2026-05-18 20:18:04', 'eduardo', 'admin', '::1', 'ABM Usuarios', 'Editar', 'Se actualizó el usuario: ccuenca a: ccuenca con rol: user');

-- --------------------------------------------------------

--
-- Table structure for table `cuentas_bancarias`
--

CREATE TABLE `cuentas_bancarias` (
  `id` int NOT NULL,
  `cuenta` varchar(50) NOT NULL,
  `titular` varchar(100) NOT NULL,
  `saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_propietario` varchar(50) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cuentas_bancarias`
--

INSERT INTO `cuentas_bancarias` (`id`, `cuenta`, `titular`, `saldo`, `created_at`, `usuario_propietario`) VALUES
(1, '100001', 'Jeraldine Jimenez', 33000.00, '2026-05-18 18:27:17', 'admin'),
(2, '20002', 'Juan Perez', 20000.00, '2026-05-18 18:29:06', 'admin'),
(3, '30003', 'Juan Perez', 12300.00, '2026-05-18 18:45:06', 'juan'),
(4, '40004', 'Jeraldine Jimenez', 30000.00, '2026-05-18 18:46:15', 'jeral'),
(5, '50005', 'Jeraldine Jimenez', 0.00, '2026-05-18 19:00:22', 'jeral'),
(6, '60006', 'Juan Perez', 30000.00, '2026-05-18 19:45:52', 'juan'),
(7, '80008', 'Jeraldine Jimenez', 32400.00, '2026-05-18 20:15:39', 'jeral');

-- --------------------------------------------------------

--
-- Table structure for table `transferencias`
--

CREATE TABLE `transferencias` (
  `id` int NOT NULL,
  `cuenta_origen_id` int NOT NULL,
  `cuenta_destino_id` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `usuario_sistema` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transferencias`
--

INSERT INTO `transferencias` (`id`, `cuenta_origen_id`, `cuenta_destino_id`, `monto`, `concepto`, `usuario_sistema`, `created_at`) VALUES
(1, 1, 2, 10000.00, 'Transferencia interna', 'jeral', '2026-05-18 18:30:05'),
(2, 1, 2, 10000.00, 'Transferencia interna', 'juan', '2026-05-18 18:31:38'),
(3, 3, 1, 3000.00, 'Transferencia', 'juan', '2026-05-18 18:45:33'),
(4, 4, 3, 300.00, 'Transferencia', 'jeral', '2026-05-18 18:46:38'),
(5, 5, 4, 10000.00, 'Transferencia interna', 'jeral', '2026-05-18 19:00:51'),
(6, 6, 3, 10000.00, 'Transferencia interna', 'juan', '2026-05-18 19:46:20'),
(7, 4, 5, 10000.00, 'Transferencia', 'jeral', '2026-05-18 19:58:40'),
(8, 5, 4, 60000.00, 'Transferencia', 'jeral', '2026-05-18 20:01:34'),
(9, 4, 7, 30000.00, 'Transferencia', 'jeral', '2026-05-18 20:16:05');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `rol` varchar(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `creado_en`, `rol`) VALUES
(1, 'ccuenca', '88d0d463da93aa62262e7d05ebe62014866d4f91', '2026-04-21 23:29:46', 'user'),
(2, 'admin', 'fbf0d03e30d504d05db9a1deec6231bc4c5c62e7', '2026-04-28 22:31:45', 'admin'),
(3, 'wily', 'fbf0d03e30d504d05db9a1deec6231bc4c5c62e7', '2026-05-18 20:52:59', 'admin'),
(4, 'eduardo', 'fbf0d03e30d504d05db9a1deec6231bc4c5c62e7', '2026-05-18 21:23:35', 'admin'),
(6, 'jeral', 'fbf0d03e30d504d05db9a1deec6231bc4c5c62e7', '2026-05-18 22:14:03', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cuentas_bancarias`
--
ALTER TABLE `cuentas_bancarias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cuenta` (`cuenta`);

--
-- Indexes for table `transferencias`
--
ALTER TABLE `transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_origen_id` (`cuenta_origen_id`),
  ADD KEY `cuenta_destino_id` (`cuenta_destino_id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cuentas_bancarias`
--
ALTER TABLE `cuentas_bancarias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transferencias`
--
ALTER TABLE `transferencias`
  ADD CONSTRAINT `transferencias_ibfk_1` FOREIGN KEY (`cuenta_origen_id`) REFERENCES `cuentas_bancarias` (`id`),
  ADD CONSTRAINT `transferencias_ibfk_2` FOREIGN KEY (`cuenta_destino_id`) REFERENCES `cuentas_bancarias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
