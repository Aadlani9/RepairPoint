-- ===================================================
-- RepairPoint - Migración: Estado "Cancelada" en Facturas
-- Añade el valor 'canceled' al ENUM invoice_status
-- y añade columna canceled_at + canceled_reason
-- ===================================================

-- Ampliar el ENUM para incluir 'canceled'
ALTER TABLE invoices
    MODIFY COLUMN invoice_status ENUM('quote','invoice','canceled')
        NOT NULL DEFAULT 'invoice'
        COMMENT 'Tipo documento: presupuesto, factura o cancelada';

-- Añadir columna de fecha de cancelación si no existe
SET @dbname = DATABASE();
SET @q1 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
       AND TABLE_NAME   = 'invoices'
       AND COLUMN_NAME  = 'canceled_at') = 0,
    'ALTER TABLE invoices ADD COLUMN canceled_at DATETIME DEFAULT NULL COMMENT \'Fecha y hora de cancelación\' AFTER invoice_status',
    'SELECT 1 -- canceled_at ya existe'
);
PREPARE stmt1 FROM @q1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Añadir columna de motivo de cancelación si no existe
SET @q2 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
       AND TABLE_NAME   = 'invoices'
       AND COLUMN_NAME  = 'canceled_reason') = 0,
    'ALTER TABLE invoices ADD COLUMN canceled_reason VARCHAR(500) DEFAULT NULL COMMENT \'Motivo de cancelación\' AFTER canceled_at',
    'SELECT 1 -- canceled_reason ya existe'
);
PREPARE stmt2 FROM @q2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ===================================================
-- Fin de la migración
-- ===================================================
