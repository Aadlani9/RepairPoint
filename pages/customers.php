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

// Crear factura rápida (con o sin cliente nuevo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quick_create_invoice') {
    $customer_id = 0;

    if (!empty($_POST['new_customer_name'])) {
        // Crear cliente nuevo primero
        $nc_name    = trim($_POST['new_customer_name']);
        $nc_phone   = trim($_POST['new_customer_phone']);
        $nc_idtype  = $_POST['new_customer_id_type'] ?? 'dni';
        $nc_idnum   = trim($_POST['new_customer_id_number']);
        $nc_email   = trim($_POST['new_customer_email'] ?? '');
        $nc_address = trim($_POST['new_customer_address'] ?? '');

        $existing = $db->selectOne(
            "SELECT id FROM customers WHERE phone = ? AND shop_id = ?",
            [$nc_phone, $shop_id]
        );

        if ($existing) {
            $customer_id = $existing['id'];
        } else {
            $customer_id = $db->insert(
                "INSERT INTO customers (full_name, phone, email, address, id_type, id_number, shop_id, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$nc_name, $nc_phone, $nc_email, $nc_address, $nc_idtype, $nc_idnum, $shop_id, $_SESSION['user_id']]
            );
        }
    } else {
        $customer_id = intval($_POST['selected_customer_id'] ?? 0);
    }

    if ($customer_id <= 0) {
        $_SESSION['error'] = 'Cliente no válido';
        header('Location: ' . url('pages/customers.php'));
        exit;
    }

    $invoice_date   = $_POST['inv_date']           ?? date('Y-m-d');
    $due_date       = $_POST['inv_due_date']        ?: null;
    $iva_rate       = floatval($_POST['inv_iva']    ?? 21);
    $pay_status     = $_POST['inv_payment_status']  ?? 'pending';
    $pay_method     = $_POST['inv_payment_method']  ?? '';
    $notes          = trim($_POST['inv_notes']      ?? '');
    $items_json     = $_POST['inv_items_data']      ?? '[]';
    $items          = json_decode($items_json, true) ?: [];

    if (empty($items)) {
        $_SESSION['error'] = 'Debes agregar al menos un artículo a la factura';
        header('Location: ' . url('pages/customers.php'));
        exit;
    }

    try {
        $db->beginTransaction();

        $invoice_id = $db->insert(
            "INSERT INTO invoices (customer_id, invoice_date, due_date, iva_rate, payment_status, payment_method, notes, shop_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$customer_id, $invoice_date, $due_date, $iva_rate, $pay_status, $pay_method, $notes, $shop_id, $_SESSION['user_id']]
        );

        if (!$invoice_id) throw new Exception('Error creando factura');

        foreach ($items as $item) {
            $subtotal = floatval($item['quantity']) * floatval($item['price']);
            $db->insert(
                "INSERT INTO invoice_items (invoice_id, item_type, description, imei, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$invoice_id, $item['type'], $item['description'], $item['imei'] ?? '', $item['quantity'], $item['price'], $subtotal]
            );
        }

        if ($pay_status === 'paid') {
            $total_row = $db->selectOne("SELECT total FROM invoices WHERE id = ?", [$invoice_id]);
            $db->update("UPDATE invoices SET paid_amount = ?, payment_date = CURDATE() WHERE id = ?",
                        [$total_row['total'], $invoice_id]);
        }

        $db->commit();
        logActivity('invoice_created', "Factura #{$invoice_id} creada desde gestión de clientes", $_SESSION['user_id']);
        header('Location: ' . url('pages/invoice_details.php?id=' . $invoice_id));
        exit;

    } catch (Exception $e) {
        $db->rollback();
        error_log("Error quick_create_invoice: " . $e->getMessage());
        $_SESSION['error'] = 'Error al crear la factura';
        header('Location: ' . url('pages/customers.php'));
        exit;
    }
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

// Parámetros de paginación
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = RECORDS_PER_PAGE;
$offset = calculateOffset($page, $limit);

// Construir query de búsqueda
$where = "c.shop_id = ?";
$params = [$shop_id];

if (!empty($search)) {
    $where .= " AND (c.full_name LIKE ? OR c.phone LIKE ? OR c.id_number LIKE ? OR c.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter !== 'all') {
    $where .= " AND c.status = ?";
    $params[] = $status_filter;
}

// Contar total para paginación
$total_records = $db->selectOne(
    "SELECT COUNT(DISTINCT c.id) as count FROM customers c WHERE $where",
    $params
)['count'] ?? 0;

$total_pages = calculateTotalPages($total_records, $limit);

$query = "SELECT c.*,
          COUNT(DISTINCT i.id) as total_invoices,
          COALESCE(SUM(CASE WHEN i.payment_status = 'pending' THEN i.total ELSE 0 END), 0) as pending_amount
          FROM customers c
          LEFT JOIN invoices i ON c.id = i.customer_id
          WHERE $where
          GROUP BY c.id ORDER BY c.created_at DESC
          LIMIT $limit OFFSET $offset";

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
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#quickInvoiceModal">
                        <i class="bi bi-file-earmark-plus"></i> Nueva Factura
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="bi bi-plus-circle"></i> Nuevo Cliente
                    </button>
                </div>
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

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <?= generatePagination($page, $total_pages, $_SERVER['PHP_SELF'], [
                    'search' => $search,
                    'status' => $status_filter
                ]) ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- =====================================================
     Modal: Nueva Factura Rápida (multi-paso)
     ===================================================== -->
<div class="modal fade" id="quickInvoiceModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-plus me-2"></i>
                    Nueva Factura
                    <small class="ms-3 fw-normal" id="qi-step-label">— Paso 1: Cliente</small>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="resetQuickInvoice()"></button>
            </div>

            <div class="modal-body">

                <!-- ── Paso 1: Búsqueda de cliente ── -->
                <div id="qi-step1">
                    <h6 class="text-muted mb-3"><i class="bi bi-search me-1"></i>Buscar cliente por teléfono, DNI/NIE/Pasaporte o nombre</h6>

                    <div class="input-group mb-3">
                        <input type="text" id="qi-search-input" class="form-control form-control-lg"
                               placeholder="Ej: 612345678 / 12345678A / Mohamed..."
                               oninput="qiSearchCustomer()">
                        <button class="btn btn-outline-secondary" type="button" onclick="qiSearchCustomer()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div id="qi-search-results" class="mb-3"></div>

                    <div id="qi-not-found" class="d-none">
                        <div class="alert alert-warning d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-x me-2"></i>Cliente no encontrado</span>
                            <button class="btn btn-primary btn-sm" onclick="qiShowNewCustomerForm()">
                                <i class="bi bi-person-plus me-1"></i>Crear nuevo cliente
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Paso 2: Crear cliente nuevo ── -->
                <div id="qi-step2" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="qiBack(1)">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <h6 class="mb-0 text-muted"><i class="bi bi-person-plus me-1"></i>Datos del nuevo cliente</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" id="nc-name" class="form-control" placeholder="Nombre completo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" id="nc-phone" class="form-control" placeholder="612 345 678">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                            <select id="nc-id-type" class="form-select">
                                <option value="dni">DNI</option>
                                <option value="nie">NIE</option>
                                <option value="passport">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">N° Documento <span class="text-danger">*</span></label>
                            <input type="text" id="nc-id-number" class="form-control" placeholder="12345678A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="nc-email" class="form-control" placeholder="email@ejemplo.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" id="nc-address" class="form-control" placeholder="Calle, número, ciudad...">
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-primary" onclick="qiConfirmNewCustomer()">
                            Continuar a Factura <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- ── Paso 3: Formulario de factura ── -->
                <div id="qi-step3" class="d-none">
                    <form method="POST" id="quickInvoiceForm">
                        <input type="hidden" name="action" value="quick_create_invoice">
                        <input type="hidden" name="selected_customer_id" id="qi-customer-id">
                        <!-- campos cliente nuevo (se rellenan si aplica) -->
                        <input type="hidden" name="new_customer_name"       id="qi-nc-name-h">
                        <input type="hidden" name="new_customer_phone"      id="qi-nc-phone-h">
                        <input type="hidden" name="new_customer_id_type"    id="qi-nc-idtype-h">
                        <input type="hidden" name="new_customer_id_number"  id="qi-nc-idnum-h">
                        <input type="hidden" name="new_customer_email"      id="qi-nc-email-h">
                        <input type="hidden" name="new_customer_address"    id="qi-nc-address-h">
                        <input type="hidden" name="inv_items_data"          id="qi-items-data">

                        <!-- Cliente seleccionado -->
                        <div class="d-flex align-items-start mb-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-3 mt-1" onclick="qiBack(1)">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <div class="alert alert-success mb-0 flex-grow-1 py-2">
                                <i class="bi bi-person-check me-2"></i>
                                <strong id="qi-selected-name"></strong>
                                <span class="text-muted ms-2" id="qi-selected-phone"></span>
                                <span class="badge bg-secondary ms-2" id="qi-selected-doc"></span>
                            </div>
                        </div>

                        <!-- Datos de la factura -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Fecha Factura <span class="text-danger">*</span></label>
                                <input type="date" name="inv_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha Vencimiento</label>
                                <input type="date" name="inv_due_date" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">IVA (%) <span class="text-danger">*</span></label>
                                <input type="number" name="inv_iva" id="qi-iva-rate" class="form-control"
                                       value="21" step="0.01" min="0" required onchange="qiCalcTotals()">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado Pago <span class="text-danger">*</span></label>
                                <select name="inv_payment_status" class="form-select" required>
                                    <option value="pending">Pendiente</option>
                                    <option value="paid">Pagado</option>
                                    <option value="partial">Parcial</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Método Pago</label>
                                <select name="inv_payment_method" class="form-select">
                                    <option value="">—</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="bizum">Bizum</option>
                                </select>
                            </div>
                        </div>

                        <!-- Artículos -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <span><i class="bi bi-cart me-1"></i>Artículos</span>
                                <button type="button" class="btn btn-sm btn-success" onclick="qiAddItem()">
                                    <i class="bi bi-plus-circle me-1"></i>Agregar
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" id="qi-items-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:14%">Tipo</th>
                                                <th style="width:38%">Descripción</th>
                                                <th style="width:16%">IMEI</th>
                                                <th style="width:8%">Cant.</th>
                                                <th style="width:12%">Precio</th>
                                                <th style="width:10%" class="text-end">Subtotal</th>
                                                <th style="width:2%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="qi-items-body">
                                            <tr id="qi-empty-row">
                                                <td colspan="7" class="text-center text-muted py-3">
                                                    <i class="bi bi-cart-x fs-4 d-block mb-1"></i>
                                                    Haz clic en «Agregar» para añadir artículos
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-7">
                                        <label class="form-label">Notas</label>
                                        <textarea name="inv_notes" class="form-control form-control-sm" rows="2"
                                                  placeholder="Notas o comentarios..."></textarea>
                                    </div>
                                    <div class="col-md-5">
                                        <table class="table table-sm mb-0 text-end">
                                            <tr>
                                                <td>Subtotal:</td>
                                                <td><strong id="qi-subtotal">€0.00</strong></td>
                                            </tr>
                                            <tr>
                                                <td>IVA (<span id="qi-iva-pct">21</span>%):</td>
                                                <td><strong id="qi-iva-amt">€0.00</strong></td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>TOTAL:</strong></td>
                                                <td><strong id="qi-total">€0.00</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

            </div><!-- /modal-body -->

            <div class="modal-footer" id="qi-footer" style="display:none">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetQuickInvoice()">Cancelar</button>
                <button type="button" class="btn btn-success btn-lg" onclick="qiSubmit()">
                    <i class="bi bi-save me-1"></i>Crear Factura
                </button>
            </div>

        </div>
    </div>
</div>

<script>
// ─────────────────────────────────────────
//  Quick Invoice Modal – JavaScript
// ─────────────────────────────────────────
let qiItems = [];
let qiCounter = 0;
let qiIsNewCustomer = false;

function resetQuickInvoice() {
    qiItems = []; qiCounter = 0; qiIsNewCustomer = false;
    document.getElementById('qi-search-input').value = '';
    document.getElementById('qi-search-results').innerHTML = '';
    document.getElementById('qi-not-found').classList.add('d-none');
    document.getElementById('qi-items-body').innerHTML =
        '<tr id="qi-empty-row"><td colspan="7" class="text-center text-muted py-3">' +
        '<i class="bi bi-cart-x fs-4 d-block mb-1"></i>Haz clic en «Agregar» para añadir artículos</td></tr>';
    document.getElementById('qi-subtotal').textContent = '€0.00';
    document.getElementById('qi-iva-amt').textContent   = '€0.00';
    document.getElementById('qi-total').textContent     = '€0.00';
    qiShowStep(1);
}

function qiShowStep(n) {
    [1,2,3].forEach(i => document.getElementById('qi-step'+i).classList.add('d-none'));
    document.getElementById('qi-step'+n).classList.remove('d-none');
    document.getElementById('qi-step-label').textContent =
        n===1 ? '— Paso 1: Cliente' : n===2 ? '— Paso 2: Nuevo cliente' : '— Paso 3: Factura';
    document.getElementById('qi-footer').style.display = n===3 ? 'flex' : 'none';
}

function qiBack(toStep) { qiShowStep(toStep); }

// ── Búsqueda de cliente ──
let qiSearchTimer;
function qiSearchCustomer() {
    clearTimeout(qiSearchTimer);
    qiSearchTimer = setTimeout(_qiDoSearch, 350);
}
function _qiDoSearch() {
    const term = document.getElementById('qi-search-input').value.trim();
    const resultsDiv = document.getElementById('qi-search-results');
    const notFound   = document.getElementById('qi-not-found');
    notFound.classList.add('d-none');
    if (term.length < 2) { resultsDiv.innerHTML = ''; return; }

    resultsDiv.innerHTML = '<div class="text-muted"><div class="spinner-border spinner-border-sm me-1"></div>Buscando...</div>';

    fetch(`<?= url('api/customer_lookup.php') ?>?term=${encodeURIComponent(term)}`)
        .then(r => r.json())
        .then(resp => {
            if (!resp.success || !resp.data.length) {
                resultsDiv.innerHTML = '';
                notFound.classList.remove('d-none');
                return;
            }
            resultsDiv.innerHTML = resp.data.map(c => `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border rounded mb-1"
                     style="cursor:pointer" onclick='qiSelectCustomer(${JSON.stringify(c)})'>
                    <div>
                        <strong>${c.full_name}</strong>
                        <span class="text-muted ms-2">${c.phone}</span>
                        <span class="badge bg-secondary ms-1">${c.id_type.toUpperCase()} ${c.id_number}</span>
                    </div>
                    <i class="bi bi-arrow-right-circle text-success fs-5"></i>
                </div>`).join('');
        })
        .catch(() => {
            resultsDiv.innerHTML = '<div class="text-danger">Error al buscar</div>';
        });
}

function qiSelectCustomer(c) {
    qiIsNewCustomer = false;
    document.getElementById('qi-customer-id').value  = c.id;
    document.getElementById('qi-nc-name-h').value    = '';
    document.getElementById('qi-selected-name').textContent  = c.full_name;
    document.getElementById('qi-selected-phone').textContent = c.phone;
    document.getElementById('qi-selected-doc').textContent   = c.id_type.toUpperCase() + ' ' + c.id_number;
    qiShowStep(3);
}

// ── Crear cliente nuevo ──
function qiShowNewCustomerForm() {
    document.getElementById('nc-name').value      = '';
    document.getElementById('nc-phone').value     = document.getElementById('qi-search-input').value.trim();
    document.getElementById('nc-id-number').value = '';
    document.getElementById('nc-email').value     = '';
    document.getElementById('nc-address').value   = '';
    qiShowStep(2);
}

function qiConfirmNewCustomer() {
    const name   = document.getElementById('nc-name').value.trim();
    const phone  = document.getElementById('nc-phone').value.trim();
    const idnum  = document.getElementById('nc-id-number').value.trim();
    if (!name || !phone || !idnum) {
        alert('Nombre, teléfono y número de documento son obligatorios');
        return;
    }
    qiIsNewCustomer = true;
    document.getElementById('qi-customer-id').value   = '';
    document.getElementById('qi-nc-name-h').value     = name;
    document.getElementById('qi-nc-phone-h').value    = phone;
    document.getElementById('qi-nc-idtype-h').value   = document.getElementById('nc-id-type').value;
    document.getElementById('qi-nc-idnum-h').value    = idnum;
    document.getElementById('qi-nc-email-h').value    = document.getElementById('nc-email').value.trim();
    document.getElementById('qi-nc-address-h').value  = document.getElementById('nc-address').value.trim();
    document.getElementById('qi-selected-name').textContent  = name;
    document.getElementById('qi-selected-phone').textContent = phone;
    document.getElementById('qi-selected-doc').textContent   =
        document.getElementById('nc-id-type').value.toUpperCase() + ' ' + idnum;
    qiShowStep(3);
}

// ── Artículos ──
function qiAddItem() {
    const id = qiCounter++;
    document.getElementById('qi-empty-row')?.remove();
    const tr = document.createElement('tr');
    tr.id = 'qi-item-' + id;
    tr.innerHTML = `
        <td><select class="form-select form-select-sm" onchange="qiUpdateItem(${id})">
            <option value="service">Servicio</option>
            <option value="product">Producto</option>
            <option value="spare_part">Repuesto</option>
        </select></td>
        <td><input type="text" class="form-control form-control-sm" placeholder="Descripción" onchange="qiUpdateItem(${id})"></td>
        <td><input type="text" class="form-control form-control-sm" placeholder="IMEI" onchange="qiUpdateItem(${id})"></td>
        <td><input type="number" class="form-control form-control-sm" value="1" min="1" onchange="qiUpdateItem(${id})"></td>
        <td><input type="number" class="form-control form-control-sm" value="0.00" min="0" step="0.01" onchange="qiUpdateItem(${id})"></td>
        <td class="text-end"><strong class="qi-subtotal">€0.00</strong></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="qiRemoveItem(${id})"><i class="bi bi-trash"></i></button></td>`;
    document.getElementById('qi-items-body').appendChild(tr);
    qiItems.push({ id, type:'service', description:'', imei:'', quantity:1, price:0, subtotal:0 });
}

function qiUpdateItem(id) {
    const tr = document.getElementById('qi-item-' + id);
    const inputs = tr.querySelectorAll('input, select');
    const qty = parseInt(inputs[3].value) || 0;
    const price = parseFloat(inputs[4].value) || 0;
    const subtotal = qty * price;
    const idx = qiItems.findIndex(x => x.id === id);
    if (idx !== -1) {
        qiItems[idx] = { id, type: inputs[0].value, description: inputs[1].value,
                         imei: inputs[2].value, quantity: qty, price, subtotal };
    }
    tr.querySelector('.qi-subtotal').textContent = '€' + subtotal.toFixed(2);
    qiCalcTotals();
}

function qiRemoveItem(id) {
    document.getElementById('qi-item-' + id)?.remove();
    qiItems = qiItems.filter(x => x.id !== id);
    if (!qiItems.length) {
        document.getElementById('qi-items-body').innerHTML =
            '<tr id="qi-empty-row"><td colspan="7" class="text-center text-muted py-3">' +
            '<i class="bi bi-cart-x fs-4 d-block mb-1"></i>Haz clic en «Agregar» para añadir artículos</td></tr>';
    }
    qiCalcTotals();
}

function qiCalcTotals() {
    const iva = parseFloat(document.getElementById('qi-iva-rate').value) || 0;
    const sub = qiItems.reduce((s, i) => s + i.subtotal, 0);
    const ivaAmt = sub * iva / 100;
    document.getElementById('qi-subtotal').textContent = '€' + sub.toFixed(2);
    document.getElementById('qi-iva-pct').textContent  = iva.toFixed(0);
    document.getElementById('qi-iva-amt').textContent  = '€' + ivaAmt.toFixed(2);
    document.getElementById('qi-total').textContent    = '€' + (sub + ivaAmt).toFixed(2);
}

function qiSubmit() {
    if (!qiItems.length) { alert('Debes agregar al menos un artículo'); return; }
    const hasDescription = qiItems.every(i => i.description.trim());
    if (!hasDescription) { alert('Todas las líneas deben tener descripción'); return; }
    document.getElementById('qi-items-data').value = JSON.stringify(qiItems);
    document.getElementById('quickInvoiceForm').submit();
}

// Limpiar al cerrar modal
document.getElementById('quickInvoiceModal').addEventListener('hidden.bs.modal', resetQuickInvoice);
</script>

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
