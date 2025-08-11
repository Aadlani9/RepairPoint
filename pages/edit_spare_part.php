<?php
/**
 * RepairPoint - Editar Repuesto
 * صفحة تعديل قطعة غيار (Admin فقط)
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

// التحقق من صلاحيات إدارة قطع الغيار
requireSparePartsManagement();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// الحصول على معرف القطعة
$part_id = intval($_GET['id'] ?? 0);

if (!$part_id) {
    setMessage('معرف القطعة غير صحيح', MSG_ERROR);
    header('Location: ' . url('pages/spare_parts.php'));
    exit;
}

$db = getDB();

// الحصول على بيانات القطعة
$part = $db->selectOne(
    "SELECT * FROM spare_parts WHERE id = ? AND shop_id = ?",
    [$part_id, $shop_id]
);

if (!$part) {
    setMessage('القطعة غير موجودة', MSG_ERROR);
    header('Location: ' . url('pages/spare_parts.php'));
    exit;
}

$page_title = 'Editar Repuesto: ' . $part['part_name'];

// الحصول على التوافق الحالي
$current_compatibility = $db->select(
    "SELECT spc.*, b.name as brand_name, m.name as model_name
     FROM spare_parts_compatibility spc
     JOIN brands b ON spc.brand_id = b.id
     JOIN models m ON spc.model_id = m.id
     WHERE spc.spare_part_id = ?
     ORDER BY b.name, m.name",
    [$part_id]
);

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        try {
            $db->beginTransaction();

            // حفظ الأسعار القديمة للمقارنة
            $old_cost_price = $part['cost_price'];
            $old_labor_cost = $part['labor_cost'];
            $old_total_price = $part['total_price'];

            // جمع البيانات الجديدة
            $new_data = [
                'part_code' => trim($_POST['part_code'] ?? ''),
                'part_name' => trim($_POST['part_name'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
                'labor_cost' => !empty($_POST['labor_cost']) ? floatval($_POST['labor_cost']) : 0.00,
                'total_price' => floatval($_POST['total_price'] ?? 0),
                'supplier_name' => trim($_POST['supplier_name'] ?? ''),
                'supplier_contact' => trim($_POST['supplier_contact'] ?? ''),
                'stock_status' => $_POST['stock_status'] ?? 'available',
                'stock_quantity' => intval($_POST['stock_quantity'] ?? 0),
                'min_stock_level' => intval($_POST['min_stock_level'] ?? 1),
                'warranty_days' => intval($_POST['warranty_days'] ?? 30),
                'notes' => trim($_POST['notes'] ?? '')
            ];

            // التحقق من صحة البيانات
            $errors = [];

            if (empty($new_data['part_name'])) {
                $errors[] = 'El nombre del repuesto es obligatorio';
            }

            if ($new_data['total_price'] <= 0) {
                $errors[] = 'El precio total debe ser mayor que cero';
            }

            if ($new_data['cost_price'] !== null && $new_data['cost_price'] < 0) {
                $errors[] = 'El precio de coste no puede ser negativo';
            }

            if ($new_data['labor_cost'] < 0) {
                $errors[] = 'El coste de mano de obra no puede ser negativo';
            }

            if ($new_data['stock_quantity'] < 0) {
                $errors[] = 'La cantidad en stock no puede ser negativa';
            }

            if ($new_data['warranty_days'] < 1) {
                $errors[] = 'Los días de garantía deben ser al menos 1';
            }

            // التحقق من عدم تكرار كود القطعة
            if (!empty($new_data['part_code']) && $new_data['part_code'] !== $part['part_code']) {
                $existing_code = $db->selectOne(
                    "SELECT id FROM spare_parts WHERE shop_id = ? AND part_code = ? AND id != ? AND is_active = TRUE",
                    [$shop_id, $new_data['part_code'], $part_id]
                );

                if ($existing_code) {
                    $errors[] = 'Ya existe otro repuesto con este código';
                }
            }

            if (!empty($errors)) {
                throw new Exception(implode('<br>', $errors));
            }

            // تحديث البيانات الأساسية
            $update_fields = [];
            $update_params = [];

            foreach ($new_data as $field => $value) {
                $update_fields[] = "$field = ?";
                $update_params[] = $value ?: null;
            }

            $update_params[] = $part_id;

            $updated = $db->update(
                "UPDATE spare_parts SET " . implode(', ', $update_fields) . " WHERE id = ?",
                $update_params
            );

            if (!$updated) {
                throw new Exception('Error al actualizar el repuesto');
            }

            // حفظ تاريخ تغيير الأسعار إذا تغيرت
            $price_changed = false;
            if ($old_cost_price != $new_data['cost_price'] ||
                $old_labor_cost != $new_data['labor_cost'] ||
                $old_total_price != $new_data['total_price']) {

                $price_changed = true;
                $change_reason = $_POST['price_change_reason'] ?? 'Actualización manual';

                if (function_exists('savePriceHistory')) {
                    savePriceHistory($part_id, $old_cost_price, $old_labor_cost, $old_total_price,
                        $new_data['cost_price'], $new_data['labor_cost'], $new_data['total_price'],
                        $change_reason, $current_user['id']);
                }
            }

            // تحديث التوافق مع الهواتف
            if (isset($_POST['update_compatibility'])) {
                // حذف التوافق الحالي
                $db->delete(
                    "DELETE FROM spare_parts_compatibility WHERE spare_part_id = ?",
                    [$part_id]
                );

                // إضافة التوافق الجديد
                if (!empty($_POST['compatible_phones'])) {
                    foreach ($_POST['compatible_phones'] as $phone) {
                        if (!empty($phone['brand_id']) && !empty($phone['model_id'])) {
                            $db->insert(
                                "INSERT INTO spare_parts_compatibility (spare_part_id, brand_id, model_id, notes)
                                 VALUES (?, ?, ?, ?)",
                                [
                                    $part_id,
                                    intval($phone['brand_id']),
                                    intval($phone['model_id']),
                                    trim($phone['notes'] ?? '') ?: null
                                ]
                            );
                        }
                    }
                }
            }

            $db->commit();

            $message = 'Repuesto actualizado exitosamente';
            if ($price_changed) {
                $message .= ' (cambio de precio registrado)';
            }

            setMessage($message, MSG_SUCCESS);
            header('Location: ' . url('pages/spare_parts.php'));
            exit;

        } catch (Exception $e) {
            $db->rollback();
            setMessage('Error al actualizar el repuesto: ' . $e->getMessage(), MSG_ERROR);
        }
    }
}

// الحصول على البيانات المطلوبة للنموذج
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$existing_categories = [];
if (function_exists('getSparePartsCategories')) {
    $existing_categories = getSparePartsCategories($shop_id);
}

// فئات افتراضية
$default_categories = [
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

$all_categories = array_unique(array_merge($existing_categories, array_keys($default_categories)));

// تضمين header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- رأس الصفحة -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-pencil-square text-warning"></i>
                            Editar Repuesto
                        </h1>
                        <p class="text-muted mb-0">
                            <?= htmlspecialchars($part['part_name']) ?>
                            <?php if (!empty($part['part_code'])): ?>
                                <small class="text-muted">(<?= htmlspecialchars($part['part_code']) ?>)</small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="<?= url('pages/spare_parts.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Volver al listado
                        </a>

                        <?php if (canDeleteSpareParts()): ?>
                            <button type="button"
                                    class="btn btn-outline-danger ms-2"
                                    onclick="deletePart(<?= $part['id'] ?>, '<?= htmlspecialchars($part['part_name']) ?>')">
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php displayMessage(); ?>

        <div class="row">
            <!-- نموذج التعديل -->
            <div class="col-lg-8">
                <form method="POST" id="editPartForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="update_compatibility" value="1">

                    <!-- المعلومات الأساسية -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle"></i>
                                Información Básica
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- اسم القطعة -->
                                <div class="col-md-8">
                                    <label for="part_name" class="form-label">
                                        Nombre del Repuesto <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="part_name"
                                           name="part_name"
                                           value="<?= htmlspecialchars($part['part_name']) ?>"
                                           required>
                                </div>

                                <!-- كود القطعة -->
                                <div class="col-md-4">
                                    <label for="part_code" class="form-label">Código del Repuesto</label>
                                    <input type="text"
                                           class="form-control"
                                           id="part_code"
                                           name="part_code"
                                           value="<?= htmlspecialchars($part['part_code'] ?? '') ?>">
                                </div>

                                <!-- الفئة -->
                                <div class="col-md-6">
                                    <label for="category" class="form-label">Categoría</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($all_categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"
                                                <?= $part['category'] === $cat ? 'selected' : '' ?>>
                                                <?= $default_categories[$cat] ?? ucfirst($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- أيام الضمانة -->
                                <div class="col-md-6">
                                    <label for="warranty_days" class="form-label">Días de Garantía</label>
                                    <input type="number"
                                           class="form-control"
                                           id="warranty_days"
                                           name="warranty_days"
                                           value="<?= htmlspecialchars($part['warranty_days']) ?>"
                                           min="1"
                                           max="365">
                                </div>

                                <!-- ملاحظات -->
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notas</label>
                                    <textarea class="form-control"
                                              id="notes"
                                              name="notes"
                                              rows="3"><?= htmlspecialchars($part['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات الأسعار -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-currency-euro"></i>
                                Precios y Costes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- سعر الشراء -->
                                <div class="col-md-4">
                                    <label for="cost_price" class="form-label">Precio de Coste</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number"
                                               class="form-control"
                                               id="cost_price"
                                               name="cost_price"
                                               value="<?= $part['cost_price'] ?? '' ?>"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <!-- تكلفة العمالة -->
                                <div class="col-md-4">
                                    <label for="labor_cost" class="form-label">Coste de Mano de Obra</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number"
                                               class="form-control"
                                               id="labor_cost"
                                               name="labor_cost"
                                               value="<?= $part['labor_cost'] ?? '0.00' ?>"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <!-- السعر النهائي -->
                                <div class="col-md-4">
                                    <label for="total_price" class="form-label">
                                        Precio Final <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number"
                                               class="form-control"
                                               id="total_price"
                                               name="total_price"
                                               value="<?= $part['total_price'] ?>"
                                               step="0.01"
                                               min="0.01"
                                               required
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <!-- سبب تغيير السعر -->
                                <div class="col-12" id="priceChangeReasonContainer" style="display: none;">
                                    <label for="price_change_reason" class="form-label">
                                        <i class="bi bi-exclamation-triangle text-warning"></i>
                                        Razón del cambio de precio
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="price_change_reason"
                                           name="price_change_reason"
                                           placeholder="Describe el motivo del cambio de precio...">
                                    <div class="form-text">
                                        Este campo es obligatorio cuando se modifican los precios
                                    </div>
                                </div>

                                <!-- عرض هامش الربح -->
                                <div class="col-12">
                                    <div class="alert alert-info" id="profitMarginInfo">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <strong>Coste Total:</strong><br>
                                                <span id="totalCostDisplay" class="h5">€0.00</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Beneficio:</strong><br>
                                                <span id="profitDisplay" class="h5">€0.00</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Margen:</strong><br>
                                                <span id="marginDisplay" class="h5">0%</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Estado:</strong><br>
                                                <span id="marginStatus" class="badge">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات المخزون -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-boxes"></i>
                                Gestión de Stock
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- الكمية الحالية -->
                                <div class="col-md-4">
                                    <label for="stock_quantity" class="form-label">Cantidad Actual</label>
                                    <input type="number"
                                           class="form-control"
                                           id="stock_quantity"
                                           name="stock_quantity"
                                           value="<?= $part['stock_quantity'] ?>"
                                           min="0">
                                </div>

                                <!-- الحد الأدنى -->
                                <div class="col-md-4">
                                    <label for="min_stock_level" class="form-label">Nivel Mínimo</label>
                                    <input type="number"
                                           class="form-control"
                                           id="min_stock_level"
                                           name="min_stock_level"
                                           value="<?= $part['min_stock_level'] ?>"
                                           min="0">
                                </div>

                                <!-- حالة المخزون -->
                                <div class="col-md-4">
                                    <label for="stock_status" class="form-label">Estado del Stock</label>
                                    <select class="form-select" id="stock_status" name="stock_status">
                                        <option value="available" <?= $part['stock_status'] === 'available' ? 'selected' : '' ?>>
                                            Disponible
                                        </option>
                                        <option value="order_required" <?= $part['stock_status'] === 'order_required' ? 'selected' : '' ?>>
                                            Necesita Pedido
                                        </option>
                                        <option value="out_of_stock" <?= $part['stock_status'] === 'out_of_stock' ? 'selected' : '' ?>>
                                            Sin Stock
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات المزود -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-truck"></i>
                                Información del Proveedor
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- اسم المزود -->
                                <div class="col-md-6">
                                    <label for="supplier_name" class="form-label">Nombre del Proveedor</label>
                                    <input type="text"
                                           class="form-control"
                                           id="supplier_name"
                                           name="supplier_name"
                                           value="<?= htmlspecialchars($part['supplier_name'] ?? '') ?>">
                                </div>

                                <!-- معلومات التواصل -->
                                <div class="col-md-6">
                                    <label for="supplier_contact" class="form-label">Contacto del Proveedor</label>
                                    <input type="text"
                                           class="form-control"
                                           id="supplier_contact"
                                           name="supplier_contact"
                                           value="<?= htmlspecialchars($part['supplier_contact'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- التوافق مع الهواتف -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-phone"></i>
                                Compatibilidad con Teléfonos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="compatibilityContainer">
                                <!-- سيتم تحميل التوافق الحالي عبر JavaScript -->
                            </div>

                            <button type="button"
                                    class="btn btn-outline-primary btn-sm w-100"
                                    onclick="addCompatibilityRow()">
                                <i class="bi bi-plus"></i>
                                Agregar Teléfono Compatible
                            </button>
                        </div>
                    </div>

                    <!-- أزرار الحفظ -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="<?= url('pages/spare_parts.php') ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i>
                                        Cancelar
                                    </a>
                                </div>
                                <div>
                                    <button type="button"
                                            class="btn btn-outline-info me-2"
                                            onclick="resetToOriginal()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        Restaurar Original
                                    </button>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-check-circle"></i>
                                        Actualizar Repuesto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- معلومات إضافية -->
            <div class="col-lg-4">
                <!-- معلومات عامة -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle"></i>
                            Información General
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted">ID:</small>
                                <div><?= $part['id'] ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Estado:</small>
                                <div>
                                    <?php if ($part['is_active']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Creado:</small>
                                <div><?= date('d/m/Y H:i', strtotime($part['created_at'])) ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Actualizado:</small>
                                <div><?= date('d/m/Y H:i', strtotime($part['updated_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- إحصائيات الاستخدام -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up"></i>
                            Estadísticas de Uso
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // الحصول على إحصائيات الاستخدام
                        $usage_stats = $db->selectOne(
                            "SELECT 
                        COUNT(*) as times_used,
                        SUM(quantity) as total_quantity_used,
                        SUM(total_price) as total_revenue,
                        AVG(unit_price) as avg_price,
                        MAX(used_at) as last_used
                     FROM repair_spare_parts rsp
                     JOIN repairs r ON rsp.repair_id = r.id
                     WHERE rsp.spare_part_id = ? AND r.status = 'delivered'",
                            [$part_id]
                        );
                        ?>

                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h4 mb-0"><?= $usage_stats['times_used'] ?? 0 ?></div>
                                    <small class="text-muted">Veces usado</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h4 mb-0"><?= $usage_stats['total_quantity_used'] ?? 0 ?></div>
                                    <small class="text-muted">Unidades vendidas</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-2">
                                    <div class="h4 mb-0 text-success">
                                        €<?= number_format($usage_stats['total_revenue'] ?? 0, 2) ?>
                                    </div>
                                    <small class="text-muted">Ingresos totales</small>
                                </div>
                            </div>
                            <?php if ($usage_stats['last_used']): ?>
                                <div class="col-12">
                                    <small class="text-muted">
                                        Último uso: <?= date('d/m/Y', strtotime($usage_stats['last_used'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراءات السريعة -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning"></i>
                            Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($part['stock_status'] !== 'out_of_stock'): ?>
                                <button type="button"
                                        class="btn btn-outline-success btn-sm"
                                        onclick="usePartInRepair(<?= $part['id'] ?>)">
                                    <i class="bi bi-plus"></i>
                                    Usar en Reparación
                                </button>
                            <?php endif; ?>

                            <button type="button"
                                    class="btn btn-outline-info btn-sm"
                                    onclick="duplicatePart()">
                                <i class="bi bi-files"></i>
                                Duplicar Repuesto
                            </button>

                            <?php if ($part['is_active']): ?>
                                <button type="button"
                                        class="btn btn-outline-warning btn-sm"
                                        onclick="deactivatePart()">
                                    <i class="bi bi-eye-slash"></i>
                                    Desactivar
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        class="btn btn-outline-success btn-sm"
                                        onclick="activatePart()">
                                    <i class="bi bi-eye"></i>
                                    Activar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript مُصحح -->
    <script>
        // متغيرات عامة
        const brands = <?= json_encode($brands) ?>;
        const currentCompatibility = <?= json_encode($current_compatibility) ?>;
        const originalData = <?= json_encode($part) ?>;
        let compatibilityRowCount = 0;

        // تتبع التغييرات في الأسعار
        const originalPrices = {
            cost: parseFloat('<?= $part['cost_price'] ?? 0 ?>'),
            labor: parseFloat('<?= $part['labor_cost'] ?? 0 ?>'),
            total: parseFloat('<?= $part['total_price'] ?>')
        };

        // حساب هامش الربح
        function calculateProfitMargin() {
            const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
            const laborCost = parseFloat(document.getElementById('labor_cost').value) || 0;
            const totalPrice = parseFloat(document.getElementById('total_price').value) || 0;

            const totalCost = costPrice + laborCost;
            const profit = totalPrice - totalCost;
            const margin = totalCost > 0 ? ((profit / totalCost) * 100) : (totalPrice > 0 ? 100 : 0);

            // تحديث العرض
            document.getElementById('totalCostDisplay').textContent = '€' + totalCost.toFixed(2);
            document.getElementById('profitDisplay').textContent = '€' + profit.toFixed(2);
            document.getElementById('marginDisplay').textContent = margin.toFixed(1) + '%';

            // تحديد حالة الهامش
            const marginStatus = document.getElementById('marginStatus');
            if (margin >= 50) {
                marginStatus.textContent = 'Excelente';
                marginStatus.className = 'badge bg-success fs-6';
            } else if (margin >= 25) {
                marginStatus.textContent = 'Bueno';
                marginStatus.className = 'badge bg-warning fs-6';
            } else if (margin >= 0) {
                marginStatus.textContent = 'Bajo';
                marginStatus.className = 'badge bg-danger fs-6';
            } else {
                marginStatus.textContent = 'Pérdida';
                marginStatus.className = 'badge bg-dark fs-6';
            }

            // التحقق من تغيير الأسعار
            checkPriceChanges();
        }

        // التحقق من تغيير الأسعار
        function checkPriceChanges() {
            const currentCost = parseFloat(document.getElementById('cost_price').value) || 0;
            const currentLabor = parseFloat(document.getElementById('labor_cost').value) || 0;
            const currentTotal = parseFloat(document.getElementById('total_price').value) || 0;

            const priceChanged =
                currentCost !== originalPrices.cost ||
                currentLabor !== originalPrices.labor ||
                currentTotal !== originalPrices.total;

            const reasonContainer = document.getElementById('priceChangeReasonContainer');
            const reasonInput = document.getElementById('price_change_reason');

            if (priceChanged) {
                reasonContainer.style.display = 'block';
                reasonInput.required = true;
            } else {
                reasonContainer.style.display = 'none';
                reasonInput.required = false;
                reasonInput.value = '';
            }
        }

        // إضافة صف توافق جديد
        function addCompatibilityRow(brandId = '', modelId = '', notes = '') {
            compatibilityRowCount++;

            const container = document.getElementById('compatibilityContainer');
            const rowDiv = document.createElement('div');
            rowDiv.className = 'compatibility-row mb-3 p-3 border rounded';
            rowDiv.id = `compatibility-row-${compatibilityRowCount}`;

            rowDiv.innerHTML = `
        <div class="row g-2">
            <div class="col-5">
                <label class="form-label">Marca</label>
                <select class="form-select brand-select" name="compatible_phones[${compatibilityRowCount}][brand_id]" onchange="loadModels(this, ${compatibilityRowCount})">
                    <option value="">Seleccionar marca</option>
                    ${brands.map(brand =>
                `<option value="${brand.id}" ${brand.id == brandId ? 'selected' : ''}>${brand.name}</option>`
            ).join('')}
                </select>
            </div>
            <div class="col-5">
                <label class="form-label">Modelo</label>
                <select class="form-select model-select" name="compatible_phones[${compatibilityRowCount}][model_id]" id="model-select-${compatibilityRowCount}">
                    <option value="">Seleccionar modelo</option>
                </select>
            </div>
            <div class="col-2">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-danger w-100" onclick="removeCompatibilityRow(${compatibilityRowCount})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="col-12">
                <label class="form-label">Notas</label>
                <input type="text" class="form-control" name="compatible_phones[${compatibilityRowCount}][notes]" value="${notes}" placeholder="Notas opcionales...">
            </div>
        </div>
    `;

            container.appendChild(rowDiv);

            // تحميل الموديلات إذا كانت الماركة محددة
            if (brandId) {
                loadModels(rowDiv.querySelector('.brand-select'), compatibilityRowCount, modelId);
            }
        }

        // حذف صف التوافق
        function removeCompatibilityRow(rowId) {
            const row = document.getElementById(`compatibility-row-${rowId}`);
            if (row) {
                row.remove();
            }
        }

        // تحميل الموديلات حسب الماركة
        function loadModels(brandSelect, rowId, selectedModelId = '') {
            const brandId = brandSelect.value;
            const modelSelect = document.getElementById(`model-select-${rowId}`);

            // مسح الموديلات الحالية
            modelSelect.innerHTML = '<option value="">Seleccionar modelo</option>';

            if (brandId) {
                // تحميل الموديلات
                fetch(`<?= url('api/models.php') ?>?action=get_by_brand&brand_id=${brandId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            data.data.forEach(model => {
                                const option = document.createElement('option');
                                option.value = model.id;
                                option.textContent = model.name;
                                if (model.id == selectedModelId) {
                                    option.selected = true;
                                }
                                modelSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                    });
            }
        }

        // تحميل التوافق الحالي
        function loadCurrentCompatibility() {
            currentCompatibility.forEach(comp => {
                addCompatibilityRow(comp.brand_id, comp.model_id, comp.notes);
            });
        }

        // استعادة البيانات الأصلية
        function resetToOriginal() {
            if (!confirm('¿Estás seguro de que deseas restaurar todos los valores originales?')) {
                return;
            }

            // استعادة الحقول الأساسية
            Object.keys(originalData).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = originalData[key] || '';
                }
            });

            // استعادة التوافق
            document.getElementById('compatibilityContainer').innerHTML = '';
            compatibilityRowCount = 0;
            loadCurrentCompatibility();

            // إعادة حساب الهامش
            calculateProfitMargin();
        }

        // تحديث حالة المخزون تلقائياً
        function updateStockStatus() {
            const quantity = parseInt(document.getElementById('stock_quantity').value) || 0;
            const minLevel = parseInt(document.getElementById('min_stock_level').value) || 1;
            const statusSelect = document.getElementById('stock_status');

            if (quantity <= 0) {
                statusSelect.value = 'out_of_stock';
            } else if (quantity <= minLevel) {
                statusSelect.value = 'order_required';
            } else {
                statusSelect.value = 'available';
            }
        }

        // وظائف الإجراءات السريعة
        function usePartInRepair(partId) {
            window.location.href = `<?= url('pages/add_repair.php') ?>?suggested_part=${partId}`;
        }

        function duplicatePart() {
            if (confirm('¿Deseas crear un nuevo repuesto basado en este?')) {
                const currentData = new URLSearchParams();

                // جمع البيانات الحالية
                const form = document.getElementById('editPartForm');
                const formData = new FormData(form);

                for (let [key, value] of formData.entries()) {
                    if (key !== 'csrf_token' && key !== 'update_compatibility') {
                        currentData.append(key, value);
                    }
                }

                // إضافة نص للتمييز
                const partName = document.getElementById('part_name').value;
                currentData.set('part_name', partName + ' (Copia)');
                currentData.delete('part_code'); // حذف الكود لتجنب التكرار

                window.location.href = `<?= url('pages/add_spare_part.php') ?>?${currentData.toString()}`;
            }
        }

        function activatePart() {
            if (confirm('¿Deseas activar este repuesto?')) {
                updatePartStatus(true);
            }
        }

        function deactivatePart() {
            if (confirm('¿Deseas desactivar este repuesto? No se mostrará en las búsquedas.')) {
                updatePartStatus(false);
            }
        }

        function updatePartStatus(isActive) {
            fetch(`<?= url('api/spare_parts.php') ?>?action=update&id=<?= $part['id'] ?>`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    is_active: isActive
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }

        // حذف القطعة
        function deletePart(partId, partName) {
            if (!confirm(`¿Estás seguro de que deseas eliminar el repuesto "${partName}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }

            if (!confirm('¿Estás completamente seguro? Esta es tu última oportunidad para cancelar.')) {
                return;
            }

            fetch(`<?= url('api/spare_parts.php') ?>?action=delete&id=${partId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Repuesto eliminado exitosamente');
                        window.location.href = '<?= url('pages/spare_parts.php') ?>';
                    } else {
                        alert(`Error al eliminar: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al eliminar el repuesto');
                });
        }

        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('editPartForm').addEventListener('submit', function(e) {
            const partName = document.getElementById('part_name').value.trim();
            const totalPrice = parseFloat(document.getElementById('total_price').value);

            if (!partName) {
                e.preventDefault();
                alert('El nombre del repuesto es obligatorio');
                document.getElementById('part_name').focus();
                return;
            }

            if (!totalPrice || totalPrice <= 0) {
                e.preventDefault();
                alert('El precio total debe ser mayor que cero');
                document.getElementById('total_price').focus();
                return;
            }

            // التحقق من سبب تغيير السعر إذا كان مطلوباً
            const reasonInput = document.getElementById('price_change_reason');
            if (reasonInput.required && !reasonInput.value.trim()) {
                e.preventDefault();
                alert('Por favor, especifica el motivo del cambio de precio');
                reasonInput.focus();
                return;
            }

            // تأكيد الحفظ
            if (!confirm('¿Estás seguro de que deseas guardar los cambios?')) {
                e.preventDefault();
            }
        });

        // إعداد الصفحة عند التحميل
        document.addEventListener('DOMContentLoaded', function() {
            // تحميل التوافق الحالي
            loadCurrentCompatibility();

            // إضافة مستمعي الأحداث للأسعار
            ['cost_price', 'labor_cost', 'total_price'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', calculateProfitMargin);
                    field.addEventListener('change', calculateProfitMargin);
                }
            });

            // إضافة مستمعي الأحداث للمخزون
            document.getElementById('stock_quantity').addEventListener('input', updateStockStatus);
            document.getElementById('min_stock_level').addEventListener('input', updateStockStatus);

            // حساب أولي
            calculateProfitMargin();

            console.log('✅ Edit spare part page loaded successfully');
        });
    </script>

    <!-- CSS محسن للصفحة -->
    <style>
        .compatibility-row {
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .compatibility-row:hover {
            background-color: #e9ecef;
        }

        #profitMarginInfo {
            border-left: 4px solid #0dcaf0;
        }

        .card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }

        .btn-outline-danger:hover {
            transform: translateY(-1px);
        }

        .border.rounded.p-2 {
            transition: all 0.2s ease;
        }

        .border.rounded.p-2:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
        }

        /* تحسين استجابة الجداول */
        @media (max-width: 768px) {
            .compatibility-row .col-5 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0.5rem;
            }

            .compatibility-row .col-2 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-top: 0.5rem;
            }
        }

        /* تنسيق مخصص للأزرار */
        .btn-group .btn {
            border-radius: 0.375rem !important;
            margin-right: 0.25rem;
        }

        /* تحسين مظهر النماذج */
        .form-control:focus,
        .form-select:focus {
            border-color: #fd7e14;
            box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
        }

        /* إشارة الحقول المطلوبة */
        .form-label .text-danger {
            font-weight: bold;
        }

        /* تنسيق البطاقات الإحصائية */
        .border.rounded.p-2 {
            background-color: #fff;
            border: 1px solid #dee2e6 !important;
        }

        /* تحسين الألوان */
        .text-success { color: #198754 !important; }
        .text-warning { color: #ffc107 !important; }
        .text-danger { color: #dc3545 !important; }

        /* تحسين التنقل */
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>