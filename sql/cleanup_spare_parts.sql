-- ===================================================
-- RepairPoint - كود تنظيف نظام قطع الغيار
-- Cleanup Script for Spare Parts System
-- ===================================================

USE repairpoint;

-- ===================================================
-- 1. حذف Stored Procedures
-- ===================================================
DROP PROCEDURE IF EXISTS GetSparePartsByPhone;
DROP PROCEDURE IF EXISTS SearchSpareParts;

-- ===================================================
-- 2. حذف Triggers
-- ===================================================
DROP TRIGGER IF EXISTS check_compatibility_shop_isolation;
DROP TRIGGER IF EXISTS save_price_history;
DROP TRIGGER IF EXISTS update_stock_on_repair;
DROP TRIGGER IF EXISTS check_stock_before_use;

-- ===================================================
-- 3. حذف Views
-- ===================================================
DROP VIEW IF EXISTS spare_parts_profit_report;
DROP VIEW IF EXISTS spare_parts_with_compatibility;
DROP VIEW IF EXISTS low_stock_parts;

-- ===================================================
-- 4. حذف الجداول (بالترتيب الصحيح بسبب Foreign Keys)
-- ===================================================
DROP TABLE IF EXISTS spare_parts_price_history;
DROP TABLE IF EXISTS repair_spare_parts;
DROP TABLE IF EXISTS spare_parts_compatibility;
DROP TABLE IF EXISTS spare_parts;

-- ===================================================
-- تنظيف مكتمل - يمكن الآن تشغيل كود إنشاء قطع الغيار من جديد
-- ===================================================

SELECT 'Cleanup completed successfully! You can now run the spare_parts_system.sql again.' AS message;