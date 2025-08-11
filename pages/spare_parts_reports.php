<?php
/**
 * RepairPoint - تقارير قطع الغيار المالية
 * صفحة التقارير والتحليلات المالية المتقدمة لقطع الغيار (Admin فقط)
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

// التحقق من صلاحيات عرض التقارير المالية
requireProfitReportsAccess();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];
$page_title = 'Informes Financieros - Repuestos';

// الحصول على فترة التقرير من المعاملات
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // بداية الشهر الحالي
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // اليوم الحالي
$category = $_GET['category'] ?? '';
$supplier = $_GET['supplier'] ?? '';

// التحقق من صحة التواريخ
$date_from = date('Y-m-d', strtotime($date_from));
$date_to = date('Y-m-d', strtotime($date_to));

$db = getDB();

// ==================================================
// الحصول على البيانات المالية الأساسية
// ==================================================

/**
 * حساب الإحصائيات المالية الأساسية
 */
function getFinancialOverview($shop_id, $date_from, $date_to, $category = '', $supplier = '') {
    $db = getDB();

    // تعديل للعمل مع جميع الإصلاحات التي لها قطع غيار (ليس فقط المُسلَّمة)
    $where_conditions = ["r.shop_id = ?"];
    $params = [$shop_id];

    // إضافة فلتر التاريخ حسب توفر البيانات
    if (!empty($date_from) && !empty($date_to)) {
        $where_conditions[] = "DATE(COALESCE(r.delivered_at, r.completed_at, r.created_at)) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    }

    if (!empty($category)) {
        $where_conditions[] = "sp.category = ?";
        $params[] = $category;
    }

    if (!empty($supplier)) {
        $where_conditions[] = "sp.supplier_name = ?";
        $params[] = $supplier;
    }

    $query = "SELECT 
                COUNT(DISTINCT rsp.id) as total_parts_sold,
                SUM(rsp.quantity) as total_quantity_sold,
                SUM(rsp.total_price) as total_revenue,
                SUM(rsp.quantity * COALESCE(rsp.unit_cost_price, 0)) as total_cost_price,
                SUM(rsp.quantity * COALESCE(rsp.unit_labor_cost, 0)) as total_labor_cost,
                COUNT(DISTINCT r.id) as repairs_with_parts
              FROM repair_spare_parts rsp
              JOIN spare_parts sp ON rsp.spare_part_id = sp.id
              JOIN repairs r ON rsp.repair_id = r.id
              WHERE " . implode(' AND ', $where_conditions);

    $result = $db->selectOne($query, $params);

    if ($result) {
        $total_costs = $result['total_cost_price'] + $result['total_labor_cost'];
        $gross_profit = $result['total_revenue'] - $total_costs;
        $profit_margin = $result['total_revenue'] > 0 ? ($gross_profit / $result['total_revenue']) * 100 : 0;

        return [
            'total_parts_sold' => (int)$result['total_parts_sold'],
            'total_quantity_sold' => (int)$result['total_quantity_sold'],
            'total_revenue' => (float)$result['total_revenue'],
            'total_cost_price' => (float)$result['total_cost_price'],
            'total_labor_cost' => (float)$result['total_labor_cost'],
            'total_costs' => $total_costs,
            'gross_profit' => $gross_profit,
            'profit_margin' => round($profit_margin, 2),
            'repairs_with_parts' => (int)$result['repairs_with_parts']
        ];
    }

    return [
        'total_parts_sold' => 0,
        'total_quantity_sold' => 0,
        'total_revenue' => 0,
        'total_cost_price' => 0,
        'total_labor_cost' => 0,
        'total_costs' => 0,
        'gross_profit' => 0,
        'profit_margin' => 0,
        'repairs_with_parts' => 0
    ];
}

/**
 * الحصول على أفضل قطع الغيار مبيعاً
 */
function getTopSellingParts($shop_id, $date_from, $date_to, $limit = 10) {
    $db = getDB();

    $query = "SELECT 
                sp.id,
                sp.part_name,
                sp.part_code,
                sp.category,
                sp.total_price,
                SUM(rsp.quantity) as total_sold,
                SUM(rsp.total_price) as revenue,
                SUM(rsp.quantity * COALESCE(rsp.unit_cost_price, 0)) as cost_price,
                SUM(rsp.quantity * COALESCE(rsp.unit_labor_cost, 0)) as labor_cost,
                COUNT(DISTINCT rsp.repair_id) as used_in_repairs
              FROM repair_spare_parts rsp
              JOIN spare_parts sp ON rsp.spare_part_id = sp.id
              JOIN repairs r ON rsp.repair_id = r.id
              WHERE r.shop_id = ? 
              AND DATE(COALESCE(r.delivered_at, r.completed_at, r.created_at)) BETWEEN ? AND ?
              GROUP BY sp.id
              ORDER BY total_sold DESC, revenue DESC
              LIMIT ?";

    $results = $db->select($query, [$shop_id, $date_from, $date_to, $limit]);

    foreach ($results as &$result) {
        $total_cost = $result['cost_price'] + $result['labor_cost'];
        $profit = $result['revenue'] - $total_cost;
        $margin = $result['revenue'] > 0 ? ($profit / $result['revenue']) * 100 : 0;

        $result['total_cost'] = $total_cost;
        $result['profit'] = $profit;
        $result['margin'] = round($margin, 2);
    }

    return $results;
}

/**
 * الحصول على أكثر قطع الغيار ربحية
 */
function getMostProfitableParts($shop_id, $date_from, $date_to, $limit = 10) {
    $db = getDB();

    $query = "SELECT 
                sp.id,
                sp.part_name,
                sp.part_code,
                sp.category,
                sp.total_price,
                SUM(rsp.quantity) as total_sold,
                SUM(rsp.total_price) as revenue,
                SUM(rsp.quantity * COALESCE(rsp.unit_cost_price, 0)) as cost_price,
                SUM(rsp.quantity * COALESCE(rsp.unit_labor_cost, 0)) as labor_cost
              FROM repair_spare_parts rsp
              JOIN spare_parts sp ON rsp.spare_part_id = sp.id
              JOIN repairs r ON rsp.repair_id = r.id
              WHERE r.shop_id = ? 
              AND DATE(COALESCE(r.delivered_at, r.completed_at, r.created_at)) BETWEEN ? AND ?
              GROUP BY sp.id
              HAVING revenue > 0
              ORDER BY (revenue - cost_price - labor_cost) DESC
              LIMIT ?";

    $results = $db->select($query, [$shop_id, $date_from, $date_to, $limit]);

    foreach ($results as &$result) {
        $total_cost = $result['cost_price'] + $result['labor_cost'];
        $profit = $result['revenue'] - $total_cost;
        $margin = $result['revenue'] > 0 ? ($profit / $result['revenue']) * 100 : 0;

        $result['total_cost'] = $total_cost;
        $result['profit'] = $profit;
        $result['margin'] = round($margin, 2);
    }

    return $results;
}

/**
 * تحليل المبيعات حسب الفئة
 */
function getSalesByCategory($shop_id, $date_from, $date_to) {
    $db = getDB();

    $query = "SELECT 
                COALESCE(sp.category, 'Sin categoría') as category,
                COUNT(DISTINCT sp.id) as unique_parts,
                SUM(rsp.quantity) as total_sold,
                SUM(rsp.total_price) as revenue,
                SUM(rsp.quantity * COALESCE(rsp.unit_cost_price, 0)) as cost_price,
                SUM(rsp.quantity * COALESCE(rsp.unit_labor_cost, 0)) as labor_cost
              FROM repair_spare_parts rsp
              JOIN spare_parts sp ON rsp.spare_part_id = sp.id
              JOIN repairs r ON rsp.repair_id = r.id
              WHERE r.shop_id = ? 
              AND DATE(COALESCE(r.delivered_at, r.completed_at, r.created_at)) BETWEEN ? AND ?
              GROUP BY sp.category
              ORDER BY revenue DESC";

    $results = $db->select($query, [$shop_id, $date_from, $date_to]);

    foreach ($results as &$result) {
        $total_cost = $result['cost_price'] + $result['labor_cost'];
        $profit = $result['revenue'] - $total_cost;
        $margin = $result['revenue'] > 0 ? ($profit / $result['revenue']) * 100 : 0;

        $result['total_cost'] = $total_cost;
        $result['profit'] = $profit;
        $result['margin'] = round($margin, 2);
    }

    return $results;
}

/**
 * تحليل المبيعات الزمني (يومي/أسبوعي/شهري)
 */
function getSalesTrend($shop_id, $date_from, $date_to, $interval = 'daily') {
    $db = getDB();

    $date_format = match($interval) {
        'daily' => '%Y-%m-%d',
        'weekly' => '%Y-%u',
        'monthly' => '%Y-%m',
        default => '%Y-%m-%d'
    };

    $query = "SELECT 
                DATE_FORMAT(r.delivered_at, ?) as period,
                COUNT(DISTINCT rsp.id) as parts_sold,
                SUM(rsp.quantity) as quantity_sold,
                SUM(rsp.total_price) as revenue,
                SUM(rsp.quantity * COALESCE(rsp.unit_cost_price, 0) + rsp.quantity * COALESCE(rsp.unit_labor_cost, 0)) as total_cost
              FROM repair_spare_parts rsp
              JOIN repairs r ON rsp.repair_id = r.id
              WHERE r.shop_id = ? 
              AND DATE(COALESCE(r.delivered_at, r.completed_at, r.created_at)) BETWEEN ? AND ?
              GROUP BY period
              ORDER BY period ASC";

    $results = $db->select($query, [$date_format, $shop_id, $date_from, $date_to]);

    foreach ($results as &$result) {
        $result['profit'] = $result['revenue'] - $result['total_cost'];
    }

    return $results;
}

/**
 * معلومات المخزون الحالي
 */
function getCurrentStockInfo($shop_id) {
    $db = getDB();

    $query = "SELECT 
                COUNT(*) as total_parts,
                SUM(stock_quantity * COALESCE(cost_price, 0)) as stock_value_cost,
                SUM(stock_quantity * total_price) as stock_value_retail,
                SUM(CASE WHEN stock_status = 'available' THEN 1 ELSE 0 END) as available_parts,
                SUM(CASE WHEN stock_status = 'order_required' THEN 1 ELSE 0 END) as low_stock_parts,
                SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_parts,
                SUM(stock_quantity) as total_quantity
              FROM spare_parts 
              WHERE shop_id = ? AND is_active = TRUE";

    return $db->selectOne($query, [$shop_id]);
}

// الحصول على البيانات
$financial_overview = getFinancialOverview($shop_id, $date_from, $date_to, $category, $supplier);
$top_selling = getTopSellingParts($shop_id, $date_from, $date_to);
$most_profitable = getMostProfitableParts($shop_id, $date_from, $date_to);
$sales_by_category = getSalesByCategory($shop_id, $date_from, $date_to);
$sales_trend = getSalesTrend($shop_id, $date_from, $date_to, 'daily');
$stock_info = getCurrentStockInfo($shop_id);

// الحصول على الفئات والموردين للفلاتر
$categories = $db->select("SELECT DISTINCT category FROM spare_parts WHERE shop_id = ? AND category IS NOT NULL ORDER BY category", [$shop_id]);
$suppliers = $db->select("SELECT DISTINCT supplier_name FROM spare_parts WHERE shop_id = ? AND supplier_name IS NOT NULL ORDER BY supplier_name", [$shop_id]);

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
                            <i class="bi bi-graph-up-arrow text-success"></i>
                            Informes Financieros - Repuestos
                        </h1>
                        <p class="text-muted mb-0">
                            Análisis detallado de rentabilidad y ventas de repuestos
                        </p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" onclick="exportReport('pdf')">
                            <i class="bi bi-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-outline-success" onclick="exportReport('excel')">
                            <i class="bi bi-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-primary" onclick="printReport()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلاتر التقرير -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-funnel"></i>
                            Filtros del Informe
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filtersForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Fecha desde</label>
                                    <input type="date" class="form-control" name="date_from" value="<?= $date_from ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha hasta</label>
                                    <input type="date" class="form-control" name="date_to" value="<?= $date_to ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" name="category">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['category']) ?>"
                                                <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                                <?= formatSparePartCategory($cat['category']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Proveedor</label>
                                    <select class="form-select" name="supplier">
                                        <option value="">Todos los proveedores</option>
                                        <?php foreach ($suppliers as $sup): ?>
                                            <option value="<?= htmlspecialchars($sup['supplier_name']) ?>"
                                                <?= $supplier === $sup['supplier_name'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sup['supplier_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Generar Informe
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                        <i class="bi bi-arrow-clockwise"></i> Restablecer
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- المؤشرات المالية الرئيسية -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-gradient rounded-3 p-3">
                                    <i class="bi bi-currency-euro text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Ingresos Totales</h6>
                                <h3 class="mb-0 text-primary">€<?= number_format($financial_overview['total_revenue'], 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-gradient rounded-3 p-3">
                                    <i class="bi bi-graph-up text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Beneficio Bruto</h6>
                                <h3 class="mb-0 text-success">€<?= number_format($financial_overview['gross_profit'], 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-gradient rounded-3 p-3">
                                    <i class="bi bi-percent text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Margen de Beneficio</h6>
                                <h3 class="mb-0 text-info"><?= $financial_overview['profit_margin'] ?>%</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-gradient rounded-3 p-3">
                                    <i class="bi bi-gear text-white fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Repuestos Vendidos</h6>
                                <h3 class="mb-0 text-warning"><?= number_format($financial_overview['total_quantity_sold']) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية والتحليلات -->
        <div class="row mb-4">
            <!-- اتجاه المبيعات -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up"></i>
                            Tendencia de Ventas
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesTrendChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- توزيع المبيعات حسب الفئة -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pie-chart"></i>
                            Ventas por Categoría
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- الجداول التحليلية -->
        <div class="row">
            <!-- أفضل المنتجات مبيعاً -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-trophy"></i>
                            Repuestos Más Vendidos
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Repuesto</th>
                                    <th>Vendidos</th>
                                    <th>Ingresos</th>
                                    <th>Margen</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($top_selling)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No hay datos de ventas en el período seleccionado
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($top_selling as $index => $part): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($part['part_name']) ?></strong>
                                                    <?php if ($part['part_code']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($part['part_code']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= $part['total_sold'] ?></span>
                                            </td>
                                            <td>
                                                <strong class="text-success">€<?= number_format($part['revenue'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $margin_class = $part['margin'] >= 50 ? 'success' : ($part['margin'] >= 25 ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?= $margin_class ?>"><?= $part['margin'] ?>%</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أكثر المنتجات ربحية -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-cash-coin"></i>
                            Repuestos Más Rentables
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Repuesto</th>
                                    <th>Vendidos</th>
                                    <th>Beneficio</th>
                                    <th>Margen</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($most_profitable)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No hay datos de rentabilidad en el período seleccionado
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($most_profitable as $part): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($part['part_name']) ?></strong>
                                                    <?php if ($part['part_code']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($part['part_code']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $part['total_sold'] ?></span>
                                            </td>
                                            <td>
                                                <strong class="text-success">€<?= number_format($part['profit'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $margin_class = $part['margin'] >= 50 ? 'success' : ($part['margin'] >= 25 ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?= $margin_class ?>"><?= $part['margin'] ?>%</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تحليل المخزون -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-boxes"></i>
                            Análisis de Inventario
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3">
                                    <h4 class="text-primary"><?= number_format($stock_info['total_parts'] ?? 0) ?></h4>
                                    <p class="text-muted mb-0">Total Repuestos</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3">
                                    <h4 class="text-success">€<?= number_format($stock_info['stock_value_retail'] ?? 0, 2) ?></h4>
                                    <p class="text-muted mb-0">Valor al Retail</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3">
                                    <h4 class="text-info">€<?= number_format($stock_info['stock_value_cost'] ?? 0, 2) ?></h4>
                                    <p class="text-muted mb-0">Valor de Coste</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3">
                                    <h4 class="text-warning"><?= number_format($stock_info['low_stock_parts'] ?? 0) ?></h4>
                                    <p class="text-muted mb-0">Stock Bajo</p>
                                </div>
                            </div>
                        </div>

                        <!-- تفاصيل المخزون -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Categoría</th>
                                            <th>Repuestos Únicos</th>
                                            <th>Cantidad Vendida</th>
                                            <th>Ingresos</th>
                                            <th>Beneficio</th>
                                            <th>Margen</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($sales_by_category)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">
                                                    No hay datos de ventas por categoría
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($sales_by_category as $cat): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= formatSparePartCategory($cat['category']) ?></strong>
                                                    </td>
                                                    <td><?= number_format($cat['unique_parts']) ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?= number_format($cat['total_sold']) ?></span>
                                                    </td>
                                                    <td>
                                                        <strong class="text-success">€<?= number_format($cat['revenue'], 2) ?></strong>
                                                    </td>
                                                    <td>
                                                        <strong class="text-info">€<?= number_format($cat['profit'], 2) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $margin_class = $cat['margin'] >= 50 ? 'success' : ($cat['margin'] >= 25 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?= $margin_class ?>"><?= $cat['margin'] ?>%</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript للرسوم البيانية والتفاعلات -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ==================================================
            // بيانات الرسوم البيانية
            // ==================================================

            // بيانات اتجاه المبيعات
            const salesTrendData = <?= json_encode($sales_trend) ?>;

            // بيانات المبيعات حسب الفئة
            const categoryData = <?= json_encode($sales_by_category) ?>;

            // ==================================================
            // رسم بياني لاتجاه المبيعات
            // ==================================================

            const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');

            const salesTrendChart = new Chart(salesTrendCtx, {
                type: 'line',
                data: {
                    labels: salesTrendData.map(item => {
                        const date = new Date(item.period);
                        return date.toLocaleDateString('es-ES', {
                            month: 'short',
                            day: 'numeric'
                        });
                    }),
                    datasets: [
                        {
                            label: 'Ingresos (€)',
                            data: salesTrendData.map(item => parseFloat(item.revenue)),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.1,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Beneficio (€)',
                            data: salesTrendData.map(item => parseFloat(item.profit)),
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.1,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Cantidad Vendida',
                            data: salesTrendData.map(item => parseInt(item.quantity_sold)),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Fecha'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Euros (€)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toFixed(2);
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Cantidad'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tendencia de Ventas de Repuestos'
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.datasetIndex < 2) {
                                        label += '€' + context.parsed.y.toFixed(2);
                                    } else {
                                        label += context.parsed.y + ' unidades';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // ==================================================
            // رسم بياني دائري للفئات
            // ==================================================

            const categoryCtx = document.getElementById('categoryChart').getContext('2d');

            // ألوان مختلفة للفئات
            const categoryColors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
            ];

            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(item => formatCategoryName(item.category)),
                    datasets: [{
                        data: categoryData.map(item => parseFloat(item.revenue)),
                        backgroundColor: categoryColors.slice(0, categoryData.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': €' + context.parsed.toFixed(2) + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });

            // ==================================================
            // دوال المساعدة
            // ==================================================

            function formatCategoryName(category) {
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

                return categories[category?.toLowerCase()] || category || 'Sin categoría';
            }

        });

        // ==================================================
        // دوال التصدير والطباعة
        // ==================================================

        function exportReport(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);

            if (format === 'pdf') {
                // فتح نافذة جديدة للطباعة كـ PDF
                window.open(`spare_parts_reports_export.php?${params.toString()}`, '_blank');
            } else if (format === 'excel') {
                // تحميل ملف Excel
                window.location.href = `spare_parts_reports_export.php?${params.toString()}`;
            }
        }

        function printReport() {
            // إخفاء العناصر غير المطلوبة للطباعة
            const hideElements = document.querySelectorAll('.btn, .card-header .bi, nav, .breadcrumb');
            hideElements.forEach(el => el.style.display = 'none');

            // طباعة الصفحة
            window.print();

            // إعادة إظهار العناصر
            hideElements.forEach(el => el.style.display = '');
        }

        function resetFilters() {
            // إعادة تعيين جميع الفلاتر
            document.querySelector('input[name="date_from"]').value = '<?= date('Y-m-01') ?>';
            document.querySelector('input[name="date_to"]').value = '<?= date('Y-m-d') ?>';
            document.querySelector('select[name="category"]').value = '';
            document.querySelector('select[name="supplier"]').value = '';

            // إرسال النموذج
            document.getElementById('filtersForm').submit();
        }

        // ==================================================
        // تحديث البيانات التلقائي (اختياري)
        // ==================================================

        function refreshData() {
            if (typeof Utils !== 'undefined') {
                Utils.showNotification('Actualizando datos...', 'info');
            }

            // إعادة تحميل الصفحة مع الفلاتر الحالية
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // تحديث تلقائي كل 5 دقائق
        setInterval(refreshData, 300000);

        // ==================================================
        // تفاعلات إضافية
        // ==================================================

        // تفعيل tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // ربط الفلاتر بالتحديث التلقائي
        document.getElementById('filtersForm').addEventListener('change', function() {
            // تأخير قصير قبل إرسال النموذج للتحديث السلس
            setTimeout(() => {
                this.submit();
            }, 500);
        });

        // اختصارات لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // Ctrl+P للطباعة
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printReport();
            }

            // Ctrl+E لتصدير Excel
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportReport('excel');
            }

            // F5 لتحديث البيانات
            if (e.key === 'F5') {
                e.preventDefault();
                refreshData();
            }
        });

    </script>

    <!-- CSS إضافي للتقارير -->
    <style>
        /* ==================================================
           تنسيقات خاصة بصفحة التقارير
           ================================================== */

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            border-radius: 1rem 1rem 0 0 !important;
        }

        .bg-gradient {
            background: linear-gradient(45deg, var(--bs-primary), var(--bs-primary-dark)) !important;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
            transform: translateX(2px);
            transition: all 0.2s ease;
        }

        /* تحسين البطاقات المالية */
        .card-body .d-flex {
            transition: all 0.3s ease;
        }

        .card-body:hover .d-flex {
            transform: scale(1.02);
        }

        /* تنسيق الرسوم البيانية */
        canvas {
            border-radius: 0.5rem;
        }

        /* تحسين الشارات */
        .badge {
            font-size: 0.75rem;
            padding: 0.375em 0.75em;
            border-radius: 0.5rem;
        }

        /* تنسيق خاص للطباعة */
        @media print {
            .btn, .card-header .bi, nav, .breadcrumb {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
                break-inside: avoid;
            }

            .row {
                break-inside: avoid;
            }

            body {
                font-size: 12px;
            }

            .table {
                font-size: 11px;
            }

            .card-title {
                font-size: 14px;
                font-weight: bold;
            }
        }

        /* تحسين الاستجابة */
        @media (max-width: 768px) {
            .card-body .d-flex {
                flex-direction: column;
                text-align: center;
            }

            .flex-shrink-0 {
                margin-bottom: 1rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 0.25em 0.5em;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .card-body {
                padding: 1rem;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-group .btn {
                margin-bottom: 0.25rem;
            }
        }

        /* تأثيرات حركية للمؤشرات */
        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-body h3 {
            animation: countUp 0.6s ease-out;
        }

        /* تحسين تدرج الألوان */
        .bg-primary.bg-gradient {
            background: linear-gradient(45deg, #0d6efd, #0b5ed7) !important;
        }

        .bg-success.bg-gradient {
            background: linear-gradient(45deg, #198754, #157347) !important;
        }

        .bg-info.bg-gradient {
            background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
        }

        .bg-warning.bg-gradient {
            background: linear-gradient(45deg, #ffc107, #e0a800) !important;
        }

        /* تحسين المظهر العام */
        .fs-4 {
            font-size: 1.5rem !important;
        }

        .rounded-3 {
            border-radius: 0.75rem !important;
        }

        /* تحسين الجداول */
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table-light {
            background-color: #f8f9fa;
        }

        /* تحسين النماذج */
        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        /* تحسين الأزرار */
        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-outline-primary:hover,
        .btn-outline-success:hover,
        .btn-outline-secondary:hover {
            transform: translateY(-1px);
        }

        /* تحسين المسافات */
        .mb-4 {
            margin-bottom: 2rem !important;
        }

        .p-3 {
            padding: 1.5rem !important;
        }

        /* تحسين النصوص */
        .text-muted {
            color: #6c757d !important;
            font-size: 0.875rem;
        }

        .text-primary,
        .text-success,
        .text-info,
        .text-warning {
            font-weight: 600;
        }

        /* تأثيرات خاصة للبيانات المهمة */
        .card-body h3 {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* تحسين العناوين */
        .card-title {
            font-weight: 600;
            color: #495057;
        }

        /* تحسين الأيقونات */
        .bi {
            vertical-align: -0.125em;
        }

        /* تحسين الحدود */
        .border-0 {
            border: 0 !important;
        }

        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
    </style>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>