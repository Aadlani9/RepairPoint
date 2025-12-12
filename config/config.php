<?php
/**
 * RepairPoint - Configuración Principal
 * Configuración básica del sistema
 */

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso denegado');
}

// ===================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// ===================================================

// Información de la aplicación
define('APP_NAME', 'RepairPoint');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistema de Gestión para Talleres de Reparación de Móviles');
define('APP_AUTHOR', '| AADLANI Mohammed');

// ===================================================
// CONFIGURACIÓN DE RUTAS
// ===================================================

// Directorio raíz del proyecto
define('ROOT_PATH', dirname(__DIR__) . '/');

define('BASE_PATH', ROOT_PATH);

// Directorio de includes
define('INCLUDES_PATH', ROOT_PATH . 'includes/');

// Directorio de pages
define('PAGES_PATH', ROOT_PATH . 'pages/');

// Directorio de assets
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// URL base del proyecto - إصلاح المشكل هنا
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// تحديد مسار المشروع بطريقة صحيحة
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// البحث عن اسم مجلد المشروع في المسار
if (strpos($script_name, '/RepairPoint/') !== false) {
    // إذا كان المشروع في مجلد RepairPoint
    $project_path = '/RepairPoint/';
} else {
    // إذا كان المشروع في جذر الخادم
    $project_path = '/';
}

define('BASE_URL', $protocol . $host . $project_path);

// ===================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ===================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'repairpoint');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===================================================
// CONFIGURACIÓN DE SESIÓN
// ===================================================

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tiempo de vida de la sesión (en segundos)
define('SESSION_LIFETIME', 3600 * 8); // 8 horas

// ===================================================
// CONFIGURACIÓN DE MENSAJES
// ===================================================

define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'danger');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// ===================================================
// CONFIGURACIÓN DE ARCHIVOS
// ===================================================

define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// ===================================================
// CONFIGURACIÓN DE PAGINACIÓN
// ===================================================

define('RECORDS_PER_PAGE', 10);

// ===================================================
// MODO DEBUG
// ===================================================

define('DEBUG_MODE', true); // Cambiar a false en producción

/**
 * Verificar si estamos en modo debug
 */
function isDebugMode() {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}

// ===================================================
// CONFIGURACIÓN DEL SISTEMA
// ===================================================

/**
 * Obtener configuración del sistema
 */
function getConfig($key = null) {
    $config = [
        'repair_status' => [
            'pending' => ['name' => 'Pendiente', 'color' => 'warning'],
            'in_progress' => ['name' => 'En Proceso', 'color' => 'info'],
            'completed' => ['name' => 'Completado', 'color' => 'success'],
            'delivered' => ['name' => 'Entregado', 'color' => 'primary'],
            'reopened' => ['name' => 'Reabierto', 'color' => 'danger']
        ],
        'repair_priority' => [
            'low' => ['name' => 'Baja', 'color' => 'secondary'],
            'medium' => ['name' => 'Media', 'color' => 'warning'],
            'high' => ['name' => 'Alta', 'color' => 'danger']
        ],
        'reopen_types' => [
            'warranty' => ['name' => 'Garantía', 'color' => 'success', 'cost' => false],
            'paid' => ['name' => 'Reparación Pagada', 'color' => 'warning', 'cost' => true],
            'goodwill' => ['name' => 'Buena Voluntad', 'color' => 'info', 'cost' => false]
        ],
        'warranty' => [
            'default_days' => 30,
            'min_days' => 7,
            'max_days' => 365
        ],
        'roles' => [
            'admin' => [
                'name' => 'Administrador',
                'permissions' => ['manage_users', 'manage_repairs', 'delete_repairs', 'view_reports', 'manage_settings', 'reopen_repairs']
            ],
            'staff' => [
                'name' => 'Personal',
                'permissions' => ['manage_repairs', 'view_reports', 'reopen_repairs']
            ]
        ]
    ];

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? null;
}

// ===================================================
// FUNCIONES DE URL
// ===================================================

/**
 * Generar URL completa
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . $path;
}

/**
 * Generar URL para assets - إصلاح المشكل هنا
 */
function asset($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . 'assets/' . $path;
}

// ===================================================
// CLASE DE BASE DE DATOS SIMPLE
// ===================================================

class SimpleDB {
    private $pdo;

    public function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            if (isDebugMode()) {
                die('Error de conexión: ' . $e->getMessage());
            } else {
                die('Error de conexión a la base de datos');
            }
        }
    }

    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if (isDebugMode()) {
                error_log('DB Error: ' . $e->getMessage());
            }
            return [];
        }
    }

    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (isDebugMode()) {
                error_log('DB Error: ' . $e->getMessage());
            }
            return false;
        }
    }

    public function insert($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if (isDebugMode()) {
                error_log('DB Error: ' . $e->getMessage());
            }
            return false;
        }
    }

    public function update($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (isDebugMode()) {
                error_log('DB Error: ' . $e->getMessage());
            }
            return false;
        }
    }

    public function exec($sql) {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            if (isDebugMode()) {
                error_log('DB Error: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function delete($sql, $params = []) {
        return $this->update($sql, $params);
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        // التحقق من وجود transaction نشط
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return false; // لا يوجد transaction نشط
    }
}

// ===================================================
// INSTANCIA GLOBAL DE LA BASE DE DATOS
// ===================================================

$db_instance = null;

/**
 * Obtener instancia de la base de datos
 */
function getDB() {
    global $db_instance;

    if ($db_instance === null) {
        $db_instance = new SimpleDB();
    }

    return $db_instance;
}

// ===================================================
// FUNCIONES DE SEGURIDAD CSRF
// ===================================================

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ===================================================
// AUTO-CARGA DE ARCHIVOS NECESARIOS
// ===================================================

// Cargar functions.php si existe
if (file_exists(INCLUDES_PATH . 'functions.php')) {
    require_once INCLUDES_PATH . 'functions.php';
}

// ===================================================
// CONFIGURACIÓN DE ERRORES
// ===================================================

if (isDebugMode()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ===================================================
// TIMEZONE
// ===================================================

date_default_timezone_set('Europe/Madrid');