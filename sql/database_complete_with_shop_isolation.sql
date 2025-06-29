-- ===================================================
-- RepairPoint - Sistema de Gestión de Talleres de Reparación de Móviles
-- Database Schema - نسخة محدثة مع Shop Isolation
-- ===================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS repairpoint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE repairpoint;

-- ===================================================
-- Tabla de Talleres (Shops) - بدون تغيير
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
                       status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Estado del taller',
                       setup_completed BOOLEAN DEFAULT FALSE COMMENT 'Si el setup inicial está completo'
);

-- ===================================================
-- Tabla de Usuarios (Users) - بدون تغيير
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
                       remember_token VARCHAR(255) COMMENT 'Token para recordar sesión',
                       reset_token VARCHAR(255) COMMENT 'Token para reset password',
                       reset_expires DATETIME COMMENT 'Expiración del token reset',
                       FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
                       INDEX idx_email (email),
                       INDEX idx_shop_id (shop_id),
                       INDEX idx_remember_token (remember_token),
                       INDEX idx_reset_token (reset_token)
);

-- ===================================================
-- Tabla de Marcas (Brands) - محدث مع shop_id
-- ===================================================
CREATE TABLE brands (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        shop_id INT NOT NULL COMMENT 'ID del taller',
                        name VARCHAR(100) NOT NULL COMMENT 'Nombre de la marca',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
                        INDEX idx_brand_name (name),
                        INDEX idx_shop_brands (shop_id, name),
                        UNIQUE KEY unique_shop_brand (shop_id, name)
);

-- ===================================================
-- Tabla de Modelos (Models) - محدث مع shop_id
-- ===================================================
CREATE TABLE models (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        shop_id INT NOT NULL COMMENT 'ID del taller',
                        brand_id INT NOT NULL COMMENT 'ID de la marca',
                        name VARCHAR(100) NOT NULL COMMENT 'Nombre del modelo',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
                        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
                        INDEX idx_shop_models (shop_id, brand_id),
                        INDEX idx_brand_model (brand_id, name),
                        UNIQUE KEY unique_shop_brand_model (shop_id, brand_id, name)
);

-- ===================================================
-- Tabla de Problemas Comunes (Common Issues) - محدث مع shop_id
-- ===================================================
CREATE TABLE common_issues (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               shop_id INT NOT NULL COMMENT 'ID del taller',
                               issue_text VARCHAR(255) NOT NULL COMMENT 'Texto del problema',
                               category VARCHAR(100) COMMENT 'Categoría del problema',
                               created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                               FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
                               INDEX idx_shop_issues (shop_id, category),
                               INDEX idx_issue_category (category)
);

-- ===================================================
-- Tabla de Reparaciones (Repairs) - بدون تغيير
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
                         INDEX idx_shop_repairs (shop_id, status),
                         INDEX idx_received_date (received_at),
                         INDEX idx_shop_date (shop_id, received_at)
);

-- ===================================================
-- Tabla de Templates por defecto (Global Data)
-- ===================================================
CREATE TABLE default_brands_template (
                                         id INT AUTO_INCREMENT PRIMARY KEY,
                                         name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre de la marca',
                                         is_active BOOLEAN DEFAULT TRUE COMMENT 'Si está activa para nuevos shops',
                                         created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE default_models_template (
                                         id INT AUTO_INCREMENT PRIMARY KEY,
                                         brand_template_id INT NOT NULL COMMENT 'ID de la marca template',
                                         name VARCHAR(100) NOT NULL COMMENT 'Nombre del modelo',
                                         is_active BOOLEAN DEFAULT TRUE COMMENT 'Si está activo para nuevos shops',
                                         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                         FOREIGN KEY (brand_template_id) REFERENCES default_brands_template(id) ON DELETE CASCADE,
                                         INDEX idx_brand_template (brand_template_id)
);

CREATE TABLE default_issues_template (
                                         id INT AUTO_INCREMENT PRIMARY KEY,
                                         category VARCHAR(100) NOT NULL COMMENT 'Categoría del problema',
                                         issue_text VARCHAR(255) NOT NULL COMMENT 'Texto del problema',
                                         is_active BOOLEAN DEFAULT TRUE COMMENT 'Si está activo para nuevos shops',
                                         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                         INDEX idx_category (category)
);

-- ===================================================
-- إدراج البيانات الافتراضية (Templates)
-- ===================================================

-- إدراج Brands Templates
INSERT INTO default_brands_template (name) VALUES
                                               ('Samsung'),
                                               ('Apple'),
                                               ('Xiaomi'),
                                               ('OPPO'),
                                               ('OnePlus'),
                                               ('Motorola'),
                                               ('Infinix'),
                                               ('Tecno'),
                                               ('Huawei');

-- إدراج Models Templates للـ Samsung
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (1, 'Galaxy S24 Ultra'),
                                                                  (1, 'Galaxy S24'),
                                                                  (1, 'Galaxy S24+'),
                                                                  (1, 'Galaxy A54 5G'),
                                                                  (1, 'Galaxy A35'),
                                                                  (1, 'Galaxy A15'),
                                                                  (1, 'Galaxy M14'),
                                                                  (1, 'Galaxy Z Fold5'),
                                                                  (1, 'Galaxy Z Flip5');

-- إدراج Models Templates للـ Apple
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (2, 'iPhone 16 Pro'),
                                                                  (2, 'iPhone 16'),
                                                                  (2, 'iPhone 15 Pro'),
                                                                  (2, 'iPhone 15'),
                                                                  (2, 'iPhone 14'),
                                                                  (2, 'iPhone 14 Pro'),
                                                                  (2, 'iPhone 13'),
                                                                  (2, 'iPhone SE (2022)');

-- إدراج Models Templates للـ Xiaomi
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (3, 'Xiaomi 14'),
                                                                  (3, 'Xiaomi 13T Pro'),
                                                                  (3, 'Xiaomi 12T'),
                                                                  (3, 'Redmi Note 13 Pro'),
                                                                  (3, 'Redmi Note 13'),
                                                                  (3, 'Redmi Note 12'),
                                                                  (3, 'Redmi 13C'),
                                                                  (3, 'POCO X6'),
                                                                  (3, 'POCO M6');

-- إدراج Models Templates للـ OPPO
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (4, 'OPPO Find X7'),
                                                                  (4, 'OPPO Reno11'),
                                                                  (4, 'OPPO A98'),
                                                                  (4, 'OPPO A78');

-- إدراج Models Templates للـ OnePlus
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (5, 'OnePlus 12'),
                                                                  (5, 'OnePlus 12R'),
                                                                  (5, 'OnePlus Nord CE 4'),
                                                                  (5, 'OnePlus Nord CE 3');

-- إدراج Models Templates للـ Motorola
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (6, 'Motorola Edge 50 Pro'),
                                                                  (6, 'Motorola G84'),
                                                                  (6, 'Motorola G73'),
                                                                  (6, 'Motorola G23'),
                                                                  (6, 'Motorola E13'),
                                                                  (6, 'Motorola G14');

-- إدراج Models Templates للـ Infinix
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (7, 'Infinix Note 13'),
                                                                  (7, 'Infinix Note 12'),
                                                                  (7, 'Infinix Zero 30'),
                                                                  (7, 'Infinix Hot 30');

-- إدراج Models Templates للـ Tecno
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (8, 'Tecno Camon 20'),
                                                                  (8, 'Tecno Spark 20'),
                                                                  (8, 'Tecno Pova 5');

-- إدراج Models Templates للـ Huawei
INSERT INTO default_models_template (brand_template_id, name) VALUES
                                                                  (9, 'Huawei Nova 11i'),
                                                                  (9, 'Huawei P60 Pro'),
                                                                  (9, 'Huawei Y9a'),
                                                                  (9, 'Huawei Y7a');

-- إدراج Common Issues Templates
INSERT INTO default_issues_template (category, issue_text) VALUES
                                                               ('Pantalla', 'Pantalla rota'),
                                                               ('Pantalla', 'Pantalla no responde al tacto'),
                                                               ('Pantalla', 'Líneas en la pantalla'),
                                                               ('Pantalla', 'Pantalla negra'),
                                                               ('Pantalla', 'Manchas en la pantalla'),
                                                               ('Batería', 'Batería se agota rápido'),
                                                               ('Batería', 'Batería no mantiene carga'),
                                                               ('Batería', 'Batería se hincha'),
                                                               ('Batería', 'Porcentaje de batería incorrecto'),
                                                               ('Carga', 'No carga'),
                                                               ('Carga', 'Carga lenta'),
                                                               ('Carga', 'Puerto de carga suelto'),
                                                               ('Carga', 'Cable de carga no funciona'),
                                                               ('Audio', 'Sin sonido'),
                                                               ('Audio', 'Micrófono no funciona'),
                                                               ('Audio', 'Altavoz distorsionado'),
                                                               ('Audio', 'Auriculares no funcionan'),
                                                               ('Cámara', 'Cámara no funciona'),
                                                               ('Cámara', 'Fotos borrosas'),
                                                               ('Cámara', 'Flash no funciona'),
                                                               ('Cámara', 'Cámara frontal no funciona'),
                                                               ('Botones', 'Botón de encendido no funciona'),
                                                               ('Botones', 'Botones de volumen no funcionan'),
                                                               ('Botones', 'Botón home no responde'),
                                                               ('Conectividad', 'Problema con WiFi'),
                                                               ('Conectividad', 'Problema con Bluetooth'),
                                                               ('Conectividad', 'Sin señal de red'),
                                                               ('Conectividad', 'GPS no funciona'),
                                                               ('Rendimiento', 'Dispositivo lento'),
                                                               ('Rendimiento', 'Se cuelga frecuentemente'),
                                                               ('Rendimiento', 'Se reinicia solo'),
                                                               ('Rendimiento', 'Aplicaciones se cierran solas'),
                                                               ('Sistema', 'El dispositivo no enciende'),
                                                               ('Sistema', 'Pantalla de la muerte'),
                                                               ('Sistema', 'Problema de software'),
                                                               ('Sistema', 'Virus o malware'),
                                                               ('Hardware', 'Golpe o caída'),
                                                               ('Hardware', 'Daño por agua'),
                                                               ('Hardware', 'Sobrecalentamiento'),
                                                               ('Hardware', 'Vibración no funciona');

-- ===================================================
-- Insertar datos de prueba (Shop y Users)
-- ===================================================

-- Insertar taller de prueba
INSERT INTO shops (name, email, phone1, address, city, country, setup_completed) VALUES
    ('TecnoFix Salamanca', 'info@tecnofix.es', '+34 666 123 456', 'Calle Mayor 123, Salamanca', 'Salamanca', 'España', TRUE);

-- Insertar usuario administrador de prueba
INSERT INTO users (name, email, password, role, shop_id) VALUES
    ('Administrador', 'admin@tecnofix.es', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
-- Contraseña: password123

-- Insertar empleado de prueba
INSERT INTO users (name, email, password, role, shop_id) VALUES
    ('Empleado Test', 'empleado@tecnofix.es', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1);
-- Contraseña: password123

-- ===================================================
-- إضافة البيانات الافتراضية للمحل التجريبي
-- ===================================================

-- نسخ Brands للمحل التجريبي
INSERT INTO brands (shop_id, name, created_at)
SELECT 1, name, NOW()
FROM default_brands_template
WHERE is_active = TRUE;

-- نسخ Models للمحل التجريبي
INSERT INTO models (shop_id, brand_id, name, created_at)
SELECT
    1,
    b.id,
    dmt.name,
    NOW()
FROM default_models_template dmt
         JOIN default_brands_template dbt ON dmt.brand_template_id = dbt.id
         JOIN brands b ON b.name = dbt.name AND b.shop_id = 1
WHERE dmt.is_active = TRUE AND dbt.is_active = TRUE;

-- نسخ Common Issues للمحل التجريبي
INSERT INTO common_issues (shop_id, category, issue_text, created_at)
SELECT 1, category, issue_text, NOW()
FROM default_issues_template
WHERE is_active = TRUE;

-- ===================================================
-- إنشاء Indexes إضافية للأداء
-- ===================================================
CREATE INDEX idx_repairs_shop_status ON repairs(shop_id, status);
CREATE INDEX idx_repairs_shop_date ON repairs(shop_id, received_at);
CREATE INDEX idx_brands_shop_name ON brands(shop_id, name);
CREATE INDEX idx_models_shop_brand ON models(shop_id, brand_id);
CREATE INDEX idx_issues_shop_category ON common_issues(shop_id, category);

-- ===================================================
-- إنشاء Views محدثة مع Shop Isolation
-- ===================================================

-- Vista de reparaciones activas مع Shop Isolation
CREATE VIEW active_repairs_view AS
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
    s.name as shop_name,
    r.shop_id
FROM repairs r
         JOIN brands b ON r.brand_id = b.id AND r.shop_id = b.shop_id
         JOIN models m ON r.model_id = m.id AND r.shop_id = m.shop_id
         JOIN users u ON r.created_by = u.id AND r.shop_id = u.shop_id
         JOIN shops s ON r.shop_id = s.id
WHERE r.status IN ('pending', 'in_progress', 'completed');

-- Vista de reparaciones entregadas مع Shop Isolation
CREATE VIEW delivered_repairs_view AS
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
    s.name as shop_name,
    r.shop_id
FROM repairs r
         JOIN brands b ON r.brand_id = b.id AND r.shop_id = b.shop_id
         JOIN models m ON r.model_id = m.id AND r.shop_id = m.shop_id
         JOIN users u ON r.created_by = u.id AND r.shop_id = u.shop_id
         JOIN shops s ON r.shop_id = s.id
WHERE r.status = 'delivered';

-- ===================================================
-- إنشاء Triggers محدثة
-- ===================================================

DELIMITER //

-- Trigger للتحقق من Shop Isolation عند إدراج Repair
CREATE TRIGGER check_repair_shop_isolation
    BEFORE INSERT ON repairs
    FOR EACH ROW
BEGIN
    DECLARE brand_shop_id INT;
    DECLARE model_shop_id INT;
    DECLARE user_shop_id INT;

    -- التحقق من أن Brand يخص نفس الـ Shop
    SELECT shop_id INTO brand_shop_id FROM brands WHERE id = NEW.brand_id;
    IF brand_shop_id != NEW.shop_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Brand must belong to the same shop';
END IF;

-- التحقق من أن Model يخص نفس الـ Shop
SELECT shop_id INTO model_shop_id FROM models WHERE id = NEW.model_id;
IF model_shop_id != NEW.shop_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Model must belong to the same shop';
END IF;

    -- التحقق من أن User يخص نفس الـ Shop
SELECT shop_id INTO user_shop_id FROM users WHERE id = NEW.created_by;
IF user_shop_id != NEW.shop_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User must belong to the same shop';
END IF;
END//

-- Trigger لتوليد Reference تلقائياً
CREATE TRIGGER generate_repair_reference
    BEFORE INSERT ON repairs
    FOR EACH ROW
BEGIN
    DECLARE next_number INT;

    -- الحصول على أعلى رقم للمحل
    SELECT COALESCE(MAX(CAST(SUBSTRING(reference, 1, LOCATE('-', reference) - 1) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM repairs
    WHERE shop_id = NEW.shop_id;

    -- توليد Reference بصيغة: NUMBER-SHOPID-DDMMYY
    SET NEW.reference = CONCAT(
        LPAD(next_number, 4, '0'),
        '-',
        NEW.shop_id,
        '-',
        DATE_FORMAT(NOW(), '%d%m%y')
    );
END//

DELIMITER ;

-- ===================================================
-- إنشاء Stored Procedures للـ Shop Setup
-- ===================================================

DELIMITER //

-- Procedure لإعداد محل جديد بالبيانات الافتراضية
CREATE PROCEDURE setup_new_shop(IN shop_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE brand_template_id INT;
    DECLARE brand_template_name VARCHAR(100);
    DECLARE new_brand_id INT;

    DECLARE brand_cursor CURSOR FOR
SELECT id, name FROM default_brands_template WHERE is_active = TRUE;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

START TRANSACTION;

-- نسخ Brands
OPEN brand_cursor;
brand_loop: LOOP
        FETCH brand_cursor INTO brand_template_id, brand_template_name;
        IF done THEN
            LEAVE brand_loop;
END IF;

        -- إدراج Brand
INSERT INTO brands (shop_id, name, created_at) VALUES (shop_id, brand_template_name, NOW());
SET new_brand_id = LAST_INSERT_ID();

        -- إدراج Models للـ Brand
INSERT INTO models (shop_id, brand_id, name, created_at)
SELECT shop_id, new_brand_id, name, NOW()
FROM default_models_template
WHERE brand_template_id = brand_template_id AND is_active = TRUE;

END LOOP;
CLOSE brand_cursor;

-- نسخ Common Issues
INSERT INTO common_issues (shop_id, category, issue_text, created_at)
SELECT shop_id, category, issue_text, NOW()
FROM default_issues_template
WHERE is_active = TRUE;

-- تحديث حالة Setup للمحل
UPDATE shops SET setup_completed = TRUE WHERE id = shop_id;

COMMIT;
END//

DELIMITER ;



-- إضافة حقول الضمانة وإعادة الفتح لجدول repairs
ALTER TABLE repairs
    ADD COLUMN warranty_days INT DEFAULT 30 COMMENT 'عدد أيام الضمانة',
ADD COLUMN reopen_type ENUM('warranty', 'paid', 'goodwill') NULL COMMENT 'نوع إعادة الفتح',
ADD COLUMN reopen_reason TEXT COMMENT 'سبب إعادة الفتح',
ADD COLUMN reopen_notes TEXT COMMENT 'ملاحظات إعادة الفتح',
ADD COLUMN reopen_date DATETIME COMMENT 'تاريخ إعادة الفتح',
ADD COLUMN parent_repair_id INT COMMENT 'معرف الإصلاح الأصلي في حالة إعادة الفتح',
ADD COLUMN is_reopened BOOLEAN DEFAULT FALSE COMMENT 'تم إعادة فتحه أم لا';

-- إضافة فهرس للبحث السريع
ALTER TABLE repairs ADD INDEX idx_reopen_status (is_reopened, status);
ALTER TABLE repairs ADD INDEX idx_warranty (delivered_at, warranty_days);

-- إضافة حالة جديدة للإصلاحات المعاد فتحها
ALTER TABLE repairs MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'delivered', 'reopened') DEFAULT 'pending';

-- تحديث الإصلاحات الموجودة لتحتوي على ضمانة افتراضية
UPDATE repairs SET warranty_days = 30 WHERE warranty_days IS NULL;