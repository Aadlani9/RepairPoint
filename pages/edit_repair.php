<?php
/**
 * RepairPoint - Editar Reparación
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Obtener ID de la reparación
$repair_id = intval($_GET['id'] ?? 0);

if (!$repair_id) {
    setMessage('ID de reparación no válido', MSG_ERROR);
    header('Location: ' . url('pages/repairs_active.php'));
    exit;
}

// Obtener datos de la reparación
$db = getDB();
$repair = $db->selectOne(
    "SELECT r.*, b.name as brand_name, m.name as model_name
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     WHERE r.id = ? AND r.shop_id = ?",
    [$repair_id, $shop_id]
);

if (!$repair) {
    setMessage('Reparación no encontrada', MSG_ERROR);
    header('Location: ' . url('pages/repairs_active.php'));
    exit;
}

$page_title = 'Editar Reparación #' . $repair['reference'];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        // Validar datos
        $required_fields = ['customer_name', 'customer_phone', 'brand_id', 'model_id', 'issue_description'];
        $errors = validateRequired($_POST, $required_fields);

        if (empty($errors)) {
            // Sanitizar datos
            $data = sanitizeArray($_POST);

            // Validar teléfono
            if (!isValidPhone($data['customer_phone'])) {
                $errors[] = 'El formato del teléfono no es válido';
            }

            // Verificar que la marca y modelo existen
            $model = $db->selectOne(
                "SELECT m.*, b.name as brand_name FROM models m 
                 JOIN brands b ON m.brand_id = b.id 
                 WHERE m.id = ? AND m.brand_id = ?",
                [$data['model_id'], $data['brand_id']]
            );

            if (!$model) {
                $errors[] = 'Marca o modelo no válido';
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    // Actualizar reparación
                    $updated = $db->update(
                        "UPDATE repairs SET 
                            customer_name = ?, customer_phone = ?, brand_id = ?, model_id = ?, 
                            issue_description = ?, estimated_cost = ?, priority = ?, 
                            notes = ?, updated_at = NOW()
                         WHERE id = ? AND shop_id = ?",
                        [
                            $data['customer_name'],
                            $data['customer_phone'],
                            $data['brand_id'],
                            $data['model_id'],
                            $data['issue_description'],
                            !empty($data['estimated_cost']) ? $data['estimated_cost'] : null,
                            $data['priority'] ?? 'medium',
                            $data['notes'] ?? null,
                            $repair_id,
                            $shop_id
                        ]
                    );

                    if ($updated !== false) {
                        $db->commit();

                        logActivity('repair_updated', "Reparación #{$repair['reference']} actualizada", $_SESSION['user_id']);

                        setMessage('Reparación actualizada correctamente', MSG_SUCCESS);

                        // Redirigir según la opción elegida
                        if (isset($_POST['action']) && $_POST['action'] === 'save_continue') {
                            // Permanecer en la página de edición
                            header('Location: ' . url('pages/edit_repair.php?id=' . $repair_id));
                        } else {
                            // Ir a detalles
                            header('Location: ' . url('pages/repair_details.php?id=' . $repair_id));
                        }
                        exit;
                    } else {
                        throw new Exception('Error al actualizar la reparación');
                    }
                } catch (Exception $e) {
                    $db->rollback();
                    error_log("Error actualizando reparación: " . $e->getMessage());
                    setMessage('Error al actualizar la reparación', MSG_ERROR);
                }
            }
        }

        if (!empty($errors)) {
            setMessage(implode('<br>', $errors), MSG_ERROR);
        }
    }
}

// Obtener marcas para el formulario
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$common_issues = $db->select("SELECT * FROM common_issues ORDER BY category, issue_text");

// Si hay datos POST, usar esos; si no, usar los de la base de datos
// إضافة معالجة آمنة للبيانات هنا
$form_data = safeFormData($_POST ? $_POST : $repair);

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
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/repairs_active.php') ?>">
                        <i class="bi bi-tools"></i> Reparaciones
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/repair_details.php?id=' . $repair['id']) ?>">
                        <i class="bi bi-eye"></i> #<?= safeHtml($repair['reference']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-pencil"></i> Editar
                </li>
            </ol>
        </nav>

        <!-- Header de la página -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header bg-warning text-dark p-4 rounded">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-1">
                                <i class="bi bi-pencil me-2"></i>
                                Editar Reparación #<?= safeHtml($repair['reference']) ?>
                            </h1>
                            <p class="mb-0 opacity-75">
                                Modifica la información de la reparación
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="<?= url('pages/repair_details.php?id=' . $repair['id']) ?>" class="btn btn-dark">
                                <i class="bi bi-arrow-left me-2"></i>Volver a Detalles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mostrar mensajes -->
        <?php displayMessage(); ?>

        <!-- Formulario principal -->
        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <div class="row">
                <!-- Formulario principal -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person-gear me-2"></i>
                                Información de la Reparación
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Información del cliente -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-muted border-bottom pb-2 mb-3">
                                        <i class="bi bi-person-vcard me-2"></i>Datos del Cliente
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="customer_name" class="form-label">
                                        Nombre Completo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="customer_name"
                                           name="customer_name"
                                           placeholder="Ej: Juan Pérez García"
                                           value="<?= safeHtml($form_data['customer_name']) ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor, introduce el nombre del cliente.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="customer_phone" class="form-label">
                                        Teléfono <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel"
                                           class="form-control"
                                           id="customer_phone"
                                           name="customer_phone"
                                           placeholder="Ej: +34 666 123 456"
                                           value="<?= safeHtml($form_data['customer_phone']) ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Introduce un número de teléfono válido.
                                    </div>
                                    <div class="form-text">
                                        Formato: +34 666 123 456 o 666 123 456
                                    </div>
                                </div>
                            </div>

                            <!-- Información del dispositivo -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-muted border-bottom pb-2 mb-3">
                                        <i class="bi bi-phone me-2"></i>Información del Dispositivo
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="brand_id" class="form-label">
                                        Marca <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select"
                                            id="brand_id"
                                            name="brand_id"
                                            data-target-model="model_id"
                                            required>
                                        <option value="">Selecciona una marca</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['id'] ?>"
                                                <?= safeSelected($form_data['brand_id'], $brand['id']) ?>>
                                                <?= safeHtml($brand['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor, selecciona una marca.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="model_id" class="form-label">
                                        Modelo <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select"
                                            id="model_id"
                                            name="model_id"
                                            required>
                                        <option value="">Cargando modelos...</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor, selecciona un modelo.
                                    </div>
                                </div>
                            </div>

                            <!-- Descripción del problema -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-muted border-bottom pb-2 mb-3">
                                        <i class="bi bi-exclamation-triangle me-2"></i>Descripción del Problema
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="issue_description" class="form-label">
                                        Problema Reportado <span class="text-danger">*</span>
                                    </label>

                                    <!-- Problemas comunes -->
                                    <?php if (!empty($common_issues)): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Problemas comunes (clic para usar):</small>
                                            <div class="common-issues-buttons mt-1">
                                                <?php
                                                $current_category = '';
                                                foreach ($common_issues as $issue):
                                                    if ($current_category !== $issue['category']):
                                                        if ($current_category !== '') echo '<br>';
                                                        $current_category = $issue['category'];
                                                        ?>
                                                        <small class="text-primary fw-bold"><?= safeHtml($issue['category']) ?>:</small>
                                                    <?php endif; ?>
                                                    <button type="button"
                                                            class="btn btn-outline-secondary btn-sm me-1 mb-1 common-issue-btn"
                                                            data-issue="<?= safeHtml($issue['issue_text']) ?>">
                                                        <?= safeHtml($issue['issue_text']) ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <textarea class="form-control"
                                              id="issue_description"
                                              name="issue_description"
                                              rows="4"
                                              placeholder="Describe detalladamente el problema del dispositivo..."
                                              required><?= safeHtml($form_data['issue_description']) ?></textarea>
                                    <div class="invalid-feedback">
                                        Por favor, describe el problema del dispositivo.
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-muted border-bottom pb-2 mb-3">
                                        <i class="bi bi-gear me-2"></i>Información Adicional
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">Prioridad</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?= safeSelected($form_data['priority'], 'low') ?>>
                                            Baja
                                        </option>
                                        <option value="medium" <?= safeSelected($form_data['priority'], 'medium') ?>>
                                            Media
                                        </option>
                                        <option value="high" <?= safeSelected($form_data['priority'], 'high') ?>>
                                            Alta
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="estimated_cost" class="form-label">Coste Estimado (€)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number"
                                               class="form-control"
                                               id="estimated_cost"
                                               name="estimated_cost"
                                               placeholder="0.00"
                                               value="<?= safeHtml($form_data['estimated_cost']) ?>"
                                               step="0.01"
                                               min="0">
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label">Notas Internas</label>
                                    <textarea class="form-control"
                                              id="notes"
                                              name="notes"
                                              rows="3"
                                              placeholder="Notas adicionales para uso interno..."><?= safeHtml($form_data['notes']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral -->
                <div class="col-lg-4">
                    <!-- Información actual -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Información Actual
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="current-info">
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Referencia:</span>
                                    <span class="text-primary">#<?= safeHtml($repair['reference']) ?></span>
                                </div>
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Estado:</span>
                                    <?= getStatusBadge($repair['status']) ?>
                                </div>
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Recibido:</span>
                                    <span><?= safeDateFormat($repair['received_at']) ?></span>
                                </div>
                                <?php if ($repair['completed_at']): ?>
                                    <div class="info-row mb-2">
                                        <span class="fw-bold">Completado:</span>
                                        <span><?= safeDateFormat($repair['completed_at']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($repair['delivered_at']): ?>
                                    <div class="info-row mb-2">
                                        <span class="fw-bold">Entregado:</span>
                                        <span><?= safeDateFormat($repair['delivered_at']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-check-square me-2"></i>
                                Acciones
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>
                                    Guardar Cambios
                                </button>
                                <button type="submit" name="action" value="save_continue" class="btn btn-success">
                                    <i class="bi bi-save me-2"></i>
                                    Guardar y Continuar Editando
                                </button>
                                <a href="<?= url('pages/repair_details.php?id=' . $repair['id']) ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de cambios -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Última Modificación
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="history-info">
                                <div class="text-muted small">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= safeDateFormat($repair['updated_at'] ?: $repair['created_at']) ?>
                                </div>
                                <?php if ($repair['updated_at']): ?>
                                    <div class="text-muted small mt-1">
                                        Actualizada por última vez
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small mt-1">
                                        Fecha de creación
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<style>


/* Estilos específicos para editar reparación */
.page-header {
    background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%);
    color: #000;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 150px;
    height: 150px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    transform: translate(50px, -50px);
}

.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-header {
    background: rgba(255, 193, 7, 0.1);
    border-bottom: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1rem 1.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--warning-color);
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

.common-issue-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.common-issue-btn:hover {
    background-color: var(--warning-color);
    border-color: var(--warning-color);
    color: #000;
    transform: translateY(-1px);
}

.current-info {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 0.5rem;
    padding: 1rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
}

.info-row span:first-child {
    min-width: 40%;
    font-size: 0.875rem;
}

.info-row span:last-child {
    text-align: right;
    font-size: 0.875rem;
}

.history-info {
    text-align: center;
    padding: 1rem;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 0.5rem;
}

.breadcrumb {
    background: transparent;
    padding: 0;
}

.breadcrumb-item a {
    text-decoration: none;
    color: var(--warning-color);
}

.breadcrumb-item.active {
    color: var(--secondary-color);
}

/* Estados especiales */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35rem 0.65rem;
}

/* Animaciones */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: slideInUp 0.5s ease-out;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        text-align: center;
        padding: 2rem 1rem !important;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .common-issues-buttons {
        text-align: center;
    }
    
    .common-issue-btn {
        font-size: 0.7rem;
        margin-bottom: 0.5rem;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .info-row span:last-child {
        text-align: left;
        margin-top: 0.25rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .page-header {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        border-radius: 0;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .common-issue-btn {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }
    
    .current-info {
        padding: 0.75rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
}

/* Indicadores de cambios */
.form-control.changed,
.form-select.changed {
    border-left: 4px solid var(--warning-color);
    background-color: rgba(255, 193, 7, 0.05);
}

/* Validación mejorada */
.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: var(--success-color);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.8-.8-.8-.8 1.2-1.2.8.8.8-.8L5.3 4.15l-.8.8.8.8-1.2 1.2-.8-.8-.8.8-1.2-1.2z'/%3e%3c/svg%3e");
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: var(--danger-color);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.8m0-2.8-2.4 2.8'/%3e%3c/svg%3e");
}
</style>

    <script>
        // JavaScript المحسن لحل مشكلة تحميل النماذج
        <?php echo file_get_contents('edit_repair_fixed_js.js'); ?>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Edit Repair - DOM Ready');

            // إعداد التحقق من صحة النموذج
            setupFormValidation();

            // إعداد تحميل الموديلات - هذا هو الأهم
            setupBrandModelHandling();

            // إعداد الهاتف
            setupPhoneFormatting();

            // إعداد الأزرار الشائعة
            setupCommonIssues();

            // إعداد كشف التغييرات
            setupChangeDetection();

            // تحميل الموديلات فوراً عند تحميل الصفحة
            loadInitialModels();

            console.log('✅ Edit Repair - All initialized');
        });

        /**
         * تحميل الموديلات الأولية عند تحميل الصفحة
         */
        function loadInitialModels() {
            const brandSelect = document.getElementById('brand_id');
            const modelSelect = document.getElementById('model_id');
            const currentModelId = <?php echo intval($repair['model_id']); ?>;

            console.log('📱 Loading initial models:', {
                brandId: brandSelect ? brandSelect.value : 'NOT_FOUND',
                currentModelId: currentModelId
            });

            // إذا كان هناك brand محدد، حمّل النماذج
            if (brandSelect && brandSelect.value) {
                loadModels(brandSelect.value, modelSelect, currentModelId);
            }
        }

        /**
         * إعداد تحميل الموديلات حسب الماركة
         */
        function setupBrandModelHandling() {
            const brandSelect = document.getElementById('brand_id');
            const modelSelect = document.getElementById('model_id');

            if (!brandSelect || !modelSelect) {
                console.error('❌ Brand or Model select not found');
                return;
            }

            // استمع لتغيير الماركة
            brandSelect.addEventListener('change', function() {
                const brandId = this.value;
                console.log('🏷️ Brand changed:', brandId);

                if (brandId) {
                    loadModels(brandId, modelSelect);
                } else {
                    // إذا لم يتم اختيار ماركة، امسح النماذج
                    modelSelect.innerHTML = '<option value="">Selecciona una marca primero</option>';
                    modelSelect.disabled = true;
                }
            });

            // استمع لتغيير النموذج
            modelSelect.addEventListener('change', function() {
                console.log('📱 Model changed:', this.value);
                // إزالة أي رسائل خطأ سابقة
                this.classList.remove('is-invalid');
                if (this.value) {
                    this.classList.add('is-valid');
                }
            });
        }

        /**
         * تحميل النماذج من API
         */
        async function loadModels(brandId, modelSelect, selectedModelId = null) {
            if (!brandId) {
                modelSelect.innerHTML = '<option value="">Selecciona una marca primero</option>';
                modelSelect.disabled = true;
                return;
            }

            console.log('🔄 Loading models for brand:', brandId, 'Selected:', selectedModelId);

            try {
                // إظهار مؤشر التحميل
                modelSelect.innerHTML = '<option value="">Cargando modelos...</option>';
                modelSelect.disabled = true;

                // بناء URL للـ API
                const apiUrl = '<?php echo url('api/models.php'); ?>?brand_id=' + brandId + '&action=get_by_brand';
                console.log('🌐 API URL:', apiUrl);

                // تحميل النماذج من API
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }

                const data = await response.json();
                console.log('📊 API Response:', data);

                // مسح النماذج الحالية
                modelSelect.innerHTML = '<option value="">Selecciona un modelo</option>';

                if (data.success && data.data && data.data.length > 0) {
                    // إضافة النماذج
                    data.data.forEach(function(model) {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;

                        // تحديد النموذج المحدد مسبقاً
                        if (selectedModelId && model.id == selectedModelId) {
                            option.selected = true;
                            console.log('✅ Selected model:', model.name);
                        }

                        modelSelect.appendChild(option);
                    });

                    // تفعيل القائمة
                    modelSelect.disabled = false;

                    // إزالة أي رسائل خطأ
                    modelSelect.classList.remove('is-invalid');
                    if (modelSelect.value) {
                        modelSelect.classList.add('is-valid');
                    }

                    console.log('✅ Models loaded successfully:', data.data.length);
                } else {
                    // لا توجد نماذج أو فشل في التحميل
                    modelSelect.innerHTML = '<option value="">No hay modelos disponibles</option>';
                    modelSelect.disabled = true;
                    console.log('⚠️ No models found for brand:', brandId);

                    // إذا كان لدينا debug info، اطبعها
                    if (data.debug) {
                        console.log('🐛 Debug info:', data.debug);
                    }
                }

            } catch (error) {
                console.error('❌ Error loading models:', error);

                // إظهار رسالة خطأ
                modelSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
                modelSelect.disabled = true;

                // إظهار notification للمستخدم
                showNotification('Error al cargar los modelos. Por favor, recarga la página.', 'error');
            }
        }

        /**
         * إعداد التحقق من صحة النموذج
         */
        function setupFormValidation() {
            const form = document.querySelector('.needs-validation');

            if (!form) return;

            // منع الإرسال الافتراضي إذا كان النموذج غير صالح
            form.addEventListener('submit', function(event) {
                // تحقق خاص للنموذج المحدد
                const modelSelect = document.getElementById('model_id');
                if (!modelSelect.value) {
                    event.preventDefault();
                    event.stopPropagation();

                    modelSelect.classList.add('is-invalid');
                    modelSelect.focus();

                    showNotification('Por favor, selecciona un modelo de teléfono', 'warning');
                    return false;
                }

                // التحقق العام من صحة النموذج
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    // البحث عن أول حقل غير صالح والتركيز عليه
                    const firstInvalidField = form.querySelector('.form-control:invalid, .form-select:invalid');
                    if (firstInvalidField) {
                        firstInvalidField.focus();
                    }
                }

                form.classList.add('was-validated');
            });
        }

        /**
         * إعداد تنسيق الهاتف
         */
        function setupPhoneFormatting() {
            const phoneInput = document.getElementById('customer_phone');
            if (!phoneInput) return;

            // دالة للتحقق من صحة الرقم
            function isValidPhone(phone) {
                if (!phone) return false;

                const clean = phone.replace(/[\s\-\.\(\)]/g, '');
                const patterns = [
                    /^\+34[6789]\d{8}$/,    // +34xxxxxxxxx
                    /^0034[6789]\d{8}$/,    // 0034xxxxxxxxx
                    /^34[6789]\d{8}$/,      // 34xxxxxxxxx
                    /^[6789]\d{8}$/         // xxxxxxxxx
                ];

                return patterns.some(function(pattern) {
                    return pattern.test(clean);
                });
            }

            // إزالة validation تلقائي عند التحميل
            phoneInput.addEventListener('focus', function() {
                this.classList.remove('is-invalid', 'is-valid');
                this.setCustomValidity('');
            });

            // التحقق فقط عند blur
            phoneInput.addEventListener('blur', function() {
                if (!this.value) {
                    this.classList.add('is-invalid');
                    this.setCustomValidity('Este campo es obligatorio');
                } else if (isValidPhone(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    this.setCustomValidity('');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                    this.setCustomValidity('Número de teléfono no válido');
                }
            });

            // منع validation أثناء الكتابة
            phoneInput.addEventListener('input', function() {
                this.classList.remove('is-invalid', 'is-valid');
                this.setCustomValidity('');
            });
        }

        /**
         * إعداد الأزرار الشائعة
         */
        function setupCommonIssues() {
            const commonIssueButtons = document.querySelectorAll('.common-issue-btn');
            const issueTextarea = document.getElementById('issue_description');

            if (!issueTextarea) return;

            commonIssueButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const issueText = this.getAttribute('data-issue');

                    if (issueTextarea.value.trim() === '') {
                        issueTextarea.value = issueText;
                    } else {
                        const confirmation = confirm('¿Quieres reemplazar el texto actual con este problema común?');
                        if (confirmation) {
                            issueTextarea.value = issueText;
                        }
                    }

                    issueTextarea.focus();
                    markAsChanged(issueTextarea);
                });
            });
        }

        /**
         * إعداد كشف التغييرات
         */
        function setupChangeDetection() {
            const inputs = document.querySelectorAll('input, select, textarea');
            const originalValues = {};

            // حفظ القيم الأصلية
            inputs.forEach(function(input) {
                originalValues[input.name] = input.value;
            });

            // كشف التغييرات
            inputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    if (this.value !== originalValues[this.name]) {
                        markAsChanged(this);
                    } else {
                        markAsUnchanged(this);
                    }
                });

                input.addEventListener('change', function() {
                    if (this.value !== originalValues[this.name]) {
                        markAsChanged(this);
                    } else {
                        markAsUnchanged(this);
                    }
                });
            });
        }

        /**
         * تمييز الحقول المتغيرة
         */
        function markAsChanged(element) {
            element.classList.add('changed');
        }

        function markAsUnchanged(element) {
            element.classList.remove('changed');
        }

        /**
         * التحقق من وجود تغييرات غير محفوظة
         */
        function hasUnsavedChanges() {
            return document.querySelectorAll('.changed').length > 0;
        }

        /**
         * إظهار notification
         */
        function showNotification(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;

            if (typeof Utils !== 'undefined' && Utils.showNotification) {
                Utils.showNotification(message, type, duration);
            } else {
                // fallback بسيط
                alert(message);
            }
        }

        // اختصارات لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S للحفظ
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const saveButton = document.querySelector('button[name="action"][value="save"]');
                if (saveButton) {
                    saveButton.click();
                }
            }
        });
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>