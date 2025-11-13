<?php
/**
 * RepairPoint - Header común para todas las páginas (محدث مع نظام قطع الغيار)
 */

// Verificar si hay sesión activa
if (!isset($_SESSION['user_id'])) {
    // Si no hay sesión, verificar si estamos en login
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'login.php' && $current_page !== 'index.php') {
        header('Location: ' . url('pages/login.php'));
        exit;
    }
}

// Obtener información del usuario actual si existe sesión
$current_user = null;
$current_shop = null;
$shop_id = null;

if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();
        $current_user = $db->selectOne(
            "SELECT u.*, s.name as shop_name, s.logo as shop_logo 
             FROM users u 
             JOIN shops s ON u.shop_id = s.id 
             WHERE u.id = ?",
            [$_SESSION['user_id']]
        );

        if ($current_user) {
            $current_shop = $db->selectOne("SELECT * FROM shops WHERE id = ?", [$current_user['shop_id']]);
            // تعيين shop_id بطريقة آمنة
            $shop_id = $current_user['shop_id'];
            $_SESSION['shop_id'] = $shop_id; // تأكد من وجوده في الجلسة
        }
    } catch (Exception $e) {
        // Si hay error en la DB, usar datos de sesión como fallback
        $current_user = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Usuario',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'staff',
            'shop_name' => $_SESSION['shop_name'] ?? 'Mi Taller'
        ];
        $current_shop = ['name' => $current_user['shop_name'], 'logo' => null];
        $shop_id = $_SESSION['shop_id'] ?? null;
    }
}

// Obtener صلاحيات قطع الغيار للمستخدم الحالي
$spare_parts_permissions = [
    'view_spare_parts' => false,
    'search_spare_parts' => false,
    'manage_spare_parts' => false,
    'add_spare_parts' => false,
    'manage_stock' => false,
    'view_profit_reports' => false
];

if (isset($_SESSION['user_id']) && $shop_id) {
    $spare_parts_permissions = getCurrentUserSparePartsPermissions();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_DESCRIPTION ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">

    <title><?= isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">

    <!-- Print CSS -->
    <link href="<?= asset('css/print.css') ?>" rel="stylesheet" media="print">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">

    <!-- Meta tags para PWA -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
</head>
<body class="<?= isset($body_class) ? $body_class : '' ?>">

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?= url('pages/dashboard.php') ?>">
            <?php if ($current_shop && isset($current_shop['logo']) && $current_shop['logo']): ?>
                <img src="<?= url($current_shop['logo']) ?>" alt="Logo" height="30" class="me-2">
            <?php else: ?>
                <i class="bi bi-tools me-2"></i>
            <?php endif; ?>
            <span class="fw-bold"><?= APP_NAME ?></span>
        </a>

        <!-- Mobile menu button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>"
                       href="<?= url('pages/dashboard.php') ?>">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Panel
                    </a>
                </li>

                <!-- Nueva Reparación -->
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'add_repair.php') ? 'active' : '' ?>"
                       href="<?= url('pages/add_repair.php') ?>">
                        <i class="bi bi-plus-circle me-1"></i>
                        Nueva Reparación
                    </a>
                </li>

                <!-- Dropdown Reparaciones -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="repairsDropdown" role="button" data-dropdown-toggle>
                        <i class="bi bi-list-check me-1"></i>
                        Reparaciones
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?= url('pages/repairs_active.php') ?>">
                                <i class="bi bi-clock me-2"></i>
                                Activas
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= url('pages/repairs_completed.php') ?>">
                                <i class="bi bi-check-circle me-2"></i>
                                Completadas
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- قطع الغيار - للمستخدمين المصرح لهم -->
                <?php if ($spare_parts_permissions['view_spare_parts'] && $shop_id): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['spare_parts.php', 'add_spare_part.php', 'edit_spare_part.php']) ? 'active' : '' ?>"
                           href="#" id="sparePartsDropdown" role="button" data-dropdown-toggle>
                            <i class="bi bi-gear me-1"></i>
                            Repuestos
                            <?php
                            // عرض إشعار المخزون المنخفض
                            try {
                                $low_stock_count = 0;
                                if ($spare_parts_permissions['manage_spare_parts'] && function_exists('getLowStockParts')) {
                                    $low_stock_parts = getLowStockParts($shop_id);
                                    $low_stock_count = count($low_stock_parts);
                                }
                                if ($low_stock_count > 0):
                                    ?>
                                    <span class="badge bg-warning ms-1"><?= $low_stock_count ?></span>
                                <?php
                                endif;
                            } catch (Exception $e) {
                                // تجاهل الأخطاء في حالة عدم وجود الجداول
                                error_log("Low stock check error: " . $e->getMessage());
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?= url('pages/spare_parts.php') ?>">
                                    <i class="bi bi-list me-2"></i>
                                    Ver Repuestos
                                </a>
                            </li>

                            <?php if ($spare_parts_permissions['search_spare_parts']): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= url('pages/spare_parts.php?action=search') ?>">
                                        <i class="bi bi-search me-2"></i>
                                        Buscar Repuestos
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- قسم الإدارة -->
                            <?php if ($spare_parts_permissions['manage_spare_parts']): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <h6 class="dropdown-header">
                                        <i class="bi bi-wrench me-1"></i>
                                        Gestión
                                    </h6>
                                </li>

                                <?php if ($spare_parts_permissions['add_spare_parts']): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('pages/add_spare_part.php') ?>">
                                            <i class="bi bi-plus-square me-2"></i>
                                            Agregar Repuesto
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($spare_parts_permissions['manage_stock']): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('pages/spare_parts.php?filter=low_stock') ?>">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Stock Bajo
                                            <?php if (isset($low_stock_count) && $low_stock_count > 0): ?>
                                                <span class="badge bg-warning ms-1"><?= $low_stock_count ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($spare_parts_permissions['view_profit_reports']): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('pages/spare_parts_reports.php') ?>">
                                            <i class="bi bi-graph-up me-2"></i>
                                            Informes Financieros
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Buscar -->
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'active' : '' ?>"
                       href="<?= url('pages/search.php') ?>">
                        <i class="bi bi-search me-1"></i>
                        Buscar
                    </a>
                </li>

                <!-- Administración (Solo Admin) -->
                <?php if ($current_user && $current_user['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-dropdown-toggle>
                            <i class="bi bi-shield-check me-1"></i>
                            Administración
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="bi bi-people me-1"></i>
                                    Gestión de Usuarios
                                </h6>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('pages/users.php') ?>">
                                    <i class="bi bi-people me-2"></i>
                                    Usuarios
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="bi bi-gear me-1"></i>
                                    Sistema
                                </h6>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('pages/settings.php') ?>">
                                    <i class="bi bi-sliders me-2"></i>
                                    Configuración
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="bi bi-bar-chart me-1"></i>
                                    Informes y Análisis
                                </h6>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('pages/reports.php') ?>">
                                    <i class="bi bi-graph-up me-2"></i>
                                    Informes Generales
                                </a>
                            </li>

                            <?php if ($spare_parts_permissions['view_profit_reports']): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= url('pages/spare_parts_reports.php') ?>">
                                        <i class="bi bi-cash-stack me-2"></i>
                                        Análisis de Rentabilidad
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- User Info & Notifications -->
            <ul class="navbar-nav">
                <!-- 🆕 زر المساعدة والدعم -->
                <li class="nav-item">
                    <a class="nav-link help-button" href="<?= url('pages/help.php') ?>" title="Centro de Ayuda y Soporte">
                        <i class="bi bi-question-circle-fill me-1"></i>
                        <span class="d-none d-lg-inline">Ayuda</span>
                    </a>
                </li>

                <!-- إشعارات (اختيارية) -->
                <?php if ($spare_parts_permissions['manage_spare_parts'] && $shop_id): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-dropdown-toggle>
                            <i class="bi bi-bell"></i>
                            <?php
                            try {
                                $notifications_count = 0;
                                if ($spare_parts_permissions['manage_stock'] && isset($_SESSION['shop_id'])) {
                                    $low_stock_parts = getLowStockParts($_SESSION['shop_id']);
                                    $notifications_count += count($low_stock_parts);
                                }

                                if ($notifications_count > 0):
                                    ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $notifications_count > 9 ? '9+' : $notifications_count ?>
            <span class="visually-hidden">notificaciones pendientes</span>
        </span>
                                <?php
                                endif;
                            } catch (Exception $e) {
                                // تجاهل الأخطاء
                                error_log("Notifications error: " . $e->getMessage());
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="bi bi-bell me-1"></i>
                                    Notificaciones
                                </h6>
                            </li>

                            <?php if (isset($low_stock_parts) && count($low_stock_parts) > 0): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= url('pages/spare_parts.php?filter=low_stock') ?>">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        <div>
                                            <div class="fw-bold">Stock Bajo</div>
                                            <small class="text-muted"><?= count($low_stock_parts) ?> repuesto(s) necesitan reposición</small>
                                        </div>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ((!isset($low_stock_parts) || count($low_stock_parts) == 0)): ?>
                                <li>
                            <span class="dropdown-item-text text-muted">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                No hay notificaciones pendientes
                            </span>
                                </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-center" href="<?= url('pages/notifications.php') ?>">
                                    <small>Ver todas las notificaciones</small>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-dropdown-toggle>
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($current_user['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <div class="fw-bold"><?= htmlspecialchars($current_user['name']) ?></div>
                                <small class="text-muted">
                                    <?= ucfirst($current_user['role']) ?> - <?= htmlspecialchars($current_shop['name']) ?>
                                </small>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= url('pages/profile.php') ?>">
                                <i class="bi bi-person me-2"></i>
                                Mi Perfil
                            </a>
                        </li>

                        <?php if ($spare_parts_permissions['view_spare_parts']): ?>
                            <li>
                                <a class="dropdown-item" href="<?= url('pages/spare_parts.php') ?>">
                                    <i class="bi bi-gear me-2"></i>
                                    Mis Repuestos
                                </a>
                            </li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= url('logout.php') ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Container -->
<div class="main-content">
    <?php endif; ?>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : ($_SESSION['message_type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <!-- Bootstrap 5 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- CSS للقوائم -->
    <style>
        .navbar-nav .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
            padding: 0.5rem 0;
            display: none !important;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .navbar-nav .dropdown-menu.show {
            display: block !important;
            opacity: 1;
            transform: translateY(0);
        }

        .navbar-nav .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            border-radius: 0.25rem;
            margin: 0 0.25rem;
        }

        .navbar-nav .dropdown-item:hover {
            background-color: rgba(13, 110, 253, 0.1);
            transform: translateX(5px);
        }

        .navbar-nav .dropdown-header {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 0.25rem;
        }

        .navbar-nav .dropdown-divider {
            margin: 0.5rem 0.25rem;
        }

        .nav-link.position-relative .badge {
            font-size: 0.6rem;
            padding: 0.25em 0.4em;
        }

        .dropdown-menu .badge {
            font-size: 0.7rem;
            padding: 0.2em 0.4em;
        }

        /* 🆕 تصميم زر المساعدة */
        .help-button {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            border-radius: 20px !important;
            color: white !important;
            border: 2px solid transparent !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .help-button:hover {
            background: linear-gradient(135deg, #218838, #1eb395) !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
        }

        .help-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .help-button:hover::before {
            left: 100%;
        }

        .help-button i {
            animation: helpPulse 2s infinite;
        }

        @keyframes helpPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @media (max-width: 991.98px) {
            .navbar-nav .dropdown-menu {
                border: none;
                box-shadow: none;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
            }

            .navbar-nav .dropdown-item {
                padding: 0.75rem 1rem;
                margin: 0;
                border-radius: 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .navbar-nav .dropdown-item:hover {
                transform: none;
                background-color: rgba(13, 110, 253, 0.1);
            }

            .help-button {
                border-radius: 15px !important;
                margin: 0.25rem 0 !important;
            }
        }

        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0.375rem;
            font-weight: 600;
        }

        .navbar-nav .dropdown-toggle.active::after {
            border-top-color: #fff;
        }

        .navbar-brand {
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            transition: all 0.3s ease;
            border-radius: 0.375rem;
            margin: 0 0.125rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .dropdown-menu {
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 576px) {
            .navbar-brand span {
                display: none;
            }

            .nav-link {
                text-align: center;
                padding: 0.75rem 1rem;
            }

            .dropdown-menu {
                width: 100%;
                border-radius: 0;
            }
        }

        .low-stock-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .dropdown-item-text .fw-bold {
            color: #0d6efd;
        }

        .dropdown-item-text small {
            color: #6c757d;
        }

        .bi {
            font-size: 1em;
            vertical-align: -0.125em;
        }

        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
        }

        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link:focus {
            color: #fff;
        }

        .navbar-dark .navbar-nav .nav-link.active {
            color: #fff;
        }

        @media (min-width: 1200px) {
            .container-fluid {
                max-width: 1400px;
            }

            .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
            }
        }

        .nav-link:focus,
        .dropdown-item:focus {
            outline: 2px solid rgba(255, 255, 255, 0.5);
            outline-offset: 2px;
        }

        @media print {
            .navbar {
                display: none !important;
            }

            .main-content {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }
        }
    </style>

    <!-- JavaScript لإصلاح القوائم -->
    <script>
        // منع التحميل المتعدد
        if (typeof window.RepairPointHeaderLoaded === 'undefined') {
            window.RepairPointHeaderLoaded = true;

            document.addEventListener('DOMContentLoaded', function() {
                // إعطاء Bootstrap وقت للتحميل
                setTimeout(initializeDropdowns, 100);

                function initializeDropdowns() {
                    // العثور على جميع عناصر القائمة المنسدلة
                    const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');

                    // إزالة أي event listeners موجودة
                    dropdownToggles.forEach(toggle => {
                        const newToggle = toggle.cloneNode(true);
                        toggle.parentNode.replaceChild(newToggle, toggle);
                    });

                    // إعادة الحصول على العناصر الجديدة
                    const freshToggles = document.querySelectorAll('[data-dropdown-toggle]');

                    // إضافة event listeners جديدة
                    freshToggles.forEach(toggle => {
                        toggle.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            const dropdownMenu = this.nextElementSibling;

                            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                                // إغلاق القوائم الأخرى
                                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                    if (menu !== dropdownMenu) {
                                        menu.classList.remove('show');
                                    }
                                });

                                // تبديل القائمة الحالية
                                const isOpen = dropdownMenu.classList.contains('show');

                                if (isOpen) {
                                    dropdownMenu.classList.remove('show');
                                    this.setAttribute('aria-expanded', 'false');
                                } else {
                                    dropdownMenu.classList.add('show');
                                    this.setAttribute('aria-expanded', 'true');
                                }
                            }
                        });

                        // إضافة attributes للوصولية
                        toggle.setAttribute('aria-haspopup', 'true');
                        toggle.setAttribute('aria-expanded', 'false');
                    });

                    // إغلاق عند النقر خارجاً
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.dropdown')) {
                            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                menu.classList.remove('show');
                                const toggle = menu.previousElementSibling;
                                if (toggle) {
                                    toggle.setAttribute('aria-expanded', 'false');
                                }
                            });
                        }
                    });

                    // إغلاق بـ Escape
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                menu.classList.remove('show');
                                const toggle = menu.previousElementSibling;
                                if (toggle) {
                                    toggle.setAttribute('aria-expanded', 'false');
                                }
                            });
                        }
                    });

                    // إضافة إغلاق القائمة الرئيسية عند اختيار رابط (للهواتف)
                    const navbarCollapse = document.getElementById('navbarNav');

                    // فقط الروابط التي تنتقل إلى صفحات أخرى (ليس dropdown-toggle)
                    const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle), .navbar-nav .dropdown-item');

                    navLinks.forEach(link => {
                        link.addEventListener('click', function(e) {
                            // التحقق من أن الرابط له href حقيقي وليس #
                            const href = this.getAttribute('href');

                            // عدم إغلاق القائمة إذا كان الرابط # أو فارغ أو javascript
                            if (!href || href === '#' || href === 'javascript:void(0)' || href.startsWith('javascript:')) {
                                return;
                            }

                            // تأخير صغير للسماح بالتنقل قبل الإغلاق
                            setTimeout(() => {
                                // إغلاق القائمة الرئيسية على الهواتف
                                if (window.innerWidth < 992 && navbarCollapse && navbarCollapse.classList.contains('show')) {
                                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                                    if (bsCollapse) {
                                        bsCollapse.hide();
                                    } else {
                                        // إذا لم يكن Bootstrap Collapse متاح، استخدم طريقة مباشرة
                                        navbarCollapse.classList.remove('show');
                                    }
                                }

                                // إغلاق القوائم المنسدلة
                                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                    menu.classList.remove('show');
                                    const toggle = menu.previousElementSibling;
                                    if (toggle) {
                                        toggle.setAttribute('aria-expanded', 'false');
                                    }
                                });
                            }, 50);
                        });
                    });
                }

                // تحديث عداد الإشعارات كل 5 دقائق
                setInterval(updateNotificationCount, 300000);

                // إعداد keyboard shortcuts
                setupKeyboardShortcuts();
            });

            // تحديث عداد الإشعارات
            function updateNotificationCount() {
                <?php if ($spare_parts_permissions['manage_spare_parts'] && $shop_id): ?>
                fetch('<?= url('api/notifications.php') ?>?action=count')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateBadges(data.data);
                        }
                    })
                    .catch(error => {
                        // تجاهل الأخطاء بصمت
                    });
                <?php endif; ?>
            }

            // تحديث الشارات
            function updateBadges(counts) {
                // تحديث شارة الإشعارات الرئيسية
                const mainBadge = document.querySelector('#notificationsDropdown .badge');
                if (mainBadge) {
                    const total = counts.low_stock || 0;
                    if (total > 0) {
                        mainBadge.textContent = total > 9 ? '9+' : total;
                        mainBadge.style.display = 'inline';
                    } else {
                        mainBadge.style.display = 'none';
                    }
                }

                // تحديث شارة قطع الغيار
                const sparePartsBadge = document.querySelector('#sparePartsDropdown .badge');
                if (sparePartsBadge && counts.low_stock) {
                    sparePartsBadge.textContent = counts.low_stock;
                    if (counts.low_stock > 0) {
                        sparePartsBadge.classList.add('low-stock-indicator');
                    } else {
                        sparePartsBadge.classList.remove('low-stock-indicator');
                    }
                }
            }

            // إعداد keyboard shortcuts
            function setupKeyboardShortcuts() {
                document.addEventListener('keydown', function(e) {
                    // Alt + D للوحة التحكم
                    if (e.altKey && e.key === 'd') {
                        e.preventDefault();
                        window.location.href = '<?= url('pages/dashboard.php') ?>';
                    }

                    // Alt + N لرeparación جديدة
                    if (e.altKey && e.key === 'n') {
                        e.preventDefault();
                        window.location.href = '<?= url('pages/add_repair.php') ?>';
                    }

                    // Alt + S للبحث
                    if (e.altKey && e.key === 's') {
                        e.preventDefault();
                        window.location.href = '<?= url('pages/search.php') ?>';
                    }

                    // Alt + H للمساعدة 🆕
                    if (e.altKey && e.key === 'h') {
                        e.preventDefault();
                        window.location.href = '<?= url('pages/help.php') ?>';
                    }

                    // Alt + R لقطع الغيار (إذا كان مصرح)
                    <?php if ($spare_parts_permissions['view_spare_parts']): ?>
                    if (e.altKey && e.key === 'r') {
                        e.preventDefault();
                        window.location.href = '<?= url('pages/spare_parts.php') ?>';
                    }
                    <?php endif; ?>
                });
            }

            // تحديد العنصر النشط في القائمة
            function highlightActiveMenuItem() {
                const currentPage = window.location.pathname.split('/').pop();
                const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

                navLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && href.includes(currentPage)) {
                        link.classList.add('active');

                        // إذا كان داخل dropdown، تفعيل الparent أيضاً
                        const dropdown = link.closest('.dropdown');
                        if (dropdown) {
                            const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
                            if (dropdownToggle) {
                                dropdownToggle.classList.add('active');
                            }
                        }
                    }
                });
            }

            // تحديد القائمة النشطة عند التحميل
            setTimeout(highlightActiveMenuItem, 500);

        } // End if not loaded

    </script>

</body>
</html>