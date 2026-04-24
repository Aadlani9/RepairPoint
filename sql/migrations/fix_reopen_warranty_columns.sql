-- ============================================
-- Migration: إصلاح أعمدة الضمان وإعادة الفتح
-- التاريخ: 2026-04-24
-- الوصف: إضافة الأعمدة الناقصة لنظام إعادة فتح الضمان
-- ============================================

-- 1. إضافة قيمة 'reopened' لعمود status إذا لم تكن موجودة
ALTER TABLE repairs MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'delivered', 'reopened') DEFAULT 'pending';

-- 2. إضافة أعمدة نوع/سبب/ملاحظات إعادة الفتح
ALTER TABLE repairs
ADD COLUMN IF NOT EXISTS reopen_type ENUM('warranty', 'paid', 'goodwill') NULL COMMENT 'نوع إعادة الفتح',
ADD COLUMN IF NOT EXISTS reopen_reason TEXT COMMENT 'سبب إعادة الفتح',
ADD COLUMN IF NOT EXISTS reopen_notes TEXT COMMENT 'ملاحظات إعادة الفتح',
ADD COLUMN IF NOT EXISTS reopen_date DATETIME COMMENT 'تاريخ إعادة الفتح',
ADD COLUMN IF NOT EXISTS is_reopened BOOLEAN DEFAULT FALSE COMMENT 'تم إعادة فتحه أم لا';

-- 3. إضافة أعمدة التتبع المتقدمة للضمان
ALTER TABLE repairs
ADD COLUMN IF NOT EXISTS reopen_warranty_days INT DEFAULT 30 COMMENT 'أيام الضمان الجديدة بعد إعادة الفتح',
ADD COLUMN IF NOT EXISTS reopen_delivered_at DATETIME COMMENT 'تاريخ التسليم بعد إعادة الفتح',
ADD COLUMN IF NOT EXISTS reopen_completed_at DATETIME COMMENT 'تاريخ الإنجاز بعد إعادة الفتح',
ADD COLUMN IF NOT EXISTS original_delivered_at DATETIME COMMENT 'تاريخ التسليم الأصلي',
ADD COLUMN IF NOT EXISTS reopen_count INT DEFAULT 0 COMMENT 'عدد مرات إعادة الفتح',
ADD COLUMN IF NOT EXISTS last_reopen_by INT COMMENT 'المستخدم الذي قام بآخر إعادة فتح';

-- 4. إضافة فهارس الأداء
ALTER TABLE repairs
ADD INDEX IF NOT EXISTS idx_reopen_status (is_reopened, status),
ADD INDEX IF NOT EXISTS idx_reopen_delivered (reopen_delivered_at),
ADD INDEX IF NOT EXISTS idx_reopen_count (reopen_count),
ADD INDEX IF NOT EXISTS idx_original_delivered (original_delivered_at);

-- 5. إنشاء جدول السجل التاريخي للإصلاحات إذا لم يكن موجوداً
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
    ) NOT NULL,
    event_data JSON,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    description TEXT,
    warranty_days INT,
    delivered_at DATETIME,
    cost_amount DECIMAL(10,2),
    performed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_repair (repair_id),
    INDEX idx_shop (shop_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
