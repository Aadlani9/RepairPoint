<?php
/**
 * RepairPoint - Página de Informes
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Informes y Estadísticas';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Filtros del informe
$period = $_GET['period'] ?? 'month';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$user_filter = $_GET['user'] ?? '';

// Determinar período de tiempo
$date_conditions = [];
$date_params = [];

switch ($period) {
    case 'today':
        $date_conditions[] = "DATE(r.created_at) = CURDATE()";
        $period_name = 'Hoy';
        break;
    case 'week':
        $date_conditions[] = "r.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        $period_name = 'Última semana';
        break;
    case 'month':
        $date_conditions[] = "MONTH(r.created_at) = MONTH(CURDATE()) AND YEAR(r.created_at) = YEAR(CURDATE())";
        $period_name = 'Este mes';
        break;
    case 'custom':
        if ($start_date && $end_date) {
            $date_conditions[] = "DATE(r.created_at) BETWEEN ? AND ?";
            $date_params = [$start_date, $end_date];
            $period_name = 'Período personalizado';
        } else {
            $period = 'month';
            $date_conditions[] = "MONTH(r.created_at) = MONTH(CURDATE()) AND YEAR(r.created_at) = YEAR(CURDATE())";
            $period_name = 'Este mes';
        }
        break;
    default:
        $date_conditions[] = "MONTH(r.created_at) = MONTH(CURDATE()) AND YEAR(r.created_at) = YEAR(CURDATE())";
        $period_name = 'Este mes';
}

// Construir consulta base
$where_conditions = ["r.shop_id = ?"];
$params = [$shop_id];

// Agregar condiciones de fecha
$where_conditions = array_merge($where_conditions, $date_conditions);
$params = array_merge($params, $date_params);

// Filtro de estado
if ($status_filter) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

// Filtro de usuario
if ($user_filter) {
    $where_conditions[] = "r.created_by = ?";
    $params[] = $user_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Obtener datos
$db = getDB();

// Estadísticas principales
$stats = [
    'total' => $db->selectOne(
            "SELECT COUNT(*) as count FROM repairs r WHERE $where_clause",
            $params
        )['count'] ?? 0,

    'pending' => $db->selectOne(
            "SELECT COUNT(*) as count FROM repairs r WHERE $where_clause AND r.status = 'pending'",
            array_merge($params, ['pending'])
        )['count'] ?? 0,

    'in_progress' => $db->selectOne(
            "SELECT COUNT(*) as count FROM repairs r WHERE $where_clause AND r.status = 'in_progress'",
            array_merge($params, ['in_progress'])
        )['count'] ?? 0,

    'completed' => $db->selectOne(
            "SELECT COUNT(*) as count FROM repairs r WHERE $where_clause AND r.status = 'completed'",
            array_merge($params, ['completed'])
        )['count'] ?? 0,

    'delivered' => $db->selectOne(
            "SELECT COUNT(*) as count FROM repairs r WHERE $where_clause AND r.status = 'delivered'",
            array_merge($params, ['delivered'])
        )['count'] ?? 0
];

// Ingresos
$revenue = $db->selectOne(
    "SELECT 
        SUM(CASE WHEN actual_cost > 0 THEN actual_cost ELSE estimated_cost END) as total_revenue,
        AVG(CASE WHEN actual_cost > 0 THEN actual_cost ELSE estimated_cost END) as avg_cost
     FROM repairs r 
     WHERE $where_clause AND (actual_cost > 0 OR estimated_cost > 0)",
    $params
);

$total_revenue = $revenue['total_revenue'] ?? 0;
$avg_cost = $revenue['avg_cost'] ?? 0;

// Tiempo promedio de reparación
$avg_time = $db->selectOne(
    "SELECT AVG(DATEDIFF(COALESCE(delivered_at, completed_at, CURDATE()), received_at)) as avg_days
     FROM repairs r 
     WHERE $where_clause",
    $params
)['avg_days'] ?? 0;

// Distribución por marcas
$brand_stats = $db->select(
    "SELECT b.name as brand_name, COUNT(*) as count
     FROM repairs r
     JOIN brands b ON r.brand_id = b.id
     WHERE $where_clause
     GROUP BY b.id, b.name
     ORDER BY count DESC
     LIMIT 5",
    $params
);

// Obtener usuarios para filtro
$users = $db->select(
    "SELECT id, name FROM users WHERE shop_id = ? ORDER BY name",
    [$shop_id]
);

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
                    <i class="bi bi-graph-up"></i> Informes
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
                                <i class="bi bi-graph-up me-2"></i>
                                Informes y Estadísticas
                            </h1>
                            <p class="mb-0 opacity-75">
                                Informe completo del rendimiento del taller - <?= $period_name ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="btn-group">
                                <button class="btn btn-light" onclick="window.print()">
                                    <i class="bi bi-printer me-2"></i>Imprimir
                                </button>
                                <button class="btn btn-outline-light" onclick="exportCSV()">
                                    <i class="bi bi-download me-2"></i>Exportar CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros del informe -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-funnel me-2"></i>Filtros del Informe
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="period" class="form-label">Período</label>
                        <select class="form-select" id="period" name="period" onchange="toggleCustomDates()">
                            <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hoy</option>
                            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Última semana</option>
                            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Este mes</option>
                            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Período personalizado</option>
                        </select>
                    </div>

                    <div class="col-md-2" id="startDateDiv" style="<?= $period !== 'custom' ? 'display: none;' : '' ?>">
                        <label for="start_date" class="form-label">Desde</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>

                    <div class="col-md-2" id="endDateDiv" style="<?= $period !== 'custom' ? 'display: none;' : '' ?>">
                        <label for="end_date" class="form-label">Hasta</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completado</option>
                            <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Entregado</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="user" class="form-label">Usuario</label>
                        <select class="form-select" id="user" name="user">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="bi bi-tools"></i>
                        </div>
                        <div class="stat-number h3 mb-1"><?= number_format($stats['total']) ?></div>
                        <div class="stat-label">Total Reparaciones</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card bg-warning text-dark h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="stat-number h3 mb-1"><?= number_format($stats['pending']) ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="bi bi-gear"></i>
                        </div>
                        <div class="stat-number h3 mb-1"><?= number_format($stats['in_progress']) ?></div>
                        <div class="stat-label">En Proceso</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-number h3 mb-1"><?= number_format($stats['delivered']) ?></div>
                        <div class="stat-label">Entregadas</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card bg-secondary text-white h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="bi bi-currency-euro"></i>
                        </div>
                        <div class="stat-number h4 mb-1">€<?= number_format($total_revenue, 2) ?></div>
                        <div class="stat-label">Ingresos Totales</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card bg-dark text-white h-100">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-number h4 mb-1"><?= round($avg_time, 1) ?></div>
                        <div class="stat-label">Días Promedio</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de estado -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pie-chart me-2"></i>
                            Estados de Reparaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($stats['total'] > 0): ?>
                            <canvas id="statusChart" height="300"></canvas>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <i class="bi bi-pie-chart display-4 text-muted mb-3"></i>
                                <p class="text-muted">No hay datos para mostrar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top marcas -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-award me-2"></i>
                            Top Marcas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($brand_stats)): ?>
                            <p class="text-muted text-center">No hay datos disponibles</p>
                        <?php else: ?>
                            <?php foreach ($brand_stats as $brand): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="brand-info">
                                        <div class="fw-semibold"><?= htmlspecialchars($brand['brand_name']) ?></div>
                                        <small class="text-muted"><?= $brand['count'] ?> reparaciones</small>
                                    </div>
                                    <div class="progress" style="width: 60px; height: 8px;">
                                        <?php $percentage = ($brand['count'] / $stats['total']) * 100; ?>
                                        <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Métricas Adicionales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="metric-box text-center p-3 mb-3 bg-light rounded">
                                    <div class="h5 text-primary mb-1">€<?= number_format($avg_cost, 2) ?></div>
                                    <small class="text-muted">Coste Promedio</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-box text-center p-3 mb-3 bg-light rounded">
                                    <?php $completion_rate = $stats['total'] > 0 ? ($stats['delivered'] / $stats['total']) * 100 : 0; ?>
                                    <div class="h5 text-success mb-1"><?= round($completion_rate, 1) ?>%</div>
                                    <small class="text-muted">Tasa de Finalización</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-week me-2"></i>
                            Resumen del Período
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="summary-info">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Período:</span>
                                <strong><?= $period_name ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total reparaciones:</span>
                                <strong><?= number_format($stats['total']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Ingresos:</span>
                                <strong class="text-success">€<?= number_format($total_revenue, 2) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tiempo promedio:</span>
                                <strong><?= round($avg_time, 1) ?> días</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Estilos específicos para informes */
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

        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }

        .metric-box {
            transition: all 0.3s ease;
        }

        .metric-box:hover {
            background-color: #e9ecef !important;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 2rem 1rem !important;
            }

            .stat-card .card-body {
                padding: 1rem;
            }
        }

        @media print {
            .btn, .card-header .btn-group, nav, .breadcrumb {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página cargada, inicializando gráfico...');
            console.log('Datos:', {
                pending: <?= $stats['pending'] ?>,
                in_progress: <?= $stats['in_progress'] ?>,
                completed: <?= $stats['completed'] ?>,
                delivered: <?= $stats['delivered'] ?>,
                total: <?= $stats['total'] ?>
            });

            // Esperar un poco para asegurar que todo esté cargado
            setTimeout(function() {
                setupChart();
            }, 500);
        });

        function setupChart() {
            const ctx = document.getElementById('statusChart');
            if (!ctx) return;

            const context = ctx.getContext('2d');

            // Verificar si hay datos
            const total = <?= $stats['total'] ?>;
            if (total === 0) {
                return;
            }

            // Datos del gráfico
            const chartData = [
                <?= $stats['pending'] ?>,
                <?= $stats['in_progress'] ?>,
                <?= $stats['completed'] ?>,
                <?= $stats['delivered'] ?>
            ];

            // Verificar que Chart.js esté cargado
            if (typeof Chart === 'undefined') {
                console.error('Chart.js no está cargado');
                return;
            }

            new Chart(context, {
                type: 'doughnut',
                data: {
                    labels: ['Pendientes', 'En Proceso', 'Completadas', 'Entregadas'],
                    datasets: [{
                        data: chartData,
                        backgroundColor: [
                            '#ffc107',
                            '#0dcaf0',
                            '#198754',
                            '#0d6efd'
                        ],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 13
                                }
                            }
                        }
                    }
                }
            });
        }

        function toggleCustomDates() {
            const period = document.getElementById('period').value;
            const startDateDiv = document.getElementById('startDateDiv');
            const endDateDiv = document.getElementById('endDateDiv');

            if (period === 'custom') {
                startDateDiv.style.display = 'block';
                endDateDiv.style.display = 'block';
            } else {
                startDateDiv.style.display = 'none';
                endDateDiv.style.display = 'none';
            }
        }

        function exportCSV() {
            let csv = 'Indicador,Valor\n';
            csv += `Total reparaciones,<?= $stats['total'] ?>\n`;
            csv += `Pendientes,<?= $stats['pending'] ?>\n`;
            csv += `En proceso,<?= $stats['in_progress'] ?>\n`;
            csv += `Completadas,<?= $stats['completed'] ?>\n`;
            csv += `Entregadas,<?= $stats['delivered'] ?>\n`;
            csv += `Ingresos totales,€<?= number_format($total_revenue, 2) ?>\n`;
            csv += `Tiempo promedio,<?= round($avg_time, 1) ?> días\n`;

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `informe_reparaciones_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>