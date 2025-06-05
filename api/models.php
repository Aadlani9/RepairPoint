<?php
/**
 * RepairPoint - API para Modelos
 * Obtiene modelos según la marca seleccionada
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

// Función para respuesta JSON
function sendJsonResponse($success, $data = null, $message = '') {
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
    sendJsonResponse(false, null, 'No autorizado');
}

try {
    $db = getDB();
    $shop_id = $_SESSION['shop_id'];
    
    // Obtener brand_id del parámetro GET
    $brand_id = intval($_GET['brand_id'] ?? 0);
    
    if (!$brand_id) {
        sendJsonResponse(false, null, 'ID de marca requerido');
    }
    
    // Verificar que la marca existe
    $brand = $db->selectOne("SELECT * FROM brands WHERE id = ?", [$brand_id]);
    
    if (!$brand) {
        sendJsonResponse(false, null, 'Marca no encontrada');
    }
    
    // Obtener modelos de la marca
    $models = $db->select(
        "SELECT id, name FROM models WHERE brand_id = ? ORDER BY name",
        [$brand_id]
    );
    
    // Log de actividad
    logActivity('models_api_accessed', "Modelos cargados para marca ID: $brand_id");
    
    sendJsonResponse(true, $models, 'Modelos obtenidos correctamente');
    
} catch (Exception $e) {
    error_log("Error en API models: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor');
}
?>