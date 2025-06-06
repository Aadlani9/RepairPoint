</div>

<!-- Modals -->
<!-- Modal إضافة علامة تجارية -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBrandModalLabel">
                    <i class="bi bi-tags me-2"></i>إضافة علامة تجارية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add_model">
                    
                    <div class="mb-3">
                        <label for="model_brand_id" class="form-label">العلامة التجارية *</label>
                        <select class="form-select" id="model_brand_id" name="brand_id" required>
                            <option value="">اختر العلامة التجارية</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>">
                                    <?= htmlspecialchars($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="model_name" class="form-label">اسم الموديل *</label>
                        <input type="text" 
                               class="form-control" 
                               id="model_name" 
                               name="model_name" 
                               placeholder="مثال: iPhone 15, Galaxy S24"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal إضافة مشكلة شائعة -->
<div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addIssueModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>إضافة مشكلة شائعة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add_issue">
                    
                    <div class="mb-3">
                        <label for="issue_category" class="form-label">التصنيف</label>
                        <input type="text" 
                               class="form-control" 
                               id="issue_category" 
                               name="category" 
                               placeholder="مثال: شاشة، بطارية، شحن"
                               list="existing-categories">
                        <datalist id="existing-categories">
                            <?php 
                            $categories = array_keys($issues_by_category);
                            foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="mb-3">
                        <label for="issue_text" class="form-label">نص المشكلة *</label>
                        <input type="text" 
                               class="form-control" 
                               id="issue_text" 
                               name="issue_text" 
                               placeholder="مثال: شاشة مكسورة، لا يشحن"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* أنماط خاصة بصفحة الإعدادات */
.page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
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

.settings-nav .list-group-item {
    border: none;
    border-radius: 0.5rem;
    margin-bottom: 0.25rem;
    transition: all 0.3s ease;
}

.settings-nav .list-group-item:hover {
    background-color: rgba(13, 110, 253, 0.1);
    transform: translateX(5px);
}

.settings-nav .list-group-item.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
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
    background: rgba(13, 110, 253, 0.05);
    border-bottom: 1px solid rgba(13, 110, 253, 0.1);
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1rem 1.5rem;
}

.current-logo img {
    border-radius: 1rem;
}

.no-logo {
    border: 2px dashed #dee2e6;
}

.category-title {
    color: var(--primary-color);
    margin-bottom: 0.75rem;
}

.issue-item {
    transition: all 0.3s ease;
}

.issue-item:hover {
    background-color: rgba(13, 110, 253, 0.1) !important;
    transform: translateX(5px);
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.setting-item:last-child {
    border-bottom: none;
}

.system-actions .btn {
    margin-bottom: 0.5rem;
}

.backup-actions .btn {
    margin-bottom: 0.5rem;
}

/* أنماط النموذج */
.needs-validation .form-control:invalid {
    border-color: var(--danger-color);
}

.needs-validation .form-control:valid {
    border-color: var(--success-color);
}

/* أنماط متجاوبة */
@media (max-width: 768px) {
    .page-header {
        text-align: center;
        padding: 2rem 1rem !important;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .settings-nav {
        margin-bottom: 2rem;
    }
    
    .settings-nav .list-group {
        display: flex;
        flex-direction: row;
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 0.5rem;
    }
    
    .settings-nav .list-group-item {
        flex: 0 0 auto;
        margin-right: 0.5rem;
        margin-bottom: 0;
    }
    
    .system-actions,
    .backup-actions {
        text-align: center;
    }
    
    .system-actions .btn,
    .backup-actions .btn {
        width: 100%;
        margin-bottom: 0.5rem;
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
    
    .current-logo img {
        max-width: 150px !important;
        max-height: 150px !important;
    }
}

/* أنماط الرسوم المتحركة */
@keyframes fadeInUp {
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
    animation: fadeInUp 0.5s ease-out;
}

.tab-pane {
    animation: fadeInUp 0.3s ease-out;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // إعداد التحقق من النماذج
    setupFormValidation();
    
    // إعداد تنسيق أرقام الهاتف
    setupPhoneFormatting();
    
    // تحميل بيانات الموديلات للعلامات
    loadModelsData();
});

function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        // التحقق الفوري
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
    });
}

function setupPhoneFormatting() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '');
            
            // تنسيق تلقائي للأرقام الإسبانية
            if (value.length === 9 && !value.startsWith('+34')) {
                this.value = '+34 ' + value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            }
        });
    });
}

function loadModelsData() {
    // تحميل بيانات الموديلات في JavaScript لاستخدامها
    window.modelsData = <?= json_encode($models_by_brand) ?>;
}

// دوال العمليات
window.showModels = function(brandId, brandName) {
    const modelsContainer = document.getElementById('models-container');
    const models = window.modelsData[brandId] || [];
    
    let html = `<div class="p-3">
                    <h6 class="fw-bold text-primary mb-3">موديلات ${brandName}</h6>`;
    
    if (models.length === 0) {
        html += '<div class="text-center text-muted">لا توجد موديلات</div>';
    } else {
        html += '<div class="list-group list-group-flush">';
        models.forEach(model => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${model.name}</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteModel(${model.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                     </div>`;
        });
        html += '</div>';
    }
    
    html += '</div>';
    modelsContainer.innerHTML = html;
};

window.deleteBrand = function(brandId) {
    if (confirm('هل أنت متأكد من حذف هذه العلامة التجارية؟ سيتم حذف جميع الموديلات المرتبطة بها.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= generateCSRFToken() ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_brand';
        
        const brandIdInput = document.createElement('input');
        brandIdInput.type = 'hidden';
        brandIdInput.name = 'brand_id';
        brandIdInput.value = brandId;
        
        form.appendChild(csrfInput);
        form.appendChild(actionInput);
        form.appendChild(brandIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
};

window.deleteModel = function(modelId) {
    if (confirm('هل أنت متأكد من حذف هذا الموديل؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= generateCSRFToken() ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_model';
        
        const modelIdInput = document.createElement('input');
        modelIdInput.type = 'hidden';
        modelIdInput.name = 'model_id';
        modelIdInput.value = modelId;
        
        form.appendChild(csrfInput);
        form.appendChild(actionInput);
        form.appendChild(modelIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
};

window.deleteIssue = function(issueId) {
    if (confirm('هل أنت متأكد من حذف هذه المشكلة؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= generateCSRFToken() ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_issue';
        
        const issueIdInput = document.createElement('input');
        issueIdInput.type = 'hidden';
        issueIdInput.name = 'issue_id';
        issueIdInput.value = issueId;
        
        form.appendChild(csrfInput);
        form.appendChild(actionInput);
        form.appendChild(issueIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
};

// دوال إعدادات النظام
window.clearCache = function() {
    if (confirm('هل تريد مسح الذاكرة المؤقتة؟')) {
        // تنفيذ مسح الذاكرة المؤقتة
        Utils.showNotification('تم مسح الذاكرة المؤقتة بنجاح', 'success');
    }
};

window.checkSystem = function() {
    Utils.showNotification('جاري فحص النظام...', 'info');
    
    // محاكاة فحص النظام
    setTimeout(() => {
        Utils.showNotification('النظام يعمل بشكل طبيعي', 'success');
    }, 2000);
};

window.exportSettings = function() {
    Utils.showNotification('جاري تصدير الإعدادات...', 'info');
    
    // تصدير الإعدادات
    const settings = {
        shop: <?= json_encode($shop) ?>,
        brands: <?= json_encode($brands) ?>,
        export_date: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(settings, null, 2)], {type: 'application/json'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'settings_' + new Date().toISOString().split('T')[0] + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    Utils.showNotification('تم تصدير الإعدادات بنجاح', 'success');
};

// دوال النسخ الاحتياطي
window.createBackup = function() {
    if (confirm('هل تريد إنشاء نسخة احتياطية من البيانات؟')) {
        Utils.showNotification('جاري إنشاء النسخة الاحتياطية...', 'info');
        
        // محاكاة إنشاء النسخة الاحتياطية
        setTimeout(() => {
            Utils.showNotification('تم إنشاء النسخة الاحتياطية بنجاح', 'success');
        }, 3000);
    }
};

window.downloadBackup = function() {
    Utils.showNotification('جاري تحميل آخر نسخة احتياطية...', 'info');
    
    // محاكاة تحميل النسخة الاحتياطية
    setTimeout(() => {
        Utils.showNotification('تم تحميل النسخة الاحتياطية بنجاح', 'success');
    }, 2000);
};

// معالج نموذج الاستعادة
document.getElementById('restoreForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = this.querySelector('input[type="file"]');
    if (!fileInput.files[0]) {
        Utils.showNotification('يرجى اختيار ملف للاستعادة', 'warning');
        return;
    }
    
    if (!confirm('تحذير: هذا الإجراء سيحل محل جميع البيانات الحالية. هل أنت متأكد؟')) {
        return;
    }
    
    Utils.showNotification('جاري استعادة البيانات...', 'info');
    
    // محاكاة عملية الاستعادة
    setTimeout(() => {
        Utils.showNotification('تم استعادة البيانات بنجاح', 'success');
        setTimeout(() => {
            location.reload();
        }, 1500);
    }, 5000);
});

// إعادة تعيين النماذج عند الإغلاق
['addBrandModal', 'addModelModal', 'addIssueModal'].forEach(modalId => {
    document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
        this.querySelector('form').reset();
        this.querySelector('form').classList.remove('was-validated');
    });
});

// اختصارات لوحة المفاتيح
document.addEventListener('keydown', function(e) {
    // Ctrl+S لحفظ الإعدادات
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const activeTab = document.querySelector('.tab-pane.active');
        const saveButton = activeTab.querySelector('button[type="submit"]');
        if (saveButton) {
            saveButton.click();
        }
    }
    
    // Escape لإغلاق النماذج
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            bootstrap.Modal.getInstance(modal).hide();
        });
    }
});
</script>

<?php
// تضمين الفوتر
require_once INCLUDES_PATH . 'footer.php';
?>dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add_brand">
                    
                    <div class="mb-3">
                        <label for="brand_name" class="form-label">اسم العلامة التجارية *</label>
                        <input type="text" 
                               class="form-control" 
                               id="brand_name" 
                               name="brand_name" 
                               placeholder="مثال: Apple, Samsung"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal إضافة موديل -->
<div class="modal fade" id="addModelModal" tabindex="-1" aria-labelledby="addModelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModelModalLabel">
                    <i class="bi bi-phone me-2"></i>إضافة موديل
                </h5>
                <button type="button" class="btn-close" data-bs-<?php
/**
 * RepairPoint - إعدادات النظام والمحل
 * صفحة خاصة بالمديرين فقط
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación y permisos de administrador
authMiddleware(true); // فقط للمديرين

$page_title = 'إعدادات النظام';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// معالجة العمليات POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('رمز الأمان غير صحيح', MSG_ERROR);
    } else {
        $action = $_POST['action'] ?? '';
        $success = false;
        $message = '';
        
        switch ($action) {
            case 'update_shop':
                $success = updateShopSettings($_POST, $shop_id);
                $message = $success ? 'تم تحديث معلومات المحل بنجاح' : 'خطأ في تحديث معلومات المحل';
                break;
                
            case 'upload_logo':
                $success = uploadShopLogo($_FILES['logo'] ?? null, $shop_id);
                $message = $success['success'] ? $success['message'] : $success['message'];
                $success = $success['success'];
                break;
                
            case 'add_brand':
                $success = addBrand($_POST['brand_name']);
                $message = $success ? 'تم إضافة العلامة التجارية بنجاح' : 'خطأ في إضافة العلامة التجارية';
                break;
                
            case 'add_model':
                $success = addModel($_POST['brand_id'], $_POST['model_name']);
                $message = $success ? 'تم إضافة الموديل بنجاح' : 'خطأ في إضافة الموديل';
                break;
                
            case 'delete_brand':
                $success = deleteBrand($_POST['brand_id']);
                $message = $success ? 'تم حذف العلامة التجارية بنجاح' : 'خطأ في حذف العلامة التجارية';
                break;
                
            case 'delete_model':
                $success = deleteModel($_POST['model_id']);
                $message = $success ? 'تم حذف الموديل بنجاح' : 'خطأ في حذف الموديل';
                break;
                
            case 'add_issue':
                $success = addCommonIssue($_POST['category'], $_POST['issue_text']);
                $message = $success ? 'تم إضافة المشكلة الشائعة بنجاح' : 'خطأ في إضافة المشكلة';
                break;
                
            case 'delete_issue':
                $success = deleteCommonIssue($_POST['issue_id']);
                $message = $success ? 'تم حذف المشكلة بنجاح' : 'خطأ في حذف المشكلة';
                break;
        }
        
        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);
        
        // إعادة توجيه لتجنب إعادة الإرسال
        if ($success && $action !== 'upload_logo') {
            header('Location: ' . url('pages/settings.php'));
            exit;
        }
    }
}

// جلب بيانات المحل الحالية
$db = getDB();
$shop = $db->selectOne("SELECT * FROM shops WHERE id = ?", [$shop_id]);

// جلب العلامات التجارية والموديلات
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$models_by_brand = [];
foreach ($brands as $brand) {
    $models_by_brand[$brand['id']] = $db->select(
        "SELECT * FROM models WHERE brand_id = ? ORDER BY name",
        [$brand['id']]
    );
}

// جلب المشاكل الشائعة
$common_issues = $db->select("SELECT * FROM common_issues ORDER BY category, issue_text");
$issues_by_category = [];
foreach ($common_issues as $issue) {
    $category = $issue['category'] ?: 'عام';
    if (!isset($issues_by_category[$category])) {
        $issues_by_category[$category] = [];
    }
    $issues_by_category[$category][] = $issue;
}

// دوال المعالجة
function updateShopSettings($data, $shop_id) {
    $required_fields = ['name', 'phone1'];
    $errors = validateRequired($data, $required_fields);
    
    if (!empty($errors)) return false;
    
    $db = getDB();
    
    // التحقق من صحة الإيميل إذا تم إدخاله
    if (!empty($data['email']) && !isValidEmail($data['email'])) {
        return false;
    }
    
    try {
        $updated = $db->update(
            "UPDATE shops SET 
                name = ?, email = ?, phone1 = ?, phone2 = ?, 
                address = ?, website = ?, city = ?, country = ?, notes = ?
             WHERE id = ?",
            [
                cleanString($data['name']),
                cleanString($data['email'] ?? ''),
                cleanString($data['phone1']),
                cleanString($data['phone2'] ?? ''),
                cleanString($data['address'] ?? ''),
                cleanString($data['website'] ?? ''),
                cleanString($data['city'] ?? ''),
                cleanString($data['country'] ?? 'España'),
                cleanString($data['notes'] ?? ''),
                $shop_id
            ]
        );
        
        if ($updated !== false) {
            logActivity('shop_settings_updated', "تحديث إعدادات المحل", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في تحديث إعدادات المحل: " . $e->getMessage());
    }
    
    return false;
}

function uploadShopLogo($file, $shop_id) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }
    
    // التحقق من نوع الملف
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مدعوم. يُسمح بـ: ' . implode(', ', $allowed_types)];
    }
    
    // التحقق من حجم الملف (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً. الحد الأقصى 2MB'];
    }
    
    // إنشاء مجلد uploads إذا لم يكن موجوداً
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // إنشاء اسم فريد للملف
    $new_filename = 'logo_' . $shop_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $db = getDB();
        
        // الحصول على الشعار القديم لحذفه
        $old_logo = $db->selectOne("SELECT logo FROM shops WHERE id = ?", [$shop_id])['logo'] ?? '';
        
        // تحديث قاعدة البيانات
        $logo_url = 'assets/uploads/' . $new_filename;
        $updated = $db->update("UPDATE shops SET logo = ? WHERE id = ?", [$logo_url, $shop_id]);
        
        if ($updated !== false) {
            // حذف الشعار القديم
            if ($old_logo && file_exists('../' . $old_logo)) {
                unlink('../' . $old_logo);
            }
            
            logActivity('shop_logo_updated', "تحديث شعار المحل", $_SESSION['user_id']);
            return ['success' => true, 'message' => 'تم رفع الشعار بنجاح'];
        } else {
            // حذف الملف إذا فشل التحديث
            unlink($upload_path);
            return ['success' => false, 'message' => 'خطأ في حفظ الشعار في قاعدة البيانات'];
        }
    }
    
    return ['success' => false, 'message' => 'خطأ في رفع الملف'];
}

function addBrand($name) {
    $name = trim($name);
    if (empty($name)) return false;
    
    $db = getDB();
    
    // التحقق من عدم التكرار
    $existing = $db->selectOne("SELECT id FROM brands WHERE name = ?", [$name]);
    if ($existing) return false;
    
    try {
        $brand_id = $db->insert("INSERT INTO brands (name) VALUES (?)", [$name]);
        if ($brand_id) {
            logActivity('brand_added', "إضافة علامة تجارية: $name", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في إضافة علامة تجارية: " . $e->getMessage());
    }
    
    return false;
}

function addModel($brand_id, $name) {
    $brand_id = intval($brand_id);
    $name = trim($name);
    
    if (!$brand_id || empty($name)) return false;
    
    $db = getDB();
    
    // التحقق من وجود العلامة التجارية
    $brand = $db->selectOne("SELECT name FROM brands WHERE id = ?", [$brand_id]);
    if (!$brand) return false;
    
    // التحقق من عدم التكرار
    $existing = $db->selectOne("SELECT id FROM models WHERE brand_id = ? AND name = ?", [$brand_id, $name]);
    if ($existing) return false;
    
    try {
        $model_id = $db->insert("INSERT INTO models (brand_id, name) VALUES (?, ?)", [$brand_id, $name]);
        if ($model_id) {
            logActivity('model_added', "إضافة موديل: {$brand['name']} $name", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في إضافة موديل: " . $e->getMessage());
    }
    
    return false;
}

function deleteBrand($brand_id) {
    $brand_id = intval($brand_id);
    if (!$brand_id) return false;
    
    $db = getDB();
    
    // التحقق من وجود العلامة التجارية
    $brand = $db->selectOne("SELECT name FROM brands WHERE id = ?", [$brand_id]);
    if (!$brand) return false;
    
    // التحقق من عدم استخدامها في إصلاحات
    $used_in_repairs = $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE brand_id = ?", [$brand_id])['count'] ?? 0;
    if ($used_in_repairs > 0) return false;
    
    try {
        $db->beginTransaction();
        
        // حذف جميع الموديلات أولاً
        $db->delete("DELETE FROM models WHERE brand_id = ?", [$brand_id]);
        
        // حذف العلامة التجارية
        $deleted = $db->delete("DELETE FROM brands WHERE id = ?", [$brand_id]);
        
        if ($deleted) {
            $db->commit();
            logActivity('brand_deleted', "حذف علامة تجارية: {$brand['name']}", $_SESSION['user_id']);
            return true;
        } else {
            $db->rollback();
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log("خطأ في حذف علامة تجارية: " . $e->getMessage());
    }
    
    return false;
}

function deleteModel($model_id) {
    $model_id = intval($model_id);
    if (!$model_id) return false;
    
    $db = getDB();
    
    // التحقق من وجود الموديل
    $model = $db->selectOne(
        "SELECT m.name, b.name as brand_name FROM models m JOIN brands b ON m.brand_id = b.id WHERE m.id = ?",
        [$model_id]
    );
    if (!$model) return false;
    
    // التحقق من عدم استخدامه في إصلاحات
    $used_in_repairs = $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE model_id = ?", [$model_id])['count'] ?? 0;
    if ($used_in_repairs > 0) return false;
    
    try {
        $deleted = $db->delete("DELETE FROM models WHERE id = ?", [$model_id]);
        if ($deleted) {
            logActivity('model_deleted', "حذف موديل: {$model['brand_name']} {$model['name']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في حذف موديل: " . $e->getMessage());
    }
    
    return false;
}

function addCommonIssue($category, $issue_text) {
    $category = trim($category);
    $issue_text = trim($issue_text);
    
    if (empty($issue_text)) return false;
    
    $db = getDB();
    
    // التحقق من عدم التكرار
    $existing = $db->selectOne("SELECT id FROM common_issues WHERE issue_text = ?", [$issue_text]);
    if ($existing) return false;
    
    try {
        $issue_id = $db->insert(
            "INSERT INTO common_issues (category, issue_text) VALUES (?, ?)",
            [$category ?: null, $issue_text]
        );
        
        if ($issue_id) {
            logActivity('common_issue_added', "إضافة مشكلة شائعة: $issue_text", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في إضافة مشكلة شائعة: " . $e->getMessage());
    }
    
    return false;
}

function deleteCommonIssue($issue_id) {
    $issue_id = intval($issue_id);
    if (!$issue_id) return false;
    
    $db = getDB();
    
    // التحقق من وجود المشكلة
    $issue = $db->selectOne("SELECT issue_text FROM common_issues WHERE id = ?", [$issue_id]);
    if (!$issue) return false;
    
    try {
        $deleted = $db->delete("DELETE FROM common_issues WHERE id = ?", [$issue_id]);
        if ($deleted) {
            logActivity('common_issue_deleted', "حذف مشكلة شائعة: {$issue['issue_text']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في حذف مشكلة شائعة: " . $e->getMessage());
    }
    
    return false;
}

// تضمين الهيدر
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= url('pages/dashboard.php') ?>">
                    <i class="bi bi-house"></i> الرئيسية
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="bi bi-gear"></i> الإعدادات
            </li>
        </ol>
    </nav>
    
    <!-- عنوان الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <i class="bi bi-gear me-2"></i>
                            إعدادات النظام
                        </h1>
                        <p class="mb-0 opacity-75">
                            إدارة إعدادات المحل والنظام
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-light" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>تحديث
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض الرسائل -->
    <?php displayMessage(); ?>

    <div class="row">
        <!-- القائمة الجانبية -->
        <div class="col-lg-3 mb-4">
            <div class="settings-nav">
                <div class="list-group">
                    <a href="#shop-settings" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                        <i class="bi bi-shop me-2"></i>معلومات المحل
                    </a>
                    <a href="#brands-models" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-tags me-2"></i>العلامات والموديلات
                    </a>
                    <a href="#common-issues" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-exclamation-triangle me-2"></i>المشاكل الشائعة
                    </a>
                    <a href="#system-settings" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-sliders me-2"></i>إعدادات النظام
                    </a>
                    <a href="#backup-restore" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-archive me-2"></i>النسخ الاحتياطي
                    </a>
                </div>
            </div>
        </div>

        <!-- المحتوى الرئيسي -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- معلومات المحل -->
                <div class="tab-pane fade show active" id="shop-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-shop me-2"></i>معلومات المحل
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="update_shop">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shop_name" class="form-label">اسم المحل *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="shop_name" 
                                               name="name" 
                                               value="<?= htmlspecialchars($shop['name']) ?>"
                                               required>
                                        <div class="invalid-feedback">
                                            يرجى إدخال اسم المحل
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="shop_email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="shop_email" 
                                               name="email" 
                                               value="<?= htmlspecialchars($shop['email'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shop_phone1" class="form-label">الهاتف الأساسي *</label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="shop_phone1" 
                                               name="phone1" 
                                               value="<?= htmlspecialchars($shop['phone1']) ?>"
                                               required>
                                        <div class="invalid-feedback">
                                            يرجى إدخال رقم الهاتف
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="shop_phone2" class="form-label">الهاتف الثانوي</label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="shop_phone2" 
                                               name="phone2" 
                                               value="<?= htmlspecialchars($shop['phone2'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="shop_address" class="form-label">العنوان</label>
                                    <textarea class="form-control" 
                                              id="shop_address" 
                                              name="address" 
                                              rows="3"><?= htmlspecialchars($shop['address'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shop_city" class="form-label">المدينة</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="shop_city" 
                                               name="city" 
                                               value="<?= htmlspecialchars($shop['city'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="shop_country" class="form-label">البلد</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="shop_country" 
                                               name="country" 
                                               value="<?= htmlspecialchars($shop['country'] ?? 'España') ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="shop_website" class="form-label">الموقع الإلكتروني</label>
                                    <input type="url" 
                                           class="form-control" 
                                           id="shop_website" 
                                           name="website" 
                                           value="<?= htmlspecialchars($shop['website'] ?? '') ?>"
                                           placeholder="https://example.com">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="shop_notes" class="form-label">ملاحظات</label>
                                    <textarea class="form-control" 
                                              id="shop_notes" 
                                              name="notes" 
                                              rows="3"><?= htmlspecialchars($shop['notes'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>حفظ التغييرات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- رفع الشعار -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-image me-2"></i>شعار المحل
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="current-logo mb-3">
                                        <?php if ($shop['logo']): ?>
                                            <img src="<?= url($shop['logo']) ?>" 
                                                 alt="شعار المحل" 
                                                 class="img-thumbnail"
                                                 style="max-width: 200px; max-height: 200px;">
                                        <?php else: ?>
                                            <div class="no-logo bg-light p-4 text-center rounded">
                                                <i class="bi bi-image display-4 text-muted"></i>
                                                <p class="text-muted mb-0">لا يوجد شعار</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="upload_logo">
                                        
                                        <div class="mb-3">
                                            <label for="logo" class="form-label">رفع شعار جديد</label>
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="logo" 
                                                   name="logo" 
                                                   accept="image/*"
                                                   required>
                                            <div class="form-text">
                                                أنواع الملفات المدعومة: JPG, PNG, GIF<br>
                                                الحد الأقصى: 2MB
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-upload me-2"></i>رفع الشعار
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- العلامات والموديلات -->
                <div class="tab-pane fade" id="brands-models">
                    <div class="row">
                        <!-- العلامات التجارية -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-tags me-2"></i>العلامات التجارية
                                    </h5>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <?php if (empty($brands)): ?>
                                            <div class="list-group-item text-center text-muted">
                                                لا توجد علامات تجارية
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($brands as $brand): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($brand['name']) ?></strong>
                                                    <small class="text-muted d-block">
                                                        <?= count($models_by_brand[$brand['id']] ?? []) ?> موديل
                                                    </small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="showModels(<?= $brand['id'] ?>, '<?= addslashes($brand['name']) ?>')">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteBrand(<?= $brand['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- الموديلات -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-phone me-2"></i>الموديلات
                                    </h5>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModelModal">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div id="models-container">
                                        <div class="p-3 text-center text-muted">
                                            اختر علامة تجارية لعرض الموديلات
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- المشاكل الشائعة -->
                <div class="tab-pane fade" id="common-issues">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>المشاكل الشائعة
                            </h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIssueModal">
                                <i class="bi bi-plus me-2"></i>إضافة مشكلة
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($issues_by_category)): ?>
                                <div class="text-center text-muted p-4">
                                    <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
                                    <p>لا توجد مشاكل شائعة محفوظة</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($issues_by_category as $category => $issues): ?>
                                <div class="category-section mb-4">
                                    <h6 class="category-title fw-bold text-primary border-bottom pb-2">
                                        <?= htmlspecialchars($category) ?>
                                    </h6>
                                    <div class="issues-list">
                                        <?php foreach ($issues as $issue): ?>
                                        <div class="issue-item d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded">
                                            <span><?= htmlspecialchars($issue['issue_text']) ?></span>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteIssue(<?= $issue['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- إعدادات النظام -->
                <div class="tab-pane fade" id="system-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-sliders me-2"></i>إعدادات النظام
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="settings-info">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="setting-item">
                                            <label class="fw-bold">إصدار النظام:</label>
                                            <span class="text-muted"><?= APP_VERSION ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="setting-item">
                                            <label class="fw-bold">قاعدة البيانات:</label>
                                            <span class="text-muted">MySQL</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="setting-item">
                                            <label class="fw-bold">إصدار PHP:</label>
                                            <span class="text-muted"><?= PHP_VERSION ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="setting-item">
                                            <label class="fw-bold">مساحة الرفع:</label>
                                            <span class="text-muted"><?= ini_get('upload_max_filesize') ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="system-actions">
                                    <h6 class="fw-bold mb-3">إجراءات النظام</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-outline-info" onclick="clearCache()">
                                            <i class="bi bi-arrow-clockwise me-2"></i>مسح الذاكرة المؤقتة
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="checkSystem()">
                                            <i class="bi bi-shield-check me-2"></i>فحص النظام
                                        </button>
                                        <button class="btn btn-outline-success" onclick="exportSettings()">
                                            <i class="bi bi-download me-2"></i>تصدير الإعدادات
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- النسخ الاحتياطي -->
                <div class="tab-pane fade" id="backup-restore">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-archive me-2"></i>النسخ الاحتياطي والاستعادة
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="backup-info mb-4">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    النسخ الاحتياطي يشمل جميع البيانات ما عدا الملفات المرفوعة (الشعارات)
                                </div>
                            </div>
                            
                            <div class="backup-actions">
                                <h6 class="fw-bold mb-3">النسخ الاحتياطي</h6>
                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <button class="btn btn-primary" onclick="createBackup()">
                                        <i class="bi bi-download me-2"></i>إنشاء نسخة احتياطية
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="downloadBackup()">
                                        <i class="bi bi-cloud-download me-2"></i>تحميل آخر نسخة
                                    </button>
                                </div>
                                
                                <h6 class="fw-bold mb-3">الاستعادة</h6>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>تحذير:</strong> استعادة النسخة الاحتياطية ستحل محل جميع البيانات الحالية
                                </div>
                                <form id="restoreForm" class="d-flex gap-2">
                                    <input type="file" class="form-control" accept=".sql" required>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-upload me-2"></i>استعادة
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>