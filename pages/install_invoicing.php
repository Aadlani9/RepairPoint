<?php
/**
 * RepairPoint - تثبيت نظام الفواتير
 * صفحة تحقق وتثبيت تلقائي لنظام الفواتير
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
$errors = [];
$success = [];
$warnings = [];

// التحقق من وجود الجداول
function checkTableExists($db, $tableName) {
    $result = $db->selectOne("SHOW TABLES LIKE ?", [$tableName]);
    return $result !== false && !empty($result);
}

// تطبيق الـ migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // قراءة ملف SQL
        $sqlFile = ROOT_PATH . 'sql/migrations/invoicing_system.sql';

        if (!file_exists($sqlFile)) {
            throw new Exception('ملف SQL غير موجود: ' . $sqlFile);
        }

        $sql = file_get_contents($sqlFile);

        // إزالة السطر USE repairpoint لأننا متصلين بالفعل
        $sql = preg_replace('/^USE\s+\w+;/mi', '', $sql);

        // تقسيم إلى استعلامات منفصلة
        $statements = array_filter(
            array_map('trim', preg_split('/;\s*$/m', $sql)),
            function($stmt) {
                return !empty($stmt) &&
                       !preg_match('/^(--|\/\*|CREATE\s+DATABASE)/', $stmt);
            }
        );

        $executed = 0;
        $failed = 0;

        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;

            try {
                // تنفيذ الاستعلام مباشرة
                $conn = $db->getConnection();
                $conn->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // تجاهل أخطاء "already exists"
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $failed++;
                    $warnings[] = "خطأ في تنفيذ الاستعلام: " . substr($statement, 0, 100) . "... - " . $e->getMessage();
                }
            }
        }

        if ($failed === 0) {
            $success[] = "تم تثبيت نظام الفواتير بنجاح! ($executed استعلام)";
        } else {
            $warnings[] = "تم التثبيت مع بعض التحذيرات. ($executed نجح، $failed فشل)";
        }

    } catch (Exception $e) {
        $errors[] = "خطأ في التثبيت: " . $e->getMessage();
    }
}

// التحقق من الجداول
$tables_status = [
    'customers' => checkTableExists($db, 'customers'),
    'invoices' => checkTableExists($db, 'invoices'),
    'invoice_items' => checkTableExists($db, 'invoice_items'),
];

$all_tables_exist = !in_array(false, $tables_status);

$page_title = 'تثبيت نظام الفواتير';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 mb-3">
                    <i class="bi bi-download text-primary"></i>
                    تثبيت نظام الفواتير
                </h1>
                <p class="lead text-muted">
                    تحقق من حالة قاعدة البيانات وقم بتثبيت الجداول المطلوبة
                </p>
            </div>

            <!-- رسائل النجاح -->
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>نجاح!</strong> <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <!-- رسائل الأخطاء -->
            <?php foreach ($errors as $msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <strong>خطأ!</strong> <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <!-- رسائل التحذيرات -->
            <?php foreach ($warnings as $msg): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>تحذير!</strong> <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <!-- حالة الجداول -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-table me-2"></i>
                        حالة جداول قاعدة البيانات
                    </h4>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($tables_status as $table => $exists): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-table me-2"></i>
                                    <strong><?= htmlspecialchars($table) ?></strong>
                                </div>
                                <?php if ($exists): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> موجود
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> غير موجود
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($all_tables_exist): ?>
                        <div class="alert alert-success mt-4 mb-0">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>رائع!</strong> جميع جداول نظام الفواتير موجودة ومثبتة بشكل صحيح.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4 mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>تنبيه!</strong> بعض الجداول غير موجودة. يجب تثبيت نظام الفواتير.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- زر التثبيت -->
            <?php if (!$all_tables_exist): ?>
                <div class="card shadow">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0">
                            <i class="bi bi-download me-2"></i>
                            تثبيت تلقائي
                        </h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            سيتم تطبيق ملف SQL التالي:
                            <code>sql/migrations/invoicing_system.sql</code>
                        </p>
                        <p class="text-muted mb-4">
                            هذا سيقوم بإنشاء جميع الجداول، Views، و Triggers اللازمة لنظام الفواتير.
                        </p>

                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من تثبيت نظام الفواتير؟')">
                            <input type="hidden" name="action" value="install">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-download me-2"></i>
                                تثبيت نظام الفواتير الآن
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-grid gap-2">
                    <a href="<?= url('pages/customers.php') ?>" class="btn btn-success btn-lg">
                        <i class="bi bi-arrow-right-circle me-2"></i>
                        الانتقال إلى إدارة الزبائن
                    </a>
                    <a href="<?= url('pages/invoices_reports.php') ?>" class="btn btn-info btn-lg">
                        <i class="bi bi-graph-up me-2"></i>
                        عرض التقارير المالية
                    </a>
                    <a href="<?= url('pages/dashboard.php') ?>" class="btn btn-secondary">
                        <i class="bi bi-house me-2"></i>
                        العودة إلى لوحة التحكم
                    </a>
                </div>
            <?php endif; ?>

            <!-- معلومات إضافية -->
            <div class="card mt-4 border-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle text-info me-2"></i>
                        ملاحظات مهمة
                    </h5>
                    <ul class="mb-0">
                        <li>يمكنك أيضاً تطبيق Migration يدوياً من phpMyAdmin</li>
                        <li>الملف موجود في: <code>sql/migrations/invoicing_system.sql</code></li>
                        <li>تأكد من عمل نسخة احتياطية قبل التثبيت</li>
                        <li>في حالة وجود مشاكل، راجع ملف: <code>sql/migrations/README_INVOICING.md</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
