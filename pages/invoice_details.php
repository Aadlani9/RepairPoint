<?php
/**
 * RepairPoint - Detalles de la Factura
 * Ver información completa de una factura
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

// Obtener ID de la factura
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Procesar actualización de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $payment_status = $_POST['payment_status'];
    $paid_amount = floatval($_POST['paid_amount']);
    $payment_method = $_POST['payment_method'];
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

    $query = "UPDATE invoices SET payment_status = ?, paid_amount = ?, payment_method = ?, payment_date = ? WHERE id = ? AND shop_id = ?";
    $result = $db->update($query, [$payment_status, $paid_amount, $payment_method, $payment_date, $invoice_id, $shop_id]);

    if ($result !== false) {
        $_SESSION['success'] = 'Estado de pago actualizado';
    } else {
        $_SESSION['error'] = 'Error al actualizar el pago';
    }
    header('Location: ' . url('pages/invoice_details.php?id=' . $invoice_id));
    exit;
}

// Obtener información de la factura con JOIN
$invoice = $db->selectOne(
    "SELECT i.*,
            c.full_name as customer_name,
            c.phone as customer_phone,
            c.email as customer_email,
            c.id_type,
            c.id_number,
            c.address as customer_address,
            s.name as shop_name,
            s.phone1 as shop_phone,
            s.email as shop_email,
            s.address as shop_address,
            s.logo as shop_logo,
            u.name as created_by_name
     FROM invoices i
     JOIN customers c ON i.customer_id = c.id
     JOIN shops s ON i.shop_id = s.id
     JOIN users u ON i.created_by = u.id
     WHERE i.id = ? AND i.shop_id = ?",
    [$invoice_id, $shop_id]
);

if (!$invoice) {
    $_SESSION['error'] = 'Factura no encontrada';
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Obtener items de la factura
$items = $db->select(
    "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id",
    [$invoice_id]
);

$page_title = 'Factura ' . $invoice['invoice_number'];
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?= url('pages/customer_details.php?id=' . $invoice['customer_id']) ?>" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="bi bi-arrow-left"></i> Volver al Cliente
                    </a>
                    <h2 class="mb-0">
                        <i class="bi bi-receipt text-primary"></i>
                        Factura <?= htmlspecialchars($invoice['invoice_number']) ?>
                    </h2>
                </div>
                <div>
                    <a href="<?= url('pages/edit_invoice.php?id=' . $invoice_id) ?>"
                       class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="<?= url('pages/print_invoice_pdf.php?id=' . $invoice_id) ?>"
                       class="btn btn-info" target="_blank">
                        <i class="bi bi-printer"></i> Imprimir
                    </a>
                    <a href="<?= url('pages/download_invoice_pdf.php?id=' . $invoice_id) ?>"
                       class="btn btn-danger">
                        <i class="bi bi-file-pdf"></i> Descargar PDF
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="bi bi-cash"></i> Actualizar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-x-circle"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Información de la factura -->
        <div class="col-md-8">
            <!-- Información del cliente y empresa -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary">Cliente</h5>
                            <p class="mb-1"><strong><?= htmlspecialchars($invoice['customer_name']) ?></strong></p>
                            <p class="mb-1">
                                <span class="badge bg-secondary"><?= strtoupper($invoice['id_type']) ?></span>
                                <?= htmlspecialchars($invoice['id_number']) ?>
                            </p>
                            <p class="mb-1"><i class="bi bi-phone"></i> <?= htmlspecialchars($invoice['customer_phone']) ?></p>
                            <?php if (!empty($invoice['customer_email'])): ?>
                                <p class="mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($invoice['customer_email']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($invoice['customer_address'])): ?>
                                <p class="mb-0"><i class="bi bi-geo-alt"></i> <?= nl2br(htmlspecialchars($invoice['customer_address'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5 class="text-primary"><?= htmlspecialchars($invoice['shop_name']) ?></h5>
                            <?php if (!empty($invoice['shop_phone'])): ?>
                                <p class="mb-1"><i class="bi bi-phone"></i> <?= htmlspecialchars($invoice['shop_phone']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($invoice['shop_email'])): ?>
                                <p class="mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($invoice['shop_email']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($invoice['shop_address'])): ?>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($invoice['shop_address'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items de la factura -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Detalles de la Factura</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>IMEI</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                                        <td>
                                            <?php
                                            $type_badges = [
                                                'service' => '<span class="badge bg-info">Servicio</span>',
                                                'product' => '<span class="badge bg-success">Producto</span>',
                                                'spare_part' => '<span class="badge bg-warning">Repuesto</span>'
                                            ];
                                            echo $type_badges[$item['item_type']];
                                            ?>
                                        </td>
                                        <td>
                                            <?= !empty($item['imei']) ? '<code>' . htmlspecialchars($item['imei']) . '</code>' : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                        <td class="text-end">€<?= number_format($item['unit_price'], 2) ?></td>
                                        <td class="text-end"><strong>€<?= number_format($item['subtotal'], 2) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong>€<?= number_format($invoice['subtotal'], 2) ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>IVA (<?= $invoice['iva_rate'] ?>%):</strong></td>
                                    <td class="text-end"><strong>€<?= number_format($invoice['iva_amount'], 2) ?></strong></td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="5" class="text-end"><h5 class="mb-0">TOTAL:</h5></td>
                                    <td class="text-end"><h4 class="mb-0">€<?= number_format($invoice['total'], 2) ?></h4></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if (!empty($invoice['notes'])): ?>
                        <div class="alert alert-info mt-3">
                            <strong><i class="bi bi-sticky"></i> Notas:</strong><br>
                            <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información lateral -->
        <div class="col-md-4">
            <!-- Estado de pago -->
            <div class="card mb-3">
                <div class="card-header
                    <?php
                    echo $invoice['payment_status'] === 'paid' ? 'bg-success' :
                        ($invoice['payment_status'] === 'partial' ? 'bg-info' : 'bg-warning');
                    ?> text-white">
                    <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Estado de Pago</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Estado:</strong>
                        <?php
                        $status_badges = [
                            'pending' => '<span class="badge bg-warning">Pendiente</span>',
                            'partial' => '<span class="badge bg-info">Parcial</span>',
                            'paid' => '<span class="badge bg-success">Pagado</span>'
                        ];
                        echo $status_badges[$invoice['payment_status']];
                        ?>
                    </p>
                    <?php if ($invoice['payment_status'] === 'paid'): ?>
                        <p class="mb-2"><strong>Fecha de Pago:</strong><br><?= date('d/m/Y', strtotime($invoice['payment_date'])) ?></p>
                        <p class="mb-0"><strong>Método:</strong><br><?= ucfirst($invoice['payment_method']) ?></p>
                    <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                        <p class="mb-2"><strong>Pagado:</strong> €<?= number_format($invoice['paid_amount'], 2) ?></p>
                        <p class="mb-0"><strong>Pendiente:</strong> <span class="text-danger">€<?= number_format($invoice['total'] - $invoice['paid_amount'], 2) ?></span></p>
                    <?php else: ?>
                        <p class="mb-0"><strong>Monto Pendiente:</strong><br><span class="text-danger fs-4">€<?= number_format($invoice['total'], 2) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Fecha Factura:</strong><br><?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?></p>
                    <?php if (!empty($invoice['due_date'])): ?>
                        <p class="mb-2"><strong>Fecha Vencimiento:</strong><br><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></p>
                    <?php endif; ?>
                    <p class="mb-2"><strong>Creado por:</strong><br><?= htmlspecialchars($invoice['created_by_name']) ?></p>
                    <p class="mb-0"><strong>Fecha Creación:</strong><br><?= date('d/m/Y H:i', strtotime($invoice['created_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Actualizar Pago -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-cash"></i> Actualizar Estado de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_payment">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Estado de Pago</label>
                        <select name="payment_status" id="payment_status" class="form-select" onchange="updatePaymentFields()">
                            <option value="pending" <?= $invoice['payment_status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="partial" <?= $invoice['payment_status'] === 'partial' ? 'selected' : '' ?>>Pago Parcial</option>
                            <option value="paid" <?= $invoice['payment_status'] === 'paid' ? 'selected' : '' ?>>Pagado</option>
                        </select>
                    </div>
                    <div class="mb-3" id="paid_amount_field">
                        <label class="form-label">Monto Pagado</label>
                        <input type="number" name="paid_amount" class="form-control" step="0.01"
                               value="<?= $invoice['paid_amount'] ?>" max="<?= $invoice['total'] ?>">
                        <small class="text-muted">Total: €<?= number_format($invoice['total'], 2) ?></small>
                    </div>
                    <div class="mb-3" id="payment_method_field">
                        <label class="form-label">Método de Pago</label>
                        <select name="payment_method" class="form-select">
                            <option value="efectivo" <?= $invoice['payment_method'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                            <option value="tarjeta" <?= $invoice['payment_method'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                            <option value="transferencia" <?= $invoice['payment_method'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                            <option value="bizum" <?= $invoice['payment_method'] === 'bizum' ? 'selected' : '' ?>>Bizum</option>
                        </select>
                    </div>
                    <div class="mb-3" id="payment_date_field">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="date" name="payment_date" class="form-control"
                               value="<?= $invoice['payment_date'] ?? date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updatePaymentFields() {
    const status = document.getElementById('payment_status').value;
    const paidAmountField = document.getElementById('paid_amount_field');
    const paymentMethodField = document.getElementById('payment_method_field');
    const paymentDateField = document.getElementById('payment_date_field');

    if (status === 'pending') {
        paidAmountField.style.display = 'none';
        paymentMethodField.style.display = 'none';
        paymentDateField.style.display = 'none';
    } else {
        paidAmountField.style.display = 'block';
        paymentMethodField.style.display = 'block';
        paymentDateField.style.display = 'block';

        if (status === 'paid') {
            document.querySelector('[name="paid_amount"]').value = <?= $invoice['total'] ?>;
        }
    }
}

// Inicializar al cargar
updatePaymentFields();
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
