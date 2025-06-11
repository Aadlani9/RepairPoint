<?php
/**
 * RepairPoint - صفحة خطأ 403 (ممنوع الوصول)
 */

// منع الوصول المباشر
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// تضمين التكوين إذا لم يكن محملاً
if (!defined('BASE_PATH')) {
    require_once '../config/config.php';
}

$page_title = 'ممنوع الوصول';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .error-icon {
            font-size: 8rem;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-code {
            font-size: 3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .error-message {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 2rem;
        }

        .btn-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline-custom:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .back-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #cbd5e0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        @media (max-width: 768px) {
            .error-icon {
                font-size: 5rem;
            }

            .error-code {
                font-size: 2rem;
            }

            .error-message {
                font-size: 1rem;
            }

            .back-link {
                position: static;
                display: block;
                text-align: center;
                margin-bottom: 2rem;
                color: #2d3748;
            }
        }
    </style>
</head>
<body>
<!-- رابط العودة -->
<a href="javascript:history.back()" class="back-link">
    <i class="bi bi-arrow-left me-2"></i>العودة
</a>

<div class="error-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="error-card text-center p-5">
                    <!-- أيقونة الخطأ -->
                    <div class="floating mb-4">
                        <i class="bi bi-shield-exclamation error-icon"></i>
                    </div>

                    <!-- رمز الخطأ -->
                    <div class="error-code">403</div>

                    <!-- رسالة الخطأ -->
                    <h1 class="h3 mb-3 text-danger">ممنوع الوصول</h1>
                    <p class="error-message">
                        عذراً، ليس لديك الصلاحية الكافية للوصول إلى هذه الصفحة.
                        <br>قد تحتاج إلى تسجيل الدخول أو طلب صلاحيات إضافية من المدير.
                    </p>

                    <!-- تفاصيل إضافية -->
                    <div class="alert alert-light border-0 mb-4">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        <small class="text-muted">
                            إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع مدير النظام.
                        </small>
                    </div>

                    <!-- أزرار الإجراءات -->
                    <div class="d-grid gap-2 d-md-block">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?= url('pages/dashboard.php') ?>" class="btn-custom">
                                <i class="bi bi-house me-2"></i>لوحة التحكم
                            </a>
                        <?php else: ?>
                            <a href="<?= url('pages/login.php') ?>" class="btn-custom">
                                <i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول
                            </a>
                        <?php endif; ?>

                        <a href="javascript:history.back()" class="btn-outline-custom">
                            <i class="bi bi-arrow-left me-2"></i>العودة للخلف
                        </a>
                    </div>

                    <!-- معلومات إضافية -->
                    <div class="mt-4 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            <?= date('Y/m/d H:i') ?>
                            <span class="mx-2">•</span>
                            <i class="bi bi-globe me-1"></i>
                            <?= $_SERVER['HTTP_HOST'] ?? 'localhost' ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // تأثيرات إضافية
    document.addEventListener('DOMContentLoaded', function() {
        // تأثير الكتابة التدريجية للرسالة
        const message = document.querySelector('.error-message');
        const text = message.textContent;
        message.textContent = '';

        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
                message.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 30);
            }
        };

        setTimeout(typeWriter, 500);

        // تسجيل محاولة الوصول غير المصرح بها
        if (typeof logSecurityEvent === 'function') {
            logSecurityEvent('UNAUTHORIZED_ACCESS', {
                url: window.location.href,
                referrer: document.referrer,
                user_agent: navigator.userAgent
            }, 'WARNING');
        }
    });

    // منع النقر بالزر الأيمن على الصفحة
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    // منع استخدام بعض الاختصارات
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.shiftKey && e.key === 'C') ||
            (e.ctrlKey && e.key === 'U')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>