<?php
/**
 * RepairPoint - مولّد ملف SQL لاستيراد الهواتف
 * يقرأ البيانات من import_phones_data.php ويولّد ملف SQL كامل
 */

// Definir acceso seguro (تجنب إعادة التعريف)
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// تجنب الاتصال بقاعدة البيانات - نحتاج فقط البيانات
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// قراءة بيانات الهواتف مباشرة
$phones_data = [];
$file_content = file_get_contents(__DIR__ . '/import_phones_data.php');

// استخراج المصفوفة بدون تنفيذ كامل السكريبت
eval(preg_replace('/^<\?php.*?\$phones_data\s*=\s*/', '$phones_data = ', $file_content, 1));

// اسم الملف الناتج
$output_file = __DIR__ . '/sql/import_phones_with_references.sql';

// بداية محتوى SQL
$sql = "-- ==========================================
-- RepairPoint - استيراد بيانات الهواتف مع المعرفات
-- ==========================================
-- تاريخ التوليد: " . date('Y-m-d H:i:s') . "
-- إجمالي السجلات: " . count($phones_data) . "
--
-- المنطق:
-- 1. إضافة البراندات الجديدة فقط (INSERT IGNORE)
-- 2. إضافة الموديلات الجديدة مع معرفاتها (إن وُجدت)
-- 3. تحديث المعرفات للموديلات الموجودة التي ليس لديها معرف
-- 4. عدم استبدال المعرفات الموجودة
-- ==========================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ==========================================
-- القسم 1: إضافة البراندات الجديدة فقط
-- ==========================================

";

// جمع البراندات الفريدة
$brands = [];
foreach ($phones_data as $phone) {
    $brand = $phone[0];
    if (!in_array($brand, $brands)) {
        $brands[] = $brand;
    }
}

// إضافة البراندات
$sql .= "INSERT IGNORE INTO brands (name) VALUES\n";
$brand_values = [];
foreach ($brands as $brand) {
    $brand_values[] = "('" . addslashes($brand) . "')";
}
$sql .= implode(",\n", $brand_values) . ";\n\n";

// إحصائيات
$stats = [
    'total' => 0,
    'with_ref' => 0,
    'without_ref' => 0,
    'by_brand' => []
];

$sql .= "-- ==========================================
-- القسم 2: إضافة الموديلات الجديدة
-- ==========================================
-- يتم إضافة model_reference فقط للموديلات التي لديها معرف
\n";

// تنظيم البيانات حسب البراند
$organized_data = [];
foreach ($phones_data as $phone) {
    $brand = $phone[0];
    $model = $phone[2];
    $ref = trim($phone[3]);

    if (!isset($organized_data[$brand])) {
        $organized_data[$brand] = [];
        $stats['by_brand'][$brand] = ['total' => 0, 'with_ref' => 0];
    }

    $organized_data[$brand][] = [
        'model' => $model,
        'ref' => $ref
    ];

    $stats['total']++;
    $stats['by_brand'][$brand]['total']++;

    if (!empty($ref)) {
        $stats['with_ref']++;
        $stats['by_brand'][$brand]['with_ref']++;
    } else {
        $stats['without_ref']++;
    }
}

// توليد INSERT للموديلات
foreach ($organized_data as $brand => $models) {
    $sql .= "\n-- ==========================================\n";
    $sql .= "-- {$brand} Models (" . count($models) . " موديل)\n";
    $sql .= "-- مع معرفات: " . $stats['by_brand'][$brand]['with_ref'] . "\n";
    $sql .= "-- بدون معرفات: " . (count($models) - $stats['by_brand'][$brand]['with_ref']) . "\n";
    $sql .= "-- ==========================================\n\n";

    foreach ($models as $model_data) {
        $model_name = addslashes($model_data['model']);
        $model_ref = addslashes($model_data['ref']);

        if (!empty($model_ref)) {
            // موديل مع معرف
            $sql .= "INSERT INTO models (brand_id, name, model_reference)\n";
            $sql .= "SELECT b.id, '{$model_name}', '{$model_ref}' FROM brands b WHERE b.name = '{$brand}'\n";
            $sql .= "WHERE NOT EXISTS (\n";
            $sql .= "    SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = '{$brand}') AND name = '{$model_name}'\n";
            $sql .= ");\n\n";
        } else {
            // موديل بدون معرف
            $sql .= "INSERT INTO models (brand_id, name)\n";
            $sql .= "SELECT b.id, '{$model_name}' FROM brands b WHERE b.name = '{$brand}'\n";
            $sql .= "WHERE NOT EXISTS (\n";
            $sql .= "    SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = '{$brand}') AND name = '{$model_name}'\n";
            $sql .= ");\n\n";
        }
    }
}

// القسم 3: تحديث المعرفات للموديلات الموجودة
$sql .= "\n-- ==========================================\n";
$sql .= "-- القسم 3: تحديث المعرفات للموديلات الموجودة\n";
$sql .= "-- ==========================================\n";
$sql .= "-- يحدّث model_reference فقط للموديلات الموجودة التي ليس لديها معرف\n\n";

foreach ($organized_data as $brand => $models) {
    foreach ($models as $model_data) {
        $model_name = addslashes($model_data['model']);
        $model_ref = addslashes($model_data['ref']);

        // فقط للموديلات التي لديها معرف
        if (!empty($model_ref)) {
            $sql .= "UPDATE models m\n";
            $sql .= "JOIN brands b ON m.brand_id = b.id\n";
            $sql .= "SET m.model_reference = '{$model_ref}'\n";
            $sql .= "WHERE b.name = '{$brand}' AND m.name = '{$model_name}'\n";
            $sql .= "AND (m.model_reference IS NULL OR m.model_reference = '');\n\n";
        }
    }
}

// الإحصائيات النهائية
$sql .= "\n-- ==========================================\n";
$sql .= "-- إحصائيات البيانات المستوردة\n";
$sql .= "-- ==========================================\n";
$sql .= "-- إجمالي السجلات: {$stats['total']}\n";
$sql .= "-- مع معرفات: {$stats['with_ref']}\n";
$sql .= "-- بدون معرفات: {$stats['without_ref']}\n";
$sql .= "-- عدد البراندات: " . count($brands) . "\n";
$sql .= "--\n";
$sql .= "-- توزيع حسب البراند:\n";
foreach ($stats['by_brand'] as $brand => $brand_stats) {
    $sql .= "-- {$brand}: {$brand_stats['total']} موديل ({$brand_stats['with_ref']} مع معرف)\n";
}
$sql .= "-- ==========================================\n\n";

// استعلام للتحقق بعد التنفيذ
$sql .= "-- للتحقق من النتائج بعد التنفيذ:\n";
$sql .= "/*\n";
$sql .= "SELECT b.name as brand, \n";
$sql .= "       COUNT(m.id) as total_models,\n";
$sql .= "       SUM(CASE WHEN m.model_reference IS NOT NULL AND m.model_reference != '' THEN 1 ELSE 0 END) as with_reference,\n";
$sql .= "       SUM(CASE WHEN m.model_reference IS NULL OR m.model_reference = '' THEN 1 ELSE 0 END) as without_reference\n";
$sql .= "FROM brands b\n";
$sql .= "LEFT JOIN models m ON b.id = m.brand_id\n";
$sql .= "GROUP BY b.id, b.name\n";
$sql .= "ORDER BY total_models DESC;\n";
$sql .= "*/\n";

// حفظ الملف
file_put_contents($output_file, $sql);

// عرض النتيجة
echo "<!DOCTYPE html>\n";
echo "<html lang='ar' dir='rtl'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>تم توليد ملف SQL بنجاح</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial; padding: 20px; background: #f5f5f5; }\n";
echo "        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo "        h1 { color: #28a745; }\n";
echo "        .stats { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }\n";
echo "        .stats h3 { margin-top: 0; color: #155724; }\n";
echo "        .brand-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }\n";
echo "        .brand-item { background: #f8f9fa; padding: 10px; border-radius: 5px; }\n";
echo "        .instructions { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107; margin: 20px 0; }\n";
echo "        code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }\n";
echo "        .success { color: #28a745; font-weight: bold; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <div class='container'>\n";
echo "        <h1>✅ تم توليد ملف SQL بنجاح!</h1>\n";
echo "        <p class='success'>الملف: <code>{$output_file}</code></p>\n";
echo "        <p>حجم الملف: <strong>" . number_format(filesize($output_file) / 1024, 2) . " KB</strong></p>\n";
echo "        \n";
echo "        <div class='stats'>\n";
echo "            <h3>📊 إحصائيات البيانات</h3>\n";
echo "            <p><strong>إجمالي السجلات:</strong> {$stats['total']}</p>\n";
echo "            <p><strong>مع معرفات (model_reference):</strong> <span style='color: #28a745;'>{$stats['with_ref']}</span></p>\n";
echo "            <p><strong>بدون معرفات:</strong> <span style='color: #6c757d;'>{$stats['without_ref']}</span></p>\n";
echo "            <p><strong>عدد البراندات:</strong> " . count($brands) . "</p>\n";
echo "        </div>\n";
echo "        \n";
echo "        <h3>📋 توزيع حسب البراند</h3>\n";
echo "        <div class='brand-stats'>\n";
foreach ($stats['by_brand'] as $brand => $brand_stats) {
    echo "            <div class='brand-item'>\n";
    echo "                <strong>{$brand}:</strong> {$brand_stats['total']} موديل<br>\n";
    echo "                <small style='color: #28a745;'>✓ {$brand_stats['with_ref']} مع معرف</small>\n";
    echo "            </div>\n";
}
echo "        </div>\n";
echo "        \n";
echo "        <div class='instructions'>\n";
echo "            <h3>🚀 طريقة التنفيذ</h3>\n";
echo "            <p><strong>الخيار 1: phpMyAdmin</strong></p>\n";
echo "            <ol>\n";
echo "                <li>افتح phpMyAdmin</li>\n";
echo "                <li>اختر قاعدة البيانات <code>repairpoint</code></li>\n";
echo "                <li>اذهب لتبويب \"SQL\"</li>\n";
echo "                <li>انسخ محتوى الملف: <code>sql/import_phones_with_references.sql</code></li>\n";
echo "                <li>الصق في المحرر واضغط \"تنفيذ\"</li>\n";
echo "            </ol>\n";
echo "            \n";
echo "            <p><strong>الخيار 2: MySQL Command Line</strong></p>\n";
echo "            <pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>mysql -u root -p repairpoint &lt; sql/import_phones_with_references.sql</pre>\n";
echo "        </div>\n";
echo "        \n";
echo "        <h3>⚠️ ملاحظات مهمة</h3>\n";
echo "        <ul>\n";
echo "            <li>✅ البراندات المكررة سيتم تخطيها تلقائياً (INSERT IGNORE)</li>\n";
echo "            <li>✅ الموديلات المكررة لن يتم إضافتها (NOT EXISTS)</li>\n";
echo "            <li>✅ المعرفات ستُضاف فقط للموديلات التي ليس لديها معرف</li>\n";
echo "            <li>✅ المعرفات الموجودة لن يتم استبدالها</li>\n";
echo "            <li>⚡ الاستيراد آمن ويمكن تنفيذه عدة مرات</li>\n";
echo "        </ul>\n";
echo "        \n";
echo "        <p style='text-align: center; margin-top: 30px;'>\n";
echo "            <a href='sql/import_phones_with_references.sql' download style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📥 تحميل ملف SQL</a>\n";
echo "        </p>\n";
echo "    </div>\n";
echo "</body>\n";
echo "</html>\n";
?>
