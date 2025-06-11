<?php
/**
 * RepairPoint - Shop Setup API Enhanced
 * واجهة برمجية لإعداد المحلات مع إدارة الماركات والموديلات
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Headers para API
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'shop_setup.php';

// Función para respuesta JSON
function sendJsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => getCurrentDateTime()
    ]);
    exit;
}

// Verificar autenticación
if (!isLoggedIn()) {
    sendJsonResponse(false, null, 'No autorizado', 401);
}

// التحقق من أن المستخدم هو admin
if (!isAdmin()) {
    sendJsonResponse(false, null, 'Se requieren permisos de administrador', 403);
}

$shop_id = $_SESSION['shop_id'];
$shop_setup = getShopSetup();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($method) {
        case 'GET':
            handleGetRequest($action, $shop_id, $shop_setup);
            break;

        case 'POST':
            handlePostRequest($action, $shop_id, $shop_setup);
            break;

        case 'PUT':
            handlePutRequest($action, $shop_id);
            break;

        case 'DELETE':
            handleDeleteRequest($action, $shop_id);
            break;

        default:
            sendJsonResponse(false, null, 'Método no permitido', 405);
    }

} catch (Exception $e) {
    error_log("Error en Shop Setup API: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor', 500);
}

/**
 * معالجة طلبات GET
 */
function handleGetRequest($action, $shop_id, $shop_setup) {
    switch ($action) {
        case 'check_status':
            // التحقق من حالة الإعداد
            $is_complete = $shop_setup->isShopSetupComplete($shop_id);
            $stats = $shop_setup->getShopSetupStats($shop_id);

            sendJsonResponse(true, [
                'setup_completed' => $is_complete,
                'stats' => $stats
            ], 'حالة الإعداد تم جلبها بنجاح');
            break;

        case 'get_available_brands':
            // الحصول على البراندات المتاحة
            $brands = $shop_setup->getAvailableBrands();
            $brands_with_count = $shop_setup->getBrandModelsCount();

            sendJsonResponse(true, [
                'brands' => $brands,
                'brands_with_models_count' => $brands_with_count
            ], 'البراندات المتاحة تم جلبها بنجاح');
            break;

        case 'get_available_categories':
            // الحصول على فئات المشاكل المتاحة
            $categories = $shop_setup->getAvailableIssueCategories();
            $categories_with_count = $shop_setup->getCategoryIssuesCount();

            sendJsonResponse(true, [
                'categories' => $categories,
                'categories_with_issues_count' => $categories_with_count
            ], 'فئات المشاكل المتاحة تم جلبها بنجاح');
            break;

        case 'get_setup_info':
            // الحصول على معلومات الإعداد الكاملة
            $setup_info = getShopSetupInfo($shop_id);

            sendJsonResponse(true, $setup_info, 'معلومات الإعداد تم جلبها بنجاح');
            break;

        case 'get_current_data':
            // الحصول على البيانات الحالية للمحل
            $db = getDB();

            $current_data = [
                'brands' => $db->select(
                    "SELECT id, name, created_at FROM brands WHERE shop_id = ? ORDER BY name",
                    [$shop_id]
                ),
                'models_count_by_brand' => $db->select(
                    "SELECT b.id, b.name, COUNT(m.id) as models_count
                     FROM brands b
                     LEFT JOIN models m ON b.id = m.brand_id
                     WHERE b.shop_id = ?
                     GROUP BY b.id, b.name
                     ORDER BY b.name",
                    [$shop_id]
                ),
                'issues_by_category' => $db->select(
                    "SELECT category, COUNT(*) as issues_count
                     FROM common_issues 
                     WHERE shop_id = ?
                     GROUP BY category
                     ORDER BY category",
                    [$shop_id]
                ),
                'total_stats' => $shop_setup->getShopSetupStats($shop_id)
            ];

            sendJsonResponse(true, $current_data, 'البيانات الحالية تم جلبها بنجاح');
            break;

        // ==================================================
        // Brand Management - GET Actions
        // ==================================================

        case 'get_brands':
            // جلب جميع الماركات للمحل
            getBrands($shop_id);
            break;

        case 'get_brand':
            // جلب ماركة محددة
            $brand_id = intval($_GET['id'] ?? 0);
            if (!$brand_id) {
                sendJsonResponse(false, null, 'معرف الماركة مطلوب', 400);
            }
            getBrand($shop_id, $brand_id);
            break;

        case 'get_brand_models':
            // جلب موديلات ماركة محددة
            $brand_id = intval($_GET['brand_id'] ?? 0);
            if (!$brand_id) {
                sendJsonResponse(false, null, 'معرف الماركة مطلوب', 400);
            }
            getBrandModels($shop_id, $brand_id);
            break;

        case 'get_models':
            // جلب جميع الموديلات
            getModels($shop_id);
            break;

        default:
            sendJsonResponse(false, null, 'إجراء غير مدعوم', 400);
    }
}

/**
 * معالجة طلبات POST
 */
function handlePostRequest($action, $shop_id, $shop_setup) {
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        sendJsonResponse(false, null, 'Token de seguridad inválido', 403);
    }

    switch ($action) {
        case 'quick_setup':
            // الإعداد السريع (جميع البيانات)
            $result = quickSetupShop($shop_id);

            if ($result['success']) {
                logActivity('quick_shop_setup', "إعداد سريع للمحل مكتمل", $_SESSION['user_id']);
                sendJsonResponse(true, $result['data'], $result['message']);
            } else {
                sendJsonResponse(false, $result['data'], $result['message'], 400);
            }
            break;

        case 'custom_setup':
            // الإعداد المخصص
            $selected_brands = $_POST['selected_brands'] ?? [];
            $selected_categories = $_POST['selected_categories'] ?? [];

            // التحقق من صحة البيانات
            if (empty($selected_brands) && empty($selected_categories)) {
                sendJsonResponse(false, null, 'يجب اختيار براند واحد على الأقل أو فئة واحدة', 400);
            }

            $result = customSetupShop($shop_id, $selected_brands, $selected_categories);

            if ($result['success']) {
                logActivity('custom_shop_setup', "إعداد مخصص للمحل مكتمل", $_SESSION['user_id']);
                sendJsonResponse(true, $result['data'], $result['message']);
            } else {
                sendJsonResponse(false, $result['data'], $result['message'], 400);
            }
            break;

        case 'reset_setup':
            // إعادة تشغيل الإعداد
            $confirm = $_POST['confirm'] ?? false;

            if (!$confirm) {
                sendJsonResponse(false, null, 'تأكيد إعادة التشغيل مطلوب', 400);
            }

            // خيارات إعادة الإعداد
            $options = [
                'setup_brands' => $_POST['setup_brands'] ?? true,
                'setup_models' => $_POST['setup_models'] ?? true,
                'setup_issues' => $_POST['setup_issues'] ?? true,
                'selected_brands' => $_POST['selected_brands'] ?? [],
                'selected_categories' => $_POST['selected_categories'] ?? []
            ];

            $result = $shop_setup->resetShopSetup($shop_id, $options);

            if ($result['success']) {
                logActivity('shop_setup_reset', "إعادة تشغيل إعداد المحل", $_SESSION['user_id']);
                sendJsonResponse(true, $result['data'], $result['message']);
            } else {
                sendJsonResponse(false, $result['data'], $result['message'], 400);
            }
            break;

        case 'add_single_brand':
            // إضافة براند واحد مع موديلاته
            $brand_template_id = intval($_POST['brand_template_id'] ?? 0);
            $include_models = $_POST['include_models'] ?? true;

            if (!$brand_template_id) {
                sendJsonResponse(false, null, 'معرف البراند مطلوب', 400);
            }

            $result = $shop_setup->setupBrandsAndModels(
                $shop_id,
                [$brand_template_id],
                $include_models
            );

            if ($result['brands_added'] > 0) {
                logActivity('single_brand_added', "إضافة براند واحد للمحل", $_SESSION['user_id']);
                sendJsonResponse(true, $result, 'تم إضافة البراند بنجاح');
            } else {
                sendJsonResponse(false, $result, 'فشل في إضافة البراند', 400);
            }
            break;

        case 'add_single_category':
            // إضافة فئة مشاكل واحدة
            $category = trim($_POST['category'] ?? '');

            if (empty($category)) {
                sendJsonResponse(false, null, 'اسم الفئة مطلوب', 400);
            }

            $result = $shop_setup->setupCommonIssues($shop_id, [$category]);

            if ($result['issues_added'] > 0) {
                logActivity('single_category_added', "إضافة فئة مشاكل للمحل: $category", $_SESSION['user_id']);
                sendJsonResponse(true, $result, 'تم إضافة الفئة بنجاح');
            } else {
                sendJsonResponse(false, $result, 'فشل في إضافة الفئة', 400);
            }
            break;

        // ==================================================
        // Brand Management - POST Actions
        // ==================================================

        case 'add_custom_brand':
            // إضافة ماركة مخصصة
            addCustomBrand($shop_id);
            break;

        case 'add_model':
            // إضافة موديل جديد
            addModel($shop_id);
            break;

        case 'add_issue':
            // إضافة مشكلة شائعة
            addCommonIssue($shop_id);
            break;

        case 'validate_setup_data':
            // التحقق من صحة بيانات الإعداد
            $selected_brands = $_POST['selected_brands'] ?? [];
            $selected_categories = $_POST['selected_categories'] ?? [];

            $validation_result = [
                'valid' => true,
                'warnings' => [],
                'estimated_data' => [
                    'brands_count' => 0,
                    'models_count' => 0,
                    'issues_count' => 0
                ]
            ];

            // حساب عدد البراندات والموديلات المتوقع
            if (!empty($selected_brands)) {
                $db = getDB();

                $placeholders = str_repeat('?,', count($selected_brands) - 1) . '?';

                // عدد البراندات
                $validation_result['estimated_data']['brands_count'] = count($selected_brands);

                // عدد الموديلات
                $models_count = $db->selectOne(
                    "SELECT COUNT(*) as count FROM default_models_template 
                     WHERE brand_template_id IN ($placeholders) AND is_active = TRUE",
                    $selected_brands
                )['count'] ?? 0;

                $validation_result['estimated_data']['models_count'] = $models_count;
            } else {
                $validation_result['warnings'][] = 'لم يتم اختيار أي براند';
            }

            // حساب عدد المشاكل المتوقع
            if (!empty($selected_categories)) {
                $db = getDB();

                $placeholders = str_repeat('?,', count($selected_categories) - 1) . '?';

                $issues_count = $db->selectOne(
                    "SELECT COUNT(*) as count FROM default_issues_template 
                     WHERE category IN ($placeholders) AND is_active = TRUE",
                    $selected_categories
                )['count'] ?? 0;

                $validation_result['estimated_data']['issues_count'] = $issues_count;
            } else {
                $validation_result['warnings'][] = 'لم يتم اختيار أي فئة مشاكل';
            }

            // التحقق من وجود بيانات حالية
            if (hasShopDefaultData($shop_id)) {
                $validation_result['warnings'][] = 'المحل يحتوي على بيانات مسبقة، سيتم دمج البيانات الجديدة';
            }

            // التحقق من صحة الاختيارات
            if (empty($selected_brands) && empty($selected_categories)) {
                $validation_result['valid'] = false;
                $validation_result['warnings'][] = 'يجب اختيار براند واحد على الأقل أو فئة مشاكل واحدة';
            }

            sendJsonResponse(true, $validation_result, 'تم التحقق من البيانات');
            break;

        case 'preview_setup':
            // معاينة الإعداد بدون تطبيق
            $selected_brands = $_POST['selected_brands'] ?? [];
            $selected_categories = $_POST['selected_categories'] ?? [];

            $preview_data = [
                'brands' => [],
                'categories' => [],
                'summary' => [
                    'total_brands' => 0,
                    'total_models' => 0,
                    'total_issues' => 0
                ]
            ];

            $db = getDB();

            // معاينة البراندات والموديلات
            if (!empty($selected_brands)) {
                $placeholders = str_repeat('?,', count($selected_brands) - 1) . '?';

                $brands_preview = $db->select(
                    "SELECT dbt.id, dbt.name, COUNT(dmt.id) as models_count
                     FROM default_brands_template dbt
                     LEFT JOIN default_models_template dmt ON dbt.id = dmt.brand_template_id AND dmt.is_active = TRUE
                     WHERE dbt.id IN ($placeholders) AND dbt.is_active = TRUE
                     GROUP BY dbt.id, dbt.name
                     ORDER BY dbt.name",
                    $selected_brands
                );

                $preview_data['brands'] = $brands_preview;
                $preview_data['summary']['total_brands'] = count($brands_preview);
                $preview_data['summary']['total_models'] = array_sum(array_column($brands_preview, 'models_count'));
            }

            // معاينة فئات المشاكل
            if (!empty($selected_categories)) {
                $placeholders = str_repeat('?,', count($selected_categories) - 1) . '?';

                $categories_preview = $db->select(
                    "SELECT category, COUNT(*) as issues_count
                     FROM default_issues_template
                     WHERE category IN ($placeholders) AND is_active = TRUE
                     GROUP BY category
                     ORDER BY category",
                    $selected_categories
                );

                $preview_data['categories'] = $categories_preview;
                $preview_data['summary']['total_issues'] = array_sum(array_column($categories_preview, 'issues_count'));
            }

            sendJsonResponse(true, $preview_data, 'معاينة الإعداد تم جلبها بنجاح');
            break;

        default:
            sendJsonResponse(false, null, 'إجراء غير مدعوم', 400);
    }
}

/**
 * معالجة طلبات PUT
 */
function handlePutRequest($action, $shop_id) {
    // الحصول على بيانات PUT
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendJsonResponse(false, null, 'بيانات غير صحيحة', 400);
    }

    switch ($action) {
        case 'update_brand':
            updateBrand($shop_id, $input);
            break;

        case 'update_model':
            updateModel($shop_id, $input);
            break;

        case 'update_issue':
            updateCommonIssue($shop_id, $input);
            break;

        default:
            sendJsonResponse(false, null, 'إجراء غير مدعوم', 400);
    }
}

/**
 * معالجة طلبات DELETE
 */
function handleDeleteRequest($action, $shop_id) {
    switch ($action) {
        case 'delete_brand':
            $brand_id = intval($_GET['id'] ?? 0);
            if (!$brand_id) {
                sendJsonResponse(false, null, 'معرف الماركة مطلوب', 400);
            }
            deleteBrand($shop_id, $brand_id);
            break;

        case 'delete_model':
            $model_id = intval($_GET['id'] ?? 0);
            if (!$model_id) {
                sendJsonResponse(false, null, 'معرف الموديل مطلوب', 400);
            }
            deleteModel($shop_id, $model_id);
            break;

        case 'delete_issue':
            $issue_id = intval($_GET['id'] ?? 0);
            if (!$issue_id) {
                sendJsonResponse(false, null, 'معرف المشكلة مطلوب', 400);
            }
            deleteCommonIssue($shop_id, $issue_id);
            break;

        default:
            sendJsonResponse(false, null, 'إجراء غير مدعوم', 400);
    }
}

// ==================================================
// دوال إدارة الماركات - GET
// ==================================================

/**
 * جلب جميع الماركات
 */
function getBrands($shop_id) {
    $db = getDB();

    $brands = $db->select(
        "SELECT b.*, COUNT(m.id) as models_count,
                (SELECT COUNT(*) FROM repairs WHERE brand_id = b.id) as repairs_count
         FROM brands b
         LEFT JOIN models m ON b.id = m.brand_id AND m.shop_id = b.shop_id
         WHERE b.shop_id = ?
         GROUP BY b.id, b.name
         ORDER BY b.name",
        [$shop_id]
    );

    sendJsonResponse(true, $brands, 'تم جلب الماركات بنجاح');
}

/**
 * جلب ماركة محددة
 */
function getBrand($shop_id, $brand_id) {
    $db = getDB();

    $brand = $db->selectOne(
        "SELECT b.*, COUNT(m.id) as models_count,
                (SELECT COUNT(*) FROM repairs WHERE brand_id = b.id) as repairs_count
         FROM brands b
         LEFT JOIN models m ON b.id = m.brand_id AND m.shop_id = b.shop_id
         WHERE b.id = ? AND b.shop_id = ?
         GROUP BY b.id, b.name",
        [$brand_id, $shop_id]
    );

    if (!$brand) {
        sendJsonResponse(false, null, 'الماركة غير موجودة', 404);
    }

    sendJsonResponse(true, $brand, 'تم جلب الماركة بنجاح');
}

/**
 * جلب موديلات ماركة محددة
 */
function getBrandModels($shop_id, $brand_id) {
    $db = getDB();

    // التحقق من وجود الماركة
    $brand = $db->selectOne("SELECT id, name FROM brands WHERE id = ? AND shop_id = ?", [$brand_id, $shop_id]);

    if (!$brand) {
        sendJsonResponse(false, null, 'الماركة غير موجودة', 404);
    }

    $models = $db->select(
        "SELECT m.*, 
                (SELECT COUNT(*) FROM repairs WHERE model_id = m.id) as repairs_count
         FROM models m
         WHERE m.brand_id = ? AND m.shop_id = ?
         ORDER BY m.name",
        [$brand_id, $shop_id]
    );

    sendJsonResponse(true, [
        'brand' => $brand,
        'models' => $models
    ], 'تم جلب موديلات الماركة بنجاح');
}

/**
 * جلب جميع الموديلات
 */
function getModels($shop_id) {
    $db = getDB();

    $models = $db->select(
        "SELECT m.*, b.name as brand_name,
                (SELECT COUNT(*) FROM repairs WHERE model_id = m.id) as repairs_count
         FROM models m
         JOIN brands b ON m.brand_id = b.id AND m.shop_id = b.shop_id
         WHERE m.shop_id = ?
         ORDER BY b.name, m.name",
        [$shop_id]
    );

    sendJsonResponse(true, $models, 'تم جلب الموديلات بنجاح');
}

// ==================================================
// دوال إدارة الماركات - POST
// ==================================================

/**
 * إضافة ماركة مخصصة
 */
function addCustomBrand($shop_id) {
    $brand_name = trim($_POST['name'] ?? '');

    if (empty($brand_name)) {
        sendJsonResponse(false, null, 'اسم الماركة مطلوب', 400);
    }

    if (strlen($brand_name) < 2 || strlen($brand_name) > 50) {
        sendJsonResponse(false, null, 'اسم الماركة يجب أن يكون بين 2 و 50 حرف', 400);
    }

    $db = getDB();

    // التحقق من عدم وجود الماركة مسبقاً
    $existing = $db->selectOne(
        "SELECT id FROM brands WHERE LOWER(name) = LOWER(?) AND shop_id = ?",
        [$brand_name, $shop_id]
    );

    if ($existing) {
        sendJsonResponse(false, null, 'الماركة موجودة مسبقاً', 400);
    }

    try {
        $brand_id = $db->insert(
            "INSERT INTO brands (name, shop_id, created_at) VALUES (?, ?, NOW())",
            [$brand_name, $shop_id]
        );

        if ($brand_id) {
            logActivity('custom_brand_added', "إضافة ماركة مخصصة: $brand_name", $_SESSION['user_id']);

            $brand = $db->selectOne(
                "SELECT * FROM brands WHERE id = ?",
                [$brand_id]
            );

            sendJsonResponse(true, $brand, 'تم إضافة الماركة بنجاح');
        } else {
            throw new Exception('فشل في إضافة الماركة');
        }

    } catch (Exception $e) {
        error_log("خطأ في إضافة ماركة مخصصة: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في إضافة الماركة', 500);
    }
}

/**
 * إضافة موديل جديد
 */
function addModel($shop_id) {
    $model_name = trim($_POST['name'] ?? '');
    $brand_id = intval($_POST['brand_id'] ?? 0);

    if (empty($model_name) || !$brand_id) {
        sendJsonResponse(false, null, 'اسم الموديل ومعرف الماركة مطلوبان', 400);
    }

    if (strlen($model_name) < 1 || strlen($model_name) > 100) {
        sendJsonResponse(false, null, 'اسم الموديل يجب أن يكون بين 1 و 100 حرف', 400);
    }

    $db = getDB();

    // التحقق من وجود الماركة
    $brand = $db->selectOne(
        "SELECT id, name FROM brands WHERE id = ? AND shop_id = ?",
        [$brand_id, $shop_id]
    );

    if (!$brand) {
        sendJsonResponse(false, null, 'الماركة غير موجودة', 404);
    }

    // التحقق من عدم وجود الموديل مسبقاً
    $existing = $db->selectOne(
        "SELECT id FROM models WHERE LOWER(name) = LOWER(?) AND brand_id = ? AND shop_id = ?",
        [$model_name, $brand_id, $shop_id]
    );

    if ($existing) {
        sendJsonResponse(false, null, 'الموديل موجود مسبقاً لهذه الماركة', 400);
    }

    try {
        $model_id = $db->insert(
            "INSERT INTO models (name, brand_id, shop_id, created_at) VALUES (?, ?, ?, NOW())",
            [$model_name, $brand_id, $shop_id]
        );

        if ($model_id) {
            logActivity('custom_model_added', "إضافة موديل مخصص: {$brand['name']} $model_name", $_SESSION['user_id']);

            $model = $db->selectOne(
                "SELECT m.*, b.name as brand_name FROM models m 
                 JOIN brands b ON m.brand_id = b.id 
                 WHERE m.id = ?",
                [$model_id]
            );

            sendJsonResponse(true, $model, 'تم إضافة الموديل بنجاح');
        } else {
            throw new Exception('فشل في إضافة الموديل');
        }

    } catch (Exception $e) {
        error_log("خطأ في إضافة موديل مخصص: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في إضافة الموديل', 500);
    }
}

/**
 * إضافة مشكلة شائعة
 */
function addCommonIssue($shop_id) {
    $issue_text = trim($_POST['issue_text'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (empty($issue_text) || empty($category)) {
        sendJsonResponse(false, null, 'نص المشكلة والفئة مطلوبان', 400);
    }

    if (strlen($issue_text) < 5 || strlen($issue_text) > 255) {
        sendJsonResponse(false, null, 'نص المشكلة يجب أن يكون بين 5 و 255 حرف', 400);
    }

    $db = getDB();

    // التحقق من عدم وجود المشكلة مسبقاً
    $existing = $db->selectOne(
        "SELECT id FROM common_issues WHERE LOWER(issue_text) = LOWER(?) AND shop_id = ?",
        [$issue_text, $shop_id]
    );

    if ($existing) {
        sendJsonResponse(false, null, 'المشكلة موجودة مسبقاً', 400);
    }

    try {
        $issue_id = $db->insert(
            "INSERT INTO common_issues (issue_text, category, shop_id, created_at) VALUES (?, ?, ?, NOW())",
            [$issue_text, $category, $shop_id]
        );

        if ($issue_id) {
            logActivity('custom_issue_added', "إضافة مشكلة مخصصة: $issue_text", $_SESSION['user_id']);

            $issue = $db->selectOne(
                "SELECT * FROM common_issues WHERE id = ?",
                [$issue_id]
            );

            sendJsonResponse(true, $issue, 'تم إضافة المشكلة بنجاح');
        } else {
            throw new Exception('فشل في إضافة المشكلة');
        }

    } catch (Exception $e) {
        error_log("خطأ في إضافة مشكلة مخصصة: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في إضافة المشكلة', 500);
    }
}

// ==================================================
// دوال إدارة الماركات - PUT (Update)
// ==================================================

/**
 * تحديث ماركة
 */
function updateBrand($shop_id, $input) {
    $brand_id = intval($input['id'] ?? 0);
    $brand_name = trim($input['name'] ?? '');

    if (!$brand_id || empty($brand_name)) {
        sendJsonResponse(false, null, 'معرف الماركة واسمها مطلوبان', 400);
    }

    if (strlen($brand_name) < 2 || strlen($brand_name) > 50) {
        sendJsonResponse(false, null, 'اسم الماركة يجب أن يكون بين 2 و 50 حرف', 400);
    }

    $db = getDB();

    // التحقق من وجود الماركة
    $brand = $db->selectOne(
        "SELECT * FROM brands WHERE id = ? AND shop_id = ?",
        [$brand_id, $shop_id]
    );

    if (!$brand) {
        sendJsonResponse(false, null, 'الماركة غير موجودة', 404);
    }

    // التحقق من عدم وجود اسم الماركة مع ماركة أخرى
    $existing = $db->selectOne(
        "SELECT id FROM brands WHERE LOWER(name) = LOWER(?) AND shop_id = ? AND id != ?",
        [$brand_name, $shop_id, $brand_id]
    );

    if ($existing) {
        sendJsonResponse(false, null, 'اسم الماركة مستخدم مسبقاً', 400);
    }

    try {
        $updated = $db->update(
            "UPDATE brands SET name = ?, updated_at = NOW() WHERE id = ? AND shop_id = ?",
            [$brand_name, $brand_id, $shop_id]
        );

        if ($updated !== false) {
            logActivity('brand_updated', "تحديث ماركة: {$brand['name']} إلى $brand_name", $_SESSION['user_id']);

            $updated_brand = $db->selectOne(
                "SELECT b.*, COUNT(m.id) as models_count FROM brands b
                 LEFT JOIN models m ON b.id = m.brand_id AND m.shop_id = b.shop_id
                 WHERE b.id = ? AND b.shop_id = ?
                 GROUP BY b.id",
                [$brand_id, $shop_id]
            );

            sendJsonResponse(true, $updated_brand, 'تم تحديث الماركة بنجاح');
        } else {
            throw new Exception('فشل في تحديث الماركة');
        }

    } catch (Exception $e) {
        error_log("خطأ في تحديث ماركة: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في تحديث الماركة', 500);
    }
}

/**
 * تحديث موديل
 */
function updateModel($shop_id, $input) {
    $model_id = intval($input['id'] ?? 0);
    $model_name = trim($input['name'] ?? '');
    $brand_id = intval($input['brand_id'] ?? 0);

    if (!$model_id || empty($model_name) || !$brand_id) {
        sendJsonResponse(false, null, 'معرف الموديل واسمه ومعرف الماركة مطلوبان', 400);
    }

    if (strlen($model_name) < 1 || strlen($model_name) > 100) {
        sendJsonResponse(false, null, 'اسم الموديل يجب أن يكون بين 1 و 100 حرف', 400);
    }

    $db = getDB();

    // التحقق من وجود الموديل
    $model = $db->selectOne(
        "SELECT * FROM models WHERE id = ? AND shop_id = ?",
        [$model_id, $shop_id]
    );

    if (!$model) {
        sendJsonResponse(false, null, 'الموديل غير موجود', 404);
    }

    // التحقق من وجود الماركة الجديدة
    $brand = $db->selectOne(
        "SELECT id, name FROM brands WHERE id = ? AND shop_id = ?",
        [$brand_id, $shop_id]
    );

    if (!$brand) {
        sendJsonResponse(false, null, 'الماركة المحددة غير موجودة', 404);
    }

    // التحقق من عدم وجود اسم الموديل مع نفس الماركة
    $existing = $db->selectOne(
        "SELECT id FROM models WHERE LOWER(name) = LOWER(?) AND brand_id = ? AND shop_id = ? AND id != ?",
        [$model_name, $brand_id, $shop_id, $model_id]
    );

    if ($existing) {
        sendJsonResponse(false, null, 'اسم الموديل مستخدم مسبقاً لهذه الماركة', 400);
    }

    try {
        $updated = $db->update(
            "UPDATE models SET name = ?, brand_id = ?, updated_at = NOW() WHERE id = ? AND shop_id = ?",
            [$model_name, $brand_id, $model_id, $shop_id]
        );

        if ($updated !== false) {
            logActivity('model_updated', "تحديث موديل: {$model['name']} إلى $model_name", $_SESSION['user_id']);

            $updated_model = $db->selectOne(
                "SELECT m.*, b.name as brand_name FROM models m 
                 JOIN brands b ON m.brand_id = b.id 
                 WHERE m.id = ? AND m.shop_id = ?",
                [$model_id, $shop_id]
            );

            sendJsonResponse(true, $updated_model, 'تم تحديث الموديل بنجاح');
        } else {
            throw new Exception('فشل في تحديث الموديل');
        }

    } catch (Exception $e) {
        error_log("خطأ في تحديث موديل: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في تحديث الموديل', 500);
    }
}

/**
 * تحديث مشكلة شائعة
 */
function updateCommonIssue($shop_id, $input) {
    $issue_id = intval($input['id'] ?? 0);
    $issue_text = trim($input['issue_text'] ?? '');
    $category = trim($input['category'] ?? '');

    if (!$issue_id || empty($issue_text) || empty($category)) {
        sendJsonResponse(false, null, 'معرف المشكلة ونصها وفئتها مطلوبان', 400);
    }

    if (strlen($issue_text) < 5 || strlen($issue_text) > 255) {
        sendJsonResponse(false, null, 'نص المشكلة يجب أن يكون بين 5 و 255 حرف', 400);
    }

    $db = getDB();

    // التحقق من وجود المشكلة
    $issue = $db->selectOne(
        "SELECT * FROM common_issues WHERE id = ? AND shop_id = ?",
        [$issue_id, $shop_id]
    );

    if (!$issue) {
        sendJsonResponse(false, null, 'المشكلة غير موجودة', 404);
    }

    // التحقق من عدم وجود نص المشكلة مع مشكلة أخرى
    $existing = $db->selectOne(
        "SELECT id FROM common_issues WHERE LOWER(issue_text) = LOWER(?) AND shop_id = ? AND id != ?",
        [$issue_text, $shop_id, $issue_id]
    );

    if ($existing) {
        sendJsonResponse(false, null, 'نص المشكلة مستخدم مسبقاً', 400);
    }

    try {
        $updated = $db->update(
            "UPDATE common_issues SET issue_text = ?, category = ?, updated_at = NOW() WHERE id = ? AND shop_id = ?",
            [$issue_text, $category, $issue_id, $shop_id]
        );

        if ($updated !== false) {
            logActivity('issue_updated', "تحديث مشكلة: {$issue['issue_text']} إلى $issue_text", $_SESSION['user_id']);

            $updated_issue = $db->selectOne(
                "SELECT * FROM common_issues WHERE id = ? AND shop_id = ?",
                [$issue_id, $shop_id]
            );

            sendJsonResponse(true, $updated_issue, 'تم تحديث المشكلة بنجاح');
        } else {
            throw new Exception('فشل في تحديث المشكلة');
        }

    } catch (Exception $e) {
        error_log("خطأ في تحديث مشكلة: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في تحديث المشكلة', 500);
    }
}

// ==================================================
// دوال إدارة الماركات - DELETE
// ==================================================

/**
 * حذف ماركة
 */
function deleteBrand($shop_id, $brand_id) {
    $db = getDB();

    // التحقق من وجود الماركة
    $brand = $db->selectOne(
        "SELECT * FROM brands WHERE id = ? AND shop_id = ?",
        [$brand_id, $shop_id]
    );

    if (!$brand) {
        sendJsonResponse(false, null, 'الماركة غير موجودة', 404);
    }

    // التحقق من وجود إصلاحات مرتبطة
    $repairs_count = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE brand_id = ? AND shop_id = ?",
        [$brand_id, $shop_id]
    )['count'] ?? 0;

    if ($repairs_count > 0) {
        sendJsonResponse(false, null, "لا يمكن حذف الماركة لأنها مرتبطة بـ $repairs_count إصلاح", 400);
    }

    try {
        $db->beginTransaction();

        // حذف جميع الموديلات المرتبطة أولاً
        $db->delete("DELETE FROM models WHERE brand_id = ? AND shop_id = ?", [$brand_id, $shop_id]);

        // حذف الماركة
        $deleted = $db->delete("DELETE FROM brands WHERE id = ? AND shop_id = ?", [$brand_id, $shop_id]);

        if ($deleted) {
            $db->commit();
            logActivity('brand_deleted', "حذف ماركة: {$brand['name']}", $_SESSION['user_id']);
            sendJsonResponse(true, null, 'تم حذف الماركة وجميع موديلاتها بنجاح');
        } else {
            $db->rollback();
            throw new Exception('فشل في حذف الماركة');
        }

    } catch (Exception $e) {
        $db->rollback();
        error_log("خطأ في حذف ماركة: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في حذف الماركة', 500);
    }
}

/**
 * حذف موديل
 */
function deleteModel($shop_id, $model_id) {
    $db = getDB();

    // التحقق من وجود الموديل
    $model = $db->selectOne(
        "SELECT m.*, b.name as brand_name FROM models m 
         JOIN brands b ON m.brand_id = b.id 
         WHERE m.id = ? AND m.shop_id = ?",
        [$model_id, $shop_id]
    );

    if (!$model) {
        sendJsonResponse(false, null, 'الموديل غير موجود', 404);
    }

    // التحقق من وجود إصلاحات مرتبطة
    $repairs_count = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE model_id = ? AND shop_id = ?",
        [$model_id, $shop_id]
    )['count'] ?? 0;

    if ($repairs_count > 0) {
        sendJsonResponse(false, null, "لا يمكن حذف الموديل لأنه مرتبط بـ $repairs_count إصلاح", 400);
    }

    try {
        $deleted = $db->delete("DELETE FROM models WHERE id = ? AND shop_id = ?", [$model_id, $shop_id]);

        if ($deleted) {
            logActivity('model_deleted', "حذف موديل: {$model['brand_name']} {$model['name']}", $_SESSION['user_id']);
            sendJsonResponse(true, null, 'تم حذف الموديل بنجاح');
        } else {
            throw new Exception('فشل في حذف الموديل');
        }

    } catch (Exception $e) {
        error_log("خطأ في حذف موديل: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في حذف الموديل', 500);
    }
}

/**
 * حذف مشكلة شائعة
 */
function deleteCommonIssue($shop_id, $issue_id) {
    $db = getDB();

    // التحقق من وجود المشكلة
    $issue = $db->selectOne(
        "SELECT * FROM common_issues WHERE id = ? AND shop_id = ?",
        [$issue_id, $shop_id]
    );

    if (!$issue) {
        sendJsonResponse(false, null, 'المشكلة غير موجودة', 404);
    }

    try {
        $deleted = $db->delete("DELETE FROM common_issues WHERE id = ? AND shop_id = ?", [$issue_id, $shop_id]);

        if ($deleted) {
            logActivity('issue_deleted', "حذف مشكلة: {$issue['issue_text']}", $_SESSION['user_id']);
            sendJsonResponse(true, null, 'تم حذف المشكلة بنجاح');
        } else {
            throw new Exception('فشل في حذف المشكلة');
        }

    } catch (Exception $e) {
        error_log("خطأ في حذف مشكلة: " . $e->getMessage());
        sendJsonResponse(false, null, 'فشل في حذف المشكلة', 500);
    }
}

// ==================================================
// دوال التحقق والتأكيد - Functions من الملف الأصلي
// ==================================================

/**
 * التحقق من صحة معرفات البراندات
 */
function validateBrandIds($brand_ids) {
    if (empty($brand_ids) || !is_array($brand_ids)) {
        return false;
    }

    $db = getDB();
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';

    $valid_count = $db->selectOne(
        "SELECT COUNT(*) as count FROM default_brands_template 
         WHERE id IN ($placeholders) AND is_active = TRUE",
        $brand_ids
    )['count'] ?? 0;

    return $valid_count === count($brand_ids);
}

/**
 * التحقق من صحة أسماء الفئات
 */
function validateCategoryNames($categories) {
    if (empty($categories) || !is_array($categories)) {
        return false;
    }

    $db = getDB();
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';

    $valid_count = $db->selectOne(
        "SELECT COUNT(DISTINCT category) as count FROM default_issues_template 
         WHERE category IN ($placeholders) AND is_active = TRUE",
        $categories
    )['count'] ?? 0;

    return $valid_count === count($categories);
}

/**
 * التحقق من وجود بيانات افتراضية للمحل
 */
function hasShopDefaultData($shop_id) {
    $db = getDB();

    $brands_count = $db->selectOne("SELECT COUNT(*) as count FROM brands WHERE shop_id = ?", [$shop_id])['count'] ?? 0;
    $issues_count = $db->selectOne("SELECT COUNT(*) as count FROM common_issues WHERE shop_id = ?", [$shop_id])['count'] ?? 0;

    return ($brands_count > 0 || $issues_count > 0);
}

/**
 * تسجيل النشاط
 */
function logActivity($action, $description, $user_id) {
    try {
        $db = getDB();
        $db->insert(
            "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())",
            [$user_id, $action, $description]
        );
    } catch (Exception $e) {
        error_log("خطأ في تسجيل النشاط: " . $e->getMessage());
    }
}

?>