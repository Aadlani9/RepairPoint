<?php
/**
 * RepairPoint - واجهة POS للإصلاحات السريعة
 * صفحة مستقلة لإدخال الإصلاحات بسرعة
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir الملفات الأساسية (مسارات صحيحة)
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// التحقق من المصادقة
authMiddleware();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// معالجة طلب إضافة إصلاح سريع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => '', 'data' => null];

    try {
        $db = getDB();

        switch ($_POST['action']) {
            case 'quick_repair':
                $response = handleQuickRepair($_POST, $db, $shop_id);
                break;

            case 'search_customer':
                $response = handleCustomerSearch($_POST, $db, $shop_id);
                break;

            case 'get_spare_parts':
                $response = handleSparePartsSearch($_POST, $db, $shop_id);
                break;

            case 'get_active_repairs':
                $response = handleActiveRepairs($db, $shop_id);
                break;
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit;
}

/**
 * معالجة إضافة إصلاح سريع
 */
function handleQuickRepair($data, $db, $shop_id) {
    try {
        // التحقق من البيانات المطلوبة
        $required = ['customer_name', 'customer_phone', 'brand_id', 'model_id', 'issue_description'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Campo {$field} es obligatorio"];
            }
        }

        $db->beginTransaction();

        // توليد رقم مرجعي
        $reference = generateRepairReference();

        // حساب التكلفة الإجمالية
        $total_cost = 0;
        $spare_parts = [];

        if (!empty($data['selected_parts'])) {
            $spare_parts = json_decode($data['selected_parts'], true);
            foreach ($spare_parts as $part) {
                $total_cost += floatval($part['price']) * intval($part['quantity']);
            }
        }

        // إضافة تكلفة يدوية إذا وجدت
        if (!empty($data['manual_cost'])) {
            $total_cost += floatval($data['manual_cost']);
        }

        // إدراج الإصلاح
        $repair_id = $db->insert(
            "INSERT INTO repairs (reference, customer_name, customer_phone, brand_id, model_id, 
                                 issue_description, estimated_cost, priority, status, warranty_days,
                                 received_at, created_by, shop_id, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), ?, ?, ?)",
            [
                $reference,
                trim($data['customer_name']),
                trim($data['customer_phone']),
                intval($data['brand_id']),
                intval($data['model_id']),
                trim($data['issue_description']),
                $total_cost,
                $data['priority'] ?? 'medium',
                intval($data['warranty_days'] ?? 30),
                $_SESSION['user_id'],
                $shop_id,
                trim($data['notes'] ?? '')
            ]
        );

        if (!$repair_id) {
            throw new Exception('Error al crear la reparación');
        }

        // إضافة قطع الغيار إذا وجدت
        foreach ($spare_parts as $part) {
            if (canUseSpareParts()) {
                addRepairSparePart(
                    $repair_id,
                    intval($part['id']),
                    intval($part['quantity']),
                    floatval($part['price'])
                );
            }
        }

        $db->commit();

        return [
            'success' => true,
            'message' => 'Reparación creada exitosamente',
            'data' => [
                'repair_id' => $repair_id,
                'reference' => $reference,
                'total_cost' => $total_cost
            ]
        ];

    } catch (Exception $e) {
        $db->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * معالجة البحث عن العملاء
 */
function handleCustomerSearch($data, $db, $shop_id) {
    $search = trim($data['search'] ?? '');

    if (strlen($search) < 2) {
        return ['success' => true, 'data' => []];
    }

    $customers = $db->select(
        "SELECT DISTINCT customer_name, customer_phone, 
                COUNT(*) as total_repairs,
                MAX(created_at) as last_repair
         FROM repairs 
         WHERE shop_id = ? AND (customer_name LIKE ? OR customer_phone LIKE ?)
         GROUP BY customer_name, customer_phone
         ORDER BY last_repair DESC
         LIMIT 8",
        [$shop_id, "%{$search}%", "%{$search}%"]
    );

    return ['success' => true, 'data' => $customers];
}

/**
 * معالجة البحث في قطع الغيار
 */
function handleSparePartsSearch($data, $db, $shop_id) {
    $brand_id = intval($data['brand_id'] ?? 0);
    $model_id = intval($data['model_id'] ?? 0);

    if (!$brand_id || !$model_id) {
        return ['success' => true, 'data' => []];
    }

    // البحث عن قطع الغيار المتوافقة
    $parts = $db->select(
        "SELECT sp.id, sp.part_name, sp.category, sp.total_price, 
                sp.stock_status, sp.stock_quantity
         FROM spare_parts sp
         LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
         WHERE sp.shop_id = ? AND sp.is_active = TRUE 
         AND sp.stock_status != 'out_of_stock'
         AND (spc.brand_id = ? AND spc.model_id = ? OR spc.spare_part_id IS NULL)
         GROUP BY sp.id
         ORDER BY sp.category, sp.part_name
         LIMIT 20",
        [$shop_id, $brand_id, $model_id]
    );

    return ['success' => true, 'data' => $parts];
}

/**
 * الحصول على الإصلاحات النشطة
 */
function handleActiveRepairs($db, $shop_id) {
    $repairs = $db->select(
        "SELECT r.id, r.reference, r.customer_name, r.status, 
                r.created_at, r.estimated_cost,
                CONCAT(b.name, ' ', m.name) as device
         FROM repairs r
         JOIN brands b ON r.brand_id = b.id
         JOIN models m ON r.model_id = m.id
         WHERE r.shop_id = ? AND r.status IN ('pending', 'in_progress', 'completed')
         ORDER BY r.created_at DESC
         LIMIT 10",
        [$shop_id]
    );

    return ['success' => true, 'data' => $repairs];
}

// الحصول على البيانات الأساسية
$db = getDB();
$brands = $db->select("SELECT * FROM brands ORDER BY name");
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Reparaciones Rápidas | RepairPoint</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .pos-container {
            height: 100vh;
            padding: 10px;
        }

        .pos-header {
            background: white;
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .pos-main {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 15px;
            height: calc(100vh - 120px);
        }

        .pos-left {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .pos-right {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .search-section {
            background: var(--light-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-section {
            background: var(--light-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .spare-parts-section {
            background: var(--light-color);
            border-radius: 12px;
            padding: 20px;
        }

        .section-title {
            color: var(--dark-color);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-pos {
            border-radius: 10px;
            font-weight: 500;
            padding: 12px 20px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-pos:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-pos-lg {
            padding: 15px 25px;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .spare-part-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .spare-part-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .spare-part-card.selected {
            border-color: var(--success-color);
            background: #f8fff9;
        }

        .spare-part-card .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .active-repair-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .active-repair-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .status-pending { border-left-color: var(--warning-color); }
        .status-in_progress { border-left-color: var(--primary-color); }
        .status-completed { border-left-color: var(--success-color); }

        .customer-suggestion {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin: 2px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .customer-suggestion:hover {
            background: var(--light-color);
            border-color: var(--primary-color);
        }

        .customer-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }

        .total-section {
            background: linear-gradient(45deg, var(--success-color), #20c997);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .total-amount {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #0b5ed7;
            transform: scale(1.1);
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 5px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .pos-main {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
            }

            .pos-right {
                max-height: 300px;
            }
        }

        @media (max-width: 768px) {
            .pos-container {
                padding: 5px;
            }

            .pos-header {
                padding: 10px 15px;
            }

            .pos-left, .pos-right {
                padding: 15px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Fullscreen Styles */
        .fullscreen-mode {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .fullscreen-mode .pos-container {
            height: 100vh;
            width: 100vw;
            padding: 10px;
        }

        .fullscreen-mode .pos-header {
            margin-bottom: 10px;
        }

        .fullscreen-mode .pos-main {
            height: calc(100vh - 100px);
        }

        /* تحسين الأزرار في الشاشة الكاملة */
        .fullscreen-mode .btn-pos {
            font-size: 1.1rem;
            padding: 12px 20px;
        }

        .fullscreen-mode .form-control,
        .fullscreen-mode .form-select {
            font-size: 1.1rem;
            padding: 14px 16px;
        }

        .fullscreen-mode .spare-part-card {
            padding: 18px;
            margin-bottom: 12px;
        }

        .fullscreen-mode .total-amount {
            font-size: 2.5rem;
        }
    </style>
</head>
<body>
<div class="pos-container">
    <!-- Header -->
    <div class="pos-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-tools text-primary fs-4 me-3"></i>
            <div>
                <h4 class="mb-0">POS - Reparaciones Rápidas</h4>
                <small class="text-muted">Usuario: <?= htmlspecialchars($current_user['name']) ?> | Taller: <?= htmlspecialchars($_SESSION['shop_name']) ?></small>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info btn-pos" id="fullscreenBtn" onclick="toggleFullscreen()">
                <i class="bi bi-arrows-fullscreen" id="fullscreenIcon"></i> <span id="fullscreenText">Pantalla Completa</span>
            </button>
            <a href="../pages/dashboard.php" class="btn btn-outline-primary btn-pos">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <button type="button" class="btn btn-outline-success btn-pos" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pos-main">
        <!-- Left Panel -->
        <div class="pos-left">
            <!-- Customer Search -->
            <div class="search-section">
                <div class="section-title">
                    <i class="bi bi-person-search text-primary"></i>
                    Buscar Cliente
                </div>
                <div class="position-relative">
                    <input type="text"
                           class="form-control"
                           id="customerSearch"
                           placeholder="Buscar por nombre o teléfono..."
                           autocomplete="off">
                    <div class="customer-suggestions" id="customerSuggestions"></div>
                </div>
            </div>

            <!-- Quick Form -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-plus-circle text-success"></i>
                    Nueva Reparación
                </div>

                <form id="quickRepairForm">
                    <div class="row g-3">
                        <!-- Customer Info -->
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Cliente *</label>
                            <input type="text" class="form-control" id="customerName" name="customer_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="customerPhone" name="customer_phone" required>
                        </div>

                        <!-- Device Info -->
                        <div class="col-md-6">
                            <label class="form-label">Marca *</label>
                            <select class="form-select" id="brandSelect" name="brand_id" required>
                                <option value="">Seleccionar marca</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modelo *</label>
                            <select class="form-select" id="modelSelect" name="model_id" required disabled>
                                <option value="">Primero selecciona una marca</option>
                            </select>
                        </div>

                        <!-- Issue Description -->
                        <div class="col-12">
                            <label class="form-label">Descripción del Problema *</label>
                            <textarea class="form-control" id="issueDescription" name="issue_description" rows="3" required></textarea>
                        </div>

                        <!-- Priority and Cost -->
                        <div class="col-md-4">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select" name="priority">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Garantía (días)</label>
                            <input type="number" class="form-control" name="warranty_days" value="30" min="1" max="365">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Coste Manual (€)</label>
                            <input type="number" class="form-control" id="manualCost" name="manual_cost" step="0.01" min="0">
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <input type="text" class="form-control" name="notes" placeholder="Observaciones adicionales...">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Spare Parts Selection -->
            <?php if (canUseSpareParts()): ?>
                <div class="spare-parts-section">
                    <div class="section-title">
                        <i class="bi bi-gear text-warning"></i>
                        Repuestos
                        <span class="badge bg-secondary ms-2" id="selectedPartsCount">0</span>
                    </div>

                    <div id="sparePartsContainer">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle fs-4"></i>
                            <p class="mt-2">Selecciona una marca y modelo para ver repuestos compatibles</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                <button type="button" class="btn btn-success btn-pos btn-pos-lg" id="saveRepairBtn">
                    <i class="bi bi-check-circle me-2"></i>
                    Guardar Reparación
                </button>
                <button type="button" class="btn btn-info btn-pos btn-pos-lg" id="saveAndPrintBtn">
                    <i class="bi bi-printer me-2"></i>
                    Guardar e Imprimir
                </button>
                <button type="button" class="btn btn-outline-secondary btn-pos" id="clearFormBtn">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="pos-right">
            <!-- Total Section -->
            <div class="total-section">
                <i class="bi bi-calculator fs-3"></i>
                <div class="total-amount" id="totalAmount">€0.00</div>
                <small>Total Estimado</small>
            </div>

            <!-- Active Repairs -->
            <div class="section-title">
                <i class="bi bi-list-task text-info"></i>
                Reparaciones Activas
                <button class="btn btn-sm btn-outline-primary ms-auto" id="refreshRepairsBtn">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>

            <div id="activeRepairsContainer">
                <div class="text-center text-muted py-4">
                    <div class="loading"></div>
                    <p class="mt-2">Cargando...</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-4">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="bg-warning text-dark rounded p-2">
                            <div class="fw-bold" id="pendingCount">-</div>
                            <small>Pendientes</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-primary text-white rounded p-2">
                            <div class="fw-bold" id="progressCount">-</div>
                            <small>En Proceso</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-success text-white rounded p-2">
                            <div class="fw-bold" id="completedCount">-</div>
                            <small>Listos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // متغيرات عامة
    let selectedSpareParts = new Map();
    let customerSearchTimeout;
    const shopId = <?= $shop_id ?>;

    // تهيئة الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 POS Interface Loaded');

        // تحميل الإصلاحات النشطة
        loadActiveRepairs();

        // إعداد البحث عن العملاء
        setupCustomerSearch();

        // إعداد اختيار الماركة والموديل
        setupBrandModelSelection();

        // إعداد أزرار الحفظ
        setupSaveButtons();

        // إعداد تحديث التكلفة الإجمالية
        setupCostCalculation();

        // تحديث دوري للإصلاحات النشطة
        setInterval(loadActiveRepairs, 30000); // كل 30 ثانية
    });

    // إعداد البحث عن العملاء
    function setupCustomerSearch() {
        const searchInput = document.getElementById('customerSearch');
        const suggestionsDiv = document.getElementById('customerSuggestions');

        searchInput.addEventListener('input', function() {
            clearTimeout(customerSearchTimeout);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            customerSearchTimeout = setTimeout(() => {
                searchCustomers(searchTerm);
            }, 300);
        });

        // إخفاء الاقتراحات عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
    }

    // البحث عن العملاء
    async function searchCustomers(searchTerm) {
        try {
            const response = await fetch('pos_interface.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=search_customer&search=${encodeURIComponent(searchTerm)}`
            });

            const data = await response.json();

            if (data.success) {
                displayCustomerSuggestions(data.data);
            }
        } catch (error) {
            console.error('Error searching customers:', error);
        }
    }

    // عرض اقتراحات العملاء
    function displayCustomerSuggestions(customers) {
        const suggestionsDiv = document.getElementById('customerSuggestions');

        if (customers.length === 0) {
            suggestionsDiv.style.display = 'none';
            return;
        }

        let html = '';
        customers.forEach(customer => {
            html += `
                    <div class="customer-suggestion" onclick="selectCustomer('${customer.customer_name}', '${customer.customer_phone}')">
                        <div class="fw-bold">${customer.customer_name}</div>
                        <div class="text-muted small">
                            <i class="bi bi-telephone me-1"></i>${customer.customer_phone}
                            <span class="ms-2">
                                <i class="bi bi-tools me-1"></i>${customer.total_repairs} reparaciones
                            </span>
                        </div>
                    </div>
                `;
        });

        suggestionsDiv.innerHTML = html;
        suggestionsDiv.style.display = 'block';
    }

    // اختيار عميل من الاقتراحات
    function selectCustomer(name, phone) {
        document.getElementById('customerName').value = name;
        document.getElementById('customerPhone').value = phone;
        document.getElementById('customerSearch').value = name;
        document.getElementById('customerSuggestions').style.display = 'none';

        // تركيز على حقل الماركة
        document.getElementById('brandSelect').focus();
    }

    // إعداد اختيار الماركة والموديل
    function setupBrandModelSelection() {
        const brandSelect = document.getElementById('brandSelect');
        const modelSelect = document.getElementById('modelSelect');

        brandSelect.addEventListener('change', function() {
            const brandId = this.value;

            // مسح الموديلات
            modelSelect.innerHTML = '<option value="">Cargando modelos...</option>';
            modelSelect.disabled = true;

            if (brandId) {
                loadModels(brandId);
            } else {
                modelSelect.innerHTML = '<option value="">Primero selecciona una marca</option>';
                clearSpareParts();
            }
        });

        modelSelect.addEventListener('change', function() {
            const brandId = brandSelect.value;
            const modelId = this.value;

            if (brandId && modelId) {
                loadSpareParts(brandId, modelId);
            } else {
                clearSpareParts();
            }
        });
    }

    // تحميل الموديلات
    async function loadModels(brandId) {
        try {
            const response = await fetch(`api/models.php?action=get_by_brand&brand_id=${brandId}`);
            const data = await response.json();

            const modelSelect = document.getElementById('modelSelect');

            if (data.success && data.data) {
                modelSelect.innerHTML = '<option value="">Seleccionar modelo</option>';

                data.data.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = model.name;
                    modelSelect.appendChild(option);
                });

                modelSelect.disabled = false;
            } else {
                modelSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
            }
        } catch (error) {
            console.error('Error loading models:', error);
            document.getElementById('modelSelect').innerHTML = '<option value="">Error al cargar modelos</option>';
        }
    }

    // تحميل قطع الغيار
    async function loadSpareParts(brandId, modelId) {
        try {
            const response = await fetch('pos_interface.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_spare_parts&brand_id=${brandId}&model_id=${modelId}`
            });

            const data = await response.json();

            if (data.success) {
                displaySpareParts(data.data);
            }
        } catch (error) {
            console.error('Error loading spare parts:', error);
            clearSpareParts();
        }
    }

    // عرض قطع الغيار
    function displaySpareParts(parts) {
        const container = document.getElementById('sparePartsContainer');

        if (parts.length === 0) {
            container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-info-circle fs-4"></i>
                        <p class="mt-2">No hay repuestos disponibles para este modelo</p>
                    </div>
                `;
            return;
        }

        let html = '';
        parts.forEach(part => {
            const isSelected = selectedSpareParts.has(part.id);
            const quantity = isSelected ? selectedSpareParts.get(part.id).quantity : 1;

            const categoryColors = {
                'pantalla': 'bg-primary',
                'bateria': 'bg-success',
                'camara': 'bg-info',
                'altavoz': 'bg-warning',
                'conector': 'bg-danger',
                'default': 'bg-secondary'
            };

            const categoryColor = categoryColors[part.category?.toLowerCase()] || categoryColors.default;

            html += `
                    <div class="spare-part-card ${isSelected ? 'selected' : ''}"
                         onclick="toggleSparePart(${part.id}, '${part.part_name}', ${part.total_price}, '${part.category || ''}')">
                        <div class="category-badge badge ${categoryColor}">
                            ${part.category || 'General'}
                        </div>
                        <div class="fw-bold">${part.part_name}</div>
                        <div class="text-success fw-bold fs-5">€${parseFloat(part.total_price).toFixed(2)}</div>
                        <div class="small text-muted">
                            Stock: ${part.stock_quantity} | ${part.stock_status}
                        </div>
                        ${isSelected ? `
                            <div class="quantity-selector" onclick="event.stopPropagation()">
                                <button class="quantity-btn" onclick="changeQuantity(${part.id}, -1)">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="quantity-input" value="${quantity}"
                                       onchange="setQuantity(${part.id}, this.value)" min="1">
                                <button class="quantity-btn" onclick="changeQuantity(${part.id}, 1)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // تبديل اختيار قطعة غيار
    function toggleSparePart(id, name, price, category) {
        if (selectedSpareParts.has(id)) {
            selectedSpareParts.delete(id);
        } else {
            selectedSpareParts.set(id, {
                id: id,
                name: name,
                price: parseFloat(price),
                category: category,
                quantity: 1
            });
        }

        updateSelectedPartsCount();
        updateTotalCost();

        // إعادة عرض قطع الغيار لتحديث الحالة
        const brandId = document.getElementById('brandSelect').value;
        const modelId = document.getElementById('modelSelect').value;
        if (brandId && modelId) {
            loadSpareParts(brandId, modelId);
        }
    }

    // تغيير الكمية
    function changeQuantity(id, change) {
        if (selectedSpareParts.has(id)) {
            const part = selectedSpareParts.get(id);
            part.quantity = Math.max(1, part.quantity + change);
            selectedSpareParts.set(id, part);

            updateSelectedPartsCount();
            updateTotalCost();

            // تحديث العرض
            const brandId = document.getElementById('brandSelect').value;
            const modelId = document.getElementById('modelSelect').value;
            if (brandId && modelId) {
                loadSpareParts(brandId, modelId);
            }
        }
    }

    // تعيين الكمية
    function setQuantity(id, value) {
        const quantity = Math.max(1, parseInt(value) || 1);
        if (selectedSpareParts.has(id)) {
            const part = selectedSpareParts.get(id);
            part.quantity = quantity;
            selectedSpareParts.set(id, part);

            updateSelectedPartsCount();
            updateTotalCost();
        }
    }

    // تحديث عداد القطع المختارة
    function updateSelectedPartsCount() {
        const count = selectedSpareParts.size;
        document.getElementById('selectedPartsCount').textContent = count;
    }

    // مسح قطع الغيار
    function clearSpareParts() {
        selectedSpareParts.clear();
        updateSelectedPartsCount();
        updateTotalCost();

        const container = document.getElementById('sparePartsContainer');
        container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-info-circle fs-4"></i>
                    <p class="mt-2">Selecciona una marca y modelo para ver repuestos compatibles</p>
                </div>
            `;
    }

    // إعداد حساب التكلفة
    function setupCostCalculation() {
        const manualCostInput = document.getElementById('manualCost');
        manualCostInput.addEventListener('input', updateTotalCost);

        // حساب أولي
        updateTotalCost();
    }

    // تحديث التكلفة الإجمالية
    function updateTotalCost() {
        let total = 0;

        // إضافة تكلفة قطع الغيار
        selectedSpareParts.forEach(part => {
            total += part.price * part.quantity;
        });

        // إضافة التكلفة اليدوية
        const manualCost = parseFloat(document.getElementById('manualCost').value) || 0;
        total += manualCost;

        document.getElementById('totalAmount').textContent = `€${total.toFixed(2)}`;
    }

    // تحميل الإصلاحات النشطة
    async function loadActiveRepairs() {
        try {
            const response = await fetch('pos_interface.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_active_repairs'
            });

            const data = await response.json();

            if (data.success) {
                displayActiveRepairs(data.data);
                updateRepairStats(data.data);
            }
        } catch (error) {
            console.error('Error loading active repairs:', error);
        }
    }

    // عرض الإصلاحات النشطة
    function displayActiveRepairs(repairs) {
        const container = document.getElementById('activeRepairsContainer');

        if (repairs.length === 0) {
            container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4"></i>
                        <p class="mt-2">No hay reparaciones activas</p>
                    </div>
                `;
            return;
        }

        let html = '';
        repairs.forEach(repair => {
            const statusClass = `status-${repair.status}`;
            const statusText = {
                'pending': 'Pendiente',
                'in_progress': 'En Proceso',
                'completed': 'Completado'
            }[repair.status] || repair.status;

            const date = new Date(repair.created_at).toLocaleDateString('es-ES', {
                month: 'short',
                day: 'numeric'
            });

            html += `
                    <div class="active-repair-item ${statusClass}" onclick="openRepairDetails(${repair.id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">#${repair.reference}</div>
                                <div class="small text-muted">${repair.customer_name}</div>
                                <div class="small">${repair.device}</div>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-secondary">${statusText}</div>
                                <div class="small text-muted mt-1">${date}</div>
                                <div class="small fw-bold">€${parseFloat(repair.estimated_cost).toFixed(2)}</div>
                            </div>
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // تحديث إحصائيات الإصلاحات
    function updateRepairStats(repairs) {
        const stats = {
            pending: 0,
            in_progress: 0,
            completed: 0
        };

        repairs.forEach(repair => {
            if (stats.hasOwnProperty(repair.status)) {
                stats[repair.status]++;
            }
        });

        document.getElementById('pendingCount').textContent = stats.pending;
        document.getElementById('progressCount').textContent = stats.in_progress;
        document.getElementById('completedCount').textContent = stats.completed;
    }

    // فتح تفاصيل الإصلاح
    function openRepairDetails(repairId) {
        window.open(`pages/repair_details.php?id=${repairId}`, '_blank');
    }

    // إعداد أزرار الحفظ
    function setupSaveButtons() {
        document.getElementById('saveRepairBtn').addEventListener('click', () => saveRepair(false));
        document.getElementById('saveAndPrintBtn').addEventListener('click', () => saveRepair(true));
        document.getElementById('clearFormBtn').addEventListener('click', clearForm);
        document.getElementById('refreshRepairsBtn').addEventListener('click', loadActiveRepairs);
    }

    // حفظ الإصلاح
    async function saveRepair(shouldPrint = false) {
        const form = document.getElementById('quickRepairForm');
        const saveBtn = document.getElementById('saveRepairBtn');
        const printBtn = document.getElementById('saveAndPrintBtn');

        // التحقق من صحة النموذج
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // تعطيل الأزرار
        saveBtn.disabled = true;
        printBtn.disabled = true;
        saveBtn.innerHTML = '<div class="loading"></div> Guardando...';

        try {
            const formData = new FormData(form);
            formData.append('action', 'quick_repair');

            // إضافة قطع الغيار المختارة
            const sparePartsArray = Array.from(selectedSpareParts.values());
            formData.append('selected_parts', JSON.stringify(sparePartsArray));

            const response = await fetch('pos_interface.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // إظهار رسالة نجاح
                showSuccessMessage(`Reparación #${data.data.reference} creada exitosamente`);

                // مسح النموذج
                clearForm();

                // إعادة تحميل الإصلاحات النشطة
                loadActiveRepairs();

                // طباعة إذا طُلب ذلك
                if (shouldPrint) {
                    window.open(`pages/print_ticket.php?id=${data.data.repair_id}`, '_blank');
                }
            } else {
                showErrorMessage(data.message || 'Error al crear la reparación');
            }
        } catch (error) {
            console.error('Error saving repair:', error);
            showErrorMessage('Error de conexión al guardar la reparación');
        } finally {
            // إعادة تفعيل الأزرار
            saveBtn.disabled = false;
            printBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Guardar Reparación';
        }
    }

    // مسح النموذج
    function clearForm() {
        document.getElementById('quickRepairForm').reset();
        document.getElementById('customerSearch').value = '';
        document.getElementById('modelSelect').innerHTML = '<option value="">Primero selecciona una marca</option>';
        document.getElementById('modelSelect').disabled = true;

        selectedSpareParts.clear();
        updateSelectedPartsCount();
        updateTotalCost();
        clearSpareParts();

        // تركيز على حقل البحث
        document.getElementById('customerSearch').focus();
    }

    // إظهار رسالة نجاح
    function showSuccessMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

        document.body.appendChild(alertDiv);

        // إضافة تأثير بصري
        alertDiv.classList.add('success-animation');

        // إزالة تلقائية بعد 5 ثواني
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // إظهار رسالة خطأ
    function showErrorMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

        document.body.appendChild(alertDiv);

        // إزالة تلقائية بعد 7 ثواني
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 7000);
    }

    // اختصارات لوحة المفاتيح
    document.addEventListener('keydown', function(e) {
        // Ctrl+S للحفظ
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveRepair(false);
        }

        // Ctrl+P للحفظ والطباعة
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            saveRepair(true);
        }

        // Escape لمسح النموذج
        if (e.key === 'Escape') {
            clearForm();
        }
    });

    // إضافة تأثيرات صوتية (اختياري)
    function playSuccessSound() {
        // يمكن إضافة صوت نجاح هنا
    }

    function playErrorSound() {
        // يمكن إضافة صوت خطأ هنا
    }

    // Fullscreen functionality
    function toggleFullscreen() {
        const container = document.querySelector('.pos-container');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const fullscreenIcon = document.getElementById('fullscreenIcon');
        const fullscreenText = document.getElementById('fullscreenText');

        if (!document.fullscreenElement) {
            // دخول الشاشة الكاملة
            document.documentElement.requestFullscreen().then(() => {
                container.classList.add('fullscreen-mode');
                fullscreenIcon.className = 'bi bi-fullscreen-exit';
                fullscreenText.textContent = 'Salir de Pantalla Completa';
                fullscreenBtn.classList.remove('btn-outline-info');
                fullscreenBtn.classList.add('btn-warning');

                // إظهار رسالة
                showSuccessMessage('Modo pantalla completa activado. Presiona ESC o el botón para salir.');
            }).catch(err => {
                showErrorMessage('No se pudo activar la pantalla completa');
            });
        } else {
            // خروج من الشاشة الكاملة
            document.exitFullscreen().then(() => {
                container.classList.remove('fullscreen-mode');
                fullscreenIcon.className = 'bi bi-arrows-fullscreen';
                fullscreenText.textContent = 'Pantalla Completa';
                fullscreenBtn.classList.remove('btn-warning');
                fullscreenBtn.classList.add('btn-outline-info');
            });
        }
    }

    // مراقبة تغيير حالة الشاشة الكاملة
    document.addEventListener('fullscreenchange', function() {
        const container = document.querySelector('.pos-container');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const fullscreenIcon = document.getElementById('fullscreenIcon');
        const fullscreenText = document.getElementById('fullscreenText');

        if (!document.fullscreenElement) {
            // خرجنا من الشاشة الكاملة
            container.classList.remove('fullscreen-mode');
            fullscreenIcon.className = 'bi bi-arrows-fullscreen';
            fullscreenText.textContent = 'Pantalla Completa';
            fullscreenBtn.classList.remove('btn-warning');
            fullscreenBtn.classList.add('btn-outline-info');
        }
    });

    /* Success Animation */
    @keyframes success-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .success-animation {
        animation: success-pulse 0.6s ease-in-out;
    }
</script>
</body>
</html>