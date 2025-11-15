<?php
/**
 * RepairPoint - API لإدراج بيانات الهواتف في قاعدة البيانات
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

// تضمين الملفات المطلوبة
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . 'functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'يجب أن تكون مسؤولاً لاستخدام هذه الميزة'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'طريقة الطلب غير صحيحة'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// قراءة البيانات من الطلب
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || $input['action'] !== 'import') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'بيانات الطلب غير صحيحة'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // الاتصال بقاعدة البيانات
    $db = getDB();

    // قراءة البيانات من API معاينة البيانات
    $data_file = __DIR__ . '/preview_phones_data.php';

    // الحصول على البيانات عبر include
    ob_start();
    include $data_file;
    $output = ob_get_clean();

    $result = json_decode($output, true);

    if (!$result || !$result['success']) {
        throw new Exception('فشل في قراءة بيانات الهواتف');
    }

    $all_data = $result['data'];

    // تنظيم البيانات حسب البراند
    $organized_data = [];
    foreach ($all_data as $item) {
        $brand = $item['brand'];
        if (!isset($organized_data[$brand])) {
            $organized_data[$brand] = [];
        }
        $organized_data[$brand][] = [
            'name' => $item['model'],
            'model_code' => $item['reference']
        ];
    }

    // بدء المعاملة
    $db->beginTransaction();

    $brands_added = 0;
    $models_added = 0;
    $brands_skipped = 0;
    $models_skipped = 0;
    $errors = [];

    foreach ($organized_data as $brand_name => $models) {
        try {
            // التحقق من وجود البراند
            $existing_brand = $db->selectOne(
                "SELECT id FROM brands WHERE name = ?",
                [$brand_name]
            );

            if ($existing_brand) {
                $brand_id = $existing_brand['id'];
                $brands_skipped++;
            } else {
                // إضافة براند جديد
                $brand_id = $db->insert(
                    "INSERT INTO brands (name) VALUES (?)",
                    [$brand_name]
                );

                if ($brand_id) {
                    $brands_added++;
                } else {
                    $errors[] = "فشل في إضافة البراند: $brand_name";
                    continue;
                }
            }

            // معالجة الموديلات
            foreach ($models as $model_data) {
                $model_name = $model_data['name'];
                $model_code = $model_data['model_code'];

                try {
                    // التحقق من وجود الموديل
                    $existing_model = $db->selectOne(
                        "SELECT id FROM models WHERE brand_id = ? AND name = ?",
                        [$brand_id, $model_name]
                    );

                    if ($existing_model) {
                        $models_skipped++;

                        // تحديث الرمز المرجعي إذا كان فارغاً في قاعدة البيانات
                        if (!empty($model_code) && empty($existing_model['model_reference'])) {
                            $db->update(
                                "UPDATE models SET model_reference = ? WHERE id = ?",
                                [$model_code, $existing_model['id']]
                            );
                        }
                    } else {
                        // إضافة موديل جديد مع الرمز المرجعي
                        if (!empty($model_code)) {
                            $model_id = $db->insert(
                                "INSERT INTO models (brand_id, name, model_reference) VALUES (?, ?, ?)",
                                [$brand_id, $model_name, $model_code]
                            );
                        } else {
                            $model_id = $db->insert(
                                "INSERT INTO models (brand_id, name) VALUES (?, ?)",
                                [$brand_id, $model_name]
                            );
                        }

                        if ($model_id) {
                            $models_added++;
                        } else {
                            $errors[] = "فشل في إضافة الموديل: $model_name";
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "خطأ في معالجة الموديل $model_name: " . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $errors[] = "خطأ في معالجة البراند $brand_name: " . $e->getMessage();
        }
    }

    // تأكيد المعاملة
    $db->commit();

    // إعداد الاستجابة
    $response = [
        'success' => true,
        'brands_added' => $brands_added,
        'brands_skipped' => $brands_skipped,
        'models_added' => $models_added,
        'models_skipped' => $models_skipped,
        'total_processed' => $brands_added + $brands_skipped,
        'total_models_processed' => $models_added + $models_skipped
    ];

    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // في حالة الخطأ - التراجع عن المعاملة
    if (isset($db)) {
        try {
            $db->rollback();
        } catch (Exception $rollback_error) {
            // تجاهل خطأ rollback إذا لم تكن هناك معاملة نشطة
        }
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
