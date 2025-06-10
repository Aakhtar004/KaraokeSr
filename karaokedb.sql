-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for karaokedb
CREATE DATABASE IF NOT EXISTS `karaokedb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `karaokedb`;

-- Dumping structure for table karaokedb.categorias_producto
CREATE TABLE IF NOT EXISTS `categorias_producto` (
  `id_categoria_producto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre de la categoría (ej: Bebidas, Piqueos, Licores)',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción adicional de la categoría',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'TRUE: activa, FALSE: inactiva',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_categoria_producto`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de los productos';

-- Dumping data for table karaokedb.categorias_producto: ~7 rows (approximately)
INSERT INTO `categorias_producto` (`id_categoria_producto`, `nombre`, `descripcion`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
	(1, 'Piqueos', 'Entradas y acompañamientos para compartir', 1, '2025-05-25 07:42:37', '2025-05-25 07:42:37'),
	(2, 'Cocteles', 'Tragos preparados, clásicos y de la casa', 1, '2025-05-25 07:42:59', '2025-05-25 07:42:59'),
	(3, 'Licores', 'Botellas y presentaciones selladas', 1, '2025-05-25 07:43:16', '2025-05-25 07:43:16'),
	(4, 'Bebidas', 'Bebidas sin alcohol', 1, '2025-05-25 07:43:42', '2025-05-25 07:43:42'),
	(5, 'Cervezas', 'Cervezas individuales', 1, '2025-05-25 07:44:02', '2025-05-25 07:44:02'),
	(6, 'Jarras', 'Jarras de tragos o mezclas para compartir', 1, '2025-05-25 07:44:22', '2025-05-25 07:44:22'),
	(7, 'Baldes', 'Baldes de cervezas para grupos', 1, '2025-05-25 07:44:37', '2025-05-25 07:44:37');

-- Dumping structure for table karaokedb.comprobantes
CREATE TABLE IF NOT EXISTS `comprobantes` (
  `id_comprobante` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_pedido` bigint(20) unsigned NOT NULL COMMENT 'FK al pedido asociado a este comprobante',
  `id_usuario_cajero` int(10) unsigned NOT NULL COMMENT 'FK al usuario (cajero) que emitió el comprobante',
  `tipo_documento_cliente` enum('DNI','RUC','CE','PASAPORTE','SIN_DOCUMENTO') NOT NULL DEFAULT 'SIN_DOCUMENTO' COMMENT 'Tipo de documento del cliente',
  `numero_documento_cliente` varchar(20) DEFAULT NULL COMMENT 'Número del documento del cliente (DNI/RUC)',
  `nombre_razon_social_cliente` varchar(200) NOT NULL COMMENT 'Nombre o razón social del cliente',
  `direccion_cliente` varchar(255) DEFAULT NULL COMMENT 'Dirección fiscal del cliente',
  `serie_comprobante` varchar(10) NOT NULL COMMENT 'Serie del comprobante (ej: B001, F001)',
  `numero_correlativo_comprobante` bigint(20) unsigned NOT NULL COMMENT 'Número correlativo del comprobante',
  `fecha_emision` timestamp NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de emisión del comprobante',
  `moneda` enum('PEN','USD') NOT NULL DEFAULT 'PEN' COMMENT 'Moneda del comprobante',
  `subtotal_comprobante` decimal(12,2) NOT NULL COMMENT 'Monto antes de impuestos',
  `igv_aplicado_tasa` decimal(5,2) NOT NULL DEFAULT 18.00 COMMENT 'Tasa de IGV aplicada (ej: 18.00 para 18%)',
  `monto_igv` decimal(12,2) NOT NULL COMMENT 'Monto del IGV',
  `monto_total_comprobante` decimal(12,2) NOT NULL COMMENT 'Monto total a pagar',
  `tipo_comprobante` enum('BOLETA','FACTURA','NOTA_VENTA') NOT NULL COMMENT 'Tipo de comprobante emitido',
  `metodo_pago` enum('EFECTIVO','TARJETA_CREDITO','TARJETA_DEBITO','YAPE','PLIN','TRANSFERENCIA','MIXTO') DEFAULT NULL COMMENT 'Método de pago utilizado',
  `referencia_pago` varchar(100) DEFAULT NULL COMMENT 'Referencia para pagos con tarjeta, yape, plin, etc.',
  `estado_comprobante` enum('EMITIDO','ANULADO','PAGADO') NOT NULL DEFAULT 'EMITIDO' COMMENT 'Estado del comprobante',
  `qr_code_data` text DEFAULT NULL COMMENT 'Datos para generar el QR de SUNAT (para facturación electrónica)',
  `hash_sunat` varchar(255) DEFAULT NULL COMMENT 'Código Hash de SUNAT para facturación electrónica',
  `notas_comprobante` text DEFAULT NULL,
  `fecha_anulacion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_comprobante`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  UNIQUE KEY `uq_serie_correlativo_tipo` (`serie_comprobante`,`numero_correlativo_comprobante`,`tipo_comprobante`),
  KEY `fk_comprobante_usuario_cajero` (`id_usuario_cajero`),
  KEY `idx_comprobante_fecha_emision` (`fecha_emision`),
  KEY `idx_comprobante_estado` (`estado_comprobante`),
  KEY `idx_comprobante_cliente_doc` (`numero_documento_cliente`),
  CONSTRAINT `fk_comprobante_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON UPDATE CASCADE,
  CONSTRAINT `fk_comprobante_usuario_cajero` FOREIGN KEY (`id_usuario_cajero`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprobantes de pago emitidos';

-- Dumping data for table karaokedb.comprobantes: ~8 rows (approximately)
INSERT INTO `comprobantes` (`id_comprobante`, `id_pedido`, `id_usuario_cajero`, `tipo_documento_cliente`, `numero_documento_cliente`, `nombre_razon_social_cliente`, `direccion_cliente`, `serie_comprobante`, `numero_correlativo_comprobante`, `fecha_emision`, `moneda`, `subtotal_comprobante`, `igv_aplicado_tasa`, `monto_igv`, `monto_total_comprobante`, `tipo_comprobante`, `metodo_pago`, `referencia_pago`, `estado_comprobante`, `qr_code_data`, `hash_sunat`, `notas_comprobante`, `fecha_anulacion`) VALUES
	(7, 7, 2, 'DNI', '95471863', 'Cliente', NULL, 'B001', 1, '2025-06-02 10:15:21', 'PEN', 30.51, 18.00, 5.49, 36.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(8, 6, 2, 'DNI', '27460426', 'Cliente', NULL, 'B001', 2, '2025-06-02 21:29:28', 'PEN', 487.29, 18.00, 87.71, 575.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(9, 8, 2, 'DNI', '53123052', 'Cliente', NULL, 'B001', 3, '2025-06-03 02:25:12', 'PEN', 2542.37, 18.00, 457.63, 3000.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(10, 10, 2, 'DNI', '36047962', 'Cliente', NULL, 'B001', 4, '2025-06-05 03:33:06', 'PEN', 42.37, 18.00, 7.63, 50.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(11, 13, 2, 'DNI', '18598342', 'Cliente', NULL, 'B001', 5, '2025-06-06 00:56:00', 'PEN', 423.73, 18.00, 76.27, 500.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(12, 14, 2, 'DNI', '42781107', 'Cliente', NULL, 'B001', 6, '2025-06-06 05:51:51', 'PEN', 84.75, 18.00, 15.25, 100.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(13, 12, 2, 'DNI', '38828486', 'Cliente', NULL, 'B001', 7, '2025-06-08 21:32:23', 'PEN', 38.14, 18.00, 6.86, 45.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(14, 11, 2, 'DNI', '59263582', 'Cliente', NULL, 'B001', 8, '2025-06-08 21:35:10', 'PEN', 28.81, 18.00, 5.19, 34.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(15, 9, 2, 'DNI', '87095738', 'Cliente', NULL, 'B001', 9, '2025-06-08 21:44:49', 'PEN', 1694.92, 18.00, 305.08, 2000.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL),
	(16, 5, 2, 'DNI', '40398441', 'Cliente', NULL, 'B001', 10, '2025-06-09 02:30:31', 'PEN', 267.80, 18.00, 48.20, 316.00, 'BOLETA', 'YAPE', NULL, 'EMITIDO', 'QR_DATA_PLACEHOLDER', 'HASH_PLACEHOLDER', NULL, NULL);

-- Dumping structure for table karaokedb.mesas
CREATE TABLE IF NOT EXISTS `mesas` (
  `id_mesa` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero_mesa` varchar(10) NOT NULL COMMENT 'Número o código identificador de la mesa',
  `estado` enum('disponible','ocupada') NOT NULL DEFAULT 'disponible' COMMENT 'Estado actual de la mesa',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_mesa`),
  UNIQUE KEY `numero_mesa` (`numero_mesa`),
  KEY `idx_mesa_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mesas del establecimiento';

-- Dumping data for table karaokedb.mesas: ~22 rows (approximately)
INSERT INTO `mesas` (`id_mesa`, `numero_mesa`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
	(1, '01', 'disponible', '2025-05-27 04:10:49', '2025-06-02 05:14:56'),
	(2, '02', 'ocupada', '2025-05-27 04:10:49', '2025-05-27 05:48:25'),
	(3, '03', 'ocupada', '2025-05-27 04:10:49', '2025-06-02 05:14:14'),
	(4, '04', 'ocupada', '2025-05-27 05:48:18', '2025-06-02 05:14:25'),
	(5, '05', 'disponible', '2025-05-28 15:27:43', '2025-06-08 21:30:31'),
	(6, '06', 'disponible', '2025-05-28 15:27:48', '2025-06-08 16:44:49'),
	(7, '07', 'disponible', '2025-05-28 15:27:52', '2025-05-28 15:27:52'),
	(8, '08', 'disponible', '2025-05-28 15:28:07', '2025-05-28 15:28:07'),
	(9, '09', 'disponible', '2025-05-28 15:28:11', '2025-05-28 15:28:11'),
	(10, '10', 'disponible', '2025-05-28 15:28:15', '2025-06-08 16:32:23'),
	(11, '11', 'disponible', '2025-05-28 15:28:18', '2025-06-06 00:51:51'),
	(12, '12', 'disponible', '2025-05-28 15:30:39', '2025-06-08 16:35:10'),
	(13, '13', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(14, '14', 'disponible', '2025-05-28 15:30:39', '2025-06-02 21:25:12'),
	(15, '15', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(16, '16', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(17, '17', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(18, '18', 'disponible', '2025-05-28 15:30:39', '2025-06-02 05:15:21'),
	(19, '19', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(20, '20', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(21, '21', 'disponible', '2025-05-28 15:30:39', '2025-05-28 15:30:39'),
	(22, '22', 'disponible', '2025-05-28 15:30:39', '2025-06-02 21:22:46');

-- Dumping structure for table karaokedb.pagos_pedido_detalle
CREATE TABLE IF NOT EXISTS `pagos_pedido_detalle` (
  `id_pago_pedido_detalle` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_comprobante` bigint(20) unsigned NOT NULL COMMENT 'FK al comprobante general al que pertenece este detalle de pago',
  `id_pedido_detalle` bigint(20) unsigned NOT NULL COMMENT 'FK al ítem específico del pedido (de la tabla Pedido_Detalles) que se está pagando con este método',
  `cantidad_item_pagada` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Cantidad del ítem referenciado en id_pedido_detalle que es cubierta por esta línea de pago. Ej: Si Pedido_Detalles tiene 3 Cervezas y 1 se paga con Yape, cantidad_item_pagada sería 1.',
  `monto_pagado` decimal(10,2) NOT NULL COMMENT 'Monto exacto cubierto por esta línea de pago para la cantidad_item_pagada. Idealmente, este monto es (Pedido_Detalles.precio_unitario_momento * Pagos_Pedido_Detalle.cantidad_item_pagada).',
  `metodo_pago` enum('EFECTIVO','TARJETA_CREDITO','TARJETA_DEBITO','YAPE','PLIN','TRANSFERENCIA') NOT NULL COMMENT 'Método de pago específico utilizado para esta porción del ítem del pedido.',
  `referencia_pago` varchar(100) DEFAULT NULL COMMENT 'Referencia asociada a este pago específico (ej: ID de transacción Yape/Plin, últimos 4 dígitos de tarjeta, etc.)',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pago_pedido_detalle`),
  KEY `fk_pagos_pedido_detalle_comprobante` (`id_comprobante`),
  KEY `fk_pagos_pedido_detalle_pedido_detalle` (`id_pedido_detalle`),
  KEY `idx_pagos_pedido_detalle_metodo_pago` (`metodo_pago`),
  CONSTRAINT `fk_pagos_pedido_detalle_comprobante` FOREIGN KEY (`id_comprobante`) REFERENCES `comprobantes` (`id_comprobante`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_pedido_detalle_pedido_detalle` FOREIGN KEY (`id_pedido_detalle`) REFERENCES `pedido_detalles` (`id_pedido_detalle`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalla los métodos de pago para ítems específicos o cantidades de ítems dentro de un comprobante.';

-- Dumping data for table karaokedb.pagos_pedido_detalle: ~12 rows (approximately)
INSERT INTO `pagos_pedido_detalle` (`id_pago_pedido_detalle`, `id_comprobante`, `id_pedido_detalle`, `cantidad_item_pagada`, `monto_pagado`, `metodo_pago`, `referencia_pago`, `fecha_creacion`, `fecha_actualizacion`) VALUES
	(5, 7, 17, 3, 36.00, 'YAPE', 'REF-00000001-17', '2025-06-02 05:15:21', '2025-06-02 05:15:21'),
	(6, 8, 16, 3, 75.00, 'YAPE', 'REF-00000002-16', '2025-06-02 16:29:28', '2025-06-02 16:29:28'),
	(7, 8, 18, 2, 500.00, 'YAPE', 'REF-00000002-18', '2025-06-02 16:29:28', '2025-06-02 16:29:28'),
	(8, 9, 19, 3, 3000.00, 'YAPE', 'REF-00000003-19', '2025-06-02 21:25:12', '2025-06-02 21:25:12'),
	(9, 10, 21, 2, 50.00, 'YAPE', 'REF-00000004-21', '2025-06-04 22:33:06', '2025-06-04 22:33:06'),
	(10, 11, 24, 2, 500.00, 'YAPE', 'REF-00000005-24', '2025-06-05 19:56:00', '2025-06-05 19:56:00'),
	(11, 12, 25, 4, 100.00, 'YAPE', 'REF-00000006-25', '2025-06-06 00:51:51', '2025-06-06 00:51:51'),
	(12, 13, 23, 3, 45.00, 'YAPE', 'REF-00000007-23', '2025-06-08 16:32:23', '2025-06-08 16:32:23'),
	(13, 14, 22, 2, 34.00, 'YAPE', 'REF-00000008-22', '2025-06-08 16:35:10', '2025-06-08 16:35:10'),
	(14, 15, 20, 2, 2000.00, 'YAPE', 'REF-00000009-20', '2025-06-08 16:44:49', '2025-06-08 16:44:49'),
	(15, 16, 13, 2, 280.00, 'YAPE', 'REF-00000010-13', '2025-06-08 21:30:31', '2025-06-08 21:30:31'),
	(16, 16, 14, 3, 36.00, 'YAPE', 'REF-00000010-14', '2025-06-08 21:30:31', '2025-06-08 21:30:31');

-- Dumping structure for table karaokedb.pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id_pedido` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_mesa` int(10) unsigned NOT NULL COMMENT 'FK a la mesa donde se realiza el pedido',
  `id_usuario_mesero` int(10) unsigned NOT NULL COMMENT 'FK al usuario (mesero) que tomó el pedido',
  `fecha_hora_pedido` timestamp NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se realizó el pedido',
  `estado_pedido` enum('PENDIENTE','EN_PREPARACION','LISTO_PARA_SERVIR','SERVIDO','CANCELADO','PAGADO') NOT NULL DEFAULT 'PENDIENTE' COMMENT 'Estado general del pedido',
  `total_pedido` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto total del pedido (calculado)',
  `notas_adicionales` text DEFAULT NULL COMMENT 'Instrucciones especiales o comentarios del cliente para el pedido general',
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pedido`),
  KEY `fk_pedido_mesa` (`id_mesa`),
  KEY `fk_pedido_usuario_mesero` (`id_usuario_mesero`),
  KEY `idx_pedido_estado` (`estado_pedido`),
  KEY `idx_pedido_fecha` (`fecha_hora_pedido`),
  CONSTRAINT `fk_pedido_mesa` FOREIGN KEY (`id_mesa`) REFERENCES `mesas` (`id_mesa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_usuario_mesero` FOREIGN KEY (`id_usuario_mesero`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pedidos de los clientes';

-- Dumping data for table karaokedb.pedidos: ~13 rows (approximately)
INSERT INTO `pedidos` (`id_pedido`, `id_mesa`, `id_usuario_mesero`, `fecha_hora_pedido`, `estado_pedido`, `total_pedido`, `notas_adicionales`, `fecha_actualizacion`) VALUES
	(2, 2, 2, '2025-05-26 17:45:00', 'PENDIENTE', 45.00, NULL, '2025-06-02 05:13:57'),
	(3, 3, 2, '2025-05-26 18:00:00', 'PENDIENTE', 24.00, 'Extra hielo', '2025-06-02 05:13:58'),
	(4, 21, 2, '2025-05-28 15:16:07', 'PENDIENTE', 52.00, 'pepe', '2025-06-08 21:32:01'),
	(5, 5, 2, '2025-06-02 07:03:35', 'PAGADO', 316.00, NULL, '2025-06-08 21:30:31'),
	(6, 10, 2, '2025-06-02 07:53:56', 'PAGADO', 575.00, 'GOGOGO', '2025-06-02 16:29:28'),
	(7, 18, 2, '2025-06-02 07:58:10', 'PAGADO', 36.00, 'pipi', '2025-06-02 05:15:21'),
	(8, 14, 2, '2025-06-03 02:23:30', 'PAGADO', 3000.00, 'MANZANA DULCE, ANTIDIABETICOS', '2025-06-02 21:25:12'),
	(9, 6, 2, '2025-06-03 02:54:14', 'PAGADO', 2000.00, NULL, '2025-06-08 16:44:49'),
	(10, 11, 2, '2025-06-05 03:31:54', 'PAGADO', 50.00, 'Nota', '2025-06-04 22:33:06'),
	(11, 12, 2, '2025-06-05 23:56:49', 'PAGADO', 34.00, NULL, '2025-06-08 16:35:10'),
	(12, 10, 2, '2025-06-05 23:57:22', 'PAGADO', 45.00, '5', '2025-06-08 16:32:23'),
	(13, 11, 2, '2025-06-05 23:57:45', 'PAGADO', 500.00, '05', '2025-06-05 19:56:00'),
	(14, 11, 2, '2025-06-06 05:50:28', 'PAGADO', 100.00, 'nota1', '2025-06-06 00:51:51');

-- Dumping structure for table karaokedb.pedido_detalles
CREATE TABLE IF NOT EXISTS `pedido_detalles` (
  `id_pedido_detalle` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_pedido` bigint(20) unsigned NOT NULL COMMENT 'FK al pedido al que pertenece este detalle',
  `id_producto` int(10) unsigned NOT NULL COMMENT 'FK al producto solicitado',
  `cantidad` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Cantidad del producto solicitado',
  `precio_unitario_momento` decimal(10,2) NOT NULL COMMENT 'Precio del producto al momento de realizar el pedido',
  `subtotal` decimal(10,2) NOT NULL COMMENT 'Subtotal (cantidad * precio_unitario_momento)',
  `notas_producto` text DEFAULT NULL COMMENT 'Notas específicas para este producto en el pedido (ej: sin hielo, término medio)',
  `estado_item` enum('SOLICITADO','EN_PREPARACION','LISTO_PARA_ENTREGA','ENTREGADO_A_MESERO','ENTREGADO_A_CLIENTE','CANCELADO') NOT NULL DEFAULT 'SOLICITADO' COMMENT 'Estado del ítem dentro del pedido y su preparación',
  `id_usuario_preparador` int(10) unsigned DEFAULT NULL COMMENT 'FK al usuario (cocinero/bartender) que preparó/está preparando el ítem (opcional)',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion_estado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Para rastrear cuándo cambió el estado del ítem',
  PRIMARY KEY (`id_pedido_detalle`),
  KEY `fk_pedidodetalle_pedido` (`id_pedido`),
  KEY `fk_pedidodetalle_producto` (`id_producto`),
  KEY `fk_pedidodetalle_usuario_preparador` (`id_usuario_preparador`),
  KEY `idx_pedidodetalle_estado_item` (`estado_item`),
  CONSTRAINT `fk_pedidodetalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pedidodetalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedidodetalle_usuario_preparador` FOREIGN KEY (`id_usuario_preparador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de los productos por pedido y su estado de preparación';

-- Dumping data for table karaokedb.pedido_detalles: ~17 rows (approximately)
INSERT INTO `pedido_detalles` (`id_pedido_detalle`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario_momento`, `subtotal`, `notas_producto`, `estado_item`, `id_usuario_preparador`, `fecha_creacion`, `fecha_actualizacion_estado`) VALUES
	(3, 2, 5, 3, 15.00, 45.00, NULL, 'SOLICITADO', 4, '2025-05-27 04:10:49', '2025-05-27 04:10:49'),
	(4, 3, 69, 2, 12.00, 24.00, NULL, 'SOLICITADO', 3, '2025-05-27 04:10:49', '2025-05-27 22:22:45'),
	(7, 4, 17, 1, 18.00, 18.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-05-28 15:17:55', '2025-05-29 15:25:53'),
	(13, 5, 41, 2, 140.00, 280.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-02 07:41:49', '2025-06-06 00:39:16'),
	(14, 5, 69, 3, 12.00, 36.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-02 07:41:49', '2025-06-06 00:39:13'),
	(15, 4, 8, 2, 17.00, 34.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-02 07:53:26', '2025-06-06 00:39:15'),
	(16, 6, 4, 3, 25.00, 75.00, NULL, 'LISTO_PARA_ENTREGA', 4, '2025-06-02 07:53:56', '2025-06-02 16:28:47'),
	(17, 7, 70, 3, 12.00, 36.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-02 07:58:10', '2025-06-02 04:17:20'),
	(18, 6, 39, 2, 250.00, 500.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-02 21:27:09', '2025-06-02 16:28:25'),
	(19, 8, 114, 3, 1000.00, 3000.00, NULL, 'LISTO_PARA_ENTREGA', 4, '2025-06-03 02:23:30', '2025-06-02 21:24:34'),
	(20, 9, 114, 2, 1000.00, 2000.00, NULL, 'LISTO_PARA_ENTREGA', 4, '2025-06-03 02:54:14', '2025-06-06 00:38:50'),
	(21, 10, 1, 2, 25.00, 50.00, NULL, 'LISTO_PARA_ENTREGA', 4, '2025-06-05 03:31:54', '2025-06-04 22:32:33'),
	(22, 11, 8, 2, 17.00, 34.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-05 23:56:49', '2025-06-06 00:39:11'),
	(23, 12, 5, 3, 15.00, 45.00, NULL, 'LISTO_PARA_ENTREGA', 4, '2025-06-05 23:57:22', '2025-06-06 00:38:49'),
	(24, 13, 39, 2, 250.00, 500.00, NULL, 'LISTO_PARA_ENTREGA', 3, '2025-06-05 23:57:45', '2025-06-05 19:55:46'),
	(25, 14, 4, 4, 25.00, 100.00, NULL, 'LISTO_PARA_ENTREGA', 4, '2025-06-06 05:50:28', '2025-06-06 00:51:10');

-- Dumping structure for table karaokedb.productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id_producto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_categoria_producto` int(10) unsigned DEFAULT NULL COMMENT 'FK a Categorias_Producto',
  `area_destino` enum('cocina','bar','ambos') NOT NULL COMMENT 'Indica el área de preparación o gestión principal del producto (ej: cocina para platos, bar para bebidas)',
  `codigo_interno` varchar(50) DEFAULT NULL COMMENT 'Código interno o SKU del producto',
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre del producto',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción detallada del producto',
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio de venta del producto',
  `stock` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Cantidad disponible en inventario',
  `unidad_medida` varchar(50) DEFAULT NULL COMMENT 'Ej: Unidad, Botella, Plato, Litro',
  `imagen_url` varchar(255) DEFAULT NULL COMMENT 'URL de la imagen del producto',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'TRUE: disponible, FALSE: no disponible/agotado',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo_interno` (`codigo_interno`),
  KEY `fk_producto_categoria` (`id_categoria_producto`),
  KEY `idx_producto_nombre` (`nombre`),
  KEY `idx_producto_estado` (`estado`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria_producto`) REFERENCES `categorias_producto` (`id_categoria_producto`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Productos ofrecidos en el establecimiento';

-- Dumping data for table karaokedb.productos: ~87 rows (approximately)
INSERT INTO `productos` (`id_producto`, `id_categoria_producto`, `area_destino`, `codigo_interno`, `nombre`, `descripcion`, `precio_unitario`, `stock`, `unidad_medida`, `imagen_url`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
	(1, 1, 'cocina', NULL, 'Alitas Broaster', 'Piqueo para compartir', 2.00, 0, 'Unidad', NULL, 0, '2025-05-25 07:42:44', '2025-06-08 20:24:31'),
	(2, 1, 'cocina', NULL, 'Alitas BBQ', 'Piqueo para compartir', 25.00, 1, 'Unidad', NULL, 0, '2025-05-25 07:42:44', '2025-06-08 21:31:37'),
	(3, 1, 'cocina', NULL, 'Chicharrón de Pollo', 'Piqueo para compartir', 25.00, 50, 'Unidad', NULL, 0, '2025-05-25 07:42:44', '2025-05-27 07:01:27'),
	(4, 1, 'cocina', NULL, 'Salchitodo', 'Piqueo para compartir', 25.00, 43, 'Unidad', NULL, 1, '2025-05-25 07:42:44', '2025-06-06 00:50:28'),
	(5, 1, 'cocina', NULL, 'Salchipapa', 'Piqueo para compartir', 15.00, 47, 'Unidad', NULL, 1, '2025-05-25 07:42:44', '2025-06-05 18:57:22'),
	(6, 1, 'cocina', NULL, 'Porción de papas', 'Piqueo para compartir', 10.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:42:44', '2025-05-25 07:42:44'),
	(7, 1, 'cocina', NULL, 'Tequeños con Queso', 'Piqueo para compartir', 20.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:42:44', '2025-05-25 07:42:44'),
	(8, 2, 'bar', NULL, 'Negroni', 'Cóctel preparado en barra', 17.00, 47, 'Unidad', '(NULL)', 0, '2025-05-25 07:43:04', '2025-06-08 20:24:40'),
	(9, 2, 'bar', NULL, 'Clavo Oxidado', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(10, 2, 'bar', NULL, 'Padrino', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(11, 2, 'bar', NULL, 'Orgasmo', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(12, 2, 'bar', NULL, 'Orgasmo Múltiple', 'Cóctel preparado en barra', 20.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(13, 2, 'bar', NULL, 'Mojito Jagger', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(14, 2, 'bar', NULL, 'Mojito Corona', 'Cóctel preparado en barra', 30.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(15, 2, 'bar', NULL, 'Mojito de Limon', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(16, 2, 'bar', NULL, 'Mojito de Maracuyá', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(17, 2, 'bar', NULL, 'Mojito de Fresa', 'Cóctel preparado en barra', 18.00, 0, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-28 15:33:23'),
	(18, 2, 'bar', NULL, 'Mojito de Mango', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(19, 2, 'bar', NULL, 'Mojito de Piña', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(20, 2, 'bar', NULL, 'Mojito de Maracumango', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(21, 2, 'bar', NULL, 'Fernet', 'Cóctel preparado en barra', 17.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(22, 2, 'bar', NULL, 'Jack Daniels', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(23, 2, 'bar', NULL, 'Jagger', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(24, 2, 'bar', NULL, 'Gin Tonic', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(25, 2, 'bar', NULL, 'Pisco Sour', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(26, 2, 'bar', NULL, 'Tacna Sour', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(27, 2, 'bar', NULL, 'Maracuya Sour', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(28, 2, 'bar', NULL, 'Cuba Libre', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(29, 2, 'bar', NULL, 'Perú Libre', 'Cóctel preparado en barra', 17.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(30, 2, 'bar', NULL, 'Chilcano', 'Cóctel preparado en barra', 17.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(31, 2, 'bar', NULL, 'Machu Picchu', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(32, 2, 'bar', NULL, 'Margarita Blue', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(33, 2, 'bar', NULL, 'Margarita Clásica', 'Cóctel preparado en barra', 18.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(34, 2, 'bar', NULL, 'Alejandra', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(35, 2, 'bar', NULL, 'Piña Colada', 'Cóctel preparado en barra', 19.00, 50, 'Unidad', NULL, 1, '2025-05-25 07:43:04', '2025-05-25 07:43:04'),
	(39, 3, 'bar', NULL, 'ET. Negra Johnnie Walker', 'Botella sellada para consumo o venta directa', 250.00, 16, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-06-05 18:57:45'),
	(40, 3, 'bar', NULL, 'ET. Roja Johnnie Walker', 'Botella sellada para consumo o venta directa', 200.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(41, 3, 'bar', NULL, 'Jagger 700ml', 'Botella sellada para consumo o venta directa', 140.00, 18, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-06-02 02:41:49'),
	(42, 3, 'bar', NULL, 'Jack Daniel\'s', 'Botella sellada para consumo o venta directa', 170.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(43, 3, 'bar', NULL, 'Vodka Russkaya/Absolut', 'Botella sellada para consumo o venta directa', 120.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(44, 3, 'bar', NULL, 'Ron Blanco Flor de Caña', 'Botella sellada para consumo o venta directa', 120.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(45, 3, 'bar', NULL, 'Ron Blanco Cartavio', 'Botella sellada para consumo o venta directa', 130.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(46, 3, 'bar', NULL, 'Ron Rubio Flor de Caña', 'Botella sellada para consumo o venta directa', 120.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(47, 3, 'bar', NULL, 'Ron Rubio Cartavio', 'Botella sellada para consumo o venta directa', 130.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(48, 3, 'bar', NULL, 'Baileys', 'Botella sellada para consumo o venta directa', 120.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(49, 3, 'bar', NULL, 'Tequila Blanco', 'Botella sellada para consumo o venta directa', 130.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(50, 3, 'bar', NULL, 'Tequila Rubio', 'Botella sellada para consumo o venta directa', 150.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(51, 3, 'bar', NULL, 'Vino Borgoña', 'Botella sellada para consumo o venta directa', 50.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(52, 3, 'bar', NULL, 'Vino Seco', 'Botella sellada para consumo o venta directa', 50.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(53, 3, 'bar', NULL, 'Vino Tinto', 'Botella sellada para consumo o venta directa', 50.00, 20, 'Botella', NULL, 1, '2025-05-25 07:43:21', '2025-05-25 07:43:21'),
	(54, 4, 'bar', NULL, 'Agua mineral con Gas', 'Bebida sin alcohol', 3.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(55, 4, 'bar', NULL, 'Agua mineral sin Gas', 'Bebida sin alcohol', 3.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(56, 4, 'bar', NULL, 'Coca Cola 1/2 Lt.', 'Bebida sin alcohol', 8.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(57, 4, 'bar', NULL, 'Coca Cola 1 Lt.', 'Bebida sin alcohol', 15.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(58, 4, 'bar', NULL, 'Inca Kola 1/2 Lt.', 'Bebida sin alcohol', 8.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(59, 4, 'bar', NULL, 'Inca Kola 1 Lt.', 'Bebida sin alcohol', 15.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(60, 4, 'bar', NULL, 'Sprite 1/2 Lt.', 'Bebida sin alcohol', 8.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(61, 4, 'bar', NULL, 'RedBull', 'Bebida sin alcohol', 10.00, 100, 'Botella', NULL, 1, '2025-05-25 07:43:49', '2025-05-25 07:43:49'),
	(69, 5, 'bar', NULL, 'Stella', 'Cerveza individual', 12.00, 97, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-06-02 02:59:39'),
	(70, 5, 'bar', NULL, 'Corona', 'Cerveza individual', 12.00, 97, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-06-02 02:58:10'),
	(71, 5, 'bar', NULL, 'Pilsen', 'Cerveza individual', 20.00, 100, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-05-25 07:44:05'),
	(72, 5, 'bar', NULL, 'Cristal', 'Cerveza individual', 20.00, 100, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-05-25 07:44:05'),
	(73, 5, 'bar', NULL, 'Cuzqueña Trigo', 'Cerveza individual', 25.00, 100, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-05-25 07:44:05'),
	(74, 5, 'bar', NULL, 'Cuzqueña Dorada', 'Cerveza individual', 25.00, 100, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-05-25 07:44:05'),
	(75, 5, 'bar', NULL, 'Cuzqueña Negra', 'Cerveza individual', 25.00, 100, 'Botella', NULL, 1, '2025-05-25 07:44:05', '2025-05-25 07:44:05'),
	(76, 6, 'bar', NULL, 'Jarra Mojito Maracuyá', 'Jarra para compartir', 50.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(77, 6, 'bar', NULL, 'Jarra Mojito Limón', 'Jarra para compartir', 50.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(78, 6, 'bar', NULL, 'Jarra Mojito Fresa', 'Jarra para compartir', 50.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(79, 6, 'bar', NULL, 'Jarra Mojito Mango', 'Jarra para compartir', 50.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(80, 6, 'bar', NULL, 'Jarra Mojito Piña', 'Jarra para compartir', 50.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(81, 6, 'bar', NULL, 'Jarra Mojito Maracumango', 'Jarra para compartir', 65.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(82, 6, 'bar', NULL, 'Jarra de Cuba Libre', 'Jarra para compartir', 45.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(83, 6, 'bar', NULL, 'Jarra de Perú Libre', 'Jarra para compartir', 45.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(84, 6, 'bar', NULL, 'Jarra de Sangría', 'Jarra para compartir', 40.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(85, 6, 'bar', NULL, 'Jarra de Pisco Sour', 'Jarra para compartir', 65.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(86, 6, 'bar', NULL, 'Jarra de Tacna Sour', 'Jarra para compartir', 65.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(87, 6, 'bar', NULL, 'Jarra de Crvz. Pilsen', 'Jarra para compartir', 30.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(88, 6, 'bar', NULL, 'Jarra de Crvz. Cristal', 'Jarra para compartir', 30.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(89, 6, 'bar', NULL, 'Jarra de Crvz. Cuz. Trigo', 'Jarra para compartir', 35.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(90, 6, 'bar', NULL, 'Jarra de Crvz. Cuz. Dorada', 'Jarra para compartir', 35.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(91, 6, 'bar', NULL, 'Jarra de Crvz. Cuz. Negra', 'Jarra para compartir', 35.00, 30, 'Jarra', NULL, 1, '2025-05-25 07:44:26', '2025-05-25 07:44:26'),
	(107, 7, 'bar', NULL, 'Balde de Stella Pq.', 'Balde de cervezas', 60.00, 21, 'Balde', NULL, 1, '2025-05-25 07:44:40', '2025-05-27 00:52:57'),
	(108, 7, 'bar', NULL, 'Balde de Corona Pq.', 'Balde de cervezas', 60.00, 20, 'Balde', NULL, 1, '2025-05-25 07:44:40', '2025-05-25 07:44:40'),
	(109, 7, 'bar', NULL, 'Balde de Stella G.', 'Balde de cervezas', 80.00, 20, 'Balde', NULL, 1, '2025-05-25 07:44:40', '2025-05-25 07:44:40'),
	(110, 7, 'bar', NULL, 'Balde de Corona G.', 'Balde de cervezas', 80.00, 20, 'Balde', NULL, 1, '2025-05-25 07:44:40', '2025-05-25 07:44:40'),
	(111, 7, 'bar', NULL, 'Balde de Pilsen', 'Balde de cervezas', 85.00, 20, 'Balde', NULL, 1, '2025-05-25 07:44:40', '2025-05-25 07:44:40'),
	(112, 7, 'bar', NULL, 'Balde de Cuz. Trigo', 'Balde de cervezas', 85.00, 20, 'Balde', NULL, 1, '2025-05-25 07:44:40', '2025-05-25 07:44:40'),
	(114, 1, 'cocina', 'PROD0113', 'manzana', '', 1000.00, 45, 'unidad', 'https://www.recetasnestle.com.pe/sites/default/files/2022-07/tipos-de-manzana-royal-gala.jpg', 1, '2025-06-02 21:34:50', '2025-06-02 21:54:14'),
	(115, 2, 'bar', 'PROD0115', 'COCTEL DE MARGARITAS', '', 100.00, 8, 'unidad', 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.masquegastronomia.com%2Fblog%2Fcocteles-clasicos-ron-n44&psig=AOvVaw3E8DTpC6G2V8KFgMvfbJe1&ust=1749257583297000&source=images&cd=vfe&opi=89978449&ved=0CBUQjRxqFwoTCKjg0rnK240DFQAAAAAdAAAAABAE', 1, '2025-06-06 05:53:13', '2025-06-06 05:53:13');

-- Dumping structure for table karaokedb.promociones
CREATE TABLE IF NOT EXISTS `promociones` (
  `id_promocion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_promocion` varchar(150) NOT NULL COMMENT 'Nombre descriptivo de la promoción (ej: Combo Cumpleañero, 2x1 en Cervezas)',
  `descripcion_promocion` text DEFAULT NULL COMMENT 'Descripción detallada de la promoción, condiciones, etc.',
  `codigo_promocion` varchar(50) DEFAULT NULL COMMENT 'Código corto opcional para identificar o aplicar la promoción',
  `precio_promocion` decimal(10,2) NOT NULL COMMENT 'Precio final de la promoción',
  `fecha_inicio` datetime NOT NULL COMMENT 'Fecha y hora de inicio de validez de la promoción',
  `fecha_fin` datetime NOT NULL COMMENT 'Fecha y hora de fin de validez de la promoción',
  `estado_promocion` enum('activa','inactiva','agotada') NOT NULL DEFAULT 'activa' COMMENT 'Estado actual de la promoción',
  `imagen_url_promocion` varchar(255) DEFAULT NULL COMMENT 'URL de la imagen representativa de la promoción',
  `dias_aplicables` set('LUN','MAR','MIE','JUE','VIE','SAB','DOM') DEFAULT NULL COMMENT 'Días de la semana en que aplica la promoción (NULL si aplica todos los días dentro del rango de fechas)',
  `stock_promocion` int(10) unsigned DEFAULT NULL COMMENT 'Cantidad limitada de promociones disponibles (NULL si no hay límite específico de stock para la promoción en sí)',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_promocion`),
  UNIQUE KEY `nombre_promocion` (`nombre_promocion`),
  UNIQUE KEY `codigo_promocion` (`codigo_promocion`),
  KEY `idx_promocion_estado` (`estado_promocion`),
  KEY `idx_promocion_fechas` (`fecha_inicio`,`fecha_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para almacenar las promociones ofrecidas';

-- Dumping data for table karaokedb.promociones: ~2 rows (approximately)
INSERT INTO `promociones` (`id_promocion`, `nombre_promocion`, `descripcion_promocion`, `codigo_promocion`, `precio_promocion`, `fecha_inicio`, `fecha_fin`, `estado_promocion`, `imagen_url_promocion`, `dias_aplicables`, `stock_promocion`, `fecha_creacion`, `fecha_actualizacion`) VALUES
	(1, 'Combo Piqueos', '2 alitas + 1 porción papas', 'CMPQ2025', 50.00, '2025-05-26 00:00:00', '2025-06-30 23:59:59', 'activa', NULL, 'LUN,MAR,MIE,JUE,VIE', NULL, '2025-05-27 04:10:49', '2025-05-27 04:10:49'),
	(2, '2x1 Cervezas', '2 cervezas al precio de 1', '2X1CV2025', 12.00, '2025-05-26 00:00:00', '2025-06-15 23:59:59', 'activa', NULL, NULL, NULL, '2025-05-27 04:10:49', '2025-05-27 04:10:49');

-- Dumping structure for table karaokedb.promocion_productos
CREATE TABLE IF NOT EXISTS `promocion_productos` (
  `id_promocion_producto` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_promocion` int(10) unsigned NOT NULL COMMENT 'FK a la promoción',
  `id_producto` int(10) unsigned NOT NULL COMMENT 'FK al producto incluido en la promoción',
  `cantidad_producto_en_promo` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Cantidad de este producto que se incluye en la promoción (ej: 2 para un 2x1)',
  `precio_original_referencia` decimal(10,2) DEFAULT NULL COMMENT 'Precio unitario del producto al momento de añadirlo a la promo (solo referencia, el precio de venta es el de la promoción)',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_promocion_producto`),
  UNIQUE KEY `uq_promocion_producto_unico` (`id_promocion`,`id_producto`),
  KEY `fk_promocionproducto_promocion` (`id_promocion`),
  KEY `fk_promocionproducto_producto` (`id_producto`),
  CONSTRAINT `fk_promocionproducto_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promocionproducto_promocion` FOREIGN KEY (`id_promocion`) REFERENCES `promociones` (`id_promocion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de enlace entre promociones y los productos que las componen';

-- Dumping data for table karaokedb.promocion_productos: ~3 rows (approximately)
INSERT INTO `promocion_productos` (`id_promocion_producto`, `id_promocion`, `id_producto`, `cantidad_producto_en_promo`, `precio_original_referencia`, `fecha_creacion`) VALUES
	(1, 1, 1, 2, 25.00, '2025-05-27 04:10:49'),
	(2, 1, 6, 1, 10.00, '2025-05-27 04:10:49'),
	(3, 2, 69, 2, 12.00, '2025-05-27 04:10:49');

-- Dumping structure for table karaokedb.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo_usuario` varchar(14) NOT NULL COMMENT 'ID personalizado formato YYMMDDHHMMSS + opcional secuencial para unicidad',
  `usuario` varchar(50) NOT NULL COMMENT 'Nombre de usuario para login',
  `contrasena` varchar(255) NOT NULL COMMENT 'Contraseña hasheada',
  `nombres` varchar(150) NOT NULL COMMENT 'Nombres completos del usuario',
  `rol` enum('administrador','mesero','bartender','cocinero') NOT NULL COMMENT 'Rol del usuario en el sistema',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'TRUE: activo, FALSE: inactivo',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `codigo_usuario` (`codigo_usuario`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `idx_usuario_rol` (`rol`),
  KEY `idx_usuario_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de usuarios del sistema';

-- Dumping data for table karaokedb.usuarios: ~6 rows (approximately)
INSERT INTO `usuarios` (`id_usuario`, `codigo_usuario`, `usuario`, `contrasena`, `nombres`, `rol`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
	(1, '250526184928', 'admin01', '$2y$12$Cn/6bXP6/9RbetNIeEVqMOqr0Z7k6Gx359aWGZi2aaCIAa71ou91m', 'Carlos Gómez', 'administrador', 1, '2025-05-26 23:49:28', '2025-05-26 23:50:01'),
	(2, '25052618492801', 'mesero01', '$2y$12$Cn/6bXP6/9RbetNIeEVqMOqr0Z7k6Gx359aWGZi2aaCIAa71ou91m', 'Lucía Rodríguez', 'mesero', 1, '2025-05-26 23:49:28', '2025-05-26 23:50:02'),
	(3, '25052618492802', 'barman01', '$2y$12$Cn/6bXP6/9RbetNIeEVqMOqr0Z7k6Gx359aWGZi2aaCIAa71ou91m', 'Esteban Torres', 'bartender', 1, '2025-05-26 23:49:28', '2025-05-26 23:50:02'),
	(4, '25052618492803', 'cocinero01', '$2y$12$Cn/6bXP6/9RbetNIeEVqMOqr0Z7k6Gx359aWGZi2aaCIAa71ou91m', 'María Fernanda Ruiz', 'cocinero', 1, '2025-05-26 23:49:28', '2025-05-26 23:50:03'),
	(6, '25052618492805', 'mesero02', '$2y$12$Cn/6bXP6/9RbetNIeEVqMOqr0Z7k6Gx359aWGZi2aaCIAa71ou91m', 'Pedro Jiménez', 'mesero', 1, '2025-05-26 23:49:28', '2025-05-26 23:50:04'),
	(9, '25052618492808', 'mesero03', '$2y$12$Cn/6bXP6/9RbetNIeEVqMOqr0Z7k6Gx359aWGZi2aaCIAa71ou91m', 'Valentina Rivas', 'mesero', 1, '2025-05-26 23:49:28', '2025-05-26 23:50:07');

-- Dumping structure for trigger karaokedb.trg_before_insert_usuarios_codigo
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_before_insert_usuarios_codigo` BEFORE INSERT ON `Usuarios` FOR EACH ROW BEGIN
    DECLARE base_code VARCHAR(12);
    DECLARE final_code VARCHAR(14);
    DECLARE counter INT DEFAULT 0;
    SET base_code = DATE_FORMAT(NOW(), '%y%m%d%H%i%s');
    SET final_code = base_code;
    -- Bucle para asegurar unicidad en caso de inserciones en el mismo segundo.
    -- Se añade un contador de dos dígitos si hay colisión.
    WHILE EXISTS(SELECT 1 FROM Usuarios WHERE codigo_usuario = final_code) DO
        SET counter = counter + 1;
        SET final_code = CONCAT(base_code, LPAD(counter, 2, '0'));
        IF counter > 99 THEN -- Prevenir bucle infinito en caso extremo
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pudo generar un codigo_usuario único.';
        END IF;
    END WHILE;
    SET NEW.codigo_usuario = final_code;
    -- Se recomienda hashear la contraseña en la capa de aplicación antes de guardarla.
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
