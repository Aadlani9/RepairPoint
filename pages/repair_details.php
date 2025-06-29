<?php
/**
 * RepairPoint - Detalles de Reparación
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Obtener ID de la reparación
$repair_id = intval($_GET['id'] ?? 0);

if (!$repair_id) {
    setMessage('ID de reparación no válido', MSG_ERROR);
    header('Location: ' . url('pages/repairs_active.php'));
    exit;
}

// Obtener datos de la reparación
$db = getDB();
$repair = $db->selectOne(
    "SELECT r.*, b.name as brand_name, m.name as model_name, 
            u.name as created_by_name, s.name as shop_name
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     JOIN users u ON r.created_by = u.id
     JOIN shops s ON r.shop_id = s.id
     WHERE r.id = ? AND r.shop_id = ?",
    [$repair_id, $shop_id]
);

if (!$repair) {
    setMessage('Reparación no encontrada', MSG_ERROR);
    header('Location: ' . url('pages/repairs_active.php'));
    exit;
}

$page_title = 'Reparación #' . $repair['reference'];

// حساب المدة والضمانة الصحيحة
$repair_duration = calculateRepairDuration($repair['received_at'], $repair['delivered_at']);
$warranty_days = $repair['warranty_days'] ?? 30;
$warranty_days_left = 0;
$is_under_warranty = false;

if ($repair['delivered_at']) {
    $warranty_days_left = calculateWarrantyDaysLeft($repair['delivered_at'], $warranty_days);
    $is_under_warranty = isUnderWarranty($repair['delivered_at'], $warranty_days);
}

// Procesar cambio de estado y acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        $action = $_POST['action'];
        $success = false;
        $message = '';

        switch ($action) {
            case 'update_status':
                $new_status = $_POST['status'] ?? '';

                if (in_array($new_status, ['pending', 'in_progress', 'completed'])) {
                    try {
                        $db->beginTransaction();

                        // إعداد البيانات للتحديث
                        $completed_at = null;
                        if ($new_status === 'completed') {
                            $completed_at = getCurrentDateTime();
                        } elseif ($new_status === 'pending' || $new_status === 'in_progress') {
                            // إذا تم إرجاع الحالة من completed، نحذف تاريخ الاكتمال
                            $completed_at = null;
                        }

                        // تحديث الإصلاح
                        $updated = $db->update(
                            "UPDATE repairs SET 
                                status = ?, 
                                completed_at = ?, 
                                updated_at = NOW() 
                             WHERE id = ? AND shop_id = ?",
                            [$new_status, $completed_at, $repair_id, $shop_id]
                        );

                        if ($updated !== false) {
                            $db->commit();

                            logActivity('repair_status_updated', "Reparación #{$repair['reference']} actualizada a $new_status", $_SESSION['user_id']);

                            $success = true;
                            $message = 'Estado actualizado correctamente';

                            // تحديث المتغير المحلي
                            $repair['status'] = $new_status;
                            if ($completed_at) {
                                $repair['completed_at'] = $completed_at;
                            } elseif ($new_status !== 'completed') {
                                $repair['completed_at'] = null;
                            }
                        } else {
                            $db->rollback();
                            $message = 'Error al actualizar el estado';
                        }

                    } catch (Exception $e) {
                        $db->rollback();
                        error_log("Error actualizando estado: " . $e->getMessage());
                        $message = 'Error al actualizar el estado';
                    }
                } else {
                    $message = 'Estado no válido';
                }
                break;

            case 'update_cost':
                $actual_cost = floatval($_POST['actual_cost'] ?? 0);

                if ($actual_cost >= 0) {
                    $updated = $db->update(
                        "UPDATE repairs SET actual_cost = ?, updated_at = NOW() WHERE id = ? AND shop_id = ?",
                        [$actual_cost > 0 ? $actual_cost : null, $repair_id, $shop_id]
                    );

                    if ($updated !== false) {
                        logActivity('repair_cost_updated', "Coste real actualizado para #{$repair['reference']}: €$actual_cost", $_SESSION['user_id']);
                        $success = true;
                        $message = 'Coste actualizado correctamente';
                        $repair['actual_cost'] = $actual_cost > 0 ? $actual_cost : null;
                    } else {
                        $message = 'Error al actualizar el coste';
                    }
                } else {
                    $message = 'Coste no válido';
                }
                break;

            case 'mark_delivered':
                $delivered_by = cleanString($_POST['delivered_by'] ?? '');
                $delivery_notes = cleanString($_POST['delivery_notes'] ?? '');

                if ($delivered_by) {
                    $updated = $db->update(
                        "UPDATE repairs SET status = 'delivered', delivered_at = NOW(), delivered_by = ?, notes = CONCAT(COALESCE(notes, ''), '\n\nEntrega: ', ?), updated_at = NOW() WHERE id = ? AND shop_id = ?",
                        [$delivered_by, $delivery_notes, $repair_id, $shop_id]
                    );

                    if ($updated !== false) {
                        logActivity('repair_delivered', "Reparación #{$repair['reference']} entregada por $delivered_by", $_SESSION['user_id']);
                        $success = true;
                        $message = 'Reparación marcada como entregada';
                        $repair['status'] = 'delivered';
                        $repair['delivered_at'] = getCurrentDateTime();
                        $repair['delivered_by'] = $delivered_by;
                    } else {
                        $message = 'Error al marcar como entregada';
                    }
                } else {
                    $message = 'Debe especificar quién entrega';
                }
                break;

            case 'reopen_repair':
                $reopen_type = $_POST['reopen_type'] ?? '';
                $reopen_reason = cleanString($_POST['reopen_reason'] ?? '');
                $reopen_notes = cleanString($_POST['reopen_notes'] ?? '');

                if (in_array($reopen_type, ['warranty', 'paid', 'goodwill']) && $reopen_reason) {
                    try {
                        $db->beginTransaction();

                        $updated = $db->update(
                            "UPDATE repairs SET 
                                status = 'reopened', 
                                reopen_type = ?, 
                                reopen_reason = ?, 
                                reopen_notes = ?, 
                                reopen_date = NOW(),
                                is_reopened = TRUE,
                                updated_at = NOW() 
                             WHERE id = ? AND shop_id = ?",
                            [$reopen_type, $reopen_reason, $reopen_notes, $repair_id, $shop_id]
                        );

                        if ($updated !== false) {
                            $db->commit();

                            $reopen_config = getConfig('reopen_types');
                            $type_name = $reopen_config[$reopen_type]['name'];

                            logActivity('repair_reopened', "Reparación #{$repair['reference']} reabierta como $type_name", $_SESSION['user_id']);

                            $success = true;
                            $message = "Reparación reabierta correctamente como: $type_name";

                            // تحديث البيانات المحلية
                            $repair['status'] = 'reopened';
                            $repair['reopen_type'] = $reopen_type;
                            $repair['reopen_reason'] = $reopen_reason;
                            $repair['reopen_notes'] = $reopen_notes;
                            $repair['reopen_date'] = getCurrentDateTime();
                            $repair['is_reopened'] = true;
                        } else {
                            $db->rollback();
                            $message = 'Error al reabrir la reparación';
                        }
                    } catch (Exception $e) {
                        $db->rollback();
                        error_log("Error reabriendo reparación: " . $e->getMessage());
                        $message = 'Error al reabrir la reparación';
                    }
                } else {
                    $message = 'Datos de reapertura incompletos';
                }
                break;
        }

        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);

        if ($success) {
            // إعادة حساب المعلومات بعد التحديث
            $repair_duration = calculateRepairDuration($repair['received_at'], $repair['delivered_at']);
            if ($repair['delivered_at']) {
                $warranty_days_left = calculateWarrantyDaysLeft($repair['delivered_at'], $warranty_days);
                $is_under_warranty = isUnderWarranty($repair['delivered_at'], $warranty_days);
            }
        }
    }
}

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/dashboard.php') ?>">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/repairs_active.php') ?>">
                        <i class="bi bi-tools"></i> Reparaciones
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-eye"></i> #<?= htmlspecialchars($repair['reference']) ?>
                </li>
            </ol>
        </nav>

        <!-- Header de la página -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header bg-info text-white p-4 rounded">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-1">
                                <i class="bi bi-eye me-2"></i>
                                Reparación #<?= htmlspecialchars($repair['reference']) ?>
                            </h1>
                            <p class="mb-0 opacity-75">
                                <?= htmlspecialchars($repair['customer_name']) ?> •
                                <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?> •
                                <?= getStatusName($repair['status']) ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="btn-group">
                                <a href="<?= url('pages/edit_repair.php?id=' . $repair['id']) ?>" class="btn btn-light">
                                    <i class="bi bi-pencil me-2"></i>Editar
                                </a>
                                <button class="btn btn-outline-light" onclick="printTicket(<?= $repair['id'] ?>)">
                                    <i class="bi bi-printer me-2"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mostrar mensajes -->
        <?php displayMessage(); ?>

        <div class="row">
            <!-- Información principal -->
            <div class="col-lg-8">
                <!-- Datos del cliente y dispositivo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-gear me-2"></i>
                            Información General
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="bi bi-person me-2"></i>Cliente
                                </h6>
                                <div class="info-group mb-3">
                                    <label class="fw-bold">Nombre:</label>
                                    <div><?= htmlspecialchars($repair['customer_name']) ?></div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="fw-bold">Teléfono:</label>
                                    <div>
                                        <a href="tel:<?= htmlspecialchars($repair['customer_phone']) ?>" class="text-decoration-none">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= htmlspecialchars($repair['customer_phone']) ?>
                                        </a>
                                    </div>
                                </div>
                                <?php if (!empty($repair['customer_email'])): ?>
                                    <div class="info-group mb-3">
                                        <label class="fw-bold">Email:</label>
                                        <div>
                                            <a href="mailto:<?= htmlspecialchars($repair['customer_email']) ?>" class="text-decoration-none">
                                                <i class="bi bi-envelope me-1"></i>
                                                <?= htmlspecialchars($repair['customer_email']) ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="bi bi-phone me-2"></i>Dispositivo
                                </h6>
                                <div class="info-group mb-3">
                                    <label class="fw-bold">Marca:</label>
                                    <div><?= htmlspecialchars($repair['brand_name']) ?></div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="fw-bold">Modelo:</label>
                                    <div><?= htmlspecialchars($repair['model_name']) ?></div>
                                </div>
                                <?php if (!empty($repair['device_serial'])): ?>
                                    <div class="info-group mb-3">
                                        <label class="fw-bold">Serial:</label>
                                        <div class="font-monospace"><?= htmlspecialchars($repair['device_serial']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descripción del problema -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Problema Reportado
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="problem-description">
                            <?= nl2br(htmlspecialchars($repair['issue_description'])) ?>
                        </div>

                        <?php if ($repair['notes']): ?>
                            <hr>
                            <h6 class="text-muted">Notas Internas:</h6>
                            <div class="notes-content">
                                <?= nl2br(htmlspecialchars($repair['notes'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Costes -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-currency-euro me-2"></i>
                            Información de Costes
                        </h5>
                        <?php if ($repair['status'] !== 'delivered'): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#costModal">
                                <i class="bi bi-pencil me-1"></i>Actualizar
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="cost-item">
                                    <label class="fw-bold text-muted">Coste Estimado:</label>
                                    <div class="cost-value">
                                        <?php if ($repair['estimated_cost']): ?>
                                            <span class="h5 text-warning">€<?= number_format($repair['estimated_cost'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="cost-item">
                                    <label class="fw-bold text-muted">Coste Real:</label>
                                    <div class="cost-value">
                                        <?php if ($repair['actual_cost']): ?>
                                            <span class="h5 text-success">€<?= number_format($repair['actual_cost'], 2) ?></span>
                                            <?php if ($repair['estimated_cost']): ?>
                                                <?php $difference = $repair['actual_cost'] - $repair['estimated_cost']; ?>
                                                <small class="<?= $difference > 0 ? 'text-danger' : 'text-success' ?>">
                                                    (<?= $difference > 0 ? '+' : '' ?>€<?= number_format($difference, 2) ?>)
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Pendiente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Garantía -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-shield-check me-2"></i>
                            Información de Garantía
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="warranty-info">
                                    <label class="fw-bold text-muted">Días de Garantía:</label>
                                    <div class="warranty-value">
                                        <span class="h6 text-info"><?= $warranty_days ?> días</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="warranty-status">
                                    <label class="fw-bold text-muted">Estado de Garantía:</label>
                                    <div class="mt-2">
                                        <?= formatWarrantyStatus($repair['delivered_at'], $warranty_days) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($repair['delivered_at']): ?>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        Entregado: <?= formatDateTime($repair['delivered_at']) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-x me-1"></i>
                                        Garantía expira:
                                        <?php
                                        $warranty_expires = date('d/m/Y', strtotime($repair['delivered_at'] . " +{$warranty_days} days"));
                                        echo $warranty_expires;
                                        ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Información de reapertura si existe -->
                        <?php if ($repair['is_reopened']): ?>
                            <hr>
                            <div class="reopen-info bg-warning bg-opacity-10 p-3 rounded">
                                <h6 class="text-warning">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Reparación Reabierta
                                </h6>
                                <div class="row">
                                    <?php if ($repair['reopen_type']): ?>
                                        <div class="col-md-6">
                                            <small><strong>Tipo:</strong>
                                                <?php
                                                $reopen_config = getConfig('reopen_types');
                                                echo $reopen_config[$repair['reopen_type']]['name'];
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($repair['reopen_date']): ?>
                                        <div class="col-md-6">
                                            <small><strong>Fecha:</strong> <?= formatDateTime($repair['reopen_date']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($repair['reopen_reason']): ?>
                                        <div class="col-12 mt-2">
                                            <small><strong>Motivo:</strong> <?= htmlspecialchars($repair['reopen_reason']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($repair['reopen_notes']): ?>
                                        <div class="col-12 mt-2">
                                            <small><strong>Notas:</strong> <?= htmlspecialchars($repair['reopen_notes']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-lg-4">
                <!-- Estado y acciones -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-gear me-2"></i>
                            Estado y Acciones
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Estado actual -->
                        <div class="status-section mb-4">
                            <label class="fw-bold text-muted">Estado Actual:</label>
                            <div class="mt-2">
                                <?= getStatusBadge($repair['status']) ?>
                                <?= getPriorityBadge($repair['priority']) ?>
                            </div>
                        </div>

                        <!-- Acciones según el estado -->
                        <?php if ($repair['status'] !== 'delivered'): ?>
                            <div class="actions-section">
                                <label class="fw-bold text-muted">Cambiar Estado:</label>
                                <form method="POST" action="" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="update_status">

                                    <div class="mb-3">
                                        <select class="form-select" name="status" required>
                                            <option value="">Seleccionar estado</option>
                                            <option value="pending" <?= ($repair['status'] === 'pending') ? 'selected' : '' ?>>
                                                Pendiente
                                            </option>
                                            <option value="in_progress" <?= ($repair['status'] === 'in_progress') ? 'selected' : '' ?>>
                                                En Proceso
                                            </option>
                                            <option value="completed" <?= ($repair['status'] === 'completed') ? 'selected' : '' ?>>
                                                Completado
                                            </option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-check me-2"></i>Actualizar Estado
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Botón de entrega -->
                        <?php if ($repair['status'] === 'completed'): ?>
                            <div class="delivery-section mt-4">
                                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#deliveryModal">
                                    <i class="bi bi-hand-thumbs-up me-2"></i>
                                    Marcar como Entregado
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información de fechas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-calendar me-2"></i>
                            Cronología
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Recibido</div>
                                    <div class="timeline-date">
                                        <?= formatDateTime($repair['received_at']) ?>
                                    </div>
                                    <small class="text-muted">
                                        Por <?= htmlspecialchars($repair['created_by_name']) ?>
                                    </small>
                                </div>
                            </div>

                            <?php if ($repair['completed_at']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Completado</div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['completed_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($repair['delivered_at']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Entregado</div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['delivered_at']) ?>
                                        </div>
                                        <?php if ($repair['delivered_by']): ?>
                                            <small class="text-muted">
                                                Por <?= htmlspecialchars($repair['delivered_by']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($repair['reopen_date']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Reabierto</div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['reopen_date']) ?>
                                        </div>
                                        <?php if ($repair['reopen_type']): ?>
                                            <small class="text-muted">
                                                Tipo: <?php
                                                $reopen_config = getConfig('reopen_types');
                                                echo $reopen_config[$repair['reopen_type']]['name'];
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="duration-info mt-3 p-3 bg-light rounded">
                            <div class="text-center">
                                <div class="fw-bold text-primary">
                                    <?= formatDurationSpanish($repair_duration) ?>
                                </div>
                                <small class="text-muted">
                                    <?= $repair['status'] === 'delivered' ? 'Duración total' : 'Días transcurridos' ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones de reapertura -->
                <?php if ($repair['status'] === 'delivered' && !$repair['is_reopened']): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Opciones de Reapertura
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="warranty-status-card mb-3 p-3 rounded"
                                 style="background-color: <?= $is_under_warranty ? 'rgba(25, 135, 84, 0.1)' : 'rgba(220, 53, 69, 0.1)' ?>;">
                                <div class="text-center">
                                    <div class="fw-bold <?= $is_under_warranty ? 'text-success' : 'text-danger' ?>">
                                        <?php if ($is_under_warranty): ?>
                                            <i class="bi bi-shield-check"></i> EN GARANTÍA
                                        <?php else: ?>
                                            <i class="bi bi-shield-x"></i> GARANTÍA EXPIRADA
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php if ($is_under_warranty): ?>
                                            Quedan <?= $warranty_days_left ?> días
                                        <?php else: ?>
                                            Expiró hace <?= abs($warranty_days_left) ?> días
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($is_under_warranty): ?>
                                    <button class="btn btn-success btn-sm"
                                            onclick="showReopenModal('warranty')"
                                            title="Reapertura gratuita por garantía">
                                        <i class="bi bi-shield-check me-2"></i>
                                        Reabrir - Garantía
                                    </button>
                                <?php endif; ?>

                                <button class="btn btn-warning btn-sm"
                                        onclick="showReopenModal('paid')"
                                        title="Nueva reparación con coste">
                                    <i class="bi bi-cash me-2"></i>
                                    Reabrir - Pagado
                                </button>

                                <button class="btn btn-info btn-sm"
                                        onclick="showReopenModal('goodwill')"
                                        title="Reapertura gratuita por buena voluntad">
                                    <i class="bi bi-heart me-2"></i>
                                    Reabrir - Cortesía
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Información del taller -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-shop me-2"></i>
                            Información del Taller
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="shop-info">
                            <div class="mb-2">
                                <strong><?= htmlspecialchars($repair['shop_name']) ?></strong>
                            </div>
                            <div class="text-muted small mb-2">
                                <i class="bi bi-person me-1"></i>
                                Registrado por: <?= htmlspecialchars($repair['created_by_name']) ?>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-calendar me-1"></i>
                                <?= formatDateTime($repair['created_at']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para actualizar coste -->
    <div class="modal fade" id="costModal" tabindex="-1" aria-labelledby="costModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="costModalLabel">
                        <i class="bi bi-currency-euro me-2"></i>Actualizar Coste Real
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_cost">

                        <div class="mb-3">
                            <label for="actual_cost" class="form-label">Coste Real (€)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number"
                                       class="form-control"
                                       id="actual_cost"
                                       name="actual_cost"
                                       value="<?= $repair['actual_cost'] ?: '' ?>"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                            </div>
                            <div class="form-text">
                                Deja en blanco o 0 si no hubo coste.
                            </div>
                        </div>

                        <?php if ($repair['estimated_cost']): ?>
                            <div class="alert alert-info">
                                <small>
                                    <strong>Coste estimado:</strong> €<?= number_format($repair['estimated_cost'], 2) ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Actualizar Coste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para marcar como entregado -->
    <div class="modal fade" id="deliveryModal" tabindex="-1" aria-labelledby="deliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deliveryModalLabel">
                        <i class="bi bi-hand-thumbs-up me-2"></i>Marcar como Entregado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="mark_delivered">

                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            Confirma que la reparación ha sido entregada al cliente.
                        </div>

                        <div class="customer-reminder mb-3 p-3 bg-light rounded">
                            <h6>Datos del Cliente:</h6>
                            <div><strong><?= htmlspecialchars($repair['customer_name']) ?></strong></div>
                            <div class="text-muted"><?= htmlspecialchars($repair['customer_phone']) ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="delivered_by" class="form-label">Entregado por *</label>
                            <input type="text"
                                   class="form-control"
                                   id="delivered_by"
                                   name="delivered_by"
                                   value="<?= htmlspecialchars($current_user['name']) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="delivery_notes" class="form-label">Notas de entrega (opcional)</label>
                            <textarea class="form-control"
                                      id="delivery_notes"
                                      name="delivery_notes"
                                      rows="3"
                                      placeholder="Comentarios sobre la entrega, estado del cliente, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check me-2"></i>Confirmar Entrega
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para reabrir reparación -->
    <div class="modal fade" id="reopenModal" tabindex="-1" aria-labelledby="reopenModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reopenModalLabel">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reabrir Reparación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="reopen_repair">
                        <input type="hidden" id="reopen_type_input" name="reopen_type" value="">

                        <div class="alert alert-info" id="reopen_info_alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <span id="reopen_info_text"></span>
                        </div>

                        <div class="customer-reminder mb-3 p-3 bg-light rounded">
                            <h6>Datos del Cliente:</h6>
                            <div><strong><?= htmlspecialchars($repair['customer_name']) ?></strong></div>
                            <div class="text-muted"><?= htmlspecialchars($repair['customer_phone']) ?></div>
                            <div class="text-muted">
                                <small>
                                    Dispositivo: <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reopen_reason" class="form-label">Motivo de Reapertura *</label>
                            <select class="form-select" id="reopen_reason" name="reopen_reason" required>
                                <option value="">Selecciona un motivo</option>
                                <option value="mismo_problema">Mismo problema persiste</option>
                                <option value="problema_relacionado">Problema relacionado con la reparación</option>
                                <option value="problema_nuevo">Problema nuevo después de la reparación</option>
                                <option value="insatisfaccion_cliente">Insatisfacción del cliente</option>
                                <option value="error_tecnico">Error técnico en la reparación</option>
                                <option value="otro">Otro motivo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reopen_notes" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control"
                                      id="reopen_notes"
                                      name="reopen_notes"
                                      rows="3"
                                      placeholder="Describe en detalle el motivo de la reapertura..."></textarea>
                        </div>

                        <div class="warranty-reminder p-3 bg-warning bg-opacity-10 rounded">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                <strong>Información de Garantía:</strong><br>
                                • Garantía original: <?= $warranty_days ?> días<br>
                                • Estado actual: <?= $is_under_warranty ? "Válida ({$warranty_days_left} días restantes)" : "Expirada" ?>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="reopen_submit_btn">
                            <i class="bi bi-check me-2"></i>Confirmar Reapertura
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Estilos específicos para detalles de reparación */
        .page-header {
            background: linear-gradient(135deg, #0dcaf0 0%, #0a98ba 100%);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .card-header {
            background: rgba(13, 202, 240, 0.05);
            border-bottom: 1px solid rgba(13, 202, 240, 0.1);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .info-group {
            border-left: 3px solid #0d6efd;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }

        .info-group label {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: #6c757d;
        }

        .problem-description {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            border-radius: 0.5rem;
            padding: 1rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .notes-content {
            background: rgba(108, 117, 125, 0.1);
            border: 1px solid rgba(108, 117, 125, 0.2);
            border-radius: 0.5rem;
            padding: 1rem;
            font-style: italic;
            color: #6c757d;
        }

        .cost-item {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            background: rgba(0, 123, 255, 0.05);
        }

        .cost-value {
            margin-top: 0.5rem;
        }

        .status-section,
        .actions-section {
            text-align: center;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-marker {
            position: absolute;
            left: -2.25rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .timeline-content {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .timeline-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.25rem;
        }

        .timeline-date {
            color: #0d6efd;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .duration-info {
            border: 2px dashed #0d6efd;
        }

        .customer-reminder {
            border-left: 4px solid #198754;
        }

        .reopen-info {
            border-left: 4px solid #ffc107;
        }

        .warranty-status-card {
            border: 2px solid;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 2rem 1rem !important;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .timeline {
                padding-left: 1.5rem;
            }

            .timeline-marker {
                left: -1.75rem;
                width: 0.75rem;
                height: 0.75rem;
            }

            .timeline::before {
                left: -1.375rem;
            }

            .info-group {
                border-left-width: 2px;
                padding-left: 0.75rem;
            }

            .cost-item {
                padding: 0.75rem;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .page-header {
                margin-left: -0.75rem;
                margin-right: -0.75rem;
                border-radius: 0;
            }

            .card-body {
                padding: 1rem;
            }

            .problem-description,
            .notes-content {
                padding: 0.75rem;
                font-size: 1rem;
            }

            .timeline-content {
                padding: 0.75rem;
            }

            .btn-group {
                width: 100%;
            }

            .btn-group .btn {
                flex: 1;
            }
        }

        /* Animaciones */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: slideInUp 0.5s ease-out;
        }

        .timeline-item {
            animation: slideInUp 0.5s ease-out;
        }

        .timeline-item:nth-child(1) { animation-delay: 0.1s; }
        .timeline-item:nth-child(2) { animation-delay: 0.2s; }
        .timeline-item:nth-child(3) { animation-delay: 0.3s; }
        .timeline-item:nth-child(4) { animation-delay: 0.4s; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-refresh cada 30 segundos si no está entregado
            <?php if ($repair['status'] !== 'delivered'): ?>
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    // Solo refrescar si la página está visible
                    // checkForUpdates();
                }
            }, 30000);
            <?php endif; ?>
        });

        // Función para mostrar modal de reapertura
        function showReopenModal(type) {
            const modal = new bootstrap.Modal(document.getElementById('reopenModal'));
            const reopenTypeInput = document.getElementById('reopen_type_input');
            const infoText = document.getElementById('reopen_info_text');
            const infoAlert = document.getElementById('reopen_info_alert');
            const submitBtn = document.getElementById('reopen_submit_btn');

            reopenTypeInput.value = type;

            // Configurar información según el tipo
            const typeConfig = {
                'warranty': {
                    text: 'Reapertura gratuita por garantía. El cliente no pagará coste adicional.',
                    alertClass: 'alert-success',
                    btnClass: 'btn-success',
                    btnText: '<i class="bi bi-shield-check me-2"></i>Reabrir con Garantía'
                },
                'paid': {
                    text: 'Nueva reparación pagada. Se aplicarán los costes normales de reparación.',
                    alertClass: 'alert-warning',
                    btnClass: 'btn-warning',
                    btnText: '<i class="bi bi-cash me-2"></i>Reabrir como Pagado'
                },
                'goodwill': {
                    text: 'Reapertura gratuita por cortesía. Sin coste para el cliente.',
                    alertClass: 'alert-info',
                    btnClass: 'btn-info',
                    btnText: '<i class="bi bi-heart me-2"></i>Reabrir por Cortesía'
                }
            };

            const config = typeConfig[type];
            infoText.textContent = config.text;
            infoAlert.className = `alert ${config.alertClass}`;
            submitBtn.className = `btn ${config.btnClass}`;
            submitBtn.innerHTML = config.btnText;

            modal.show();
        }

        // Función para imprimir ticket
        window.printTicket = function(repairId) {
            const printWindow = window.open(`<?= url('pages/print_ticket.php?id=') ?>${repairId}`, '_blank');
            if (printWindow) {
                printWindow.focus();
            } else {
                showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
            }
        };

        // Función para verificar actualizaciones
        function checkForUpdates() {
            fetch(`<?= url('api/repairs.php') ?>?action=get_status&id=<?= $repair['id'] ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.status !== '<?= $repair['status'] ?>') {
                        showNotification('El estado de la reparación ha cambiado. Recargando...', 'info');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.log('Error verificando actualizaciones:', error);
                });
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' :
                type === 'warning' ? 'alert-warning' :
                    type === 'danger' ? 'alert-danger' : 'alert-info';

            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + E para editar
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                window.location.href = '<?= url('pages/edit_repair.php?id=' . $repair['id']) ?>';
            }

            // Ctrl/Cmd + P para imprimir
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printTicket(<?= $repair['id'] ?>);
            }

            // Escape para volver
            if (e.key === 'Escape') {
                window.history.back();
            }
        });

        // Confirmación antes de cambios importantes
        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                if (this.value === 'completed') {
                    const confirm = window.confirm('¿Confirmas que la reparación está completada y lista para entregar?');
                    if (!confirm) {
                        this.value = '<?= $repair['status'] ?>';
                    }
                }
            });
        }

        // Actualización automática del coste
        const costInput = document.getElementById('actual_cost');
        if (costInput) {
            costInput.addEventListener('input', function() {
                const estimated = <?= $repair['estimated_cost'] ?: 0 ?>;
                const actual = parseFloat(this.value) || 0;

                if (estimated > 0 && actual > 0) {
                    const difference = actual - estimated;
                    const percentage = ((difference / estimated) * 100).toFixed(1);

                    // Mostrar diferencia visual si es necesario
                    const parent = this.closest('.modal-body');
                    let diffElement = parent.querySelector('.cost-difference');

                    if (!diffElement) {
                        diffElement = document.createElement('div');
                        diffElement.className = 'cost-difference mt-2';
                        this.parentNode.parentNode.appendChild(diffElement);
                    }

                    if (Math.abs(difference) > 0.01) {
                        const diffClass = difference > 0 ? 'text-danger' : 'text-success';
                        const diffSign = difference > 0 ? '+' : '';
                        diffElement.innerHTML = `
                    <small class="${diffClass}">
                        <strong>Diferencia:</strong> ${diffSign}€${difference.toFixed(2)} (${diffSign}${percentage}%)
                    </small>
                `;
                    } else {
                        diffElement.innerHTML = '';
                    }
                }
            });
        }

        // Validación de formularios
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showNotification('Por favor, completa todos los campos requeridos', 'danger');
                }
            });
        });

        // Auto-focus en modales
        document.addEventListener('shown.bs.modal', function(e) {
            const modal = e.target;
            const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>