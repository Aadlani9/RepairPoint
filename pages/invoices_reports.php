<?php
/**
 * RepairPoint - Reportes de Facturación
 * Sistema de reportes y análisis de facturas
 */

define('SECURE_ACCESS', true);
require_once '../config/config.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('pages/login.php'));
    exit;
}

// Verificar permisos de administrador
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ' . url('pages/403.php'));
    exit;
}

$db = getDB();
$shop_id = $_SESSION['shop_id'];

// Filtros
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : 'all';

// Estadísticas generales
$stats_query = "SELECT
    COUNT(*) as total_invoices,
    COALESCE(SUM(total), 0) as total_amount,
    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) as paid_amount,
    COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total ELSE 0 END), 0) as pending_amount,
    COALESCE(SUM(CASE WHEN payment_status = 'partial' THEN total - paid_amount ELSE 0 END), 0) as partial_pending,
    COALESCE(SUM(subtotal), 0) as total_subtotal,
    COALESCE(SUM(iva_amount), 0) as total_iva,
    COUNT(DISTINCT customer_id) as total_customers
FROM invoices
WHERE shop_id = ? AND invoice_date BETWEEN ? AND ?";

$params = [$shop_id, $date_from, $date_to];
$stats = $db->selectOne($stats_query, $params);

// Facturas por estado
$status_stats = $db->select(
    "SELECT payment_status,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total
     FROM invoices
     WHERE shop_id = ? AND invoice_date BETWEEN ? AND ?
     GROUP BY payment_status",
    $params
);

// Top 10 clientes
$top_customers = $db->select(
    "SELECT c.full_name, c.phone,
            COUNT(i.id) as invoice_count,
            COALESCE(SUM(i.total), 0) as total_amount
     FROM customers c
     JOIN invoices i ON c.id = i.customer_id
     WHERE c.shop_id = ? AND i.invoice_date BETWEEN ? AND ?
     GROUP BY c.id
     ORDER BY total_amount DESC
     LIMIT 10",
    $params
);

// Facturas por método de pago
$payment_methods = $db->select(
    "SELECT payment_method,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total
     FROM invoices
     WHERE shop_id = ? AND invoice_date BETWEEN ? AND ? AND payment_status = 'paid'
     GROUP BY payment_method",
    $params
);

// Últimas facturas
$recent_query = "SELECT i.*, c.full_name as customer_name
                 FROM invoices i
                 JOIN customers c ON i.customer_id = c.id
                 WHERE i.shop_id = ? AND i.invoice_date BETWEEN ? AND ?";

if ($payment_status !== 'all') {
    $recent_query .= " AND i.payment_status = ?";
    $params[] = $payment_status;
}

$recent_query .= " ORDER BY i.invoice_date DESC, i.id DESC LIMIT 50";
$recent_invoices = $db->select($recent_query, $params);

$page_title = 'Reportes de Facturación';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'navbar.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-graph-up text-primary"></i> Reportes de Facturación
            </h2>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado de Pago</label>
                    <select name="payment_status" class="form-select">
                        <option value="all" <?= $payment_status === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="pending" <?= $payment_status === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="partial" <?= $payment_status === 'partial' ? 'selected' : '' ?>>Parcial</option>
                        <option value="paid" <?= $payment_status === 'paid' ? 'selected' : '' ?>>Pagado</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen de Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Total Facturas</h6>
                            <h2 class="mb-0"><?= number_format($stats['total_invoices']) ?></h2>
                        </div>
                        <i class="bi bi-receipt fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Total Cobrado</h6>
                            <h2 class="mb-0">€<?= number_format($stats['paid_amount'], 2) ?></h2>
                        </div>
                        <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Total Pendiente</h6>
                            <h2 class="mb-0">€<?= number_format($stats['pending_amount'] + $stats['partial_pending'], 2) ?></h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 small">Total Clientes</h6>
                            <h2 class="mb-0"><?= number_format($stats['total_customers']) ?></h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desglose de IVA -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-percent"></i> Desglose de IVA</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td><strong>Subtotal (Base Imponible):</strong></td>
                            <td class="text-end">€<?= number_format($stats['total_subtotal'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total IVA:</strong></td>
                            <td class="text-end">€<?= number_format($stats['total_iva'], 2) ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Total Facturado:</strong></td>
                            <td class="text-end"><strong>€<?= number_format($stats['total_amount'], 2) ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Por Estado de Pago -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Por Estado</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <?php
                        $status_labels = [
                            'pending' => ['label' => 'Pendiente', 'class' => 'warning'],
                            'partial' => ['label' => 'Parcial', 'class' => 'info'],
                            'paid' => ['label' => 'Pagado', 'class' => 'success']
                        ];

                        foreach ($status_stats as $stat):
                            $status_info = $status_labels[$stat['payment_status']];
                        ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= $status_info['class'] ?>"><?= $status_info['label'] ?></span>
                                </td>
                                <td class="text-center"><?= $stat['count'] ?> facturas</td>
                                <td class="text-end"><strong>€<?= number_format($stat['total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Por Método de Pago -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Métodos de Pago</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payment_methods)): ?>
                        <p class="text-muted text-center mb-0">No hay pagos registrados</p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <?php foreach ($payment_methods as $method): ?>
                                <tr>
                                    <td><strong><?= ucfirst($method['payment_method']) ?></strong></td>
                                    <td class="text-center"><?= $method['count'] ?></td>
                                    <td class="text-end">€<?= number_format($method['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 Clientes -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 10 Clientes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th class="text-center">N° Facturas</th>
                            <th class="text-end">Total Facturado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_customers)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay datos disponibles</td>
                            </tr>
                        <?php else: ?>
                            <?php $position = 1; foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <?php if ($position <= 3): ?>
                                            <span class="badge bg-warning"><?= $position ?></span>
                                        <?php else: ?>
                                            <?= $position ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($customer['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($customer['phone']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $customer['invoice_count'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong>€<?= number_format($customer['total_amount'], 2) ?></strong>
                                    </td>
                                </tr>
                                <?php $position++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Listado de Facturas -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Últimas Facturas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N° Factura</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_invoices)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No hay facturas en este período</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_invoices as $invoice): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?></td>
                                    <td><?= htmlspecialchars($invoice['customer_name']) ?></td>
                                    <td class="text-end"><strong>€<?= number_format($invoice['total'], 2) ?></strong></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'pending' => '<span class="badge bg-warning">Pendiente</span>',
                                            'partial' => '<span class="badge bg-info">Parcial</span>',
                                            'paid' => '<span class="badge bg-success">Pagado</span>'
                                        ];
                                        echo $status_badges[$invoice['payment_status']];
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('pages/invoice_details.php?id=' . $invoice['id']) ?>"
                                               class="btn btn-info" title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= url('pages/print_invoice_pdf.php?id=' . $invoice['id']) ?>"
                                               class="btn btn-danger" title="PDF" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        </div>
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

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
