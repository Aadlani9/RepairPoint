<?php
/**
 * RepairPoint - Detalles de Reparación (محدث مع نظام قطع الغيار)
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

// Obtener datos de la reparación (مع دعم الأجهزة المخصصة)
$db = getDB();
$repair = $db->selectOne(
    "SELECT r.*,
            b.name as brand_name,
            m.name as model_name,
            m.model_reference,
            u.name as created_by_name,
            s.name as shop_name
     FROM repairs r
     LEFT JOIN brands b ON r.brand_id = b.id
     LEFT JOIN models m ON r.model_id = m.id
     JOIN users u ON r.created_by = u.id
     JOIN shops s ON r.shop_id = s.id
     WHERE r.id = ? AND r.shop_id = ?",
    [$repair_id, $shop_id]
);

// إعداد متغير لعرض الجهاز بشكل صحيح (مع دعم الأجهزة المخصصة)
$deviceDisplayName = '';
if ($repair) {
    $deviceDisplayName = getDeviceDisplayName($repair);
}

if (!$repair) {
    setMessage('Reparación no encontrada', MSG_ERROR);
    header('Location: ' . url('pages/repairs_active.php'));
    exit;
}

$page_title = 'Reparación #' . $repair['reference'];

// 🔧 التوافق مع قواعد البيانات - تعيين قيم افتراضية للحقول التي قد لا تكون موجودة
$repair['original_delivered_at'] = $repair['original_delivered_at'] ?? null;
$repair['reopen_delivered_at'] = $repair['reopen_delivered_at'] ?? null;
$repair['reopen_warranty_days'] = $repair['reopen_warranty_days'] ?? null;
$repair['reopen_count'] = $repair['reopen_count'] ?? 0;
$repair['reopen_completed_at'] = $repair['reopen_completed_at'] ?? null;
$repair['last_reopen_by'] = $repair['last_reopen_by'] ?? null;

// Obtener qطع الغيار المستخدمة en esta reparación
$used_spare_parts = getRepairSpareParts($repair_id);
$spare_parts_cost = calculateRepairSparePartsCost($repair_id);

// صلاحيات قطع الغيار
$spare_parts_permissions = getCurrentUserSparePartsPermissions();

// حساب المدة والضمانة الصحيحة (مع مراعاة إعادة الفتح)
$current_duration = calculateCurrentRepairDuration($repair);
$total_duration = calculateTotalRepairDuration($repair);
$warranty_info = getCurrentWarrantyInfo($repair);

// للتوافق مع الكود القديم
$warranty_days = $warranty_info['warranty_days'];
$warranty_days_left = $warranty_info['days_left'];
$is_under_warranty = $warranty_info['is_valid'];

// الحصول على سجل الأحداث
$repair_history = getRepairHistory($repair_id);

// Procesar acciones de قطع الغيار
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spare_part_action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        $action = $_POST['spare_part_action'];
        $success = false;
        $message = '';

        switch ($action) {
            case 'add_spare_part':
                if ($spare_parts_permissions['use_spare_parts']) {
                    $spare_part_id = intval($_POST['spare_part_id'] ?? 0);
                    $quantity = intval($_POST['quantity'] ?? 1);
                    $unit_price = floatval($_POST['unit_price'] ?? 0);

                    if ($spare_part_id > 0 && $quantity > 0) {
                        $usage_id = addRepairSparePart($repair_id, $spare_part_id, $quantity, $unit_price);
                        if ($usage_id) {
                            $success = true;
                            $message = 'Repuesto agregado correctamente';
                            // إعادة تحميل البيانات
                            $used_spare_parts = getRepairSpareParts($repair_id);
                            $spare_parts_cost = calculateRepairSparePartsCost($repair_id);
                        } else {
                            $message = 'Error al agregar el repuesto';
                        }
                    } else {
                        $message = 'Datos de repuesto no válidos';
                    }
                } else {
                    $message = 'No tienes permisos para agregar repuestos';
                }
                break;

            case 'remove_spare_part':
                if ($spare_parts_permissions['use_spare_parts']) {
                    $usage_id = intval($_POST['usage_id'] ?? 0);

                    if ($usage_id > 0) {
                        try {
                            $db->beginTransaction();

                            // الحصول على بيانات الاستخدام قبل الحذف
                            $usage = $db->selectOne(
                                "SELECT * FROM repair_spare_parts WHERE id = ? AND repair_id = ?",
                                [$usage_id, $repair_id]
                            );

                            if ($usage) {
                                // حذف الاستخدام
                                $deleted = $db->delete(
                                    "DELETE FROM repair_spare_parts WHERE id = ?",
                                    [$usage_id]
                                );

                                if ($deleted) {
                                    // إرجاع المخزون
                                    updateSparePartStock($usage['spare_part_id'], $usage['quantity'], 'add');

                                    $db->commit();
                                    $success = true;
                                    $message = 'Repuesto eliminado correctamente';

                                    // إعادة تحميل البيانات
                                    $used_spare_parts = getRepairSpareParts($repair_id);
                                    $spare_parts_cost = calculateRepairSparePartsCost($repair_id);
                                } else {
                                    $db->rollback();
                                    $message = 'Error al eliminar el repuesto';
                                }
                            } else {
                                $db->rollback();
                                $message = 'Repuesto no encontrado';
                            }

                        } catch (Exception $e) {
                            $db->rollback();
                            error_log("Error eliminando repuesto: " . $e->getMessage());
                            $message = 'Error al eliminar el repuesto';
                        }
                    } else {
                        $message = 'ID de repuesto no válido';
                    }
                } else {
                    $message = 'No tienes permisos para eliminar repuestos';
                }
                break;
        }

        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);
    }
}

// Procesar cambio de estado y acciones (الكود الأصلي)
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

                        // حفظ التسليم الأصلي إذا لم يكن محفوظًا
                        if (!empty($repair['delivered_at']) && empty($repair['original_delivered_at'])) {
                            $db->update(
                                "UPDATE repairs SET original_delivered_at = ? WHERE id = ?",
                                [$repair['delivered_at'], $repair_id]
                            );
                        }

                        // تحديث الإصلاح مع تعيين الضمان الجديد
                        $updated = $db->update(
                            "UPDATE repairs SET
                                status = 'reopened',
                                reopen_type = ?,
                                reopen_reason = ?,
                                reopen_notes = ?,
                                reopen_date = NOW(),
                                is_reopened = TRUE,
                                reopen_warranty_days = 30,
                                reopen_count = COALESCE(reopen_count, 0) + 1,
                                last_reopen_by = ?,
                                updated_at = NOW(),
                                updated_by = ?
                             WHERE id = ? AND shop_id = ?",
                            [
                                $reopen_type,
                                $reopen_reason,
                                $reopen_notes,
                                $_SESSION['user_id'],
                                $_SESSION['user_id'],
                                $repair_id,
                                $shop_id
                            ]
                        );

                        if ($updated !== false) {
                            // تسجيل الحدث في السجل التاريخي
                            $event_type = match($reopen_type) {
                                'warranty' => 'warranty_reopened',
                                'paid' => 'paid_reopened',
                                'goodwill' => 'goodwill_reopened',
                                default => 'reopened'
                            };

                            $reopen_config = getConfig('reopen_types');
                            $type_name = $reopen_config[$reopen_type]['name'];

                            addRepairHistoryEvent(
                                $repair_id,
                                $shop_id,
                                $event_type,
                                [
                                    'description' => "Reparación reabierta como: $type_name",
                                    'reopen_type' => $reopen_type,
                                    'reopen_reason' => $reopen_reason,
                                    'reopen_notes' => $reopen_notes,
                                    'new_warranty_days' => 30
                                ]
                            );

                            $db->commit();

                            logActivity('repair_reopened', "Reparación #{$repair['reference']} reabierta como $type_name", $_SESSION['user_id']);

                            $success = true;
                            $message = "Reparación reabierta correctamente como: $type_name con nueva garantía de 30 días";

                            // تحديث البيانات المحلية
                            $repair['status'] = 'reopened';
                            $repair['reopen_type'] = $reopen_type;
                            $repair['reopen_reason'] = $reopen_reason;
                            $repair['reopen_notes'] = $reopen_notes;
                            $repair['reopen_date'] = getCurrentDateTime();
                            $repair['is_reopened'] = true;
                            $repair['reopen_warranty_days'] = 30;
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
                        <div class="col-md-6">
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
                        <div class="col-md-6 text-md-end">
                            <div class="btn-group" role="group">
                                <a href="<?= url('pages/edit_repair.php?id=' . $repair['id']) ?>" class="btn btn-light">
                                    <i class="bi bi-pencil me-2"></i>Editar
                                </a>
                                <button class="btn btn-outline-light" onclick="printTicket(<?= $repair['id'] ?>)">
                                    <i class="bi bi-printer me-2"></i>Ticket
                                </button>
                                <?php if (!empty($used_spare_parts) && $spare_parts_permissions['print_invoice']): ?>
                                    <button class="btn btn-outline-light" onclick="printSparePartsInvoice(<?= $repair['id'] ?>)">
                                        <i class="bi bi-receipt me-2"></i>Factura Repuestos
                                    </button>
                                <?php endif; ?>
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

                <!-- قطع الغيار المستخدمة - جديد -->
                <?php if ($spare_parts_permissions['view_spare_parts']): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-gear me-2"></i>
                                Repuestos Utilizados
                                <?php if (!empty($used_spare_parts)): ?>
                                    <span class="badge bg-primary ms-2"><?= count($used_spare_parts) ?></span>
                                <?php endif; ?>
                            </h5>
                            <?php if ($spare_parts_permissions['use_spare_parts'] && $repair['status'] !== 'delivered'): ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSparePartModal">
                                    <i class="bi bi-plus me-1"></i>Agregar Repuesto
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($used_spare_parts)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-gear" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="mt-3">No se han utilizado repuestos en esta reparación</p>
                                    <?php if ($spare_parts_permissions['use_spare_parts'] && $repair['status'] !== 'delivered'): ?>
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSparePartModal">
                                            <i class="bi bi-plus me-2"></i>Agregar Primer Repuesto
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Repuesto</th>
                                            <th>Categoría</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-end">Total</th>
                                            <?php if ($spare_parts_permissions['view_detailed_costs']): ?>
                                                <th class="text-end">Beneficio</th>
                                            <?php endif; ?>
                                            <th class="text-center">Garantía</th>
                                            <?php if ($spare_parts_permissions['use_spare_parts'] && $repair['status'] !== 'delivered'): ?>
                                                <th class="text-center">Acciones</th>
                                            <?php endif; ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($used_spare_parts as $part): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($part['part_name']) ?></div>
                                                    <?php if (!empty($part['part_code'])): ?>
                                                        <small class="text-muted">Código: <?= htmlspecialchars($part['part_code']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= formatSparePartCategory($part['category']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= $part['quantity'] ?></span>
                                                </td>
                                                <td class="text-end">
                                                    €<?= number_format($part['unit_price'], 2) ?>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    €<?= number_format($part['total_price'], 2) ?>
                                                </td>
                                                <?php if ($spare_parts_permissions['view_detailed_costs']): ?>
                                                    <td class="text-end">
                                                        <?php
                                                        $unit_cost = ($part['unit_cost_price'] ?? 0) + ($part['unit_labor_cost'] ?? 0);
                                                        $profit = ($part['unit_price'] - $unit_cost) * $part['quantity'];
                                                        $profit_class = $profit > 0 ? 'text-success' : ($profit < 0 ? 'text-danger' : 'text-muted');
                                                        ?>
                                                        <span class="<?= $profit_class ?> fw-bold">
                                                            €<?= number_format($profit, 2) ?>
                                                        </span>
                                                        <?php if ($unit_cost > 0): ?>
                                                            <br><small class="text-muted">
                                                                <?= number_format(($profit / ($unit_cost * $part['quantity'])) * 100, 1) ?>% margen
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?= $part['warranty_days'] ?> días</span>
                                                </td>
                                                <?php if ($spare_parts_permissions['use_spare_parts'] && $repair['status'] !== 'delivered'): ?>
                                                    <td class="text-center">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="removeRepairSparePart(<?= $part['id'] ?>, '<?= htmlspecialchars($part['part_name']) ?>')"
                                                                title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">TOTAL REPUESTOS:</th>
                                            <th class="text-end">€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?></th>
                                            <?php if ($spare_parts_permissions['view_detailed_costs']): ?>
                                                <th class="text-end">
                                                    <span class="<?= $spare_parts_cost['total_profit'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                        €<?= number_format($spare_parts_cost['total_profit'], 2) ?>
                                                    </span>
                                                </th>
                                            <?php endif; ?>
                                            <th></th>
                                            <?php if ($spare_parts_permissions['use_spare_parts'] && $repair['status'] !== 'delivered'): ?>
                                                <th></th>
                                            <?php endif; ?>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- إجمالي التكاليف المفصل للإدارة -->
                                <?php if ($spare_parts_permissions['view_detailed_costs'] && !empty($used_spare_parts)): ?>
                                    <div class="row mt-3">
                                        <div class="col-md-8 offset-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title mb-3">Análisis Financiero (Solo Admin)</h6>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between">
                                                                <small>Coste de compra:</small>
                                                                <small class="fw-bold">€<?= number_format($spare_parts_cost['total_cost_price'], 2) ?></small>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between">
                                                                <small>Coste de mano de obra:</small>
                                                                <small class="fw-bold">€<?= number_format($spare_parts_cost['total_labor_cost'], 2) ?></small>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <hr class="my-2">
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between">
                                                                <small>Precio al cliente:</small>
                                                                <small class="fw-bold text-primary">€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?></small>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between">
                                                                <small>Beneficio neto:</small>
                                                                <small class="fw-bold <?= $spare_parts_cost['total_profit'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                                    €<?= number_format($spare_parts_cost['total_profit'], 2) ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <?php if ($spare_parts_cost['total_cost_price'] > 0): ?>
                                                            <div class="col-12 mt-2">
                                                                <div class="text-center">
                                                                    <small class="badge bg-info">
                                                                        Margen: <?= number_format(($spare_parts_cost['total_profit'] / ($spare_parts_cost['total_cost_price'] + $spare_parts_cost['total_labor_cost'])) * 100, 1) ?>%
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Costes (تحديث لتشمل قطع الغيار) -->
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

                        <!-- تفاصيل التكاليف مع قطع الغيار -->
                        <?php if (!empty($used_spare_parts) || ($repair['actual_cost'] > 0)): ?>
                            <hr>
                            <div class="cost-breakdown">
                                <h6 class="text-muted mb-3">Desglose de Costes:</h6>
                                <div class="row">
                                    <?php if (!empty($used_spare_parts)): ?>
                                        <div class="col-md-6">
                                            <div class="breakdown-item p-3 border rounded bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span><i class="bi bi-gear me-2"></i>Repuestos:</span>
                                                    <span class="fw-bold text-primary">€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?></span>
                                                </div>
                                                <small class="text-muted"><?= count($used_spare_parts) ?> repuesto(s)</small>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                    $labor_cost = 0;
                                    if ($repair['actual_cost'] > 0) {
                                        $labor_cost = $repair['actual_cost'] - ($spare_parts_cost['total_customer_price'] ?? 0);
                                    }
                                    if ($labor_cost > 0):
                                        ?>
                                        <div class="col-md-6">
                                            <div class="breakdown-item p-3 border rounded bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span><i class="bi bi-wrench me-2"></i>Mano de obra:</span>
                                                    <span class="fw-bold text-info">€<?= number_format($labor_cost, 2) ?></span>
                                                </div>
                                                <small class="text-muted">Trabajo técnico</small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (($spare_parts_cost['total_customer_price'] ?? 0) > 0 && $labor_cost > 0): ?>
                                    <div class="mt-3 p-3 bg-primary bg-opacity-10 rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">TOTAL FACTURADO:</span>
                                            <span class="h5 mb-0 text-primary">
                                                €<?= number_format(($spare_parts_cost['total_customer_price'] ?? 0) + $labor_cost, 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                        <?php if ($repair['is_reopened'] && $warranty_info['original_delivered_at']): ?>
                            <!-- Garantía Original -->
                            <div class="mb-4 p-3 bg-light rounded">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-clock-history me-2"></i>Reparación Original
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">Garantía original:</small>
                                        <span class="fw-bold"><?= $warranty_info['original_warranty_days'] ?> días</span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">Entregado:</small>
                                        <span class="fw-bold"><?= formatDate($warranty_info['original_delivered_at'], 'd/m/Y H:i') ?></span>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <small class="text-muted d-block mb-1">Expira:</small>
                                        <span class="text-muted">
                                            <?php
                                            $original_expires = date('d/m/Y', strtotime($warranty_info['original_delivered_at'] . " +{$warranty_info['original_warranty_days']} days"));
                                            echo $original_expires;
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Garantía Actual (Reabierta) -->
                            <div class="p-3 bg-warning bg-opacity-10 border border-warning rounded">
                                <h6 class="text-warning mb-3">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Garantía Actual (Reabierta)
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">Nueva garantía:</small>
                                        <span class="fw-bold text-warning"><?= $warranty_info['warranty_days'] ?> días</span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">Estado:</small>
                                        <?php if ($warranty_info['is_valid']): ?>
                                            <span class="badge bg-success">
                                                Válida - <?= $warranty_info['days_left'] ?> días restantes
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Expirada</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($warranty_info['delivered_at']): ?>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted d-block mb-1">Re-entregado:</small>
                                            <span class="fw-bold"><?= formatDate($warranty_info['delivered_at'], 'd/m/Y H:i') ?></span>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted d-block mb-1">Expira:</small>
                                            <span class="fw-bold">
                                                <?= date('d/m/Y', strtotime($warranty_info['warranty_expires_at'])) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($repair['reopen_type']): ?>
                                        <div class="col-12 mt-2">
                                            <small class="text-muted d-block mb-1">Tipo de reapertura:</small>
                                            <?php
                                            $reopen_config = getConfig('reopen_types');
                                            $reopen_type_info = $reopen_config[$repair['reopen_type']];
                                            ?>
                                            <span class="badge bg-<?= $reopen_type_info['color'] ?>">
                                                <?= $reopen_type_info['name'] ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($repair['reopen_reason']): ?>
                                        <div class="col-12 mt-2">
                                            <small class="text-muted d-block mb-1">Motivo:</small>
                                            <span><?= htmlspecialchars(formatReopenReason($repair['reopen_reason'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Garantía Normal (Sin reapertura) -->
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
                                            <?= formatWarrantyStatusEnhanced($repair) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($warranty_info['delivered_at']): ?>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            Entregado: <?= formatDateTime($warranty_info['delivered_at']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-x me-1"></i>
                                            Garantía expira:
                                            <?= date('d/m/Y', strtotime($warranty_info['warranty_expires_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
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
                            <!-- Recibido -->
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <i class="bi bi-inbox me-2"></i>Recibido
                                    </div>
                                    <div class="timeline-date">
                                        <?= formatDateTime($repair['received_at']) ?>
                                    </div>
                                    <small class="text-muted">
                                        Por <?= htmlspecialchars($repair['created_by_name']) ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Completado primera vez -->
                            <?php if ($repair['completed_at']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <i class="bi bi-check-circle me-2"></i>Completado
                                        </div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['completed_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Entregado primera vez -->
                            <?php if ($repair['delivered_at'] || $repair['original_delivered_at']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <i class="bi bi-hand-thumbs-up me-2"></i>Entregado
                                        </div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['original_delivered_at'] ?? $repair['delivered_at']) ?>
                                        </div>
                                        <?php if ($repair['delivered_by']): ?>
                                            <small class="text-muted">
                                                Por <?= htmlspecialchars($repair['delivered_by']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($warranty_info['original_warranty_days']): ?>
                                            <div class="mt-1">
                                                <small class="badge bg-info">Garantía: <?= $warranty_info['original_warranty_days'] ?> días</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Reabierto -->
                            <?php if ($repair['reopen_date']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title fw-bold text-warning">
                                            <i class="bi bi-arrow-clockwise me-2"></i>REABIERTO POR GARANTÍA
                                        </div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['reopen_date']) ?>
                                        </div>
                                        <?php if ($repair['reopen_type']): ?>
                                            <?php
                                            $reopen_config = getConfig('reopen_types');
                                            $reopen_type_info = $reopen_config[$repair['reopen_type']];
                                            ?>
                                            <div class="mt-2">
                                                <small class="badge bg-<?= $reopen_type_info['color'] ?>">
                                                    <?= $reopen_type_info['name'] ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($repair['reopen_reason']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <strong>Motivo:</strong> <?= htmlspecialchars(formatReopenReason($repair['reopen_reason'])) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($repair['reopen_notes']): ?>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($repair['reopen_notes']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Completado después de reapertura -->
                            <?php if (!empty($repair['reopen_completed_at'])): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <i class="bi bi-check-circle me-2"></i>Completado nuevamente
                                        </div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['reopen_completed_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Entregado después de reapertura -->
                            <?php if (!empty($repair['reopen_delivered_at'])): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <i class="bi bi-hand-thumbs-up me-2"></i>Re-entregado
                                        </div>
                                        <div class="timeline-date">
                                            <?= formatDateTime($repair['reopen_delivered_at']) ?>
                                        </div>
                                        <?php if (!empty($repair['reopen_warranty_days'])): ?>
                                            <div class="mt-1">
                                                <small class="badge bg-success">Nueva garantía: <?= $repair['reopen_warranty_days'] ?> días</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Duración -->
                        <div class="duration-info mt-3">
                            <div class="row g-2">
                                <?php if ($repair['is_reopened']): ?>
                                    <!-- Duración Actual (desde reapertura) -->
                                    <div class="col-6">
                                        <div class="p-3 bg-info bg-opacity-10 rounded">
                                            <div class="text-center">
                                                <div class="fw-bold text-info">
                                                    <?= formatDurationSpanish($current_duration) ?>
                                                </div>
                                                <small class="text-muted">Duración Actual</small>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Duración Total -->
                                    <div class="col-6">
                                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                                            <div class="text-center">
                                                <div class="fw-bold text-primary">
                                                    <?= formatDurationSpanish($total_duration) ?>
                                                </div>
                                                <small class="text-muted">Duración Total</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Solo duración total si no hay reapertura -->
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded">
                                            <div class="text-center">
                                                <div class="fw-bold text-primary">
                                                    <?= formatDurationSpanish($current_duration) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $repair['status'] === 'delivered' ? 'Duración total' : 'Días transcurridos' ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
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

    <!-- Modal para agregar repuesto -->
<?php if ($spare_parts_permissions['use_spare_parts'] && $repair['status'] !== 'delivered'): ?>
    <div class="modal fade" id="addSparePartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Repuesto a la Reparación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- بحث قطع الغيار -->
                    <div class="mb-3">
                        <label class="form-label">Buscar Repuesto</label>
                        <input type="text"
                               class="form-control"
                               id="modalSparePartSearch"
                               placeholder="Buscar por nombre o código...">
                    </div>

                    <!-- filtros -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Filtrar por categoría</label>
                            <select class="form-select" id="modalCategoryFilter">
                                <option value="">Todas las categorías</option>
                                <option value="pantalla">Pantalla</option>
                                <option value="bateria">Batería</option>
                                <option value="camara">Cámara</option>
                                <option value="altavoz">Altavoz</option>
                                <option value="conector">Conector</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Compatible con</label>
                            <div class="form-control-plaintext">
                                <strong><?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- نتائج البحث -->
                    <div id="modalSparePartResults">
                        <div class="text-center text-muted">
                            <i class="bi bi-search"></i>
                            <p>Escribe para buscar repuestos compatibles</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Modal para eliminar repuesto -->
    <div class="modal fade" id="removeSparePartModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar Repuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="removeSparePartForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="spare_part_action" value="remove_spare_part">
                        <input type="hidden" name="usage_id" id="removeUsageId">

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ¿Estás seguro de que quieres eliminar este repuesto de la reparación?
                        </div>

                        <div class="text-center">
                            <strong id="removePartName"></strong>
                        </div>

                        <div class="mt-3 p-3 bg-info bg-opacity-10 rounded">
                            <small class="text-info">
                                <i class="bi bi-info-circle me-1"></i>
                                El stock del repuesto será restaurado automáticamente.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Eliminar Repuesto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modales originales (actualizar coste, entrega, reapertura) -->
    <div class="modal fade" id="costModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-currency-euro me-2"></i>Actualizar Coste Real
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_cost">

                        <!-- معلومات قطع الغيار إن وجدت -->
                        <?php if (!empty($used_spare_parts)): ?>
                            <div class="alert alert-info">
                                <h6><i class="bi bi-gear me-2"></i>Repuestos incluidos:</h6>
                                <div class="small">
                                    Total repuestos: <strong>€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="actual_cost" class="form-label">Coste Total Final (€)</label>
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
                                <?php if (!empty($used_spare_parts)): ?>
                                    Incluye repuestos (€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?>) + mano de obra.
                                <?php else: ?>
                                    Coste total de la reparación. Deja en blanco o 0 si no hubo coste.
                                <?php endif; ?>
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
    <div class="modal fade" id="deliveryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-hand-thumbs-up me-2"></i>Marcar como Entregado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
    <div class="modal fade" id="reopenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reabrir Reparación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
        /* أنماط محسنة لقطع الغيار */
        .spare-parts-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1rem;
        }

        .breakdown-item {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6 !important;
        }

        .breakdown-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .cost-item {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            background: rgba(0, 123, 255, 0.05);
        }

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

        .spare-part-row {
            transition: all 0.3s ease;
        }

        .spare-part-row:hover {
            background-color: #f8f9fa;
        }

        .warranty-status-card {
            border: 2px solid;
            transition: all 0.3s ease;
        }

        .warranty-status-card:hover {
            transform: scale(1.02);
        }

        /* تحسينات الجوال */
        @media (max-width: 768px) {
            .breakdown-item {
                margin-bottom: 0.5rem;
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

            .btn-group {
                width: 100%;
                flex-direction: column;
            }

            .btn-group .btn {
                margin-bottom: 0.25rem;
            }
        }

        /* أنماط الجداول المحسنة */
        .table-hover tbody tr:hover {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .table thead th {
            border-bottom: 2px solid #0dcaf0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .table tfoot th {
            border-top: 2px solid #0dcaf0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        /* أنماط الكروت */
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

        /* أنماط البحث في المودال */
        .modal-dialog.modal-lg {
            max-width: 800px;
        }

        #modalSparePartResults .list-group-item {
            transition: all 0.3s ease;
        }

        #modalSparePartResults .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        /* تحسينات الأنيميشن */
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
        // متغيرات عامة
        const repairId = <?= $repair['id'] ?>;
        const canUseSpareParts = <?= json_encode($spare_parts_permissions['use_spare_parts']) ?>;
        const brandId = <?= $repair['brand_id'] ?>;
        const modelId = <?= $repair['model_id'] ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // إعداد بحث قطع الغيار في المودال
            if (canUseSpareParts) {
                setupModalSparePartSearch();
            }

            // إعداد المؤقتات والتحديث التلقائي
            setupAutoRefresh();

            // إعداد أزرار الطباعة
            setupPrintButtons();
        });

        // إعداد بحث قطع الغيار في المودال
        function setupModalSparePartSearch() {
            const searchInput = document.getElementById('modalSparePartSearch');
            const categoryFilter = document.getElementById('modalCategoryFilter');
            const resultsContainer = document.getElementById('modalSparePartResults');
            let searchTimeout;

            function performSearch() {
                const searchTerm = searchInput.value.trim();
                const category = categoryFilter.value;

                if (searchTerm.length < 2 && !category) {
                    resultsContainer.innerHTML = `
                        <div class="text-center text-muted">
                            <i class="bi bi-search"></i>
                            <p>Escribe al menos 2 caracteres para buscar</p>
                        </div>
                    `;
                    return;
                }

                // عرض مؤشر التحميل
                resultsContainer.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Buscando...</span>
                        </div>
                        <p class="mt-2">Buscando repuestos compatibles...</p>
                    </div>
                `;

                let url = `<?= url('api/spare_parts.php') ?>?action=search&brand_id=${brandId}&model_id=${modelId}&limit=20`;

                if (searchTerm) {
                    url += `&term=${encodeURIComponent(searchTerm)}`;
                }

                if (category) {
                    url += `&category=${encodeURIComponent(category)}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.parts) {
                            displayModalSparePartResults(data.data.parts);
                        } else {
                            resultsContainer.innerHTML = `
                                <div class="text-center text-muted">
                                    <i class="bi bi-exclamation-circle"></i>
                                    <p>No se encontraron repuestos compatibles</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error searching spare parts:', error);
                        resultsContainer.innerHTML = `
                            <div class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                <p>Error al buscar repuestos</p>
                            </div>
                        `;
                    });
            }

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            categoryFilter.addEventListener('change', performSearch);

            // بحث تلقائي عند فتح المودال
            document.getElementById('addSparePartModal').addEventListener('shown.bs.modal', function() {
                if (searchInput.value.length >= 2 || categoryFilter.value) {
                    performSearch();
                } else {
                    // عرض القطع المتوافقة بشكل افتراضي
                    performSearch();
                }
            });
        }

        // عرض نتائج البحث في المودال
        function displayModalSparePartResults(parts) {
            const resultsContainer = document.getElementById('modalSparePartResults');

            if (parts.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="bi bi-inbox"></i>
                        <p>No hay repuestos disponibles para este dispositivo</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="list-group">';

            parts.forEach(part => {
                const isAvailable = part.stock_status !== 'out_of_stock' && part.stock_quantity > 0;
                const stockBadge = getStockBadge(part.stock_status, part.stock_quantity);

                html += `
                    <div class="list-group-item ${!isAvailable ? 'list-group-item-secondary' : ''}" data-part-id="${part.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${part.part_name}</h6>
                                ${part.part_code ? `<small class="text-muted">Código: ${part.part_code}</small><br>` : ''}
                                <span class="badge bg-secondary">${formatSparePartCategory(part.category)}</span>
                                <span class="badge bg-primary ms-1">€${parseFloat(part.total_price).toFixed(2)}</span>
                                ${stockBadge}
                                ${part.warranty_days ? `<span class="badge bg-success ms-1">${part.warranty_days} días garantía</span>` : ''}
                            </div>
                            <div class="ms-3">
                                ${isAvailable ?
                    `<button type="button" class="btn btn-sm btn-outline-primary"
                                             onclick="selectSparePartForRepair(${part.id}, '${part.part_name.replace(/'/g, "\\'")}', ${part.total_price})">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>` :
                    `<button type="button" class="btn btn-sm btn-secondary" disabled>
                                        Sin stock
                                    </button>`
                }
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            resultsContainer.innerHTML = html;
        }

        // اختيار قطعة غيار للإصلاح
        function selectSparePartForRepair(partId, partName, unitPrice) {
            // إنشاء form للإرسال
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const fields = {
                'csrf_token': '<?= generateCSRFToken() ?>',
                'spare_part_action': 'add_spare_part',
                'spare_part_id': partId,
                'quantity': 1,
                'unit_price': unitPrice
            };

            Object.keys(fields).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        // حذف قطعة غيار من الإصلاح
        function removeRepairSparePart(usageId, partName) {
            document.getElementById('removeUsageId').value = usageId;
            document.getElementById('removePartName').textContent = partName;

            const modal = new bootstrap.Modal(document.getElementById('removeSparePartModal'));
            modal.show();
        }

        // إعداد أزرار الطباعة
        function setupPrintButtons() {
            window.printTicket = function(repairId) {
                const printWindow = window.open(`<?= url('pages/print_selector.php?id=') ?>${repairId}`, '_blank');
                if (printWindow) {
                    printWindow.focus();
                } else {
                    showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
                }
            };

            window.printSparePartsInvoice = function(repairId) {
                const printWindow = window.open(`<?= url('pages/print_spare_parts_invoice.php?id=') ?>${repairId}`, '_blank');
                if (printWindow) {
                    printWindow.focus();
                } else {
                    showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
                }
            };
        }

        // إعداد التحديث التلقائي
        function setupAutoRefresh() {
            <?php if ($repair['status'] !== 'delivered'): ?>
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    checkForUpdates();
                }
            }, 30000);
            <?php endif; ?>
        }

        // التحقق من التحديثات
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

        // مودال إعادة الفتح
        function showReopenModal(type) {
            const modal = new bootstrap.Modal(document.getElementById('reopenModal'));
            const reopenTypeInput = document.getElementById('reopen_type_input');
            const infoText = document.getElementById('reopen_info_text');
            const infoAlert = document.getElementById('reopen_info_alert');
            const submitBtn = document.getElementById('reopen_submit_btn');

            reopenTypeInput.value = type;

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

        // دوال مساعدة
        function getStockBadge(status, quantity) {
            switch (status) {
                case 'available':
                    return `<span class="badge bg-success">Disponible (${quantity})</span>`;
                case 'order_required':
                    return `<span class="badge bg-warning">Necesita pedido (${quantity})</span>`;
                case 'out_of_stock':
                    return `<span class="badge bg-danger">Sin stock</span>`;
                default:
                    return `<span class="badge bg-secondary">-</span>`;
            }
        }

        function formatSparePartCategory(category) {
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

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // atajos de teclado
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                window.location.href = '<?= url('pages/edit_repair.php?id=' . $repair['id']) ?>';
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printTicket(<?= $repair['id'] ?>);
            }

            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>