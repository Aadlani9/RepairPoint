<?php
/**
 * RepairPoint - API إدارة قطع الغيار
 * Spare Parts Management API
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Headers para API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticación
authMiddleware();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];
$user_role = $current_user['role'];

// Obtener método y acción
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Función de respuesta
function apiResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para validar datos de entrada
function validateSparePartData($data, $isUpdate = false) {
    $errors = [];

    if (!$isUpdate && empty($data['part_name'])) {
        $errors[] = 'El nombre de la pieza es obligatorio';
    }

    if (!$isUpdate && empty($data['total_price'])) {
        $errors[] = 'El precio total es obligatorio';
    }

    if (!empty($data['total_price']) && (!is_numeric($data['total_price']) || $data['total_price'] < 0)) {
        $errors[] = 'El precio total debe ser un número válido';
    }

    if (!empty($data['cost_price']) && (!is_numeric($data['cost_price']) || $data['cost_price'] < 0)) {
        $errors[] = 'El precio de coste debe ser un número válido';
    }

    if (!empty($data['labor_cost']) && (!is_numeric($data['labor_cost']) || $data['labor_cost'] < 0)) {
        $errors[] = 'El coste de mano de obra debe ser un número válido';
    }

    if (!empty($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
        $errors[] = 'La cantidad en stock debe ser un número válido';
    }

    return $errors;
}

try {
    $db = getDB();

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'search':
                    // البحث في قطع الغيار
                    $search_term = $_GET['term'] ?? '';
                    $category = $_GET['category'] ?? '';
                    $stock_status = $_GET['stock_status'] ?? '';
                    $brand_id = intval($_GET['brand_id'] ?? 0);
                    $model_id = intval($_GET['model_id'] ?? 0);
                    $limit = intval($_GET['limit'] ?? 50);
                    $offset = intval($_GET['offset'] ?? 0);

                    $query = "SELECT sp.*, 
                                     GROUP_CONCAT(DISTINCT CONCAT(b.name, ' ', m.name) SEPARATOR ', ') as compatible_phones,
                                     COUNT(DISTINCT spc.model_id) as compatibility_count";

                    // إخفاء معلومات التكلفة للموظفين العاديين
                    if ($user_role !== 'admin') {
                        $query .= " FROM (SELECT id, shop_id, part_code, part_name, category, total_price, 
                                                stock_status, stock_quantity, warranty_days, notes, 
                                                created_at, updated_at, is_active,
                                                NULL as cost_price, NULL as labor_cost, 
                                                NULL as supplier_name, NULL as supplier_contact
                                         FROM spare_parts) sp";
                    } else {
                        $query .= " FROM spare_parts sp";
                    }

                    $query .= " LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
                               LEFT JOIN brands b ON spc.brand_id = b.id
                               LEFT JOIN models m ON spc.model_id = m.id
                               WHERE sp.shop_id = ? AND sp.is_active = TRUE";

                    $params = [$shop_id];

                    // فلاتر البحث
                    if (!empty($search_term)) {
                        $query .= " AND (sp.part_name LIKE ? OR sp.part_code LIKE ?)";
                        $search_param = '%' . $search_term . '%';
                        $params[] = $search_param;
                        $params[] = $search_param;
                    }

                    if (!empty($category)) {
                        $query .= " AND sp.category = ?";
                        $params[] = $category;
                    }

                    if (!empty($stock_status)) {
                        $query .= " AND sp.stock_status = ?";
                        $params[] = $stock_status;
                    }

                    // فلتر حسب الهاتف
                    if ($brand_id > 0 && $model_id > 0) {
                        $query .= " AND spc.brand_id = ? AND spc.model_id = ?";
                        $params[] = $brand_id;
                        $params[] = $model_id;
                    }

                    $query .= " GROUP BY sp.id ORDER BY sp.part_name LIMIT ? OFFSET ?";
                    $params[] = $limit;
                    $params[] = $offset;

                    $parts = $db->select($query, $params);

                    // إحصائيات إضافية
                    $total_query = "SELECT COUNT(DISTINCT sp.id) as total 
                                   FROM spare_parts sp 
                                   LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
                                   WHERE sp.shop_id = ? AND sp.is_active = TRUE";
                    $total_params = [$shop_id];

                    if (!empty($search_term)) {
                        $total_query .= " AND (sp.part_name LIKE ? OR sp.part_code LIKE ?)";
                        $total_params[] = '%' . $search_term . '%';
                        $total_params[] = '%' . $search_term . '%';
                    }

                    if (!empty($category)) {
                        $total_query .= " AND sp.category = ?";
                        $total_params[] = $category;
                    }

                    if (!empty($stock_status)) {
                        $total_query .= " AND sp.stock_status = ?";
                        $total_params[] = $stock_status;
                    }

                    if ($brand_id > 0 && $model_id > 0) {
                        $total_query .= " AND spc.brand_id = ? AND spc.model_id = ?";
                        $total_params[] = $brand_id;
                        $total_params[] = $model_id;
                    }

                    $total_result = $db->selectOne($total_query, $total_params);
                    $total_count = $total_result['total'] ?? 0;

                    apiResponse(true, [
                        'parts' => $parts,
                        'pagination' => [
                            'total' => $total_count,
                            'limit' => $limit,
                            'offset' => $offset,
                            'has_more' => ($offset + $limit) < $total_count
                        ]
                    ]);
                    break;

                case 'get_by_phone':
                    // الحصول على قطع الغيار لهاتف معين
                    $brand_id = intval($_GET['brand_id'] ?? 0);
                    $model_id = intval($_GET['model_id'] ?? 0);

                    if (!$brand_id || !$model_id) {
                        apiResponse(false, null, 'معرف الماركة والموديل مطلوبان', 400);
                    }

                    $query = "SELECT sp.id, sp.part_code, sp.part_name, sp.category, 
                                    sp.total_price, sp.stock_status, sp.stock_quantity, 
                                    sp.warranty_days, sp.notes";

                    if ($user_role === 'admin') {
                        $query .= ", sp.cost_price, sp.labor_cost, sp.supplier_name";
                    }

                    $query .= " FROM spare_parts sp
                               JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
                               WHERE sp.shop_id = ? AND sp.is_active = TRUE 
                               AND spc.brand_id = ? AND spc.model_id = ?
                               ORDER BY sp.category, sp.part_name";

                    $parts = $db->select($query, [$shop_id, $brand_id, $model_id]);

                    apiResponse(true, $parts);
                    break;

                case 'get_categories':
                    // الحصول على فئات قطع الغيار
                    $categories = $db->select(
                        "SELECT DISTINCT category 
                         FROM spare_parts 
                         WHERE shop_id = ? AND is_active = TRUE AND category IS NOT NULL
                         ORDER BY category",
                        [$shop_id]
                    );

                    apiResponse(true, array_column($categories, 'category'));
                    break;

                case 'get_part':
                    // الحصول على قطعة غيار واحدة
                    $part_id = intval($_GET['id'] ?? 0);

                    if (!$part_id) {
                        apiResponse(false, null, 'معرف القطعة مطلوب', 400);
                    }

                    $query = "SELECT sp.*";

                    if ($user_role !== 'admin') {
                        $query = "SELECT sp.id, sp.shop_id, sp.part_code, sp.part_name, sp.category, 
                                        sp.total_price, sp.stock_status, sp.stock_quantity, 
                                        sp.warranty_days, sp.notes, sp.created_at, sp.updated_at, sp.is_active";
                    }

                    $query .= " FROM spare_parts sp WHERE sp.id = ? AND sp.shop_id = ?";

                    $part = $db->selectOne($query, [$part_id, $shop_id]);

                    if (!$part) {
                        apiResponse(false, null, 'القطعة غير موجودة', 404);
                    }

                    // الحصول على الهواتف المتوافقة
                    $compatibility = $db->select(
                        "SELECT spc.brand_id, spc.model_id, b.name as brand_name, m.name as model_name
                         FROM spare_parts_compatibility spc
                         JOIN brands b ON spc.brand_id = b.id
                         JOIN models m ON spc.model_id = m.id
                         WHERE spc.spare_part_id = ?
                         ORDER BY b.name, m.name",
                        [$part_id]
                    );

                    $part['compatibility'] = $compatibility;

                    apiResponse(true, $part);
                    break;

                case 'stock_alerts':
                    // تنبيهات المخزون (Admin فقط)
                    if ($user_role !== 'admin') {
                        apiResponse(false, null, 'غير مصرح لك بالوصول', 403);
                    }

                    $low_stock = $db->select(
                        "SELECT * FROM low_stock_parts WHERE shop_id = ? ORDER BY stock_quantity ASC",
                        [$shop_id]
                    );

                    apiResponse(true, $low_stock);
                    break;

                default:
                    apiResponse(false, null, 'عملية غير مدعومة', 400);
            }
            break;

        case 'POST':
            switch ($action) {
                case 'add':
                    // إضافة قطعة غيار جديدة (Admin فقط)
                    if ($user_role !== 'admin') {
                        apiResponse(false, null, 'غير مصرح لك بإضافة قطع الغيار', 403);
                    }

                    $input = json_decode(file_get_contents('php://input'), true);

                    if (!$input) {
                        $input = $_POST;
                    }

                    // التحقق من صحة البيانات
                    $errors = validateSparePartData($input);
                    if (!empty($errors)) {
                        apiResponse(false, null, implode(', ', $errors), 400);
                    }

                    // إدراج القطعة الجديدة
                    $db->beginTransaction();

                    try {
                        $part_data = [
                            'shop_id' => $shop_id,
                            'part_code' => $input['part_code'] ?? null,
                            'part_name' => $input['part_name'],
                            'category' => $input['category'] ?? null,
                            'cost_price' => $input['cost_price'] ?? null,
                            'labor_cost' => $input['labor_cost'] ?? 0.00,
                            'total_price' => $input['total_price'],
                            'supplier_name' => $input['supplier_name'] ?? null,
                            'supplier_contact' => $input['supplier_contact'] ?? null,
                            'stock_status' => $input['stock_status'] ?? 'available',
                            'stock_quantity' => $input['stock_quantity'] ?? 0,
                            'min_stock_level' => $input['min_stock_level'] ?? 1,
                            'notes' => $input['notes'] ?? null,
                            'warranty_days' => $input['warranty_days'] ?? 30
                        ];

                        $part_id = $db->insert(
                            "INSERT INTO spare_parts (shop_id, part_code, part_name, category, cost_price, 
                                                     labor_cost, total_price, supplier_name, supplier_contact, 
                                                     stock_status, stock_quantity, min_stock_level, notes, warranty_days)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array_values($part_data)
                        );

                        if (!$part_id) {
                            throw new Exception('فشل في إضافة القطعة');
                        }

                        // إضافة التوافق مع الهواتف
                        if (!empty($input['compatible_phones']) && is_array($input['compatible_phones'])) {
                            foreach ($input['compatible_phones'] as $phone) {
                                if (isset($phone['brand_id']) && isset($phone['model_id'])) {
                                    $db->insert(
                                        "INSERT INTO spare_parts_compatibility (spare_part_id, brand_id, model_id, notes)
                                         VALUES (?, ?, ?, ?)",
                                        [
                                            $part_id,
                                            $phone['brand_id'],
                                            $phone['model_id'],
                                            $phone['notes'] ?? null
                                        ]
                                    );
                                }
                            }
                        }

                        $db->commit();

                        apiResponse(true, ['part_id' => $part_id], 'تم إضافة القطعة بنجاح');

                    } catch (Exception $e) {
                        $db->rollback();
                        apiResponse(false, null, 'خطأ في إضافة القطعة: ' . $e->getMessage(), 500);
                    }
                    break;

                case 'update_stock':
                    // تحديث المخزون
                    $part_id = intval($input['part_id'] ?? $_POST['part_id'] ?? 0);
                    $new_quantity = intval($input['quantity'] ?? $_POST['quantity'] ?? 0);
                    $operation = $input['operation'] ?? $_POST['operation'] ?? 'set'; // set, add, subtract

                    if (!$part_id) {
                        apiResponse(false, null, 'معرف القطعة مطلوب', 400);
                    }

                    // التحقق من وجود القطعة
                    $part = $db->selectOne(
                        "SELECT * FROM spare_parts WHERE id = ? AND shop_id = ?",
                        [$part_id, $shop_id]
                    );

                    if (!$part) {
                        apiResponse(false, null, 'القطعة غير موجودة', 404);
                    }

                    // حساب الكمية الجديدة
                    switch ($operation) {
                        case 'add':
                            $final_quantity = $part['stock_quantity'] + $new_quantity;
                            break;
                        case 'subtract':
                            $final_quantity = max(0, $part['stock_quantity'] - $new_quantity);
                            break;
                        default: // set
                            $final_quantity = max(0, $new_quantity);
                    }

                    // تحديد حالة المخزون
                    $new_status = 'available';
                    if ($final_quantity <= 0) {
                        $new_status = 'out_of_stock';
                    } elseif ($final_quantity <= $part['min_stock_level']) {
                        $new_status = 'order_required';
                    }

                    // تحديث المخزون
                    $updated = $db->update(
                        "UPDATE spare_parts SET stock_quantity = ?, stock_status = ? WHERE id = ?",
                        [$final_quantity, $new_status, $part_id]
                    );

                    if ($updated) {
                        apiResponse(true, [
                            'new_quantity' => $final_quantity,
                            'new_status' => $new_status
                        ], 'تم تحديث المخزون بنجاح');
                    } else {
                        apiResponse(false, null, 'فشل في تحديث المخزون', 500);
                    }
                    break;

                default:
                    apiResponse(false, null, 'عملية غير مدعومة', 400);
            }
            break;

        case 'PUT':
            switch ($action) {
                case 'update':
                    // تحديث قطعة غيار (Admin فقط)
                    if ($user_role !== 'admin') {
                        apiResponse(false, null, 'غير مصرح لك بتعديل قطع الغيار', 403);
                    }

                    $part_id = intval($_GET['id'] ?? 0);
                    $input = json_decode(file_get_contents('php://input'), true);

                    if (!$part_id) {
                        apiResponse(false, null, 'معرف القطعة مطلوب', 400);
                    }

                    if (!$input) {
                        apiResponse(false, null, 'بيانات غير صحيحة', 400);
                    }

                    // التحقق من وجود القطعة
                    $existing_part = $db->selectOne(
                        "SELECT * FROM spare_parts WHERE id = ? AND shop_id = ?",
                        [$part_id, $shop_id]
                    );

                    if (!$existing_part) {
                        apiResponse(false, null, 'القطعة غير موجودة', 404);
                    }

                    // التحقق من صحة البيانات
                    $errors = validateSparePartData($input, true);
                    if (!empty($errors)) {
                        apiResponse(false, null, implode(', ', $errors), 400);
                    }

                    $db->beginTransaction();

                    try {
                        // إعداد البيانات للتحديث
                        $update_fields = [];
                        $update_params = [];

                        $allowed_fields = [
                            'part_code', 'part_name', 'category', 'cost_price', 'labor_cost',
                            'total_price', 'supplier_name', 'supplier_contact', 'stock_status',
                            'stock_quantity', 'min_stock_level', 'notes', 'warranty_days'
                        ];

                        foreach ($allowed_fields as $field) {
                            if (array_key_exists($field, $input)) {
                                $update_fields[] = "$field = ?";
                                $update_params[] = $input[$field];
                            }
                        }

                        if (!empty($update_fields)) {
                            $update_params[] = $part_id;

                            $updated = $db->update(
                                "UPDATE spare_parts SET " . implode(', ', $update_fields) . " WHERE id = ?",
                                $update_params
                            );

                            if (!$updated) {
                                throw new Exception('فشل في تحديث القطعة');
                            }
                        }

                        // تحديث التوافق مع الهواتف
                        if (array_key_exists('compatible_phones', $input)) {
                            // حذف التوافق الحالي
                            $db->delete(
                                "DELETE FROM spare_parts_compatibility WHERE spare_part_id = ?",
                                [$part_id]
                            );

                            // إضافة التوافق الجديد
                            if (!empty($input['compatible_phones']) && is_array($input['compatible_phones'])) {
                                foreach ($input['compatible_phones'] as $phone) {
                                    if (isset($phone['brand_id']) && isset($phone['model_id'])) {
                                        $db->insert(
                                            "INSERT INTO spare_parts_compatibility (spare_part_id, brand_id, model_id, notes)
                                             VALUES (?, ?, ?, ?)",
                                            [
                                                $part_id,
                                                $phone['brand_id'],
                                                $phone['model_id'],
                                                $phone['notes'] ?? null
                                            ]
                                        );
                                    }
                                }
                            }
                        }

                        $db->commit();

                        apiResponse(true, ['part_id' => $part_id], 'تم تحديث القطعة بنجاح');

                    } catch (Exception $e) {
                        $db->rollback();
                        apiResponse(false, null, 'خطأ في تحديث القطعة: ' . $e->getMessage(), 500);
                    }
                    break;

                default:
                    apiResponse(false, null, 'عملية غير مدعومة', 400);
            }
            break;

        case 'DELETE':
            switch ($action) {
                case 'delete':
                    // حذف قطعة غيار (Admin فقط)
                    if ($user_role !== 'admin') {
                        apiResponse(false, null, 'غير مصرح لك بحذف قطع الغيار', 403);
                    }

                    $part_id = intval($_GET['id'] ?? 0);

                    if (!$part_id) {
                        apiResponse(false, null, 'معرف القطعة مطلوب', 400);
                    }

                    // التحقق من وجود القطعة
                    $part = $db->selectOne(
                        "SELECT * FROM spare_parts WHERE id = ? AND shop_id = ?",
                        [$part_id, $shop_id]
                    );

                    if (!$part) {
                        apiResponse(false, null, 'القطعة غير موجودة', 404);
                    }

                    // التحقق من استخدام القطعة في إصلاحات
                    $usage_count = $db->selectOne(
                        "SELECT COUNT(*) as count FROM repair_spare_parts WHERE spare_part_id = ?",
                        [$part_id]
                    );

                    if ($usage_count['count'] > 0) {
                        // إخفاء القطعة بدلاً من حذفها
                        $updated = $db->update(
                            "UPDATE spare_parts SET is_active = FALSE WHERE id = ?",
                            [$part_id]
                        );

                        if ($updated) {
                            apiResponse(true, null, 'تم إخفاء القطعة بنجاح (لا يمكن حذفها لأنها مستخدمة في إصلاحات)');
                        } else {
                            apiResponse(false, null, 'فشل في إخفاء القطعة', 500);
                        }
                    } else {
                        // حذف القطعة نهائياً
                        $deleted = $db->delete(
                            "DELETE FROM spare_parts WHERE id = ?",
                            [$part_id]
                        );

                        if ($deleted) {
                            apiResponse(true, null, 'تم حذف القطعة بنجاح');
                        } else {
                            apiResponse(false, null, 'فشل في حذف القطعة', 500);
                        }
                    }
                    break;

                default:
                    apiResponse(false, null, 'عملية غير مدعومة', 400);
            }
            break;

        default:
            apiResponse(false, null, 'طريقة HTTP غير مدعومة', 405);
    }

} catch (Exception $e) {
    error_log("Spare Parts API Error: " . $e->getMessage());
    apiResponse(false, null, 'خطأ داخلي في الخادم', 500);
}
?>