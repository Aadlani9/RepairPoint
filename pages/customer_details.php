<?php
/**
 * RepairPoint - Detalles del Cliente
 * Ver información completa del cliente y sus facturas
 */

define('SECURE_ACCESS', true);
require_once '../config/config.php';
require_once '../config/database.php';

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

// Obtener ID del cliente
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Obtener información del cliente
$customer = $db->selectOne(
    "SELECT * FROM customers WHERE id = ? AND shop_id = ?",
    [$customer_id, $shop_id]
);

if (!$customer) {
    $_SESSION['error'] = 'Cliente no encontrado';
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Obtener facturas del cliente
$invoices = $db->select(
    "SELECT i.*,
     COUNT(ii.id) as total_items
     FROM invoices i
     LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
     WHERE i.customer_id = ?
     GROUP BY i.id
     ORDER BY i.invoice_date DESC",
    [$customer_id]
);

// Estadísticas del cliente
$stats = $db->selectOne(
    "SELECT
        COUNT(*) as total_invoices,
        COALESCE(SUM(total), 0) as total_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) as paid_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total ELSE 0 END), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'partial' THEN total - paid_amount ELSE 0 END), 0) as partial_pending
    FROM invoices
    WHERE customer_id = ?",
    [$customer_id]
);

$page_title = 'Detalles del Cliente';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'navbar.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?= url('pages/customers.php') ?>" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <h2 class="mb-0">
                        <i class="bi bi-person-circle text-primary"></i>
                        <?= htmlspecialchars($customer['full_name']) ?>
                    </h2>
                </div>
                <div>
                    <a href="<?= url('pages/create_invoice.php?customer_id=' . $customer_id) ?>"
                       class="btn btn-primary">
                        <i class="bi bi-file-earmark-plus"></i> Nueva Factura
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del cliente -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información del Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Nombre Completo</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($customer['full_name']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Teléfono</label>
                            <p class="mb-0">
                                <i class="bi bi-phone text-primary"></i>
                                <a href="tel:<?= $customer['phone'] ?>"><?= htmlspecialchars($customer['phone']) ?></a>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0">
                                <?php if (!empty($customer['email'])): ?>
                                    <i class="bi bi-envelope text-primary"></i>
                                    <a href="mailto:<?= $customer['email'] ?>"><?= htmlspecialchars($customer['email']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Documento</label>
                            <p class="mb-0">
                                <span class="badge bg-secondary"><?= strtoupper($customer['id_type']) ?></span>
                                <?= htmlspecialchars($customer['id_number']) ?>
                            </p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small">Dirección</label>
                            <p class="mb-0">
                                <?= !empty($customer['address']) ? nl2br(htmlspecialchars($customer['address'])) : '<span class="text-muted">No especificada</span>' ?>
                            </p>
                        </div>
                        <?php if (!empty($customer['notes'])): ?>
                            <div class="col-md-12 mb-3">
                                <label class="text-muted small">Notas</label>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($customer['notes'])) ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="text-muted small">Estado</label>
                            <p class="mb-0">
                                <?php if ($customer['status'] === 'active'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Cliente desde</label>
                            <p class="mb-0"><?= date('d/m/Y', strtotime($customer['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="col-md-4">
            <div class="card mb-3 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 small">Total Facturas</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_invoices']) ?></h3>
                </div>
            </div>
            <div class="card mb-3 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 small">Total Pagado</h6>
                    <h3 class="mb-0">€<?= number_format($stats['paid_amount'], 2) ?></h3>
                </div>
            </div>
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-white-50 small">Pendiente de Pago</h6>
                    <h3 class="mb-0">€<?= number_format($stats['pending_amount'] + $stats['partial_pending'], 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Facturas del cliente -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Facturas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N° Factura</th>
                            <th>Fecha</th>
                            <th>Items</th>
                            <th>Subtotal</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Estado Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="text-muted mt-2">No hay facturas registradas para este cliente</p>
                                    <a href="<?= url('pages/create_invoice.php?customer_id=' . $customer_id) ?>"
                                       class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Crear Primera Factura
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $invoice['total_items'] ?> items</span>
                                    </td>
                                    <td>€<?= number_format($invoice['subtotal'], 2) ?></td>
                                    <td>
                                        <small class="text-muted"><?= $invoice['iva_rate'] ?>%</small><br>
                                        €<?= number_format($invoice['iva_amount'], 2) ?>
                                    </td>
                                    <td>
                                        <strong>€<?= number_format($invoice['total'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'pending' => '<span class="badge bg-warning">Pendiente</span>',
                                            'partial' => '<span class="badge bg-info">Parcial</span>',
                                            'paid' => '<span class="badge bg-success">Pagado</span>'
                                        ];
                                        echo $status_badges[$invoice['payment_status']];
                                        ?>
                                        <?php if ($invoice['payment_status'] === 'partial'): ?>
                                            <br><small class="text-muted">Pagado: €<?= number_format($invoice['paid_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('pages/invoice_details.php?id=' . $invoice['id']) ?>"
                                               class="btn btn-info" title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= url('pages/print_invoice_pdf.php?id=' . $invoice['id']) ?>"
                                               class="btn btn-danger" title="Descargar PDF" target="_blank">
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
