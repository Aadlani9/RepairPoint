-- =====================================================
-- قاعدة بيانات السيرفر الكاملة
-- RepairPoint Database - Server Version
-- التاريخ: 2026-02-04
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- حذف الجداول القديمة
-- =====================================================
DROP TABLE IF EXISTS `repair_spare_parts`;
DROP TABLE IF EXISTS `repair_history`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `spare_parts_compatibility`;
DROP TABLE IF EXISTS `spare_parts_price_history`;
DROP TABLE IF EXISTS `spare_parts`;
DROP TABLE IF EXISTS `repairs`;
DROP TABLE IF EXISTS `models`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `common_issues`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `shops`;

DROP VIEW IF EXISTS `invoice_details`;
DROP VIEW IF EXISTS `low_stock_parts`;
DROP VIEW IF EXISTS `repairs_with_device_info`;
DROP VIEW IF EXISTS `spare_parts_profit_report`;
DROP VIEW IF EXISTS `spare_parts_with_compatibility`;

DROP PROCEDURE IF EXISTS `GetSparePartsByPhone`;
DROP PROCEDURE IF EXISTS `SearchModels`;
DROP PROCEDURE IF EXISTS `SearchSpareParts`;

-- =====================================================
-- جدول shops
-- =====================================================
CREATE TABLE `shops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL COMMENT 'Nombre del taller',
  `email` varchar(150) DEFAULT NULL COMMENT 'Email del taller',
  `phone1` varchar(20) NOT NULL COMMENT 'Teléfono principal',
  `phone2` varchar(20) DEFAULT NULL COMMENT 'Teléfono adicional',
  `address` text DEFAULT NULL COMMENT 'Dirección completa',
  `website` varchar(255) DEFAULT NULL COMMENT 'Sitio web del taller',
  `logo` varchar(255) DEFAULT NULL COMMENT 'Logo del taller',
  `city` varchar(100) DEFAULT NULL COMMENT 'Ciudad',
  `country` varchar(100) DEFAULT 'España' COMMENT 'País',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'Estado del taller',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shops` (`id`, `name`, `email`, `phone1`, `phone2`, `address`, `website`, `logo`, `city`, `country`, `created_at`, `notes`, `status`) VALUES
(1, 'ELECTRO MTI', 'contact@electromti.com', '+34 602 682 042', '', 'Avenida Estación, 42 Torre Pacheco, Murcia', 'https://electromti.com/', 'assets/uploads/logo_1_1749595628.png', 'Torre Pacheco', 'España', '2025-06-10 20:48:05', '', 'active');

-- =====================================================
-- جدول users
-- =====================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre completo del usuario',
  `email` varchar(150) NOT NULL COMMENT 'Email',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Teléfono',
  `password` varchar(255) NOT NULL COMMENT 'Contraseña encriptada',
  `role` enum('admin','staff') DEFAULT 'staff' COMMENT 'Rol del usuario',
  `shop_id` int(11) NOT NULL COMMENT 'ID del taller',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  `last_login` datetime DEFAULT NULL COMMENT 'Último inicio de sesión',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'Estado del usuario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `shop_id` (`shop_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `shop_id`, `created_at`, `last_login`, `status`) VALUES
(1, 'tohami', 'tohami@electromti.com', '+34 602 682 042', '$2y$10$jS7oMk4GR33gsj6owOiMGubJu47IiONmwadYVqidasmyZpn6uXhXq', 'admin', 1, '2025-06-10 20:48:05', '2026-02-04 10:29:39', 'active'),
(2, 'hicham', 'hicham@electromti.com', '+34 666 111 222', '$2y$10$7YLrAqUeu7nmieKMotv.h.om9iJxEsiOK.GNYLG3GosxOXIztrfFm', 'staff', 1, '2025-06-10 20:48:05', '2025-10-30 17:27:37', 'active'),
(3, 'mohamed', 'mohamed@electromti.com', '+34 602 86 12 27', '$2y$10$AiucfFtvgBNxBFtRfiLQqOaav0EgjEAYWGvdCKbZF0U62a.vosIWm', 'admin', 1, '2025-08-23 14:07:41', '2025-08-23 16:27:28', 'active');

-- =====================================================
-- جدول brands
-- =====================================================
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la marca',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_brand_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `brands` (`id`, `name`, `created_at`) VALUES
(1, 'Apple', '2025-06-10 20:48:05'),
(2, 'Samsung', '2025-06-10 20:48:05'),
(3, 'Huawei', '2025-06-10 20:48:05'),
(4, 'Xiaomi', '2025-06-10 20:48:05'),
(5, 'Oppo', '2025-06-10 20:48:05'),
(12, 'Realme', '2025-07-09 18:08:56'),
(13, 'VIVO', '2025-07-09 18:08:56'),
(14, 'TCL', '2025-07-09 18:08:56'),
(17, 'Desconocido', '2025-11-14 18:44:24');

-- =====================================================
-- جدول common_issues
-- =====================================================
CREATE TABLE `common_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_text` varchar(255) NOT NULL COMMENT 'Texto del problema',
  `category` varchar(100) DEFAULT NULL COMMENT 'Categoría del problema',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `common_issues` (`id`, `issue_text`, `category`, `created_at`) VALUES
(1, 'Pantalla rota', 'Pantalla', '2025-06-10 20:48:05'),
(2, 'Batería se agota rápido', 'Batería', '2025-06-10 20:48:05'),
(3, 'No carga', 'Carga', '2025-06-10 20:48:05'),
(4, 'Problema de sonido', 'Audio', '2025-06-10 20:48:05'),
(5, 'Cámara no funciona', 'Cámara', '2025-06-10 20:48:05'),
(6, 'Botón de encendido no funciona', 'Botones', '2025-06-10 20:48:05'),
(7, 'Problema con WiFi', 'Conectividad', '2025-06-10 20:48:05'),
(8, 'Dispositivo lento', 'Rendimiento', '2025-06-10 20:48:05'),
(9, 'Problema con Bluetooth', 'Conectividad', '2025-06-10 20:48:05'),
(10, 'El dispositivo no enciende', 'Sistema', '2025-06-10 20:48:05'),
(11, 'altavos de abajo', 'Audio', '2025-06-11 10:56:49');

-- =====================================================
-- جدول models
-- =====================================================
CREATE TABLE `models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL COMMENT 'ID de la marca',
  `name` varchar(100) NOT NULL COMMENT 'Nombre del modelo',
  `model_reference` varchar(50) DEFAULT NULL COMMENT 'معرّف الموديل التجاري',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_brand_model` (`brand_id`,`name`),
  UNIQUE KEY `model_reference` (`model_reference`),
  KEY `idx_brand_model` (`brand_id`,`name`),
  KEY `idx_model_reference` (`model_reference`),
  CONSTRAINT `models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `models` (`id`, `brand_id`, `name`, `model_reference`, `created_at`) VALUES
(1, 1, 'iPhone 15 Pro Max', NULL, '2025-06-10 20:48:05'),
(2, 1, 'iPhone 15 Pro', NULL, '2025-06-10 20:48:05'),
(3, 1, 'iPhone 15', NULL, '2025-06-10 20:48:05'),
(4, 1, 'iPhone 14 Pro Max', NULL, '2025-06-10 20:48:05'),
(5, 1, 'iPhone 14 Pro', NULL, '2025-06-10 20:48:05'),
(6, 1, 'iPhone 14', NULL, '2025-06-10 20:48:05'),
(7, 1, 'iPhone 13', NULL, '2025-06-10 20:48:05'),
(8, 1, 'iPhone 12', NULL, '2025-06-10 20:48:05'),
(9, 2, 'Galaxy S24 Ultra', NULL, '2025-06-10 20:48:05'),
(10, 2, 'Galaxy S24+', NULL, '2025-06-10 20:48:05'),
(11, 2, 'Galaxy S24', NULL, '2025-06-10 20:48:05'),
(12, 2, 'Galaxy S23', NULL, '2025-06-10 20:48:05'),
(13, 2, 'Galaxy A54', NULL, '2025-06-10 20:48:05'),
(14, 2, 'Galaxy A34', NULL, '2025-06-10 20:48:05'),
(15, 2, 'Galaxy Note 20', NULL, '2025-06-10 20:48:05'),
(17, 1, 'Appel 13 Pro', NULL, '2025-06-18 11:10:14'),
(18, 1, 'Watch Series 1', NULL, '2025-07-09 18:08:56'),
(19, 1, 'Watch Series 2', NULL, '2025-07-09 18:08:56'),
(20, 1, 'Watch Series 3', NULL, '2025-07-09 18:08:56'),
(21, 1, 'Watch Series 4', NULL, '2025-07-09 18:08:56'),
(22, 1, 'Watch Series 5', NULL, '2025-07-09 18:08:56'),
(23, 1, 'Watch Series 6', NULL, '2025-07-09 18:08:56'),
(24, 1, 'Watch Series SE', NULL, '2025-07-09 18:08:56'),
(25, 1, 'Watch Series SE 2022', NULL, '2025-07-09 18:08:56'),
(26, 1, 'Watch Series 7', NULL, '2025-07-09 18:08:56'),
(27, 1, 'Watch Series 8', NULL, '2025-07-09 18:08:56'),
(28, 1, 'iPhone 16e', NULL, '2025-07-09 18:08:56'),
(29, 1, 'iPhone 16 Pro Max', NULL, '2025-07-09 18:08:56'),
(30, 1, 'iPhone 16 Pro', NULL, '2025-07-09 18:08:56'),
(31, 1, 'iPhone 16 Plus', NULL, '2025-07-09 18:08:56'),
(32, 1, 'iPhone 16', NULL, '2025-07-09 18:08:56'),
(33, 1, 'iPhone 15 Plus', NULL, '2025-07-09 18:08:56'),
(34, 1, 'iPhone 14 Plus', NULL, '2025-07-09 18:08:56'),
(35, 1, 'iPhone SE 2022', NULL, '2025-07-09 18:08:56'),
(36, 1, 'iPhone SE 2020', NULL, '2025-07-09 18:08:56'),
(37, 1, 'iPhone 13 Pro Max', NULL, '2025-07-09 18:08:56'),
(38, 1, 'iPhone 13 Pro', NULL, '2025-07-09 18:08:56'),
(39, 1, 'iPhone 13 Mini', NULL, '2025-07-09 18:08:56'),
(40, 1, 'iPhone 12 Pro Max', NULL, '2025-07-09 18:08:56'),
(41, 1, 'iPhone 12 Pro', NULL, '2025-07-09 18:08:56'),
(42, 1, 'iPhone 12 Mini', NULL, '2025-07-09 18:08:56'),
(43, 1, 'iPhone 11 Pro Max', NULL, '2025-07-09 18:08:56'),
(44, 1, 'iPhone 11 Pro', NULL, '2025-07-09 18:08:56'),
(45, 1, 'iPhone 11', NULL, '2025-07-09 18:08:56'),
(46, 1, 'iPhone Xs Max', NULL, '2025-07-09 18:08:56'),
(47, 1, 'iPhone XS', NULL, '2025-07-09 18:08:56'),
(48, 1, 'iPhone XR', NULL, '2025-07-09 18:08:56'),
(49, 1, 'iPhone X', NULL, '2025-07-09 18:08:56'),
(50, 1, 'iPhone 8 Plus', NULL, '2025-07-09 18:08:56'),
(51, 1, 'iPhone 8', NULL, '2025-07-09 18:08:56'),
(52, 1, 'iPhone 7 Plus', NULL, '2025-07-09 18:08:56'),
(53, 1, 'iPhone 7', NULL, '2025-07-09 18:08:56'),
(54, 1, 'iPhone 6s Plus', NULL, '2025-07-09 18:08:56'),
(55, 1, 'iPhone 6S', NULL, '2025-07-09 18:08:56'),
(56, 1, 'iPhone 6 Plus', NULL, '2025-07-09 18:08:56'),
(57, 1, 'iPhone 6', NULL, '2025-07-09 18:08:56'),
(58, 1, 'iPhone SE', NULL, '2025-07-09 18:08:56'),
(59, 1, 'iPhone 5S', NULL, '2025-07-09 18:08:56'),
(60, 1, 'iPhone 5C', NULL, '2025-07-09 18:08:56'),
(61, 1, 'iPhone 5', NULL, '2025-07-09 18:08:56'),
(62, 1, 'iPhone 4S', NULL, '2025-07-09 18:08:56'),
(63, 1, 'iPhone 4', NULL, '2025-07-09 18:08:56'),
(64, 2, 'F700 Z Flip (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(65, 2, 'F707 Z Flip 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(66, 2, 'F711 Z Flip 3 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(67, 2, 'F721 Z Flip 4 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(68, 2, 'F731 Z Flip 5 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(69, 2, 'F741 Z Flip 6 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(70, 2, 'F900 Fold (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(71, 2, 'F907 Fold 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(72, 2, 'F916 Z Fold 2 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(73, 2, 'F926 Z Fold 3 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(74, 2, 'F936 Z Fold 4 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(75, 2, 'F946 Z Fold 5 5G (Series F (Fold/Flip))', NULL, '2025-07-09 18:08:56'),
(76, 2, 'A91 (A Series)', NULL, '2025-07-09 18:08:56'),
(77, 2, 'A90 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(78, 2, 'A80 (A Series)', NULL, '2025-07-09 18:08:56'),
(79, 2, 'A73 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(80, 2, 'A72 (A Series)', NULL, '2025-07-09 18:08:56'),
(81, 2, 'A71 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(82, 2, 'A71 (A Series)', NULL, '2025-07-09 18:08:56'),
(83, 2, 'A70 (A Series)', NULL, '2025-07-09 18:08:56'),
(84, 2, 'A60 (A Series)', NULL, '2025-07-09 18:08:56'),
(85, 2, 'A56 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(86, 2, 'A55 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(87, 2, 'A54 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(88, 2, 'A53 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(89, 2, 'A52s 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(90, 2, 'A52 (A Series)', NULL, '2025-07-09 18:08:56'),
(91, 2, 'A51 (A Series)', NULL, '2025-07-09 18:08:56'),
(92, 2, 'A50s (A Series)', NULL, '2025-07-09 18:08:56'),
(93, 2, 'A50 (A Series)', NULL, '2025-07-09 18:08:56'),
(94, 2, 'A42 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(95, 2, 'A41 (A Series)', NULL, '2025-07-09 18:08:56'),
(96, 2, 'A40 (A Series)', NULL, '2025-07-09 18:08:56'),
(97, 2, 'A36 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(98, 2, 'A35 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(99, 2, 'A34 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(100, 2, 'A33 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(101, 2, 'A32 5G (A Series)', NULL, '2025-07-09 18:08:56'),
(102, 2, 'A32 4G (A Series)', NULL, '2025-07-09 18:08:56'),
(103, 2, 'A31 (A Series)', NULL, '2025-07-09 18:08:56'),
(104, 2, 'A30s (A Series)', NULL, '2025-07-09 18:08:56'),
(105, 2, 'A30 (A Series)', NULL, '2025-07-09 18:08:56'),
(106, 2, 'S25 Ultra 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(107, 2, 'S25 Edge 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(108, 2, 'S25 Plus 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(109, 2, 'S25 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(110, 2, 'S24 Ultra 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(111, 2, 'S24 Plus 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(112, 2, 'S24 FE 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(113, 2, 'S24 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(114, 2, 'S23 FE 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(115, 2, 'S23 Ultra 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(116, 2, 'S23 Plus 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(117, 2, 'S23 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(118, 2, 'S22 Ultra 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(119, 2, 'S22 Plus 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(120, 2, 'S22 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(121, 2, 'S21 Ultra 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(122, 2, 'S21 Plus 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(123, 2, 'S21 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(124, 2, 'S21 FE 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(125, 2, 'S20 FE (S Series)', NULL, '2025-07-09 18:08:56'),
(126, 2, 'S20 Ultra (S Series)', NULL, '2025-07-09 18:08:56'),
(127, 2, 'S20 Plus (S Series)', NULL, '2025-07-09 18:08:56'),
(128, 2, 'S20 (S Series)', NULL, '2025-07-09 18:08:56'),
(129, 2, 'S10 5G (S Series)', NULL, '2025-07-09 18:08:56'),
(130, 2, 'S10 Plus (S Series)', NULL, '2025-07-09 18:08:56'),
(131, 2, 'S10 Lite (S Series)', NULL, '2025-07-09 18:08:56'),
(132, 2, 'S10E (S Series)', NULL, '2025-07-09 18:08:56'),
(133, 2, 'S10 (S Series)', NULL, '2025-07-09 18:08:56'),
(134, 2, 'S9 Plus (S Series)', NULL, '2025-07-09 18:08:56'),
(135, 2, 'S9 (S Series)', NULL, '2025-07-09 18:08:56'),
(136, 2, 'S8 Plus (S Series)', NULL, '2025-07-09 18:08:56'),
(137, 2, 'S8 (S Series)', NULL, '2025-07-09 18:08:56'),
(138, 2, 'S7 Edge (S Series)', NULL, '2025-07-09 18:08:56'),
(139, 2, 'S7 (S Series)', NULL, '2025-07-09 18:08:56'),
(140, 2, 'S6 Active (S Series)', NULL, '2025-07-09 18:08:56'),
(141, 2, 'S6 Edge+ (S Series)', NULL, '2025-07-09 18:08:56'),
(142, 2, 'S6 Edge (S Series)', NULL, '2025-07-09 18:08:56'),
(143, 2, 'S6 (S Series)', NULL, '2025-07-09 18:08:56'),
(144, 2, 'S5 Neo (S Series)', NULL, '2025-07-09 18:08:56'),
(145, 2, 'S5 mini (S Series)', NULL, '2025-07-09 18:08:56'),
(146, 2, 'S5 (S Series)', NULL, '2025-07-09 18:08:56'),
(147, 2, 'S4 mini (S Series)', NULL, '2025-07-09 18:08:56'),
(148, 2, 'S4 (S Series)', NULL, '2025-07-09 18:08:56'),
(149, 2, 'S3 Neo (S Series)', NULL, '2025-07-09 18:08:56'),
(150, 2, 'S3 (S Series)', NULL, '2025-07-09 18:08:56'),
(151, 2, 'S3 mini (S Series)', NULL, '2025-07-09 18:08:56'),
(152, 2, 'S2 (S Series)', NULL, '2025-07-09 18:08:56'),
(153, 4, 'Mi 14 Ultra 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(154, 4, 'Mi 14T Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(155, 4, 'Mi 14 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(156, 4, 'Mi 14T 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(157, 4, 'Mi 14 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(158, 4, 'Mi 13 Ultra 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(159, 4, 'Mi 11 Ultra 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(160, 4, 'Poco C40 (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(161, 4, 'Poco X7 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(162, 4, 'Poco X7 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(163, 4, 'Poco X5 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(164, 4, 'Poco F7 Ultra 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(165, 4, 'Poco F7 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(166, 4, 'Poco F6 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(167, 4, 'Poco F6 (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(168, 4, 'Poco F5 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(169, 4, 'Poco F5 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(170, 4, 'Poco F4 GT (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(171, 4, 'Poco F4 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(172, 4, 'Poco X6 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(173, 4, 'Poco X6 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(174, 4, 'Poco X4 GT (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(175, 4, 'Poco X4 Pro 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(176, 4, 'Poco X3 Pro (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(177, 4, 'Poco X3 (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(178, 4, 'Poco M5 (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(179, 4, 'Poco M4 Pro (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(180, 4, 'Poco M3 Pro (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(181, 4, 'Poco M3 (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(182, 4, 'Poco F3 5G (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(183, 4, 'Poco F2 Pro (Serie Mi)', NULL, '2025-07-09 18:08:56'),
(184, 4, 'Redmi 14C (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(185, 4, 'Redmi 13 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(186, 4, 'Redmi A5 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(187, 4, 'Redmi A3 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(188, 4, 'Redmi A2 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(189, 4, 'Redmi A1 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(190, 4, 'REDMI S2 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(191, 4, 'Redmi Note 14 Pro+ 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(192, 4, 'Redmi Note 14 Pro 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(193, 4, 'Redmi Note 14 Pro 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(194, 4, 'Redmi Note 14 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(195, 4, 'Redmi Note 13 Pro Plus 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(196, 4, 'Redmi Note 13 Pro 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(197, 4, 'Redmi Note 13 Pro 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(198, 4, 'Redmi Note 13 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(199, 4, 'Redmi Note 13 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(200, 4, 'Redmi Note 12S (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(201, 4, 'Redmi Note 12 Pro Plus 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(202, 4, 'Redmi Note 12 Pro 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(203, 4, 'Redmi Note 12 Pro 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(204, 4, 'Redmi Note 12 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(205, 4, 'Redmi Note 12 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(206, 4, 'Redmi Note 11T Pro Plus (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(207, 4, 'Redmi Note 11T Pro (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(208, 4, 'Redmi Note 11 Pro+ 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(209, 4, 'Redmi Note 11 Pro 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(210, 4, 'Redmi Note 11S (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(211, 4, 'Redmi Note 11 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(212, 4, 'Redmi Note 11 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(213, 4, 'Redmi Note 10 Pro 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(214, 4, 'Redmi Note 10 Pro 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(215, 4, 'Redmi Note 10S (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(216, 4, 'Redmi Note 10 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(217, 4, 'Redmi Note 10 4G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(218, 4, 'Redmi Note 9S (Note 9 Pro) (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(219, 4, 'Redmi Note 9T (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(220, 4, 'Redmi Note 9 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(221, 4, 'Redmi Note 8 2021 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(222, 4, 'Redmi Note 8T (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(223, 4, 'Redmi Note 8 Pro (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(224, 4, 'Redmi Note 8 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(225, 4, 'Redmi 13C (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(226, 4, 'Redmi 12C (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(227, 4, 'REDMI 12 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(228, 4, 'Redmi 10A (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(229, 4, 'Redmi 10C (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(230, 4, 'Redmi 10 5G (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(231, 4, 'Redmi 10 (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(232, 4, 'Redmi 9T (Serie Redmi)', NULL, '2025-07-09 18:08:56'),
(233, 4, 'Black Shark (Black Shark)', NULL, '2025-07-09 18:08:56'),
(234, 5, 'Reno 12 Pro 5G', NULL, '2025-07-09 18:08:56'),
(235, 5, 'Reno 12F', NULL, '2025-07-09 18:08:56'),
(236, 5, 'Reno 12 5G', NULL, '2025-07-09 18:08:56'),
(237, 5, 'Reno 11 Pro', NULL, '2025-07-09 18:08:56'),
(238, 5, 'Reno 11F', NULL, '2025-07-09 18:08:56'),
(239, 5, 'Reno 10 Pro 5G', NULL, '2025-07-09 18:08:56'),
(240, 5, 'Reno 10 5G', NULL, '2025-07-09 18:08:56'),
(241, 5, 'Reno 8 Pro 5G', NULL, '2025-07-09 18:08:56'),
(242, 5, 'Reno 8T', NULL, '2025-07-09 18:08:56'),
(243, 5, 'Reno 8 5G', NULL, '2025-07-09 18:08:56'),
(244, 5, 'Reno 8 4G', NULL, '2025-07-09 18:08:56'),
(245, 5, 'Reno 6 Pro 5G', NULL, '2025-07-09 18:08:56'),
(246, 5, 'Reno 7Z 5G', NULL, '2025-07-09 18:08:56'),
(247, 5, 'Reno 6 5G', NULL, '2025-07-09 18:08:56'),
(248, 5, 'Reno 5 Lite', NULL, '2025-07-09 18:08:56'),
(249, 5, 'Find X5 Pro 5G', NULL, '2025-07-09 18:08:56'),
(250, 5, 'Find X5 Lite 5G', NULL, '2025-07-09 18:08:56'),
(251, 5, 'Find X5', NULL, '2025-07-09 18:08:56'),
(252, 5, 'Find X3 Pro 5G', NULL, '2025-07-09 18:08:56'),
(253, 5, 'Find X3 Neo 5G', NULL, '2025-07-09 18:08:56'),
(254, 5, 'Find X3 Lite 5G', NULL, '2025-07-09 18:08:56'),
(255, 5, 'Find X2 Lite 5G', NULL, '2025-07-09 18:08:56'),
(256, 5, 'Find X2 Pro', NULL, '2025-07-09 18:08:56'),
(257, 5, 'Find X2 5G', NULL, '2025-07-09 18:08:56'),
(258, 5, 'A80', NULL, '2025-07-09 18:08:56'),
(259, 5, 'A9 2020', NULL, '2025-07-09 18:08:56'),
(260, 5, 'A98 5G', NULL, '2025-07-09 18:08:56'),
(261, 5, 'Oppo A97', NULL, '2025-07-09 18:08:56'),
(262, 5, 'A96 4G', NULL, '2025-07-09 18:08:56'),
(263, 5, 'A94 5G', NULL, '2025-07-09 18:08:56'),
(264, 5, 'A94 4G', NULL, '2025-07-09 18:08:56'),
(265, 5, 'A78', NULL, '2025-07-09 18:08:56'),
(266, 5, 'A77 5G', NULL, '2025-07-09 18:08:56'),
(267, 5, 'A76', NULL, '2025-07-09 18:08:56'),
(268, 5, 'Oppo A74 5G', NULL, '2025-07-09 18:08:56'),
(269, 5, 'A74 4G', NULL, '2025-07-09 18:08:56'),
(270, 5, 'A73 5G', NULL, '2025-07-09 18:08:56'),
(271, 5, 'A72 5G', NULL, '2025-07-09 18:08:56'),
(272, 5, 'A72 2020', NULL, '2025-07-09 18:08:56'),
(273, 5, 'A60', NULL, '2025-07-09 18:08:56'),
(274, 5, 'A58 4G', NULL, '2025-07-09 18:08:56'),
(275, 5, 'A57 4G', NULL, '2025-07-09 18:08:56'),
(276, 5, 'A54 5G (A93 5G)', NULL, '2025-07-09 18:08:56'),
(277, 5, 'A54 4G', NULL, '2025-07-09 18:08:56'),
(278, 5, 'A53 (A53s/A32 2020)', NULL, '2025-07-09 18:08:56'),
(279, 12, '12 Pro 5G', NULL, '2025-07-09 18:08:56'),
(280, 12, '12X 5G', NULL, '2025-07-09 18:08:56'),
(281, 12, '11 Pro 5G', NULL, '2025-07-09 18:08:56'),
(282, 12, '10 Pro 5G', NULL, '2025-07-09 18:08:56'),
(283, 12, '10 4G', NULL, '2025-07-09 18:08:56'),
(284, 12, 'X3 (X50)', NULL, '2025-07-09 18:08:56'),
(285, 12, 'X2 Pro', NULL, '2025-07-09 18:08:56'),
(286, 12, 'X2', NULL, '2025-07-09 18:08:56'),
(287, 12, '2 Pro', NULL, '2025-07-09 18:08:56'),
(288, 12, '3', NULL, '2025-07-09 18:08:56'),
(289, 12, '3 Pro', NULL, '2025-07-09 18:08:56'),
(290, 12, '3i', NULL, '2025-07-09 18:08:56'),
(291, 12, '5', NULL, '2025-07-09 18:08:56'),
(292, 12, '5 Pro', NULL, '2025-07-09 18:08:56'),
(293, 12, '5i', NULL, '2025-07-09 18:08:56'),
(294, 12, '6', NULL, '2025-07-09 18:08:56'),
(295, 12, '6i', NULL, '2025-07-09 18:08:56'),
(296, 12, '6 Pro', NULL, '2025-07-09 18:08:56'),
(297, 12, '7', NULL, '2025-07-09 18:08:56'),
(298, 12, '7 5G', NULL, '2025-07-09 18:08:56'),
(299, 12, '7i', NULL, '2025-07-09 18:08:56'),
(300, 12, '7 Pro', NULL, '2025-07-09 18:08:56'),
(301, 12, '8i', NULL, '2025-07-09 18:08:56'),
(302, 12, '8 5G', NULL, '2025-07-09 18:08:56'),
(303, 12, '8 Pro', NULL, '2025-07-09 18:08:56'),
(304, 12, '9', NULL, '2025-07-09 18:08:56'),
(305, 12, '9 5G', NULL, '2025-07-09 18:08:56'),
(306, 12, '9i', NULL, '2025-07-09 18:08:56'),
(307, 12, '9i 5G', NULL, '2025-07-09 18:08:56'),
(308, 12, '9 Pro', NULL, '2025-07-09 18:08:56'),
(309, 12, '9 Pro Plus', NULL, '2025-07-09 18:08:56'),
(310, 12, 'C1', NULL, '2025-07-09 18:08:56'),
(311, 12, 'C2', NULL, '2025-07-09 18:08:56'),
(312, 12, 'C3', NULL, '2025-07-09 18:08:56'),
(313, 12, 'C11', NULL, '2025-07-09 18:08:56'),
(314, 12, 'C11 2021', NULL, '2025-07-09 18:08:56'),
(315, 12, 'C20 (C21)', NULL, '2025-07-09 18:08:56'),
(316, 12, 'C21Y', NULL, '2025-07-09 18:08:56'),
(317, 12, 'C30', NULL, '2025-07-09 18:08:56'),
(318, 12, 'C31', NULL, '2025-07-09 18:08:56'),
(319, 12, 'C35', NULL, '2025-07-09 18:08:56'),
(320, 12, 'C53', NULL, '2025-07-09 18:08:56'),
(321, 12, 'C55', NULL, '2025-07-09 18:08:56'),
(322, 12, 'C61', NULL, '2025-07-09 18:08:56'),
(323, 12, 'C63', NULL, '2025-07-09 18:08:56'),
(324, 12, 'C65', NULL, '2025-07-09 18:08:56'),
(325, 12, 'C67', NULL, '2025-07-09 18:08:56'),
(326, 12, 'C75', NULL, '2025-07-09 18:08:56'),
(327, 12, 'Note 50', NULL, '2025-07-09 18:08:56'),
(328, 12, 'GT 5G', NULL, '2025-07-09 18:08:56'),
(329, 13, 'Vivo Y3 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(330, 13, 'Vivo Y01 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(331, 13, 'Vivo Y02 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(332, 13, 'Y91 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(333, 13, 'Y72 5G (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(334, 13, 'Vivo Y70 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(335, 13, 'Y11s (Y20s) (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(336, 13, 'Y15S (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(337, 13, 'Y16 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(338, 13, 'Y17S (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(339, 13, 'Y18 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(340, 13, 'Vivo Y19 (Y5s) (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(341, 13, 'Y21 (2021) (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(342, 13, 'Y21S (2021) (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(343, 13, 'Vivo Y22S (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(344, 13, 'Y28 4G (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(345, 13, 'Y28S 5G (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(346, 13, 'Vivio Y33s (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(347, 13, 'Vivo Y35 4G (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(348, 13, 'Y36 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(349, 13, 'Vivo Y52s (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(350, 13, 'Vivo Y52 5G (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(351, 13, 'Y55 5G (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(352, 13, 'Vivo Y93 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(353, 13, 'Vivo Y50 (VIVO Series Y)', NULL, '2025-07-09 18:08:56'),
(354, 13, 'X27 (VIVO Series X)', NULL, '2025-07-09 18:08:56'),
(355, 13, 'X27 Pro (VIVO Series X)', NULL, '2025-07-09 18:08:56'),
(356, 13, 'X60 Pro (VIVO Series X)', NULL, '2025-07-09 18:08:56'),
(357, 13, 'V21 5G (VIVO Series V)', NULL, '2025-07-09 18:08:56'),
(358, 13, 'V23 5G (VIVO Series V)', NULL, '2025-07-09 18:08:56'),
(359, 13, 'V40 SE (VIVO Series V)', NULL, '2025-07-09 18:08:56'),
(360, 3, 'Honor 200 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(361, 3, 'Honor 90 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(362, 3, 'Honor 90 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(363, 3, 'Honor 70 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(364, 3, 'Honor 70 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(365, 3, 'Honor Magic 7 Pro (Honor Series)', NULL, '2025-07-09 18:08:56'),
(366, 3, 'Honor Magic 7 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(367, 3, 'Honor Magic 6 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(368, 3, 'Honor Magic 5 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(369, 3, 'Honor Magic 4 Pro (Honor Series)', NULL, '2025-07-09 18:08:56'),
(370, 3, 'Honor 50 SE (Honor Series)', NULL, '2025-07-09 18:08:56'),
(371, 3, 'Honor 50 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(372, 3, 'Honor 50 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(373, 3, 'Honor X10 5G (Honor Series)', NULL, '2025-07-09 18:08:56'),
(374, 3, 'Honor X9B (Honor Series)', NULL, '2025-07-09 18:08:56'),
(375, 3, 'Honor X9 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(376, 3, 'Honor X8B (Honor Series)', NULL, '2025-07-09 18:08:56'),
(377, 3, 'Honor X8A (Honor Series)', NULL, '2025-07-09 18:08:56'),
(378, 3, 'Honor X8 5G (Honor Series)', NULL, '2025-07-09 18:08:56'),
(379, 3, 'Honor X8 4G (Honor Series)', NULL, '2025-07-09 18:08:56'),
(380, 3, 'Honor X7C (Honor Series)', NULL, '2025-07-09 18:08:56'),
(381, 3, 'Honor X7B (Honor Series)', NULL, '2025-07-09 18:08:56'),
(382, 3, 'Honor X7A (Honor Series)', NULL, '2025-07-09 18:08:56'),
(383, 3, 'Honor X7 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(384, 3, 'Honor X6B (Honor Series)', NULL, '2025-07-09 18:08:56'),
(385, 3, 'Honor X6A (Honor Series)', NULL, '2025-07-09 18:08:56'),
(386, 3, 'Honor X5 4G (Honor Series)', NULL, '2025-07-09 18:08:56'),
(387, 3, 'Honor Play (Honor Series)', NULL, '2025-07-09 18:08:56'),
(388, 3, 'Honor 20 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(389, 3, 'Honor 20 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(390, 3, 'Honor 10 Lite (Honor Series)', NULL, '2025-07-09 18:08:56'),
(391, 3, 'Honor 10 (Honor Series)', NULL, '2025-07-09 18:08:56'),
(392, 3, 'Nova 10 (Series Nova)', NULL, '2025-07-09 18:08:56'),
(393, 3, 'Nova 10 Pro (Series Nova)', NULL, '2025-07-09 18:08:56'),
(394, 3, 'Nova 10 SE (Series Nova)', NULL, '2025-07-09 18:08:56'),
(395, 3, 'Nova Y61 (Series Nova)', NULL, '2025-07-09 18:08:56'),
(396, 3, 'Nova Y70 (Series Nova)', NULL, '2025-07-09 18:08:56'),
(397, 3, 'Nova Y90 (Series Nova)', NULL, '2025-07-09 18:08:56'),
(398, 3, 'Nova Y91 (Series Nova)', NULL, '2025-07-09 18:08:56'),
(399, 3, 'Mate 30 Pro (Mate Series)', NULL, '2025-07-09 18:08:56'),
(400, 3, 'Mate 30 Lite (Mate Series)', NULL, '2025-07-09 18:08:56'),
(401, 3, 'Mate 30 (Mate Series)', NULL, '2025-07-09 18:08:56'),
(402, 3, 'Mate 20X (Mate Series)', NULL, '2025-07-09 18:08:56'),
(403, 3, 'Mate 20 Pro (Mate Series)', NULL, '2025-07-09 18:08:56'),
(404, 3, 'Mate 20 Lite (Mate Series)', NULL, '2025-07-09 18:08:56'),
(405, 3, 'Mate 20 (Mate Series)', NULL, '2025-07-09 18:08:56'),
(406, 3, 'Mate 10 Pro (Mate Series)', NULL, '2025-07-09 18:08:56'),
(407, 3, 'Mate 10 Lite (Mate Series)', NULL, '2025-07-09 18:08:56'),
(408, 3, 'Mate 10 (Mate Series)', NULL, '2025-07-09 18:08:56'),
(409, 3, 'Mate 9 (Mate Series)', NULL, '2025-07-09 18:08:56'),
(410, 3, 'Mate 8 (Mate Series)', NULL, '2025-07-09 18:08:56'),
(411, 3, 'Mate 7 (Mate Series)', NULL, '2025-07-09 18:08:56'),
(412, 3, 'Mate S (Mate Series)', NULL, '2025-07-09 18:08:56'),
(413, 3, 'P smart S (P Series)', NULL, '2025-07-09 18:08:56'),
(414, 3, 'P Smart Z (P Series)', NULL, '2025-07-09 18:08:56'),
(415, 3, 'P Smart 2021 (P Series)', NULL, '2025-07-09 18:08:56'),
(416, 3, 'P Smart 2020 (P Series)', NULL, '2025-07-09 18:08:56'),
(417, 3, 'P Smart 2019 (P Series)', NULL, '2025-07-09 18:08:56'),
(418, 3, 'P Smart Pro (P Series)', NULL, '2025-07-09 18:08:56'),
(419, 3, 'P Smart Plus (P Series)', NULL, '2025-07-09 18:08:56'),
(420, 3, 'P Smart (P Series)', NULL, '2025-07-09 18:08:56'),
(421, 3, 'P60 (P Series)', NULL, '2025-07-09 18:08:56'),
(422, 3, 'P50 Pro (P Series)', NULL, '2025-07-09 18:08:56'),
(423, 3, 'P40 Pro Plus (P Series)', NULL, '2025-07-09 18:08:56'),
(424, 3, 'P40 Pro (P Series)', NULL, '2025-07-09 18:08:56'),
(425, 3, 'P40 Lite E (Y7p 2020) (P Series)', NULL, '2025-07-09 18:08:56'),
(426, 3, 'P40 Lite 5G (P Series)', NULL, '2025-07-09 18:08:56'),
(427, 3, 'P40 Lite (P Series)', NULL, '2025-07-09 18:08:56'),
(428, 3, 'P30 Pro (P Series)', NULL, '2025-07-09 18:08:56'),
(429, 3, 'P30 Lite (P Series)', NULL, '2025-07-09 18:08:56'),
(430, 3, 'P30 (P Series)', NULL, '2025-07-09 18:08:56'),
(431, 3, 'P20 Pro (P Series)', NULL, '2025-07-09 18:08:56'),
(432, 3, 'P20 Lite (P Series)', NULL, '2025-07-09 18:08:56'),
(433, 3, 'P20 (P Series)', NULL, '2025-07-09 18:08:56'),
(434, 3, 'P10 Plus (P Series)', NULL, '2025-07-09 18:08:56'),
(435, 3, 'P10 Lite (P Series)', NULL, '2025-07-09 18:08:56'),
(436, 3, 'P10 (P Series)', NULL, '2025-07-09 18:08:56'),
(437, 3, 'P9 Plus (P Series)', NULL, '2025-07-09 18:08:56'),
(438, 3, 'P9 Lite 2017 (P Series)', NULL, '2025-07-09 18:08:56'),
(439, 3, 'P9 Lite (P Series)', NULL, '2025-07-09 18:08:56'),
(440, 3, 'P9 (P Series)', NULL, '2025-07-09 18:08:56'),
(441, 3, 'P8 Lite 2017 (P Series)', NULL, '2025-07-09 18:08:56'),
(442, 3, 'P8 Lite (P Series)', NULL, '2025-07-09 18:08:56'),
(443, 3, 'P8 (P Series)', NULL, '2025-07-09 18:08:56'),
(444, 3, 'P7 (P Series)', NULL, '2025-07-09 18:08:56'),
(445, 14, '10 SE', NULL, '2025-07-09 18:08:56'),
(446, 14, '10L', NULL, '2025-07-09 18:08:56'),
(447, 14, '10 5G', NULL, '2025-07-09 18:08:56'),
(448, 14, '10 Lite', NULL, '2025-07-09 18:08:56'),
(449, 14, '10 Pro', NULL, '2025-07-09 18:08:56'),
(450, 14, '10 Plus', NULL, '2025-07-09 18:08:56'),
(451, 14, '20 5G', NULL, '2025-07-09 18:08:56'),
(452, 14, '20Y (2021)', NULL, '2025-07-09 18:08:56'),
(453, 14, '20E (2021)', NULL, '2025-07-09 18:08:56'),
(454, 14, '20R', NULL, '2025-07-09 18:08:56'),
(455, 14, '20 SE', NULL, '2025-07-09 18:08:56'),
(456, 14, '20 Pro', NULL, '2025-07-09 18:08:56'),
(457, 14, '20L', NULL, '2025-07-09 18:08:56'),
(458, 14, '30 (30 Plus)', NULL, '2025-07-09 18:08:56'),
(459, 14, '30 SE', NULL, '2025-07-09 18:08:56'),
(460, 14, '30E', NULL, '2025-07-09 18:08:56'),
(461, 14, '305i', NULL, '2025-07-09 18:08:56'),
(462, 14, '405', NULL, '2025-07-09 18:08:56'),
(463, 14, '40R', NULL, '2025-07-09 18:08:56'),
(464, 14, '40 SE', NULL, '2025-07-09 18:08:56'),
(465, 14, '40 NxtPaper 5G', NULL, '2025-07-09 18:08:56'),
(466, 14, '501', NULL, '2025-07-09 18:08:56'),
(467, 14, '505', NULL, '2025-07-09 18:08:56'),
(468, 14, '50 5G', NULL, '2025-07-09 18:08:56'),
(469, 14, '50 Pro NxtPaper 5G', NULL, '2025-07-09 18:08:56'),
(471, 17, 'Dispositivo Personalizado', NULL, '2025-11-14 18:44:24');

-- =====================================================
-- جدول customers
-- =====================================================
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL COMMENT 'Nombre completo del cliente',
  `phone` varchar(20) NOT NULL COMMENT 'Teléfono del cliente',
  `email` varchar(150) DEFAULT NULL COMMENT 'Email del cliente (opcional)',
  `address` text DEFAULT NULL COMMENT 'Dirección del cliente',
  `id_type` enum('dni','nie','passport') NOT NULL DEFAULT 'dni' COMMENT 'Tipo de documento',
  `id_number` varchar(50) NOT NULL COMMENT 'Número de documento',
  `shop_id` int(11) NOT NULL COMMENT 'ID del taller',
  `created_by` int(11) NOT NULL COMMENT 'ID del usuario que creó el cliente',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha de actualización',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'Estado del cliente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone_shop` (`phone`,`shop_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer_phone` (`phone`),
  KEY `idx_customer_name` (`full_name`),
  KEY `idx_customer_id_number` (`id_number`),
  KEY `idx_shop_id` (`shop_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول invoices
-- =====================================================
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL COMMENT 'Número de factura único',
  `customer_id` int(11) NOT NULL COMMENT 'ID del cliente',
  `invoice_date` date NOT NULL COMMENT 'Fecha de la factura',
  `due_date` date DEFAULT NULL COMMENT 'Fecha de vencimiento',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal sin IVA',
  `iva_rate` decimal(5,2) NOT NULL DEFAULT 21.00 COMMENT 'Tasa de IVA (%)',
  `iva_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto del IVA',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total con IVA',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending' COMMENT 'Estado del pago',
  `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Monto pagado',
  `payment_date` date DEFAULT NULL COMMENT 'Fecha de pago',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Método de pago',
  `shop_id` int(11) NOT NULL COMMENT 'ID del taller',
  `created_by` int(11) NOT NULL COMMENT 'ID del usuario que creó la factura',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha de actualización',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_shop_id` (`shop_id`),
  KEY `idx_invoices_customer_status` (`customer_id`,`payment_status`),
  KEY `idx_invoices_date_shop` (`invoice_date`,`shop_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول invoice_items
-- =====================================================
CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL COMMENT 'ID de la factura',
  `item_type` enum('service','product','spare_part') NOT NULL DEFAULT 'service' COMMENT 'Tipo de item',
  `description` text NOT NULL COMMENT 'Descripción del producto/servicio',
  `imei` varchar(50) DEFAULT NULL COMMENT 'IMEI del dispositivo',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Cantidad',
  `unit_price` decimal(10,2) NOT NULL COMMENT 'Precio unitario',
  `subtotal` decimal(10,2) NOT NULL COMMENT 'Subtotal (cantidad * precio)',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  PRIMARY KEY (`id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_imei` (`imei`),
  CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول repairs
-- =====================================================
CREATE TABLE `repairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL COMMENT 'Número de referencia único',
  `customer_name` varchar(100) NOT NULL COMMENT 'Nombre del cliente',
  `customer_phone` varchar(20) NOT NULL COMMENT 'Teléfono del cliente',
  `brand_id` int(11) NOT NULL COMMENT 'ID de la marca',
  `model_id` int(11) NOT NULL COMMENT 'ID del modelo',
  `device_input_type` enum('list','search','otro') DEFAULT 'list' COMMENT 'طريقة إدخال الجهاز',
  `custom_brand` varchar(100) DEFAULT NULL COMMENT 'ماركة مخصصة للأجهزة غير الموجودة',
  `custom_model` varchar(100) DEFAULT NULL COMMENT 'موديل مخصص للأجهزة غير الموجودة',
  `issue_description` text NOT NULL COMMENT 'Descripción del problema',
  `estimated_cost` decimal(10,2) DEFAULT NULL COMMENT 'Coste estimado',
  `actual_cost` decimal(10,2) DEFAULT NULL COMMENT 'Coste real',
  `status` enum('pending','in_progress','completed','delivered','reopened') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium' COMMENT 'Prioridad de la reparación',
  `received_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha y hora de recepción',
  `estimated_completion` datetime DEFAULT NULL COMMENT 'Fecha estimada de finalización',
  `completed_at` datetime DEFAULT NULL COMMENT 'Fecha de finalización',
  `delivered_at` datetime DEFAULT NULL COMMENT 'Fecha de entrega',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID del usuario que registró',
  `delivered_by` varchar(100) DEFAULT NULL COMMENT 'Nombre del empleado que entregó',
  `shop_id` int(11) NOT NULL COMMENT 'ID del taller',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `warranty_days` int(11) DEFAULT 30 COMMENT 'Number of warranty days',
  `reopen_type` enum('warranty','paid','goodwill') DEFAULT NULL COMMENT 'Reopen type',
  `reopen_reason` text DEFAULT NULL COMMENT 'Reason for reopening',
  `reopen_notes` text DEFAULT NULL COMMENT 'Reopening notes',
  `reopen_date` datetime DEFAULT NULL COMMENT 'Reopening date',
  `parent_repair_id` int(11) DEFAULT NULL COMMENT 'Original repair ID in case of reopening',
  `is_reopened` tinyint(1) DEFAULT 0 COMMENT 'Whether it was reopened',
  `reopen_delivered_at` datetime DEFAULT NULL COMMENT 'تاريخ التسليم بعد إعادة الفتح الأخيرة',
  `reopen_warranty_days` int(11) DEFAULT 30 COMMENT 'أيام الضمان الجديدة بعد إعادة الفتح',
  `reopen_completed_at` datetime DEFAULT NULL COMMENT 'تاريخ الإنجاز بعد إعادة الفتح',
  `original_delivered_at` datetime DEFAULT NULL COMMENT 'نسخة من تاريخ التسليم الأصلي',
  `reopen_count` int(11) DEFAULT 0 COMMENT 'عدد مرات إعادة الفتح',
  `last_reopen_by` int(11) DEFAULT NULL COMMENT 'المستخدم الذي قام بآخر إعادة فتح',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `brand_id` (`brand_id`),
  KEY `model_id` (`model_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_reference` (`reference`),
  KEY `idx_customer_phone` (`customer_phone`),
  KEY `idx_customer_name` (`customer_name`),
  KEY `idx_status` (`status`),
  KEY `idx_shop_id` (`shop_id`),
  KEY `idx_received_date` (`received_at`),
  KEY `idx_repairs_status_shop` (`status`,`shop_id`),
  KEY `idx_repairs_date_shop` (`received_at`,`shop_id`),
  KEY `idx_reopen_status` (`is_reopened`,`status`),
  KEY `idx_warranty` (`delivered_at`,`warranty_days`),
  KEY `idx_custom_device` (`custom_brand`,`custom_model`),
  KEY `idx_reopen_delivered` (`reopen_delivered_at`),
  KEY `idx_reopen_count` (`reopen_count`),
  KEY `idx_original_delivered` (`original_delivered_at`),
  FULLTEXT KEY `idx_customer_search` (`customer_name`,`customer_phone`),
  CONSTRAINT `repairs_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `repairs_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`),
  CONSTRAINT `repairs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `repairs_ibfk_4` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول repair_history
-- =====================================================
CREATE TABLE `repair_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `event_type` enum('created','status_changed','completed','delivered','reopened','warranty_reopened','paid_reopened','goodwill_reopened','redelivered','cost_updated','note_added') NOT NULL COMMENT 'نوع الحدث',
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'بيانات الحدث بتنسيق JSON' CHECK (json_valid(`event_data`)),
  `old_status` varchar(50) DEFAULT NULL COMMENT 'الحالة القديمة',
  `new_status` varchar(50) DEFAULT NULL COMMENT 'الحالة الجديدة',
  `description` text DEFAULT NULL COMMENT 'وصف الحدث',
  `warranty_days` int(11) DEFAULT NULL COMMENT 'أيام الضمان في وقت الحدث',
  `delivered_at` datetime DEFAULT NULL COMMENT 'تاريخ التسليم في وقت الحدث',
  `cost_amount` decimal(10,2) DEFAULT NULL COMMENT 'التكلفة في وقت الحدث',
  `performed_by` int(11) NOT NULL COMMENT 'المستخدم الذي قام بالإجراء',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'وقت تسجيل الحدث',
  PRIMARY KEY (`id`),
  KEY `idx_repair` (`repair_id`),
  KEY `idx_shop` (`shop_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `performed_by` (`performed_by`),
  CONSTRAINT `repair_history_ibfk_1` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_history_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_history_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='السجل التاريخي الكامل لجميع أحداث الإصلاحات';

-- =====================================================
-- جدول spare_parts
-- =====================================================
CREATE TABLE `spare_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL COMMENT 'ربط بالمحل',
  `part_code` varchar(50) DEFAULT NULL COMMENT 'كود القطعة التجاري',
  `part_name` varchar(200) NOT NULL COMMENT 'اسم القطعة',
  `category` varchar(100) DEFAULT NULL COMMENT 'فئة القطعة',
  `cost_price` decimal(10,2) DEFAULT NULL COMMENT 'سعر الشراء - Admin فقط',
  `labor_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'تكلفة التركيب - Admin فقط',
  `total_price` decimal(10,2) NOT NULL COMMENT 'السعر النهائي للعميل',
  `supplier_name` varchar(200) DEFAULT NULL COMMENT 'اسم المزود - Admin فقط',
  `supplier_contact` varchar(100) DEFAULT NULL COMMENT 'تواصل المزود',
  `stock_status` enum('available','out_of_stock','order_required') DEFAULT 'available',
  `stock_quantity` int(11) DEFAULT 0 COMMENT 'الكمية المتوفرة',
  `min_stock_level` int(11) DEFAULT 1 COMMENT 'الحد الأدنى للمخزون',
  `notes` text DEFAULT NULL COMMENT 'ملاحظات عامة',
  `warranty_days` int(11) DEFAULT 30 COMMENT 'أيام الضمانة',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'قطعة نشطة أم لا',
  `price_updated_at` datetime DEFAULT current_timestamp() COMMENT 'آخر تحديث للسعر',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shop_parts` (`shop_id`,`is_active`),
  KEY `idx_part_code` (`part_code`),
  KEY `idx_category` (`category`),
  KEY `idx_stock_status` (`stock_status`),
  KEY `idx_part_name` (`part_name`),
  KEY `idx_parts_search` (`shop_id`,`part_name`,`category`,`is_active`),
  CONSTRAINT `spare_parts_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول spare_parts_compatibility
-- =====================================================
CREATE TABLE `spare_parts_compatibility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spare_part_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `model_id` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL COMMENT 'ملاحظات خاصة بالتوافق',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_compatibility` (`spare_part_id`,`brand_id`,`model_id`),
  KEY `model_id` (`model_id`),
  KEY `idx_part_compatibility` (`spare_part_id`),
  KEY `idx_brand_model_parts` (`brand_id`,`model_id`),
  CONSTRAINT `spare_parts_compatibility_ibfk_1` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_parts_compatibility_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_parts_compatibility_ibfk_3` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول spare_parts_price_history
-- =====================================================
CREATE TABLE `spare_parts_price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spare_part_id` int(11) NOT NULL,
  `old_cost_price` decimal(10,2) DEFAULT NULL,
  `old_labor_cost` decimal(10,2) DEFAULT NULL,
  `old_total_price` decimal(10,2) DEFAULT NULL,
  `new_cost_price` decimal(10,2) DEFAULT NULL,
  `new_labor_cost` decimal(10,2) DEFAULT NULL,
  `new_total_price` decimal(10,2) DEFAULT NULL,
  `change_reason` varchar(255) DEFAULT NULL COMMENT 'سبب التغيير',
  `updated_by` int(11) DEFAULT NULL COMMENT 'المستخدم الذي غير السعر',
  `updated_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_part_history` (`spare_part_id`),
  KEY `idx_price_changes_date` (`updated_at`),
  KEY `idx_price_history_dates` (`spare_part_id`,`updated_at`),
  CONSTRAINT `spare_parts_price_history_ibfk_1` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_parts_price_history_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- جدول repair_spare_parts
-- =====================================================
CREATE TABLE `repair_spare_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `spare_part_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1 COMMENT 'الكمية المستخدمة',
  `unit_cost_price` decimal(10,2) DEFAULT NULL COMMENT 'سعر الشراء وقت الإصلاح',
  `unit_labor_cost` decimal(10,2) DEFAULT NULL COMMENT 'تكلفة العمالة وقت الإصلاح',
  `unit_price` decimal(10,2) NOT NULL COMMENT 'السعر للعميل وقت الإصلاح',
  `total_price` decimal(10,2) NOT NULL COMMENT 'quantity × unit_price',
  `warranty_days` int(11) DEFAULT 30 COMMENT 'ضمانة هذه القطعة',
  `notes` text DEFAULT NULL COMMENT 'ملاحظات خاصة بهذا الاستخدام',
  `used_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_repair_parts` (`repair_id`),
  KEY `idx_part_usage` (`spare_part_id`),
  KEY `idx_usage_date` (`used_at`),
  KEY `idx_repair_parts_profit` (`repair_id`,`total_price`),
  CONSTRAINT `repair_spare_parts_ibfk_1` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_spare_parts_ibfk_2` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger: generate_invoice_number
DELIMITER $$
CREATE TRIGGER `generate_invoice_number` BEFORE INSERT ON `invoices` FOR EACH ROW BEGIN
    DECLARE next_number INT;
    DECLARE year_str VARCHAR(4);
    SET year_str = DATE_FORMAT(NOW(), '%Y');
    SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM invoices
    WHERE invoice_number LIKE CONCAT('INV-', year_str, '-%');
    SET NEW.invoice_number = CONCAT('INV-', year_str, '-', LPAD(next_number, 4, '0'));
END$$
DELIMITER ;

-- Trigger: calculate_invoice_totals_insert
DELIMITER $$
CREATE TRIGGER `calculate_invoice_totals_insert` AFTER INSERT ON `invoice_items` FOR EACH ROW BEGIN
    UPDATE invoices
    SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id),
        iva_amount = subtotal * (iva_rate / 100),
        total = subtotal + iva_amount
    WHERE id = NEW.invoice_id;
END$$
DELIMITER ;

-- Trigger: calculate_invoice_totals_update
DELIMITER $$
CREATE TRIGGER `calculate_invoice_totals_update` AFTER UPDATE ON `invoice_items` FOR EACH ROW BEGIN
    UPDATE invoices
    SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id),
        iva_amount = subtotal * (iva_rate / 100),
        total = subtotal + iva_amount
    WHERE id = NEW.invoice_id;
END$$
DELIMITER ;

-- Trigger: calculate_invoice_totals_delete
DELIMITER $$
CREATE TRIGGER `calculate_invoice_totals_delete` AFTER DELETE ON `invoice_items` FOR EACH ROW BEGIN
    UPDATE invoices
    SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM invoice_items WHERE invoice_id = OLD.invoice_id),
        iva_amount = subtotal * (iva_rate / 100),
        total = subtotal + iva_amount
    WHERE id = OLD.invoice_id;
END$$
DELIMITER ;

-- Trigger: check_stock_before_use
DELIMITER $$
CREATE TRIGGER `check_stock_before_use` BEFORE INSERT ON `repair_spare_parts` FOR EACH ROW BEGIN
    DECLARE current_stock INT;
    DECLARE part_name VARCHAR(200);
    DECLARE error_message TEXT;
    SELECT stock_quantity, part_name INTO current_stock, part_name FROM spare_parts WHERE id = NEW.spare_part_id;
    IF current_stock < NEW.quantity THEN
        SET error_message = CONCAT('Insufficient stock for part: ', IFNULL(part_name, 'Unknown'), '. Available: ', IFNULL(current_stock, 0), ', Required: ', NEW.quantity);
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = error_message;
    END IF;
END$$
DELIMITER ;

-- Trigger: update_stock_on_repair
DELIMITER $$
CREATE TRIGGER `update_stock_on_repair` AFTER INSERT ON `repair_spare_parts` FOR EACH ROW BEGIN
    UPDATE spare_parts
    SET stock_quantity = stock_quantity - NEW.quantity,
        stock_status = CASE
            WHEN (stock_quantity - NEW.quantity) <= 0 THEN 'out_of_stock'
            WHEN (stock_quantity - NEW.quantity) <= min_stock_level THEN 'order_required'
            ELSE 'available'
        END
    WHERE id = NEW.spare_part_id;
END$$
DELIMITER ;

-- Trigger: save_price_history
DELIMITER $$
CREATE TRIGGER `save_price_history` BEFORE UPDATE ON `spare_parts` FOR EACH ROW BEGIN
    IF OLD.cost_price != NEW.cost_price OR OLD.labor_cost != NEW.labor_cost OR OLD.total_price != NEW.total_price THEN
        INSERT INTO spare_parts_price_history (spare_part_id, old_cost_price, old_labor_cost, old_total_price, new_cost_price, new_labor_cost, new_total_price, change_reason)
        VALUES (NEW.id, OLD.cost_price, OLD.labor_cost, OLD.total_price, NEW.cost_price, NEW.labor_cost, NEW.total_price, 'Price update');
        SET NEW.price_updated_at = NOW();
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- VIEWS
-- =====================================================

-- View: invoice_details
CREATE VIEW `invoice_details` AS
SELECT `i`.`id` AS `id`, `i`.`invoice_number` AS `invoice_number`, `i`.`invoice_date` AS `invoice_date`, `i`.`due_date` AS `due_date`, `c`.`full_name` AS `customer_name`, `c`.`phone` AS `customer_phone`, `c`.`email` AS `customer_email`, `c`.`id_type` AS `id_type`, `c`.`id_number` AS `id_number`, `c`.`address` AS `customer_address`, `i`.`subtotal` AS `subtotal`, `i`.`iva_rate` AS `iva_rate`, `i`.`iva_amount` AS `iva_amount`, `i`.`total` AS `total`, `i`.`payment_status` AS `payment_status`, `i`.`paid_amount` AS `paid_amount`, `i`.`payment_date` AS `payment_date`, `i`.`payment_method` AS `payment_method`, `s`.`name` AS `shop_name`, `s`.`phone1` AS `shop_phone`, `s`.`email` AS `shop_email`, `s`.`address` AS `shop_address`, `s`.`logo` AS `shop_logo`, `u`.`name` AS `created_by_name`, `i`.`created_at` AS `created_at`, `i`.`notes` AS `notes` FROM (((`invoices` `i` JOIN `customers` `c` ON(`i`.`customer_id` = `c`.`id`)) JOIN `shops` `s` ON(`i`.`shop_id` = `s`.`id`)) JOIN `users` `u` ON(`i`.`created_by` = `u`.`id`));

-- View: low_stock_parts
CREATE VIEW `low_stock_parts` AS
SELECT `sp`.`id` AS `id`, `sp`.`shop_id` AS `shop_id`, `sp`.`part_code` AS `part_code`, `sp`.`part_name` AS `part_name`, `sp`.`category` AS `category`, `sp`.`stock_quantity` AS `stock_quantity`, `sp`.`min_stock_level` AS `min_stock_level`, `sp`.`supplier_name` AS `supplier_name`, `sp`.`supplier_contact` AS `supplier_contact`, `s`.`name` AS `shop_name` FROM (`spare_parts` `sp` JOIN `shops` `s` ON(`sp`.`shop_id` = `s`.`id`)) WHERE `sp`.`is_active` = 1 AND (`sp`.`stock_quantity` <= `sp`.`min_stock_level` OR `sp`.`stock_status` = 'out_of_stock');

-- View: repairs_with_device_info
CREATE VIEW `repairs_with_device_info` AS
SELECT `r`.`id` AS `id`, `r`.`reference` AS `reference`, `r`.`customer_name` AS `customer_name`, `r`.`customer_phone` AS `customer_phone`, `r`.`device_input_type` AS `device_input_type`, `r`.`brand_id` AS `brand_id`, `r`.`model_id` AS `model_id`, `b`.`name` AS `brand_name`, `m`.`name` AS `model_name`, `m`.`model_reference` AS `model_reference`, `r`.`custom_brand` AS `custom_brand`, `r`.`custom_model` AS `custom_model`, CASE WHEN `r`.`device_input_type` = 'otro' THEN CONCAT(COALESCE(`r`.`custom_brand`,'Desconocido'),' ',COALESCE(`r`.`custom_model`,'Desconocido')) ELSE CONCAT(`b`.`name`,' ',`m`.`name`,CASE WHEN `m`.`model_reference` IS NOT NULL THEN CONCAT(' (',`m`.`model_reference`,')') ELSE '' END) END AS `device_display`, `r`.`issue_description` AS `issue_description`, `r`.`estimated_cost` AS `estimated_cost`, `r`.`status` AS `status`, `r`.`priority` AS `priority`, `r`.`created_at` AS `created_at` FROM ((`repairs` `r` LEFT JOIN `brands` `b` ON(`r`.`brand_id` = `b`.`id`)) LEFT JOIN `models` `m` ON(`r`.`model_id` = `m`.`id`));

-- View: spare_parts_profit_report
CREATE VIEW `spare_parts_profit_report` AS
SELECT `sp`.`id` AS `id`, `sp`.`shop_id` AS `shop_id`, `sp`.`part_code` AS `part_code`, `sp`.`part_name` AS `part_name`, `sp`.`category` AS `category`, `sp`.`cost_price` AS `cost_price`, `sp`.`labor_cost` AS `labor_cost`, `sp`.`total_price` AS `total_price`, `sp`.`total_price`- COALESCE(`sp`.`cost_price`,0) - COALESCE(`sp`.`labor_cost`,0) AS `profit_per_unit`, `sp`.`stock_quantity` AS `stock_quantity`, `sp`.`stock_status` AS `stock_status`, COUNT(`rsp`.`id`) AS `times_used`, SUM(`rsp`.`quantity`) AS `total_quantity_sold`, SUM(`rsp`.`total_price`) AS `total_revenue`, SUM(`rsp`.`quantity` * COALESCE(`rsp`.`unit_cost_price`,0)) AS `total_cost_price`, SUM(`rsp`.`quantity` * COALESCE(`rsp`.`unit_labor_cost`,0)) AS `total_labor_cost`, SUM(`rsp`.`total_price`) - SUM(`rsp`.`quantity` * COALESCE(`rsp`.`unit_cost_price`,0)) - SUM(`rsp`.`quantity` * COALESCE(`rsp`.`unit_labor_cost`,0)) AS `total_profit` FROM ((`spare_parts` `sp` LEFT JOIN `repair_spare_parts` `rsp` ON(`sp`.`id` = `rsp`.`spare_part_id`)) LEFT JOIN `repairs` `r` ON(`rsp`.`repair_id` = `r`.`id` AND `r`.`status` = 'delivered')) WHERE `sp`.`is_active` = 1 GROUP BY `sp`.`id`;

-- View: spare_parts_with_compatibility
CREATE VIEW `spare_parts_with_compatibility` AS
SELECT `sp`.`id` AS `id`, `sp`.`shop_id` AS `shop_id`, `sp`.`part_code` AS `part_code`, `sp`.`part_name` AS `part_name`, `sp`.`category` AS `category`, `sp`.`total_price` AS `total_price`, `sp`.`stock_status` AS `stock_status`, `sp`.`stock_quantity` AS `stock_quantity`, `sp`.`warranty_days` AS `warranty_days`, GROUP_CONCAT(DISTINCT CONCAT(`b`.`name`,' - ',`m`.`name`) ORDER BY `b`.`name` ASC,`m`.`name` ASC SEPARATOR ', ') AS `compatible_phones`, COUNT(DISTINCT `spc`.`model_id`) AS `compatibility_count` FROM (((`spare_parts` `sp` LEFT JOIN `spare_parts_compatibility` `spc` ON(`sp`.`id` = `spc`.`spare_part_id`)) LEFT JOIN `brands` `b` ON(`spc`.`brand_id` = `b`.`id`)) LEFT JOIN `models` `m` ON(`spc`.`model_id` = `m`.`id`)) WHERE `sp`.`is_active` = 1 GROUP BY `sp`.`id`;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure: GetSparePartsByPhone
DELIMITER $$
CREATE PROCEDURE `GetSparePartsByPhone` (IN `p_shop_id` INT, IN `p_brand_id` INT, IN `p_model_id` INT)
BEGIN
    SELECT DISTINCT sp.id, sp.part_code, sp.part_name, sp.category, sp.total_price, sp.stock_status, sp.stock_quantity, sp.warranty_days, sp.notes
    FROM spare_parts sp
    JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
    WHERE sp.shop_id = p_shop_id AND sp.is_active = TRUE AND spc.brand_id = p_brand_id AND spc.model_id = p_model_id
    ORDER BY sp.category, sp.part_name;
END$$
DELIMITER ;

-- Procedure: SearchModels
DELIMITER $$
CREATE PROCEDURE `SearchModels` (IN `p_search_term` VARCHAR(255), IN `p_limit` INT)
BEGIN
    SELECT m.id AS model_id, m.name AS model_name, m.model_reference, b.id AS brand_id, b.name AS brand_name,
        CONCAT(b.name, ' ', m.name, CASE WHEN m.model_reference IS NOT NULL THEN CONCAT(' (', m.model_reference, ')') ELSE '' END) AS display_name
    FROM models m
    JOIN brands b ON m.brand_id = b.id
    WHERE m.name LIKE CONCAT('%', p_search_term, '%') OR m.model_reference LIKE CONCAT('%', p_search_term, '%') OR b.name LIKE CONCAT('%', p_search_term, '%') OR CONCAT(b.name, ' ', m.name) LIKE CONCAT('%', p_search_term, '%')
    ORDER BY CASE WHEN m.model_reference = p_search_term THEN 1 WHEN m.model_reference LIKE CONCAT(p_search_term, '%') THEN 2 WHEN m.name LIKE CONCAT(p_search_term, '%') THEN 3 WHEN b.name LIKE CONCAT(p_search_term, '%') THEN 4 ELSE 5 END, b.name, m.name
    LIMIT p_limit;
END$$
DELIMITER ;

-- Procedure: SearchSpareParts
DELIMITER $$
CREATE PROCEDURE `SearchSpareParts` (IN `p_shop_id` INT, IN `p_search_term` VARCHAR(255), IN `p_category` VARCHAR(100), IN `p_stock_status` VARCHAR(20))
BEGIN
    SELECT sp.id, sp.part_code, sp.part_name, sp.category, sp.total_price, sp.stock_status, sp.stock_quantity, sp.supplier_name,
        GROUP_CONCAT(DISTINCT CONCAT(b.name, ' ', m.name) SEPARATOR ', ') as compatible_phones
    FROM spare_parts sp
    LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
    LEFT JOIN brands b ON spc.brand_id = b.id
    LEFT JOIN models m ON spc.model_id = m.id
    WHERE sp.shop_id = p_shop_id AND sp.is_active = TRUE
    AND (p_search_term IS NULL OR sp.part_name LIKE CONCAT('%', p_search_term, '%') OR sp.part_code LIKE CONCAT('%', p_search_term, '%'))
    AND (p_category IS NULL OR sp.category = p_category)
    AND (p_stock_status IS NULL OR sp.stock_status = p_stock_status)
    GROUP BY sp.id
    ORDER BY sp.part_name;
END$$
DELIMITER ;

-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
