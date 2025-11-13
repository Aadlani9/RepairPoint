-- ===================================================
-- RepairPoint - Migration: إضافة معرف الموديل ودعم الأجهزة المخصصة
-- Add Model Reference and Custom Device Support
-- Date: 2025-11-13
-- ===================================================

USE repairpoint;

-- ===================================================
-- 1. إضافة حقل model_reference لجدول models
-- ===================================================

-- التحقق من عدم وجود الحقل قبل الإضافة
SET @dbname = DATABASE();
SET @tablename = 'models';
SET @columnname = 'model_reference';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(50) NULL UNIQUE COMMENT ''معرّف الموديل التجاري (مثل: V2244, SM-S928)'' AFTER name')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- إضافة index للبحث السريع
SET @indexStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = 'idx_model_reference'
  ) > 0,
  'SELECT 1',
  CONCAT('CREATE INDEX idx_model_reference ON ', @tablename, ' (model_reference)')
));
PREPARE createIndexIfNotExists FROM @indexStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- ===================================================
-- 2. إضافة حقول الأجهزة المخصصة لجدول repairs
-- ===================================================

-- إضافة device_input_type
SET @tablename = 'repairs';
SET @columnname = 'device_input_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' ENUM(''list'', ''search'', ''otro'') DEFAULT ''list'' COMMENT ''طريقة إدخال الجهاز'' AFTER model_id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- إضافة custom_brand
SET @columnname = 'custom_brand';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(100) NULL COMMENT ''ماركة مخصصة للأجهزة غير الموجودة'' AFTER device_input_type')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- إضافة custom_model
SET @columnname = 'custom_model';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(100) NULL COMMENT ''موديل مخصص للأجهزة غير الموجودة'' AFTER custom_brand')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- إضافة index للبحث في الأجهزة المخصصة
SET @indexStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = 'idx_custom_device'
  ) > 0,
  'SELECT 1',
  CONCAT('CREATE INDEX idx_custom_device ON ', @tablename, ' (custom_brand, custom_model)')
));
PREPARE createIndexIfNotExists FROM @indexStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- ===================================================
-- 3. تحديث قيود التحقق (Validation Logic)
-- ===================================================

-- ملاحظة: الآن brand_id و model_id يمكن أن يكونا NULL إذا كان device_input_type = 'otro'
-- سنضيف منطق التحقق في الـ PHP بدلاً من قاعدة البيانات

-- ===================================================
-- 4. View محسّن لعرض الأجهزة
-- ===================================================

-- حذف الـ View القديم إن وجد
DROP VIEW IF EXISTS repairs_with_device_info;

-- إنشاء View جديد يتضمن المعرّف والأجهزة المخصصة
CREATE VIEW repairs_with_device_info AS
SELECT
    r.id,
    r.reference,
    r.customer_name,
    r.customer_phone,
    r.device_input_type,

    -- معلومات الجهاز من القائمة
    r.brand_id,
    r.model_id,
    b.name AS brand_name,
    m.name AS model_name,
    m.model_reference,

    -- معلومات الجهاز المخصص
    r.custom_brand,
    r.custom_model,

    -- عرض موحد للجهاز
    CASE
        WHEN r.device_input_type = 'otro' THEN
            CONCAT(
                COALESCE(r.custom_brand, 'Desconocido'),
                ' ',
                COALESCE(r.custom_model, 'Desconocido')
            )
        ELSE
            CONCAT(
                b.name,
                ' ',
                m.name,
                CASE
                    WHEN m.model_reference IS NOT NULL THEN CONCAT(' (', m.model_reference, ')')
                    ELSE ''
                END
            )
    END AS device_display,

    r.issue_description,
    r.estimated_cost,
    r.status,
    r.priority,
    r.created_at
FROM repairs r
LEFT JOIN brands b ON r.brand_id = b.id
LEFT JOIN models m ON r.model_id = m.id;

-- ===================================================
-- 5. Stored Procedure للبحث في الموديلات
-- ===================================================

DROP PROCEDURE IF EXISTS SearchModels;

DELIMITER //

CREATE PROCEDURE SearchModels(
    IN p_search_term VARCHAR(255),
    IN p_limit INT
)
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
END//

DELIMITER ;

-- ===================================================
-- النهاية - Migration مكتمل
-- ===================================================

-- عرض ملخص التغييرات
SELECT
    'Migration completed successfully!' AS status,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'models'
     AND COLUMN_NAME = 'model_reference') AS model_reference_added,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'repairs'
     AND COLUMN_NAME = 'device_input_type') AS device_input_type_added,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'repairs'
     AND COLUMN_NAME = 'custom_brand') AS custom_brand_added,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'repairs'
     AND COLUMN_NAME = 'custom_model') AS custom_model_added;

/*
==============================================
ملاحظات مهمة:
==============================================

1. model_reference:
   - اختياري (NULL allowed)
   - فريد (UNIQUE constraint)
   - يسمح بالبحث السريع

2. device_input_type:
   - 'list': اختيار من القائمة (الافتراضي)
   - 'search': بحث سريع
   - 'otro': جهاز مخصص

3. custom_brand & custom_model:
   - يُستخدمان فقط عند device_input_type = 'otro'
   - NULL في باقي الحالات

4. التوافق مع البيانات القديمة:
   - جميع الإصلاحات القديمة ستكون device_input_type = 'list'
   - لن تتأثر البيانات الموجودة

5. الـ View الجديد:
   - يعرض معلومات موحدة للجهاز
   - يدعم كلا النوعين (من القائمة أو مخصص)

6. Stored Procedure:
   - يبحث في جميع الحقول (اسم الموديل، المعرّف، الماركة)
   - نتائج مرتبة حسب الأقرب للبحث
*/
