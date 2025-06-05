<?php
/**
 * RepairPoint - API de Búsqueda
 * Búsqueda rápida de reparaciones
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
    
    // Obtener parámetros de búsqueda
    $query = trim($_GET['q'] ?? '');
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $status = $_GET['status'] ?? '';
    
    if (strlen($query) < 2) {
        sendJsonResponse(false, null, 'Consulta muy corta (mínimo 2 caracteres)');
    }
    
    // Construir consulta SQL
    $where_conditions = ["r.shop_id = ?"];
    $params = [$shop_id];
    
    // Búsqueda en múltiples campos
    $search_conditions = [
        "r.reference LIKE ?",
        "r.customer_name LIKE ?", 
        "r.customer_phone LIKE ?",
        "b.name LIKE ?",
        "m.name LIKE ?",
        "r.issue_description LIKE ?"
    ];
    
    $search_term = '%' . $query . '%';
    $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
    $params = array_merge($params, array_fill(0, count($search_conditions), $search_term));
    
    // Filtro por estado
    if ($status && in_array($status, ['pending', 'in_progress', 'completed', 'delivered'])) {
        $where_conditions[] = "r.status = ?";
        $params[] = $status;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Ejecutar búsqueda
    $results = $db->select(
        "SELECT r.id, r.reference, r.customer_name, r.customer_phone, 
                r.status, r.priority, r.received_at, r.issue_description,
                b.name as brand_name, m.name as model_name,
                u.name as created_by_name
         FROM repairs r 
         JOIN brands b ON r.brand_id = b.id 
         JOIN models m ON r.model_id = m.id 
         JOIN users u ON r.created_by = u.id
         WHERE $where_clause
         ORDER BY 
            CASE r.status
                WHEN 'pending' THEN 1
                WHEN 'in_progress' THEN 2  
                WHEN 'completed' THEN 3
                WHEN 'delivered' THEN 4
            END,
            r.received_at DESC
         LIMIT ?",
        array_merge($params, [$limit])
    );
    
    // Formatear resultados
    $formatted_results = array_map(function($repair) {
        return [
            'id' => $repair['id'],
            'reference' => $repair['reference'],
            'customer_name' => $repair['customer_name'],
            'customer_phone' => $repair['customer_phone'],
            'device' => $repair['brand_name'] . ' ' . $repair['model_name'],
            'brand_name' => $repair['brand_name'],
            'model_name' => $repair['model_name'],
            'status' => $repair['status'],
            'status_name' => getStatusName($repair['status']),
            'priority' => $repair['priority'],
            'received_at' => $repair['received_at'],
            'received_formatted' => formatDateTime($repair['received_at']),
            'issue_preview' => mb_strimwidth($repair['issue_description'], 0, 100, '...'),
            'created_by' => $repair['created_by_name'],
            'url' => url('pages/repair_details.php?id=' . $repair['id'])
        ];
    }, $results);
    
    // Log de actividad
    logActivity('search_api_used', "Búsqueda: '$query' - " . count($results) . " resultados");
    
    sendJsonResponse(true, $formatted_results, count($results) . ' resultados encontrados');
    
} catch (Exception $e) {
    error_log("Error en API search: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor');
}
?>  