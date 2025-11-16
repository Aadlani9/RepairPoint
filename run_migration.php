<?php
/**
 * تشغيل Migration لإضافة حقول الضمان
 */

define('SECURE_ACCESS', true);
require_once 'config/config.php';

echo "🚀 بدء تشغيل migration...\n\n";

// قراءة ملف migration
$migration_file = __DIR__ . '/sql/migrations/add_warranty_tracking_and_history.sql';

if (!file_exists($migration_file)) {
    die("❌ ملف migration غير موجود: $migration_file\n");
}

echo "📄 قراءة ملف: $migration_file\n";
$sql = file_get_contents($migration_file);

// تقسيم SQL إلى أوامر منفصلة
$statements = [];
$current_statement = '';
$in_delimiter_block = false;
$custom_delimiter = ';';

$lines = explode("\n", $sql);
foreach ($lines as $line) {
    $line = trim($line);

    if (empty($line) || substr($line, 0, 2) === '--') {
        continue;
    }

    if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
        $custom_delimiter = trim($matches[1]);
        $in_delimiter_block = ($custom_delimiter !== ';');
        continue;
    }

    $current_statement .= $line . "\n";

    if ($in_delimiter_block) {
        if (substr(rtrim($line), -strlen($custom_delimiter)) === $custom_delimiter) {
            $current_statement = substr($current_statement, 0, -strlen($custom_delimiter) - 1);
            $statements[] = trim($current_statement);
            $current_statement = '';
        }
    } else {
        if (substr($line, -1) === ';') {
            $statements[] = trim($current_statement);
            $current_statement = '';
        }
    }
}

if (!empty(trim($current_statement))) {
    $statements[] = trim($current_statement);
}

echo "📊 عدد الأوامر: " . count($statements) . "\n\n";

$db = getDB();
$success_count = 0;
$error_count = 0;

foreach ($statements as $index => $statement) {
    if (empty($statement)) continue;

    $preview = substr($statement, 0, 60) . '...';
    echo "⚡ أمر " . ($index + 1) . ": $preview\n";

    try {
        $db->getPDO()->exec($statement);
        echo "   ✅ نجح\n";
        $success_count++;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
            echo "   ⚠️  موجود مسبقاً\n";
            $success_count++;
        } else {
            echo "   ❌ خطأ: " . $msg . "\n";
            $error_count++;
        }
    }
}

echo "\n📈 النتائج: ✅ $success_count | ❌ $error_count\n";
echo ($error_count === 0 ? "✨ نجح!\n" : "⚠️  توجد أخطاء\n");
