<?php
/**
 * RepairPoint - API للنماذج مع debug شامل
 * فحص مشكلة عدم ظهور النماذج
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

// Función para respuesta JSON con debug
function sendJsonResponse($success, $data = null, $message = '', $debug = null) {
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => getCurrentDateTime()
    ];

    // إضافة debug info
    if ($debug) {
        $response['debug'] = $debug;
    }

    echo json_encode($response);
    exit;
}

// Verificar autenticación
if (!isLoggedIn()) {
    sendJsonResponse(false, null, 'No autorizado');
}

try {
    $db = getDB();
    $shop_id = $_SESSION['shop_id'];

    // Obtener brand_id del parámetro GET
    $brand_id = intval($_GET['brand_id'] ?? 0);

    // Debug info inicial
    $debug_info = [
        'brand_id_received' => $_GET['brand_id'] ?? 'NOT_SET',
        'brand_id_parsed' => $brand_id,
        'shop_id' => $shop_id,
        'session_user_id' => $_SESSION['user_id'] ?? 'NOT_SET'
    ];

    if (!$brand_id) {
        sendJsonResponse(false, null, 'ID de marca requerido', $debug_info);
    }

    // Verificar que la marca existe
    $brand = $db->selectOne(
        "SELECT * FROM brands WHERE id = ?",
        [$brand_id]
    );

    $debug_info['brand_query'] = "SELECT * FROM brands WHERE id = $brand_id";
    $debug_info['brand_found'] = $brand ? true : false;

    if ($brand) {
        $debug_info['brand_data'] = $brand;
    }

    if (!$brand) {
        sendJsonResponse(false, null, 'Marca no encontrada', $debug_info);
    }

    // Verificar si la marca pertenece al shop (si el sistema usa shop isolation)
    if (isset($brand['shop_id']) && $brand['shop_id'] != $shop_id) {
        $debug_info['shop_mismatch'] = true;
        sendJsonResponse(false, null, 'Marca no pertenece al shop', $debug_info);
    }

    // Obtener modelos - intentar con y sin shop_id
    $models_query = "SELECT id, name, model_reference, brand_id, created_at FROM models WHERE brand_id = ?";
    $models_params = [$brand_id];

    // Si la tabla models tiene shop_id, agregarlo
    $table_info = $db->select("DESCRIBE models");
    $has_shop_id = false;
    foreach ($table_info as $column) {
        if ($column['Field'] === 'shop_id') {
            $has_shop_id = true;
            break;
        }
    }

    if ($has_shop_id) {
        $models_query .= " AND shop_id = ?";
        $models_params[] = $shop_id;
    }

    $models_query .= " ORDER BY name ASC";

    $debug_info['models_query'] = $models_query;
    $debug_info['models_params'] = $models_params;
    $debug_info['table_has_shop_id'] = $has_shop_id;

    $models = $db->select($models_query, $models_params);

    $debug_info['models_count'] = count($models);
    $debug_info['models_data'] = $models;

    // Verificar si hay modelos en la tabla en general
    $all_models = $db->select("SELECT id, name, brand_id FROM models WHERE brand_id = ?", [$brand_id]);
    $debug_info['all_models_for_brand'] = count($all_models);

    if (count($all_models) > 0) {
        $debug_info['all_models_sample'] = array_slice($all_models, 0, 3);
    }

    // Verificar todas las marcas disponibles
    $all_brands = $db->select("SELECT id, name FROM brands ORDER BY name LIMIT 10");
    $debug_info['available_brands'] = $all_brands;

    // Log de actividad
    logActivity('models_api_accessed', "Modelos solicitados para marca ID: $brand_id");

    sendJsonResponse(true, $models, 'Modelos obtenidos', $debug_info);

} catch (Exception $e) {
    $debug_info['error_details'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];

    error_log("Error en API models: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor', $debug_info);
}
?>