<?php
/**
 * RepairPoint - API para Problemas Comunes
 * Obtiene lista de problemas frecuentes
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
    
    // Obtener parámetros
    $category = trim($_GET['category'] ?? '');
    
    // Construir consulta
    $where_clause = '';
    $params = [];
    
    if ($category) {
        $where_clause = 'WHERE category = ?';
        $params[] = $category;
    }
    
    // Obtener problemas comunes
    $issues = $db->select(
        "SELECT id, issue_text, category, created_at 
         FROM common_issues 
         $where_clause
         ORDER BY category, issue_text",
        $params
    );
    
    // Agrupar por categoría
    $grouped_issues = [];
    foreach ($issues as $issue) {
        $cat = $issue['category'] ?: 'General';
        if (!isset($grouped_issues[$cat])) {
            $grouped_issues[$cat] = [];
        }
        $grouped_issues[$cat][] = [
            'id' => $issue['id'],
            'text' => $issue['issue_text'],
            'category' => $issue['category']
        ];
    }
    
    // Obtener categorías disponibles
    $categories = $db->select(
        "SELECT DISTINCT category FROM common_issues 
         WHERE category IS NOT NULL AND category != ''
         ORDER BY category"
    );
    
    $response_data = [
        'issues' => $issues,
        'grouped' => $grouped_issues,
        'categories' => array_column($categories, 'category'),
        'total' => count($issues)
    ];
    
    sendJsonResponse(true, $response_data, 'Problemas comunes obtenidos');
    
} catch (Exception $e) {
    error_log("Error en API common-issues: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error interno del servidor');
}
?>