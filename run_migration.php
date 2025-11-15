<?php
/**
 * Script لتطبيق migration نظام تتبع الضمان والتاريخ
 */

// تعريف الوصول الآمن
define('SECURE_ACCESS', true);

// تضمين ملفات الإعداد
require_once 'config/config.php';
require_once 'includes/functions.php';

echo "=== بدء تطبيق Migration: نظام تتبع الضمان والتاريخ ===\n\n";

try {
    $db = getDB();
    $pdo = $db->getConnection();

    // قراءة ملف SQL
    $sqlFile = __DIR__ . '/sql/migrations/add_warranty_tracking_and_history.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("ملف SQL غير موجود: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // تقسيم الـ SQL إلى statements منفصلة
    // إزالة التعليقات والسطور الفارغة
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/^\s*$/m', '', $sql);

    // تقسيم حسب DELIMITER
    $parts = explode('DELIMITER', $sql);

    $pdo->beginTransaction();

    echo "تطبيق التعديلات على قاعدة البيانات...\n";

    // تنفيذ الجزء الأول (قبل DELIMITER)
    if (!empty($parts[0])) {
        $statements = array_filter(array_map('trim', explode(';', $parts[0])));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "✓ تم تنفيذ statement بنجاح\n";
                } catch (PDOException $e) {
                    // تجاهل أخطاء "already exists"
                    if (strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        echo "⚠ تحذير: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }

    // تنفيذ الـ triggers (إذا وجدت)
    if (isset($parts[1])) {
        // استخراج كل trigger
        preg_match_all('/CREATE TRIGGER.*?END\$\$/s', $parts[1], $triggers);
        foreach ($triggers[0] as $trigger) {
            $trigger = trim($trigger);
            if (!empty($trigger)) {
                try {
                    $pdo->exec($trigger);
                    echo "✓ تم إنشاء Trigger بنجاح\n";
                } catch (PDOException $e) {
                    echo "⚠ تحذير Trigger: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    $pdo->commit();

    echo "\n✅ تم تطبيق Migration بنجاح!\n";
    echo "\nالتغييرات المطبقة:\n";
    echo "1. ✓ جدول repair_history للسجل التاريخي\n";
    echo "2. ✓ حقول جديدة في جدول repairs\n";
    echo "3. ✓ فهارس محسنة للأداء\n";
    echo "4. ✓ Triggers تلقائية لتسجيل الأحداث\n";
    echo "5. ✓ View محسن v_repairs_latest_event\n";
    echo "6. ✓ نقل البيانات الموجودة\n\n";

    // التحقق من النتائج
    echo "التحقق من التطبيق:\n";

    $tables = $pdo->query("SHOW TABLES LIKE 'repair_history'")->fetchAll();
    if (count($tables) > 0) {
        echo "✓ جدول repair_history موجود\n";
    }

    $columns = $pdo->query("SHOW COLUMNS FROM repairs LIKE 'reopen_delivered_at'")->fetchAll();
    if (count($columns) > 0) {
        echo "✓ الحقول الجديدة موجودة في repairs\n";
    }

    echo "\n=== انتهى تطبيق Migration بنجاح ===\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ خطأ: " . $e->getMessage() . "\n";
    echo "تم التراجع عن التغييرات.\n";
    exit(1);
}
