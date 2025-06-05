
<?php
/**
 * RepairPoint - Header común para todas las páginas
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
if (isset($_SESSION['user_id'])) {
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
    }
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
            <?php if ($current_shop && $current_shop['logo']): ?>
                <img src="<?= $current_shop['logo'] ?>" alt="Logo" height="30" class="me-2">
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
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>" 
                       href="<?= url('pages/dashboard.php') ?>">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Panel
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'add_repair.php') ? 'active' : '' ?>" 
                       href="<?= url('pages/add_repair.php') ?>">
                        <i class="bi bi-plus-circle me-1"></i>
                        Nueva Reparación
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="repairsDropdown" role="button" data-bs-toggle="dropdown">
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
                
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'active' : '' ?>" 
                       href="<?= url('pages/search.php') ?>">
                        <i class="bi bi-search me-1"></i>
                        Buscar
                    </a>
                </li>

                <?php if ($current_user && $current_user['role'] === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear me-1"></i>
                        Administración
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?= url('pages/users.php') ?>">
                                <i class="bi bi-people me-2"></i>
                                Usuarios
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= url('pages/settings.php') ?>">
                                <i class="bi bi-sliders me-2"></i>
                                Configuración
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= url('pages/reports.php') ?>">
                                <i class="bi bi-graph-up me-2"></i>
                                Informes
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <!-- User Info -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($current_user['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
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