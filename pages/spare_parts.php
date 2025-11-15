<?php
/**
 * RepairPoint - Gestión de Repuestos
 * صفحة قطع الغيار الرئيسية مع واجهة موحدة وصلاحيات مختلفة
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

// التحقق من صلاحيات عرض قطع الغيار
checkSparePartsPageAccess('spare_parts');

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];
$user_role = $current_user['role'];
$page_title = 'Gestión de Repuestos';

// الحصول على صلاحيات المستخدم
$permissions = getCurrentUserSparePartsPermissions();

// معالجة طلبات البحث
$search_term = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand_id = intval($_GET['brand_id'] ?? 0);
$model_id = intval($_GET['model_id'] ?? 0);

// معالجة الترقيم
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// الحصول على البيانات
$db = getDB();

// الحصول على الماركات والموديلات للفلاتر
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$models = [];
if ($brand_id > 0) {
    $models = $db->select("SELECT * FROM models WHERE brand_id = ? ORDER BY name", [$brand_id]);
}

// الحصول على الفئات
$categories = getSparePartsCategories($shop_id);

// البحث في قطع الغيار
$parts = searchSpareParts($shop_id, $search_term, $category, '', $brand_id, $model_id, $limit, $offset);

// حساب إجمالي النتائج للترقيم
$total_query = "SELECT COUNT(DISTINCT sp.id) as total 
                FROM spare_parts sp 
                LEFT JOIN spare_parts_compatibility spc ON sp.id = spc.spare_part_id
                WHERE sp.shop_id = ? AND sp.is_active = TRUE";
$total_params = [$shop_id];

if (!empty($search_term)) {
    $total_query .= " AND (sp.part_name LIKE ? OR sp.part_code LIKE ?)";
    $total_params[] = '%' . $search_term . '%';
    $total_params[] = '%' . $search_term . '%';
}

if (!empty($category)) {
    $total_query .= " AND sp.category = ?";
    $total_params[] = $category;
}

if ($brand_id > 0 && $model_id > 0) {
    $total_query .= " AND spc.brand_id = ? AND spc.model_id = ?";
    $total_params[] = $brand_id;
    $total_params[] = $model_id;
}

$total_result = $db->selectOne($total_query, $total_params);
$total_count = $total_result['total'] ?? 0;
$total_pages = ceil($total_count / $limit);

// فلترة البيانات حسب الصلاحيات
if (!$permissions['view_detailed_costs']) {
    $parts = filterSparePartsData($parts);
}

// تضمين header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- رأس الصفحة -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-gear-fill text-primary"></i>
                            Gestión de Repuestos
                        </h1>
                        <p class="text-muted mb-0">
                            <?php if ($permissions['view_detailed_costs']): ?>
                                Administra el inventario y precios de repuestos
                            <?php else: ?>
                                Consulta repuestos disponibles y precios
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <?php if ($permissions['add_spare_parts']): ?>
                            <a href="<?= url('pages/add_spare_part.php') ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i>
                                Agregar Repuesto
                            </a>
                        <?php endif; ?>

                        <?php if ($permissions['view_profit_reports']): ?>
                            <a href="<?= url('pages/spare_parts_reports.php') ?>" class="btn btn-outline-success ms-2">
                                <i class="bi bi-graph-up"></i>
                                Informes
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-funnel"></i>
                            Filtros de Búsqueda
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="searchForm">
                            <div class="row g-3">
                                <!-- بحث نصي -->
                                <div class="col-md-3">
                                    <label class="form-label">Buscar repuesto</label>
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="<?= htmlspecialchars($search_term) ?>"
                                           placeholder="Nombre o código...">
                                </div>

                                <!-- فئة القطعة -->
                                <div class="col-md-3">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" name="category">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"
                                                <?= $category === $cat ? 'selected' : '' ?>>
                                                <?= formatSparePartCategory($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- الماركة -->
                                <div class="col-md-2">
                                    <label class="form-label">Marca</label>
                                    <select class="form-select" name="brand_id" id="brandSelect">
                                        <option value="">Todas las marcas</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['id'] ?>"
                                                <?= $brand_id == $brand['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($brand['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- الموديل -->
                                <div class="col-md-2">
                                    <label class="form-label">Modelo</label>
                                    <select class="form-select" name="model_id" id="modelSelect">
                                        <option value="">Todos los modelos</option>
                                        <?php foreach ($models as $model): ?>
                                            <option value="<?= $model['id'] ?>"
                                                <?= $model_id == $model['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($model['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- أزرار التحكم -->
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <a href="<?= url('pages/spare_parts.php') ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- نتائج البحث -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Resultados de búsqueda
                            <span class="badge bg-primary"><?= number_format($total_count) ?></span>
                        </h5>

                        <!-- خيارات العرض -->
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="viewMode" id="listView" checked>
                            <label class="btn btn-outline-secondary" for="listView">
                                <i class="bi bi-list"></i>
                            </label>

                            <input type="radio" class="btn-check" name="viewMode" id="gridView">
                            <label class="btn btn-outline-secondary" for="gridView">
                                <i class="bi bi-grid"></i>
                            </label>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <?php if (empty($parts)): ?>
                            <!-- لا توجد نتائج -->
                            <div class="text-center py-5">
                                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                                <h4 class="text-muted mt-3">No se encontraron repuestos</h4>
                                <p class="text-muted">
                                    Intenta modificar los filtros de búsqueda o
                                    <?php if ($permissions['add_spare_parts']): ?>
                                        <a href="<?= url('pages/add_spare_part.php') ?>">agregar un nuevo repuesto</a>
                                    <?php else: ?>
                                        contacta al administrador
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- عرض القائمة -->
                            <div id="listViewContent">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Repuesto</th>
                                            <th>Categoría</th>
                                            <th>Precio</th>
                                            <?php if ($permissions['view_detailed_costs']): ?>
                                                <th>Coste</th>
                                                <th>Margen</th>
                                            <?php endif; ?>
                                            <th>Teléfonos compatibles</th>
                                            <th>Acciones</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($parts as $part): ?>
                                            <tr>
                                                <!-- معلومات القطعة -->
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($part['part_name']) ?></strong>
                                                        <?php if (!empty($part['part_code'])): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                Código: <?= htmlspecialchars($part['part_code']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>

                                                <!-- الفئة -->
                                                <td>
                                                    <?php if (!empty($part['category'])): ?>
                                                        <span class="badge bg-info">
                                                            <?= formatSparePartCategory($part['category']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- السعر -->
                                                <td>
                                                    <strong class="text-success">
                                                        €<?= number_format($part['total_price'], 2) ?>
                                                    </strong>
                                                </td>

                                                <!-- التكلفة والهامش (للإدارة فقط) -->
                                                <?php if ($permissions['view_detailed_costs']): ?>
                                                    <td>
                                                        <?php
                                                        $total_cost = ($part['cost_price'] ?? 0) + ($part['labor_cost'] ?? 0);
                                                        ?>
                                                        <small class="text-muted">
                                                            Producto: €<?= number_format($part['cost_price'] ?? 0, 2) ?><br>
                                                            Mano obra: €<?= number_format($part['labor_cost'] ?? 0, 2) ?><br>
                                                            <strong>Total: €<?= number_format($total_cost, 2) ?></strong>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $margin = calculatePartProfitMargin(
                                                            $part['cost_price'] ?? 0,
                                                            $part['labor_cost'] ?? 0,
                                                            $part['total_price']
                                                        );
                                                        $margin_class = $margin >= 50 ? 'success' : ($margin >= 25 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?= $margin_class ?>">
                                                            <?= $margin ?>%
                                                        </span>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- الهواتف المتوافقة -->
                                                <td>
                                                    <?php if (!empty($part['compatible_phones'])): ?>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars(safeTruncate($part['compatible_phones'], 50)) ?>
                                                        </small>
                                                        <?php if (strlen($part['compatible_phones']) > 50): ?>
                                                            <button class="btn btn-link btn-sm p-0"
                                                                    data-bs-toggle="tooltip"
                                                                    title="<?= htmlspecialchars($part['compatible_phones']) ?>">
                                                                <i class="bi bi-info-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin especificar</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- الإجراءات -->
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- عرض التفاصيل -->
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-info"
                                                                onclick="viewPartDetails(<?= $part['id'] ?>)"
                                                                title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </button>

                                                        <!-- استخدام في إصلاح -->
                                                        <?php if ($permissions['use_spare_parts']): ?>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="usePartInRepair(<?= $part['id'] ?>)"
                                                                    title="Usar en reparación">
                                                                <i class="bi bi-plus"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <!-- تعديل (للإدارة فقط) -->
                                                        <?php if ($permissions['manage_spare_parts']): ?>
                                                            <a href="<?= url('pages/edit_spare_part.php?id=' . $part['id']) ?>"
                                                               class="btn btn-sm btn-outline-warning"
                                                               title="Editar">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <!-- حذف (للإدارة فقط) -->
                                                        <?php if ($permissions['delete_spare_parts']): ?>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="deletePart(<?= $part['id'] ?>, '<?= htmlspecialchars($part['part_name']) ?>')"
                                                                    title="Eliminar">
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
                            </div>

                            <!-- عرض الشبكة -->
                            <div id="gridViewContent" style="display: none;">
                                <div class="row g-3 p-3">
                                    <?php foreach ($parts as $part): ?>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <!-- رأس البطاقة -->
                                                    <div class="mb-2">
                                                        <h6 class="card-title mb-0">
                                                            <?= htmlspecialchars(safeTruncate($part['part_name'], 30)) ?>
                                                        </h6>
                                                    </div>

                                                    <!-- معلومات القطعة -->
                                                    <div class="mb-3">
                                                        <?php if (!empty($part['part_code'])): ?>
                                                            <small class="text-muted">
                                                                Código: <?= htmlspecialchars($part['part_code']) ?>
                                                            </small>
                                                            <br>
                                                        <?php endif; ?>

                                                        <?php if (!empty($part['category'])): ?>
                                                            <span class="badge bg-info mb-1">
                                                            <?= formatSparePartCategory($part['category']) ?>
                                                        </span>
                                                            <br>
                                                        <?php endif; ?>

                                                        <strong class="text-success">
                                                            €<?= number_format($part['total_price'], 2) ?>
                                                        </strong>
                                                    </div>

                                                    <!-- الهواتف المتوافقة -->
                                                    <?php if (!empty($part['compatible_phones'])): ?>
                                                        <div class="mb-3">
                                                            <small class="text-muted">
                                                                <strong>Compatible:</strong><br>
                                                                <?= htmlspecialchars(safeTruncate($part['compatible_phones'], 60)) ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- إجراءات البطاقة -->
                                                <div class="card-footer">
                                                    <div class="d-grid gap-2">
                                                        <button type="button"
                                                                class="btn btn-outline-info btn-sm"
                                                                onclick="viewPartDetails(<?= $part['id'] ?>)">
                                                            <i class="bi bi-eye"></i>
                                                            Ver detalles
                                                        </button>

                                                        <?php if ($permissions['use_spare_parts']): ?>
                                                            <button type="button"
                                                                    class="btn btn-success btn-sm"
                                                                    onclick="usePartInRepair(<?= $part['id'] ?>)">
                                                                <i class="bi bi-plus"></i>
                                                                Usar en reparación
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- الترقيم -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تفاصيل القطعة -->
    <div class="modal fade" id="partDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Repuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="partDetailsContent">
                    <!-- محتوى تفاصيل القطعة سيتم تحميله عبر AJAX -->
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // متغيرات عامة
        const currentUserId = <?= $current_user['id'] ?>;
        const shopId = <?= $shop_id ?>;
        const userPermissions = <?= json_encode($permissions) ?>;

        // تبديل طرق العرض
        document.getElementById('listView').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('listViewContent').style.display = 'block';
                document.getElementById('gridViewContent').style.display = 'none';
            }
        });

        document.getElementById('gridView').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('listViewContent').style.display = 'none';
                document.getElementById('gridViewContent').style.display = 'block';
            }
        });

        // تحميل الموديلات عند تغيير الماركة
        document.getElementById('brandSelect').addEventListener('change', function() {
            const brandId = this.value;
            const modelSelect = document.getElementById('modelSelect');

            // مسح الموديلات الحالية
            modelSelect.innerHTML = '<option value="">Todos los modelos</option>';

            if (brandId) {
                // تحميل الموديلات الجديدة
                fetch(`<?= url('api/models.php') ?>?action=get_by_brand&brand_id=${brandId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            data.data.forEach(model => {
                                const option = document.createElement('option');
                                option.value = model.id;
                                option.textContent = model.name;
                                modelSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                    });
            }
        });

        // عرض تفاصيل القطعة
        function viewPartDetails(partId) {
            const modal = new bootstrap.Modal(document.getElementById('partDetailsModal'));
            const content = document.getElementById('partDetailsContent');

            // عرض مؤشر التحميل
            content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;

            modal.show();

            // تحميل تفاصيل القطعة
            fetch(`<?= url('api/spare_parts.php') ?>?action=get_part&id=${partId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        displayPartDetails(data.data);
                    } else {
                        content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error al cargar los detalles: ${data.message || 'Error desconocido'}
                    </div>
                `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Error de conexión al cargar los detalles
                </div>
            `;
                });
        }

        // عرض تفاصيل القطعة في Modal
        function displayPartDetails(part) {
            const content = document.getElementById('partDetailsContent');

            let compatibilityHtml = '';
            if (part.compatibility && part.compatibility.length > 0) {
                compatibilityHtml = part.compatibility.map(comp =>
                    `<span class="badge bg-secondary me-1 mb-1">${comp.brand_name} ${comp.model_name}</span>`
                ).join('');
            } else {
                compatibilityHtml = '<span class="text-muted">Sin especificar</span>';
            }

            let costDetailsHtml = '';
            if (userPermissions.view_detailed_costs && part.cost_price !== null) {
                const totalCost = (parseFloat(part.cost_price) || 0) + (parseFloat(part.labor_cost) || 0);
                const profit = parseFloat(part.total_price) - totalCost;
                const margin = totalCost > 0 ? ((profit / totalCost) * 100).toFixed(1) : 100;

                costDetailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Detalles de Costes</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Coste del producto:</td>
                            <td class="text-end">€${parseFloat(part.cost_price || 0).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td>Mano de obra:</td>
                            <td class="text-end">€${parseFloat(part.labor_cost || 0).toFixed(2)}</td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Coste total:</strong></td>
                            <td class="text-end"><strong>€${totalCost.toFixed(2)}</strong></td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>Beneficio:</strong></td>
                            <td class="text-end"><strong>€${profit.toFixed(2)}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Margen:</strong></td>
                            <td class="text-end"><strong>${margin}%</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Información del Proveedor</h6>
                    <p><strong>Nombre:</strong> ${part.supplier_name || 'No especificado'}</p>
                    <p><strong>Contacto:</strong> ${part.supplier_contact || 'No especificado'}</p>
                </div>
            </div>
            <hr>
        `;
            }

            content.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h4>${part.part_name}</h4>
                ${part.part_code ? `<p class="text-muted">Código: ${part.part_code}</p>` : ''}

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <strong>Categoría:</strong><br>
                        ${part.category ?
                `<span class="badge bg-info">${formatCategory(part.category)}</span>` :
                '<span class="text-muted">Sin especificar</span>'
            }
                    </div>
                    <div class="col-sm-6">
                        <strong>Precio:</strong><br>
                        <span class="h4 text-success">€${parseFloat(part.total_price).toFixed(2)}</span>
                    </div>
                </div>

                <div class="mb-3">
                    <strong>Garantía:</strong><br>
                    ${part.warranty_days || 30} días
                </div>

                ${part.notes ? `
                    <div class="mb-3">
                        <strong>Notas:</strong><br>
                        <p class="text-muted">${part.notes}</p>
                    </div>
                ` : ''}
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Teléfonos Compatibles</h6>
                    </div>
                    <div class="card-body">
                        ${compatibilityHtml}
                    </div>
                </div>
            </div>
        </div>

        <hr>

        ${costDetailsHtml}

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Creado: ${formatDate(part.created_at)}<br>
                        Actualizado: ${formatDate(part.updated_at)}
                    </small>

                    <div>
                        ${userPermissions.use_spare_parts ?
                `<button type="button" class="btn btn-success" onclick="usePartInRepair(${part.id})">
                                <i class="bi bi-plus"></i> Usar en reparación
                            </button>` : ''
            }

                        ${userPermissions.manage_spare_parts ?
                `<a href="<?= url('pages/edit_spare_part.php') ?>?id=${part.id}" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Editar
                            </a>` : ''
            }
                    </div>
                </div>
            </div>
        </div>
    `;
        }

        // استخدام القطعة في إصلاح
        function usePartInRepair(partId) {
            // إعادة توجيه لصفحة إضافة إصلاح مع القطعة المحددة
            window.location.href = `<?= url('pages/add_repair.php') ?>?suggested_part=${partId}`;
        }

        // حذف قطعة غيار
        function deletePart(partId, partName) {
            if (!userPermissions.delete_spare_parts) {
                alert('No tienes permisos para eliminar repuestos');
                return;
            }

            if (!confirm(`¿Estás seguro de que deseas eliminar el repuesto "${partName}"?`)) {
                return;
            }

            // إرسال طلب الحذف
            fetch(`<?= url('api/spare_parts.php') ?>?action=delete&id=${partId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // إعادة تحميل الصفحة
                        location.reload();
                    } else {
                        alert(`Error al eliminar: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al eliminar el repuesto');
                });
        }

        // دوال مساعدة
        function formatCategory(category) {
            const categories = {
                'pantalla': 'Pantalla',
                'bateria': 'Batería',
                'camara': 'Cámara',
                'altavoz': 'Altavoz',
                'auricular': 'Auricular',
                'conector': 'Conector',
                'boton': 'Botón',
                'sensor': 'Sensor',
                'flex': 'Flex',
                'marco': 'Marco',
                'tapa': 'Tapa trasera',
                'cristal': 'Cristal',
                'otros': 'Otros'
            };

            return categories[category.toLowerCase()] || category;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // تفعيل tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // بحث فوري
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // إرسال البحث تلقائياً بعد توقف الكتابة لثانية واحدة
                if (this.value.length >= 2 || this.value.length === 0) {
                    document.getElementById('searchForm').submit();
                }
            }, 1000);
        });
    </script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>