<?php
/**
 * RepairPoint - Agregar Nueva Reparación
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Nueva Reparación';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

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
            $db = getDB();
            
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
                    
                    // Generar referencia única
                    $reference = generateRepairReference();
                    
                    // Insertar reparación
                    $repair_id = $db->insert(
                        "INSERT INTO repairs (
                            reference, customer_name, customer_phone, brand_id, model_id, 
                            issue_description, estimated_cost, priority, status, 
                            received_at, created_by, shop_id, notes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?)",
                        [
                            $reference,
                            $data['customer_name'],
                            $data['customer_phone'],
                            $data['brand_id'],
                            $data['model_id'],
                            $data['issue_description'],
                            !empty($data['estimated_cost']) ? $data['estimated_cost'] : null,
                            $data['priority'] ?? 'medium',
                            $_SESSION['user_id'],
                            $shop_id,
                            $data['notes'] ?? null
                        ]
                    );
                    
                    if ($repair_id) {
                        $db->commit();
                        
                        logActivity('repair_created', "Nueva reparación #$reference creada", $_SESSION['user_id']);
                        
                        setMessage('Reparación registrada correctamente con referencia #' . $reference, MSG_SUCCESS);
                        
                        // Redirigir según la opción elegida
                        if (isset($_POST['action']) && $_POST['action'] === 'print') {
                            header('Location: ' . url('pages/print_ticket.php?id=' . $repair_id));
                        } elseif (isset($_POST['action']) && $_POST['action'] === 'continue') {
                            header('Location: ' . url('pages/add_repair.php'));
                        } else {
                            header('Location: ' . url('pages/repair_details.php?id=' . $repair_id));
                        }
                        exit;
                    } else {
                        throw new Exception('Error al insertar la reparación');
                    }
                } catch (Exception $e) {
                    $db->rollback();
                    error_log("Error creando reparación: " . $e->getMessage());
                    setMessage('Error al registrar la reparación', MSG_ERROR);
                }
            }
        }
        
        if (!empty($errors)) {
            setMessage(implode('<br>', $errors), MSG_ERROR);
        }
    }
}

// Obtener marcas para el formulario
$db = getDB();
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$common_issues = $db->select("SELECT * FROM common_issues ORDER BY category, issue_text");

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
                <i class="bi bi-plus-circle"></i> Nueva Reparación
            </li>
        </ol>
    </nav>
    
    <!-- Header de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nueva Reparación
                        </h1>
                        <p class="mb-0 opacity-75">
                            Registra una nueva reparación en el sistema
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-light">
                            <i class="bi bi-list me-2"></i>Ver Reparaciones
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
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person me-2"></i>
                            Información del Cliente y Dispositivo
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
                                       placeholder="Ej: Mohammed Adlani.. "
                                       value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
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
                                       autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>"
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
                                            <?= (($_POST['brand_id'] ?? '') == $brand['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($brand['name']) ?>
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
                                        required
                                        disabled>
                                    <option value="">Selecciona una marca primero</option>
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
                                        <small class="text-muted">Problemas comunes (clic para seleccionar):</small>
                                        <div class="common-issues-buttons mt-1">
                                            <?php
                                            $current_category = '';
                                            foreach ($common_issues as $issue):
                                                if ($current_category !== $issue['category']):
                                                    if ($current_category !== '') echo '<br>';
                                                    $current_category = $issue['category'];
                                                    ?>
                                                    <small class="text-primary fw-bold"><?= htmlspecialchars($issue['category']) ?>:</small>
                                                <?php endif; ?>
                                                <button type="button"
                                                        class="btn btn-outline-secondary btn-sm me-1 mb-1 common-issue-btn"
                                                        data-issue="<?= htmlspecialchars($issue['issue_text']) ?>">
                                                    <?= htmlspecialchars($issue['issue_text']) ?>
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
                                          required><?= htmlspecialchars($_POST['issue_description'] ?? '') ?></textarea>
                                <div class="invalid-feedback">
                                    Por favor, describe el problema del dispositivo.
                                </div>
                                <div class="form-text">
                                    Incluye todos los detalles posibles para un mejor diagnóstico.
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
                                    <option value="low" <?= (($_POST['priority'] ?? '') === 'low') ? 'selected' : '' ?>>
                                        Baja
                                    </option>
                                    <option value="medium" <?= (($_POST['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>
                                        Media
                                    </option>
                                    <option value="high" <?= (($_POST['priority'] ?? '') === 'high') ? 'selected' : '' ?>>
                                        Alta
                                    </option>
                                </select>
                                <div class="form-text">
                                    Prioridad según la urgencia del cliente.
                                </div>
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
                                           value="<?= htmlspecialchars($_POST['estimated_cost'] ?? '') ?>"
                                           step="0.01"
                                           min="0">
                                </div>
                                <div class="form-text">
                                    Estimación inicial del coste de reparación.
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="notes" class="form-label">Notas Internas</label>
                                <textarea class="form-control"
                                          id="notes"
                                          name="notes"
                                          rows="3"
                                          placeholder="Notas adicionales para uso interno..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                                <div class="form-text">
                                    Información adicional solo visible para el personal.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel lateral con acciones -->
            <div class="col-12 col-lg-4">
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
                                Guardar Reparación
                            </button>
                            <button type="submit" name="action" value="print" class="btn btn-success">
                                <i class="bi bi-printer me-2"></i>
                                Guardar e Imprimir Ticket
                            </button>
                            <button type="submit" name="action" value="continue" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle me-2"></i>
                                Guardar y Agregar Otra
                            </button>
                            <a href="<?= url('pages/dashboard.php') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Vista previa del ticket -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-eye me-2"></i>
                            Vista Previa del Ticket
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="ticket-preview" id="ticketPreview">
                            <div class="ticket-header text-center mb-2">
                                <strong><?= htmlspecialchars($current_user['shop_name']) ?></strong>
                            </div>
                            <div class="ticket-content">
                                <div class="ticket-row">
                                    <span>Cliente:</span>
                                    <span id="preview_customer">-</span>
                                </div>
                                <div class="ticket-row">
                                    <span>Teléfono:</span>
                                    <span id="preview_phone">-</span>
                                </div>
                                <div class="ticket-row">
                                    <span>Dispositivo:</span>
                                    <span id="preview_device">-</span>
                                </div>
                                <div class="ticket-row">
                                    <span>Problema:</span>
                                    <span id="preview_issue">-</span>
                                </div>
                                <div class="ticket-row">
                                    <span>Fecha:</span>
                                    <span><?= formatDateTime(date('Y-m-d H:i:s')) ?></span>
                                </div>
                                <div class="ticket-row">
                                    <span>Referencia:</span>
                                    <span>Se generará automáticamente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<style>
/* Estilos específicos para agregar reparación */
.page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
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
    background: rgba(255, 255, 255, 0.1);
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
    background: rgba(13, 110, 253, 0.05);
    border-bottom: 1px solid rgba(13, 110, 253, 0.1);
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1rem 1.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.common-issue-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.common-issue-btn:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    transform: translateY(-1px);
}

.ticket-preview {
    max-width: 100%;
    background: white;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    line-height: 1.4;
}

.ticket-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    padding: 0.1rem 0;
}

.ticket-row span:first-child {
    font-weight: bold;
    min-width: 40%;
}

.ticket-row span:last-child {
    text-align: right;
    word-break: break-word;
    max-width: 55%;
}

.breadcrumb {
    background: transparent;
    padding: 0;
}

.breadcrumb-item a {
    text-decoration: none;
    color: var(--primary-color);
}

.breadcrumb-item.active {
    color: var(--secondary-color);
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
    
    .ticket-preview {
        font-size: 0.75rem;
    }
    
    .btn {
        font-size: 0.875rem;
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
        font-size: 0.7rem;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar validación del formulario
    const form = document.querySelector('.needs-validation');
    const preview = initTicketPreview();
    
    // Manejar botones de problemas comunes
    setupCommonIssues();
    
    // Validación en tiempo real
    setupFormValidation(form);
    
    // Actualizar vista previa en tiempo real
    updatePreviewOnChange();
    
    // Auto-format teléfono
    setupPhoneFormatting();
});

function initTicketPreview() {
    return {
        customer: document.getElementById('preview_customer'),
        phone: document.getElementById('preview_phone'),
        device: document.getElementById('preview_device'),
        issue: document.getElementById('preview_issue')
    };
}

function setupCommonIssues() {
    const commonIssueButtons = document.querySelectorAll('.common-issue-btn');
    const issueTextarea = document.getElementById('issue_description');
    
    commonIssueButtons.forEach(button => {
        button.addEventListener('click', function() {
            const issueText = this.getAttribute('data-issue');
            issueTextarea.value = issueText;
            issueTextarea.focus();
            updateTicketPreview();
        });
    });
}

function setupFormValidation(form) {
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Validación en tiempo real للحقول العادية فقط (بدون الهاتف)
    const inputs = form.querySelectorAll('input:not(#customer_phone), select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
}



function updatePreviewOnChange() {
    const fields = [
        'customer_name', 
        'customer_phone', 
        'brand_id', 
        'model_id', 
        'issue_description'
    ];
    
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateTicketPreview);
            field.addEventListener('change', updateTicketPreview);
        }
    });
}

function updateTicketPreview() {
    const preview = {
        customer: document.getElementById('preview_customer'),
        phone: document.getElementById('preview_phone'),
        device: document.getElementById('preview_device'),
        issue: document.getElementById('preview_issue')
    };
    
    // Actualizar nombre
    const customerName = document.getElementById('customer_name').value;
    preview.customer.textContent = customerName || '-';
    
    // Actualizar teléfono
    const phone = document.getElementById('customer_phone').value;
    preview.phone.textContent = phone || '-';
    
    // Actualizar dispositivo
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');
    const brandName = brandSelect.options[brandSelect.selectedIndex]?.text || '';
    const modelName = modelSelect.options[modelSelect.selectedIndex]?.text || '';
    
    if (brandName && modelName && modelName !== 'Selecciona una marca primero') {
        preview.device.textContent = `${brandName} ${modelName}`;
    } else if (brandName) {
        preview.device.textContent = brandName;
    } else {
        preview.device.textContent = '-';
    }
    
    // Actualizar problema
    const issue = document.getElementById('issue_description').value;
    preview.issue.textContent = issue ? (issue.length > 30 ? issue.substring(0, 30) + '...' : issue) : '-';
}

// function setupPhoneFormatting() {
//     const phoneInput = document.getElementById('customer_phone');
//
//     phoneInput.addEventListener('input', function() {
//         let value = this.value.replace(/\s/g, '');
//
//         // إزالة أي أحرف غير رقمية ما عدا + في البداية
//         value = value.replace(/[^\d+]/g, '');
//
//         // إذا لم يبدأ بـ +34 ولكن بدأ برقم 6-9
//         if (/^[6789]/.test(value) && value.length >= 9) {
//             // تنسيق للرقم الإسباني بدون +34
//             if (value.length === 9) {
//                 this.value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
//             }
//         }
//         // إذا بدأ بـ +34
//         else if (value.startsWith('+34')) {
//             let number = value.substring(3);
//             if (number.length >= 9) {
//                 number = number.substring(0, 9);
//                 this.value = '+34 ' + number.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
//             } else {
//                 this.value = '+34 ' + number;
//             }
//         }
//         // إذا بدأ بـ 34
//         else if (value.startsWith('34') && !value.startsWith('+34')) {
//             let number = value.substring(2);
//             if (number.length >= 9) {
//                 number = number.substring(0, 9);
//                 this.value = '+34 ' + number.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
//             } else {
//                 this.value = '+34 ' + number;
//             }
//         }
//
//         // تحقق فوري من صحة الرقم
//         this.setCustomValidity('');
//         if (this.value) {
//             const cleanNumber = this.value.replace(/\s/g, '');
//             const spanishPhonePattern = /^(\+34|34)?[6789]\d{8}$/;
//
//             if (!spanishPhonePattern.test(cleanNumber)) {
//                 this.setCustomValidity('يرجى إدخال رقم هاتف إسباني صحيح');
//             }
//         }
//     });
//
//     // تحقق عند blur أيضاً
//     phoneInput.addEventListener('blur', function() {
//         if (this.value) {
//             const cleanNumber = this.value.replace(/\s/g, '');
//             const spanishPhonePattern = /^(\+34|34)?[6789]\d{8}$/;
//
//             if (spanishPhonePattern.test(cleanNumber)) {
//                 this.classList.remove('is-invalid');
//                 this.classList.add('is-valid');
//                 this.setCustomValidity('');
//             } else {
//                 this.classList.remove('is-valid');
//                 this.classList.add('is-invalid');
//                 this.setCustomValidity('يرجى إدخال رقم هاتف إسباني صحيح');
//             }
//         }
//     });
// }


function setupPhoneFormatting() {
    const phoneInput = document.getElementById('customer_phone');
    if (!phoneInput) return;

    // دالة للتحقق من صحة الرقم الإسباني
    function isValidSpanishPhone(phone) {
        if (!phone) return false;
        const cleanNumber = phone.replace(/[\s\-\.\(\)]/g, '');
        const patterns = [
            /^\+34[6789]\d{8}$/,    // +34xxxxxxxxx
            /^0034[6789]\d{8}$/,    // 0034xxxxxxxxx
            /^34[6789]\d{8}$/,      // 34xxxxxxxxx
            /^[6789]\d{8}$/,        // xxxxxxxxx
        ];
        return patterns.some(pattern => pattern.test(cleanNumber));
    }

    // إزالة validation عند focus
    phoneInput.addEventListener('focus', function() {
        this.classList.remove('is-invalid', 'is-valid');
        this.setCustomValidity('');
    });

    // تنسيق الرقم أثناء الكتابة
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\s/g, '');
        value = value.replace(/[^\d+]/g, '');

        // تنسيق حسب النمط
        if (/^[6789]/.test(value) && value.length >= 9) {
            if (value.length === 9) {
                this.value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            }
        }
        else if (value.startsWith('+34')) {
            let number = value.substring(3);
            if (number.length >= 9) {
                number = number.substring(0, 9);
                this.value = '+34 ' + number.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            } else {
                this.value = '+34 ' + number;
            }
        }
        else if (value.startsWith('34') && !value.startsWith('+34')) {
            let number = value.substring(2);
            if (number.length >= 9) {
                number = number.substring(0, 9);
                this.value = '+34 ' + number.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            } else {
                this.value = '+34 ' + number;
            }
        }

        // إزالة validation errors أثناء الكتابة
        this.classList.remove('is-invalid', 'is-valid');
        this.setCustomValidity('');
    });

    // التحقق فقط عند blur وإذا كان الحقل مليان
    phoneInput.addEventListener('blur', function() {
        if (!this.value) {
            // إذا الحقل فارغ ومطلوب
            if (this.hasAttribute('required')) {
                this.classList.add('is-invalid');
                this.setCustomValidity('هذا الحقل مطلوب');
            }
            return;
        }

        // التحقق من صحة الرقم
        if (isValidSpanishPhone(this.value)) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            this.setCustomValidity('');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            this.setCustomValidity('رقم الهاتف غير صحيح');
        }
    });

    // التحقق عند submit النموذج
    const form = phoneInput.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (phoneInput.value && !isValidSpanishPhone(phoneInput.value)) {
                e.preventDefault();
                phoneInput.classList.add('is-invalid');
                phoneInput.setCustomValidity('رقم الهاتف غير صحيح');
                phoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                phoneInput.focus();
                return false;
            }
        });
    }
}

// Función global para actualizar modelos
window.updateModels = function(brandId) {
    const modelSelect = document.getElementById('model_id');
    
    if (!brandId) {
        modelSelect.innerHTML = '<option value="">Selecciona una marca primero</option>';
        modelSelect.disabled = true;
        updateTicketPreview();
        return;
    }
    
    modelSelect.innerHTML = '<option value="">Cargando...</option>';
    modelSelect.disabled = true;
    
    fetch(`<?= url('api/models.php') ?>?brand_id=${brandId}`)
        .then(response => response.json())
        .then(data => {
            modelSelect.innerHTML = '<option value="">Selecciona un modelo</option>';
            
            if (data.success && data.data) {
                data.data.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = model.name;
                    modelSelect.appendChild(option);
                });
            }
            
            modelSelect.disabled = false;
            updateTicketPreview();
        })
        .catch(error => {
            console.error('Error cargando modelos:', error);
            modelSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
            modelSelect.disabled = false;
        });
};

// Configurar el cambio de marca
document.getElementById('brand_id').addEventListener('change', function() {
    updateModels(this.value);
});
</script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>