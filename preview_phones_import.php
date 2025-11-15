<?php
/**
 * RepairPoint - معاينة بيانات الهواتف قبل الإدراج
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once 'config/config.php';
require_once INCLUDES_PATH . 'functions.php';

// التحقق من تسجيل الدخول
checkLogin();

// الحصول على معلومات المستخدم
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

// يجب أن يكون المستخدم admin
if ($user_role !== 'admin') {
    $_SESSION['error_message'] = 'يجب أن تكون مسؤولاً للوصول إلى هذه الصفحة';
    header('Location: pages/dashboard.php');
    exit;
}

$page_title = "معاينة بيانات الهواتف";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - RepairPoint</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .page-header h1 {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            font-size: 2.5em;
            margin: 0;
            font-weight: bold;
        }

        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .search-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .search-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            position: sticky;
            top: 0;
            background: #667eea;
            color: white;
            z-index: 10;
        }

        .table thead th {
            border: none;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-phone { background-color: #28a745; }
        .badge-watch { background-color: #17a2b8; }
        .badge-tablet { background-color: #ffc107; }

        .btn-import {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.2em;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }

        .btn-import:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .btn-import:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .alert-custom {
            border-radius: 10px;
            border-left: 5px solid;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Scrollbar تحسين */
        .table-container::-webkit-scrollbar {
            width: 10px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 5px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .brand-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .brand-tag {
            background: #e9ecef;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .brand-tag strong {
            color: #667eea;
        }

        .import-actions {
            text-align: center;
            padding: 30px 0;
            border-top: 2px solid #dee2e6;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-mobile-alt"></i> معاينة بيانات الهواتف قبل الإدراج</h1>
            <p class="text-muted">راجع البيانات وتأكد من صحتها قبل إدراجها في قاعدة البيانات</p>
        </div>

        <!-- الإحصائيات -->
        <div id="statsContainer" class="stats-container">
            <div class="loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-3">جاري تحميل البيانات...</p>
            </div>
        </div>

        <!-- إحصائيات البراندات -->
        <div id="brandStats" class="brand-stats" style="display: none;">
            <!-- سيتم ملؤها ديناميكياً -->
        </div>

        <!-- البحث -->
        <div class="search-container" style="display: none;" id="searchContainer">
            <h5 class="mb-3"><i class="fas fa-search"></i> البحث والتصفية</h5>
            <div class="search-row">
                <div>
                    <label class="form-label">البراند</label>
                    <input type="text" id="searchBrand" class="form-control" placeholder="ابحث عن براند...">
                </div>
                <div>
                    <label class="form-label">الموديل</label>
                    <input type="text" id="searchModel" class="form-control" placeholder="ابحث عن موديل...">
                </div>
                <div>
                    <label class="form-label">الرمز المرجعي</label>
                    <input type="text" id="searchReference" class="form-control" placeholder="ابحث عن رمز...">
                </div>
                <div>
                    <label class="form-label">النوع</label>
                    <select id="filterType" class="form-select">
                        <option value="">الكل</option>
                        <option value="Phone">Phone</option>
                        <option value="Watch">Watch</option>
                        <option value="Tablet">Tablet</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button onclick="resetFilters()" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> إعادة تعيين
                </button>
            </div>
        </div>

        <!-- الجدول -->
        <div id="tableContainerWrapper" style="display: none;">
            <h5 class="mb-3"><i class="fas fa-table"></i> البيانات (<span id="displayCount">0</span> من <span id="totalCount">0</span>)</h5>
            <div class="table-container">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>البراند</th>
                            <th>الفئة</th>
                            <th>الموديل</th>
                            <th>الرمز المرجعي</th>
                            <th style="width: 100px;">النوع</th>
                        </tr>
                    </thead>
                    <tbody id="dataTableBody">
                        <!-- سيتم ملؤه ديناميكياً -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- رسالة التحذيرات -->
        <div id="warningsContainer"></div>

        <!-- أزرار الإدراج -->
        <div class="import-actions" id="importActions" style="display: none;">
            <h4 class="mb-3">هل أنت متأكد من إدراج جميع البيانات؟</h4>
            <p class="text-muted">سيتم إضافة البراندات والموديلات إلى قاعدة البيانات (سيتم تخطي المكرر)</p>
            <button onclick="startImport()" id="importBtn" class="btn btn-import">
                <i class="fas fa-database"></i> إدراج جميع البيانات في قاعدة البيانات
            </button>
            <div id="importProgress" style="display: none;" class="mt-3">
                <div class="spinner-border text-success" role="status"></div>
                <p class="mt-2">جاري الإدراج...</p>
            </div>
        </div>

        <!-- النتائج -->
        <div id="resultsContainer"></div>

        <!-- زر العودة -->
        <div class="text-center mt-4">
            <a href="pages/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let allData = [];
        let filteredData = [];
        let stats = {};

        // تحميل البيانات عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
        });

        // تحميل البيانات من API
        async function loadData() {
            try {
                const response = await fetch('api/preview_phones_data.php');
                const result = await response.json();

                if (result.success) {
                    allData = result.data;
                    filteredData = allData;
                    stats = result.stats;

                    displayStats();
                    displayBrandStats();
                    displayTable();
                    checkForWarnings();

                    // إظهار العناصر المخفية
                    document.getElementById('searchContainer').style.display = 'block';
                    document.getElementById('tableContainerWrapper').style.display = 'block';
                    document.getElementById('importActions').style.display = 'block';
                } else {
                    showError('فشل في تحميل البيانات: ' + result.error);
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }
        }

        // عرض الإحصائيات
        function displayStats() {
            const container = document.getElementById('statsContainer');
            const totalBrands = Object.keys(stats.brands).length;
            const totalPhones = stats.types['Phone'] || 0;
            const totalWatches = stats.types['Watch'] || 0;
            const totalTablets = stats.types['Tablet'] || 0;

            container.innerHTML = `
                <div class="stat-card">
                    <h3>${stats.total}</h3>
                    <p><i class="fas fa-mobile-alt"></i> إجمالي السجلات</p>
                </div>
                <div class="stat-card">
                    <h3>${totalBrands}</h3>
                    <p><i class="fas fa-tags"></i> عدد البراندات</p>
                </div>
                <div class="stat-card">
                    <h3>${totalPhones}</h3>
                    <p><i class="fas fa-phone"></i> هواتف</p>
                </div>
                <div class="stat-card">
                    <h3>${totalWatches}</h3>
                    <p><i class="fas fa-clock"></i> ساعات</p>
                </div>
            `;
        }

        // عرض إحصائيات البراندات
        function displayBrandStats() {
            const container = document.getElementById('brandStats');
            container.style.display = 'flex';

            let html = '<h5 class="w-100 mb-3"><i class="fas fa-chart-bar"></i> توزيع البراندات:</h5>';

            for (const [brand, count] of Object.entries(stats.brands)) {
                html += `<div class="brand-tag"><strong>${brand}:</strong> ${count}</div>`;
            }

            container.innerHTML = html;
        }

        // عرض الجدول
        function displayTable() {
            const tbody = document.getElementById('dataTableBody');
            document.getElementById('totalCount').textContent = allData.length;
            document.getElementById('displayCount').textContent = filteredData.length;

            if (filteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">لا توجد بيانات</td></tr>';
                return;
            }

            let html = '';
            filteredData.forEach(item => {
                const typeBadge = getTypeBadge(item.type);
                html += `
                    <tr>
                        <td>${item.id}</td>
                        <td><strong>${escapeHtml(item.brand)}</strong></td>
                        <td>${escapeHtml(item.category)}</td>
                        <td>${escapeHtml(item.model)}</td>
                        <td><code>${escapeHtml(item.reference) || '-'}</code></td>
                        <td>${typeBadge}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // الحصول على badge النوع
        function getTypeBadge(type) {
            const badges = {
                'Phone': '<span class="badge badge-phone">Phone</span>',
                'Watch': '<span class="badge badge-watch">Watch</span>',
                'Tablet': '<span class="badge badge-tablet">Tablet</span>'
            };
            return badges[type] || `<span class="badge bg-secondary">${type}</span>`;
        }

        // البحث والتصفية
        document.addEventListener('DOMContentLoaded', function() {
            const searchBrand = document.getElementById('searchBrand');
            const searchModel = document.getElementById('searchModel');
            const searchReference = document.getElementById('searchReference');
            const filterType = document.getElementById('filterType');

            if (searchBrand) searchBrand.addEventListener('input', applyFilters);
            if (searchModel) searchModel.addEventListener('input', applyFilters);
            if (searchReference) searchReference.addEventListener('input', applyFilters);
            if (filterType) filterType.addEventListener('change', applyFilters);
        });

        function applyFilters() {
            const brandFilter = document.getElementById('searchBrand')?.value.toLowerCase() || '';
            const modelFilter = document.getElementById('searchModel')?.value.toLowerCase() || '';
            const refFilter = document.getElementById('searchReference')?.value.toLowerCase() || '';
            const typeFilter = document.getElementById('filterType')?.value || '';

            filteredData = allData.filter(item => {
                const matchBrand = item.brand.toLowerCase().includes(brandFilter);
                const matchModel = item.model.toLowerCase().includes(modelFilter);
                const matchRef = item.reference.toLowerCase().includes(refFilter);
                const matchType = !typeFilter || item.type === typeFilter;

                return matchBrand && matchModel && matchRef && matchType;
            });

            displayTable();
        }

        function resetFilters() {
            document.getElementById('searchBrand').value = '';
            document.getElementById('searchModel').value = '';
            document.getElementById('searchReference').value = '';
            document.getElementById('filterType').value = '';
            filteredData = allData;
            displayTable();
        }

        // التحقق من التحذيرات
        function checkForWarnings() {
            const container = document.getElementById('warningsContainer');
            const missingRefs = allData.filter(item => !item.reference).length;

            if (missingRefs > 0) {
                container.innerHTML = `
                    <div class="alert alert-warning alert-custom mt-3">
                        <h5><i class="fas fa-exclamation-triangle"></i> تنبيه</h5>
                        <p>يوجد <strong>${missingRefs}</strong> موديل بدون رمز مرجعي (Reference Code)</p>
                        <small>هذا أمر طبيعي - يمكنك الإدراج بأمان</small>
                    </div>
                `;
            }
        }

        // بدء الإدراج
        async function startImport() {
            if (!confirm('هل أنت متأكد من إدراج جميع البيانات في قاعدة البيانات؟\n\nسيتم إضافة ' + stats.total + ' موديل.')) {
                return;
            }

            const btn = document.getElementById('importBtn');
            const progress = document.getElementById('importProgress');

            btn.disabled = true;
            progress.style.display = 'block';

            try {
                const response = await fetch('api/import_preview_phones.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'import' })
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result);
                } else {
                    showError('فشل الإدراج: ' + result.error);
                    btn.disabled = false;
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
                btn.disabled = false;
            } finally {
                progress.style.display = 'none';
            }
        }

        // عرض نتيجة النجاح
        function showSuccess(result) {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = `
                <div class="alert alert-success alert-custom mt-4">
                    <h4><i class="fas fa-check-circle"></i> تم الإدراج بنجاح!</h4>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>البراندات المضافة:</strong> <span class="badge bg-success">${result.brands_added}</span></p>
                            <p><strong>البراندات الموجودة مسبقاً:</strong> <span class="badge bg-warning">${result.brands_skipped}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الموديلات المضافة:</strong> <span class="badge bg-success">${result.models_added}</span></p>
                            <p><strong>الموديلات الموجودة مسبقاً:</strong> <span class="badge bg-warning">${result.models_skipped}</span></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="pages/settings.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> إدارة البراندات والموديلات
                        </a>
                        <a href="pages/add_repair.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> إضافة إصلاح جديد
                        </a>
                    </div>
                </div>
            `;

            // إخفاء زر الإدراج
            document.getElementById('importActions').style.display = 'none';
        }

        // عرض خطأ
        function showError(message) {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = `
                <div class="alert alert-danger alert-custom mt-4">
                    <h5><i class="fas fa-times-circle"></i> خطأ</h5>
                    <p>${escapeHtml(message)}</p>
                </div>
            `;
        }

        // دالة لتنظيف HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
