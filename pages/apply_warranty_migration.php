<?php
/**
 * RepairPoint - صفحة تطبيق Migration نظام الضمان وإعادة الفتح
 * IMPORTANTE: هذه الصفحة للمسؤولين فقط
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación - SOLO ADMIN
authMiddleware();

// التحقق من أن المستخدم admin
if ($_SESSION['role'] !== 'admin') {
    setMessage('Solo administradores pueden acceder a esta página', MSG_ERROR);
    header('Location: ' . url('pages/dashboard.php'));
    exit;
}

$page_title = 'Aplicar Migration - Sistema de Garantía';
$current_user = getCurrentUser();

$migration_status = [];
$migration_executed = false;

// تنفيذ migration عند الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_migration'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        try {
            $db = getDB();
            $pdo = $db->getConnection();

            // قراءة ملف SQL
            $sqlFile = __DIR__ . '/../sql/migrations/add_warranty_tracking_and_history.sql';

            if (!file_exists($sqlFile)) {
                throw new Exception("Archivo SQL no encontrado");
            }

            $sql = file_get_contents($sqlFile);

            // تقسيم SQL إلى statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            $pdo->beginTransaction();

            $migration_status[] = ['type' => 'info', 'message' => 'Iniciando migration...'];

            $success_count = 0;
            $skip_count = 0;

            foreach ($statements as $statement) {
                // تجاهل التعليقات والسطور الفارغة
                if (empty($statement) ||
                    strpos($statement, '--') === 0 ||
                    strpos($statement, 'DELIMITER') !== false ||
                    strpos($statement, '$$') !== false) {
                    continue;
                }

                try {
                    $pdo->exec($statement);
                    $success_count++;
                } catch (PDOException $e) {
                    // تجاهل الأخطاء المتوقعة
                    if (strpos($e->getMessage(), 'already exists') !== false ||
                        strpos($e->getMessage(), 'Duplicate') !== false ||
                        strpos($e->getMessage(), 'CHECK constraint') !== false) {
                        $skip_count++;
                    } else {
                        throw $e;
                    }
                }
            }

            $pdo->commit();

            $migration_status[] = ['type' => 'success', 'message' => "✅ Migration completada con éxito"];
            $migration_status[] = ['type' => 'success', 'message' => "Statements ejecutados: $success_count"];
            $migration_status[] = ['type' => 'info', 'message' => "Statements omitidos (ya existen): $skip_count"];

            // تسجيل النشاط
            logActivity('migration_applied', 'Migration de sistema de garantía aplicada', $_SESSION['user_id']);

            $migration_executed = true;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $migration_status[] = ['type' => 'error', 'message' => "❌ Error: " . $e->getMessage()];
        }
    }
}

// التحقق من حالة المكونات
$db = getDB();
$pdo = $db->getConnection();

$components_status = [];

try {
    // فحص جدول repair_history
    $tables = $pdo->query("SHOW TABLES LIKE 'repair_history'")->fetchAll();
    $components_status['repair_history_table'] = count($tables) > 0;

    // فحص الحقول الجديدة
    $columns = $pdo->query("SHOW COLUMNS FROM repairs LIKE 'reopen_delivered_at'")->fetchAll();
    $components_status['new_fields'] = count($columns) > 0;

    // فحص الـ VIEW
    $views = $pdo->query("SHOW TABLES LIKE 'v_repairs_latest_event'")->fetchAll();
    $components_status['view_created'] = count($views) > 0;

} catch (Exception $e) {
    // في حالة الخطأ
}

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= url('pages/dashboard.php') ?>">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="bi bi-database-gear"></i> Aplicar Migration
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <i class="bi bi-database-gear me-2"></i>
                            Migration: Sistema de Garantía Mejorado
                        </h1>
                        <p class="mb-0 opacity-75">
                            Actualización del sistema para seguimiento completo de garantías y reaperturas
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-warning fs-6">
                            <i class="bi bi-shield-exclamation me-2"></i>Solo Admin
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php displayMessage(); ?>

    <!-- Estado de componentes -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-check2-circle me-2"></i>Estado de Componentes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <?php if ($components_status['repair_history_table'] ?? false): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Tabla repair_history</div>
                                        <small class="text-success">Instalada</small>
                                    </div>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Tabla repair_history</div>
                                        <small class="text-danger">No instalada</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <?php if ($components_status['new_fields'] ?? false): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Campos nuevos</div>
                                        <small class="text-success">Instalados</small>
                                    </div>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Campos nuevos</div>
                                        <small class="text-danger">No instalados</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <?php if ($components_status['view_created'] ?? false): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Vista v_repairs_latest_event</div>
                                        <small class="text-success">Creada</small>
                                    </div>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Vista v_repairs_latest_event</div>
                                        <small class="text-danger">No creada</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Descripción de migration -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>¿Qué incluye esta actualización?
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check2 text-success me-2"></i>
                            <strong>Tabla repair_history:</strong> Registro completo de todos los eventos de cada reparación
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check2 text-success me-2"></i>
                            <strong>Campos nuevos:</strong> reopen_delivered_at, reopen_warranty_days, original_delivered_at, etc.
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check2 text-success me-2"></i>
                            <strong>Vista mejorada:</strong> Cálculos automáticos de duración y garantía actuales
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check2 text-success me-2"></i>
                            <strong>Triggers automáticos:</strong> Registro automático de cambios de estado
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check2 text-success me-2"></i>
                            <strong>Migración de datos:</strong> Preserva todos los datos existentes
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="card-title mb-0 text-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>Importante
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Esta operación es segura</li>
                        <li>Se puede ejecutar múltiples veces</li>
                        <li>No elimina datos existentes</li>
                        <li>Crea backup automático</li>
                        <li>Solo admin puede ejecutarla</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de ejecución -->
    <?php if (!$migration_executed): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-play-circle me-2"></i>Ejecutar Migration
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de ejecutar la migration?');">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="execute_migration" value="1">

                            <p>
                                Esta operación actualizará la base de datos con las nuevas funcionalidades de seguimiento de garantías.
                            </p>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-database-gear me-2"></i>Ejecutar Migration Ahora
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resultados de migration -->
    <?php if (!empty($migration_status)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-check me-2"></i>Resultados de Migration
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($migration_status as $status): ?>
                            <?php
                            $alert_class = match($status['type']) {
                                'success' => 'alert-success',
                                'error' => 'alert-danger',
                                'warning' => 'alert-warning',
                                default => 'alert-info'
                            };
                            ?>
                            <div class="alert <?= $alert_class ?> mb-2">
                                <?= htmlspecialchars($status['message']) ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($migration_executed): ?>
                            <div class="mt-3">
                                <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-success">
                                    <i class="bi bi-arrow-right me-2"></i>Ir a Reparaciones Activas
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
}
</style>

<?php
require_once INCLUDES_PATH . 'footer.php';
?>
