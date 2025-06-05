<?php
/**
 * RepairPoint - Búsqueda Avanzada
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Búsqueda Avanzada';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Parámetros de búsqueda
$search_query = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$brand_filter = $_GET['brand'] ?? '';

$results = [];
$total_results = 0;

// Realizar búsqueda si hay parámetros
if ($search_query || $status_filter || $priority_filter || $date_from || $date_to || $brand_filter) {
    $db = getDB();
    
    // Construir consulta
    $where_conditions = ["r.shop_id = ?"];
    $params = [$shop_id];
    
    // Búsqueda por texto
    if ($search_query) {
        $search_conditions = [
            "r.reference LIKE ?",
            "r.customer_name LIKE ?",
            "r.customer_phone LIKE ?",
            "b.name LIKE ?",
            "m.name LIKE ?",
            "r.issue_description LIKE ?"
        ];
        
        $search_term = '%' . $search_query . '%';
        $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
        $params = array_merge($params, array_fill(0, count($search_conditions), $search_term));
    }
    
    // Filtros adicionales
    if ($status_filter) {
        $where_conditions[] = "r.status = ?";
        $params[] = $status_filter;
    }
    
    if ($priority_filter) {
        $where_conditions[] = "r.priority = ?";
        $params[] = $priority_filter;
    }
    
    if ($date_from) {
        $where_conditions[] = "DATE(r.received_at) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "DATE(r.received_at) <= ?";
        $params[] = $date_to;
    }
    
    if ($brand_filter) {
        $where_conditions[] = "r.brand_id = ?";
        $params[] = $brand_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Contar resultados totales
    $total_results = $db->selectOne(
        "SELECT COUNT(*) as count FROM repairs r 
         JOIN brands b ON r.brand_id = b.id 
         JOIN models m ON r.model_id = m.id 
         WHERE $where_clause",
        $params
    )['count'] ?? 0;
    
    // Obtener resultados
    $results = $db->select(
        "SELECT r.*, b.name as brand_name, m.name as model_name, u.name as created_by_name,
                DATEDIFF(COALESCE(r.delivered_at, NOW()), r.received_at) as repair_days
         FROM repairs r 
         JOIN brands b ON r.brand_id = b.id 
         JOIN models m ON r.model_id = m.id 
         JOIN users u ON r.created_by = u.id
         WHERE $where_clause
         ORDER BY r.received_at DESC
         LIMIT 100",
        $params
    );
}

// Obtener marcas para filtros
$db = getDB();
$brands = $db->select("SELECT * FROM brands ORDER BY name");

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
                <i class="bi bi-search"></i> Búsqueda Avanzada
            </li>
        </ol>
    </nav>

    <!-- Header de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-secondary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <i class="bi bi-search me-2"></i>
                            Búsqueda Avanzada
                        </h1>
                        <p class="mb-0 opacity-75">
                            Encuentra reparaciones usando múltiples criterios
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="search-stats">
                            <?php if ($total_results > 0): ?>
                                <span class="badge bg-light text-dark fs-6">
                                    <?= $total_results ?> resultado<?= $total_results !== 1 ? 's' : '' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de búsqueda avanzada -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>
                Criterios de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="search-form">
                <div class="row g-3">
                    <!-- Búsqueda principal -->
                    <div class="col-md-6">
                        <label for="q" class="form-label">Buscar por</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="q" 
                                   name="q" 
                                   placeholder="Nombre, teléfono, referencia, marca, modelo..."
                                   value="<?= htmlspecialchars($search_query) ?>"
                                   autocomplete="off">
                        </div>
                        <div class="form-text">
                            Busca en nombre, teléfono, referencia, marca, modelo y descripción
                        </div>
                    </div>

                    <!-- Estado -->
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
                            <option value="delivered" <?= ($status_filter === 'delivered') ? 'selected' : '' ?>>
                                Entregado
                            </option>
                        </select>
                    </div>

                    <!-- Prioridad -->
                    <div class="col-md-3">
                        <label for="priority" class="form-label">Prioridad</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">Todas las prioridades</option>
                            <option value="low" <?= ($priority_filter === 'low') ? 'selected' : '' ?>>
                                Baja
                            </option>
                            <option value="medium" <?= ($priority_filter === 'medium') ? 'selected' : '' ?>>
                                Media
                            </option>
                            <option value="high" <?= ($priority_filter === 'high') ? 'selected' : '' ?>>
                                Alta
                            </option>
                        </select>
                    </div>

                    <!-- Marca -->
                    <div class="col-md-4">
                        <label for="brand" class="form-label">Marca</label>
                        <select class="form-select" id="brand" name="brand">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" 
                                        <?= ($brand_filter == $brand['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fecha desde -->
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">Desde</label>
                        <input type="date" 
                               class="form-control" 
                               id="date_from" 
                               name="date_from" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>

                    <!-- Fecha hasta -->
                    <div class="col-md-4">
                        <label for="date_to" class="form-label">Hasta</label>
                        <input type="date" 
                               class="form-control" 
                               id="date_to" 
                               name="date_to" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Buscar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                <i class="bi bi-x me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Búsquedas rápidas -->
                        <div class="quick-searches">
                            <span class="text-muted me-2">Rápido:</span>
                            <button type="button" class="btn btn-outline-primary btn-sm me-1" onclick="quickSearch('pending')">
                                Pendientes
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="quickSearch('completed')">
                                Completados
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="quickSearch('high')">
                                Alta Prioridad
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados de búsqueda -->
    <?php if (isset($_GET['q']) || isset($_GET['status']) || isset($_GET['priority']) || isset($_GET['date_from']) || isset($_GET['brand'])): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-check me-2"></i>
                Resultados de Búsqueda
                <span class="badge bg-primary ms-2"><?= $total_results ?></span>
            </h5>
            <?php if ($total_results > 0): ?>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="exportResults()">
                    <i class="bi bi-download"></i> Exportar
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="printResults()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($results)): ?>
                <div class="text-center p-5">
                    <i class="bi bi-search display-4 text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron resultados</h5>
                    <p class="text-muted mb-3">
                        Intenta modificar los criterios de búsqueda o usar términos más generales.
                    </p>
                    <button class="btn btn-primary" onclick="clearSearch()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Nueva Búsqueda
                    </button>
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
                                <th>Duración</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $repair): ?>
                            <tr class="result-row" data-id="<?= $repair['id'] ?>">
                                <td>
                                    <div class="fw-bold text-primary">
                                        #<?= htmlspecialchars($repair['reference']) ?>
                                    </div>
                                    <?php if ($repair['estimated_cost'] || $repair['actual_cost']): ?>
                                        <small class="text-success">
                                            €<?= number_format($repair['actual_cost'] ?: $repair['estimated_cost'], 2) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="fw-semibold">
                                            <?= highlightSearchTerm(htmlspecialchars($repair['customer_name']), $search_query) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= highlightSearchTerm(htmlspecialchars($repair['customer_phone']), $search_query) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="device-info">
                                        <div class="fw-semibold">
                                            <?= highlightSearchTerm(htmlspecialchars($repair['brand_name']), $search_query) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= highlightSearchTerm(htmlspecialchars($repair['model_name']), $search_query) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="issue-preview" title="<?= htmlspecialchars($repair['issue_description']) ?>">
                                        <?= highlightSearchTerm(
                                            htmlspecialchars(mb_strimwidth($repair['issue_description'], 0, 50, '...')), 
                                            $search_query
                                        ) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= getStatusBadge($repair['status']) ?>
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
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $repair['repair_days'] ?> días
                                    </span>
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
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_results > 100): ?>
                <div class="card-footer">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Se muestran los primeros 100 resultados de <?= $total_results ?> encontrados. 
                        Refina tu búsqueda para obtener resultados más específicos.
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Estado inicial sin búsqueda -->
    <div class="card">
        <div class="card-body text-center p-5">
            <i class="bi bi-search display-1 text-muted mb-4"></i>
            <h4 class="text-muted mb-3">Búsqueda Avanzada</h4>
            <p class="text-muted mb-4">
                Utiliza los filtros de arriba para encontrar reparaciones específicas.<br>
                Puedes buscar por nombre, teléfono, referencia, marca, modelo o descripción del problema.
            </p>
            
            <!-- Sugerencias de búsqueda -->
            <div class="search-suggestions">
                <h6 class="text-muted mb-3">Búsquedas sugeridas:</h6>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="suggestedSearch('iPhone')">
                        iPhone
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="suggestedSearch('Samsung')">
                        Samsung
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="suggestedSearch('pantalla rota')">
                        Pantalla rota
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="suggestedSearch('no carga')">
                        No carga
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Estilos específicos para búsqueda */
.page-header {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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

.search-form {
    background: rgba(108, 117, 125, 0.05);
    border-radius: 1rem;
    padding: 1rem;
}

.result-row {
    transition: all 0.2s ease;
}

.result-row:hover {
    background-color: rgba(108, 117, 125, 0.04);
    transform: translateX(2px);
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
    min-width: 100px;
    font-size: 0.875rem;
}

.search-suggestions .btn {
    margin: 0.25rem;
}

/* Resaltado de términos de búsqueda */
.highlight {
    background-color: yellow;
    font-weight: bold;
    padding: 0.1rem 0.2rem;
    border-radius: 0.2rem;
}

.quick-searches {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.25rem;
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

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        text-align: center;
        padding: 2rem 1rem !important;
    }
    
    .search-form {
        padding: 0.75rem;
    }
    
    .quick-searches {
        justify-content: center;
        margin-top: 1rem;
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
    
    .search-form {
        padding: 0.5rem;
    }
    
    .table th,
    .table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
    }
    
    .search-suggestions .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar búsqueda en tiempo real
    setupLiveSearch();
    
    // Configurar validación de fechas
    setupDateValidation();
    
    // Auto-focus en el campo de búsqueda
    const searchInput = document.getElementById('q');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
});

function setupLiveSearch() {
    const searchInput = document.getElementById('q');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Búsqueda automática después de 1 segundo de inactividad
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3) {
                    // Opcional: hacer búsqueda AJAX en tiempo real
                    // performLiveSearch(this.value);
                }
            }, 1000);
        });
    }
}

function setupDateValidation() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', function() {
            if (this.value && dateTo.value && this.value > dateTo.value) {
                dateTo.value = this.value;
            }
            if (this.value) {
                dateTo.min = this.value;
            }
        });
        
        dateTo.addEventListener('change', function() {
            if (this.value && dateFrom.value && this.value < dateFrom.value) {
                dateFrom.value = this.value;
            }
            if (this.value) {
                dateFrom.max = this.value;
            }
        });
    }
}

// Funciones globales
window.clearSearch = function() {
    window.location.href = '<?= url('pages/search.php') ?>';
};

window.quickSearch = function(type) {
    const form = document.querySelector('.search-form');
    const statusSelect = form.querySelector('[name="status"]');
    const prioritySelect = form.querySelector('[name="priority"]');
    
    // Limpiar filtros actuales
    form.reset();
    
    switch(type) {
        case 'pending':
            statusSelect.value = 'pending';
            break;
        case 'completed':
            statusSelect.value = 'completed';
            break;
        case 'high':
            prioritySelect.value = 'high';
            break;
    }
    
    form.submit();
};

window.suggestedSearch = function(term) {
    const searchInput = document.getElementById('q');
    searchInput.value = term;
    document.querySelector('.search-form').submit();
};

window.exportResults = function() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `<?= url('api/search.php') ?>?${params.toString()}`;
    
    // Crear enlace temporal para descarga
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `busqueda_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Utils.showNotification('Exportando resultados...', 'info');
};

window.printResults = function() {
    const params = new URLSearchParams(window.location.search);
    const printUrl = `<?= url('pages/print_report.php') ?>?type=search&${params.toString()}`;
    
    const printWindow = window.open(printUrl, '_blank');
    if (printWindow) {
        printWindow.focus();
    } else {
        Utils.showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
    }
};

window.printTicket = function(repairId) {
    const printWindow = window.open(`<?= url('pages/print_ticket.php?id=') ?>${repairId}`, '_blank');
    if (printWindow) {
        printWindow.focus();
    } else {
        Utils.showNotification('Por favor, permite las ventanas emergentes para imprimir', 'warning');
    }
};

// Búsqueda AJAX en tiempo real (opcional)
async function performLiveSearch(query) {
    try {
        const response = await Ajax.get(`<?= url('api/search.php') ?>?q=${encodeURIComponent(query)}&limit=5`);
        
        if (response.success && response.data.length > 0) {
            showLiveResults(response.data);
        }
    } catch (error) {
        console.log('Error en búsqueda en vivo:', error);
    }
}

function showLiveResults(results) {
    // Implementar dropdown con resultados en tiempo real
    // Crear un dropdown debajo del campo de búsqueda con los resultados
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F para enfocar búsqueda
    if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('q').focus();
    }
    
    // Enter para buscar
    if (e.key === 'Enter' && document.activeElement.tagName !== 'BUTTON') {
        e.preventDefault();
        document.querySelector('.search-form').submit();
    }
    
    // Escape para limpiar
    if (e.key === 'Escape') {
        clearSearch();
    }
});
</script>

<?php
// Función para resaltar términos de búsqueda
function highlightSearchTerm($text, $search) {
    if (!$search || strlen($search) < 2) {
        return $text;
    }
    
    return preg_replace(
        '/(' . preg_quote($search, '/') . ')/i',
        '<span class="highlight">$1</span>',
        $text
    );
}

// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>