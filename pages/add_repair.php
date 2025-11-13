<?php
/**
 * RepairPoint - Agregar Nueva Reparación (محدث لدعم قطع الغيار)
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

// التحقق من وجود قطعة غيار مقترحة من URL
$suggested_part_id = intval($_GET['suggested_part'] ?? 0);
$suggested_part = null;

if ($suggested_part_id > 0 && canUseSpareParts()) {
    $db = getDB();
    $suggested_part = $db->selectOne(
        "SELECT sp.*,
                GROUP_CONCAT(DISTINCT CONCAT(
                    b.name, ' ', m.name,
                    CASE
                        WHEN m.model_reference IS NOT NULL THEN CONCAT(' (', m.model_reference, ')')
                        ELSE ''
                    END
                ) SEPARATOR ', ') as compatible_phones
         FROM spare_parts sp
         LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
         LEFT JOIN brands b ON spc.brand_id = b.id
         LEFT JOIN models m ON spc.model_id = m.id
         WHERE sp.id = ? AND sp.shop_id = ? AND sp.is_active = TRUE
         GROUP BY sp.id",
        [$suggested_part_id, $shop_id]
    );
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        // Validar datos básicos
        $device_input_type = $_POST['device_input_type'] ?? 'list';

        // تحديد الحقول المطلوبة حسب نوع إدخال الجهاز
        if ($device_input_type === 'otro') {
            $required_fields = ['customer_name', 'customer_phone', 'custom_model', 'issue_description'];
        } else {
            $required_fields = ['customer_name', 'customer_phone', 'brand_id', 'model_id', 'issue_description'];
        }

        $errors = validateRequired($_POST, $required_fields);

        if (empty($errors)) {
            $db = getDB();

            // Sanitizar datos
            $data = sanitizeArray($_POST);

            // Validar teléfono
            if (!isValidPhone($data['customer_phone'])) {
                $errors[] = 'El formato del teléfono no es válido';
            }

            // Validar días de garantía
            $warranty_days = intval($data['warranty_days'] ?? 30);
            $warranty_config = getConfig('warranty');
            if ($warranty_days < $warranty_config['min_days'] || $warranty_days > $warranty_config['max_days']) {
                $warranty_days = $warranty_config['default_days'];
            }

            // Verificar الجهاز حسب نوع الإدخال
            $model = null;
            if ($device_input_type !== 'otro') {
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
            } else {
                // للأجهزة المخصصة، التحقق من وجود custom_model على الأقل
                if (empty($data['custom_model'])) {
                    $errors[] = 'Debe ingresar el modelo del dispositivo';
                }
            }

            // التحقق من طريقة التسعير
            $pricing_method = $data['pricing_method'] ?? 'manual';
            $total_cost = 0;
            $spare_parts_data = [];

            if ($pricing_method === 'spare_parts' && canUseSpareParts()) {
                // التحقق من قطع الغيار
                if (empty($data['selected_parts'])) {
                    $errors[] = 'Debe seleccionar al menos una pieza de repuesto';
                } else {
                    // التحقق من توفر كل قطعة
                    foreach ($data['selected_parts'] as $part_data) {
                        $part_id = intval($part_data['id']);
                        $quantity = intval($part_data['quantity']);

                        if ($part_id <= 0 || $quantity <= 0) {
                            continue;
                        }

                        $availability = checkSparePartAvailability($part_id, $quantity);
                        if (!$availability['available']) {
                            $errors[] = "Pieza no disponible: " . $availability['message'];
                        } else {
                            // الحصول على بيانات القطعة
                            $part = $db->selectOne(
                                "SELECT * FROM spare_parts WHERE id = ? AND shop_id = ?",
                                [$part_id, $shop_id]
                            );

                            if ($part) {
                                $spare_parts_data[] = [
                                    'part' => $part,
                                    'quantity' => $quantity,
                                    'unit_price' => $part['total_price'],
                                    'total_price' => $part['total_price'] * $quantity
                                ];
                                $total_cost += $part['total_price'] * $quantity;
                            }
                        }
                    }
                }
            } else {
                // الطريقة اليدوية
                $total_cost = floatval($data['estimated_cost'] ?? 0);
                if ($total_cost <= 0) {
                    $errors[] = 'El coste estimado debe ser mayor que cero';
                }
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    // Generar referencia única
                    $reference = generateRepairReference();

                    // Insertar reparación مع دعم الأجهزة المخصصة
                    $repair_id = $db->insert(
                        "INSERT INTO repairs (
                            reference, customer_name, customer_phone, brand_id, model_id,
                            device_input_type, custom_brand, custom_model,
                            issue_description, estimated_cost, priority, status, warranty_days,
                            received_at, created_by, shop_id, notes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), ?, ?, ?)",
                        [
                            $reference,
                            $data['customer_name'],
                            $data['customer_phone'],
                            $device_input_type === 'otro' ? null : $data['brand_id'],
                            $device_input_type === 'otro' ? null : $data['model_id'],
                            $device_input_type,
                            $device_input_type === 'otro' ? ($data['custom_brand'] ?? null) : null,
                            $device_input_type === 'otro' ? $data['custom_model'] : null,
                            $data['issue_description'],
                            $total_cost,
                            $data['priority'] ?? 'medium',
                            $warranty_days,
                            $_SESSION['user_id'],
                            $shop_id,
                            $data['notes'] ?? null
                        ]
                    );

                    if (!$repair_id) {
                        throw new Exception('Error al insertar la reparación');
                    }

                    // إضافة قطع الغيار إذا تم اختيارها
                    if ($pricing_method === 'spare_parts' && !empty($spare_parts_data)) {
                        foreach ($spare_parts_data as $part_data) {
                            $part_usage_id = addRepairSparePart(
                                $repair_id,
                                $part_data['part']['id'],
                                $part_data['quantity'],
                                $part_data['unit_price']
                            );

                            if (!$part_usage_id) {
                                throw new Exception('Error al agregar la pieza: ' . $part_data['part']['part_name']);
                            }
                        }
                    }

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

                } catch (Exception $e) {
                    // التحقق من وجود transaction نشط قبل rollback
                    try {
                        $db->rollback();
                    } catch (PDOException $rollbackError) {
                        // تجاهل خطأ rollback إذا لم يكن هناك transaction نشط
                        error_log("Rollback error (normal if no active transaction): " . $rollbackError->getMessage());
                    }

                    error_log("Error creando reparación: " . $e->getMessage());
                    setMessage('Error al registrar la reparación: ' . $e->getMessage(), MSG_ERROR);
                }
            }
        }

        if (!empty($errors)) {
            setMessage(implode('<br>', $errors), MSG_ERROR);
        }
    }
}

// Obtener datos para el formulario
$db = getDB();
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$common_issues = $db->select("SELECT * FROM common_issues ORDER BY category, issue_text");

$warranty_config = getConfig('warranty');
$default_warranty = $warranty_config['default_days'];
$min_warranty = $warranty_config['min_days'];
$max_warranty = $warranty_config['max_days'];

// التحقق من صلاحيات قطع الغيار
$can_use_spare_parts = canUseSpareParts();

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
        <form method="POST" action="" class="needs-validation" novalidate id="repairForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="row">
                <!-- Formulario principal -->
                <div class="col-12 col-lg-8">
                    <!-- Información del Cliente y Dispositivo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person me-2"></i>
                                Información del Cliente y Dispositivo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Nombre del cliente -->
                                <div class="col-md-6">
                                    <label for="customer_name" class="form-label">
                                        Nombre del Cliente <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="customer_name"
                                           name="customer_name"
                                           value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor, ingrese el nombre del cliente.
                                    </div>
                                </div>

                                <!-- Teléfono del cliente -->
                                <div class="col-md-6">
                                    <label for="customer_phone" class="form-label">
                                        Teléfono <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel"
                                           class="form-control"
                                           id="customer_phone"
                                           name="customer_phone"
                                           value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>"
                                           placeholder="+34 XXX XXX XXX"
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor, ingrese un teléfono válido.
                                    </div>
                                </div>

                                <!-- Tipo de entrada del dispositivo -->
                                <div class="col-12">
                                    <label class="form-label">
                                        Método de Selección del Dispositivo <span class="text-danger">*</span>
                                    </label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check device-input-option">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="device_input_type"
                                                       id="device_input_list"
                                                       value="list"
                                                       checked>
                                                <label class="form-check-label" for="device_input_list">
                                                    <i class="bi bi-list-ul me-1"></i>
                                                    <strong>Seleccionar de la lista</strong>
                                                    <small class="d-block text-muted">Elige marca y modelo</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check device-input-option">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="device_input_type"
                                                       id="device_input_search"
                                                       value="search">
                                                <label class="form-check-label" for="device_input_search">
                                                    <i class="bi bi-search me-1"></i>
                                                    <strong>Búsqueda rápida</strong>
                                                    <small class="d-block text-muted">Buscar por modelo/referencia</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check device-input-option">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="device_input_type"
                                                       id="device_input_otro"
                                                       value="otro">
                                                <label class="form-check-label" for="device_input_otro">
                                                    <i class="bi bi-pencil me-1"></i>
                                                    <strong>Otro</strong>
                                                    <small class="d-block text-muted">Dispositivo no encontrado</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección: Seleccionar de la lista -->
                                <div id="device_list_section" class="col-12">
                                    <div class="row">
                                        <!-- Marca del dispositivo -->
                                        <div class="col-md-6">
                                            <label for="brand_id" class="form-label">
                                                Marca del Dispositivo <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select"
                                                    id="brand_id"
                                                    name="brand_id">
                                                <option value="">Seleccionar marca</option>
                                                <?php foreach ($brands as $brand): ?>
                                                    <option value="<?= $brand['id'] ?>"
                                                        <?= safeSelected($_POST['brand_id'] ?? '', $brand['id']) ?>>
                                                        <?= htmlspecialchars($brand['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor, seleccione una marca.
                                            </div>
                                        </div>

                                        <!-- Modelo del dispositivo -->
                                        <div class="col-md-6">
                                            <label for="model_id" class="form-label">
                                                Modelo del Dispositivo <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select"
                                                    id="model_id"
                                                    name="model_id"
                                                    disabled>
                                                <option value="">Primero selecciona una marca</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor, seleccione un modelo.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección: Búsqueda rápida -->
                                <div id="device_search_section" class="col-12" style="display: none;">
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="device_search_input" class="form-label">
                                                Buscar Dispositivo <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="device_search_input"
                                                   placeholder="Ej: V2244, iPhone 15, Galaxy S24..."
                                                   autocomplete="off">
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Puedes buscar por nombre del modelo o por su referencia
                                            </div>
                                            <div id="device_search_results" class="search-results"></div>
                                            <!-- Hidden fields للقيم المختارة -->
                                            <input type="hidden" id="search_brand_id" name="brand_id_search">
                                            <input type="hidden" id="search_model_id" name="model_id_search">
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección: Otro (dispositivo personalizado) -->
                                <div id="device_otro_section" class="col-12" style="display: none;">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Dispositivo no encontrado:</strong> No habrá repuestos compatibles disponibles automáticamente.
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="custom_brand" class="form-label">
                                                Marca <small class="text-muted">(opcional)</small>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="custom_brand"
                                                   name="custom_brand"
                                                   placeholder="Ej: Realme, OnePlus...">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="custom_model" class="form-label">
                                                Modelo <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="custom_model"
                                                   name="custom_model"
                                                   placeholder="Ej: GT Neo 3, Nord 2T...">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el modelo del dispositivo.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción del problema -->
                                <div class="col-12">
                                    <label for="issue_description" class="form-label">
                                        Descripción del Problema <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control"
                                              id="issue_description"
                                              name="issue_description"
                                              rows="4"
                                              required><?= htmlspecialchars($_POST['issue_description'] ?? '') ?></textarea>
                                    <div class="invalid-feedback">
                                        Por favor, describa el problema.
                                    </div>

                                    <!-- Problemas comunes -->
                                    <div class="mt-2">
                                        <small class="text-muted">Problemas comunes:</small>
                                        <div class="common-issues-container mt-1">
                                            <?php foreach ($common_issues as $issue): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary common-issue-btn me-1 mb-1"
                                                        data-issue="<?= htmlspecialchars($issue['issue_text']) ?>">
                                                    <?= htmlspecialchars($issue['issue_text']) ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prioridad -->
                                <div class="col-md-6">
                                    <label for="priority" class="form-label">Prioridad</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?= safeSelected($_POST['priority'] ?? 'medium', 'low') ?>>
                                            Baja
                                        </option>
                                        <option value="medium" <?= safeSelected($_POST['priority'] ?? 'medium', 'medium') ?>>
                                            Media
                                        </option>
                                        <option value="high" <?= safeSelected($_POST['priority'] ?? 'medium', 'high') ?>>
                                            Alta
                                        </option>
                                    </select>
                                </div>

                                <!-- Días de garantía -->
                                <div class="col-md-6">
                                    <label for="warranty_days" class="form-label">Días de Garantía</label>
                                    <input type="number"
                                           class="form-control"
                                           id="warranty_days"
                                           name="warranty_days"
                                           value="<?= htmlspecialchars($_POST['warranty_days'] ?? $default_warranty) ?>"
                                           min="<?= $min_warranty ?>"
                                           max="<?= $max_warranty ?>">
                                    <div class="form-text">
                                        Entre <?= $min_warranty ?> y <?= $max_warranty ?> días
                                    </div>
                                </div>

                                <!-- Notas adicionales -->
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notas Adicionales</label>
                                    <textarea class="form-control"
                                              id="notes"
                                              name="notes"
                                              rows="2"
                                              placeholder="Observaciones adicionales..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- سعر الإصلاح -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-currency-euro me-2"></i>
                                Coste de la Reparación
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- اختيار طريقة التسعير -->
                            <div class="pricing-method-selection mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check pricing-option">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="pricing_method"
                                                   id="pricing_manual"
                                                   value="manual"
                                                   checked>
                                            <label class="form-check-label" for="pricing_manual">
                                                <i class="bi bi-pencil-square me-2"></i>
                                                <strong>Método Manual</strong>
                                                <small class="d-block text-muted">Ingresa el precio manualmente</small>
                                            </label>
                                        </div>
                                    </div>

                                    <?php if ($can_use_spare_parts): ?>
                                        <div class="col-md-6">
                                            <div class="form-check pricing-option">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="pricing_method"
                                                       id="pricing_spare_parts"
                                                       value="spare_parts">
                                                <label class="form-check-label" for="pricing_spare_parts">
                                                    <i class="bi bi-gear me-2"></i>
                                                    <strong>Con Repuestos</strong>
                                                    <small class="d-block text-muted">Selecciona piezas del inventario</small>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- الطريقة اليدوية -->
                            <div id="manual_pricing_section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="estimated_cost" class="form-label">
                                            Coste Estimado <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">€</span>
                                            <input type="number"
                                                   class="form-control"
                                                   id="estimated_cost"
                                                   name="estimated_cost"
                                                   value="<?= htmlspecialchars($_POST['estimated_cost'] ?? '') ?>"
                                                   step="0.01"
                                                   min="0"
                                                   placeholder="0.00">
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, ingrese el coste estimado.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- قسم قطع الغيار -->
                            <?php if ($can_use_spare_parts): ?>
                                <div id="spare_parts_section" style="display: none;">
                                    <div class="spare-parts-selection">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">Repuestos Seleccionados</h6>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    id="addSparePartBtn">
                                                <i class="bi bi-plus"></i> Agregar Repuesto
                                            </button>
                                        </div>

                                        <div id="selectedPartsContainer">
                                            <!-- قطع الغيار المختارة ستظهر هنا -->
                                        </div>

                                        <!-- إجمالي التكلفة -->
                                        <div class="total-cost-section mt-3 p-3 bg-light rounded">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Total de Repuestos:</strong>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <span class="h5 text-primary" id="totalSpareParts">€0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- أزرار الحفظ -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit"
                                        name="action"
                                        value="save"
                                        class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Guardar Reparación
                                </button>
                                <button type="submit"
                                        name="action"
                                        value="print"
                                        class="btn btn-success">
                                    <i class="bi bi-printer me-2"></i>
                                    Guardar e Imprimir
                                </button>
                                <button type="submit"
                                        name="action"
                                        value="continue"
                                        class="btn btn-info">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Guardar y Continuar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vista previa del ticket -->
                <div class="col-12 col-lg-4">
                    <div class="card sticky-top">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-receipt me-2"></i>
                                Vista Previa del Ticket
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="ticket-preview p-3 border rounded bg-light" style="font-family: monospace; font-size: 0.85rem;">
                                <div class="text-center mb-3">
                                    <strong>REPARACIÓN</strong><br>
                                    <span id="preview_reference">#REF-XXXX</span>
                                </div>

                                <div class="mb-2">
                                    <strong>Cliente:</strong><br>
                                    <span id="preview_customer">-</span><br>
                                    <span id="preview_phone">-</span>
                                </div>

                                <div class="mb-2">
                                    <strong>Dispositivo:</strong><br>
                                    <span id="preview_device">-</span>
                                </div>

                                <div class="mb-2">
                                    <strong>Problema:</strong><br>
                                    <span id="preview_issue">-</span>
                                </div>

                                <div class="mb-2">
                                    <strong>Coste:</strong><br>
                                    <span id="preview_cost">€0.00</span>
                                </div>

                                <div class="mb-2">
                                    <strong>Prioridad:</strong>
                                    <span id="preview_priority">Media</span>
                                </div>

                                <div class="text-center mt-3 pt-2 border-top">
                                    <small>Garantía: <span id="preview_warranty"><?= $default_warranty ?></span> días</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal لإضافة قطعة غيار -->
<?php if ($can_use_spare_parts): ?>
    <div class="modal fade" id="addSparePartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Repuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- بحث قطع الغيار -->
                    <div class="mb-3">
                        <label class="form-label">Buscar Repuesto</label>
                        <input type="text"
                               class="form-control"
                               id="sparePartSearch"
                               placeholder="Buscar por nombre o código...">
                    </div>

                    <!-- نتائج البحث -->
                    <div id="sparePartResults">
                        <div class="text-center text-muted">
                            <i class="bi bi-search"></i>
                            <p>Escribe para buscar repuestos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- JavaScript -->
    <script>
        // متغيرات عامة
        const canUseSpareParts = <?= json_encode($can_use_spare_parts) ?>;
        const shopId = <?= $shop_id ?>;
        let selectedParts = [];
        let nextPartIndex = 0;

        // إعداد الصفحة عند التحميل
        document.addEventListener('DOMContentLoaded', function() {
            // إعداد التحقق من صحة النموذج
            setupFormValidation();

            // إعداد الأوضاع الثلاثة للجهاز
            setupDeviceInputModes();

            // إعداد تحميل الموديلات
            setupBrandModelHandling();

            // إعداد الهاتف
            setupPhoneFormatting();

            // إعداد الأزرار الشائعة
            setupCommonIssues();

            // إعداد المعاينة
            setupTicketPreview();

            // إعداد طرق التسعير
            setupPricingMethods();

            // إضافة قطعة غيار مقترحة إذا وجدت
            <?php if ($suggested_part): ?>
            addSuggestedPart(<?= json_encode($suggested_part) ?>);
            <?php endif; ?>
        });

        // إعداد التحقق من صحة النموذج
        function setupFormValidation() {
            const form = document.getElementById('repairForm');

            form.addEventListener('submit', function(event) {
                // التحقق من طريقة التسعير
                const pricingMethod = document.querySelector('input[name="pricing_method"]:checked').value;

                if (pricingMethod === 'manual') {
                    const estimatedCost = document.getElementById('estimated_cost');
                    estimatedCost.required = true;

                    if (!estimatedCost.value || parseFloat(estimatedCost.value) <= 0) {
                        estimatedCost.setCustomValidity('El coste estimado debe ser mayor que cero');
                        estimatedCost.classList.add('is-invalid');
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    } else {
                        estimatedCost.setCustomValidity('');
                        estimatedCost.classList.remove('is-invalid');
                    }
                } else if (pricingMethod === 'spare_parts') {
                    const estimatedCost = document.getElementById('estimated_cost');
                    estimatedCost.required = false;
                    estimatedCost.setCustomValidity('');

                    if (selectedParts.length === 0) {
                        alert('Debe seleccionar al menos un repuesto');
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            });
        }

        // إعداد الأوضاع الثلاثة لاختيار الجهاز
        function setupDeviceInputModes() {
            const listRadio = document.getElementById('device_input_list');
            const searchRadio = document.getElementById('device_input_search');
            const otroRadio = document.getElementById('device_input_otro');

            const listSection = document.getElementById('device_list_section');
            const searchSection = document.getElementById('device_search_section');
            const otroSection = document.getElementById('device_otro_section');

            function switchDeviceInputMode() {
                // إخفاء جميع الأقسام
                listSection.style.display = 'none';
                searchSection.style.display = 'none';
                otroSection.style.display = 'none';

                // إظهار القسم المختار
                if (listRadio.checked) {
                    listSection.style.display = 'block';
                    // تمكين الحقول
                    document.getElementById('brand_id').removeAttribute('disabled');
                    document.getElementById('brand_id').setAttribute('required', 'required');
                    document.getElementById('model_id').setAttribute('required', 'required');
                    document.getElementById('custom_model').removeAttribute('required');
                } else if (searchRadio.checked) {
                    searchSection.style.display = 'block';
                    // تعطيل حقول القائمة
                    document.getElementById('brand_id').removeAttribute('required');
                    document.getElementById('model_id').removeAttribute('required');
                    document.getElementById('custom_model').removeAttribute('required');
                } else if (otroRadio.checked) {
                    otroSection.style.display = 'block';
                    // تعطيل حقول القائمة وتمكين custom_model
                    document.getElementById('brand_id').removeAttribute('required');
                    document.getElementById('model_id').removeAttribute('required');
                    document.getElementById('custom_model').setAttribute('required', 'required');
                }

                updateTicketPreview();
            }

            listRadio.addEventListener('change', switchDeviceInputMode);
            searchRadio.addEventListener('change', switchDeviceInputMode);
            otroRadio.addEventListener('change', switchDeviceInputMode);

            // إعداد البحث السريع
            setupQuickDeviceSearch();

            // التحميل الأولي
            switchDeviceInputMode();
        }

        // إعداد البحث السريع في الأجهزة
        function setupQuickDeviceSearch() {
            const searchInput = document.getElementById('device_search_input');
            const searchResults = document.getElementById('device_search_results');
            let searchTimeout;

            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = this.value.trim();

                if (searchTerm.length < 2) {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    performDeviceSearch(searchTerm);
                }, 300);
            });
        }

        // تنفيذ البحث عن الأجهزة
        function performDeviceSearch(searchTerm) {
            const searchResults = document.getElementById('device_search_results');

            searchResults.innerHTML = `
                <div class="text-center p-2">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <span class="ms-2">Buscando...</span>
                </div>
            `;
            searchResults.style.display = 'block';

            fetch(`<?= url('api/models_search.php') ?>?term=${encodeURIComponent(searchTerm)}&limit=10`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        displayDeviceSearchResults(data.data);
                    } else {
                        searchResults.innerHTML = `
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                No se encontraron dispositivos. Intenta con otro término.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error buscando dispositivos:', error);
                    searchResults.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Error al buscar. Por favor intenta de nuevo.
                        </div>
                    `;
                });
        }

        // عرض نتائج البحث عن الأجهزة
        function displayDeviceSearchResults(devices) {
            const searchResults = document.getElementById('device_search_results');
            let html = '<div class="list-group mt-2">';

            devices.forEach(device => {
                html += `
                    <button type="button"
                            class="list-group-item list-group-item-action"
                            onclick="selectDeviceFromSearch(${device.brand_id}, ${device.model_id}, '${escapeHtml(device.display_name)}')">
                        <i class="bi bi-phone me-2"></i>
                        ${escapeHtml(device.display_name)}
                    </button>
                `;
            });

            html += '</div>';
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }

        // اختيار جهاز من نتائج البحث
        function selectDeviceFromSearch(brandId, modelId, displayName) {
            // ملء الحقول المخفية
            document.getElementById('search_brand_id').value = brandId;
            document.getElementById('search_model_id').value = modelId;

            // تحديث brand_id و model_id الرئيسيين
            document.getElementById('brand_id').value = brandId;
            document.getElementById('model_id').value = modelId;

            // عرض الجهاز المختار
            const searchInput = document.getElementById('device_search_input');
            searchInput.value = displayName;
            searchInput.classList.add('is-valid');

            // إخفاء النتائج
            document.getElementById('device_search_results').style.display = 'none';

            // تحديث المعاينة
            updateTicketPreview();

            console.log('✅ Dispositivo seleccionado:', displayName);
        }

        // إعداد تحميل الموديلات حسب الماركة
        function setupBrandModelHandling() {
            const brandSelect = document.getElementById('brand_id');
            const modelSelect = document.getElementById('model_id');

            brandSelect.addEventListener('change', function() {
                const brandId = this.value;

                // مسح الموديلات الحالية
                modelSelect.innerHTML = '<option value="">Cargando modelos...</option>';
                modelSelect.disabled = true;

                if (brandId) {
                    // تحميل الموديلات
                    fetch(`<?= url('api/models.php') ?>?action=get_by_brand&brand_id=${brandId}`)
                        .then(response => response.json())
                        .then(data => {
                            modelSelect.innerHTML = '<option value="">Seleccionar modelo</option>';

                            if (data.success && data.data) {
                                data.data.forEach(model => {
                                    const option = document.createElement('option');
                                    option.value = model.id;
                                    option.textContent = model.name;
                                    modelSelect.appendChild(option);
                                });
                                modelSelect.disabled = false;

                                // تحديث قطع الغيار المقترحة
                                if (canUseSpareParts) {
                                    updateSuggestedParts();
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading models:', error);
                            modelSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
                        });
                } else {
                    modelSelect.innerHTML = '<option value="">Primero selecciona una marca</option>';
                    modelSelect.disabled = true;
                }

                updateTicketPreview();
            });

            // تحديث عند تغيير الموديل
            modelSelect.addEventListener('change', function() {
                updateTicketPreview();
                if (canUseSpareParts) {
                    updateSuggestedParts();
                }
            });
        }

        // إعداد تنسيق الهاتف
        function setupPhoneFormatting() {
            const phoneInput = document.getElementById('customer_phone');

            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');

                if (value.startsWith('34')) {
                    value = value.substring(2);
                }

                if (value.length <= 9) {
                    if (value.length >= 3 && value.length <= 6) {
                        this.value = '+34 ' + value.substring(0, 3) + ' ' + value.substring(3);
                    } else if (value.length > 6) {
                        this.value = '+34 ' + value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6, 9);
                    } else if (value.length > 0) {
                        this.value = '+34 ' + value;
                    }
                }

                updateTicketPreview();
            });
        }

        // إعداد الأزرار الشائعة
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

        // إعداد المعاينة
        function setupTicketPreview() {
            const inputs = ['customer_name', 'customer_phone', 'issue_description', 'priority', 'warranty_days'];

            inputs.forEach(inputId => {
                const element = document.getElementById(inputId);
                if (element) {
                    element.addEventListener('input', updateTicketPreview);
                    element.addEventListener('change', updateTicketPreview);
                }
            });

            // إعداد أولي
            updateTicketPreview();
        }

        // تحديث المعاينة
        function updateTicketPreview() {
            const customerName = document.getElementById('customer_name').value || '-';
            const customerPhone = document.getElementById('customer_phone').value || '-';
            const brandSelect = document.getElementById('brand_id');
            const modelSelect = document.getElementById('model_id');
            const issue = document.getElementById('issue_description').value || '-';
            const priority = document.getElementById('priority').value;
            const warranty = document.getElementById('warranty_days').value;

            // تحديث معلومات الجهاز
            let device = '-';
            if (brandSelect.value && modelSelect.value) {
                const brandName = brandSelect.options[brandSelect.selectedIndex].text;
                const modelName = modelSelect.options[modelSelect.selectedIndex].text;
                device = `${brandName} ${modelName}`;
            }

            // تحديث التكلفة
            let cost = '€0.00';
            const pricingMethod = document.querySelector('input[name="pricing_method"]:checked').value;

            if (pricingMethod === 'manual') {
                const estimatedCost = document.getElementById('estimated_cost').value;
                if (estimatedCost) {
                    cost = '€' + parseFloat(estimatedCost).toFixed(2);
                }
            } else if (pricingMethod === 'spare_parts') {
                const total = calculateSpareParsTotal();
                cost = '€' + total.toFixed(2);
            }

            // تحديث العناصر
            document.getElementById('preview_customer').textContent = customerName;
            document.getElementById('preview_phone').textContent = customerPhone;
            document.getElementById('preview_device').textContent = device;
            document.getElementById('preview_issue').textContent = issue.substring(0, 50) + (issue.length > 50 ? '...' : '');
            document.getElementById('preview_cost').textContent = cost;
            document.getElementById('preview_priority').textContent = getPriorityText(priority);
            document.getElementById('preview_warranty').textContent = warranty || '30';
        }

        // إعداد طرق التسعير
        function setupPricingMethods() {
            if (!canUseSpareParts) return;

            const manualRadio = document.getElementById('pricing_manual');
            const sparePartsRadio = document.getElementById('pricing_spare_parts');
            const manualSection = document.getElementById('manual_pricing_section');
            const sparePartsSection = document.getElementById('spare_parts_section');

            function togglePricingMethod() {
                if (manualRadio.checked) {
                    manualSection.style.display = 'block';
                    sparePartsSection.style.display = 'none';
                    document.getElementById('estimated_cost').required = true;
                } else {
                    manualSection.style.display = 'none';
                    sparePartsSection.style.display = 'block';
                    document.getElementById('estimated_cost').required = false;
                }
                updateTicketPreview();
            }

            manualRadio.addEventListener('change', togglePricingMethod);
            sparePartsRadio.addEventListener('change', togglePricingMethod);

            // إعداد زر إضافة قطعة غيار
            const addSparePartBtn = document.getElementById('addSparePartBtn');
            if (addSparePartBtn) {
                addSparePartBtn.addEventListener('click', function() {
                    const modal = new bootstrap.Modal(document.getElementById('addSparePartModal'));
                    modal.show();
                });
            }

            // إعداد البحث في قطع الغيار
            setupSparePartSearch();
        }

        // إعداد بحث قطع الغيار
        function setupSparePartSearch() {
            const searchInput = document.getElementById('sparePartSearch');
            const resultsContainer = document.getElementById('sparePartResults');
            let searchTimeout;

            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = this.value.trim();

                if (searchTerm.length < 2) {
                    resultsContainer.innerHTML = `
                <div class="text-center text-muted">
                    <i class="bi bi-search"></i>
                    <p>Escribe al menos 2 caracteres para buscar</p>
                </div>
            `;
                    return;
                }

                searchTimeout = setTimeout(() => {
                    searchSpareParts(searchTerm);
                }, 300);
            });
        }

        // البحث في قطع الغيار
        function searchSpareParts(searchTerm) {
            const resultsContainer = document.getElementById('sparePartResults');
            const brandId = document.getElementById('brand_id').value;
            const modelId = document.getElementById('model_id').value;

            // عرض مؤشر التحميل
            resultsContainer.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <p class="mt-2">Buscando repuestos...</p>
        </div>
    `;

            let url = `<?= url('api/spare_parts.php') ?>?action=search&term=${encodeURIComponent(searchTerm)}&limit=10`;

            if (brandId && modelId) {
                url += `&brand_id=${brandId}&model_id=${modelId}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.parts) {
                        displaySparePartResults(data.data.parts);
                    } else {
                        resultsContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>No se encontraron repuestos</p>
                    </div>
                `;
                    }
                })
                .catch(error => {
                    console.error('Error searching spare parts:', error);
                    resultsContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Error al buscar repuestos</p>
                </div>
            `;
                });
        }

        // عرض نتائج البحث
        function displaySparePartResults(parts) {
            const resultsContainer = document.getElementById('sparePartResults');

            if (parts.length === 0) {
                resultsContainer.innerHTML = `
            <div class="text-center text-muted">
                <i class="bi bi-inbox"></i>
                <p>No hay repuestos disponibles</p>
            </div>
        `;
                return;
            }

            let html = '<div class="list-group">';

            parts.forEach(part => {
                const isSelected = selectedParts.find(p => p.id === part.id);
                const isAvailable = part.stock_status !== 'out_of_stock' && part.stock_quantity > 0;

                html += `
            <div class="list-group-item ${isSelected ? 'list-group-item-success' : ''} ${!isAvailable ? 'list-group-item-secondary' : ''}"
                 data-part-id="${part.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${part.part_name}</h6>
                        ${part.part_code ? `<small class="text-muted">Código: ${part.part_code}</small><br>` : ''}
                        ${part.compatible_phones ? `<small class="text-info">Compatible: ${part.compatible_phones}</small><br>` : ''}
                        <span class="badge bg-primary">€${parseFloat(part.total_price).toFixed(2)}</span>
                        ${getStockBadge(part.stock_status, part.stock_quantity)}
                    </div>
                    <div class="ms-3">
                        ${isSelected ?
                    `<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSparePart(${part.id})">
                                <i class="bi bi-trash"></i>
                            </button>` :
                    `<button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="addSparePart(${part.id}, '${part.part_name.replace(/'/g, "\\'")}', ${part.total_price})"
                                    ${!isAvailable ? 'disabled' : ''}>
                                <i class="bi bi-plus"></i>
                            </button>`
                }
                    </div>
                </div>
            </div>
        `;
            });

            html += '</div>';
            resultsContainer.innerHTML = html;
        }

        // إضافة قطعة غيار
        function addSparePart(partId, partName, unitPrice) {
            // التحقق من عدم وجود القطعة مسبقاً
            if (selectedParts.find(p => p.id === partId)) {
                alert('Esta pieza ya está seleccionada');
                return;
            }

            const part = {
                id: partId,
                name: partName,
                unitPrice: parseFloat(unitPrice),
                quantity: 1,
                total: parseFloat(unitPrice)
            };

            selectedParts.push(part);
            updateSelectedPartsDisplay();
            updateTicketPreview();

            // إغلاق modal
            const modalElement = document.getElementById('addSparePartModal');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            // تحديث نتائج البحث
            const searchTerm = document.getElementById('sparePartSearch').value;
            if (searchTerm.length >= 2) {
                searchSpareParts(searchTerm);
            }
        }

        // إزالة قطعة غيار
        function removeSparePart(partId) {
            selectedParts = selectedParts.filter(p => p.id !== partId);
            updateSelectedPartsDisplay();
            updateTicketPreview();

            // تحديث نتائج البحث إذا كانت مفتوحة
            const modal = document.getElementById('addSparePartModal');
            if (modal.classList.contains('show')) {
                const searchTerm = document.getElementById('sparePartSearch').value;
                if (searchTerm.length >= 2) {
                    searchSpareParts(searchTerm);
                }
            }
        }

        // تحديث عرض القطع المختارة
        function updateSelectedPartsDisplay() {
            const container = document.getElementById('selectedPartsContainer');

            if (selectedParts.length === 0) {
                container.innerHTML = `
            <div class="text-center text-muted p-4">
                <i class="bi bi-gear" style="font-size: 2rem;"></i>
                <p class="mt-2">No hay repuestos seleccionados</p>
                <small>Haz clic en "Agregar Repuesto" para seleccionar piezas</small>
            </div>
        `;
                return;
            }

            let html = '';
            let totalCost = 0;

            selectedParts.forEach((part, index) => {
                totalCost += part.total;

                html += `
            <div class="selected-part-item border rounded p-3 mb-2">
                <input type="hidden" name="selected_parts[${index}][id]" value="${part.id}">
                <input type="hidden" name="selected_parts[${index}][quantity]" value="${part.quantity}">

                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-1">${part.name}</h6>
                        <small class="text-muted">€${part.unitPrice.toFixed(2)} por unidad</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number"
                               class="form-control form-control-sm"
                               value="${part.quantity}"
                               min="1"
                               onchange="updatePartQuantity(${part.id}, this.value)">
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="fw-bold">€${part.total.toFixed(2)}</div>
                    </div>
                    <div class="col-md-1">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="removeSparePart(${part.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
            });

            container.innerHTML = html;

            // تحديث الإجمالي
            document.getElementById('totalSpareParts').textContent = '€' + totalCost.toFixed(2);
        }

        // تحديث كمية القطعة
        function updatePartQuantity(partId, newQuantity) {
            const quantity = parseInt(newQuantity) || 1;
            const part = selectedParts.find(p => p.id === partId);

            if (part) {
                part.quantity = quantity;
                part.total = part.unitPrice * quantity;
                updateSelectedPartsDisplay();
                updateTicketPreview();
            }
        }

        // حساب إجمالي قطع الغيار
        function calculateSpareParsTotal() {
            return selectedParts.reduce((total, part) => total + part.total, 0);
        }

        // إضافة قطعة غيار مقترحة
        function addSuggestedPart(part) {
            if (canUseSpareParts && part) {
                // تفعيل طريقة قطع الغيار
                const sparePartsRadio = document.getElementById('pricing_spare_parts');
                if (sparePartsRadio) {
                    sparePartsRadio.checked = true;
                    sparePartsRadio.dispatchEvent(new Event('change'));
                }

                // إضافة القطعة
                setTimeout(() => {
                    addSparePart(part.id, part.part_name, part.total_price);
                }, 100);
            }
        }

        // تحديث قطع الغيار المقترحة
        function updateSuggestedParts() {
            const brandId = document.getElementById('brand_id').value;
            const modelId = document.getElementById('model_id').value;

            if (!brandId || !modelId || !canUseSpareParts) return;

            // يمكن إضافة منطق لاقتراح قطع الغيار تلقائياً هنا
        }

        // دوال مساعدة
        function getPriorityText(priority) {
            const priorities = {
                'low': 'Baja',
                'medium': 'Media',
                'high': 'Alta'
            };
            return priorities[priority] || 'Media';
        }

        function getStockBadge(status, quantity) {
            switch (status) {
                case 'available':
                    return `<span class="badge bg-success">Disponible (${quantity})</span>`;
                case 'order_required':
                    return `<span class="badge bg-warning">Necesita pedido (${quantity})</span>`;
                case 'out_of_stock':
                    return `<span class="badge bg-danger">Sin stock</span>`;
                default:
                    return `<span class="badge bg-secondary">-</span>`;
            }
        }



        /**
         * نظام البحث عن العملاء مع debug شامل
         */

// متغيرات للتحكم في البحث
        let searchTimeout;
        let currentSuggestions = [];
        let selectedSuggestionIndex = -1;

        // إعداد نظام التملؤ التلقائي
        function setupCustomerAutocomplete() {
            console.log('🔍 Setting up customer autocomplete...');

            const nameInput = document.getElementById('customer_name');
            const phoneInput = document.getElementById('customer_phone');

            if (nameInput) {
                console.log('✅ Found name input');
                setupAutocompleteForInput(nameInput, 'name');
            } else {
                console.log('❌ Name input not found');
            }

            if (phoneInput) {
                console.log('✅ Found phone input');
                setupAutocompleteForInput(phoneInput, 'phone');
            } else {
                console.log('❌ Phone input not found');
            }
        }

        /**
         * إعداد التملؤ التلقائي لحقل معين
         */
        function setupAutocompleteForInput(input, type) {
            console.log(`🔧 Setting up autocomplete for ${type} input`);

            // إنشاء container للاقتراحات
            const suggestionsContainer = createSuggestionsContainer(input);

            // معالج الكتابة
            input.addEventListener('input', function(e) {
                const value = e.target.value.trim();
                console.log(`⌨️ User typed in ${type}:`, value);

                // إخفاء الاقتراحات إذا كان النص قصير
                if (value.length < 3) {
                    console.log('⚠️ Search term too short, hiding suggestions');
                    hideSuggestions(suggestionsContainer);
                    return;
                }

                // تأخير البحث لتجنب الطلبات المتكررة
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    console.log(`🔍 Starting search for: "${value}"`);
                    searchCustomers(value, suggestionsContainer, input, type);
                }, 300);
            });

            // معالج لوحة المفاتيح
            input.addEventListener('keydown', function(e) {
                handleKeyboardNavigation(e, suggestionsContainer, input);
            });

            // إخفاء الاقتراحات عند النقر خارجاً
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    hideSuggestions(suggestionsContainer);
                }
            });

            console.log(`✅ Autocomplete setup complete for ${type}`);
        }

        /**
         * إنشاء container للاقتراحات
         */
        function createSuggestionsContainer(input) {
            const container = document.createElement('div');
            container.className = 'customer-suggestions';
            container.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    `;

            // إضافة Container بعد الـ input
            const wrapper = input.parentNode;
            wrapper.style.position = 'relative';
            wrapper.appendChild(container);

            console.log('📦 Suggestions container created');
            return container;
        }

        /**
         * البحث عن العملاء
         */
        async function searchCustomers(searchTerm, container, input, type) {
            console.log('🔍 Starting customer search...');
            console.log('Search term:', searchTerm);
            console.log('Type:', type);

            try {
                // إظهار مؤشر التحميل
                showLoadingInSuggestions(container);

                // بناء URL
                const baseUrl = '<?php echo url('api/customer_search.php'); ?>';
                const url = `${baseUrl}?search=${encodeURIComponent(searchTerm)}&limit=8`;

                console.log('🌐 API URL:', url);

                // طلب البحث
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('📡 Response status:', response.status);
                console.log('📡 Response ok:', response.ok);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('📊 Full API Response:', data);

                // إظهار debug info
                if (data.debug) {
                    console.log('🐛 Debug Info:', data.debug);

                    // إظهار معلومات مفيدة
                    if (data.debug.total_repairs_in_shop !== undefined) {
                        console.log(`📊 Total repairs in shop: ${data.debug.total_repairs_in_shop}`);
                    }

                    if (data.debug.repairs_with_names !== undefined) {
                        console.log(`👥 Repairs with names: ${data.debug.repairs_with_names}`);
                    }

                    if (data.debug.sample_customer_names) {
                        console.log('👤 Sample customer names:', data.debug.sample_customer_names);
                    }
                }

                if (data.success && data.data && data.data.length > 0) {
                    console.log('✅ Customers found:', data.data.length);
                    currentSuggestions = data.data;
                    showSuggestions(container, data.data, input, type);
                } else {
                    console.log('❌ No customers found');
                    showNoResults(container, data.debug);
                }

            } catch (error) {
                console.error('❌ Error searching customers:', error);
                showErrorInSuggestions(container, error.message);
            }
        }

        /**
         * عرض الاقتراحات
         */
        function showSuggestions(container, customers, input, type) {
            console.log('📋 Showing suggestions for', customers.length, 'customers');

            let html = '';

            customers.forEach((customer, index) => {
                const isFrequent = customer.customer_type === 'frequent';
                const frequentBadge = isFrequent ? '<span class="badge bg-primary ms-2">Cliente frecuente</span>' : '';

                html += `
            <div class="suggestion-item" data-index="${index}" data-customer='${JSON.stringify(customer)}'>
                <div class="d-flex justify-content-between align-items-start p-3 border-bottom">
                    <div class="customer-info flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-person-circle text-primary me-2"></i>
                            <strong class="customer-name">${escapeHtml(customer.name)}</strong>
                            ${frequentBadge}
                        </div>
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-telephone text-muted me-2"></i>
                            <span class="customer-phone">${escapeHtml(customer.phone_formatted)}</span>
                        </div>
                        ${customer.recent_devices ? `
                            <div class="d-flex align-items-center">
                                <i class="bi bi-phone text-muted me-2"></i>
                                <small class="text-muted">${escapeHtml(customer.recent_devices)}</small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="customer-stats text-end">
                        <small class="text-muted d-block">
                            <i class="bi bi-tools me-1"></i>${customer.total_repairs} reparaciones
                        </small>
                        <small class="text-muted d-block">
                            <i class="bi bi-calendar me-1"></i>${customer.last_repair_formatted}
                        </small>
                        <small class="text-success d-block">
                            <i class="bi bi-cash me-1"></i>${customer.total_spent_formatted}
                        </small>
                    </div>
                </div>
            </div>
        `;
            });

            container.innerHTML = html;
            container.style.display = 'block';

            // إضافة event listeners للاقتراحات
            container.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    const customerData = JSON.parse(this.getAttribute('data-customer'));
                    selectCustomer(customerData, input, container);
                });

                item.addEventListener('mouseenter', function() {
                    // إزالة التحديد السابق
                    container.querySelectorAll('.suggestion-item').forEach(i => i.classList.remove('active'));
                    // إضافة التحديد الحالي
                    this.classList.add('active');
                    selectedSuggestionIndex = parseInt(this.getAttribute('data-index'));
                });
            });

            console.log('✅ Suggestions displayed successfully');
        }

        /**
         * تحديد عميل من الاقتراحات
         */
        function selectCustomer(customer, input, container) {
            console.log('✅ Customer selected:', customer);

            // ملء الحقول
            const nameInput = document.getElementById('customer_name');
            const phoneInput = document.getElementById('customer_phone');

            if (nameInput) {
                nameInput.value = customer.name;
                nameInput.classList.add('is-valid');
                console.log('✅ Name field filled');
            }

            if (phoneInput) {
                phoneInput.value = customer.phone_formatted;
                phoneInput.classList.add('is-valid');
                console.log('✅ Phone field filled');
            }

            // إخفاء الاقتراحات
            hideSuggestions(container);

            // إظهار معلومات العميل
            showCustomerInfo(customer);

            // التركيز على الحقل التالي
            const brandField = document.getElementById('brand_id');
            if (brandField) {
                brandField.focus();
                console.log('✅ Focus moved to brand field');
            }
        }

        /**
         * إظهار معلومات العميل
         */
        function showCustomerInfo(customer) {
            console.log('📋 Showing customer info');

            let infoContainer = document.getElementById('customer-info-display');

            if (!infoContainer) {
                infoContainer = document.createElement('div');
                infoContainer.id = 'customer-info-display';
                infoContainer.className = 'alert alert-info fade show mt-3';

                const phoneInput = document.getElementById('customer_phone');
                if (phoneInput && phoneInput.closest('.col-md-6')) {
                    phoneInput.closest('.col-md-6').appendChild(infoContainer);
                }
            }

            infoContainer.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">
                    <i class="bi bi-person-check text-success me-2"></i>
                    Cliente encontrado
                </h6>
                <div class="d-flex align-items-center gap-3">
                    <small><i class="bi bi-tools me-1"></i>${customer.total_repairs} reparaciones</small>
                    <small><i class="bi bi-calendar me-1"></i>Última: ${customer.last_repair_formatted}</small>
                    <small><i class="bi bi-cash me-1"></i>Total: ${customer.total_spent_formatted}</small>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="hideCustomerInfo()"></button>
        </div>
    `;

            // Auto-hide بعد 10 ثواني
            setTimeout(() => {
                hideCustomerInfo();
            }, 10000);
        }

        /**
         * إخفاء معلومات العميل
         */
        function hideCustomerInfo() {
            const infoContainer = document.getElementById('customer-info-display');
            if (infoContainer) {
                infoContainer.remove();
            }
        }

        /**
         * معالجة التنقل بلوحة المفاتيح
         */
        function handleKeyboardNavigation(e, container, input) {
            const suggestions = container.querySelectorAll('.suggestion-item');

            if (suggestions.length === 0) return;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, suggestions.length - 1);
                    updateSelectedSuggestion(suggestions);
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, 0);
                    updateSelectedSuggestion(suggestions);
                    break;

                case 'Enter':
                    e.preventDefault();
                    if (selectedSuggestionIndex >= 0 && suggestions[selectedSuggestionIndex]) {
                        const customerData = JSON.parse(suggestions[selectedSuggestionIndex].getAttribute('data-customer'));
                        selectCustomer(customerData, input, container);
                    }
                    break;

                case 'Escape':
                    hideSuggestions(container);
                    break;
            }
        }

        /**
         * تحديث الاقتراح المحدد
         */
        function updateSelectedSuggestion(suggestions) {
            suggestions.forEach((item, index) => {
                if (index === selectedSuggestionIndex) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        /**
         * إظهار مؤشر التحميل
         */
        function showLoadingInSuggestions(container) {
            container.innerHTML = `
        <div class="p-3 text-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            Buscando clientes...
        </div>
    `;
            container.style.display = 'block';
        }

        /**
         * إظهار رسالة عدم وجود نتائج مع debug
         */
        function showNoResults(container, debugInfo) {
            let debugHtml = '';

            if (debugInfo) {
                debugHtml = `
            <div class="mt-2 p-2 bg-light border rounded">
                <small class="text-muted">Debug Info:</small><br>
                <small class="text-muted">Total repairs in shop: ${debugInfo.total_repairs_in_shop || 0}</small><br>
                <small class="text-muted">Repairs with names: ${debugInfo.repairs_with_names || 0}</small><br>
                ${debugInfo.sample_customer_names && debugInfo.sample_customer_names.length > 0 ?
                    `<small class="text-muted">Sample names: ${debugInfo.sample_customer_names.map(c => c.customer_name).join(', ')}</small>` :
                    '<small class="text-muted">No sample names found</small>'
                }
            </div>
        `;
            }

            container.innerHTML = `
        <div class="p-3 text-center text-muted">
            <i class="bi bi-search mb-2"></i>
            <div>No se encontraron clientes</div>
            <small>Intenta con otro término de búsqueda</small>
            ${debugHtml}
        </div>
    `;
            container.style.display = 'block';
        }

        /**
         * إظهار رسالة خطأ
         */
        function showErrorInSuggestions(container, errorMessage) {
            container.innerHTML = `
        <div class="p-3 text-center text-danger">
            <i class="bi bi-exclamation-triangle mb-2"></i>
            <div>Error al buscar clientes</div>
            <small>${errorMessage}</small>
            <small class="d-block">Revisa la consola para más detalles</small>
        </div>
    `;
            container.style.display = 'block';
        }

        /**
         * إخفاء الاقتراحات
         */
        function hideSuggestions(container) {
            container.style.display = 'none';
            selectedSuggestionIndex = -1;
        }

        /**
         * تأمين النص من XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // إضافة CSS والإعداد
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Customer autocomplete - DOM Ready');

            // إضافة CSS
            const style = document.createElement('style');
            style.textContent = `
        .customer-suggestions {
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background: white;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1050;
        }

        .suggestion-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
        }

        .suggestion-item:hover,
        .suggestion-item.active {
            background-color: #e3f2fd;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .customer-info .customer-name {
            color: #495057;
        }

        .customer-info .customer-phone {
            color: #6c757d;
        }

        .customer-stats {
            min-width: 120px;
        }

        .badge {
            font-size: 0.7rem;
        }

        #customer-info-display {
            border-left: 4px solid #28a745;
            background-color: #f8fff9;
            border-color: #c3e6cb;
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
    `;
            document.head.appendChild(style);

            // إعداد نظام التملؤ التلقائي
            setupCustomerAutocomplete();

            console.log('✅ Customer autocomplete initialized');
        });


    </script>

    <style>
        /* أنماط إضافية */
        .pricing-option {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pricing-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .pricing-option .form-check-input:checked ~ .form-check-label {
            color: #007bff;
        }

        .pricing-option .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }

        /* أنماط لأوضاع اختيار الجهاز */
        .device-input-option {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }

        .device-input-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .device-input-option .form-check-input:checked ~ .form-check-label {
            color: #007bff;
        }

        .device-input-option .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }

        /* نتائج البحث */
        .search-results {
            position: relative;
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
        }

        .search-results .list-group-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-results .list-group-item:hover {
            background-color: #e3f2fd;
        }

        .selected-part-item {
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .selected-part-item:hover {
            background-color: #e9ecef;
        }

        .common-issue-btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .ticket-preview {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px dashed #dee2e6;
        }

        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .sticky-top {
            top: 1rem;
        }

        @media (max-width: 768px) {
            .pricing-option {
                margin-bottom: 0.5rem;
            }

            .selected-part-item .row > div {
                margin-bottom: 0.5rem;
            }

            .ticket-preview {
                font-size: 0.75rem;
            }
        }

        /* تحسينات للقطع المختارة */
        .selected-part-item .btn-sm {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* تنسيق نتائج البحث */
        .list-group-item-success {
            border-color: #d1e7dd;
        }

        .list-group-item-secondary {
            opacity: 0.6;
        }

        /* تحسين العناصر التفاعلية */
        .form-check-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }


        <!-- CSS إضافي للتحسين -->
             /* تحسين شكل الاقتراحات */
         .customer-suggestions {
             border: 1px solid #dee2e6;
             border-radius: 0.375rem;
             box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
             background: white;
             max-height: 400px;
             overflow-y: auto;
             z-index: 1050;
         }

        .suggestion-item {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
        }

        .suggestion-item:hover,
        .suggestion-item.active {
            background-color: #e3f2fd;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .customer-info .bi {
            color: #6c757d;
        }

        .customer-stats .bi {
            color: #28a745;
        }

        .badge.bg-primary {
            background-color: #007bff !important;
        }

        /* تحسين الـ alert */
        #customer-info-display {
            border-left: 4px solid #28a745;
            background-color: #f8fff9;
            border-color: #c3e6cb;
        }

        /* تحسين الـ loading */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* تحسين responsive */
        @media (max-width: 768px) {
            .customer-suggestions {
                max-height: 300px;
            }

            .suggestion-item .d-flex {
                flex-direction: column;
            }

            .customer-stats {
                text-align: left;
                margin-top: 0.5rem;
            }
        }

    </style>



<?php require_once INCLUDES_PATH . 'footer.php'; ?>