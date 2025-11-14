
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


/**
 * Obtener instancia de la clase Auth
 */
function getAuth() {
    static $auth_instance = null;

    if ($auth_instance === null) {
        // التأكد من تحميل ملف Auth
        if (!class_exists('Auth')) {
            require_once INCLUDES_PATH . 'auth.php';
        }

        $auth_instance = new Auth();
    }

    return $auth_instance;
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
    if (empty($phone)) return '';

    $clean_phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

    // إذا كان الرقم يبدأ بـ +34
    if (preg_match('/^\+34([6789]\d{8})$/', $clean_phone, $matches)) {
        return '+34 ' . substr($matches[1], 0, 3) . ' ' . substr($matches[1], 3, 3) . ' ' . substr($matches[1], 6, 3);
    }

    // إذا كان الرقم يبدأ بـ 0034
    if (preg_match('/^0034([6789]\d{8})$/', $clean_phone, $matches)) {
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
 * دالة جديدة للتحقق من صحة بيانات الإصلاح
 */
function validateRepairData($data) {
    $errors = [];

    // التحقق من الحقول المطلوبة
    $required_fields = ['customer_name', 'customer_phone', 'brand_id', 'model_id', 'issue_description'];

    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es obligatorio";
        }
    }

    // التحقق من صحة الهاتف
    if (!empty($data['customer_phone']) && !isValidPhone($data['customer_phone'])) {
        $errors[] = 'El formato del teléfono no es válido';
    }

    // التحقق من صحة التكلفة
    if (!empty($data['estimated_cost']) && !is_numeric($data['estimated_cost'])) {
        $errors[] = 'La cantidad del coste debe ser numérica';
    }

    // التحقق من صحة الأولوية
    $valid_priorities = ['low', 'medium', 'high'];
    if (!empty($data['priority']) && !in_array($data['priority'], $valid_priorities)) {
        $errors[] = 'Prioridad no válida';
    }

    return $errors;
}

/**
 * دالة مساعدة لتنظيف بيانات النموذج
 */
function cleanRepairFormData($data) {
    return [
        'customer_name' => cleanString($data['customer_name']),
        'customer_phone' => cleanString($data['customer_phone']),
        'brand_id' => intval($data['brand_id']),
        'model_id' => intval($data['model_id']),
        'issue_description' => cleanString($data['issue_description']),
        'priority' => cleanString($data['priority'] ?? 'medium'),
        'estimated_cost' => !empty($data['estimated_cost']) ? floatval($data['estimated_cost']) : null,
        'notes' => cleanString($data['notes'] ?? ''),
        'warranty_days' => intval($data['warranty_days'] ?? 30)
    ];
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
        "SELECT r.*,
                b.name as brand_name,
                m.name as model_name,
                m.model_reference
         FROM repairs r
         LEFT JOIN brands b ON r.brand_id = b.id
         LEFT JOIN models m ON r.model_id = m.id
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
        'updated_at' => '',
        'warranty_days' => 30
    ];

    // دمج القيم الافتراضية مع المخصصة
    $merged_defaults = array_merge($safe_defaults, $defaults);

    // دمج البيانات الفعلية مع التأكد من وجود جميع المفاتيح
    $safe_data = [];
    foreach ($merged_defaults as $key => $default_value) {
        if (isset($data[$key])) {
            // إذا كانت القيمة موجودة، استخدمها
            $safe_data[$key] = $data[$key];
        } else {
            // إذا لم تكن موجودة، استخدم القيمة الافتراضية
            $safe_data[$key] = $default_value;
        }
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


// ===================================================
// FUNCIONES DE قطع الغيار (SPARE PARTS)
// ===================================================

/**
 * البحث في قطع الغيار
 */
function searchSpareParts($shop_id, $search_term = '', $category = '', $stock_status = '', $brand_id = 0, $model_id = 0, $limit = 50, $offset = 0) {
    $db = getDB();

    $query = "SELECT sp.*, 
                     GROUP_CONCAT(DISTINCT CONCAT(b.name, ' ', m.name) SEPARATOR ', ') as compatible_phones,
                     COUNT(DISTINCT spc.model_id) as compatibility_count
              FROM spare_parts sp
              LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
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

    return $db->select($query, $params);
}

/**
 * الحصول على قطع الغيار المقترحة لهاتف معين
 */
function getSuggestedParts($shop_id, $brand_id, $model_id) {
    $db = getDB();

    $query = "SELECT sp.id, sp.part_code, sp.part_name, sp.category, 
                     sp.total_price, sp.stock_status, sp.stock_quantity, 
                     sp.warranty_days, sp.notes
              FROM spare_parts sp
              JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
              WHERE sp.shop_id = ? AND sp.is_active = TRUE 
              AND spc.brand_id = ? AND spc.model_id = ?
              AND sp.stock_status != 'out_of_stock'
              ORDER BY sp.category, sp.part_name";

    return $db->select($query, [$shop_id, $brand_id, $model_id]);
}

/**
 * حساب التكلفة الإجمالية لقطع الغيار
 */
function calculateSparePartsCost($parts_array) {
    $total_cost = 0;

    if (!is_array($parts_array) || empty($parts_array)) {
        return $total_cost;
    }

    foreach ($parts_array as $part) {
        if (isset($part['price']) && isset($part['quantity'])) {
            $total_cost += (float)$part['price'] * (int)$part['quantity'];
        }
    }

    return $total_cost;
}

/**
 * تحديث المخزون لقطعة غيار
 */
function updateSparePartStock($part_id, $quantity_used, $operation = 'subtract') {
    $db = getDB();

    try {
        // الحصول على البيانات الحالية
        $part = $db->selectOne(
            "SELECT stock_quantity, min_stock_level FROM spare_parts WHERE id = ?",
            [$part_id]
        );

        if (!$part) {
            return false;
        }

        // حساب الكمية الجديدة
        switch ($operation) {
            case 'add':
                $new_quantity = $part['stock_quantity'] + $quantity_used;
                break;
            case 'subtract':
            default:
                $new_quantity = max(0, $part['stock_quantity'] - $quantity_used);
        }

        // تحديد حالة المخزون
        $new_status = 'available';
        if ($new_quantity <= 0) {
            $new_status = 'out_of_stock';
        } elseif ($new_quantity <= $part['min_stock_level']) {
            $new_status = 'order_required';
        }

        // تحديث المخزون
        return $db->update(
            "UPDATE spare_parts SET stock_quantity = ?, stock_status = ? WHERE id = ?",
            [$new_quantity, $new_status, $part_id]
        );

    } catch (Exception $e) {
        error_log("Error updating spare part stock: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على تاريخ أسعار قطعة غيار
 */
function getPartPriceHistory($part_id, $limit = 10) {
    $db = getDB();

    return $db->select(
        "SELECT sph.*, u.name as updated_by_name
         FROM spare_parts_price_history sph
         LEFT JOIN users u ON sph.updated_by = u.id
         WHERE sph.spare_part_id = ?
         ORDER BY sph.updated_at DESC
         LIMIT ?",
        [$part_id, $limit]
    );
}

/**
 * الحصول على فئات قطع الغيار
 */
function getSparePartsCategories($shop_id) {
    $db = getDB();

    $categories = $db->select(
        "SELECT DISTINCT category 
         FROM spare_parts 
         WHERE shop_id = ? AND is_active = TRUE AND category IS NOT NULL
         ORDER BY category",
        [$shop_id]
    );

    return array_column($categories, 'category');
}

/**
 * الحصول على قطع الغيار منخفضة المخزون
 */
function getLowStockParts($shop_id) {
    $db = getDB();

    return $db->select(
        "SELECT * FROM low_stock_parts WHERE shop_id = ? ORDER BY stock_quantity ASC",
        [$shop_id]
    );
}

/**
 * إضافة قطعة غيار مستخدمة في الإصلاح
 */
// ابحث عن دالة addRepairSparePart واستبدلها بهذا
function addRepairSparePart($repair_id, $spare_part_id, $quantity = 1, $unit_price = null) {
    $db = getDB();

    try {
        // ❌ احذف هذا السطر - لا نحتاج transaction جديد
        // $db->beginTransaction();

        // الحصول على بيانات القطعة
        $part = $db->selectOne(
            "SELECT * FROM spare_parts WHERE id = ?",
            [$spare_part_id]
        );

        if (!$part) {
            throw new Exception('القطعة غير موجودة');
        }

        // التحقق من توفر المخزون
        if ($part['stock_quantity'] < $quantity) {
            throw new Exception('الكمية المطلوبة غير متوفرة في المخزون');
        }

        // استخدام السعر الحالي إذا لم يتم تحديد سعر
        if ($unit_price === null) {
            $unit_price = $part['total_price'];
        }

        $total_price = $unit_price * $quantity;

        // إدراج استخدام القطعة
        $usage_id = $db->insert(
            "INSERT INTO repair_spare_parts (repair_id, spare_part_id, quantity, 
                                           unit_cost_price, unit_labor_cost, unit_price, total_price, warranty_days)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $repair_id,
                $spare_part_id,
                $quantity,
                $part['cost_price'],
                $part['labor_cost'],
                $unit_price,
                $total_price,
                $part['warranty_days']
            ]
        );

        if (!$usage_id) {
            throw new Exception('فشل في تسجيل استخدام القطعة');
        }

        // تحديث المخزون
        if (!updateSparePartStock($spare_part_id, $quantity, 'subtract')) {
            throw new Exception('فشل في تحديث المخزون');
        }

        // ❌ احذف هذا السطر - add_repair.php سيتولى commit
        // $db->commit();

        return $usage_id;

    } catch (Exception $e) {
        // ❌ احذف rollback - add_repair.php سيتولاه
        // $db->rollback();
        error_log("Error adding repair spare part: " . $e->getMessage());
        throw $e;
    }
}

/**
 * الحصول على قطع الغيار المستخدمة في إصلاح
 */
function getRepairSpareParts($repair_id) {
    $db = getDB();

    return $db->select(
        "SELECT rsp.*, sp.part_name, sp.part_code, sp.category
         FROM repair_spare_parts rsp
         JOIN spare_parts sp ON rsp.spare_part_id = sp.id
         WHERE rsp.repair_id = ?
         ORDER BY sp.category, sp.part_name",
        [$repair_id]
    );
}

/**
 * حساب التكلفة الإجمالية لقطع الغيار في الإصلاح
 */
function calculateRepairSparePartsCost($repair_id) {
    $db = getDB();

    $result = $db->selectOne(
        "SELECT 
            SUM(total_price) as total_customer_price,
            SUM(quantity * COALESCE(unit_cost_price, 0)) as total_cost_price,
            SUM(quantity * COALESCE(unit_labor_cost, 0)) as total_labor_cost
         FROM repair_spare_parts 
         WHERE repair_id = ?",
        [$repair_id]
    );

    if (!$result) {
        return [
            'total_customer_price' => 0,
            'total_cost_price' => 0,
            'total_labor_cost' => 0,
            'total_profit' => 0
        ];
    }

    $total_customer_price = (float)($result['total_customer_price'] ?? 0);
    $total_cost_price = (float)($result['total_cost_price'] ?? 0);
    $total_labor_cost = (float)($result['total_labor_cost'] ?? 0);
    $total_profit = $total_customer_price - $total_cost_price - $total_labor_cost;

    return [
        'total_customer_price' => $total_customer_price,
        'total_cost_price' => $total_cost_price,
        'total_labor_cost' => $total_labor_cost,
        'total_profit' => $total_profit
    ];
}

/**
 * التحقق من توفر قطعة غيار
 */
function checkSparePartAvailability($part_id, $required_quantity = 1) {
    $db = getDB();

    $part = $db->selectOne(
        "SELECT stock_quantity, stock_status, part_name 
         FROM spare_parts 
         WHERE id = ? AND is_active = TRUE",
        [$part_id]
    );

    if (!$part) {
        return [
            'available' => false,
            'message' => 'القطعة غير موجودة',
            'stock_quantity' => 0
        ];
    }

    if ($part['stock_status'] === 'out_of_stock' || $part['stock_quantity'] < $required_quantity) {
        return [
            'available' => false,
            'message' => 'الكمية المطلوبة غير متوفرة في المخزون',
            'stock_quantity' => $part['stock_quantity']
        ];
    }

    return [
        'available' => true,
        'message' => 'متوفر',
        'stock_quantity' => $part['stock_quantity']
    ];
}

/**
 * البحث السريع في قطع الغيار
 */
function quickSearchSpareParts($shop_id, $search_term, $limit = 10) {
    $db = getDB();

    return $db->select(
        "SELECT id, part_code, part_name, category, total_price, stock_status, stock_quantity
         FROM spare_parts
         WHERE shop_id = ? AND is_active = TRUE
         AND (part_name LIKE ? OR part_code LIKE ?)
         ORDER BY 
            CASE 
                WHEN part_name LIKE ? THEN 1
                WHEN part_code LIKE ? THEN 2
                ELSE 3
            END,
            part_name
         LIMIT ?",
        [
            $shop_id,
            '%' . $search_term . '%',
            '%' . $search_term . '%',
            $search_term . '%',
            $search_term . '%',
            $limit
        ]
    );
}

/**
 * تنسيق عرض حالة المخزون
 */
function formatStockStatus($status, $quantity = 0) {
    switch ($status) {
        case 'available':
            return '<span class="badge bg-success">Disponible (' . $quantity . ')</span>';
        case 'order_required':
            return '<span class="badge bg-warning">Necesita pedido (' . $quantity . ')</span>';
        case 'out_of_stock':
            return '<span class="badge bg-danger">Sin stock</span>';
        default:
            return '<span class="badge bg-secondary">Desconocido</span>';
    }
}

/**
 * تنسيق عرض فئة قطعة الغيار
 */
function formatSparePartCategory($category) {
    // إضافة تحقق من null
    if ($category === null || $category === '') {
        return 'Sin categoría';
    }

    $categories = [
        'pantalla' => 'Pantalla',
        'bateria' => 'Batería',
        'camara' => 'Cámara',
        'altavoz' => 'Altavoz',
        'auricular' => 'Auricular',
        'conector' => 'Conector',
        'boton' => 'Botón',
        'sensor' => 'Sensor',
        'flex' => 'Flex',
        'marco' => 'Marco',
        'tapa' => 'Tapa trasera',
        'cristal' => 'Cristal',
        'otros' => 'Otros'
    ];

    return $categories[strtolower($category)] ?? ucfirst($category);
}

/**
 * حساب هامش الربح لقطعة غيار
 */
function calculatePartProfitMargin($cost_price, $labor_cost, $total_price) {
    $total_cost = (float)$cost_price + (float)$labor_cost;
    $profit = (float)$total_price - $total_cost;

    if ($total_cost == 0) {
        return $total_price > 0 ? 100 : 0;
    }

    return round(($profit / $total_cost) * 100, 2);
}


/**
 * الحصول على صلاحيات قطع الغيار للمستخدم الحالي
 */
function getCurrentUserSparePartsPermissions() {
    if (!isset($_SESSION['user_id'])) {
        return [
            'view_spare_parts' => false,
            'search_spare_parts' => false,
            'manage_spare_parts' => false,
            'add_spare_parts' => false,
            'manage_stock' => false,
            'view_profit_reports' => false,
            'view_detailed_costs' => false,
            'delete_spare_parts' => false,
            'use_spare_parts' => false,
            'print_invoice' => false
        ];
    }

    $user_role = $_SESSION['user_role'] ?? 'staff';

    if ($user_role === 'admin') {
        return [
            'view_spare_parts' => true,
            'search_spare_parts' => true,
            'manage_spare_parts' => true,
            'add_spare_parts' => true,
            'manage_stock' => true,
            'view_profit_reports' => true,
            'view_detailed_costs' => true,
            'delete_spare_parts' => true,
            'use_spare_parts' => true,
            'print_invoice' => true
        ];
    }

    return [
        'view_spare_parts' => true,
        'search_spare_parts' => true,
        'manage_spare_parts' => false,
        'add_spare_parts' => false,
        'manage_stock' => false,
        'view_profit_reports' => false,
        'view_detailed_costs' => false,
        'delete_spare_parts' => false,
        'use_spare_parts' => true,
        'print_invoice' => true
    ];
}

/**
 * الحصول على ID الماركة الافتراضية للأجهزة المخصصة
 * Get default brand ID for custom devices
 */
function getDefaultBrandId() {
    static $brandId = null;

    if ($brandId === null) {
        $db = getDB();

        // محاولة الحصول على ID من جدول config
        $config = $db->selectOne(
            "SELECT setting_value FROM config WHERE setting_key = 'default_unknown_brand_id' LIMIT 1"
        );

        if ($config && $config['setting_value']) {
            $brandId = intval($config['setting_value']);
        } else {
            // البحث عن الماركة مباشرة
            $brand = $db->selectOne(
                "SELECT id FROM brands WHERE name = 'Desconocido' LIMIT 1"
            );

            if ($brand) {
                $brandId = intval($brand['id']);
            } else {
                // إنشاء الماركة إذا لم تكن موجودة
                $brandId = $db->insert(
                    "INSERT INTO brands (name, created_at) VALUES ('Desconocido', NOW())"
                );
            }
        }
    }

    return $brandId;
}

/**
 * الحصول على ID الموديل الافتراضي للأجهزة المخصصة
 * Get default model ID for custom devices
 */
function getDefaultModelId() {
    static $modelId = null;

    if ($modelId === null) {
        $db = getDB();

        // محاولة الحصول على ID من جدول config
        $config = $db->selectOne(
            "SELECT setting_value FROM config WHERE setting_key = 'default_unknown_model_id' LIMIT 1"
        );

        if ($config && $config['setting_value']) {
            $modelId = intval($config['setting_value']);
        } else {
            // البحث عن الموديل مباشرة
            $brandId = getDefaultBrandId();
            $model = $db->selectOne(
                "SELECT id FROM models WHERE brand_id = ? AND name = 'Dispositivo Personalizado' LIMIT 1",
                [$brandId]
            );

            if ($model) {
                $modelId = intval($model['id']);
            } else {
                // إنشاء الموديل إذا لم يكن موجوداً
                $modelId = $db->insert(
                    "INSERT INTO models (brand_id, name, created_at) VALUES (?, 'Dispositivo Personalizado', NOW())",
                    [$brandId]
                );
            }
        }
    }

    return $modelId;
}

/**
 * الحصول على اسم الجهاز للعرض (مع دعم الأجهزة المخصصة)
 * Get device display name (with custom device support)
 *
 * @param array $repair معلومات الإصلاح
 * @return string
 */
function getDeviceDisplayName($repair) {
    // إذا كان جهاز مخصص
    if (isset($repair['device_input_type']) && $repair['device_input_type'] === 'otro') {
        $brand = $repair['custom_brand'] ?? 'Desconocido';
        $model = $repair['custom_model'] ?? 'Desconocido';
        return trim($brand . ' ' . $model);
    }

    // جهاز عادي من القائمة
    $brandName = $repair['brand_name'] ?? '';
    $modelName = $repair['model_name'] ?? '';
    $modelRef = isset($repair['model_reference']) && $repair['model_reference'] ?
                " ({$repair['model_reference']})" : '';

    return trim($brandName . ' ' . $modelName . $modelRef);
}

?>