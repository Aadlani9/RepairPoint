<?php
/**
 * RepairPoint - Agregar Nuevo Repuesto
 * صفحة إضافة قطعة غيار جديدة (Admin فقط)
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
$page_title = 'Agregar Nuevo Repuesto';

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        $db = getDB();

        try {
            $db->beginTransaction();

            // جمع البيانات من النموذج
            $part_data = [
                'shop_id' => $shop_id,
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

            if (empty($part_data['part_name'])) {
                $errors[] = 'El nombre del repuesto es obligatorio';
            }

            if ($part_data['total_price'] <= 0) {
                $errors[] = 'El precio total debe ser mayor que cero';
            }

            if ($part_data['cost_price'] !== null && $part_data['cost_price'] < 0) {
                $errors[] = 'El precio de coste no puede ser negativo';
            }

            if ($part_data['labor_cost'] < 0) {
                $errors[] = 'El coste de mano de obra no puede ser negativo';
            }

            if ($part_data['stock_quantity'] < 0) {
                $errors[] = 'La cantidad en stock no puede ser negativa';
            }

            if ($part_data['warranty_days'] < 1) {
                $errors[] = 'Los días de garantía deben ser al menos 1';
            }

            // التحقق من عدم تكرار كود القطعة
            if (!empty($part_data['part_code'])) {
                $existing_code = $db->selectOne(
                    "SELECT id FROM spare_parts WHERE shop_id = ? AND part_code = ? AND is_active = TRUE",
                    [$shop_id, $part_data['part_code']]
                );

                if ($existing_code) {
                    $errors[] = 'Ya existe un repuesto con este código';
                }
            }

            if (!empty($errors)) {
                throw new Exception(implode('<br>', $errors));
            }

            // إدراج القطعة الجديدة
            $part_id = $db->insert(
                "INSERT INTO spare_parts (shop_id, part_code, part_name, category, cost_price, 
                                         labor_cost, total_price, supplier_name, supplier_contact, 
                                         stock_status, stock_quantity, min_stock_level, warranty_days, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $part_data['shop_id'],
                    $part_data['part_code'] ?: null,
                    $part_data['part_name'],
                    $part_data['category'] ?: null,
                    $part_data['cost_price'],
                    $part_data['labor_cost'],
                    $part_data['total_price'],
                    $part_data['supplier_name'] ?: null,
                    $part_data['supplier_contact'] ?: null,
                    $part_data['stock_status'],
                    $part_data['stock_quantity'],
                    $part_data['min_stock_level'],
                    $part_data['warranty_days'],
                    $part_data['notes'] ?: null
                ]
            );

            if (!$part_id) {
                throw new Exception('Error al crear el repuesto');
            }

            // إضافة التوافق مع الهواتف
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

            $db->commit();

            setMessage('Repuesto agregado exitosamente', MSG_SUCCESS);
            header('Location: ' . url('pages/spare_parts.php'));
            exit;

        } catch (Exception $e) {
            $db->rollback();
            setMessage('Error al agregar el repuesto: ' . $e->getMessage(), MSG_ERROR);
        }
    }
}

// الحصول على البيانات المطلوبة للنموذج
$db = getDB();

// الحصول على الماركات
$brands = $db->select("SELECT * FROM brands ORDER BY name");

// الحصول على الفئات الموجودة
$existing_categories = getSparePartsCategories($shop_id);

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

// دمج الفئات
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
                            <i class="bi bi-plus-circle-fill text-primary"></i>
                            Agregar Nuevo Repuesto
                        </h1>
                        <p class="text-muted mb-0">
                            Agrega un nuevo repuesto al inventario del taller
                        </p>
                    </div>
                    <div>
                        <a href="<?= url('pages/spare_parts.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Volver al listado
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php displayMessage(); ?>

        <!-- نموذج إضافة القطعة -->
        <form method="POST" id="addPartForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <div class="row">
                <!-- المعلومات الأساسية -->
                <div class="col-lg-8">
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
                                           value="<?= htmlspecialchars($_POST['part_name'] ?? '') ?>"
                                           required>
                                    <div class="form-text">
                                        Ejemplo: Pantalla LCD iPhone 15 Pro Max
                                    </div>
                                </div>

                                <!-- كود القطعة -->
                                <div class="col-md-4">
                                    <label for="part_code" class="form-label">Código del Repuesto</label>
                                    <input type="text"
                                           class="form-control"
                                           id="part_code"
                                           name="part_code"
                                           value="<?= htmlspecialchars($_POST['part_code'] ?? '') ?>"
                                           placeholder="Opcional">
                                    <div class="form-text">
                                        Código interno opcional
                                    </div>
                                </div>

                                <!-- الفئة -->
                                <div class="col-md-4">
                                    <label for="category" class="form-label">Categoría</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($all_categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"
                                                <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                                <?= $default_categories[$cat] ?? ucfirst($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- فئة مخصصة -->
                                <div class="col-md-4">
                                    <label for="custom_category" class="form-label">Categoría Personalizada</label>
                                    <input type="text"
                                           class="form-control"
                                           id="custom_category"
                                           placeholder="Nueva categoría...">
                                    <div class="form-text">
                                        Escribe para crear una nueva categoría
                                    </div>
                                </div>

                                <!-- أيام الضمانة -->
                                <div class="col-md-4">
                                    <label for="warranty_days" class="form-label">Días de Garantía</label>
                                    <input type="number"
                                           class="form-control"
                                           id="warranty_days"
                                           name="warranty_days"
                                           value="<?= htmlspecialchars($_POST['warranty_days'] ?? '30') ?>"
                                           min="1"
                                           max="365">
                                </div>

                                <!-- ملاحظات -->
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notas</label>
                                    <textarea class="form-control"
                                              id="notes"
                                              name="notes"
                                              rows="3"
                                              placeholder="Notas adicionales sobre el repuesto..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
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
                                               value="<?= htmlspecialchars($_POST['cost_price'] ?? '') ?>"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                    </div>
                                    <div class="form-text">
                                        Precio de compra del repuesto
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
                                               value="<?= htmlspecialchars($_POST['labor_cost'] ?? '0.00') ?>"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                    </div>
                                    <div class="form-text">
                                        Coste estimado de instalación
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
                                               value="<?= htmlspecialchars($_POST['total_price'] ?? '') ?>"
                                               step="0.01"
                                               min="0.01"
                                               required
                                               placeholder="0.00">
                                    </div>
                                    <div class="form-text">
                                        Precio de venta al cliente
                                    </div>
                                </div>

                                <!-- عرض هامش الربح -->
                                <div class="col-12">
                                    <div class="alert alert-info" id="profitMarginInfo" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Coste Total:</strong>
                                                <span id="totalCostDisplay">€0.00</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Beneficio:</strong>
                                                <span id="profitDisplay">€0.00</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Margen:</strong>
                                                <span id="marginDisplay">0%</span>
                                            </div>
                                            <div class="col-md-3">
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
                                    <label for="stock_quantity" class="form-label">Cantidad Inicial</label>
                                    <input type="number"
                                           class="form-control"
                                           id="stock_quantity"
                                           name="stock_quantity"
                                           value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '0') ?>"
                                           min="0">
                                    <div class="form-text">
                                        Cantidad disponible inicialmente
                                    </div>
                                </div>

                                <!-- الحد الأدنى -->
                                <div class="col-md-4">
                                    <label for="min_stock_level" class="form-label">Nivel Mínimo de Stock</label>
                                    <input type="number"
                                           class="form-control"
                                           id="min_stock_level"
                                           name="min_stock_level"
                                           value="<?= htmlspecialchars($_POST['min_stock_level'] ?? '1') ?>"
                                           min="0">
                                    <div class="form-text">
                                        Aviso cuando llegue a esta cantidad
                                    </div>
                                </div>

                                <!-- حالة المخزون -->
                                <div class="col-md-4">
                                    <label for="stock_status" class="form-label">Estado del Stock</label>
                                    <select class="form-select" id="stock_status" name="stock_status">
                                        <option value="available" <?= ($_POST['stock_status'] ?? 'available') === 'available' ? 'selected' : '' ?>>
                                            Disponible
                                        </option>
                                        <option value="order_required" <?= ($_POST['stock_status'] ?? '') === 'order_required' ? 'selected' : '' ?>>
                                            Necesita Pedido
                                        </option>
                                        <option value="out_of_stock" <?= ($_POST['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>
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
                                           value="<?= htmlspecialchars($_POST['supplier_name'] ?? '') ?>"
                                           placeholder="Nombre de la empresa...">
                                </div>

                                <!-- معلومات التواصل -->
                                <div class="col-md-6">
                                    <label for="supplier_contact" class="form-label">Contacto del Proveedor</label>
                                    <input type="text"
                                           class="form-control"
                                           id="supplier_contact"
                                           name="supplier_contact"
                                           value="<?= htmlspecialchars($_POST['supplier_contact'] ?? '') ?>"
                                           placeholder="Teléfono, email...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- التوافق مع الهواتف -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-phone"></i>
                                Compatibilidad con Teléfonos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="compatibilityContainer">
                                <!-- سيتم إضافة حقول التوافق هنا عبر JavaScript -->
                            </div>

                            <button type="button"
                                    class="btn btn-outline-primary btn-sm w-100"
                                    onclick="addCompatibilityRow()">
                                <i class="bi bi-plus"></i>
                                Agregar Teléfono Compatible
                            </button>

                            <div class="form-text mt-2">
                                Especifica con qué teléfonos es compatible este repuesto
                            </div>
                        </div>
                    </div>

                    <!-- معاينة سريعة -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-eye"></i>
                                Vista Previa
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="previewContent">
                                <div class="text-center text-muted">
                                    <i class="bi bi-file-text" style="font-size: 2rem;"></i>
                                    <p class="mt-2">La vista previa aparecerá aquí</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الحفظ -->
            <div class="row">
                <div class="col-12">
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
                                    <button type="reset" class="btn btn-outline-warning me-2">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        Limpiar
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i>
                                        Guardar Repuesto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript -->
    <script>
        // متغيرات عامة
        const brands = <?= json_encode($brands) ?>;
        let compatibilityRowCount = 0;

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
                marginStatus.className = 'badge bg-success';
            } else if (margin >= 25) {
                marginStatus.textContent = 'Bueno';
                marginStatus.className = 'badge bg-warning';
            } else if (margin >= 0) {
                marginStatus.textContent = 'Bajo';
                marginStatus.className = 'badge bg-danger';
            } else {
                marginStatus.textContent = 'Pérdida';
                marginStatus.className = 'badge bg-dark';
            }

            // إظهار/إخفاء المعلومات
            const profitInfo = document.getElementById('profitMarginInfo');
            if (totalPrice > 0) {
                profitInfo.style.display = 'block';
            } else {
                profitInfo.style.display = 'none';
            }

            updatePreview();
        }

        // إضافة صف توافق جديد
        function addCompatibilityRow() {
            compatibilityRowCount++;

            const container = document.getElementById('compatibilityContainer');
            const rowDiv = document.createElement('div');
            rowDiv.className = 'compatibility-row mb-3 p-3 border rounded';
            rowDiv.id = `compatibility-row-${compatibilityRowCount}`;

            rowDiv.innerHTML = `
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label">Marca</label>
                <select class="form-select brand-select" name="compatible_phones[${compatibilityRowCount}][brand_id]" onchange="loadModels(this, ${compatibilityRowCount})">
                    <option value="">Seleccionar marca</option>
                    ${brands.map(brand => `<option value="${brand.id}">${brand.name}</option>`).join('')}
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Modelo</label>
                <select class="form-select model-select" name="compatible_phones[${compatibilityRowCount}][model_id]" id="model-select-${compatibilityRowCount}">
                    <option value="">Seleccionar modelo</option>
                </select>
            </div>
            <div class="col-10">
                <label class="form-label">Notas</label>
                <input type="text" class="form-control" name="compatible_phones[${compatibilityRowCount}][notes]" placeholder="Notas opcionales...">
            </div>
            <div class="col-2">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-danger w-100" onclick="removeCompatibilityRow(${compatibilityRowCount})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;

            container.appendChild(rowDiv);
        }

        // حذف صف التوافق
        function removeCompatibilityRow(rowId) {
            const row = document.getElementById(`compatibility-row-${rowId}`);
            if (row) {
                row.remove();
            }
        }

        // تحميل الموديلات حسب الماركة
        function loadModels(brandSelect, rowId) {
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
                                modelSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                    });
            }
        }

        // تحديث المعاينة
        function updatePreview() {
            const partName = document.getElementById('part_name').value;
            const partCode = document.getElementById('part_code').value;
            const category = document.getElementById('category').value;
            const totalPrice = document.getElementById('total_price').value;
            const stockQuantity = document.getElementById('stock_quantity').value;
            const stockStatus = document.getElementById('stock_status').value;
            const warrantyDays = document.getElementById('warranty_days').value;

            const previewContent = document.getElementById('previewContent');

            if (!partName && !totalPrice) {
                previewContent.innerHTML = `
            <div class="text-center text-muted">
                <i class="bi bi-file-text" style="font-size: 2rem;"></i>
                <p class="mt-2">La vista previa aparecerá aquí</p>
            </div>
        `;
                return;
            }

            let stockBadge = '';
            switch (stockStatus) {
                case 'available':
                    stockBadge = `<span class="badge bg-success">Disponible (${stockQuantity})</span>`;
                    break;
                case 'order_required':
                    stockBadge = `<span class="badge bg-warning">Necesita pedido (${stockQuantity})</span>`;
                    break;
                case 'out_of_stock':
                    stockBadge = `<span class="badge bg-danger">Sin stock</span>`;
                    break;
            }

            previewContent.innerHTML = `
        <div class="preview-card">
            <h6 class="fw-bold">${partName || 'Nombre del repuesto'}</h6>
            ${partCode ? `<small class="text-muted">Código: ${partCode}</small><br>` : ''}
            ${category ? `<span class="badge bg-info mb-2">${getCategoryName(category)}</span><br>` : ''}
            <div class="text-success fw-bold">€${parseFloat(totalPrice || 0).toFixed(2)}</div>
            <div class="mt-2">${stockBadge}</div>
            <small class="text-muted">Garantía: ${warrantyDays || 30} días</small>
        </div>
    `;
        }

        // الحصول على اسم الفئة
        function getCategoryName(category) {
            const categories = {
                'pantalla': 'Pantalla',
                'bateria': 'Batería',
                'camara': 'Cámara',
                'altavoz': 'Altavoz',
                'auricular': 'Auricular',
                'conector': 'Conector',
                'boton': 'Botón',
                'sensor': 'Sensor',
                'flex': 'Flex',
                'marco': 'Marco',
                'tapa': 'Tapa trasera',
                'cristal': 'Cristal',
                'otros': 'Otros'
            };

            return categories[category] || category;
        }

        // معالجة الفئة المخصصة
        document.getElementById('custom_category').addEventListener('input', function() {
            const customValue = this.value.trim();
            const categorySelect = document.getElementById('category');

            if (customValue) {
                // البحث عن خيار موجود أو إنشاء جديد
                let optionExists = false;
                for (let option of categorySelect.options) {
                    if (option.value.toLowerCase() === customValue.toLowerCase()) {
                        option.selected = true;
                        optionExists = true;
                        break;
                    }
                }

                if (!optionExists) {
                    // إضافة خيار جديد
                    const newOption = document.createElement('option');
                    newOption.value = customValue.toLowerCase();
                    newOption.textContent = customValue;
                    newOption.selected = true;
                    categorySelect.appendChild(newOption);
                }

                // مسح الحقل المخصص
                this.value = '';

                updatePreview();
            }
        });

        // تحديث تلقائي للمعاينة وحساب الهامش
        document.addEventListener('DOMContentLoaded', function() {
            // إضافة مستمعي الأحداث
            ['part_name', 'part_code', 'category', 'total_price', 'stock_quantity', 'stock_status', 'warranty_days'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', updatePreview);
                    field.addEventListener('change', updatePreview);
                }
            });

            ['cost_price', 'labor_cost', 'total_price'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', calculateProfitMargin);
                    field.addEventListener('change', calculateProfitMargin);
                }
            });

            // إضافة صف توافق أولي
            addCompatibilityRow();

            // حساب أولي
            calculateProfitMargin();
            updatePreview();
        });

        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('addPartForm').addEventListener('submit', function(e) {
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

            // تأكيد الحفظ
            if (!confirm('¿Estás seguro de que deseas guardar este repuesto?')) {
                e.preventDefault();
            }
        });

        // تنظيف النموذج
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            setTimeout(() => {
                // إعادة تعيين المتغيرات
                compatibilityRowCount = 0;
                document.getElementById('compatibilityContainer').innerHTML = '';

                // إضافة صف توافق جديد
                addCompatibilityRow();

                // إعادة حساب الهامش والمعاينة
                calculateProfitMargin();
                updatePreview();
            }, 100);
        });

        // حفظ مسودة في localStorage (اختياري)
        function saveDraft() {
            const formData = new FormData(document.getElementById('addPartForm'));
            const draftData = {};

            for (let [key, value] of formData.entries()) {
                draftData[key] = value;
            }

            localStorage.setItem('spare_part_draft', JSON.stringify(draftData));
        }

        // تحميل مسودة من localStorage (اختياري)
        function loadDraft() {
            const draftData = localStorage.getItem('spare_part_draft');
            if (draftData) {
                try {
                    const data = JSON.parse(draftData);

                    Object.keys(data).forEach(key => {
                        const field = document.querySelector(`[name="${key}"]`);
                        if (field) {
                            field.value = data[key];
                        }
                    });

                    calculateProfitMargin();
                    updatePreview();
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        }

        // حفظ مسودة تلقائياً كل 30 ثانية
        setInterval(saveDraft, 30000);

        // تحديث المخزون تلقائياً بناءً على الكمية
        document.getElementById('stock_quantity').addEventListener('input', function() {
            const quantity = parseInt(this.value) || 0;
            const minLevel = parseInt(document.getElementById('min_stock_level').value) || 1;
            const statusSelect = document.getElementById('stock_status');

            if (quantity <= 0) {
                statusSelect.value = 'out_of_stock';
            } else if (quantity <= minLevel) {
                statusSelect.value = 'order_required';
            } else {
                statusSelect.value = 'available';
            }

            updatePreview();
        });

        // تحديث الحد الأدنى بناءً على الكمية
        document.getElementById('min_stock_level').addEventListener('input', function() {
            const minLevel = parseInt(this.value) || 1;
            const quantity = parseInt(document.getElementById('stock_quantity').value) || 0;
            const statusSelect = document.getElementById('stock_status');

            if (quantity <= 0) {
                statusSelect.value = 'out_of_stock';
            } else if (quantity <= minLevel) {
                statusSelect.value = 'order_required';
            } else {
                statusSelect.value = 'available';
            }

            updatePreview();
        });
    </script>

    <style>
        .preview-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            background-color: #f8f9fa;
        }

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

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .btn-outline-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* تحسين الاستجابة */
        @media (max-width: 768px) {
            .compatibility-row .col-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0.5rem;
            }

            .compatibility-row .col-10 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .compatibility-row .col-2 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-top: 0.5rem;
            }

            .preview-card {
                font-size: 0.9rem;
            }
        }

        /* تنسيق المعاينة */
        .preview-card h6 {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .preview-card .badge {
            font-size: 0.75rem;
        }

        .preview-card .text-success {
            font-size: 1.1rem;
        }

        /* تحسين العناصر التفاعلية */
        .card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }

        /* تنسيق الأزرار */
        .btn-group .btn {
            border-radius: 0.375rem !important;
        }

        .btn-group .btn:not(:last-child) {
            margin-right: 0.5rem;
        }

        /* إشارة الحقول المطلوبة */
        .form-label .text-danger {
            font-weight: bold;
        }

        /* تحسين الجداول في الشاشات الصغيرة */
        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>