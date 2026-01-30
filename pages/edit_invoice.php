<?php
/**
 * RepairPoint - Editar Factura
 * Sistema de edición de facturas
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
    $_SESSION['error'] = 'ID de factura no válido';
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Obtener factura actual
$invoice = $db->selectOne(
    "SELECT i.*, c.full_name as customer_name, c.phone as customer_phone
     FROM invoices i
     JOIN customers c ON i.customer_id = c.id
     WHERE i.id = ? AND i.shop_id = ?",
    [$invoice_id, $shop_id]
);

if (!$invoice) {
    $_SESSION['error'] = 'Factura no encontrada';
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Obtener items actuales
$current_items = $db->select(
    "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id",
    [$invoice_id]
);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_invoice') {
    try {
        $db->beginTransaction();

        $customer_id = intval($_POST['customer_id']);
        $invoice_date = $_POST['invoice_date'];
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $iva_rate = floatval($_POST['iva_rate']);
        $notes = trim($_POST['notes']);

        // Actualizar factura
        $update_query = "UPDATE invoices SET customer_id = ?, invoice_date = ?, due_date = ?, iva_rate = ?, notes = ? WHERE id = ? AND shop_id = ?";
        $result = $db->update($update_query, [
            $customer_id, $invoice_date, $due_date, $iva_rate, $notes, $invoice_id, $shop_id
        ]);

        if ($result === false) {
            throw new Exception('Error al actualizar la factura');
        }

        // Eliminar items antiguos
        $db->delete("DELETE FROM invoice_items WHERE invoice_id = ?", [$invoice_id]);

        // Insertar nuevos items
        $items = json_decode($_POST['items_data'], true);
        foreach ($items as $item) {
            $item_query = "INSERT INTO invoice_items (invoice_id, item_type, description, imei, quantity, unit_price, subtotal)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $db->insert($item_query, [
                $invoice_id,
                $item['type'],
                $item['description'],
                !empty($item['imei']) ? $item['imei'] : null,
                $item['quantity'],
                $item['price'],
                $item['subtotal']
            ]);
        }

        $db->commit();

        $_SESSION['success'] = 'Factura actualizada exitosamente';
        header('Location: ' . url('pages/invoice_details.php?id=' . $invoice_id));
        exit;

    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = 'Error al actualizar la factura: ' . $e->getMessage();
    }
}

// Obtener todos los clientes activos
$customers = $db->select(
    "SELECT * FROM customers WHERE shop_id = ? AND status = 'active' ORDER BY full_name",
    [$shop_id]
);

$page_title = 'Editar Factura ' . $invoice['invoice_number'];
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="<?= url('pages/invoice_details.php?id=' . $invoice_id) ?>" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Volver a la Factura
            </a>
            <h2 class="mb-0">
                <i class="bi bi-pencil text-warning"></i> Editar Factura <?= htmlspecialchars($invoice['invoice_number']) ?>
            </h2>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulario de factura -->
    <form method="POST" id="invoiceForm">
        <input type="hidden" name="action" value="update_invoice">
        <input type="hidden" name="items_data" id="items_data">

        <div class="row">
            <!-- Información del cliente -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Información del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select" required onchange="loadCustomerInfo()">
                                <option value="">Seleccionar cliente...</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>"
                                            data-name="<?= htmlspecialchars($customer['full_name']) ?>"
                                            data-phone="<?= htmlspecialchars($customer['phone']) ?>"
                                            data-email="<?= htmlspecialchars($customer['email']) ?>"
                                            data-address="<?= htmlspecialchars($customer['address']) ?>"
                                            data-idtype="<?= strtoupper($customer['id_type']) ?>"
                                            data-idnumber="<?= htmlspecialchars($customer['id_number']) ?>"
                                            <?= $invoice['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer['full_name']) ?> - <?= htmlspecialchars($customer['phone']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="customerInfo" style="display: block">
                            <div class="alert alert-info">
                                <p class="mb-1"><strong id="info_name"></strong></p>
                                <p class="mb-1"><i class="bi bi-phone"></i> <span id="info_phone"></span></p>
                                <p class="mb-1"><i class="bi bi-envelope"></i> <span id="info_email"></span></p>
                                <p class="mb-1"><strong>Documento:</strong> <span id="info_id"></span></p>
                                <p class="mb-0"><i class="bi bi-geo-alt"></i> <span id="info_address"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de la factura -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-file-text"></i> Datos de la Factura</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha Factura <span class="text-danger">*</span></label>
                                <input type="date" name="invoice_date" class="form-control" value="<?= $invoice['invoice_date'] ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha Vencimiento</label>
                                <input type="date" name="due_date" class="form-control" value="<?= $invoice['due_date'] ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Tasa IVA (%) <span class="text-danger">*</span></label>
                                <input type="number" name="iva_rate" id="iva_rate" class="form-control"
                                       value="<?= $invoice['iva_rate'] ?>" step="0.01" required onchange="calculateTotals()">
                            </div>
                        </div>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-info-circle"></i> <strong>Nota:</strong> Para cambiar el estado de pago, use el botón "Actualizar Pago" en la página de detalles.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items de la factura -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-cart"></i> Items de la Factura</h5>
                <button type="button" class="btn btn-light btn-sm" onclick="addItem()">
                    <i class="bi bi-plus-circle"></i> Agregar Item
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Tipo</th>
                                <th width="35%">Descripción</th>
                                <th width="15%">IMEI (opcional)</th>
                                <th width="10%">Cantidad</th>
                                <th width="12%">Precio Unit.</th>
                                <th width="10%">Subtotal</th>
                                <th width="3%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        </tbody>
                    </table>
                </div>

                <!-- Totales -->
                <div class="row mt-4">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <table class="table table-sm">
                            <tr>
                                <td class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end" id="display_subtotal">€0.00</td>
                            </tr>
                            <tr>
                                <td class="text-end"><strong>IVA (<span id="display_iva_rate">21</span>%):</strong></td>
                                <td class="text-end" id="display_iva">€0.00</td>
                            </tr>
                            <tr class="table-primary">
                                <td class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><h4 id="display_total">€0.00</h4></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notas adicionales -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sticky"></i> Notas Adicionales</h5>
            </div>
            <div class="card-body">
                <textarea name="notes" class="form-control" rows="3" placeholder="Notas o comentarios adicionales..."><?= htmlspecialchars($invoice['notes']) ?></textarea>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="<?= url('pages/invoice_details.php?id=' . $invoice_id) ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let items = [];
let itemCounter = 0;

// Items existentes de la factura
const existingItems = <?= json_encode($current_items) ?>;

// Cargar información del cliente seleccionado
function loadCustomerInfo() {
    const select = document.getElementById('customer_id');
    const option = select.options[select.selectedIndex];

    if (select.value) {
        document.getElementById('customerInfo').style.display = 'block';
        document.getElementById('info_name').textContent = option.dataset.name;
        document.getElementById('info_phone').textContent = option.dataset.phone;
        document.getElementById('info_email').textContent = option.dataset.email || 'No especificado';
        document.getElementById('info_id').textContent = option.dataset.idtype + ' ' + option.dataset.idnumber;
        document.getElementById('info_address').textContent = option.dataset.address || 'No especificada';
    } else {
        document.getElementById('customerInfo').style.display = 'none';
    }
}

// Agregar nuevo item
function addItem(type = 'service', description = '', imei = '', quantity = 1, price = 0) {
    const row = document.createElement('tr');
    row.id = 'item_' + itemCounter;
    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm" onchange="updateItem(${itemCounter})">
                <option value="service" ${type === 'service' ? 'selected' : ''}>Servicio</option>
                <option value="product" ${type === 'product' ? 'selected' : ''}>Producto</option>
                <option value="spare_part" ${type === 'spare_part' ? 'selected' : ''}>Repuesto</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" placeholder="Descripción"
                   value="${escapeHtml(description)}" onchange="updateItem(${itemCounter})" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" placeholder="IMEI"
                   value="${escapeHtml(imei || '')}" onchange="updateItem(${itemCounter})">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" value="${quantity}" min="1"
                   onchange="updateItem(${itemCounter})" required>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" value="${parseFloat(price).toFixed(2)}" min="0" step="0.01"
                   onchange="updateItem(${itemCounter})" required>
        </td>
        <td class="text-end">
            <strong class="item-subtotal">€${(quantity * price).toFixed(2)}</strong>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${itemCounter})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    document.getElementById('itemsBody').appendChild(row);

    items.push({
        id: itemCounter,
        type: type,
        description: description,
        imei: imei || '',
        quantity: quantity,
        price: price,
        subtotal: quantity * price
    });

    itemCounter++;
    calculateTotals();
}

// Escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Actualizar item
function updateItem(id) {
    const row = document.getElementById('item_' + id);
    const inputs = row.querySelectorAll('input, select');

    const itemData = {
        id: id,
        type: inputs[0].value,
        description: inputs[1].value,
        imei: inputs[2].value,
        quantity: parseInt(inputs[3].value),
        price: parseFloat(inputs[4].value),
        subtotal: parseInt(inputs[3].value) * parseFloat(inputs[4].value)
    };

    const index = items.findIndex(item => item.id === id);
    if (index !== -1) {
        items[index] = itemData;
    }

    row.querySelector('.item-subtotal').textContent = '€' + itemData.subtotal.toFixed(2);
    calculateTotals();
}

// Eliminar item
function removeItem(id) {
    document.getElementById('item_' + id).remove();
    items = items.filter(item => item.id !== id);

    if (items.length === 0) {
        document.getElementById('itemsBody').innerHTML = `
            <tr id="emptyRow">
                <td colspan="7" class="text-center text-muted">
                    <i class="bi bi-cart-x fs-3"></i>
                    <p>No hay items. Haz clic en "Agregar Item" para comenzar.</p>
                </td>
            </tr>
        `;
    }

    calculateTotals();
}

// Calcular totales
function calculateTotals() {
    const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
    const ivaRate = parseFloat(document.getElementById('iva_rate').value);
    const iva = subtotal * (ivaRate / 100);
    const total = subtotal + iva;

    document.getElementById('display_subtotal').textContent = '€' + subtotal.toFixed(2);
    document.getElementById('display_iva_rate').textContent = ivaRate.toFixed(0);
    document.getElementById('display_iva').textContent = '€' + iva.toFixed(2);
    document.getElementById('display_total').textContent = '€' + total.toFixed(2);
}

// Validar y enviar formulario
document.getElementById('invoiceForm').addEventListener('submit', function(e) {
    if (items.length === 0) {
        e.preventDefault();
        alert('Debes agregar al menos un item a la factura');
        return false;
    }

    document.getElementById('items_data').value = JSON.stringify(items);
});

// Cargar items existentes al cargar la página
window.addEventListener('DOMContentLoaded', function() {
    loadCustomerInfo();

    // Cargar items existentes
    existingItems.forEach(function(item) {
        addItem(item.item_type, item.description, item.imei, item.quantity, item.unit_price);
    });

    if (existingItems.length === 0) {
        document.getElementById('itemsBody').innerHTML = `
            <tr id="emptyRow">
                <td colspan="7" class="text-center text-muted">
                    <i class="bi bi-cart-x fs-3"></i>
                    <p>No hay items. Haz clic en "Agregar Item" para comenzar.</p>
                </td>
            </tr>
        `;
    }
});
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
