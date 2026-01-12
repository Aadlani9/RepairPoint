<?php
/**
 * RepairPoint - اختبار الاتصال بقاعدة البيانات
 * ⚠️ هذا الملف للاختبار فقط - احذفه بعد النشر!
 */

// تفعيل عرض الأخطاء للاختبار فقط
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تعريف الوصول الآمن
define('SECURE_ACCESS', true);

// تحميل ملف قاعدة البيانات
require_once __DIR__ . '/../config/database.php';

// إعداد header JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // محاولة الحصول على اتصال بقاعدة البيانات
    $db = Database::getInstance();

    // اختبار الاتصال
    if ($db->isConnected()) {
        // اختبار استعلام بسيط
        $result = $db->selectOne("SELECT 1 as test");

        if ($result && isset($result['test']) && $result['test'] == 1) {
            // نجاح كامل
            echo json_encode([
                'status' => 'success',
                'message' => 'اتصال قاعدة البيانات ناجح',
                'connection' => 'active',
                'query_test' => 'passed',
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            // الاتصال موجود لكن الاستعلام فشل
            echo json_encode([
                'status' => 'warning',
                'message' => 'الاتصال موجود لكن الاستعلام فشل',
                'connection' => 'active',
                'query_test' => 'failed',
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } else {
        // الاتصال فشل
        echo json_encode([
            'status' => 'error',
            'message' => 'فشل الاتصال بقاعدة البيانات',
            'connection' => 'failed',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    // خطأ في الاتصال
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'خطأ في الاتصال بقاعدة البيانات',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
