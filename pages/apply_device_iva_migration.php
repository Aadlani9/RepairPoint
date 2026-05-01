<?php
/**
 * RepairPoint - Migración: Device + IVA Invoice
 * Aplica la migración para añadir columnas device e invoice_status
 */

define('SECURE_ACCESS', true);
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('pages/login.php'));
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ' . url('pages/403.php'));
    exit;
}

$db = getDB();
$results = [];
$errors  = [];

// Verificar columnas existentes
function columnExists($db, $table, $column) {
    $r = $db->selectOne(
        "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $column]
    );
    return $r && $r['cnt'] > 0;
}

$device_exists         = columnExists($db, 'invoices', 'device');
$invoice_status_exists = columnExists($db, 'invoices', 'invoice_status');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    $conn = $db->getConnection();

    if (!$device_exists) {
        try {
            $conn->exec("ALTER TABLE invoices ADD COLUMN device VARCHAR(255) DEFAULT NULL COMMENT 'Dispositivo asociado a la factura' AFTER notes");
            $results[] = '✅ Columna <code>device</code> añadida correctamente.';
            $device_exists = true;
        } catch (PDOException $e) {
            $errors[] = '❌ Error añadiendo <code>device</code>: ' . $e->getMessage();
        }
    } else {
        $results[] = 'ℹ️ Columna <code>device</code> ya existía.';
    }

    if (!$invoice_status_exists) {
        try {
            $conn->exec("ALTER TABLE invoices ADD COLUMN invoice_status ENUM('quote','invoice') NOT NULL DEFAULT 'invoice' COMMENT 'Tipo documento: presupuesto o factura' AFTER device");
            $results[] = '✅ Columna <code>invoice_status</code> añadida correctamente.';
            $invoice_status_exists = true;
        } catch (PDOException $e) {
            $errors[] = '❌ Error añadiendo <code>invoice_status</code>: ' . $e->getMessage();
        }
    } else {
        $results[] = 'ℹ️ Columna <code>invoice_status</code> ya existía.';
    }

    // Asegurar que registros existentes tengan invoice_status = 'invoice'
    try {
        $conn->exec("UPDATE invoices SET invoice_status = 'invoice' WHERE invoice_status IS NULL OR invoice_status = ''");
        $results[] = '✅ Registros existentes actualizados a <code>invoice</code>.';
    } catch (PDOException $e) {
        // Ignorar si ya están correctos
    }

    if (empty($errors)) {
        $_SESSION['success'] = 'Migración aplicada correctamente.';
    }
}

$page_title = 'Migración: Device + Estado Factura';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-4">
                <h2><i class="bi bi-database-gear text-primary"></i> Migración: Device + Estado Factura</h2>
                <p class="text-muted">Añade los campos <strong>Dispositivo</strong> e <strong>Estado de documento</strong> a las facturas.</p>
            </div>

            <?php foreach ($results as $msg): ?>
                <div class="alert alert-info"><?= $msg ?></div>
            <?php endforeach; ?>

            <?php foreach ($errors as $msg): ?>
                <div class="alert alert-danger"><?= $msg ?></div>
            <?php endforeach; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Estado de las columnas</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><code>invoices.device</code> — Dispositivo de la factura</span>
                            <?php if ($device_exists): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Existe</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Falta</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><code>invoices.invoice_status</code> — Presupuesto / Factura</span>
                            <?php if ($invoice_status_exists): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Existe</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Falta</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if (!$device_exists || !$invoice_status_exists): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="apply">
                    <button type="submit" class="btn btn-primary btn-lg w-100"
                            onclick="return confirm('¿Aplicar la migración?')">
                        <i class="bi bi-play-circle me-2"></i>Aplicar Migración Ahora
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>¡Todo correcto!</strong> Ambas columnas ya existen. No es necesario hacer nada.
                </div>
                <a href="<?= url('pages/customers.php') ?>" class="btn btn-success w-100">
                    <i class="bi bi-arrow-right-circle me-2"></i>Ir a Gestión de Clientes
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
