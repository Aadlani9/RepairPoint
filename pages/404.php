<?php
/**
 * RepairPoint - صفحة 404 (الصفحة غير موجودة)
 */

// تعيين رمز الاستجابة HTTP 404
http_response_code(404);

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

$page_title = 'الصفحة غير موجودة - 404';
$body_class = 'error-page';

// تحديد ما إذا كان المستخدم مسجل دخوله أم لا
$is_logged_in = isLoggedIn();
$current_user = $is_logged_in ? getCurrentUser() : null;

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid h-100">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="error-container text-center">
                    <!-- رقم الخطأ -->
                    <div class="error-code">
                        <h1 class="display-1 fw-bold text-primary">404</h1>
                    </div>

                    <!-- أيقونة -->
                    <div class="error-icon mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 5rem;"></i>
                    </div>

                    <!-- الرسالة الرئيسية -->
                    <div class="error-message mb-4">
                        <h2 class="h3 mb-3">¡Página no encontrada!</h2>
                        <h3 class="h5 mb-3 text-muted">الصفحة غير موجودة!</h3>
                        <p class="text-muted">
                            La página que buscas no existe o ha sido movida.
                            <br>
                            الصفحة التي تبحث عنها غير موجودة أو تم نقلها.
                        </p>
                    </div>

                    <!-- معلومات إضافية -->
                    <div class="error-details mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-info-circle me-2"></i>
                                    ماذا يمكنك فعله؟
                                </h6>
                                <ul class="list-unstyled text-start">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        تحقق من صحة الرابط المكتوب
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        استخدم القائمة الرئيسية للتنقل
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        ارجع للصفحة السابقة
                                    </li>
                                    <?php if ($is_logged_in): ?>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            استخدم البحث للعثور على ما تريد
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- أزرار التنقل -->
                    <div class="error-actions">
                        <div class="d-grid gap-2 d-md-block">
                            <?php if ($is_logged_in): ?>
                                <!-- إذا كان مسجل دخوله -->
                                <a href="<?= url('pages/dashboard.php') ?>" class="btn btn-primary btn-lg">
                                    <i class="bi bi-house me-2"></i>
                                    العودة للرئيسية
                                </a>
                                <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-tools me-2"></i>
                                    الرeparaciones
                                </a>
                                <a href="<?= url('pages/search.php') ?>" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-search me-2"></i>
                                    البحث
                                </a>
                            <?php else: ?>
                                <!-- إذا لم يكن مسجل دخوله -->
                                <a href="<?= url('pages/login.php') ?>" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    تسجيل الدخول
                                </a>
                                <a href="<?= url('index.php') ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-house me-2"></i>
                                    الصفحة الرئيسية
                                </a>
                            <?php endif; ?>

                            <button onclick="history.back()" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>
                                الصفحة السابقة
                            </button>
                        </div>
                    </div>

                    <!-- معلومات تقنية (للمطورين فقط) -->
                    <?php if (defined('DEBUG_MODE') && DEBUG_MODE && $is_logged_in && $current_user['role'] === 'admin'): ?>
                        <div class="debug-info mt-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <i class="bi bi-bug me-2"></i>
                                    معلومات تقنية (Debug)
                                </div>
                                <div class="card-body">
                                    <small class="text-muted">
                                        <strong>الطلب:</strong> <?= htmlspecialchars($_SERVER['REQUEST_URI']) ?><br>
                                        <strong>الطريقة:</strong> <?= htmlspecialchars($_SERVER['REQUEST_METHOD']) ?><br>
                                        <strong>الوقت:</strong> <?= date('Y-m-d H:i:s') ?><br>
                                        <strong>IP:</strong> <?= htmlspecialchars($_SERVER['REMOTE_ADDR']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* أنماط صفحة الخطأ 404 */
        .error-page {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-container {
            padding: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .error-code h1 {
            font-size: 8rem;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            animation: bounce 2s infinite;
        }

        .error-icon {
            animation: pulse 2s infinite;
        }

        .error-message h2,
        .error-message h3 {
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .btn {
            border-radius: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0.25rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .debug-info {
            max-width: 600px;
            margin: 0 auto;
        }

        /* أنماط الرسوم المتحركة */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        /* أنماط متجاوبة */
        @media (max-width: 768px) {
            .error-code h1 {
                font-size: 6rem;
            }

            .error-icon i {
                font-size: 4rem !important;
            }

            .error-container {
                padding: 1rem;
            }

            .btn-lg {
                font-size: 1rem;
                padding: 0.75rem 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .error-code h1 {
                font-size: 4rem;
            }

            .error-icon i {
                font-size: 3rem !important;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .d-md-block .btn {
                margin-bottom: 0.75rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تسجيل الخطأ 404 (اختياري - للإحصائيات)
            if (typeof logActivity === 'function') {
                logActivity('404_error', 'صفحة غير موجودة: ' + window.location.pathname);
            }

            // إضافة تأثيرات تفاعلية
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.02)';
                });

                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // عد تنازلي للتوجيه التلقائي (اختياري)
            <?php if ($is_logged_in): ?>
            let countdown = 30;
            const redirectTimer = setInterval(function() {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(redirectTimer);
                    window.location.href = '<?= url('pages/dashboard.php') ?>';
                }
            }, 1000);

            // إيقاف العد التنازلي عند التفاعل
            document.addEventListener('click', function() {
                clearInterval(redirectTimer);
            });
            <?php endif; ?>
        });

        // دالة للرجوع للصفحة السابقة مع التحقق
        function goBack() {
            if (history.length > 1) {
                history.back();
            } else {
                <?php if ($is_logged_in): ?>
                window.location.href = '<?= url('pages/dashboard.php') ?>';
                <?php else: ?>
                window.location.href = '<?= url('index.php') ?>';
                <?php endif; ?>
            }
        }
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>