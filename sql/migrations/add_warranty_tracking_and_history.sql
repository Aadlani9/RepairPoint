-- ============================================
-- Migration: نظام تتبع الضمان والتاريخ الكامل
-- التاريخ: 2025-11-15
-- الوصف: إضافة جدول السجل التاريخي وحقول الضمان الجديدة
-- ============================================

-- 1. إنشاء جدول السجل التاريخي للإصلاحات
CREATE TABLE IF NOT EXISTS repair_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repair_id INT NOT NULL,
    shop_id INT NOT NULL,
    event_type ENUM(
        'created',
        'status_changed',
        'completed',
        'delivered',
        'reopened',
        'warranty_reopened',
        'paid_reopened',
        'goodwill_reopened',
        'redelivered',
        'cost_updated',
        'note_added'
    ) NOT NULL COMMENT 'نوع الحدث',
    event_data JSON COMMENT 'بيانات الحدث بتنسيق JSON',
    old_status VARCHAR(50) COMMENT 'الحالة القديمة',
    new_status VARCHAR(50) COMMENT 'الحالة الجديدة',
    description TEXT COMMENT 'وصف الحدث',
    warranty_days INT COMMENT 'أيام الضمان في وقت الحدث',
    delivered_at DATETIME COMMENT 'تاريخ التسليم في وقت الحدث',
    cost_amount DECIMAL(10,2) COMMENT 'التكلفة في وقت الحدث',
    performed_by INT NOT NULL COMMENT 'المستخدم الذي قام بالإجراء',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'وقت تسجيل الحدث',

    INDEX idx_repair (repair_id),
    INDEX idx_shop (shop_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='السجل التاريخي الكامل لجميع أحداث الإصلاحات';

-- 2. إضافة حقول جديدة لجدول repairs
ALTER TABLE repairs
ADD COLUMN IF NOT EXISTS reopen_delivered_at DATETIME COMMENT 'تاريخ التسليم بعد إعادة الفتح الأخيرة',
ADD COLUMN IF NOT EXISTS reopen_warranty_days INT DEFAULT 30 COMMENT 'أيام الضمان الجديدة بعد إعادة الفتح',
ADD COLUMN IF NOT EXISTS reopen_completed_at DATETIME COMMENT 'تاريخ الإنجاز بعد إعادة الفتح',
ADD COLUMN IF NOT EXISTS original_delivered_at DATETIME COMMENT 'نسخة من تاريخ التسليم الأصلي',
ADD COLUMN IF NOT EXISTS reopen_count INT DEFAULT 0 COMMENT 'عدد مرات إعادة الفتح',
ADD COLUMN IF NOT EXISTS last_reopen_by INT COMMENT 'المستخدم الذي قام بآخر إعادة فتح';

-- 3. إضافة فهارس للأداء
ALTER TABLE repairs
ADD INDEX IF NOT EXISTS idx_reopen_delivered (reopen_delivered_at),
ADD INDEX IF NOT EXISTS idx_reopen_count (reopen_count),
ADD INDEX IF NOT EXISTS idx_original_delivered (original_delivered_at);

-- 4. إنشاء trigger لتسجيل الأحداث تلقائيًا عند التحديثات
DELIMITER $$

-- Trigger عند تحديث حالة الإصلاح
DROP TRIGGER IF EXISTS repair_status_change_history$$
CREATE TRIGGER repair_status_change_history
AFTER UPDATE ON repairs
FOR EACH ROW
BEGIN
    -- تسجيل تغيير الحالة
    IF OLD.status != NEW.status THEN
        INSERT INTO repair_history (
            repair_id,
            shop_id,
            event_type,
            old_status,
            new_status,
            description,
            warranty_days,
            delivered_at,
            performed_by
        ) VALUES (
            NEW.id,
            NEW.shop_id,
            CASE
                WHEN NEW.status = 'completed' THEN 'completed'
                WHEN NEW.status = 'delivered' THEN 'delivered'
                WHEN NEW.status = 'reopened' THEN
                    CASE NEW.reopen_type
                        WHEN 'warranty' THEN 'warranty_reopened'
                        WHEN 'paid' THEN 'paid_reopened'
                        WHEN 'goodwill' THEN 'goodwill_reopened'
                        ELSE 'reopened'
                    END
                ELSE 'status_changed'
            END,
            OLD.status,
            NEW.status,
            CONCAT('Estado cambiado de ', OLD.status, ' a ', NEW.status),
            COALESCE(NEW.reopen_warranty_days, NEW.warranty_days),
            COALESCE(NEW.reopen_delivered_at, NEW.delivered_at),
            NEW.updated_by
        );
    END IF;

    -- تسجيل التسليم بعد إعادة الفتح
    IF NEW.reopen_delivered_at IS NOT NULL AND OLD.reopen_delivered_at IS NULL THEN
        INSERT INTO repair_history (
            repair_id,
            shop_id,
            event_type,
            description,
            warranty_days,
            delivered_at,
            performed_by
        ) VALUES (
            NEW.id,
            NEW.shop_id,
            'redelivered',
            'Dispositivo re-entregado después de reapertura',
            NEW.reopen_warranty_days,
            NEW.reopen_delivered_at,
            NEW.updated_by
        );
    END IF;
END$$

DELIMITER ;

-- 5. نقل البيانات الموجودة إلى original_delivered_at
UPDATE repairs
SET original_delivered_at = delivered_at
WHERE delivered_at IS NOT NULL
  AND original_delivered_at IS NULL
  AND is_reopened = FALSE;

-- 6. إنشاء سجل تاريخي للإصلاحات الموجودة (optional - يمكن تعطيله)
INSERT INTO repair_history (
    repair_id,
    shop_id,
    event_type,
    new_status,
    description,
    warranty_days,
    delivered_at,
    performed_by,
    created_at
)
SELECT
    r.id,
    r.shop_id,
    'created',
    r.status,
    'Reparación inicial registrada',
    r.warranty_days,
    r.delivered_at,
    r.created_by,
    r.created_at
FROM repairs r
WHERE NOT EXISTS (
    SELECT 1 FROM repair_history rh
    WHERE rh.repair_id = r.id
    AND rh.event_type = 'created'
);

-- 7. إنشاء view مساعد للحصول على آخر حدث لكل إصلاح
CREATE OR REPLACE VIEW v_repairs_latest_event AS
SELECT
    r.id AS repair_id,
    r.reference,
    r.status,
    r.is_reopened,
    r.reopen_type,
    r.reopen_count,
    -- آخر تسليم (من إعادة الفتح أو الأصلي)
    COALESCE(r.reopen_delivered_at, r.delivered_at) AS current_delivered_at,
    -- الضمان الحالي
    COALESCE(r.reopen_warranty_days, r.warranty_days) AS current_warranty_days,
    -- تاريخ البداية للحساب (من إعادة الفتح أو الاستلام الأصلي)
    COALESCE(r.reopen_date, r.received_at) AS current_start_date,
    -- التسليم الأصلي
    r.original_delivered_at,
    r.warranty_days AS original_warranty_days,
    -- المدة الحالية (من آخر حدث)
    DATEDIFF(
        COALESCE(r.reopen_delivered_at, NOW()),
        COALESCE(r.reopen_date, r.received_at)
    ) AS current_duration,
    -- المدة الكلية
    DATEDIFF(
        COALESCE(r.reopen_delivered_at, r.delivered_at, NOW()),
        r.received_at
    ) AS total_duration,
    -- أيام الضمان المتبقية
    CASE
        WHEN COALESCE(r.reopen_delivered_at, r.delivered_at) IS NOT NULL THEN
            GREATEST(0, COALESCE(r.reopen_warranty_days, r.warranty_days) -
                DATEDIFF(NOW(), COALESCE(r.reopen_delivered_at, r.delivered_at)))
        ELSE NULL
    END AS warranty_days_remaining,
    -- هل في الضمان؟
    CASE
        WHEN COALESCE(r.reopen_delivered_at, r.delivered_at) IS NOT NULL THEN
            DATEDIFF(NOW(), COALESCE(r.reopen_delivered_at, r.delivered_at)) <=
            COALESCE(r.reopen_warranty_days, r.warranty_days)
        ELSE FALSE
    END AS is_under_warranty
FROM repairs r;

-- 8. إضافة تعليق على الـ view
COMMENT ON VIEW v_repairs_latest_event IS 'View محسّن يعرض آخر معلومات لكل إصلاح مع حسابات المدة والضمان الصحيحة';

-- ============================================
-- نهاية Migration
-- ============================================
