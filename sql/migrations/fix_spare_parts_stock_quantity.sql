-- ===================================================
-- Fix Spare Parts Stock Quantity
-- تحديث stock_quantity من NULL إلى 0
-- ===================================================

USE repairpoint;

-- 1. تحديث جميع القيم NULL إلى 0
UPDATE spare_parts
SET stock_quantity = 0
WHERE stock_quantity IS NULL;

-- 2. تعديل الجدول لضمان عدم السماح بـ NULL في المستقبل
ALTER TABLE spare_parts
MODIFY COLUMN stock_quantity INT NOT NULL DEFAULT 0 COMMENT 'الكمية المتوفرة';

-- 3. تحديث stock_status للقطع التي كميتها 0
UPDATE spare_parts
SET stock_status = 'available'
WHERE stock_quantity = 0
AND stock_status = 'out_of_stock';

-- عرض النتائج
SELECT
    'تم تحديث القطع بنجاح' as message,
    COUNT(*) as total_parts,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as parts_with_zero_stock,
    SUM(CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END) as parts_with_stock
FROM spare_parts;
