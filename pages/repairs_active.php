<?php
/**
 * RepairPoint - Reparaciones Activas
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Reparaciones Activas';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Parámetros de paginación y filtros
$page = max(1, intval($_GET['page'] ?? 1));
$limit = RECORDS_PER_PAGE;
$offset = calculateOffset($page, $limit);

$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = trim($_GET['search'] ?? '');

// Construir consulta base
$where_conditions = ["r.shop_id = ?"];
$params = [$shop_id];

// Solo reparaciones activas (no entregadas)
$where_conditions[] = "r.status IN ('pending', 'in_progress', 'completed')";

// Filtros adicionales
if ($status_filter) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "r.priority = ?";
    $params[] = $priority_filter;
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

// Obtener reparaciones
$repairs = $db->select(
    "SELECT r.*, b.name as brand_name, m.name as model_name, u.name as created_by_name
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     JOIN users u ON r.created_by = u.id
     WHERE $where_clause
     ORDER BY 
        CASE 
            WHEN r.priority = 'high' THEN 1
            WHEN r.priority = 'medium' THEN 2
            WHEN r.priority = 'low' THEN 3
        END,
        r.received_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

// Obtener estadísticas rápidas
$stats = [
    'pending' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status = 'pending'", [$shop_id])['count'] ?? 0,
    'in_progress' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status = 'in_progress'", [$shop_id])['count'] ?? 0,
    'completed' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND status = 'completed'", [$shop_id])['count'] ?? 0,
    'high_priority' => $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE shop_id = ? AND priority = 'high' AND status IN ('pending', 'in_progress', 'completed')", [$shop_id])['count'] ?? 0
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
                <i class="bi bi-tools"></i> Reparaciones Activas
            </li>
        </ol>
    </nav>

    <!-- Header de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <i class="bi bi-tools me-2"></i>
                            Reparaciones Activas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Gestiona las reparaciones en proceso y pendientes
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?= url('pages/add_repair.php') ?>" class="btn btn-light">
                            <i class="bi bi-plus-circle me-2"></i>Nueva Reparación
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= $stats['pending'] ?></div>
                            <div class="stat-label">Pendientes</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-clock"></i>
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
                            <div class="stat-number h4 mb-1"><?= $stats['in_progress'] ?></div>
                            <div class="stat-label">En Proceso</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-gear"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= $stats['completed'] ?></div>
                            <div class="stat-label">Completadas</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-number h4 mb-1"><?= $stats['high_priority'] ?></div>
                            <div class="stat-label">Alta Prioridad</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-exclamation-triangle"></i>
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
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos los estados</option>
                        <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>
                            Pendiente
                        </option>
                        <option value="in_progress" <?= ($status_filter === 'in_progress') ? 'selected' : '' ?>>
                            En Proceso
                        </option>
                        <option value="completed" <?= ($status_filter === 'completed') ? 'selected' : '' ?>>
                            Completado
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="priority" class="form-label">Prioridad</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">Todas las prioridades</option>
                        <option value="high" <?= ($priority_filter === 'high') ? 'selected' : '' ?>>
                            Alta
                        </option>
                        <option value="medium" <?= ($priority_filter === 'medium') ? 'selected' : '' ?>>
                            Media
                        </option>
                        <option value="low" <?= ($priority_filter === 'low') ? 'selected' : '' ?>>
                            Baja
                        </option>
                    </select>
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
            
            <?php if ($search || $status_filter || $priority_filter): ?>
            <div class="mt-3">
                <span class="text-muted">Filtros activos:</span>
                <?php if ($search): ?>
                    <span class="badge bg-primary me-2">Búsqueda: <?= htmlspecialchars($search) ?></span>
                <?php endif; ?>
                <?php if ($status_filter): ?>
                    <span class="badge bg-secondary me-2">Estado: <?= getStatusName($status_filter) ?></span>
                <?php endif; ?>
                <?php if ($priority_filter): ?>
                    <span class="badge bg-warning me-2">Prioridad: <?= ucfirst($priority_filter) ?></span>
                <?php endif; ?>
                <a href="<?= url('pages/repairs_active.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Limpiar filtros
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de reparaciones -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-check me-2"></i>
                Listado de Reparaciones
                <span class="badge bg-primary ms-2"><?= $total_records ?></span>
            </h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                    <i class="bi bi-download"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($repairs)): ?>
                <div class="text-center p-5">
                    <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                    <h5 class="text-muted">No hay reparaciones activas</h5>
                    <p class="text-muted mb-3">
                        <?php if ($search || $status_filter || $priority_filter): ?>
                            No se encontraron reparaciones que coincidan con los filtros aplicados.
                        <?php else: ?>
                            Aún no hay reparaciones registradas en el sistema.
                        <?php endif; ?>
                    </p>
                    <a href="<?= url('pages/add_repair.php') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Primera Reparación
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
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Fecha</th>
                                <th>Técnico</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repairs as $repair): ?>
                            <tr class="repair-row" data-id="<?= $repair['id'] ?>">
                                <td>
                                    <div class="fw-bold text-primary">
                                        #<?= htmlspecialchars($repair['reference']) ?>
                                    </div>
                                    <?php if ($repair['estimated_cost']): ?>
                                        <small class="text-success">
                                            €<?= number_format($repair['estimated_cost'], 2) ?>
                                        </small>
                                    <?php endif; ?>
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
                                        <?= htmlspecialchars(mb_strimwidth($repair['issue_description'], 0, 50, '...')) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= getStatusBadge($repair['status']) ?>
                                    <?php if ($repair['status'] === 'completed' && !$repair['delivered_at']): ?>
                                        <div class="mt-1">
                                            <small class="badge bg-warning">Listo para entregar</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= getPriorityBadge($repair['priority']) ?>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <div class="small">
                                            <?= formatDate($repair['received_at'], 'd/m/Y') ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= formatDate($repair['received_at'], 'H:i') ?>
                                        </small>
                                        <div class="small text-muted">
                                            Hace <?= daysSince($repair['received_at']) ?> días
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($repair['created_by_name']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('pages/repair_details.php?id=' . $repair['id']) ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= url('pages/edit_repair.php?id=' . $repair['id']) ?>" 
                                           class="btn btn-outline-secondary" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-outline-success" 
                                                onclick="printTicket(<?= $repair['id'] ?>)" 
                                                title="Imprimir ticket">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                        <?php if ($repair['status'] === 'completed'): ?>
                                        <button class="btn btn-outline-info" 
                                                onclick="markAsDelivered(<?= $repair['id'] ?>)" 
                                                title="Marcar como entregado">
                                            <i class="bi bi-hand-thumbs-up"></i>
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
                <?= generatePagination($page, $total_pages, '', [
                    'search' => $search,
                    'status' => $status_filter,
                    'priority' => $priority_filter
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para marcar como entregado -->
<div class="modal fade" id="deliveryModal" tabindex="-1" aria-labelledby="deliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryModalLabel">
                    <i class="bi bi-hand-thumbs-up me-2"></i>Marcar como Entregado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="deliveryForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Confirma que la reparación ha sido entregada al cliente.
                    </div>
                    
                    <div class="mb-3">
                        <label for="delivered_by" class="form-label">Entregado por</label>
                        <input type="text" 
                               class="form-control" 
                               id="delivered_by" 
                               name="delivered_by" 
                               value="<?= htmlspecialchars($current_user['name']) ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="delivery_notes" class="form-label">Notas de entrega (opcional)</label>
                        <textarea class="form-control" 
                                  id="delivery_notes" 
                                  name="delivery_notes" 
                                  rows="3" 
                                  placeholder="Comentarios adicionales sobre la entrega..."></textarea>
                    </div>
                    
                    <input type="hidden" id="repair_id_to_deliver" name="repair_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check me-2"></i>Confirmar Entrega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para reparaciones activas */
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
    background-color: rgba(13, 110, 253, 0.04);
}

.customer-info,
.device-info {
    min-width: 150px;
}

.issue-preview {
    max-width: 200px;
    cursor: help;
}

.date-info {
    min-width: 120px;
    font-size: 0.875rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.table th {
    background-color: var(--light-color);
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    white-space: nowrap;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35rem 0.65rem;
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
        max-width: 150px;
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
        max-width: 100px;
    }
    
    .date-info {
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
    
    // Configurar formulario de entrega
    setupDeliveryForm();
    
    // Auto-refresh cada 2 minutos
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            // Solo refrescar si la página está visible
            // refreshTable();
        }
    }, 120000);
});

function setupDeliveryForm() {
    const deliveryForm = document.getElementById('deliveryForm');
    
    if (deliveryForm) {
        deliveryForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            FormHandler.setButtonLoading(submitBtn, true);
            
            try {
                const response = await Ajax.post('<?= url('api/repairs.php') ?>', {
                    action: 'mark_delivered',
                    repair_id: formData.get('repair_id'),
                    delivered_by: formData.get('delivered_by'),
                    delivery_notes: formData.get('delivery_notes')
                });
                
                if (response.success) {
                    Utils.showNotification('Reparación marcada como entregada', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('deliveryModal')).hide();
                    
                    // Actualizar la fila o recargar la página
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    Utils.showNotification(response.message || 'Error al marcar como entregada', 'error');
                }
            } catch (error) {
                Utils.showNotification('Error de conexión', 'error');
            } finally {
                FormHandler.setButtonLoading(submitBtn, false);
            }
        });
    }
}

// Funciones globales
window.printTicket = function(repairId) {
    const printWindow = window.open(`<?= url('pages/print_ticket.php?id=') ?>${repairId}`, '_blank');
    if (printWindow) {
        printWindow.focus();
    } else {
        Utils.showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
    }
};

window.refreshTable = function() {
    Utils.showNotification('Actualizando datos...', 'info', 2000);
    setTimeout(() => {
        location.reload();
    }, 500);
};

window.exportData = function() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `<?= url('api/repairs.php') ?>?action=export&${params.toString()}`;
    
    // Crear enlace temporal para descarga
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `reparaciones_activas_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Utils.showNotification('Exportando datos...', 'info');
};

// Función para filtro rápido por estado
window.filterByStatus = function(status) {
    const url = new URL(window.location);
    url.searchParams.set('status', status);
    url.searchParams.delete('page'); // Reset página
    window.location.href = url.toString();
};

// Función para filtro rápido por prioridad
window.filterByPriority = function(priority) {
    const url = new URL(window.location);
    url.searchParams.set('priority', priority);
    url.searchParams.delete('page'); // Reset página
    window.location.href = url.toString();
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
    
    // Ctrl/Cmd + N para nueva reparación
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        window.location.href = '<?= url('pages/add_repair.php') ?>';
    }
    
    // R para refrescar
    if (e.key === 'r' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        refreshTable();
    }
});

// Notificación de nuevas reparaciones (simulado)
function checkForNewRepairs() {
    // Esta función se puede implementar para verificar nuevas reparaciones
    // Ajax.get('api/check-new-repairs.php').then(response => { ... });
}

// Verificar nuevas reparaciones cada 5 minutos
setInterval(checkForNewRepairs, 300000);
</script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>