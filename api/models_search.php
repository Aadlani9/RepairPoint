<?php
/**
 * RepairPoint - API البحث السريع في الموديلات
 * Models Quick Search API
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Headers para API
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Función para respuesta JSON
function sendJsonResponse($success, $data = null, $message = '') {
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => getCurrentDateTime()
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar autenticación
if (!isLoggedIn()) {
    sendJsonResponse(false, null, 'No autorizado');
}

try {
    $db = getDB();
    $shop_id = $_SESSION['shop_id'];

    // Obtener parámetro البحث
    $search_term = trim($_GET['term'] ?? $_GET['search'] ?? '');
    $limit = intval($_GET['limit'] ?? 20);
    $limit = min($limit, 50); // Maximum 50 results

    if (empty($search_term)) {
        sendJsonResponse(false, null, 'Término de búsqueda requerido');
    }

    if (strlen($search_term) < 2) {
        sendJsonResponse(false, null, 'El término de búsqueda debe tener al menos 2 caracteres');
    }

    // البحث في الموديلات
    // يبحث في: اسم الموديل، المعرّف، واسم الماركة
    $query = "
        SELECT
            m.id AS model_id,
            m.name AS model_name,
            m.model_reference,
            b.id AS brand_id,
            b.name AS brand_name,
            CONCAT(
                b.name,
                ' ',
                m.name,
                CASE
                    WHEN m.model_reference IS NOT NULL THEN CONCAT(' (', m.model_reference, ')')
                    ELSE ''
                END
            ) AS display_name,
            -- Relevance score للترتيب
            CASE
                -- تطابق تام مع المعرّف (أعلى أولوية)
                WHEN m.model_reference = ? THEN 1
                -- المعرّف يبدأ بالنص المبحوث
                WHEN m.model_reference LIKE CONCAT(?, '%') THEN 2
                -- اسم الموديل يبدأ بالنص المبحوث
                WHEN m.name LIKE CONCAT(?, '%') THEN 3
                -- اسم الماركة يبدأ بالنص المبحوث
                WHEN b.name LIKE CONCAT(?, '%') THEN 4
                -- المعرّف يحتوي على النص
                WHEN m.model_reference LIKE CONCAT('%', ?, '%') THEN 5
                -- اسم الموديل يحتوي على النص
                WHEN m.name LIKE CONCAT('%', ?, '%') THEN 6
                -- اسم الماركة يحتوي على النص
                WHEN b.name LIKE CONCAT('%', ?, '%') THEN 7
                -- النص الكامل يحتوي على البحث
                ELSE 8
            END AS relevance
        FROM models m
        JOIN brands b ON m.brand_id = b.id
        WHERE
            m.name LIKE CONCAT('%', ?, '%')
            OR m.model_reference LIKE CONCAT('%', ?, '%')
            OR b.name LIKE CONCAT('%', ?, '%')
            OR CONCAT(b.name, ' ', m.name) LIKE CONCAT('%', ?, '%')
        ORDER BY relevance ASC, b.name ASC, m.name ASC
        LIMIT ?
    ";

    $search_param = $search_term;

    $models = $db->select($query, [
        $search_param, // للتطابق التام
        $search_param, // model_reference LIKE
        $search_param, // model name LIKE
        $search_param, // brand name LIKE
        $search_param, // model_reference contains
        $search_param, // model name contains
        $search_param, // brand name contains
        $search_param, // WHERE model name
        $search_param, // WHERE model reference
        $search_param, // WHERE brand name
        $search_param, // WHERE concat
        $limit
    ]);

    // تنسيق النتائج
    $results = [];
    foreach ($models as $model) {
        $results[] = [
            'model_id' => intval($model['model_id']),
            'model_name' => $model['model_name'],
            'model_reference' => $model['model_reference'],
            'brand_id' => intval($model['brand_id']),
            'brand_name' => $model['brand_name'],
            'display_name' => $model['display_name'],
            'label' => $model['display_name'], // لـ autocomplete
            'value' => $model['display_name']  // لـ autocomplete
        ];
    }

    sendJsonResponse(true, $results, count($results) . ' modelo(s) encontrado(s)');

} catch (Exception $e) {
    error_log("Error en API models_search: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor');
}
?>
