-- ===================================================
-- RepairPoint - Migración: Dispositivo + Estado Factura
-- Añade columnas device e invoice_status a la tabla invoices
-- ===================================================

-- Añadir campo 'device' si no existe
SET @dbname = DATABASE();

SET @q1 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
       AND TABLE_NAME   = 'invoices'
       AND COLUMN_NAME  = 'device') = 0,
    'ALTER TABLE invoices ADD COLUMN device VARCHAR(255) DEFAULT NULL COMMENT \'Dispositivo asociado a la factura\' AFTER notes',
    'SELECT 1 -- device ya existe'
);
PREPARE stmt1 FROM @q1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Añadir campo 'invoice_status' si no existe
SET @q2 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
       AND TABLE_NAME   = 'invoices'
       AND COLUMN_NAME  = 'invoice_status') = 0,
    "ALTER TABLE invoices ADD COLUMN invoice_status ENUM('quote','invoice') NOT NULL DEFAULT 'invoice' COMMENT 'Tipo documento: presupuesto o factura' AFTER device",
    'SELECT 1 -- invoice_status ya existe'
);
PREPARE stmt2 FROM @q2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Actualizar registros existentes a 'invoice'
UPDATE invoices SET invoice_status = 'invoice' WHERE invoice_status IS NULL;

-- ===================================================
-- Fin de la migración
-- ===================================================
