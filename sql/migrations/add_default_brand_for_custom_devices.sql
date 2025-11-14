-- ===================================================
-- RepairPoint - Migration: إضافة ماركة افتراضية للأجهزة المخصصة
-- Add Default Brand for Custom Devices
-- Date: 2025-11-14
-- ===================================================

USE repairpoint;

-- ===================================================
-- 1. إضافة ماركة "Desconocido" (Unknown)
-- ===================================================

-- التحقق من عدم وجود الماركة مسبقاً
SET @default_brand_id = NULL;

SELECT id INTO @default_brand_id
FROM brands
WHERE name = 'Desconocido'
LIMIT 1;

-- إضافة الماركة إذا لم تكن موجودة
INSERT INTO brands (name, created_at)
SELECT 'Desconocido', NOW()
WHERE @default_brand_id IS NULL;

-- الحصول على ID الماركة
SELECT id INTO @default_brand_id
FROM brands
WHERE name = 'Desconocido'
LIMIT 1;

-- ===================================================
-- 2. إضافة موديل افتراضي "Dispositivo Personalizado"
-- ===================================================

-- التحقق من عدم وجود الموديل مسبقاً
SET @default_model_id = NULL;

SELECT id INTO @default_model_id
FROM models
WHERE brand_id = @default_brand_id
AND name = 'Dispositivo Personalizado'
LIMIT 1;

-- إضافة الموديل إذا لم يكن موجوداً
INSERT INTO models (brand_id, name, created_at)
SELECT @default_brand_id, 'Dispositivo Personalizado', NOW()
WHERE @default_model_id IS NULL;

-- الحصول على ID الموديل
SELECT id INTO @default_model_id
FROM models
WHERE brand_id = @default_brand_id
AND name = 'Dispositivo Personalizado'
LIMIT 1;

-- ===================================================
-- 3. عرض النتائج
-- ===================================================

SELECT
    '✅ Migration completed successfully!' AS status,
    @default_brand_id AS default_brand_id,
    @default_model_id AS default_model_id,
    (SELECT name FROM brands WHERE id = @default_brand_id) AS brand_name,
    (SELECT name FROM models WHERE id = @default_model_id) AS model_name;

-- ===================================================
-- 4. إضافة ملاحظة في جدول config (اختياري)
-- ===================================================

-- حفظ IDs للاستخدام في الـ PHP
INSERT INTO config (setting_key, setting_value, description, created_at)
VALUES
    ('default_unknown_brand_id', @default_brand_id, 'ID de la marca por defecto para dispositivos personalizados', NOW()),
    ('default_unknown_model_id', @default_model_id, 'ID del modelo por defecto para dispositivos personalizados', NOW())
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    updated_at = NOW();

/*
==============================================
ملاحظات مهمة:
==============================================

1. الماركة "Desconocido":
   - تُستخدم فقط عند اختيار "Otro Dispositivo no encontrado"
   - لن تظهر في قائمة الماركات العادية

2. الموديل "Dispositivo Personalizado":
   - موديل عام لجميع الأجهزة المخصصة
   - يحافظ على قيود NOT NULL في القاعدة

3. البيانات الفعلية:
   - ستُحفظ في custom_brand و custom_model
   - ستُعرض في الواجهة بدلاً من "Desconocido"

4. الإحصائيات:
   - ستظهر "Desconocido" في إحصائيات الماركات
   - يمكن تحسينها لاحقاً لعرض التفاصيل المخصصة

5. التوافق:
   - لن تتأثر الإصلاحات القديمة
   - يعمل مع جميع الاستعلامات الموجودة دون تعديل
*/
