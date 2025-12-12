-- ===================================================
-- RepairPoint - Sistema de Facturación
-- Migration para el sistema de facturación de clientes
-- ===================================================

USE repairpoint;

-- ===================================================
-- Tabla de Clientes (Customers)
-- ===================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL COMMENT 'Nombre completo del cliente',
    phone VARCHAR(20) NOT NULL COMMENT 'Teléfono del cliente',
    email VARCHAR(150) COMMENT 'Email del cliente (opcional)',
    address TEXT COMMENT 'Dirección del cliente',

    -- Documento de identidad
    id_type ENUM('dni', 'nie', 'passport') NOT NULL DEFAULT 'dni' COMMENT 'Tipo de documento',
    id_number VARCHAR(50) NOT NULL COMMENT 'Número de documento',

    -- Metadata
    shop_id INT NOT NULL COMMENT 'ID del taller',
    created_by INT NOT NULL COMMENT 'ID del usuario que creó el cliente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de actualización',
    notes TEXT COMMENT 'Notas adicionales',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Estado del cliente',

    -- Relaciones
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),

    -- Índices
    INDEX idx_customer_phone (phone),
    INDEX idx_customer_name (full_name),
    INDEX idx_customer_id_number (id_number),
    INDEX idx_shop_id (shop_id),
    UNIQUE KEY unique_phone_shop (phone, shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- Tabla de Facturas (Invoices)
-- ===================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Número de factura único',
    customer_id INT NOT NULL COMMENT 'ID del cliente',

    -- Fechas
    invoice_date DATE NOT NULL COMMENT 'Fecha de la factura',
    due_date DATE COMMENT 'Fecha de vencimiento',

    -- Montos
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal sin IVA',
    iva_rate DECIMAL(5,2) NOT NULL DEFAULT 21.00 COMMENT 'Tasa de IVA (%)',
    iva_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto del IVA',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total con IVA',

    -- Estado de pago
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending' COMMENT 'Estado del pago',
    paid_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Monto pagado',
    payment_date DATE COMMENT 'Fecha de pago',
    payment_method VARCHAR(50) COMMENT 'Método de pago (efectivo, tarjeta, transferencia)',

    -- Metadata
    shop_id INT NOT NULL COMMENT 'ID del taller',
    created_by INT NOT NULL COMMENT 'ID del usuario que creó la factura',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de actualización',
    notes TEXT COMMENT 'Notas adicionales',

    -- Relaciones
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),

    -- Índices
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_payment_status (payment_status),
    INDEX idx_shop_id (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- Tabla de Items de Factura (Invoice Items)
-- ===================================================
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL COMMENT 'ID de la factura',

    -- Descripción del producto/servicio
    item_type ENUM('service', 'product', 'spare_part') NOT NULL DEFAULT 'service' COMMENT 'Tipo de item',
    description TEXT NOT NULL COMMENT 'Descripción del producto/servicio',
    imei VARCHAR(50) COMMENT 'IMEI del dispositivo (para reparaciones)',

    -- Cantidades y precios
    quantity INT NOT NULL DEFAULT 1 COMMENT 'Cantidad',
    unit_price DECIMAL(10,2) NOT NULL COMMENT 'Precio unitario',
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Subtotal (cantidad * precio)',

    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',

    -- Relaciones
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,

    -- Índices
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_imei (imei)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- Vista de facturas con información completa
-- ===================================================
CREATE OR REPLACE VIEW invoice_details AS
SELECT
    i.id,
    i.invoice_number,
    i.invoice_date,
    i.due_date,
    c.full_name as customer_name,
    c.phone as customer_phone,
    c.email as customer_email,
    c.id_type,
    c.id_number,
    c.address as customer_address,
    i.subtotal,
    i.iva_rate,
    i.iva_amount,
    i.total,
    i.payment_status,
    i.paid_amount,
    i.payment_date,
    i.payment_method,
    s.name as shop_name,
    s.phone1 as shop_phone,
    s.email as shop_email,
    s.address as shop_address,
    s.logo as shop_logo,
    u.name as created_by_name,
    i.created_at,
    i.notes
FROM invoices i
JOIN customers c ON i.customer_id = c.id
JOIN shops s ON i.shop_id = s.id
JOIN users u ON i.created_by = u.id;

-- ===================================================
-- Trigger para generar número de factura automáticamente
-- ===================================================
DELIMITER //

DROP TRIGGER IF EXISTS generate_invoice_number//
CREATE TRIGGER generate_invoice_number
BEFORE INSERT ON invoices
FOR EACH ROW
BEGIN
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
END//

-- ===================================================
-- Trigger para calcular montos de factura automáticamente
-- ===================================================
DROP TRIGGER IF EXISTS calculate_invoice_totals_insert//
CREATE TRIGGER calculate_invoice_totals_insert
AFTER INSERT ON invoice_items
FOR EACH ROW
BEGIN
    UPDATE invoices
    SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id),
        iva_amount = subtotal * (iva_rate / 100),
        total = subtotal + iva_amount
    WHERE id = NEW.invoice_id;
END//

DROP TRIGGER IF EXISTS calculate_invoice_totals_update//
CREATE TRIGGER calculate_invoice_totals_update
AFTER UPDATE ON invoice_items
FOR EACH ROW
BEGIN
    UPDATE invoices
    SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id),
        iva_amount = subtotal * (iva_rate / 100),
        total = subtotal + iva_amount
    WHERE id = NEW.invoice_id;
END//

DROP TRIGGER IF EXISTS calculate_invoice_totals_delete//
CREATE TRIGGER calculate_invoice_totals_delete
AFTER DELETE ON invoice_items
FOR EACH ROW
BEGIN
    UPDATE invoices
    SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM invoice_items WHERE invoice_id = OLD.invoice_id),
        iva_amount = subtotal * (iva_rate / 100),
        total = subtotal + iva_amount
    WHERE id = OLD.invoice_id;
END//

DELIMITER ;

-- ===================================================
-- Índices adicionales para rendimiento
-- ===================================================
CREATE INDEX idx_invoices_customer_status ON invoices(customer_id, payment_status);
CREATE INDEX idx_invoices_date_shop ON invoices(invoice_date, shop_id);

-- ===================================================
-- Datos de ejemplo (opcional - comentar en producción)
-- ===================================================
/*
-- Insertar cliente de ejemplo
INSERT INTO customers (full_name, phone, email, id_type, id_number, shop_id, created_by) VALUES
('Juan García Pérez', '+34 666 777 888', 'juan.garcia@example.com', 'dni', '12345678A', 1, 1);

-- Insertar factura de ejemplo
INSERT INTO invoices (customer_id, invoice_date, shop_id, created_by) VALUES
(1, CURDATE(), 1, 1);

-- Insertar items de factura de ejemplo
INSERT INTO invoice_items (invoice_id, item_type, description, imei, quantity, unit_price, subtotal) VALUES
(1, 'service', 'Reparación de pantalla iPhone 13', '123456789012345', 1, 80.00, 80.00),
(1, 'spare_part', 'Pantalla iPhone 13 OLED', NULL, 1, 120.00, 120.00);
*/

-- ===================================================
-- Fin de la migración
-- ===================================================
