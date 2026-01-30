<?php
/**
 * RepairPoint - Editar Cliente
 * Página para editar información del cliente
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

// Obtener ID del cliente
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    $_SESSION['error'] = 'ID de cliente no válido';
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

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_customer') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $id_type = $_POST['id_type'];
    $id_number = trim($_POST['id_number']);
    $address = trim($_POST['address']);
    $notes = trim($_POST['notes']);
    $status = $_POST['status'];

    // Validaciones básicas
    $errors = [];
    if (empty($full_name)) {
        $errors[] = 'El nombre es obligatorio';
    }
    if (empty($phone)) {
        $errors[] = 'El teléfono es obligatorio';
    }
    if (empty($id_number)) {
        $errors[] = 'El número de documento es obligatorio';
    }

    // Verificar si el documento ya existe en otro cliente
    $existing = $db->selectOne(
        "SELECT id FROM customers WHERE id_number = ? AND shop_id = ? AND id != ?",
        [$id_number, $shop_id, $customer_id]
    );
    if ($existing) {
        $errors[] = 'Ya existe un cliente con ese número de documento';
    }

    if (empty($errors)) {
        $query = "UPDATE customers SET
                    full_name = ?,
                    phone = ?,
                    email = ?,
                    id_type = ?,
                    id_number = ?,
                    address = ?,
                    notes = ?,
                    status = ?
                  WHERE id = ? AND shop_id = ?";

        $result = $db->update($query, [
            $full_name, $phone, $email, $id_type, $id_number, $address, $notes, $status, $customer_id, $shop_id
        ]);

        if ($result !== false) {
            $_SESSION['success'] = 'Cliente actualizado exitosamente';
            header('Location: ' . url('pages/customer_details.php?id=' . $customer_id));
            exit;
        } else {
            $errors[] = 'Error al actualizar el cliente';
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'Editar Cliente';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="<?= url('pages/customer_details.php?id=' . $customer_id) ?>" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <h2 class="mb-0">
                <i class="bi bi-pencil text-warning"></i> Editar Cliente
            </h2>
            <p class="text-muted"><?= htmlspecialchars($customer['full_name']) ?></p>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-x-circle"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bi bi-person-gear"></i> Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_customer">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= htmlspecialchars($customer['full_name']) ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?= htmlspecialchars($customer['phone']) ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($customer['email']) ?>">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                        <select name="id_type" class="form-select" required>
                            <option value="dni" <?= $customer['id_type'] === 'dni' ? 'selected' : '' ?>>DNI</option>
                            <option value="nie" <?= $customer['id_type'] === 'nie' ? 'selected' : '' ?>>NIE</option>
                            <option value="cif" <?= $customer['id_type'] === 'cif' ? 'selected' : '' ?>>CIF</option>
                            <option value="passport" <?= $customer['id_type'] === 'passport' ? 'selected' : '' ?>>Pasaporte</option>
                            <option value="other" <?= $customer['id_type'] === 'other' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Número de Documento <span class="text-danger">*</span></label>
                        <input type="text" name="id_number" class="form-control"
                               value="<?= htmlspecialchars($customer['id_number']) ?>" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($customer['address']) ?></textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Notas internas sobre el cliente..."><?= htmlspecialchars($customer['notes']) ?></textarea>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactive" <?= $customer['status'] === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <a href="<?= url('pages/customer_details.php?id=' . $customer_id) ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
