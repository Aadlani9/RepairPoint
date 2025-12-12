<?php
/**
 * RepairPoint - تشخيص نظام الفواتير
 * صفحة للتحقق من البيانات وتشخيص المشاكل
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

// جمع معلومات التشخيص
$diagnostics = [];

// 1. التحقق من الجداول
$tables = ['customers', 'invoices', 'invoice_items'];
$diagnostics['tables'] = [];
foreach ($tables as $table) {
    $result = $db->selectOne("SHOW TABLES LIKE ?", [$table]);
    $diagnostics['tables'][$table] = ($result !== false && !empty($result));
}

// 2. عد السجلات
$diagnostics['counts'] = [];
if ($diagnostics['tables']['customers']) {
    $result = $db->selectOne("SELECT COUNT(*) as count FROM customers WHERE shop_id = ?", [$shop_id]);
    $diagnostics['counts']['customers'] = $result ? $result['count'] : 0;
}
if ($diagnostics['tables']['invoices']) {
    $result = $db->selectOne("SELECT COUNT(*) as count FROM invoices WHERE shop_id = ?", [$shop_id]);
    $diagnostics['counts']['invoices'] = $result ? $result['count'] : 0;
}
if ($diagnostics['tables']['invoice_items']) {
    $result = $db->selectOne("SELECT COUNT(*) as count FROM invoice_items");
    $diagnostics['counts']['invoice_items'] = $result ? $result['count'] : 0;
}

// 3. الزبائن الحاليين
$diagnostics['customers'] = [];
if ($diagnostics['tables']['customers']) {
    $customers = $db->select("SELECT id, full_name, phone FROM customers WHERE shop_id = ? LIMIT 10", [$shop_id]);
    $diagnostics['customers'] = $customers ?: [];
}

// 4. الفواتير الحالية
$diagnostics['invoices'] = [];
if ($diagnostics['tables']['invoices']) {
    $invoices = $db->select("SELECT id, invoice_number, customer_id, total FROM invoices WHERE shop_id = ? LIMIT 10", [$shop_id]);
    $diagnostics['invoices'] = $invoices ?: [];
}

// 5. معلومات الجلسة
$diagnostics['session'] = [
    'user_id' => $_SESSION['user_id'],
    'shop_id' => $_SESSION['shop_id'],
    'user_role' => $_SESSION['user_role']
];

$page_title = 'تشخيص نظام الفواتير';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-tools text-primary"></i> تشخيص نظام الفواتير
            </h2>

            <!-- حالة الجداول -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-table"></i> حالة الجداول</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الجدول</th>
                                <th>الحالة</th>
                                <th>عدد السجلات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): ?>
                                <tr>
                                    <td><code><?= $table ?></code></td>
                                    <td>
                                        <?php if ($diagnostics['tables'][$table]): ?>
                                            <span class="badge bg-success">✓ موجود</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ غير موجود</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($diagnostics['counts'][$table])): ?>
                                            <strong><?= $diagnostics['counts'][$table] ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- معلومات الجلسة -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> معلومات الجلسة</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>User ID:</strong></td>
                            <td><?= $diagnostics['session']['user_id'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Shop ID:</strong></td>
                            <td><?= $diagnostics['session']['shop_id'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Role:</strong></td>
                            <td><span class="badge bg-primary"><?= $diagnostics['session']['user_role'] ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- الزبائن -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-people"></i> الزبائن (آخر 10)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($diagnostics['customers'])): ?>
                        <div class="alert alert-warning mb-0">
                            لا يوجد زبائن مسجلين
                        </div>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnostics['customers'] as $customer): ?>
                                    <tr>
                                        <td><?= $customer['id'] ?></td>
                                        <td><?= htmlspecialchars($customer['full_name']) ?></td>
                                        <td><?= htmlspecialchars($customer['phone']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- الفواتير -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> الفواتير (آخر 10)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($diagnostics['invoices'])): ?>
                        <div class="alert alert-warning mb-0">
                            لا يوجد فواتير مسجلة
                        </div>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>رقم الفاتورة</th>
                                    <th>ID الزبون</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnostics['invoices'] as $invoice): ?>
                                    <tr>
                                        <td><?= $invoice['id'] ?></td>
                                        <td><code><?= htmlspecialchars($invoice['invoice_number']) ?></code></td>
                                        <td><?= $invoice['customer_id'] ?></td>
                                        <td>€<?= number_format($invoice['total'], 2) ?></td>
                                        <td>
                                            <a href="<?= url('pages/invoice_details.php?id=' . $invoice['id']) ?>" class="btn btn-sm btn-primary">
                                                عرض
                                            </a>
                                            <a href="<?= url('pages/print_invoice_pdf.php?id=' . $invoice['id']) ?>" class="btn btn-sm btn-danger" target="_blank">
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- الإجراءات -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-wrench"></i> الإجراءات</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= url('pages/customers.php') ?>" class="btn btn-primary">
                            <i class="bi bi-people"></i> إدارة الزبائن
                        </a>
                        <a href="<?= url('pages/invoices_reports.php') ?>" class="btn btn-info">
                            <i class="bi bi-graph-up"></i> التقارير المالية
                        </a>
                        <a href="<?= url('pages/install_invoicing.php') ?>" class="btn btn-warning">
                            <i class="bi bi-download"></i> صفحة التثبيت
                        </a>
                        <a href="<?= url('pages/dashboard.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-house"></i> لوحة التحكم
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
