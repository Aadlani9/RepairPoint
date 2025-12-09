<?php
/**
 * Fix Spare Parts Stock Quantity
 * صفحة مؤقتة لإصلاح stock_quantity
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación والصلاحيات (Admin فقط)
authMiddleware();
$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    die('❌ غير مصرح لك بالوصول إلى هذه الصفحة');
}

$page_title = 'إصلاح Stock Quantity';
$fixed = false;
$errors = [];
$stats = null;

// تنفيذ الإصلاح
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_now'])) {
    try {
        $db = getDB();

        // 1. عرض القطع التي لديها stock_quantity = NULL قبل الإصلاح
        $null_parts = $db->select("SELECT id, part_name, stock_quantity, stock_status FROM spare_parts WHERE stock_quantity IS NULL");

        // 2. تحديث NULL إلى 0
        $updated = $db->update("UPDATE spare_parts SET stock_quantity = 0 WHERE stock_quantity IS NULL");

        // 3. تحديث stock_status للقطع بكمية 0 لتكون متاحة
        $status_updated = $db->update("UPDATE spare_parts SET stock_status = 'available' WHERE stock_quantity = 0");

        // 4. الحصول على الإحصائيات
        $stats = $db->selectOne("SELECT
            COUNT(*) as total_parts,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as parts_with_zero_stock,
            SUM(CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END) as parts_with_stock,
            SUM(CASE WHEN stock_status = 'available' THEN 1 ELSE 0 END) as available_parts
        FROM spare_parts");

        $stats['updated'] = $updated;
        $stats['status_updated'] = $status_updated;
        $stats['null_parts_before'] = $null_parts;

        $fixed = true;

    } catch (Exception $e) {
        $errors[] = 'خطأ: ' . $e->getMessage();
    }
}

require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">
                        <i class="bi bi-tools me-2"></i>
                        إصلاح Stock Quantity لقطع الغيار
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($fixed): ?>
                        <!-- عرض النتائج -->
                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle me-2"></i>تم الإصلاح بنجاح!</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>التحديثات:</h6>
                                    <ul>
                                        <li>تم تحديث <strong><?= $stats['updated'] ?></strong> قطعة من NULL إلى 0</li>
                                        <li>تم تحديث حالة <strong><?= $stats['status_updated'] ?></strong> قطعة</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>الإحصائيات النهائية:</h6>
                                    <ul>
                                        <li>إجمالي القطع: <strong><?= $stats['total_parts'] ?></strong></li>
                                        <li>قطع بكمية 0: <strong><?= $stats['parts_with_zero_stock'] ?></strong></li>
                                        <li>قطع بكمية > 0: <strong><?= $stats['parts_with_stock'] ?></strong></li>
                                        <li>قطع متاحة: <strong><?= $stats['available_parts'] ?></strong></li>
                                    </ul>
                                </div>
                            </div>

                            <?php if (!empty($stats['null_parts_before'])): ?>
                                <hr>
                                <h6>القطع التي تم إصلاحها:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>اسم القطعة</th>
                                                <th>الحالة السابقة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['null_parts_before'] as $part): ?>
                                                <tr>
                                                    <td><?= $part['id'] ?></td>
                                                    <td><?= htmlspecialchars($part['part_name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">NULL</span>
                                                        →
                                                        <span class="badge bg-success">0</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <hr>
                            <a href="<?= url('pages/spare_parts.php') ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>
                                العودة إلى قطع الغيار
                            </a>
                        </div>

                    <?php elseif (!empty($errors)): ?>
                        <!-- عرض الأخطاء -->
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-exclamation-triangle me-2"></i>حدث خطأ</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                    <?php else: ?>
                        <!-- نموذج الإصلاح -->
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle me-2"></i>ماذا ستفعل هذه العملية؟</h5>
                            <ol>
                                <li>تحديث جميع القطع التي لديها <code>stock_quantity = NULL</code> إلى <code>0</code></li>
                                <li>تحديث حالة المخزون للقطع بكمية 0 لتكون <code>available</code></li>
                                <li>السماح بإضافة القطع في الإصلاحات بدون التحقق من المخزون</li>
                            </ol>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>ملاحظة:</strong> هذه العملية آمنة ولن تحذف أي بيانات
                        </div>

                        <form method="POST">
                            <button type="submit" name="fix_now" class="btn btn-warning btn-lg">
                                <i class="bi bi-tools me-2"></i>
                                إصلاح الآن
                            </button>
                            <a href="<?= url('pages/spare_parts.php') ?>" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>
                                إلغاء
                            </a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
