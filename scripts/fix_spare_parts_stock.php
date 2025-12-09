<?php
/**
 * Fix Spare Parts Stock Quantity
 * تحديث stock_quantity من NULL إلى 0
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . 'functions.php';

echo "🔧 إصلاح stock_quantity لقطع الغيار...\n\n";

try {
    $db = getDB();

    // 1. عرض القطع التي لديها stock_quantity = NULL
    $null_parts = $db->select("SELECT id, part_name, stock_quantity, stock_status FROM spare_parts WHERE stock_quantity IS NULL");

    if (!empty($null_parts)) {
        echo "📋 القطع التي لديها stock_quantity = NULL:\n";
        foreach ($null_parts as $part) {
            echo "   - ID: {$part['id']} | {$part['part_name']} | Status: {$part['stock_status']}\n";
        }
        echo "\n";
    } else {
        echo "✅ لا توجد قطع بقيمة NULL\n\n";
    }

    // 2. تحديث NULL إلى 0
    $updated = $db->update("UPDATE spare_parts SET stock_quantity = 0 WHERE stock_quantity IS NULL");
    echo "✅ تم تحديث {$updated} قطعة من NULL إلى 0\n";

    // 3. تحديث stock_status للقطع بكمية 0 لتكون متاحة
    $status_updated = $db->update("UPDATE spare_parts SET stock_status = 'available' WHERE stock_quantity = 0");
    echo "✅ تم تحديث حالة {$status_updated} قطعة إلى 'available'\n\n";

    // 4. عرض الإحصائيات النهائية
    $stats = $db->selectOne("SELECT
        COUNT(*) as total_parts,
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as parts_with_zero_stock,
        SUM(CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END) as parts_with_stock,
        SUM(CASE WHEN stock_status = 'available' THEN 1 ELSE 0 END) as available_parts
    FROM spare_parts");

    echo "📊 الإحصائيات النهائية:\n";
    echo "   إجمالي القطع: " . $stats['total_parts'] . "\n";
    echo "   قطع بكمية 0: " . $stats['parts_with_zero_stock'] . "\n";
    echo "   قطع بكمية > 0: " . $stats['parts_with_stock'] . "\n";
    echo "   قطع متاحة: " . $stats['available_parts'] . "\n\n";

    echo "✨ تم الإصلاح بنجاح!\n";

} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    exit(1);
}
