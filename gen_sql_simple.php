<?php
/**
 * مولّد SQL بسيط - مستقل تماماً
 */

// قراءة ملف البيانات
$content = file_get_contents(__DIR__ . '/import_phones_data.php');

// استخراج المصفوفة
preg_match('/\$phones_data\s*=\s*\[(.*?)\];/s', $content, $matches);

if (empty($matches[1])) {
    die("Error: Could not extract phones_data\n");
}

// تقييم المصفوفة
eval('$phones_data = [' . $matches[1] . '];');

echo "✓ Found " . count($phones_data) . " phones\n";

// جمع البراندات
$brands = [];
$stats = ['total' => 0, 'with_ref' => 0, 'without_ref' => 0];

foreach ($phones_data as $phone) {
    $brand = $phone[0];
    $ref = trim($phone[3]);

    if (!in_array($brand, $brands)) {
        $brands[] = $brand;
    }

    $stats['total']++;
    if (!empty($ref)) {
        $stats['with_ref']++;
    } else {
        $stats['without_ref']++;
    }
}

echo "✓ Brands: " . count($brands) . "\n";
echo "✓ With references: " . $stats['with_ref'] . "\n";
echo "✓ Without references: " . $stats['without_ref'] . "\n";

// بدء توليد SQL
$sql = "-- ==========================================
-- RepairPoint - استيراد 490 موديل هاتف
-- ==========================================
-- تاريخ: " . date('Y-m-d H:i:s') . "
-- إجمالي: {$stats['total']} موديل
-- مع معرفات: {$stats['with_ref']}
-- بدون معرفات: {$stats['without_ref']}
-- ==========================================

SET NAMES utf8mb4;

-- البراندات
INSERT IGNORE INTO brands (name) VALUES\n";

$brand_values = [];
foreach ($brands as $brand) {
    $brand_values[] = "('" . addslashes($brand) . "')";
}
$sql .= implode(",\n", $brand_values) . ";\n\n";

// الموديلات
$sql .= "-- الموديلات\n";

foreach ($phones_data as $phone) {
    $brand = addslashes($phone[0]);
    $model = addslashes($phone[2]);
    $ref = addslashes(trim($phone[3]));

    if (!empty($ref)) {
        $sql .= "INSERT INTO models (brand_id, name, model_reference)\n";
        $sql .= "SELECT id, '{$model}', '{$ref}' FROM brands WHERE name = '{$brand}'\n";
    } else {
        $sql .= "INSERT INTO models (brand_id, name)\n";
        $sql .= "SELECT id, '{$model}' FROM brands WHERE name = '{$brand}'\n";
    }
    $sql .= "WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = '{$brand}') AND name = '{$model}');\n\n";
}

// تحديث المعرفات
$sql .= "-- تحديث المعرفات للموديلات الموجودة\n";

foreach ($phones_data as $phone) {
    $brand = addslashes($phone[0]);
    $model = addslashes($phone[2]);
    $ref = addslashes(trim($phone[3]));

    if (!empty($ref)) {
        $sql .= "UPDATE models m JOIN brands b ON m.brand_id = b.id\n";
        $sql .= "SET m.model_reference = '{$ref}'\n";
        $sql .= "WHERE b.name = '{$brand}' AND m.name = '{$model}' AND (m.model_reference IS NULL OR m.model_reference = '');\n\n";
    }
}

// حفظ
$output = __DIR__ . '/sql/import_phones_with_references.sql';
file_put_contents($output, $sql);

echo "\n✅ SQL file generated: $output\n";
echo "✅ Size: " . number_format(filesize($output)) . " bytes\n";
echo "✅ Lines: " . count(explode("\n", $sql)) . "\n";
?>
