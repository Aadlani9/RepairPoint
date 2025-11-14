<?php
/**
 * RepairPoint - Reparaciones Completadas
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Reparaciones Completadas';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Parámetros de paginación y filtros
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = calculateOffset($page, $limit);

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

// Construir consulta base
$where_conditions = ["r.shop_id = ?"];
$params = [$shop_id];

// Solo reparaciones entregadas
$where_conditions[] = "r.status = 'delivered'";

// Filtros adicionales
if ($date_from) {
    $where_conditions[] = "DATE(r.delivered_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(r.delivered_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where_conditions[] = "(r.reference LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

$where_clause = implode(' AND ', $where_conditions);

// Obtener total de registros para paginación
$db = getDB();
$total_records = $db->selectOne(
    "SELECT COUNT(*) as count FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     WHERE $where_clause",
    $params
)['count'] ?? 0;

$total_pages = calculateTotalPages($total_records, $limit);

// Obtener reparaciones completadas
$repairs = $db->select(
    "SELECT r.*, b.name as brand_name, m.name as model_name, u.name as created_by_name,
            DATEDIFF(r.delivered_at, r.received_at) as repair_days
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     JOIN users u ON r.created_by = u.id
     WHERE $where_clause
     ORDER BY r.delivered_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

// Obtener estadísticas
$stats = [
    'total_delivered' => $total_records,
    'this_month' => $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs 
         WHERE shop_id = ? AND status = 'delivered' 
         AND MONTH(delivered_at) = MONTH(CURDATE()) 
         AND YEAR(delivered_at) = YEAR(CURDATE())",
        [$shop_id]
    )['count'] ?? 0,
    'today' => $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs 
         WHERE shop_id = ? AND status = 'delivered' 
         AND DATE(delivered_at) = CURDATE()",
        [$shop_id]
    )['count'] ?? 0,
    'avg_days' => $db->selectOne(
        "SELECT AVG(DATEDIFF(delivered_at, received_at)) as avg_days 
         FROM repairs 
         WHERE shop_id = ? AND status = 'delivered' 
         AND delivered_at IS NOT NULL",
        [$shop_id]
    )['avg_days'] ?? 0
];

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= url('pages/dashboard.php') ?>">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="bi bi-check-circle"></i> Reparaciones Completadas
            </li>
        </ol>
    </nav>

    <!-- Header de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-success text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <i class="bi bi-check-circle me-2"></i>
                            Reparaciones Completadas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Historial de reparaciones entregadas a los clientes
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-light">
                            <i class="bi bi-tools me-2"></i>Ver Activas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= $stats['total_delivered'] ?></div>
                            <div class="stat-label">Total Entregadas</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= $stats['this_month'] ?></div>
                            <div class="stat-label">Este Mes</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-calendar-month"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= $stats['today'] ?></div>
                            <div class="stat-label">Hoy</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= round($stats['avg_days'], 1) ?></div>
                            <div class="stat-label">Días Promedio</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="search" 
                               name="search" 
                               placeholder="Nombre, teléfono, referencia..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Desde</label>
                    <input type="date" 
                           class="form-control" 
                           id="date_from" 
                           name="date_from" 
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input type="date" 
                           class="form-control" 
                           id="date_to" 
                           name="date_to" 
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-2"></i>Filtrar
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Filtros rápidos -->
            <div class="mt-3">
                <span class="text-muted me-3">Filtros rápidos:</span>
                <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="filterToday()">
                    Hoy
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="filterThisWeek()">
                    Esta Semana
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="filterThisMonth()">
                    Este Mes
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                    <i class="bi bi-x me-1"></i>Limpiar
                </button>
            </div>
            
            <?php if ($search || $date_from || $date_to): ?>
            <div class="mt-3">
                <span class="text-muted">Filtros activos:</span>
                <?php if ($search): ?>
                    <span class="badge bg-primary me-2">Búsqueda: <?= htmlspecialchars($search) ?></span>
                <?php endif; ?>
                <?php if ($date_from): ?>
                    <span class="badge bg-secondary me-2">Desde: <?= formatDateOnly($date_from) ?></span>
                <?php endif; ?>
                <?php if ($date_to): ?>
                    <span class="badge bg-secondary me-2">Hasta: <?= formatDateOnly($date_to) ?></span>
                <?php endif; ?>
                <a href="<?= url('pages/repairs_completed.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Limpiar filtros
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de reparaciones completadas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-check me-2"></i>
                Historial de Reparaciones
                <span class="badge bg-success ms-2"><?= $total_records ?></span>
            </h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                    <i class="bi bi-download"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="printReport()">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($repairs)): ?>
                <div class="text-center p-5">
                    <i class="bi bi-archive display-4 text-muted mb-3"></i>
                    <h5 class="text-muted">No hay reparaciones completadas</h5>
                    <p class="text-muted mb-3">
                        <?php if ($search || $date_from || $date_to): ?>
                            No se encontraron reparaciones que coincidan con los filtros aplicados.
                        <?php else: ?>
                            Aún no se han completado reparaciones en el sistema.
                        <?php endif; ?>
                    </p>
                    <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-primary">
                        <i class="bi bi-tools me-2"></i>Ver Reparaciones Activas
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
                                <th>Problema</th>
                                <th>Duración</th>
                                <th>Coste</th>
                                <th>Fecha Entrega</th>
                                <th>Entregado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repairs as $repair): ?>
                            <tr class="repair-row">
                                <td>
                                    <div class="fw-bold text-success">
                                        #<?= htmlspecialchars($repair['reference']) ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?= formatDateOnly($repair['received_at']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($repair['customer_name']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= htmlspecialchars($repair['customer_phone']) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="device-info">
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($repair['brand_name']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($repair['model_name']) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="issue-preview" title="<?= htmlspecialchars($repair['issue_description']) ?>">
                                        <?= htmlspecialchars(mb_strimwidth($repair['issue_description'], 0, 40, '...')) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="duration-info">
                                        <span class="badge bg-info">
                                            <?= $repair['repair_days'] ?> días
                                        </span>
                                        <?php if ($repair['repair_days'] <= 2): ?>
                                            <div class="mt-1">
                                                <small class="badge bg-success">Rápido</small>
                                            </div>
                                        <?php elseif ($repair['repair_days'] > 7): ?>
                                            <div class="mt-1">
                                                <small class="badge bg-warning">Lento</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($repair['actual_cost']): ?>
                                        <div class="fw-bold text-success">
                                            €<?= number_format($repair['actual_cost'], 2) ?>
                                        </div>
                                        <?php if ($repair['estimated_cost'] && $repair['actual_cost'] != $repair['estimated_cost']): ?>
                                            <small class="text-muted">
                                                Est: €<?= number_format($repair['estimated_cost'], 2) ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php elseif ($repair['estimated_cost']): ?>
                                        <div class="text-muted">
                                            €<?= number_format($repair['estimated_cost'], 2) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="delivery-info">
                                        <div class="fw-semibold">
                                            <?= formatDate($repair['delivered_at'], 'd/m/Y') ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= formatDate($repair['delivered_at'], 'H:i') ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($repair['delivered_by'] ?: $repair['created_by_name']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('pages/repair_details.php?id=' . $repair['id']) ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-success" 
                                                onclick="printTicket(<?= $repair['id'] ?>)" 
                                                title="Reimprimir ticket">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                        <?php if (canDeleteRepairs()): ?>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteRepair(<?= $repair['id'] ?>)" 
                                                title="Eliminar registro"
                                                data-confirm-delete="¿Estás seguro de eliminar esta reparación? Esta acción no se puede deshacer.">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <?= generatePagination($page, $total_pages, $_SERVER['PHP_SELF'], [
                'search' => $search,
                'date_from' => $date_from,
                'date_to' => $date_to
            ]) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Estilos específicos para reparaciones completadas */
.page-header {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
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

.stat-card {
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}

.repair-row {
    transition: background-color 0.2s ease;
}

.repair-row:hover {
    background-color: rgba(25, 135, 84, 0.04);
}

.customer-info,
.device-info {
    min-width: 150px;
}

.issue-preview {
    max-width: 200px;
    cursor: help;
}

.duration-info,
.delivery-info {
    min-width: 120px;
    font-size: 0.875rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.table th {
    background-color: rgba(25, 135, 84, 0.1);
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    white-space: nowrap;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35rem 0.65rem;
}

/* Indicadores de rendimiento */
.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

/* Animaciones */
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

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        text-align: center;
        padding: 2rem 1rem !important;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .customer-info,
    .device-info,
    .issue-preview {
        min-width: auto;
        max-width: 120px;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .stat-card .card-body {
        padding: 1rem;
        text-align: center;
    }
    
    .stat-icon {
        position: static;
        display: block;
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
    
    .table th,
    .table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
    }
    
    .issue-preview {
        max-width: 80px;
    }
    
    .duration-info,
    .delivery-info {
        min-width: 80px;
        font-size: 0.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar fechas por defecto
    setupDateInputs();
});

function setupDateInputs() {
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    // Evitar fechas futuras
    const today = new Date().toISOString().split('T')[0];
    dateFromInput.max = today;
    dateToInput.max = today;
    
    // Validar rango de fechas
    dateFromInput.addEventListener('change', function() {
        if (this.value && dateToInput.value && this.value > dateToInput.value) {
            dateToInput.value = this.value;
        }
        if (this.value) {
            dateToInput.min = this.value;
        }
    });
    
    dateToInput.addEventListener('change', function() {
        if (this.value && dateFromInput.value && this.value < dateFromInput.value) {
            dateFromInput.value = this.value;
        }
        if (this.value) {
            dateFromInput.max = this.value;
        }
    });
}

// Funciones de filtros rápidos
window.filterToday = function() {
    const today = new Date().toISOString().split('T')[0];
    const url = new URL(window.location);
    url.searchParams.set('date_from', today);
    url.searchParams.set('date_to', today);
    url.searchParams.delete('page');
    window.location.href = url.toString();
};

window.filterThisWeek = function() {
    const today = new Date();
    const firstDay = new Date(today.setDate(today.getDate() - today.getDay()));
    const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 6));
    
    const dateFrom = firstDay.toISOString().split('T')[0];
    const dateTo = lastDay.toISOString().split('T')[0];
    
    const url = new URL(window.location);
    url.searchParams.set('date_from', dateFrom);
    url.searchParams.set('date_to', dateTo);
    url.searchParams.delete('page');
    window.location.href = url.toString();
};

window.filterThisMonth = function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const dateFrom = firstDay.toISOString().split('T')[0];
    const dateTo = lastDay.toISOString().split('T')[0];
    
    const url = new URL(window.location);
    url.searchParams.set('date_from', dateFrom);
    url.searchParams.set('date_to', dateTo);
    url.searchParams.delete('page');
    window.location.href = url.toString();
};

window.clearFilters = function() {
    window.location.href = '<?= url('pages/repairs_completed.php') ?>';
};

// Función para refrescar tabla
window.refreshTable = function() {
    Utils.showNotification('Actualizando datos...', 'info', 2000);
    setTimeout(() => {
        location.reload();
    }, 500);
};

// Función para exportar datos
window.exportData = function() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `<?= url('api/repairs.php') ?>?action=export_completed&${params.toString()}`;
    
    // Crear enlace temporal para descarga
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `reparaciones_completadas_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Utils.showNotification('Exportando datos...', 'info');
};

// Función para imprimir reporte
window.printReport = function() {
    const params = new URLSearchParams(window.location.search);
    const reportUrl = `<?= url('pages/print_report.php') ?>?type=completed&${params.toString()}`;
    
    const printWindow = window.open(reportUrl, '_blank');
    if (printWindow) {
        printWindow.focus();
    } else {
        Utils.showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
    }
};

// Función para imprimir ticket
window.printTicket = function(repairId) {
    const printWindow = window.open(`<?= url('pages/print_selector.php?id=') ?>${repairId}`, '_blank');
    if (printWindow) {
        printWindow.focus();
    } else {
        Utils.showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
    }
};

// Función para eliminar reparación
window.deleteRepair = function(repairId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta reparación? Esta acción no se puede deshacer.')) {
        Ajax.post('<?= url('api/repairs.php') ?>', {
            action: 'delete',
            repair_id: repairId
        }).then(response => {
            if (response.success) {
                Utils.showNotification('Reparación eliminada correctamente', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                Utils.showNotification(response.message || 'Error al eliminar la reparación', 'error');
            }
        }).catch(error => {
            Utils.showNotification('Error de conexión', 'error');
        });
    }
};

// Búsqueda en tiempo real
const searchInput = document.getElementById('search');
if (searchInput) {
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                this.form.submit();
            }
        }, 500);
    });
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F para enfocar búsqueda
    if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('search').focus();
    }
    
    // Ctrl/Cmd + E para exportar
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportData();
    }
    
    // Ctrl/Cmd + P para imprimir reporte
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printReport();
    }
    
    // R para refrescar
    if (e.key === 'r' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        refreshTable();
    }
});

// Función para mostrar estadísticas detalladas
window.showDetailedStats = function() {
    Ajax.get('<?= url('api/reports.php') ?>?type=completed_stats').then(response => {
        if (response.success) {
            const data = response.data;
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Estadísticas Generales</h6>
                        <ul class="list-unstyled">
                            <li><strong>Total reparaciones:</strong> ${data.total}</li>
                            <li><strong>Tiempo promedio:</strong> ${data.avg_days} días</li>
                            <li><strong>Tiempo mínimo:</strong> ${data.min_days} días</li>
                            <li><strong>Tiempo máximo:</strong> ${data.max_days} días</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Por Prioridad</h6>
                        <ul class="list-unstyled">
                            <li><strong>Alta:</strong> ${data.high_priority}</li>
                            <li><strong>Media:</strong> ${data.medium_priority}</li>
                            <li><strong>Baja:</strong> ${data.low_priority}</li>
                        </ul>
                    </div>
                </div>
            `;
            
            // Mostrar en modal
            const modal = new bootstrap.Modal(document.createElement('div'));
            // Implementar modal de estadísticas detalladas
        }
    });
};

// Auto-actualización cada 5 minutos si la página está visible
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // Verificar si hay nuevas reparaciones completadas
        // Ajax.get('api/check-new-completed.php').then(response => { ... });
    }
}, 300000);
</script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>