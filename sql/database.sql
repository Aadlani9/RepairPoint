-- ===================================================
-- RepairPoint - Sistema de Gestión de Talleres de Reparación de Móviles
-- Database Schema - مصحح
-- ===================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS repairpoint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE repairpoint;

-- ===================================================
-- Tabla de Talleres (Shops)
-- ===================================================
CREATE TABLE shops (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(150) NOT NULL COMMENT 'Nombre del taller',
                       email VARCHAR(150) UNIQUE COMMENT 'Email del taller',
                       phone1 VARCHAR(20) NOT NULL COMMENT 'Teléfono principal',
                       phone2 VARCHAR(20) COMMENT 'Teléfono adicional',
                       address TEXT COMMENT 'Dirección completa',
                       website VARCHAR(255) COMMENT 'Sitio web del taller',
                       logo VARCHAR(255) COMMENT 'Logo del taller',
                       city VARCHAR(100) COMMENT 'Ciudad',
                       country VARCHAR(100) DEFAULT 'España' COMMENT 'País',
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
                       notes TEXT COMMENT 'Notas adicionales',
                       status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Estado del taller'
);

-- ===================================================
-- Tabla de Usuarios (Users)
-- ===================================================
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(100) NOT NULL COMMENT 'Nombre completo del usuario',
                       email VARCHAR(150) NOT NULL UNIQUE COMMENT 'Email',
                       phone VARCHAR(20) COMMENT 'Teléfono',
                       password VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada',
                       role ENUM('admin', 'staff') DEFAULT 'staff' COMMENT 'Rol del usuario',
                       shop_id INT NOT NULL COMMENT 'ID del taller',
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación',
                       last_login DATETIME COMMENT 'Último inicio de sesión',
                       status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Estado del usuario',
                       FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- ===================================================
-- Tabla de Marcas (Brands)
-- ===================================================
CREATE TABLE brands (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre de la marca',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_brand_name (name)
);

-- ===================================================
-- Tabla de Modelos (Models)
-- ===================================================
CREATE TABLE models (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        brand_id INT NOT NULL COMMENT 'ID de la marca',
                        name VARCHAR(100) NOT NULL COMMENT 'Nombre del modelo',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
                        INDEX idx_brand_model (brand_id, name),
                        UNIQUE KEY unique_brand_model (brand_id, name)
);

-- ===================================================
-- Tabla de Problemas Comunes (Common Issues)
-- ===================================================
CREATE TABLE common_issues (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               issue_text VARCHAR(255) NOT NULL COMMENT 'Texto del problema',
                               category VARCHAR(100) COMMENT 'Categoría del problema',
                               created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ===================================================
-- Tabla de Reparaciones (Repairs)
-- ===================================================
CREATE TABLE repairs (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         reference VARCHAR(50) NOT NULL UNIQUE COMMENT 'Número de referencia único',
                         customer_name VARCHAR(100) NOT NULL COMMENT 'Nombre del cliente',
                         customer_phone VARCHAR(20) NOT NULL COMMENT 'Teléfono del cliente',
                         brand_id INT NOT NULL COMMENT 'ID de la marca',
                         model_id INT NOT NULL COMMENT 'ID del modelo',
                         issue_description TEXT NOT NULL COMMENT 'Descripción del problema',
                         estimated_cost DECIMAL(10,2) COMMENT 'Coste estimado',
                         actual_cost DECIMAL(10,2) COMMENT 'Coste real',
                         status ENUM('pending', 'in_progress', 'completed', 'delivered') DEFAULT 'pending' COMMENT 'Estado de la reparación',
                         priority ENUM('low', 'medium', 'high') DEFAULT 'medium' COMMENT 'Prioridad de la reparación',
                         received_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de recepción',
                         estimated_completion DATETIME COMMENT 'Fecha estimada de finalización',
                         completed_at DATETIME COMMENT 'Fecha de finalización',
                         delivered_at DATETIME COMMENT 'Fecha de entrega',
                         created_by INT NOT NULL COMMENT 'ID del usuario que registró',
                         delivered_by VARCHAR(100) COMMENT 'Nombre del empleado que entregó',
                         shop_id INT NOT NULL COMMENT 'ID del taller',
                         notes TEXT COMMENT 'Notas adicionales',
                         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                         updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                         FOREIGN KEY (brand_id) REFERENCES brands(id),
                         FOREIGN KEY (model_id) REFERENCES models(id),
                         FOREIGN KEY (created_by) REFERENCES users(id),
                         FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,

                         INDEX idx_reference (reference),
                         INDEX idx_customer_phone (customer_phone),
                         INDEX idx_customer_name (customer_name),
                         INDEX idx_status (status),
                         INDEX idx_shop_id (shop_id),
                         INDEX idx_received_date (received_at)
);

-- ===================================================
-- Datos iniciales para pruebas - مصحح
-- ===================================================

-- Insertar taller de prueba
INSERT INTO shops (name, email, phone1, address, city, country) VALUES
    ('aadlani ', 'info@tecnofix.es', '+34 666 123 456', 'Calle Mayor 123, Salamanca', 'Salamanca', 'España');

-- Insertar usuario administrador de prueba - HASH مصحح
INSERT INTO users (name, email, password, role, shop_id) VALUES
    ('tohami', 'admin@tecnofix.es', '$2y$10$3ouELpU5HK9H4hKOlJvYa.tN1UQ5PX.GJ9XzGJ3T4RnKX8ZgKJ9c6', 'admin', 1);
-- كلمة المرور: password123

-- Insertar empleado de prueba - HASH مصحح
INSERT INTO users (name, email, password, role, shop_id) VALUES
    ('mohammed', 'empleado@tecnofix.es', '$2y$10$3ouELpU5HK9H4hKOlJvYa.tN1UQ5PX.GJ9XzGJ3T4RnKX8ZgKJ9c6', 'staff', 1);
-- كلمة المرور: password123

-- Insertar marcas comunes
INSERT INTO brands (name) VALUES
                              ('Apple'),
                              ('Samsung'),
                              ('Huawei'),
                              ('Xiaomi'),
                              ('Oppo'),
                              ('OnePlus'),
                              ('Google'),
                              ('Sony'),
                              ('LG'),
                              ('Nokia');

-- Insertar modelos de iPhone
INSERT INTO models (brand_id, name) VALUES
                                        (1, 'iPhone 15 Pro Max'),
                                        (1, 'iPhone 15 Pro'),
                                        (1, 'iPhone 15'),
                                        (1, 'iPhone 14 Pro Max'),
                                        (1, 'iPhone 14 Pro'),
                                        (1, 'iPhone 14'),
                                        (1, 'iPhone 13'),
                                        (1, 'iPhone 12');

-- Insertar modelos de Samsung
INSERT INTO models (brand_id, name) VALUES
                                        (2, 'Galaxy S24 Ultra'),
                                        (2, 'Galaxy S24+'),
                                        (2, 'Galaxy S24'),
                                        (2, 'Galaxy S23'),
                                        (2, 'Galaxy A54'),
                                        (2, 'Galaxy A34'),
                                        (2, 'Galaxy Note 20');

-- Insertar modelos de Huawei
INSERT INTO models (brand_id, name) VALUES
                                        (3, 'P60 Pro'),
                                        (3, 'Mate 50'),
                                        (3, 'Nova 11'),
                                        (3, 'Y9 Prime');

-- Insertar problemas comunes
INSERT INTO common_issues (issue_text, category) VALUES
                                                     ('Pantalla rota', 'Pantalla'),
                                                     ('Batería se agota rápido', 'Batería'),
                                                     ('No carga', 'Carga'),
                                                     ('Problema de sonido', 'Audio'),
                                                     ('Cámara no funciona', 'Cámara'),
                                                     ('Botón de encendido no funciona', 'Botones'),
                                                     ('Problema con WiFi', 'Conectividad'),
                                                     ('Dispositivo lento', 'Rendimiento'),
                                                     ('Problema con Bluetooth', 'Conectividad'),
                                                     ('El dispositivo no enciende', 'Sistema');

-- ===================================================
-- Crear índices adicionales para rendimiento
-- ===================================================
CREATE INDEX idx_repairs_status_shop ON repairs(status, shop_id);
CREATE INDEX idx_repairs_date_shop ON repairs(received_at, shop_id);
CREATE FULLTEXT INDEX idx_customer_search ON repairs(customer_name, customer_phone);

-- ===================================================
-- Crear vistas útiles
-- ===================================================

-- Vista de reparaciones activas con detalles completos
CREATE VIEW active_repairs AS
SELECT
    r.id,
    r.reference,
    r.customer_name,
    r.customer_phone,
    b.name as brand_name,
    m.name as model_name,
    r.issue_description,
    r.status,
    r.priority,
    r.received_at,
    r.estimated_completion,
    u.name as created_by_name,
    s.name as shop_name
FROM repairs r
         JOIN brands b ON r.brand_id = b.id
         JOIN models m ON r.model_id = m.id
         JOIN users u ON r.created_by = u.id
         JOIN shops s ON r.shop_id = s.id
WHERE r.status IN ('pending', 'in_progress', 'completed');

-- Vista de reparaciones entregadas
CREATE VIEW delivered_repairs AS
SELECT
    r.id,
    r.reference,
    r.customer_name,
    r.customer_phone,
    b.name as brand_name,
    m.name as model_name,
    r.issue_description,
    r.delivered_at,
    r.delivered_by,
    u.name as created_by_name,
    s.name as shop_name
FROM repairs r
         JOIN brands b ON r.brand_id = b.id
         JOIN models m ON r.model_id = m.id
         JOIN users u ON r.created_by = u.id
         JOIN shops s ON r.shop_id = s.id
WHERE r.status = 'delivered';

-- ===================================================
-- Crear triggers para actualización automática
-- ===================================================

DELIMITER //

-- Trigger para actualizar último inicio de sesión
CREATE TRIGGER update_last_login
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    IF NEW.last_login != OLD.last_login THEN
    UPDATE users SET last_login = NOW() WHERE id = NEW.id;
END IF;
END//

-- Trigger para generar referencia automática
CREATE TRIGGER generate_reference
    BEFORE INSERT ON repairs
    FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SELECT AUTO_INCREMENT INTO next_id
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repairs';

    SET NEW.reference = CONCAT(next_id, DATE_FORMAT(NOW(), '%d%m%Y'));
END//

DELIMITER ;