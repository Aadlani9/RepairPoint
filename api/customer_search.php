<?php
/**
 * RepairPoint - API بحث العملاء مع debug شامل
 * لحل مشكلة "No se encontraron clientes"
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

// Función para respuesta JSON con debug completo
function sendJsonResponse($success, $data = null, $message = '', $debug = null) {
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => getCurrentDateTime()
    ];

    // إضافة debug info دائماً
    if ($debug) {
        $response['debug'] = $debug;
    }

    echo json_encode($response);
    exit;
}

// Verificar autenticación
if (!isLoggedIn()) {
    sendJsonResponse(false, null, 'No autorizado', [
        'session_status' => 'No logged in',
        'session_data' => $_SESSION
    ]);
}

try {
    $db = getDB();
    $shop_id = $_SESSION['shop_id'];

    // Obtener término de búsqueda
    $search_term = trim($_GET['search'] ?? '');
    $limit = intval($_GET['limit'] ?? 10);

    // Debug info inicial
    $debug_info = [
        'search_term_received' => $_GET['search'] ?? 'NOT_SET',
        'search_term_cleaned' => $search_term,
        'shop_id' => $shop_id,
        'user_id' => $_SESSION['user_id'] ?? 'NOT_SET',
        'limit' => $limit,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI']
    ];

    if (empty($search_term)) {
        sendJsonResponse(false, null, 'Término de búsqueda requerido', $debug_info);
    }

    if (strlen($search_term) < 3) {
        sendJsonResponse(false, null, 'Mínimo 3 caracteres para búsqueda', $debug_info);
    }

    // Limpiar término de búsqueda
    $clean_term = '%' . $search_term . '%';
    $debug_info['clean_term'] = $clean_term;

    // Primero, vamos a verificar si hay datos en la tabla repairs
    $total_repairs = $db->selectOne("SELECT COUNT(*) as total FROM repairs WHERE shop_id = ?", [$shop_id]);
    $debug_info['total_repairs_in_shop'] = $total_repairs['total'] ?? 0;

    // Verificar si hay repairs con customer_name no vacío
    $repairs_with_names = $db->selectOne("SELECT COUNT(*) as total FROM repairs WHERE shop_id = ? AND customer_name IS NOT NULL AND customer_name != ''", [$shop_id]);
    $debug_info['repairs_with_names'] = $repairs_with_names['total'] ?? 0;

    // Verificar algunos ejemplos de customer_name
    $sample_names = $db->select("SELECT customer_name, customer_phone FROM repairs WHERE shop_id = ? AND customer_name IS NOT NULL AND customer_name != '' LIMIT 5", [$shop_id]);
    $debug_info['sample_customer_names'] = $sample_names;

    // Ahora intentar la búsqueda principal
    $base_query = "SELECT 
            customer_name,
            customer_phone,
            COUNT(*) as total_repairs,
            MAX(created_at) as last_repair_date,
            SUM(CASE 
                WHEN actual_cost IS NOT NULL AND actual_cost > 0 THEN actual_cost 
                WHEN estimated_cost IS NOT NULL AND estimated_cost > 0 THEN estimated_cost 
                ELSE 0 
            END) as total_spent
        FROM repairs r
        WHERE r.shop_id = ? 
        AND (
            r.customer_phone LIKE ? OR 
            r.customer_name LIKE ?
        )
        GROUP BY r.customer_phone, r.customer_name
        ORDER BY last_repair_date DESC
        LIMIT ?";

    $debug_info['query'] = $base_query;
    $debug_info['query_params'] = [$shop_id, $clean_term, $clean_term, $limit];

    $customers = $db->select($base_query, [$shop_id, $clean_term, $clean_term, $limit]);

    $debug_info['customers_found'] = count($customers);
    $debug_info['raw_customers'] = $customers;

    // Si no encontramos nada, intentar búsqueda más simple
    if (empty($customers)) {
        $debug_info['trying_simple_search'] = true;

        // Búsqueda simple solo por nombre
        $simple_customers = $db->select(
            "SELECT customer_name, customer_phone FROM repairs WHERE shop_id = ? AND customer_name LIKE ? LIMIT 3",
            [$shop_id, $clean_term]
        );
        $debug_info['simple_search_results'] = $simple_customers;

        // Búsqueda simple solo por teléfono
        $phone_customers = $db->select(
            "SELECT customer_name, customer_phone FROM repairs WHERE shop_id = ? AND customer_phone LIKE ? LIMIT 3",
            [$shop_id, $clean_term]
        );
        $debug_info['phone_search_results'] = $phone_customers;

        // Búsqueda sin shop_id (para verificar si el problema es shop isolation)
        $no_shop_customers = $db->select(
            "SELECT customer_name, customer_phone FROM repairs WHERE customer_name LIKE ? LIMIT 3",
            [$clean_term]
        );
        $debug_info['no_shop_search_results'] = $no_shop_customers;
    }

    // Si encontramos clientes, formatear la respuesta
    $formatted_customers = [];
    foreach ($customers as $customer) {
        // تنسيق رقم الهاتف
        $formatted_phone = formatPhoneNumber($customer['customer_phone']);

        // تنسيق آخر تاريخ إصلاح
        $last_repair_formatted = '';
        if ($customer['last_repair_date']) {
            $last_repair_formatted = formatDate($customer['last_repair_date'], 'd/m/Y');
        }

        // تنسيق إجمالي المبلغ
        $total_spent_formatted = number_format($customer['total_spent'], 2) . '€';

        $formatted_customers[] = [
            'name' => $customer['customer_name'],
            'phone' => $customer['customer_phone'],
            'phone_formatted' => $formatted_phone,
            'total_repairs' => intval($customer['total_repairs']),
            'last_repair_date' => $customer['last_repair_date'],
            'last_repair_formatted' => $last_repair_formatted,
            'total_spent' => floatval($customer['total_spent']),
            'total_spent_formatted' => $total_spent_formatted,
            'recent_devices' => '', // مؤقتاً
            'customer_type' => $customer['total_repairs'] >= 3 ? 'frequent' : 'regular'
        ];
    }

    $debug_info['formatted_customers'] = $formatted_customers;
    $debug_info['final_count'] = count($formatted_customers);

    // Log de actividad
    logActivity('customer_search', "Búsqueda de clientes: $search_term", $_SESSION['user_id']);

    if (count($formatted_customers) > 0) {
        sendJsonResponse(true, $formatted_customers, 'Búsqueda completada', $debug_info);
    } else {
        sendJsonResponse(false, [], 'No se encontraron clientes', $debug_info);
    }

} catch (Exception $e) {
    $debug_info['error_details'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    error_log("Error en customer search API: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor', $debug_info);
}
?>