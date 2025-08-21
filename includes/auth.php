<?php
/**
 * RepairPoint - Sistema de Autenticación
 * Manejo de login, logout y sesiones
 */

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso denegado');
}

/**
 * Clase para manejo de autenticación
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Iniciar sesión de usuario
     */
    public function login($email, $password, $remember_me = false) {
        try {
            // Buscar usuario por email
            $user = $this->db->selectOne(
                "SELECT u.*, s.name as shop_name, s.status as shop_status 
                 FROM users u 
                 JOIN shops s ON u.shop_id = s.id 
                 WHERE u.email = ? AND u.status = 'active'",
                [$email]
            );
            
            if (!$user) {
                logActivity('failed_login_attempt', "Email: {$email} - Usuario no encontrado");
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }
            
            // Verificar si el taller está activo
            if ($user['shop_status'] !== 'active') {
                logActivity('failed_login_attempt', "Email: {$email} - Taller inactivo");
                return [
                    'success' => false,
                    'message' => 'El taller está temporalmente desactivado'
                ];
            }
            
            // Verificar contraseña
            if (!verifyPassword($password, $user['password'])) {
                logActivity('failed_login_attempt', "Email: {$email} - Contraseña incorrecta", $user['id']);
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ];
            }
            
            // Crear sesión
            $this->createSession($user);
            
            // Actualizar último login
            $this->updateLastLogin($user['id']);
            
            // Manejar "Recordarme"
            if ($remember_me) {
                $this->setRememberMeCookie($user['id']);
            }
            
            logActivity('successful_login', "Inicio de sesión exitoso", $user['id']);
            
            return [
                'success' => true,
                'message' => 'Bienvenido, ' . $user['name'],
                'redirect' => url('pages/dashboard.php')
            ];
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id) {
            logActivity('logout', "Cierre de sesión", $user_id);
        }
        
        // Limpiar sesión
        session_destroy();
        
        // Limpiar cookie de "recordarme"
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
            
            // Opcional: eliminar token de base de datos
            if ($user_id) {
                $this->db->update(
                    "UPDATE users SET remember_token = NULL WHERE id = ?",
                    [$user_id]
                );
            }
        }
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
            'redirect' => url('pages/login.php')
        ];
    }
    
    /**
     * Verificar si hay sesión activa válida
     */
    public function checkSession() {
        // Verificar si hay sesión activa
        if (isLoggedIn()) {
            // Verificar si la sesión no ha expirado
            if (isset($_SESSION['last_activity'])) {
                if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                    $this->logout();
                    return false;
                }
            }
            
            // Actualizar última actividad
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Verificar cookie de "recordarme"
        if (isset($_COOKIE['remember_token'])) {
            return $this->loginFromRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Cambiar contraseña de usuario
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verificar contraseña actual
            $user = $this->db->selectOne(
                "SELECT password FROM users WHERE id = ?",
                [$user_id]
            );
            
            if (!$user || !verifyPassword($current_password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ];
            }
            
            // Validar nueva contraseña
            if (strlen($new_password) < 6) {
                return [
                    'success' => false,
                    'message' => 'La nueva contraseña debe tener al menos 6 caracteres'
                ];
            }
            
            // Actualizar contraseña
            $hashed_password = hashPassword($new_password);
            $updated = $this->db->update(
                "UPDATE users SET password = ? WHERE id = ?",
                [$hashed_password, $user_id]
            );
            
            if ($updated) {
                logActivity('password_changed', "Cambio de contraseña", $user_id);
                return [
                    'success' => true,
                    'message' => 'Contraseña cambiada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la contraseña'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Recuperar contraseña (generar token)
     */
    public function requestPasswordReset($email) {
        try {
            $user = $this->db->selectOne(
                "SELECT id, name FROM users WHERE email = ? AND status = 'active'",
                [$email]
            );
            
            if (!$user) {
                // Por seguridad, siempre devolver éxito
                return [
                    'success' => true,
                    'message' => 'Si el email existe, recibirás instrucciones de recuperación'
                ];
            }
            
            // Generar token de recuperación
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
            
            $this->db->update(
                "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?",
                [$token, $expires, $user['id']]
            );
            
            // Aquí enviarías el email con el token
            // Por ahora solo logueamos
            logActivity('password_reset_requested', "Token: {$token}", $user['id']);
            
            return [
                'success' => true,
                'message' => 'Si el email existe, recibirás instrucciones de recuperación',
                'token' => isDebugMode() ? $token : null // Solo en desarrollo
            ];
            
        } catch (Exception $e) {
            error_log("Error en recuperación de contraseña: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Restablecer contraseña con token
     */
    public function resetPassword($token, $new_password) {
        try {
            $user = $this->db->selectOne(
                "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active'",
                [$token]
            );
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ];
            }
            
            // Validar nueva contraseña
            if (strlen($new_password) < 6) {
                return [
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ];
            }
            
            // Actualizar contraseña y limpiar token
            $hashed_password = hashPassword($new_password);
            $this->db->update(
                "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?",
                [$hashed_password, $user['id']]
            );
            
            logActivity('password_reset_completed', "Contraseña restablecida", $user['id']);
            
            return [
                'success' => true,
                'message' => 'Contraseña restablecida correctamente. Puedes iniciar sesión.'
            ];
            
        } catch (Exception $e) {
            error_log("Error restableciendo contraseña: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Crear sesión de usuario
     */
    private function createSession($user) {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['shop_id'] = $user['shop_id'];
        $_SESSION['shop_name'] = $user['shop_name'];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Actualizar último login
     */
    private function updateLastLogin($user_id) {
        $this->db->update(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user_id]
        );
    }
    
    /**
     * Establecer cookie de "recordarme"
     */
    private function setRememberMeCookie($user_id) {
        $token = generateToken();
        $expires = time() + (30 * 24 * 60 * 60); // 30 días
        
        // Guardar token en base de datos
        $this->db->update(
            "UPDATE users SET remember_token = ? WHERE id = ?",
            [$token, $user_id]
        );
        
        // Establecer cookie
        setcookie('remember_token', $token, $expires, '/', '', false, true);
    }
    
    /**
     * Iniciar sesión desde token de "recordarme"
     */
    private function loginFromRememberToken($token) {
        try {
            $user = $this->db->selectOne(
                "SELECT u.*, s.name as shop_name, s.status as shop_status 
                 FROM users u 
                 JOIN shops s ON u.shop_id = s.id 
                 WHERE u.remember_token = ? AND u.status = 'active' AND s.status = 'active'",
                [$token]
            );
            
            if ($user) {
                $this->createSession($user);
                $this->updateLastLogin($user['id']);
                logActivity('auto_login_remember_token', "Login automático", $user['id']);
                return true;
            }
            
            // Token inválido, eliminar cookie
            setcookie('remember_token', '', time() - 3600, '/');
            
        } catch (Exception $e) {
            error_log("Error en auto-login: " . $e->getMessage());
        }
        
        return false;
    }
}

// ===================================================
// FUNCIONES AUXILIARES DE AUTENTICACIÓN
// ===================================================

/**
 * Obtener instancia de Auth
 */
//function getAuth() {
//    static $auth = null;
//    if ($auth === null) {
//        $auth = new Auth();
//    }
//    return $auth;
//}

/**
 * Middleware de autenticación para páginas
 */
function authMiddleware($require_admin = false) {
    $auth = getAuth();
    
    if (!$auth->checkSession()) {
        header('Location: ' . url('pages/login.php'));
        exit;
    }
    
    if ($require_admin && !isAdmin()) {
        setMessage('No tienes permisos para acceder a esta página', MSG_ERROR);
        header('Location: ' . url('pages/dashboard.php'));
        exit;
    }
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    return $db->selectOne(
        "SELECT u.*, s.name as shop_name FROM users u JOIN shops s ON u.shop_id = s.id WHERE u.id = ?",
        [$_SESSION['user_id']]
    );
}

/**
 * Verificar permisos específicos
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    $roles_config = getConfig('roles');
    
    if (!isset($roles_config[$user_role])) {
        return false;
    }
    
    return in_array($permission, $roles_config[$user_role]['permissions']);
}

/**
 * Verificar si puede gestionar usuarios
 */
function canManageUsers() {
    return hasPermission('manage_users');
}

/**
 * Verificar si puede gestionar reparaciones
 */
function canManageRepairs() {
    return hasPermission('manage_repairs');
}

/**
 * Verificar si puede eliminar reparaciones
 */
function canDeleteRepairs() {
    return hasPermission('delete_repairs');
}

/**
 * Verificar si puede ver informes
 */
function canViewReports() {
    return hasPermission('view_reports');
}

/**
 * Verificar si puede gestionar configuración
 */
function canManageSettings() {
    return hasPermission('manage_settings');
}


// ===================================================
// FUNCIONES DE AUTENTICACIÓN PARA قطع الغيار (SPARE PARTS)
// ===================================================

/**
 * التحقق من صلاحية إدارة قطع الغيار
 */
function canManageSpareParts() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية عرض تفاصيل التكلفة
 */
function canViewDetailedCosts() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية تعديل الأسعار
 */
function canEditPrices() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية عرض التقارير المالية
 */
function canViewProfitReports() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية إدارة المخزون
 */
function canManageStock() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية إضافة قطع غيار جديدة
 */
function canAddSpareParts() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية حذف قطع الغيار
 */
function canDeleteSpareParts() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية عرض معلومات المزودين
 */
function canViewSupplierInfo() {
    return isLoggedIn() && isAdmin();
}

/**
 * التحقق من صلاحية البحث في قطع الغيار
 */
function canSearchSpareParts() {
    return isLoggedIn(); // جميع المستخدمين المسجلين
}

/**
 * التحقق من صلاحية عرض قطع الغيار
 */
function canViewSpareParts() {
    return isLoggedIn(); // جميع المستخدمين المسجلين
}

/**
 * التحقق من صلاحية استخدام قطع الغيار في الإصلاحات
 */
function canUseSpareParts() {
    return isLoggedIn(); // جميع المستخدمين المسجلين
}

/**
 * التحقق من صلاحية طباعة فاتورة قطع الغيار
 */
function canPrintSparePartsInvoice() {
    return isLoggedIn(); // جميع المستخدمين المسجلين
}

/**
 * فلترة بيانات قطعة الغيار حسب الصلاحيات
 */
function filterSparePartData($part_data) {
    if (!canViewDetailedCosts()) {
        // إخفاء معلومات التكلفة للموظفين العاديين
        unset($part_data['cost_price']);
        unset($part_data['labor_cost']);
        unset($part_data['supplier_name']);
        unset($part_data['supplier_contact']);
    }

    return $part_data;
}

/**
 * فلترة مصفوفة قطع الغيار حسب الصلاحيات
 */
function filterSparePartsData($parts_array) {
    if (!is_array($parts_array)) {
        return $parts_array;
    }

    foreach ($parts_array as &$part) {
        $part = filterSparePartData($part);
    }

    return $parts_array;
}

/**
 * ميدل وير للتحقق من صلاحيات إدارة قطع الغيار
 */
function requireSparePartsManagement() {
    if (!canManageSpareParts()) {
        setMessage('لا تملك صلاحية إدارة قطع الغيار', MSG_ERROR);
        header('Location: ' . url('pages/dashboard.php'));
        exit;
    }
}

/**
 * ميدل وير للتحقق من صلاحيات عرض التقارير المالية
 */
function requireProfitReportsAccess() {
    if (!canViewProfitReports()) {
        setMessage('لا تملك صلاحية عرض التقارير المالية', MSG_ERROR);
        header('Location: ' . url('pages/dashboard.php'));
        exit;
    }
}


/**
 * التحقق من صلاحية معينة لقطع الغيار
 */
function hasSparePartPermission($permission) {
    $permissions = getCurrentUserSparePartsPermissions();
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

/**
 * تحديث إعدادات القائمة لتشمل قطع الغيار
 */
function getNavigationMenuWithSpareParts() {
    $menu = [
        'dashboard' => [
            'title' => 'Panel de Control',
            'url' => url('pages/dashboard.php'),
            'icon' => 'bi-speedometer2',
            'permission' => 'view_dashboard'
        ],
        'add_repair' => [
            'title' => 'Nueva Reparación',
            'url' => url('pages/add_repair.php'),
            'icon' => 'bi-plus-circle',
            'permission' => 'add_repairs'
        ],
        'active_repairs' => [
            'title' => 'Reparaciones Activas',
            'url' => url('pages/repairs_active.php'),
            'icon' => 'bi-tools',
            'permission' => 'view_repairs'
        ],
        'completed_repairs' => [
            'title' => 'Reparaciones Completadas',
            'url' => url('pages/repairs_completed.php'),
            'icon' => 'bi-check-circle',
            'permission' => 'view_repairs'
        ]
    ];

    // إضافة قطع الغيار للقائمة
    if (canViewSpareParts()) {
        $menu['spare_parts'] = [
            'title' => 'Repuestos',
            'url' => url('pages/spare_parts.php'),
            'icon' => 'bi-gear',
            'permission' => 'view_spare_parts'
        ];
    }

    // إضافة قائمة فرعية للإدارة
    if (isAdmin()) {
        $menu['divider_admin'] = ['type' => 'divider'];

        if (canManageSpareParts()) {
            $menu['add_spare_part'] = [
                'title' => 'Agregar Repuesto',
                'url' => url('pages/add_spare_part.php'),
                'icon' => 'bi-plus-square',
                'permission' => 'add_spare_parts'
            ];
        }

        if (canViewProfitReports()) {
            $menu['spare_parts_reports'] = [
                'title' => 'Informes Financieros',
                'url' => url('pages/spare_parts_reports.php'),
                'icon' => 'bi-graph-up',
                'permission' => 'view_profit_reports'
            ];
        }

        $menu['users'] = [
            'title' => 'Usuarios',
            'url' => url('pages/users.php'),
            'icon' => 'bi-people',
            'permission' => 'manage_users'
        ];

        $menu['reports'] = [
            'title' => 'Informes',
            'url' => url('pages/reports.php'),
            'icon' => 'bi-bar-chart',
            'permission' => 'view_reports'
        ];

        $menu['settings'] = [
            'title' => 'Configuración',
            'url' => url('pages/settings.php'),
            'icon' => 'bi-gear',
            'permission' => 'manage_settings'
        ];
    }

    return $menu;
}

/**
 * التحقق من صلاحية الوصول لصفحة معينة من قطع الغيار
 */
function checkSparePartsPageAccess($page) {
    switch ($page) {
        case 'spare_parts':
            if (!canViewSpareParts()) {
                setMessage('لا تملك صلاحية عرض قطع الغيار', MSG_ERROR);
                header('Location: ' . url('pages/dashboard.php'));
                exit;
            }
            break;

        case 'add_spare_part':
        case 'edit_spare_part':
            requireSparePartsManagement();
            break;

        case 'spare_parts_reports':
            requireProfitReportsAccess();
            break;

        default:
            // صفحة غير معروفة
            break;
    }
}

/**
 * إضافة معلومات قطع الغيار لبيانات المستخدم الحالي
 */
function getCurrentUserWithSparePartsInfo() {
    $user = getCurrentUser();

    if ($user) {
        $user['spare_parts_permissions'] = getCurrentUserSparePartsPermissions();
    }

    return $user;
}

?>