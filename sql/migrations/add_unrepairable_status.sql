-- ============================================
-- Migration: إضافة حالة "غير قابل للإصلاح"
-- التاريخ: 2026-04-24
-- ============================================

-- 1. إضافة قيمة unrepairable لعمود status
ALTER TABLE repairs MODIFY COLUMN status
    ENUM('pending', 'in_progress', 'completed', 'delivered', 'reopened', 'unrepairable')
    DEFAULT 'pending';

-- 2. إضافة الأعمدة الجديدة
ALTER TABLE repairs
ADD COLUMN IF NOT EXISTS unrepairable_reason TEXT    COMMENT 'سبب عدم الإصلاح',
ADD COLUMN IF NOT EXISTS unrepairable_notes  TEXT    COMMENT 'ملاحظات إضافية عن عدم الإصلاح',
ADD COLUMN IF NOT EXISTS unrepairable_at     DATETIME COMMENT 'تاريخ تسجيل عدم الإصلاح',
ADD COLUMN IF NOT EXISTS unrepairable_by     INT     COMMENT 'المستخدم الذي سجل عدم الإصلاح';
