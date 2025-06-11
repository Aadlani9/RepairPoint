<?php
/**
 * RepairPoint - نظام التوجيه المكتمل (Router)
 * يتعامل مع توجيه المسارات وحماية الصفحات
 */

// منع الوصول المباشر
if (!defined('SECURE_ACCESS')) {
    http_response_code(403);
    die('Access Denied');
}

class Router {
    private static $routes = [];
    private static $public_routes = [
        'login',
        'index'
    ];

    private static $admin_routes = [
        'users',
        'settings',
        'reports'
    ];

    /**
     * تسجيل مسار جديد
     */
    public static function register($path, $file, $auth_required = true, $admin_only = false) {
        self::$routes[$path] = [
            'file' => $file,
            'auth_required' => $auth_required,
            'admin_only' => $admin_only
        ];
    }

    /**
     * معالجة الطلب الحالي
     */
    public static function handleRequest() {
        try {
            // الحصول على المسار المطلوب
            $request_path = self::getCurrentPath();

            // تنظيف المسار
            $clean_path = self::sanitizePath($request_path);

            // إذا كان المسار فارغ، توجيه للصفحة الافتراضية
            if (empty($clean_path) || $clean_path === 'index') {
                return self::handleDefaultRoute();
            }

            // التحقق من وجود المسار المسجل
            if (self::routeExists($clean_path)) {
                return self::loadRoute($clean_path);
            }

            // محاولة العثور على الملف مباشرة
            return self::loadFile($clean_path);

        } catch (Exception $e) {
            error_log("Router Error: " . $e->getMessage());
            return self::show500();
        }
    }

    /**
     * معالجة المسار الافتراضي
     */
    private static function handleDefaultRoute() {
        if (self::isAuthenticated()) {
            return self::redirectTo('dashboard');
        } else {
            return self::redirectTo('login');
        }
    }

    /**
     * الحصول على المسار الحالي
     */
    private static function getCurrentPath() {
        $path = '';

        // من URL parameters
        if (isset($_GET['page'])) {
            $path = $_GET['page'];
        }
        // من REQUEST_URI
        elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];

            // إزالة query string
            if (strpos($uri, '?') !== false) {
                $uri = substr($uri, 0, strpos($uri, '?'));
            }

            // استخراج اسم الملف
            $path = basename($uri, '.php');

            // إذا كان المسار هو اسم المجلد، تجاهله
            if ($path === basename(dirname($_SERVER['SCRIPT_NAME']))) {
                $path = '';
            }
        }
        // من SCRIPT_NAME
        elseif (isset($_SERVER['SCRIPT_NAME'])) {
            $path = basename($_SERVER['SCRIPT_NAME'], '.php');
        }

        return $path;
    }

    /**
     * تنظيف المسار
     */
    private static function sanitizePath($path) {
        // إزالة الملحقات
        $path = preg_replace('/\.(php|html|htm)$/', '', $path);

        // إزالة الأحرف الخطيرة
        $path = preg_replace('/[^a-zA-Z0-9_-]/', '', $path);

        // إزالة المسارات النسبية
        $path = str_replace(['../', './', '../', '..\\'], '', $path);

        // تحويل إلى أحرف صغيرة
        $path = strtolower($path);

        return $path;
    }

    /**
     * التحقق من وجود المسار المسجل
     */
    private static function routeExists($path) {
        return isset(self::$routes[$path]);
    }

    /**
     * تحميل مسار مسجل
     */
    private static function loadRoute($path) {
        $route = self::$routes[$path];

        // التحقق من الصلاحيات
        if ($route['auth_required'] && !self::isAuthenticated()) {
            return self::redirectToLogin();
        }

        // التحقق من صلاحيات الإدارة
        if ($route['admin_only'] && !self::isAdmin()) {
            return self::show403();
        }

        return self::includeFile($route['file']);
    }

    /**
     * تحميل ملف مباشرة
     */
    private static function loadFile($path) {
        // المسارات المسموحة
        $allowed_files = [
            'login' => ['file' => 'login.php', 'auth' => false, 'admin' => false],
            'dashboard' => ['file' => 'dashboard.php', 'auth' => true, 'admin' => false],
            'add_repair' => ['file' => 'add_repair.php', 'auth' => true, 'admin' => false],
            'repairs_active' => ['file' => 'repairs_active.php', 'auth' => true, 'admin' => false],
            'repairs_completed' => ['file' => 'repairs_completed.php', 'auth' => true, 'admin' => false],
            'repair_details' => ['file' => 'repair_details.php', 'auth' => true, 'admin' => false],
            'edit_repair' => ['file' => 'edit_repair.php', 'auth' => true, 'admin' => false],
            'search' => ['file' => 'search.php', 'auth' => true, 'admin' => false],
            'profile' => ['file' => 'profile.php', 'auth' => true, 'admin' => false],
            'print_ticket' => ['file' => 'print_ticket.php', 'auth' => true, 'admin' => false],
            'users' => ['file' => 'users.php', 'auth' => true, 'admin' => true],
            'settings' => ['file' => 'settings.php', 'auth' => true, 'admin' => true],
            'reports' => ['file' => 'reports.php', 'auth' => true, 'admin' => true]
        ];

        // التحقق من أن الملف مسموح
        if (!array_key_exists($path, $allowed_files)) {
            return self::show404();
        }

        $file_config = $allowed_files[$path];

        // بناء مسار الملف
        $file_path = PAGES_PATH . $file_config['file'];

        // التحقق من وجود الملف
        if (!file_exists($file_path)) {
            return self::show404();
        }

        // التحقق من الصلاحيات
        if ($file_config['auth'] && !self::isAuthenticated()) {
            return self::redirectToLogin();
        }

        // التحقق من صلاحيات الإدارة
        if ($file_config['admin'] && !self::isAdmin()) {
            return self::show403();
        }

        return self::includeFile($file_path);
    }

    /**
     * تضمين الملف
     */
    private static function includeFile($file_path) {
        // التحقق من وجود الملف
        if (!file_exists($file_path)) {
            return self::show404();
        }

        // التحقق من أن الملف ضمن مجلد المشروع
        $real_path = realpath($file_path);
        $base_path = realpath(BASE_PATH);

        if (!$real_path || !$base_path || strpos($real_path, $base_path) !== 0) {
            error_log("Security: Attempted to include file outside project: $file_path");
            return self::show403();
        }

        // تعيين متغيرات عامة
        global $current_user, $shop_id;

        if (self::isAuthenticated()) {
            $current_user = $_SESSION;
            $shop_id = $_SESSION['shop_id'] ?? null;
        }

        // تضمين الملف
        try {
            require $real_path;
            return true;
        } catch (Exception $e) {
            error_log("Error including file $file_path: " . $e->getMessage());
            return self::show500();
        }
    }

    /**
     * التحقق من تسجيل الدخول
     */
    private static function isAuthenticated() {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['shop_id']) &&
            !empty($_SESSION['user_id']) &&
            !empty($_SESSION['shop_id']);
    }

    /**
     * التحقق من صلاحيات الإدارة
     */
    private static function isAdmin() {
        return self::isAuthenticated() &&
            isset($_SESSION['user_role']) &&
            $_SESSION['user_role'] === 'admin';
    }

    /**
     * إعادة التوجيه لصفحة تسجيل الدخول
     */
    private static function redirectToLogin() {
        // حفظ الصفحة المطلوبة للعودة إليها بعد تسجيل الدخول
        if (!in_array(self::getCurrentPath(), ['login', 'logout'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        }

        self::redirectTo('login');
        return false;
    }

    /**
     * إعادة التوجيه لصفحة معينة
     */
    private static function redirectTo($page) {
        $url = url("pages/$page.php");
        header("Location: $url");
        exit;
    }

    /**
     * عرض صفحة 403
     */
    private static function show403() {
        http_response_code(403);

        $error_page = PAGES_PATH . '403.php';
        if (file_exists($error_page)) {
            require $error_page;
        } else {
            self::showDefaultError(403, 'ممنوع الوصول', 'ليس لديك صلاحية للوصول لهذه الصفحة.');
        }

        return false;
    }

    /**
     * عرض صفحة 404
     */
    private static function show404() {
        http_response_code(404);

        $error_page = PAGES_PATH . '404.php';
        if (file_exists($error_page)) {
            require $error_page;
        } else {
            self::showDefaultError(404, 'الصفحة غير موجودة', 'الصفحة التي تبحث عنها غير موجودة.');
        }

        return false;
    }

    /**
     * عرض صفحة 500
     */
    private static function show500() {
        http_response_code(500);

        $error_page = PAGES_PATH . '500.php';
        if (file_exists($error_page)) {
            require $error_page;
        } else {
            self::showDefaultError(500, 'خطأ في الخادم', 'حدث خطأ داخلي في الخادم.');
        }

        return false;
    }

    /**
     * عرض صفحة خطأ افتراضية
     */
    private static function showDefaultError($code, $title, $message) {
        ?>
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= $title ?> - <?= APP_NAME ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .error-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
                .error-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; text-align: center; }
                .error-code { font-size: 4rem; font-weight: bold; color: #dc3545; }
            </style>
        </head>
        <body>
        <div class="error-container">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="error-card">
                            <div class="error-code"><?= $code ?></div>
                            <h1 class="h3 mb-3"><?= $title ?></h1>
                            <p class="text-muted mb-4"><?= $message ?></p>
                            <div class="d-grid gap-2 d-md-block">
                                <?php if (self::isAuthenticated()): ?>
                                    <a href="<?= url('pages/dashboard.php') ?>" class="btn btn-primary">
                                        <i class="bi bi-house me-2"></i>الصفحة الرئيسية
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('pages/login.php') ?>" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول
                                    </a>
                                <?php endif; ?>
                                <button onclick="history.back()" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>العودة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * تسجيل المسارات الافتراضية
     */
    public static function registerDefaultRoutes() {
        // المسارات العامة
        self::register('login', PAGES_PATH . 'login.php', false, false);
        self::register('index', PAGES_PATH . 'login.php', false, false);

        // المسارات المحمية العادية
        self::register('dashboard', PAGES_PATH . 'dashboard.php', true, false);
        self::register('add_repair', PAGES_PATH . 'add_repair.php', true, false);
        self::register('repairs_active', PAGES_PATH . 'repairs_active.php', true, false);
        self::register('repairs_completed', PAGES_PATH . 'repairs_completed.php', true, false);
        self::register('repair_details', PAGES_PATH . 'repair_details.php', true, false);
        self::register('edit_repair', PAGES_PATH . 'edit_repair.php', true, false);
        self::register('search', PAGES_PATH . 'search.php', true, false);
        self::register('profile', PAGES_PATH . 'profile.php', true, false);
        self::register('print_ticket', PAGES_PATH . 'print_ticket.php', true, false);

        // المسارات المحمية للإدارة فقط
        self::register('users', PAGES_PATH . 'users.php', true, true);
        self::register('settings', PAGES_PATH . 'settings.php', true, true);
        self::register('reports', PAGES_PATH . 'reports.php', true, true);
    }

    /**
     * الحصول على قائمة بجميع المسارات المسجلة
     */
    public static function getRegisteredRoutes() {
        return self::$routes;
    }

    /**
     * التحقق من صحة المسار
     */
    public static function isValidRoute($path) {
        $clean_path = self::sanitizePath($path);
        return self::routeExists($clean_path) || array_key_exists($clean_path, [
                'login', 'dashboard', 'add_repair', 'repairs_active',
                'repairs_completed', 'repair_details', 'edit_repair',
                'search', 'profile', 'print_ticket', 'users', 'settings', 'reports'
            ]);
    }

    /**
     * الحصول على المسار الآمن
     */
    public static function getSafePath($path) {
        $clean_path = self::sanitizePath($path);

        if (self::isValidRoute($clean_path)) {
            return $clean_path;
        }

        // إرجاع مسار افتراضي آمن
        return self::isAuthenticated() ? 'dashboard' : 'login';
    }
}

// تسجيل المسارات الافتراضية عند تحميل الملف
Router::registerDefaultRoutes();

// دالة مساعدة عامة للتوجيه
function route($path) {
    $safe_path = Router::getSafePath($path);
    return url("pages/$safe_path.php");
}

// دالة للتحقق من المسار الحالي
function isCurrentRoute($path) {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    return $current === $path;
}

?>