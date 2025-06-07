
<?php
/**
 * RepairPoint - Funciones Auxiliares
 * Funciones de utilidad para el sistema
 */

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso denegado');
}

// ===================================================
// FUNCIONES DE AUTENTICACIÓN Y SEGURIDAD
// ===================================================

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Verificar si el usuario es staff
 */
function isStaff() {
    return hasRole('staff');
}

/**
 * Redireccionar si no está logueado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . url('pages/login.php'));
        exit;
    }
}

/**
 * Redireccionar si no es admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setMessage('No tienes permisos para acceder a esta página', MSG_ERROR);
        header('Location: ' . url('pages/dashboard.php'));
        exit;
    }
}

/**
 * Hash de contraseña segura
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generar token aleatorio
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// ===================================================
// FUNCIONES DE MENSAJES Y NOTIFICACIONES
// ===================================================

/**
 * Establecer mensaje de sesión
 */
function setMessage($message, $type = MSG_INFO) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Obtener y limpiar mensaje de sesión
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = [
            'text' => $_SESSION['message'],
            'type' => $_SESSION['message_type'] ?? MSG_INFO
        ];
        unset($_SESSION['message'], $_SESSION['message_type']);
        return $message;
    }
    return null;
}

/**
 * Mostrar mensaje si existe
 */
function displayMessage() {
    $message = getMessage();
    if ($message) {
        $iconClass = [
            MSG_SUCCESS => 'bi-check-circle',
            MSG_ERROR => 'bi-exclamation-triangle',
            MSG_WARNING => 'bi-exclamation-triangle',
            MSG_INFO => 'bi-info-circle'
        ][$message['type']] ?? 'bi-info-circle';
        
        echo '<div class="alert alert-' . $message['type'] . ' alert-dismissible fade show" role="alert">';
        echo '<i class="bi ' . $iconClass . ' me-2"></i>';
        echo htmlspecialchars($message['text']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// ===================================================
// FUNCIONES DE VALIDACIÓN
// ===================================================

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar teléfono español - محسن
 */
function isValidPhone($phone) {
    // إزالة جميع المسافات والرموز
    $clean_phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

    // أنماط الهواتف الإسبانية المقبولة
    $patterns = [
        '/^\+34[6789]\d{8}$/',    // +34xxxxxxxxx
        '/^0034[6789]\d{8}$/',    // 0034xxxxxxxxx
        '/^34[6789]\d{8}$/',      // 34xxxxxxxxx
        '/^[6789]\d{8}$/',        // xxxxxxxxx (9 أرقام تبدأ بـ 6-9)
    ];

    // فحص جميع الأنماط
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $clean_phone)) {
            return true;
        }
    }

    return false;
}

/**
 * تنسيق رقم الهاتف للعرض
 */
function formatPhoneNumber($phone) {
    $clean_phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

    // إذا كان الرقم يبدأ بـ +34
    if (preg_match('/^\+34([6789]\d{8})$/', $clean_phone, $matches)) {
        return '+34 ' . substr($matches[1], 0, 3) . ' ' . substr($matches[1], 3, 3) . ' ' . substr($matches[1], 6, 3);
    }

    // إذا كان الرقم يبدأ بـ 34
    if (preg_match('/^34([6789]\d{8})$/', $clean_phone, $matches)) {
        return '+34 ' . substr($matches[1], 0, 3) . ' ' . substr($matches[1], 3, 3) . ' ' . substr($matches[1], 6, 3);
    }

    // إذا كان 9 أرقام فقط
    if (preg_match('/^([6789]\d{8})$/', $clean_phone, $matches)) {
        return '+34 ' . substr($matches[1], 0, 3) . ' ' . substr($matches[1], 3, 3) . ' ' . substr($matches[1], 6, 3);
    }

    return $phone; // إرجاع الرقم كما هو إذا لم يطابق أي نمط
}

/**
 * Limpiar string para evitar XSS
 */
function cleanString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar datos requeridos
 */
function validateRequired($data, $required_fields) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es obligatorio";
        }
    }
    
    return $errors;
}

/**
 * Sanitizar array de datos
 */
function sanitizeArray($array) {
    $sanitized = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value);
        } else {
            $sanitized[$key] = cleanString($value);
        }
    }
    return $sanitized;
}

// ===================================================
// FUNCIONES DE FECHA Y HORA
// ===================================================

/**
 * Formatear fecha para mostrar
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Formatear fecha solo día
 */
function formatDateOnly($date) {
    return formatDate($date, 'd/m/Y');
}

/**
 * Formatear fecha y hora
 */
function formatDateTime($date) {
    return formatDate($date, 'd/m/Y H:i');
}

/**
 * Obtener fecha actual en formato MySQL
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Calcular días transcurridos
 */
function daysSince($date) {
    try {
        $dateObj = new DateTime($date);
        $now = new DateTime();
        $interval = $now->diff($dateObj);
        return $interval->days;
    } catch (Exception $e) {
        return 0;
    }
}

// ===================================================
// FUNCIONES DE ESTADOS Y PRIORIDADES
// ===================================================

/**
 * Obtener badge HTML para estado
 */
function getStatusBadge($status) {
    $statuses = getConfig('repair_status');
    
    if (!isset($statuses[$status])) {
        return '<span class="badge bg-secondary">Desconocido</span>';
    }
    
    $config = $statuses[$status];
    return '<span class="badge bg-' . $config['color'] . '">' . $config['name'] . '</span>';
}

/**
 * Obtener nombre del estado
 */
function getStatusName($status) {
    $statuses = getConfig('repair_status');
    return $statuses[$status]['name'] ?? 'Desconocido';
}

/**
 * Obtener badge HTML para prioridad
 */
function getPriorityBadge($priority) {
    $priorities = getConfig('repair_priority');
    
    if (!isset($priorities[$priority])) {
        return '<span class="text-secondary">-</span>';
    }
    
    $config = $priorities[$priority];
    return '<span class="text-' . $config['color'] . ' fw-bold">' . $config['name'] . '</span>';
}

// ===================================================
// FUNCIONES DE PAGINACIÓN
// ===================================================

/**
 * Generar enlaces de paginación
 */
function generatePagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Paginación">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Página anterior
    if ($current_page > 1) {
        $prev_url = $base_url . '?' . http_build_query(array_merge($params, ['page' => $current_page - 1]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '">Anterior</a></li>';
    }
    
    // Páginas numéricas
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $current_page) ? ' active' : '';
        $page_url = $base_url . '?' . http_build_query(array_merge($params, ['page' => $i]));
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    // Página siguiente
    if ($current_page < $total_pages) {
        $next_url = $base_url . '?' . http_build_query(array_merge($params, ['page' => $current_page + 1]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $next_url . '">Siguiente</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Calcular offset para paginación
 */
function calculateOffset($page, $limit) {
    return ($page - 1) * $limit;
}

/**
 * Calcular total de páginas
 */
function calculateTotalPages($total_records, $limit) {
    return ceil($total_records / $limit);
}

// ===================================================
// FUNCIONES DE ARCHIVO Y SUBIDA
// ===================================================

/**
 * Validar archivo subido
 */
function validateUploadedFile($file, $allowed_types = ALLOWED_IMAGE_TYPES, $max_size = MAX_FILE_SIZE) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error al subir el archivo';
        return $errors;
    }
    
    // Verificar tamaño
    if ($file['size'] > $max_size) {
        $errors[] = 'El archivo es demasiado grande. Máximo: ' . formatBytes($max_size);
    }
    
    // Verificar tipo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        $errors[] = 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowed_types);
    }
    
    return $errors;
}

/**
 * Formatear bytes en tamaño legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Generar nombre único para archivo
 */
function generateUniqueFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

// ===================================================
// FUNCIONES DE LOGGING Y DEBUG
// ===================================================

/**
 * Log de actividad
 */
function logActivity($action, $details = '', $user_id = null) {
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    $log_entry = [
        'timestamp' => getCurrentDateTime(),
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // En un entorno de producción, esto se guardaría en base de datos
    if (isDebugMode()) {
        error_log('ACTIVITY LOG: ' . json_encode($log_entry));
    }
}

/**
 * Debug dump solo en modo desarrollo
 */
function debugDump($var, $label = '') {
    if (isDebugMode()) {
        echo '<pre style="background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; margin: 10px 0;">';
        if ($label) echo '<strong>' . $label . ':</strong><br>';
        var_dump($var);
        echo '</pre>';
    }
}

// ===================================================
// FUNCIONES ESPECÍFICAS DEL NEGOCIO
// ===================================================

/**
 * Generar referencia única para reparación
 */
function generateRepairReference() {
    return uniqid() . date('dmY');
}

/**
 * Obtener estadísticas del dashboard
 */
function getDashboardStats($shop_id) {
    $db = getDB();
    
    $stats = [];
    
    // Reparaciones activas
    $stats['active'] = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status IN ('pending', 'in_progress', 'completed')",
        [$shop_id]
    )['count'] ?? 0;
    
    // Reparaciones entregadas hoy
    $stats['delivered_today'] = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status = 'delivered' AND DATE(delivered_at) = CURDATE()",
        [$shop_id]
    )['count'] ?? 0;
    
    // Reparaciones pendientes
    $stats['pending'] = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status = 'pending'",
        [$shop_id]
    )['count'] ?? 0;
    
    // Total del mes
    $stats['month_total'] = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
        [$shop_id]
    )['count'] ?? 0;
    
    return $stats;
}

/**
 * Buscar reparaciones
 */
function searchRepairs($shop_id, $query, $limit = 20) {
    $db = getDB();
    
    $sql = "SELECT r.*, b.name as brand_name, m.name as model_name, u.name as created_by_name
            FROM repairs r 
            JOIN brands b ON r.brand_id = b.id 
            JOIN models m ON r.model_id = m.id 
            JOIN users u ON r.created_by = u.id
            WHERE r.shop_id = ? AND (
                r.reference LIKE ? OR 
                r.customer_name LIKE ? OR 
                r.customer_phone LIKE ? OR
                b.name LIKE ? OR
                m.name LIKE ?
            )
            ORDER BY r.created_at DESC 
            LIMIT ?";
    
    $search_term = '%' . $query . '%';
    
    return $db->select($sql, [
        $shop_id, 
        $search_term, 
        $search_term, 
        $search_term, 
        $search_term, 
        $search_term, 
        $limit
    ]);
}

/**
 * Obtener próximas reparaciones a entregar
 */
function getUpcomingDeliveries($shop_id, $days = 7) {
    $db = getDB();
    
    return $db->select(
        "SELECT r.*, b.name as brand_name, m.name as model_name
         FROM repairs r 
         JOIN brands b ON r.brand_id = b.id 
         JOIN models m ON r.model_id = m.id
         WHERE r.shop_id = ? 
         AND r.status = 'completed' 
         AND r.estimated_completion <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
         ORDER BY r.estimated_completion ASC",
        [$shop_id, $days]
    );
}


/**
 * Safe htmlspecialchars - يتعامل مع null values
 */
function safeHtml($value, $default = '') {
    if ($value === null || $value === '') {
        return htmlspecialchars($default, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Safe form data preparation
 */
function safeFormData($data, $defaults = []) {
    $safe_defaults = [
        'customer_name' => '',
        'customer_phone' => '',
        'brand_id' => '',
        'model_id' => '',
        'issue_description' => '',
        'priority' => 'medium',
        'estimated_cost' => '',
        'actual_cost' => '',
        'notes' => '',
        'status' => 'pending',
        'reference' => '',
        'received_at' => '',
        'completed_at' => '',
        'delivered_at' => '',
        'created_at' => '',
        'updated_at' => ''
    ];

    // دمج القيم الافتراضية مع المخصصة
    $merged_defaults = array_merge($safe_defaults, $defaults);

    // دمج البيانات الفعلية مع التأكد من وجود جميع المفاتيح
    $safe_data = [];
    foreach ($merged_defaults as $key => $default_value) {
        $safe_data[$key] = isset($data[$key]) ? $data[$key] : $default_value;
    }

    return $safe_data;
}

/**
 * Safe option selected - للـ select options
 */
function safeSelected($current_value, $option_value, $default = '') {
    $current = $current_value ?? $default;
    return ($current == $option_value) ? 'selected' : '';
}

/**
 * Safe checked - للـ checkboxes و radio buttons
 */
function safeChecked($current_value, $check_value, $default = false) {
    $current = $current_value ?? $default;
    return ($current == $check_value) ? 'checked' : '';
}

/**
 * Safe number formatting - يتعامل مع null values
 */
function safeNumber($value, $decimals = 2, $default = 0) {
    if ($value === null || $value === '' || !is_numeric($value)) {
        return number_format($default, $decimals);
    }
    return number_format(floatval($value), $decimals);
}

/**
 * Safe date formatting - نسخة محسنة
 */
function safeDateFormat($date, $format = 'd/m/Y H:i', $default = '') {
    if (empty($date) || $date === null || $date === '0000-00-00 00:00:00') {
        return $default;
    }

    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Safe array value - الحصول على قيمة من array بأمان
 */
function safeArrayValue($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Safe integer conversion
 */
function safeInt($value, $default = 0) {
    if ($value === null || $value === '') {
        return $default;
    }
    return intval($value);
}

/**
 * Safe float conversion
 */
function safeFloat($value, $default = 0.0) {
    if ($value === null || $value === '' || !is_numeric($value)) {
        return $default;
    }
    return floatval($value);
}

/**
 * Safe string truncation
 */
function safeTruncate($string, $length = 50, $suffix = '...') {
    if ($string === null || $string === '') {
        return '';
    }

    if (mb_strlen($string) <= $length) {
        return $string;
    }

    return mb_substr($string, 0, $length) . $suffix;
}

?>