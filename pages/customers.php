<?php
/**
 * RepairPoint - Gestión de Clientes
 * Sistema de administración de clientes para facturación
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

// ===================================================
// PROCESAMIENTO DE ACCIONES (POST)
// ===================================================

// Agregar nuevo cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $id_type = $_POST['id_type'];
    $id_number = trim($_POST['id_number']);
    $notes = trim($_POST['notes']);

    // Validación
    if (empty($full_name) || empty($phone) || empty($id_number)) {
        $_SESSION['error'] = 'El nombre, teléfono y número de documento son obligatorios';
    } else {
        // Verificar si el teléfono ya existe
        $existing = $db->selectOne(
            "SELECT id FROM customers WHERE phone = ? AND shop_id = ?",
            [$phone, $shop_id]
        );

        if ($existing) {
            $_SESSION['error'] = 'Ya existe un cliente con este número de teléfono';
        } else {
            $query = "INSERT INTO customers (full_name, phone, email, address, id_type, id_number, notes, shop_id, created_by)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = $db->insert($query, [
                $full_name, $phone, $email, $address, $id_type, $id_number, $notes, $shop_id, $_SESSION['user_id']
            ]);

            if ($result) {
                $_SESSION['success'] = 'Cliente agregado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al agregar el cliente';
            }
        }
    }
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Editar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_customer') {
    $customer_id = intval($_POST['customer_id']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $id_type = $_POST['id_type'];
    $id_number = trim($_POST['id_number']);
    $notes = trim($_POST['notes']);
    $status = $_POST['status'];

    if (empty($full_name) || empty($phone) || empty($id_number)) {
        $_SESSION['error'] = 'El nombre, teléfono y número de documento son obligatorios';
    } else {
        // Verificar que el teléfono no esté en uso por otro cliente
        $existing = $db->selectOne(
            "SELECT id FROM customers WHERE phone = ? AND shop_id = ? AND id != ?",
            [$phone, $shop_id, $customer_id]
        );

        if ($existing) {
            $_SESSION['error'] = 'Ya existe otro cliente con este número de teléfono';
        } else {
            $query = "UPDATE customers
                      SET full_name = ?, phone = ?, email = ?, address = ?, id_type = ?, id_number = ?, notes = ?, status = ?
                      WHERE id = ? AND shop_id = ?";
            $result = $db->update($query, [
                $full_name, $phone, $email, $address, $id_type, $id_number, $notes, $status, $customer_id, $shop_id
            ]);

            if ($result !== false) {
                $_SESSION['success'] = 'Cliente actualizado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al actualizar el cliente';
            }
        }
    }
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// Eliminar cliente
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $customer_id = intval($_GET['id']);

    // Verificar si el cliente tiene facturas
    $invoices = $db->selectOne(
        "SELECT COUNT(*) as count FROM invoices WHERE customer_id = ?",
        [$customer_id]
    );

    if ($invoices['count'] > 0) {
        $_SESSION['error'] = 'No se puede eliminar el cliente porque tiene facturas asociadas';
    } else {
        $result = $db->delete("DELETE FROM customers WHERE id = ? AND shop_id = ?", [$customer_id, $shop_id]);

        if ($result) {
            $_SESSION['success'] = 'Cliente eliminado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el cliente';
        }
    }
    header('Location: ' . url('pages/customers.php'));
    exit;
}

// ===================================================
// BÚSQUEDA Y FILTROS
// ===================================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Construir query de búsqueda
$query = "SELECT c.*,
          COUNT(DISTINCT i.id) as total_invoices,
          COALESCE(SUM(CASE WHEN i.payment_status = 'pending' THEN i.total ELSE 0 END), 0) as pending_amount
          FROM customers c
          LEFT JOIN invoices i ON c.id = i.customer_id
          WHERE c.shop_id = ?";
$params = [$shop_id];

if (!empty($search)) {
    $query .= " AND (c.full_name LIKE ? OR c.phone LIKE ? OR c.id_number LIKE ? OR c.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter !== 'all') {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";

$customers = $db->select($query, $params);

// Si hay error, asignar array vacío
if ($customers === false) {
    $customers = [];
}

// Estadísticas
$stats = $db->selectOne(
    "SELECT
        COUNT(*) as total_customers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_customers
    FROM customers
    WHERE shop_id = ?",
    [$shop_id]
);

// Si hay error (tablas no existen), usar valores por defecto
if (!$stats) {
    $stats = [
        'total_customers' => 0,
        'active_customers' => 0,
        'inactive_customers' => 0
    ];
    $_SESSION['warning'] = 'Las tablas de facturación no existen. Por favor, ejecuta la migración SQL primero.';
}

$page_title = 'Gestión de Clientes';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="bi bi-people-fill text-primary"></i> Gestión de Clientes
                </h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="bi bi-plus-circle"></i> Nuevo Cliente
                </button>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Total Clientes</h6>
                            <h2 class="mb-0"><?= number_format($stats['total_customers']) ?></h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Clientes Activos</h6>
                            <h2 class="mb-0"><?= number_format($stats['active_customers']) ?></h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Clientes Inactivos</h6>
                            <h2 class="mb-0"><?= number_format($stats['inactive_customers']) ?></h2>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['warning'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nombre, teléfono, email o documento..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de clientes -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Documento</th>
                            <th>Email</th>
                            <th>Facturas</th>
                            <th>Pendiente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="text-muted mt-2">No hay clientes registrados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($customer['full_name']) ?></strong>
                                    </td>
                                    <td>
                                        <i class="bi bi-phone"></i> <?= htmlspecialchars($customer['phone']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= strtoupper($customer['id_type']) ?>
                                        </span>
                                        <?= htmlspecialchars($customer['id_number']) ?>
                                    </td>
                                    <td>
                                        <?= !empty($customer['email']) ? htmlspecialchars($customer['email']) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $customer['total_invoices'] ?> facturas</span>
                                    </td>
                                    <td>
                                        <?php if ($customer['pending_amount'] > 0): ?>
                                            <span class="text-danger fw-bold">€<?= number_format($customer['pending_amount'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['status'] === 'active'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('pages/customer_details.php?id=' . $customer['id']) ?>"
                                               class="btn btn-info" title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-warning"
                                                    onclick="editCustomer(<?= htmlspecialchars(json_encode($customer)) ?>)"
                                                    title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($customer['total_invoices'] == 0): ?>
                                                <a href="<?= url('pages/customers.php?action=delete&id=' . $customer['id']) ?>"
                                                   class="btn btn-danger"
                                                   onclick="return confirm('¿Estás seguro de eliminar este cliente?')"
                                                   title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
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

<!-- Modal Agregar Cliente -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_customer">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                            <select name="id_type" class="form-select" required>
                                <option value="dni">DNI</option>
                                <option value="nie">NIE</option>
                                <option value="passport">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" name="id_number" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_customer">
                <input type="hidden" name="customer_id" id="edit_customer_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                            <select name="id_type" id="edit_id_type" class="form-select" required>
                                <option value="dni">DNI</option>
                                <option value="nie">NIE</option>
                                <option value="passport">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" name="id_number" id="edit_id_number" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Actualizar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCustomer(customer) {
    document.getElementById('edit_customer_id').value = customer.id;
    document.getElementById('edit_full_name').value = customer.full_name;
    document.getElementById('edit_phone').value = customer.phone;
    document.getElementById('edit_email').value = customer.email || '';
    document.getElementById('edit_id_type').value = customer.id_type;
    document.getElementById('edit_id_number').value = customer.id_number;
    document.getElementById('edit_address').value = customer.address || '';
    document.getElementById('edit_status').value = customer.status;
    document.getElementById('edit_notes').value = customer.notes || '';

    const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
    modal.show();
}
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
