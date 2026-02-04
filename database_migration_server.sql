-- =====================================================
-- Migration Script: تحديث قاعدة بيانات السيرفر
-- من: النسخة القديمة
-- إلى: النسخة المطابقة لـ Local
-- التاريخ: 2026-02-04
-- =====================================================
-- ⚠️ هام: قم بعمل نسخة احتياطية قبل التنفيذ!
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- =====================================================
-- 1. تحديث جدول models - إضافة عمود model_reference
-- =====================================================
ALTER TABLE `models`
ADD COLUMN IF NOT EXISTS `model_reference` varchar(50) DEFAULT NULL COMMENT 'معرّف الموديل التجاري (مثل: V2244, SM-S928)' AFTER `name`;

-- إضافة index للعمود الجديد
CREATE INDEX IF NOT EXISTS `idx_model_reference` ON `models` (`model_reference`);

-- =====================================================
-- 2. تحديث جدول repairs - إضافة الأعمدة الناقصة
-- =====================================================
ALTER TABLE `repairs`
ADD COLUMN IF NOT EXISTS `device_input_type` enum('list','search','otro') DEFAULT 'list' COMMENT 'طريقة إدخال الجهاز' AFTER `model_id`,
ADD COLUMN IF NOT EXISTS `custom_brand` varchar(100) DEFAULT NULL COMMENT 'ماركة مخصصة للأجهزة غير الموجودة' AFTER `device_input_type`,
ADD COLUMN IF NOT EXISTS `custom_model` varchar(100) DEFAULT NULL COMMENT 'موديل مخصص للأجهزة غير الموجودة' AFTER `custom_brand`,
ADD COLUMN IF NOT EXISTS `reopen_delivered_at` datetime DEFAULT NULL COMMENT 'تاريخ التسليم بعد إعادة الفتح الأخيرة' AFTER `is_reopened`,
ADD COLUMN IF NOT EXISTS `reopen_warranty_days` int(11) DEFAULT 30 COMMENT 'أيام الضمان الجديدة بعد إعادة الفتح' AFTER `reopen_delivered_at`,
ADD COLUMN IF NOT EXISTS `reopen_completed_at` datetime DEFAULT NULL COMMENT 'تاريخ الإنجاز بعد إعادة الفتح' AFTER `reopen_warranty_days`,
ADD COLUMN IF NOT EXISTS `original_delivered_at` datetime DEFAULT NULL COMMENT 'نسخة من تاريخ التسليم الأصلي' AFTER `reopen_completed_at`,
ADD COLUMN IF NOT EXISTS `reopen_count` int(11) DEFAULT 0 COMMENT 'عدد مرات إعادة الفتح' AFTER `original_delivered_at`,
ADD COLUMN IF NOT EXISTS `last_reopen_by` int(11) DEFAULT NULL COMMENT 'المستخدم الذي قام بآخر إعادة فتح' AFTER `reopen_count`;

-- إضافة indexes للأعمدة الجديدة
CREATE INDEX IF NOT EXISTS `idx_custom_device` ON `repairs` (`custom_brand`, `custom_model`);
CREATE INDEX IF NOT EXISTS `idx_reopen_delivered` ON `repairs` (`reopen_delivered_at`);
CREATE INDEX IF NOT EXISTS `idx_reopen_count` ON `repairs` (`reopen_count`);
CREATE INDEX IF NOT EXISTS `idx_original_delivered` ON `repairs` (`original_delivered_at`);

-- تحديث البيانات الموجودة - نسخ delivered_at إلى original_delivered_at للإصلاحات المسلمة
UPDATE `repairs`
SET `original_delivered_at` = `delivered_at`
WHERE `delivered_at` IS NOT NULL AND `original_delivered_at` IS NULL;

-- =====================================================
-- 3. إضافة brand "Desconocido" إذا لم يكن موجوداً
-- =====================================================
INSERT IGNORE INTO `brands` (`id`, `name`, `created_at`)
VALUES (17, 'Desconocido', NOW());

-- =====================================================
-- 4. إضافة model "Dispositivo Personalizado" إذا لم يكن موجوداً
-- =====================================================
INSERT IGNORE INTO `models` (`id`, `brand_id`, `name`, `model_reference`, `created_at`)
VALUES (471, 17, 'Dispositivo Personalizado', NULL, NOW());

-- =====================================================
-- 5. إنشاء جدول customers
-- =====================================================
CREATE TABLE IF NOT EXISTS `customers` (
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
-- 6. إنشاء جدول invoices
-- =====================================================
CREATE TABLE IF NOT EXISTS `invoices` (
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
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'Método de pago (efectivo, tarjeta, transferencia)',
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
-- 7. إنشاء Trigger لتوليد رقم الفاتورة تلقائياً
-- =====================================================
DROP TRIGGER IF EXISTS `generate_invoice_number`;
DELIMITER $$
CREATE TRIGGER `generate_invoice_number` BEFORE INSERT ON `invoices` FOR EACH ROW BEGIN
    DECLARE next_number INT;
    DECLARE year_str VARCHAR(4);

    -- Obtener el año actual
    SET year_str = DATE_FORMAT(NOW(), '%Y');

    -- Obtener el siguiente número de factura para el año actual
    SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM invoices
    WHERE invoice_number LIKE CONCAT('INV-', year_str, '-%');

    -- Generar el número de factura: INV-YYYY-NNNN
    SET NEW.invoice_number = CONCAT('INV-', year_str, '-', LPAD(next_number, 4, '0'));
END$$
DELIMITER ;

-- =====================================================
-- 8. إنشاء جدول invoice_items
-- =====================================================
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL COMMENT 'ID de la factura',
  `item_type` enum('service','product','spare_part') NOT NULL DEFAULT 'service' COMMENT 'Tipo de item',
  `description` text NOT NULL COMMENT 'Descripción del producto/servicio',
  `imei` varchar(50) DEFAULT NULL COMMENT 'IMEI del dispositivo (para reparaciones)',
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
-- 9. إنشاء Triggers لحساب مجاميع الفاتورة
-- =====================================================
DROP TRIGGER IF EXISTS `calculate_invoice_totals_insert`;
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

DROP TRIGGER IF EXISTS `calculate_invoice_totals_update`;
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

DROP TRIGGER IF EXISTS `calculate_invoice_totals_delete`;
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

-- =====================================================
-- 10. إنشاء جدول repair_history
-- =====================================================
CREATE TABLE IF NOT EXISTS `repair_history` (
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
-- 11. إنشاء View: invoice_details
-- =====================================================
DROP VIEW IF EXISTS `invoice_details`;
CREATE VIEW `invoice_details` AS
SELECT
    `i`.`id` AS `id`,
    `i`.`invoice_number` AS `invoice_number`,
    `i`.`invoice_date` AS `invoice_date`,
    `i`.`due_date` AS `due_date`,
    `c`.`full_name` AS `customer_name`,
    `c`.`phone` AS `customer_phone`,
    `c`.`email` AS `customer_email`,
    `c`.`id_type` AS `id_type`,
    `c`.`id_number` AS `id_number`,
    `c`.`address` AS `customer_address`,
    `i`.`subtotal` AS `subtotal`,
    `i`.`iva_rate` AS `iva_rate`,
    `i`.`iva_amount` AS `iva_amount`,
    `i`.`total` AS `total`,
    `i`.`payment_status` AS `payment_status`,
    `i`.`paid_amount` AS `paid_amount`,
    `i`.`payment_date` AS `payment_date`,
    `i`.`payment_method` AS `payment_method`,
    `s`.`name` AS `shop_name`,
    `s`.`phone1` AS `shop_phone`,
    `s`.`email` AS `shop_email`,
    `s`.`address` AS `shop_address`,
    `s`.`logo` AS `shop_logo`,
    `u`.`name` AS `created_by_name`,
    `i`.`created_at` AS `created_at`,
    `i`.`notes` AS `notes`
FROM (((`invoices` `i`
    JOIN `customers` `c` ON(`i`.`customer_id` = `c`.`id`))
    JOIN `shops` `s` ON(`i`.`shop_id` = `s`.`id`))
    JOIN `users` `u` ON(`i`.`created_by` = `u`.`id`));

-- =====================================================
-- 12. إنشاء View: repairs_with_device_info
-- =====================================================
DROP VIEW IF EXISTS `repairs_with_device_info`;
CREATE VIEW `repairs_with_device_info` AS
SELECT
    `r`.`id` AS `id`,
    `r`.`reference` AS `reference`,
    `r`.`customer_name` AS `customer_name`,
    `r`.`customer_phone` AS `customer_phone`,
    `r`.`device_input_type` AS `device_input_type`,
    `r`.`brand_id` AS `brand_id`,
    `r`.`model_id` AS `model_id`,
    `b`.`name` AS `brand_name`,
    `m`.`name` AS `model_name`,
    `m`.`model_reference` AS `model_reference`,
    `r`.`custom_brand` AS `custom_brand`,
    `r`.`custom_model` AS `custom_model`,
    CASE
        WHEN `r`.`device_input_type` = 'otro' THEN CONCAT(COALESCE(`r`.`custom_brand`,'Desconocido'),' ',COALESCE(`r`.`custom_model`,'Desconocido'))
        ELSE CONCAT(`b`.`name`,' ',`m`.`name`, CASE WHEN `m`.`model_reference` IS NOT NULL THEN CONCAT(' (',`m`.`model_reference`,')') ELSE '' END)
    END AS `device_display`,
    `r`.`issue_description` AS `issue_description`,
    `r`.`estimated_cost` AS `estimated_cost`,
    `r`.`status` AS `status`,
    `r`.`priority` AS `priority`,
    `r`.`created_at` AS `created_at`
FROM ((`repairs` `r`
    LEFT JOIN `brands` `b` ON(`r`.`brand_id` = `b`.`id`))
    LEFT JOIN `models` `m` ON(`r`.`model_id` = `m`.`id`));

-- =====================================================
-- 13. إنشاء Stored Procedure: SearchModels
-- =====================================================
DROP PROCEDURE IF EXISTS `SearchModels`;
DELIMITER $$
CREATE PROCEDURE `SearchModels` (IN `p_search_term` VARCHAR(255), IN `p_limit` INT)
BEGIN
    -- البحث في اسم الموديل، المعرّف، واسم الماركة
    SELECT
        m.id AS model_id,
        m.name AS model_name,
        m.model_reference,
        b.id AS brand_id,
        b.name AS brand_name,
        CONCAT(
            b.name,
            ' ',
            m.name,
            CASE
                WHEN m.model_reference IS NOT NULL THEN CONCAT(' (', m.model_reference, ')')
                ELSE ''
            END
        ) AS display_name
    FROM models m
    JOIN brands b ON m.brand_id = b.id
    WHERE
        m.name LIKE CONCAT('%', p_search_term, '%')
        OR m.model_reference LIKE CONCAT('%', p_search_term, '%')
        OR b.name LIKE CONCAT('%', p_search_term, '%')
        OR CONCAT(b.name, ' ', m.name) LIKE CONCAT('%', p_search_term, '%')
    ORDER BY
        -- ترتيب حسب الأقرب للبحث
        CASE
            WHEN m.model_reference = p_search_term THEN 1
            WHEN m.model_reference LIKE CONCAT(p_search_term, '%') THEN 2
            WHEN m.name LIKE CONCAT(p_search_term, '%') THEN 3
            WHEN b.name LIKE CONCAT(p_search_term, '%') THEN 4
            ELSE 5
        END,
        b.name,
        m.name
    LIMIT p_limit;
END$$
DELIMITER ;

-- =====================================================
-- 14. إدراج سجل أولي في repair_history للإصلاحات الموجودة
-- =====================================================
INSERT INTO `repair_history` (`repair_id`, `shop_id`, `event_type`, `old_status`, `new_status`, `description`, `warranty_days`, `delivered_at`, `performed_by`, `created_at`)
SELECT
    r.id,
    r.shop_id,
    'created',
    NULL,
    r.status,
    'Reparación inicial registrada',
    r.warranty_days,
    r.delivered_at,
    COALESCE(r.created_by, 1),
    r.created_at
FROM repairs r
WHERE NOT EXISTS (
    SELECT 1 FROM repair_history rh WHERE rh.repair_id = r.id
);

-- =====================================================
-- تأكيد التغييرات
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- =====================================================
-- ✅ تم الانتهاء من Migration بنجاح!
-- =====================================================
SELECT '✅ Migration completed successfully!' AS status;
SELECT 'الجداول الجديدة: customers, invoices, invoice_items, repair_history' AS new_tables;
SELECT 'الأعمدة الجديدة في repairs: device_input_type, custom_brand, custom_model, etc.' AS new_columns;
SELECT 'الأعمدة الجديدة في models: model_reference' AS model_updates;
