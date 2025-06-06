try {
        $new_password_hash = hashPassword($data['new_password']);
        
        $updated = $db->update(
            "UPDATE users SET password = ? WHERE id = ?",
            [$new_password_hash, $user_id]
        );
        
        if ($updated !== false) {
            logActivity('password_changed', "تغيير كلمة المرور", $user_id);
            return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
        } else {
            return ['success' => false, 'message' => 'خطأ في تحديث كلمة المرور'];
        }
    } catch (Exception $e) {
        error_log("خطأ في تغيير كلمة المرور: " . $e->getMessage());
        return ['success' => false, 'message' => 'خطأ في النظام، يرجى المحاولة مرة أخرى'];
    }
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
                <i class="bi bi-person"></i> الملف الشخصي
            </li>
        </ol>
    </nav>
    
    <!-- عنوان الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-gradient-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                <div class="avatar-circle bg-white text-primary">
                                    <?= strtoupper(substr($current_user['name'], 0, 2)) ?>
                                </div>
                            </div>
                            <div>
                                <h1 class="h3 mb-1">
                                    مرحباً، <?= htmlspecialchars($current_user['name']) ?>
                                </h1>
                                <p class="mb-0 opacity-75">
                                    <?= $current_user['role'] === 'admin' ? 'مدير' : 'موظف' ?> • 
                                    <?= htmlspecialchars($current_user['shop_name']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-light text-dark fs-6">
                            <?= $current_user['status'] === 'active' ? 'حساب نشط' : 'حساب غير نشط' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض الرسائل -->
    <?php displayMessage(); ?>

    <div class="row">
        <!-- معلومات المستخدم والإحصائيات -->
        <div class="col-lg-4 mb-4">
            <!-- بطاقة المعلومات الأساسية -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-badge me-2"></i>معلوماتي
                    </h5>
                </div>
                <div class="card-body">
                    <div class="user-info-details">
                        <div class="info-item mb-3">
                            <label class="fw-bold text-muted">الاسم:</label>
                            <div><?= htmlspecialchars($current_user['name']) ?></div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="fw-bold text-muted">البريد الإلكتروني:</label>
                            <div><?= htmlspecialchars($current_user['email']) ?></div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="fw-bold text-muted">الهاتف:</label>
                            <div><?= $current_user['phone'] ? htmlspecialchars($current_user['phone']) : 'غير محدد' ?></div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="fw-bold text-muted">الدور:</label>
                            <div>
                                <?php if ($current_user['role'] === 'admin'): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-shield me-1"></i>مدير
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-person-workspace me-1"></i>موظف
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <label class="fw-bold text-muted">تاريخ الانضمام:</label>
                            <div><?= formatDate($current_user['created_at'], 'd/m/Y') ?></div>
                        </div>
                        
                        <?php if ($current_user['last_login']): ?>
                        <div class="info-item">
                            <label class="fw-bold text-muted">آخر تسجيل دخول:</label>
                            <div><?= formatDateTime($current_user['last_login']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- إحصائيات المستخدم -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>إحصائياتي
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label text-muted">إجمالي الإصلاحات</div>
                                    <div class="stat-value h4 text-primary mb-0"><?= $user_stats['total_repairs'] ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-tools text-primary"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label text-muted">هذا الشهر</div>
                                    <div class="stat-value h4 text-success mb-0"><?= $user_stats['this_month_repairs'] ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-calendar-month text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label text-muted">مكتملة</div>
                                    <div class="stat-value h4 text-info mb-0"><?= $user_stats['completed_repairs'] ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle text-info"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label text-muted">متوسط الإنجاز</div>
                                    <div class="stat-value h4 text-warning mb-0">
                                        <?= round($user_stats['avg_completion_days'], 1) ?> يوم
                                    </div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-clock-history text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- نماذج التحديث -->
        <div class="col-lg-8">
            <!-- تحديث المعلومات الشخصية -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil me-2"></i>تحديث المعلومات الشخصية
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">الاسم الكامل *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars($current_user['name']) ?>"
                                       required>
                                <div class="invalid-feedback">
                                    يرجى إدخال الاسم الكامل
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($current_user['email']) ?>"
                                       required>
                                <div class="invalid-feedback">
                                    يرجى إدخال بريد إلكتروني صحيح
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">رقم الهاتف</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>"
                                   placeholder="+34 666 123 456">
                            <div class="form-text">
                                اتركه فارغاً إذا كنت لا تريد تحديثه
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- تغيير كلمة المرور -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lock me-2"></i>تغيير كلمة المرور
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">كلمة المرور الحالية *</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="current_password" 
                                       name="current_password" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('current_password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                يرجى إدخال كلمة المرور الحالية
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">كلمة المرور الجديدة *</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       minlength="6"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('new_password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                يجب أن تكون كلمة المرور 6 أحرف على الأقل
                            </div>
                            <div class="invalid-feedback">
                                كلمة المرور يجب أن تكون 6 أحرف على الأقل
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">تأكيد كلمة المرور *</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       minlength="6"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('confirm_password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="confirm-password-feedback">
                                يرجى تأكيد كلمة المرور
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-shield-lock me-2"></i>تغيير كلمة المرور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- آخر الأنشطة -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>آخر أنشطتي
                    </h5>
                    <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-sm btn-outline-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-inbox display-4 mb-3"></i>
                            <p>لا توجد أنشطة حديثة</p>
                            <a href="<?= url('pages/add_repair.php') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus me-2"></i>إضافة إصلاح جديد
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="activity-content">
                                        <h6 class="mb-1">
                                            <a href="<?= url('pages/repair_details.php?id=' . $activity['id']) ?>" 
                                               class="text-decoration-none">
                                                #<?= htmlspecialchars($activity['reference']) ?>
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            <?= htmlspecialchars($activity['customer_name']) ?> • 
                                            <?= htmlspecialchars($activity['brand_name'] . ' ' . $activity['model_name']) ?>
                                        </p>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(mb_strimwidth($activity['issue_description'], 0, 50, '...')) ?>
                                        </small>
                                    </div>
                                    <div class="activity-meta text-end">
                                        <?= getStatusBadge($activity['status']) ?>
                                        <div class="small text-muted mt-1">
                                            <?= formatDate($activity['created_at'], 'd/m/Y') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($recent_activities) > 5): ?>
                        <div class="card-footer text-center">
                            <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-link">
                                عرض المزيد (<?= count($recent_activities) - 5 ?> أخرى)
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* أنماط خاصة بصفحة الملف الشخصي */
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

.avatar-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.5rem;
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

.info-item {
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-item label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.stat-item {
    padding: 1rem;
    border-radius: 0.5rem;
    background: rgba(13, 110, 253, 0.05);
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: rgba(13, 110, 253, 0.1);
    transform: translateY(-2px);
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-value {
    font-weight: 700;
}

.stat-icon {
    font-size: 1.5rem;
    opacity: 0.7;
}

.activity-content h6 a {
    color: var(--primary-color);
    font-weight: 600;
}

.activity-content h6 a:hover {
    text-decoration: underline !important;
}

.activity-meta {
    min-width: 120px;
}

/* أنماط النموذج */
.needs-validation .form-control:invalid {
    border-color: var(--danger-color);
}

.needs-validation .form-control:valid {
    border-color: var(--success-color);
}

.input-group .btn {
    border-color: var(--bs-border-color);
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
    
    .avatar-circle {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .user-info-details {
        text-align: center;
    }
    
    .info-item {
        text-align: left;
    }
    
    .stat-item {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        text-align: center;
    }
    
    .stat-icon {
        display: none;
    }
    
    .activity-meta {
        min-width: auto;
        text-align: left;
        margin-top: 0.5rem;
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
    
    .activity-content,
    .activity-meta {
        width: 100%;
        text-align: left;
    }
    
    .activity-meta {
        margin-top: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
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

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }

/* أنماط خاصة بكلمات المرور */
.password-strength {
    height: 4px;
    background: #f0f0f0;
    border-radius: 2px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    transition: width 0.3s ease, background-color 0.3s ease;
}

.strength-weak { background-color: #dc3545; }
.strength-medium { background-color: #ffc107; }
.strength-strong { background-color: #198754; }
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
    
    // إعداد تنسيق رقم الهاتف
    setupPhoneFormatting();
    
    // إعداد التحقق من كلمة المرور
    setupPasswordValidation();
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
        const inputs = form.querySelectorAll('input');
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
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '');
            
            // تنسيق تلقائي للأرقام الإسبانية
            if (value.length === 9 && !value.startsWith('+34')) {
                this.value = '+34 ' + value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            }
        });
    }
}

function setupPasswordValidation() {
    const passwordForm = document.getElementById('passwordForm');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const confirmFeedback = document.getElementById('confirm-password-feedback');
    
    // التحقق من تطابق كلمات المرور
    function validatePasswordMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('كلمة المرور غير متطابقة');
            confirmFeedback.textContent = 'كلمة المرور غير متطابقة';
            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmFeedback.textContent = 'يرجى تأكيد كلمة المرور';
            if (confirmPassword.value) {
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
            }
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // إضافة مؤشر قوة كلمة المرور
    addPasswordStrengthIndicator(newPassword);
}

function addPasswordStrengthIndicator(passwordInput) {
    const strengthContainer = document.createElement('div');
    strengthContainer.className = 'password-strength';
    
    const strengthBar = document.createElement('div');
    strengthBar.className = 'password-strength-bar';
    strengthContainer.appendChild(strengthBar);
    
    passwordInput.parentNode.insertBefore(strengthContainer, passwordInput.nextSibling);
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        strengthBar.style.width = strength.percentage + '%';
        strengthBar.className = 'password-strength-bar ' + strength.class;
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 6) score += 1;
    if (password.length >= 10) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    
    if (score < 3) {
        return { percentage: 33, class: 'strength-weak' };
    } else if (score < 5) {
        return { percentage: 66, class: 'strength-medium' };
    } else {
        return { percentage: 100, class: 'strength-strong' };
    }
}

// دالة إظهار/إخفاء كلمة المرور
window.togglePassword = function(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
};

// اختصارات لوحة المفاتيح
document.addEventListener('keydown', function(e) {
    // Ctrl+S لحفظ المعلومات الشخصية
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const profileForm = document.querySelector('form[action=""][method="POST"]');
        if (profileForm && profileForm.querySelector('input[name="action"][value="update_profile"]')) {
            profileForm.submit();
        }
    }
    
    // Ctrl+P لتغيير كلمة المرور
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        document.getElementById('current_password').focus();
    }
});

// تأكيد عند مغادرة الصفحة مع وجود تغييرات غير محفوظة
let formChanged = false;

document.querySelectorAll('input').forEach(input => {
    input.addEventListener('input', function() {
        formChanged = true;
    });
});

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        formChanged = false;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// تحديث الإحصائيات كل 5 دقائق
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // يمكن إضافة AJAX لتحديث الإحصائيات
        // Ajax.get('api/user-stats.php').then(data => { ... });
    }
}, 300000);

// دالة لتنبيه المستخدم عند نجاح العمليات
function showSuccessAnimation() {
    // إضافة تأثير بصري عند النجاح
    const successElement = document.createElement('div');
    successElement.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
    successElement.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 4rem;
        color: #198754;
        z-index: 9999;
        animation: successPulse 1s ease-out;
    `;
    
    // إضافة CSS للرسوم المتحركة
    const style = document.createElement('style');
    style.textContent = `
        @keyframes successPulse {
            0% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(successElement);
    
    setTimeout(() => {
        document.body.removeChild(successElement);
        document.head.removeChild(style);
    }, 1000);
}

// معالج خاص لنماذج الملف الشخصي
document.querySelectorAll('form').forEach(form => {
    const submitButton = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function() {
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحفظ...';
        }
    });
});
</script>

<?php
// تضمين الفوتر
require_once INCLUDES_PATH . 'footer.php';
?><?php
/**
 * RepairPoint - الملف الشخصي
 * صفحة خاصة بالمستخدم لإدارة معلوماته الشخصية
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'الملف الشخصي';
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
            case 'update_profile':
                $result = updateProfile($_POST, $current_user['id']);
                $success = $result['success'];
                $message = $result['message'];
                break;
                
            case 'change_password':
                $result = changePassword($_POST, $current_user['id']);
                $success = $result['success'];
                $message = $result['message'];
                break;
        }
        
        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);
        
        // إعادة توجيه لتجنب إعادة الإرسال
        if ($success) {
            header('Location: ' . url('pages/profile.php'));
            exit;
        }
    }
}

// جلب بيانات المستخدم المحدثة
$current_user = getCurrentUser();

// جلب إحصائيات المستخدم
$db = getDB();
$user_stats = [
    'total_repairs' => $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs WHERE created_by = ?",
        [$current_user['id']]
    )['count'] ?? 0,
    
    'this_month_repairs' => $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs 
         WHERE created_by = ? 
         AND MONTH(created_at) = MONTH(CURDATE()) 
         AND YEAR(created_at) = YEAR(CURDATE())",
        [$current_user['id']]
    )['count'] ?? 0,
    
    'completed_repairs' => $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs 
         WHERE created_by = ? AND status = 'delivered'",
        [$current_user['id']]
    )['count'] ?? 0,
    
    'avg_completion_days' => $db->selectOne(
        "SELECT AVG(DATEDIFF(delivered_at, received_at)) as avg_days 
         FROM repairs 
         WHERE created_by = ? AND status = 'delivered' 
         AND delivered_at IS NOT NULL",
        [$current_user['id']]
    )['avg_days'] ?? 0
];

// جلب آخر الأنشطة
$recent_activities = $db->select(
    "SELECT r.*, b.name as brand_name, m.name as model_name
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id
     WHERE r.created_by = ?
     ORDER BY r.created_at DESC 
     LIMIT 10",
    [$current_user['id']]
);

// دوال المعالجة
function updateProfile($data, $user_id) {
    $required_fields = ['name', 'email'];
    $errors = validateRequired($data, $required_fields);
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة'];
    }
    
    // التحقق من صحة الإيميل
    if (!isValidEmail($data['email'])) {
        return ['success' => false, 'message' => 'البريد الإلكتروني غير صحيح'];
    }
    
    // التحقق من صحة الهاتف إذا تم إدخاله
    if (!empty($data['phone']) && !isValidPhone($data['phone'])) {
        return ['success' => false, 'message' => 'رقم الهاتف غير صحيح'];
    }
    
    $db = getDB();
    
    // التحقق من عدم تكرار الإيميل
    $existing = $db->selectOne(
        "SELECT id FROM users WHERE email = ? AND id != ?",
        [trim($data['email']), $user_id]
    );
    
    if ($existing) {
        return ['success' => false, 'message' => 'البريد الإلكتروني مستخدم من قبل مستخدم آخر'];
    }
    
    try {
        $updated = $db->update(
            "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?",
            [
                cleanString($data['name']),
                cleanString($data['email']),
                cleanString($data['phone'] ?? ''),
                $user_id
            ]
        );
        
        if ($updated !== false) {
            // تحديث بيانات الجلسة
            $_SESSION['user_name'] = cleanString($data['name']);
            $_SESSION['user_email'] = cleanString($data['email']);
            
            logActivity('profile_updated', "تحديث الملف الشخصي", $user_id);
            return ['success' => true, 'message' => 'تم تحديث الملف الشخصي بنجاح'];
        } else {
            return ['success' => false, 'message' => 'خطأ في تحديث البيانات'];
        }
    } catch (Exception $e) {
        error_log("خطأ في تحديث الملف الشخصي: " . $e->getMessage());
        return ['success' => false, 'message' => 'خطأ في النظام، يرجى المحاولة مرة أخرى'];
    }
}

function changePassword($data, $user_id) {
    $required_fields = ['current_password', 'new_password', 'confirm_password'];
    $errors = validateRequired($data, $required_fields);
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => 'يرجى ملء جميع حقول كلمة المرور'];
    }
    
    // التحقق من تطابق كلمة المرور الجديدة
    if ($data['new_password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'كلمة المرور الجديدة غير متطابقة'];
    }
    
    // التحقق من طول كلمة المرور الجديدة
    if (strlen($data['new_password']) < 6) {
        return ['success' => false, 'message' => 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل'];
    }
    
    $db = getDB();
    
    // التحقق من كلمة المرور الحالية
    $user = $db->selectOne("SELECT password FROM users WHERE id = ?", [$user_id]);
    
    if (!$user || !verifyPassword($data['current_password'], $user['password'])) {
        return ['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة'];
    }
    
    try {
        $new_password_