<?php
/**
 * RepairPoint - Footer محسن ومتطور
 * تحسينات شاملة للـ footer مع معلومات مفيدة وتفاعلية
 */

// إغلاق main-content container
if (isset($_SESSION['user_id'])): ?>
    </div> <!-- End main-content -->
<?php endif; ?>

<?php
// الحصول على معلومات إضافية للمستخدمين المسجلين
$footer_stats = [];
$system_info = [];
$shop_info = [];

if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();
        $shop_id = $_SESSION['shop_id'];

        // إحصائيات سريعة
        $footer_stats = [
            'total_repairs' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ?", [$shop_id])['count'] ?? 0,
            'active_repairs' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status IN ('pending', 'in_progress')", [$shop_id])['count'] ?? 0,
            'completed_today' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()", [$shop_id])['count'] ?? 0,
            'revenue_month' => $db->selectOne("SELECT SUM(actual_cost) as total FROM repairs WHERE shop_id = ? AND status = 'delivered' AND MONTH(delivered_at) = MONTH(CURDATE()) AND YEAR(delivered_at) = YEAR(CURDATE())", [$shop_id])['total'] ?? 0
        ];

        // معلومات المحل
        $shop_info = $db->selectOne("SELECT name, phone1, address, city FROM shops WHERE id = ?", [$shop_id]) ?: [];

        // معلومات النظام
        $system_info = [
            'login_time' => $_SESSION['login_time'] ?? time(),
            'user_role' => $_SESSION['user_role'] ?? 'staff',
            'last_activity' => $_SESSION['last_activity'] ?? time(),
            'session_duration' => time() - ($_SESSION['login_time'] ?? time())
        ];

    } catch (Exception $e) {
        // تجاهل الأخطاء وتعيين قيم افتراضية
        error_log("Footer stats error: " . $e->getMessage());
    }
}
?>

<!-- Footer محسن -->
<footer class="footer mt-auto <?= isset($_SESSION['user_id']) ? 'footer-logged-in' : 'footer-guest' ?>">
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Footer للمستخدمين المسجلين -->

        <!-- قسم الإحصائيات السريعة -->
        <div class="footer-stats py-3 bg-primary text-white">
            <div class="container">
                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($footer_stats['total_repairs'] ?? 0) ?></div>
                            <div class="stat-label">Total Reparaciones</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($footer_stats['active_repairs'] ?? 0) ?></div>
                            <div class="stat-label">Activas</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($footer_stats['completed_today'] ?? 0) ?></div>
                            <div class="stat-label">Completadas Hoy</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">€<?= number_format($footer_stats['revenue_month'] ?? 0) ?></div>
                            <div class="stat-label">Ingresos del Mes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- القسم الرئيسي للمعلومات -->
        <div class="footer-main py-4 bg-light">
            <div class="container">
                <div class="row">
                    <!-- معلومات المحل -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h6 class="footer-title">
                            <i class="bi bi-shop me-2"></i>
                            <?= htmlspecialchars($shop_info['name'] ?? 'Mi Taller') ?>
                        </h6>
                        <div class="footer-content">
                            <?php if (!empty($shop_info['address'])): ?>
                                <p class="mb-1">
                                    <i class="bi bi-geo-alt text-muted me-2"></i>
                                    <?= htmlspecialchars($shop_info['address']) ?>
                                    <?php if (!empty($shop_info['city'])): ?>
                                        <br><small class="text-muted ms-4"><?= htmlspecialchars($shop_info['city']) ?></small>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($shop_info['phone1'])): ?>
                                <p class="mb-1">
                                    <i class="bi bi-telephone text-muted me-2"></i>
                                    <a href="tel:<?= htmlspecialchars($shop_info['phone1']) ?>" class="text-decoration-none">
                                        <?= formatPhoneNumber($shop_info['phone1']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Accesos Rápidos -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h6 class="footer-title">
                            <i class="bi bi-lightning me-2"></i>
                            Accesos Rápidos
                        </h6>
                        <div class="footer-links">
                            <a href="<?= url('pages/add_repair.php') ?>" class="footer-link">
                                <i class="bi bi-plus-circle me-2"></i>Nueva Reparación
                            </a>
                            <a href="<?= url('pages/repairs_active.php') ?>" class="footer-link">
                                <i class="bi bi-clock me-2"></i>Reparaciones Activas
                            </a>
                            <a href="<?= url('pages/search.php') ?>" class="footer-link">
                                <i class="bi bi-search me-2"></i>Buscar
                            </a>
                            <?php if (isAdmin()): ?>
                                <a href="<?= url('pages/reports.php') ?>" class="footer-link">
                                    <i class="bi bi-graph-up me-2"></i>Informes
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Soporte y Ayuda -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h6 class="footer-title">
                            <i class="bi bi-headset me-2"></i>
                            Soporte y Ayuda
                        </h6>
                        <div class="footer-links">
                            <a href="<?= url('pages/help.php') ?>" class="footer-link">
                                <i class="bi bi-question-circle me-2"></i>Centro de Ayuda
                            </a>
                            <button class="footer-link border-0 bg-transparent p-0 text-start w-100" onclick="showKeyboardShortcuts()">
                                <i class="bi bi-keyboard me-2"></i>Atajos de Teclado
                            </button>
                            <button class="footer-link border-0 bg-transparent p-0 text-start w-100" onclick="showSystemInfo()">
                                <i class="bi bi-info-circle me-2"></i>Info del Sistema
                            </button>
                            <a href="mailto:support@repairpoint.com" class="footer-link">
                                <i class="bi bi-envelope me-2"></i>Contactar Soporte
                            </a>
                        </div>
                    </div>

                    <!-- Información de Sesión -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h6 class="footer-title">
                            <i class="bi bi-person-check me-2"></i>
                            Tu Sesión
                        </h6>
                        <div class="footer-content small text-muted">
                            <p class="mb-1">
                                <i class="bi bi-clock me-2"></i>
                                Conectado: <?= formatSessionDuration($system_info['session_duration'] ?? 0) ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-shield me-2"></i>
                                Rol: <?= ucfirst($system_info['user_role'] ?? 'Usuario') ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-calendar me-2"></i>
                                Ingreso: <?= date('H:i', $system_info['login_time'] ?? time()) ?>
                            </p>
                            <div class="session-actions mt-2">
                                <button class="btn btn-outline-primary btn-sm me-1" onclick="refreshSession()" title="Refrescar Sesión">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="showSessionDetails()" title="Detalles de Sesión">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم الأدوات والاختصارات -->
        <div class="footer-tools py-2 bg-white border-top">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="footer-shortcuts">
                            <small class="text-muted me-3">
                                <i class="bi bi-keyboard me-1"></i>Atajos:
                            </small>
                            <span class="shortcut-item">Alt+D Dashboard</span>
                            <span class="shortcut-item">Alt+N Nueva</span>
                            <span class="shortcut-item">Alt+S Buscar</span>
                            <span class="shortcut-item">Alt+H Ayuda</span>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="footer-actions">
                            <button class="btn btn-outline-success btn-sm me-1" onclick="openPrintMode()" title="Modo Impresión">
                                <i class="bi bi-printer"></i>
                            </button>
                            <button class="btn btn-outline-info btn-sm me-1" onclick="toggleFullscreen()" title="Pantalla Completa">
                                <i class="bi bi-fullscreen"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="startInteractiveTour()" title="Guía Interactiva">
                                <i class="bi bi-play-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Footer للزوار -->
        <div class="footer-guest-content py-4 bg-primary text-white">
            <div class="container text-center">
                <h5 class="mb-3">
                    <i class="bi bi-tools me-2"></i>
                    <?= APP_NAME ?>
                </h5>
                <p class="mb-3">
                    Sistema profesional de gestión de reparaciones de dispositivos móviles
                </p>
                <div class="guest-actions">
                    <a href="<?= url('pages/login.php') ?>" class="btn btn-light me-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Iniciar Sesión
                    </a>
                    <a href="<?= url('pages/help.php') ?>" class="btn btn-outline-light">
                        <i class="bi bi-question-circle me-2"></i>
                        Ayuda
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer Bottom - حقوق الطبع -->
    <div class="footer-bottom py-3 <?= isset($_SESSION['user_id']) ? 'bg-dark text-white' : 'bg-dark text-white' ?>">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="copyright">
                        &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>
                        <span class="ms-2 text-muted">•</span>
                        <span class="ms-2 small">
                            <i class="bi bi-shield-check me-1"></i>
                            Sistema Seguro
                        </span>
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="developer-info">
                        <i class="bi bi-code-slash me-1"></i>
                        Desarrollado con ❤️ por <?= APP_AUTHOR ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Modals للمعلومات الإضافية -->

<!-- Modal: Atajos de Teclado -->
<div class="modal fade" id="keyboardShortcutsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-keyboard me-2"></i>
                    Atajos de Teclado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Navegación</h6>
                        <ul class="list-unstyled">
                            <li><kbd>Alt + D</kbd> Dashboard</li>
                            <li><kbd>Alt + N</kbd> Nueva Reparación</li>
                            <li><kbd>Alt + S</kbd> Buscar</li>
                            <li><kbd>Alt + H</kbd> Ayuda</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Acciones</h6>
                        <ul class="list-unstyled">
                            <li><kbd>Ctrl + S</kbd> Guardar</li>
                            <li><kbd>Ctrl + P</kbd> Imprimir</li>
                            <li><kbd>F1</kbd> Ayuda Contextual</li>
                            <li><kbd>Esc</kbd> Cancelar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Información del Sistema -->
<div class="modal fade" id="systemInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Información del Sistema
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="system-info">
                    <div class="row">
                        <div class="col-6">
                            <strong>Versión:</strong>
                        </div>
                        <div class="col-6">
                            <?= APP_VERSION ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <strong>PHP:</strong>
                        </div>
                        <div class="col-6">
                            <?= PHP_VERSION ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <strong>Base de Datos:</strong>
                        </div>
                        <div class="col-6">
                            MySQL
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <strong>Navegador:</strong>
                        </div>
                        <div class="col-6">
                            <span id="browserInfo">Detectando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS Styles للـ Footer -->
<style>
    /* Footer Styles */
    .footer {
        margin-top: auto;
    }

    .footer-logged-in {
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }

    /* Stats Section */
    .footer-stats {
        background: linear-gradient(135deg, #0d6efd, #0056b3);
    }

    .stat-item {
        transition: transform 0.3s ease;
        cursor: pointer;
    }

    .stat-item:hover {
        transform: translateY(-2px);
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #fff;
    }

    .stat-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.8);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Footer Main */
    .footer-title {
        color: #0d6efd;
        font-weight: 600;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }

    .footer-links {
        display: flex;
        flex-direction: column;
    }

    .footer-link {
        color: #6c757d;
        text-decoration: none;
        padding: 0.25rem 0;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    .footer-link:hover {
        color: #0d6efd;
        transform: translateX(5px);
    }

    .footer-content {
        color: #6c757d;
        line-height: 1.6;
    }

    /* Session Actions */
    .session-actions .btn {
        border-radius: 50%;
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Footer Tools */
    .footer-shortcuts {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .shortcut-item {
        font-size: 0.75rem;
        background: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        margin-right: 0.5rem;
        margin-bottom: 0.25rem;
        color: #495057;
    }

    .footer-actions .btn {
        border-radius: 20px;
        padding: 0.375rem 0.75rem;
    }

    /* Guest Footer */
    .footer-guest-content {
        background: linear-gradient(135deg, #0d6efd, #6610f2);
    }

    .guest-actions .btn {
        margin-bottom: 0.5rem;
    }

    /* Footer Bottom */
    .footer-bottom {
        background: #212529 !important;
    }

    .copyright {
        font-size: 0.9rem;
    }

    .developer-info {
        font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .footer-stats .row {
            text-align: center;
        }

        .stat-number {
            font-size: 1.2rem;
        }

        .stat-label {
            font-size: 0.7rem;
        }

        .footer-shortcuts {
            justify-content: center;
            margin-bottom: 1rem;
        }

        .footer-actions {
            text-align: center;
        }

        .shortcut-item {
            font-size: 0.7rem;
            margin-right: 0.25rem;
        }
    }

    @media (max-width: 576px) {
        .footer-main .col-lg-3 {
            margin-bottom: 2rem;
        }

        .footer-title {
            text-align: center;
        }

        .footer-links {
            align-items: center;
        }

        .footer-content {
            text-align: center;
        }
    }

    /* Animation */
    .footer-link {
        animation: fadeInUp 0.6s ease;
    }

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

    /* Print Styles */
    @media print {
        .footer {
            display: none !important;
        }
    }
</style>

<!-- JavaScript للـ Footer -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تحديث معلومات المتصفح
        updateBrowserInfo();

        // إضافة event listeners للإحصائيات
        setupStatsClickHandlers();

        // إعداد auto-refresh للإحصائيات
        setupStatsAutoRefresh();
    });

    // دوال المساعدة
    function showKeyboardShortcuts() {
        const modal = new bootstrap.Modal(document.getElementById('keyboardShortcutsModal'));
        modal.show();
    }

    function showSystemInfo() {
        updateBrowserInfo();
        const modal = new bootstrap.Modal(document.getElementById('systemInfoModal'));
        modal.show();
    }

    function updateBrowserInfo() {
        const browserInfo = document.getElementById('browserInfo');
        if (browserInfo) {
            const userAgent = navigator.userAgent;
            let browser = 'Desconocido';

            if (userAgent.includes('Chrome')) browser = 'Chrome';
            else if (userAgent.includes('Firefox')) browser = 'Firefox';
            else if (userAgent.includes('Safari')) browser = 'Safari';
            else if (userAgent.includes('Edge')) browser = 'Edge';

            browserInfo.textContent = browser;
        }
    }

    function refreshSession() {
        // إرسال ping للخادم للحفاظ على الجلسة
        fetch('<?= url('api/ping.php') ?>')
            .then(response => response.json())
            .then(data => {
                if (typeof Utils !== 'undefined') {
                    Utils.showNotification('Sesión refrescada correctamente', 'success', 2000);
                }
            })
            .catch(error => {
                console.log('Ping failed:', error);
            });
    }

    function showSessionDetails() {
        if (typeof Utils !== 'undefined') {
            Utils.showNotification('Sesión activa desde: <?= date('H:i', $system_info['login_time'] ?? time()) ?>', 'info', 3000);
        }
    }

    function openPrintMode() {
        window.print();
    }

    function toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen();
        }
    }

    function startInteractiveTour() {
        // إذا كان نظام الجولة التفاعلية متاح
        if (typeof window.RepairPoint !== 'undefined' && window.RepairPoint.tour) {
            window.RepairPoint.tour.start();
        } else {
            // إعادة توجيه لصفحة المساعدة
            window.location.href = '<?= url('pages/help.php') ?>?tour=1';
        }
    }

    function setupStatsClickHandlers() {
        // إضافة تفاعل للإحصائيات
        document.querySelectorAll('.stat-item').forEach((item, index) => {
            item.addEventListener('click', function() {
                const urls = [
                    '<?= url('pages/repairs.php') ?>',
                    '<?= url('pages/repairs_active.php') ?>',
                    '<?= url('pages/repairs_completed.php') ?>',
                    '<?= url('pages/reports.php') ?>'
                ];

                if (urls[index]) {
                    window.location.href = urls[index];
                }
            });
        });
    }

    function setupStatsAutoRefresh() {
        // تحديث الإحصائيات كل 5 دقائق
        setInterval(function() {
            updateFooterStats();
        }, 300000); // 5 minutes
    }

    function updateFooterStats() {
        fetch('<?= url('api/footer-stats.php') ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStatsDisplay(data.stats);
                }
            })
            .catch(error => {
                console.log('Stats update failed:', error);
            });
    }

    function updateStatsDisplay(stats) {
        const statNumbers = document.querySelectorAll('.stat-number');

        if (statNumbers[0]) statNumbers[0].textContent = stats.total_repairs || 0;
        if (statNumbers[1]) statNumbers[1].textContent = stats.active_repairs || 0;
        if (statNumbers[2]) statNumbers[2].textContent = stats.completed_today || 0;
        if (statNumbers[3]) statNumbers[3].textContent = '€' + (stats.revenue_month || 0);
    }

    console.log('✅ Enhanced Footer loaded successfully');
</script>

<?php
// دالة لتنسيق مدة الجلسة
function formatSessionDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . ' seg';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . ' min';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
?>

<!-- Bootstrap 5 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?= asset('js/main.js') ?>"></script>

<?php if (isset($_SESSION['user_id'])): ?>
    <!-- Print JavaScript -->
    <script src="<?= asset('js/print.js') ?>"></script>
<?php endif; ?>

<!-- Page-specific JavaScript -->
<?php if (isset($page_scripts)): ?>
    <?= $page_scripts ?>
<?php endif; ?>

</body>
</html>