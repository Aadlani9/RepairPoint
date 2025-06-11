<?php
/**
 * RepairPoint - صفحة 500 (خطأ في الخادم)
 */

// تعيين كود الاستجابة HTTP
http_response_code(500);

// Definir acceso seguro
define('SECURE_ACCESS', true);

// تجربة تحميل التكوين بأمان
$config_loaded = false;
try {
    require_once '../config/config.php';
    require_once INCLUDES_PATH . 'functions.php';
    $config_loaded = true;
} catch (Exception $e) {
    // إذا فشل تحميل التكوين، استخدم قيم افتراضية
    define('APP_NAME', 'RepairPoint');
    define('APP_DESCRIPTION', 'نظام إدارة الإصلاحات');
    define('APP_AUTHOR', 'RepairPoint Team');
}

$page_title = 'خطأ في الخادم - 500';
$body_class = 'error-page';

function asset($path) {
    return ($config_loaded && function_exists('asset')) ? asset($path) : '../assets/' . $path;
}

function url($path = '') {
    return ($config_loaded && function_exists('url')) ? url($path) : '../' . $path;
}
?>
<!DOCTYPE html>
<html lang="es" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_DESCRIPTION ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">
    <meta name="robots" content="noindex, nofollow">

    <title><?= $page_title ?> - <?= APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <?php if ($config_loaded): ?>
        <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">

    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            direction: ltr;
        }

        .error-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }

        .error-icon {
            font-size: 6rem;
            color: #dc3545;
            margin-bottom: 2rem;
            display: block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .error-title {
            font-size: 3rem;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .error-subtitle {
            font-size: 1.3rem;
            color: #495057;
            margin-bottom: 2rem;
        }

        .error-description {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-group-custom {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-home {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-refresh {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .btn-home:hover, .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: white;
        }

        .technical-info {
            text-align: right;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .technical-info h6 {
            color: #495057;
            margin-bottom: 1rem;
        }

        .technical-info ul {
            list-style: none;
            padding: 0;
        }

        .technical-info li {
            margin-bottom: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .technical-info li::before {
            content: "→";
            margin-left: 0.5rem;
            color: #dc3545;
        }

        .error-id {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #495057;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .error-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .error-icon {
                font-size: 4rem;
            }

            .error-title {
                font-size: 2rem;
            }

            .error-subtitle {
                font-size: 1.1rem;
            }

            .btn-group-custom {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body class="<?= $body_class ?>">

<div class="error-container">
    <div class="error-card">
        <i class="bi bi-exclamation-octagon error-icon"></i>

        <h1 class="error-title">500</h1>

        <h2 class="error-subtitle">¡Error interno del servidor!</h2>

        <p class="error-description">
            Ha ocurrido un error inesperado en el servidor. Nuestro equipo técnico ha sido notificado
            y está trabajando para resolver el problema lo antes posible.
        </p>

        <div class="btn-group-custom">
            <a href="<?= url('') ?>" class="btn-home">
                <i class="bi bi-house"></i>
                Inicio
            </a>
            <button onclick="location.reload()" class="btn-refresh">
                <i class="bi bi-arrow-clockwise"></i>
                Intentar de nuevo
            </button>
        </div>

        <div class="technical-info">
            <h6>معلومات تقنية:</h6>
            <ul>
                <li>تم تسجيل هذا الخطأ في سجلات النظام</li>
                <li>فريق الدعم الفني تم إشعاره تلقائياً</li>
                <li>يمكنك المحاولة مرة أخرى بعد بضع دقائق</li>
                <li>إذا استمر الخطأ، تواصل مع الدعم الفني</li>
            </ul>
        </div>

        <div class="error-id">
            Error ID: <?= uniqid('ERR_') ?>_<?= date('YmdHis') ?>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // تسجيل الخطأ في الكونسول
    console.error('500 Internal Server Error - Time: ' + new Date().toISOString());

    // إعادة المحاولة التلقائية بعد 10 ثوانٍ (اختياري)
    let countdown = 10;
    const countdownElement = document.createElement('div');
    countdownElement.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: rgba(0,0,0,0.8); color: white; padding: 10px 15px; border-radius: 5px; font-size: 0.9rem;';
    document.body.appendChild(countdownElement);

    const timer = setInterval(() => {
        countdownElement.textContent = `إعادة المحاولة التلقائية في ${countdown} ثانية...`;
        countdown--;

        if (countdown < 0) {
            clearInterval(timer);
            countdownElement.textContent = 'جاري إعادة تحميل الصفحة...';
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }, 1000);

    // إلغاء إعادة المحاولة التلقائية عند النقر على أي زر
    document.addEventListener('click', () => {
        clearInterval(timer);
        countdownElement.remove();
    });
</script>

</body>
</html>