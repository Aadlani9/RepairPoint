<?php
/**
 * RepairPoint - Panel de Control
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Panel de Control';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Obtener estadísticas
$stats = getDashboardStats($shop_id);

// Obtener reparaciones recientes
$db = getDB();
$recent_repairs = $db->select(
    "SELECT r.*, b.name as brand_name, m.name as model_name, u.name as created_by_name
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     JOIN users u ON r.created_by = u.id
     WHERE r.shop_id = ? 
     ORDER BY r.created_at DESC 
     LIMIT 10",
    [$shop_id]
);

// Obtener próximas entregas
$upcoming_deliveries = getUpcomingDeliveries($shop_id, 3);

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <!-- Saludo de bienvenida -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-header bg-gradient-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            ¡Bienvenido, <?= htmlspecialchars($current_user['name']) ?>!
                        </h1>
                        <p class="mb-0 opacity-75">
                            <?= htmlspecialchars($current_user['shop_name']) ?> • 
                            <?= ucfirst($current_user['role']) ?> • 
                            <?= formatDate(date('Y-m-d H:i:s'), 'd/m/Y') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="quick-actions">
                            <a href="<?= url('pages/add_repair.php') ?>" class="btn btn-light me-2">
                                <i class="bi bi-plus-circle me-2"></i>Nueva Reparación
                            </a>
                            <a href="<?= url('pages/search.php') ?>" class="btn btn-outline-light">
                                <i class="bi bi-search me-2"></i>Buscar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number display-6 fw-bold">
                                <?= $stats['active'] ?>
                            </div>
                            <div class="stat-label">
                                Reparaciones Activas
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-tools"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number display-6 fw-bold">
                                <?= $stats['pending'] ?>
                            </div>
                            <div class="stat-label">
                                Pendientes
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number display-6 fw-bold">
                                <?= $stats['delivered_today'] ?>
                            </div>
                            <div class="stat-label">
                                Entregadas Hoy
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number display-6 fw-bold">
                                <?= $stats['month_total'] ?>
                            </div>
                            <div class="stat-label">
                                Total del Mes
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-calendar-month"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Reparaciones recientes -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Reparaciones Recientes
                    </h5>
                    <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-sm btn-outline-primary">
                        Ver todas
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_repairs)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox display-4 mb-3"></i>
                            <p class="mb-0">No hay reparaciones registradas</p>
                            <a href="<?= url('pages/add_repair.php') ?>" class="btn btn-primary btn-sm mt-2">
                                Agregar Primera Reparación
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Referencia</th>
                                        <th>Cliente</th>
                                        <th>Dispositivo</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_repairs as $repair): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">
                                                #<?= htmlspecialchars($repair['reference']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($repair['customer_name']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($repair['customer_phone']) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($repair['brand_name']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($repair['model_name']) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($repair['status']) ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= formatDateTime($repair['received_at']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('pages/repair_details.php?id=' . $repair['id']) ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-secondary" 
                                                        onclick="printTicket(<?= $repair['id'] ?>)" 
                                                        title="Imprimir ticket">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panel lateral -->
        <div class="col-lg-4">
            <!-- Próximas entregas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        Próximas Entregas
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_deliveries)): ?>
                        <p class="text-muted text-center mb-0">
                            <i class="bi bi-calendar-x me-2"></i>
                            No hay entregas próximas
                        </p>
                    <?php else: ?>
                        <?php foreach ($upcoming_deliveries as $delivery): ?>
                        <div class="d-flex align-items-center p-2 border-bottom">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-truncate">
                                    <?= htmlspecialchars($delivery['customer_name']) ?>
                                </div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($delivery['brand_name'] . ' ' . $delivery['model_name']) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-success">
                                    <?= formatDateOnly($delivery['estimated_completion']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= url('pages/add_repair.php') ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nueva Reparación
                        </a>
                        <a href="<?= url('pages/search.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-search me-2"></i>
                            Buscar Cliente
                        </a>
                        <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-list-check me-2"></i>
                            Ver Activas
                        </a>
                        <?php if (isAdmin()): ?>
                        <a href="<?= url('pages/settings.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-gear me-2"></i>
                            Configuración
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

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
                            <strong><?= htmlspecialchars($current_user['shop_name']) ?></strong>
                        </div>
                        <div class="text-muted small mb-2">
                            <i class="bi bi-person me-1"></i>
                            <?= htmlspecialchars($current_user['name']) ?> 
                            <span class="badge bg-secondary ms-1">
                                <?= ucfirst($current_user['role']) ?>
                            </span>
                        </div>
                        <div class="text-muted small mb-2">
                            <i class="bi bi-envelope me-1"></i>
                            <?= htmlspecialchars($current_user['email']) ?>
                        </div>
                        <?php if ($current_user['last_login']): ?>
                        <div class="text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            Último acceso: <?= formatDateTime($current_user['last_login']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos y estadísticas adicionales (para administradores) -->
    <?php if (isAdmin()): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Resumen del Mes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="metric">
                                <div class="metric-value h4 text-primary">
                                    <?= $stats['month_total'] ?>
                                </div>
                                <div class="metric-label text-muted">
                                    Total Reparaciones
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric">
                                <div class="metric-value h4 text-success">
                                    <?php
                                    $completed_month = $db->selectOne(
                                        "SELECT COUNT(*) as count FROM repairs 
                                         WHERE shop_id = ? AND status = 'delivered' 
                                         AND MONTH(delivered_at) = MONTH(CURDATE()) 
                                         AND YEAR(delivered_at) = YEAR(CURDATE())",
                                        [$shop_id]
                                    )['count'] ?? 0;
                                    echo $completed_month;
                                    ?>
                                </div>
                                <div class="metric-label text-muted">
                                    Entregadas
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric">
                                <div class="metric-value h4 text-warning">
                                    <?= $stats['active'] ?>
                                </div>
                                <div class="metric-label text-muted">
                                    En Proceso
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric">
                                <div class="metric-value h4 text-info">
                                    <?php
                                    $avg_time = $db->selectOne(
                                        "SELECT AVG(DATEDIFF(delivered_at, received_at)) as avg_days 
                                         FROM repairs 
                                         WHERE shop_id = ? AND status = 'delivered' 
                                         AND delivered_at IS NOT NULL 
                                         AND MONTH(delivered_at) = MONTH(CURDATE())",
                                        [$shop_id]
                                    )['avg_days'] ?? 0;
                                    echo round($avg_time, 1);
                                    ?>
                                </div>
                                <div class="metric-label text-muted">
                                    Días Promedio
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Estilos específicos del dashboard */
.welcome-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
    position: relative;
    overflow: hidden;
}

.welcome-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(50px, -50px);
}

.stat-card {
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}

.stat-card .card-body {
    position: relative;
    z-index: 2;
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.7;
    position: absolute;
    right: 1rem;
    top: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 500;
    opacity: 0.9;
}

.metric {
    padding: 1.5rem;
    border-radius: 0.5rem;
    background: rgba(13, 110, 253, 0.05);
    transition: all 0.3s ease;
    border: 1px solid rgba(13, 110, 253, 0.1);
}

.metric:hover {
    background: rgba(13, 110, 253, 0.1);
    transform: translateY(-3px);
    border-color: rgba(13, 110, 253, 0.2);
}

.metric-value {
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.875rem;
    font-weight: 500;
}

.shop-info {
    line-height: 1.8;
}

.table-hover tbody tr {
    transition: background-color 0.2s ease;
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.04);
}

.quick-actions .btn {
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.quick-actions .btn:hover {
    transform: translateY(-1px);
}

/* Cards con efectos */
.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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

.card-title {
    font-weight: 600;
    color: var(--dark-color);
}

/* Badges personalizados */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
}

/* Botones de acción */
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.375rem;
}

/* Animaciones de carga */
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

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.welcome-header {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(1) {
    animation: slideInLeft 0.6s ease-out 0.1s both;
}

.stat-card:nth-child(2) {
    animation: slideInLeft 0.6s ease-out 0.2s both;
}

.stat-card:nth-child(3) {
    animation: slideInRight 0.6s ease-out 0.3s both;
}

.stat-card:nth-child(4) {
    animation: slideInRight 0.6s ease-out 0.4s both;
}

.card {
    animation: fadeInUp 0.6s ease-out 0.5s both;
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .welcome-header {
        text-align: center;
        padding: 2rem 1rem;
    }
    
    .welcome-header h1 {
        font-size: 1.75rem;
    }
    
    .quick-actions {
        margin-top: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .quick-actions .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
    
    .stat-card .card-body {
        padding: 1rem;
        text-align: center;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .stat-icon {
        position: static;
        display: block;
        margin-bottom: 0.5rem;
        opacity: 0.8;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .metric {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .welcome-header {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        border-radius: 0;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .table th,
    .table td {
        padding: 0.5rem 0.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Efectos hover para las cards de estadísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Animar números al cargar
    animateNumbers();
    
    // Actualizar estadísticas cada 5 minutos
    setInterval(function() {
        // Aquí podrías hacer una petición AJAX para actualizar las estadísticas
        // Ajax.get('api/dashboard-stats.php').then(data => { ... });
    }, 300000); // 5 minutos
});

function animateNumbers() {
    const numbers = document.querySelectorAll('.stat-number');
    numbers.forEach(numberEl => {
        const finalNumber = parseInt(numberEl.textContent);
        let currentNumber = 0;
        const increment = Math.ceil(finalNumber / 30);
        
        const timer = setInterval(() => {
            currentNumber += increment;
            if (currentNumber >= finalNumber) {
                currentNumber = finalNumber;
                clearInterval(timer);
            }
            numberEl.textContent = currentNumber;
        }, 50);
    });
}

// Función para imprimir ticket
window.printTicket = function(repairId) {
    const printWindow = window.open(`<?= url('pages/print_ticket.php?id=') ?>${repairId}`, '_blank');
    if (printWindow) {
        printWindow.focus();
    } else {
        Utils.showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
    }
};

// Función para búsqueda rápida
function quickSearch(query) {
    if (query.length >= 3) {
        window.location.href = `<?= url('pages/search.php?q=') ?>${encodeURIComponent(query)}`;
    }
}

// Auto-refresh de notificaciones (para futuras implementaciones)
function checkNotifications() {
    // Ajax.get('api/notifications.php').then(data => { ... });
}

// Verificar notificaciones cada 2 minutos
setInterval(checkNotifications, 120000);
</script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>