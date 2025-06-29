
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
/**
 * إصلاح دالة generatePagination - استبدل الدالة الموجودة بهذه
 */
function generatePagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) return '';

    // إصلاح المسار - إزالة BASE_URL إذا كان موجود
    $clean_url = str_replace(BASE_URL, '', $base_url);
    $clean_url = ltrim($clean_url, '/');

    $html = '<nav aria-label="Paginación">';
    $html .= '<ul class="pagination justify-content-center">';

    // عرض معلومات الصفحة
    $start_record = ($current_page - 1) * (defined('RECORDS_PER_PAGE') ? RECORDS_PER_PAGE : 20) + 1;
    $end_record = min($current_page * (defined('RECORDS_PER_PAGE') ? RECORDS_PER_PAGE : 20), $total_pages * (defined('RECORDS_PER_PAGE') ? RECORDS_PER_PAGE : 20));

    // زر "الأولى" (إذا لم نكن في الصفحة الأولى)
    if ($current_page > 1) {
        $first_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => 1]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $first_url . '" title="الصفحة الأولى"><i class="bi bi-chevron-double-left"></i></a></li>';
    }

    // الصفحة السابقة
    if ($current_page > 1) {
        $prev_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => $current_page - 1]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '" title="الصفحة السابقة">السابق</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">السابق</span></li>';
    }

    // الصفحات المرقمة
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    // إذا كنا في بداية القائمة، أظهر المزيد في النهاية
    if ($start <= 3) {
        $end = min($total_pages, 5);
    }

    // إذا كنا في نهاية القائمة، أظهر المزيد في البداية
    if ($end >= $total_pages - 2) {
        $start = max(1, $total_pages - 4);
    }

    // نقاط البداية إذا لزم الأمر
    if ($start > 1) {
        $page_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => 1]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $page_url . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // الصفحات المرقمة الأساسية
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $current_page) ? ' active' : '';
        $page_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => $i]));
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }

    // نقاط النهاية إذا لزم الأمر
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $page_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => $total_pages]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $page_url . '">' . $total_pages . '</a></li>';
    }

    // الصفحة التالية
    if ($current_page < $total_pages) {
        $next_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => $current_page + 1]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $next_url . '" title="الصفحة التالية">التالي</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">التالي</span></li>';
    }

    // زر "الأخيرة" (إذا لم نكن في الصفحة الأخيرة)
    if ($current_page < $total_pages) {
        $last_url = $clean_url . '?' . http_build_query(array_merge($params, ['page' => $total_pages]));
        $html .= '<li class="page-item"><a class="page-link" href="' . $last_url . '" title="الصفحة الأخيرة"><i class="bi bi-chevron-double-right"></i></a></li>';
    }

    $html .= '</ul>';

    // إضافة معلومات الصفحة
    $total_records = $total_pages * (defined('RECORDS_PER_PAGE') ? RECORDS_PER_PAGE : 20);
    $html .= '<div class="pagination-info text-center mt-2">';
    $html .= '<small class="text-muted">';
    $html .= 'الصفحة ' . $current_page . ' من ' . $total_pages;
    $html .= ' (' . number_format($total_records) . ' عنصر إجمالي)';
    $html .= '</small>';
    $html .= '</div>';

    $html .= '</nav>';
    return $html;
}

/**
 * دالة مساعدة لحساب معلومات الصفحة الحالية
 */
function getPaginationInfo($current_page, $total_pages, $records_per_page) {
    $total_records = $total_pages * $records_per_page;
    $start_record = ($current_page - 1) * $records_per_page + 1;
    $end_record = min($current_page * $records_per_page, $total_records);

    return [
        'start' => $start_record,
        'end' => $end_record,
        'total' => $total_records,
        'current_page' => $current_page,
        'total_pages' => $total_pages
    ];
}

/**
 * تحسين دالة calculateTotalPages - إضافة حماية من القسمة على صفر
 */
function calculateTotalPages($total_records, $limit) {
    if ($limit <= 0) return 1;
    return max(1, ceil($total_records / $limit));
}

/**
 * تحسين دالة calculateOffset - إضافة حماية
 */
function calculateOffset($page, $limit) {
    $page = max(1, intval($page));
    $limit = max(1, intval($limit));
    return ($page - 1) * $limit;
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
    $db = getDB();

    try {
        // الحصول على آخر رقم متسلسل من قاعدة البيانات
        $last_repair = $db->selectOne(
            "SELECT id FROM repairs ORDER BY id DESC LIMIT 1"
        );

        // تحديد الرقم المتسلسل التالي
        $next_number = $last_repair ? ($last_repair['id'] + 1) : 1;

        // تنسيق التاريخ: dMYYY (يوم + شهر + 3 أرقام من السنة)
        // مثال: 6 ديسمبر 2025 = 6122025 -> 612025
        $date_format = date('jn') . date('Y'); // يوم بدون صفر + شهر بدون صفر + سنة كاملة

        // دمج الرقم مع التاريخ
        $reference = $next_number . $date_format;

        return $reference;

    } catch (Exception $e) {
        // في حالة الخطأ، استخدم timestamp بسيط
        error_log("خطأ في generateRepairReference: " . $e->getMessage());
        return time() . date('jn'); // timestamp + يوم وشهر
    }
}

/**
 * نسخة محسنة أكثر - تأخذ shop_id في الاعتبار
 * لضمان عدم التكرار بين المحلات المختلفة
 */
function generateRepairReferenceAdvanced($shop_id) {
    $db = getDB();

    try {
        // الحصول على آخر رقم متسلسل لهذا المحل فقط
        $last_repair = $db->selectOne(
            "SELECT id FROM repairs WHERE shop_id = ? ORDER BY id DESC LIMIT 1",
            [$shop_id]
        );

        // عد الإصلاحات في هذا المحل للحصول على رقم متسلسل محلي
        $repairs_count = $db->selectOne(
            "SELECT COUNT(*) as count FROM repairs WHERE shop_id = ?",
            [$shop_id]
        );

        $next_number = ($repairs_count['count'] ?? 0) + 1;

        // تنسيق التاريخ المختصر: يوم + شهر + آخر رقمين من السنة
        $date_format = date('jn') . date('y'); // مثال: 6122025 -> 61225

        // دمج الرقم مع التاريخ
        $reference = $next_number . $date_format;

        return $reference;

    } catch (Exception $e) {
        error_log("خطأ في generateRepairReferenceAdvanced: " . $e->getMessage());
        return rand(1, 999) . date('jny'); // رقم عشوائي + تاريخ مختصر
    }
}

/**
 * نسخة بسيطة جداً - للاستخدام المباشر
 * الشكل: رقم الإصلاح + يوم + شهر + سنة مختصرة
 */
function generateSimpleReference() {
    static $counter = null;

    if ($counter === null) {
        // محاولة الحصول على آخر رقم من قاعدة البيانات
        try {
            $db = getDB();
            $last_id = $db->selectOne("SELECT MAX(id) as max_id FROM repairs");
            $counter = ($last_id['max_id'] ?? 0) + 1;
        } catch (Exception $e) {
            // في حالة فشل قاعدة البيانات، ابدأ من رقم عشوائي
            $counter = rand(1, 100);
        }
    } else {
        $counter++;
    }

    // تنسيق: رقم + يوم + شهر + آخر رقمين من السنة
    // مثال: 16 ديسمبر 2025 = 1 + 6 + 12 + 25 = 161225
    $reference = $counter . date('jn') . date('y');

    return $reference;
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



function getLogoUrl($logo_path) {
    if (empty($logo_path)) {
        return asset('images/default-logo.png'); // شعار افتراضي
    }

    // إذا كان المسار يبدأ بـ assets/ فقط
    if (strpos($logo_path, 'assets/') === 0) {
        return url($logo_path);
    }

    // إذا كان المسار يحتوي على assets/ في الوسط
    if (strpos($logo_path, 'assets/') !== false) {
        $clean_path = substr($logo_path, strpos($logo_path, 'assets/'));
        return url($clean_path);
    }

    // إذا كان اسم ملف فقط
    if (!strpos($logo_path, '/')) {
        return url('assets/uploads/' . $logo_path);
    }

    // افتراضي
    return url($logo_path);
}

/**
 * دالة للحصول على شعار المحل
 */
function getShopLogo($shop_data) {
    if (isset($shop_data['logo']) && !empty($shop_data['logo'])) {
        return getLogoUrl($shop_data['logo']);
    }

    // شعار افتراضي إذا لم يوجد
    return asset('images/default-logo.png');
}


/**
 * حساب مدة الإصلاح بالأيام بشكل صحيح
 */
function calculateRepairDuration($received_at, $delivered_at = null) {
    if (empty($received_at)) return 0;

    try {
        $start = new DateTime($received_at);
        $end = $delivered_at ? new DateTime($delivered_at) : new DateTime();

        $interval = $start->diff($end);
        return $interval->days;
    } catch (Exception $e) {
        error_log("Error calculating repair duration: " . $e->getMessage());
        return 0;
    }
}

/**
 * حساب أيام الضمانة المتبقية
 */
function calculateWarrantyDaysLeft($delivered_at, $warranty_days = 30) {
    if (empty($delivered_at)) return 0;

    try {
        $delivery_date = new DateTime($delivered_at);
        $warranty_expires = clone $delivery_date;
        $warranty_expires->add(new DateInterval("P{$warranty_days}D"));

        $now = new DateTime();

        if ($now > $warranty_expires) {
            return 0; // انتهت الضمانة
        }

        $interval = $now->diff($warranty_expires);
        return $interval->days;
    } catch (Exception $e) {
        error_log("Error calculating warranty days: " . $e->getMessage());
        return 0;
    }
}

/**
 * التحقق من صلاحية الضمانة
 */
function isUnderWarranty($delivered_at, $warranty_days = 30) {
    return calculateWarrantyDaysLeft($delivered_at, $warranty_days) > 0;
}

/**
 * تنسيق عرض المدة بالإسبانية
 */
function formatDurationSpanish($days) {
    if ($days == 0) return '0 días';
    if ($days == 1) return '1 día';
    return $days . ' días';
}

/**
 * تنسيق عرض الضمانة
 */
function formatWarrantyStatus($delivered_at, $warranty_days = 30) {
    if (empty($delivered_at)) {
        return '<span class="badge bg-secondary">Sin entregar</span>';
    }

    $days_left = calculateWarrantyDaysLeft($delivered_at, $warranty_days);

    if ($days_left > 7) {
        return '<span class="badge bg-success">Garantía válida (' . $days_left . ' días)</span>';
    } elseif ($days_left > 0) {
        return '<span class="badge bg-warning">Garantía expira pronto (' . $days_left . ' días)</span>';
    } else {
        return '<span class="badge bg-danger">Garantía expirada</span>';
    }
}

?>