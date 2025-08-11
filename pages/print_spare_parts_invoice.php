<?php
/**
 * RepairPoint - Factura A4 de Repuestos
 * فاتورة مفصلة لقطع الغيار - ورق A4 عمودي
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Obtener ID de la reparación
$repair_id = intval($_GET['id'] ?? 0);

if (!$repair_id) {
    die('ID de reparación no válido');
}

// Obtener datos de la reparación
$db = getDB();
$repair = $db->selectOne(
    "SELECT r.*, b.name as brand_name, m.name as model_name, u.name as created_by_name, s.*
     FROM repairs r 
     JOIN brands b ON r.brand_id = b.id 
     JOIN models m ON r.model_id = m.id 
     JOIN users u ON r.created_by = u.id
     JOIN shops s ON r.shop_id = s.id
     WHERE r.id = ? AND r.shop_id = ?",
    [$repair_id, $shop_id]
);

if (!$repair) {
    die('Reparación no encontrada');
}

// Obtener قطع الغيار المستخدمة
$used_spare_parts = getRepairSpareParts($repair_id);

if (empty($used_spare_parts)) {
    die('Esta reparación no tiene repuestos registrados');
}

// Verificar صلاحيات الطباعة
$spare_parts_permissions = getCurrentUserSparePartsPermissions();
if (!$spare_parts_permissions['print_invoice']) {
    die('No tienes permisos para imprimir esta factura');
}

// حساب التكاليف
$spare_parts_cost = calculateRepairSparePartsCost($repair_id);

// معلومات الضمانة
$warranty_days = $repair['warranty_days'] ?? 30;
$is_under_warranty = false;
$warranty_days_left = 0;

if ($repair['delivered_at']) {
    $warranty_days_left = calculateWarrantyDaysLeft($repair['delivered_at'], $warranty_days);
    $is_under_warranty = isUnderWarranty($repair['delivered_at'], $warranty_days);
}

// Log de actividad
logActivity('spare_parts_invoice_printed', "Factura de repuestos impresa para reparación #{$repair['reference']}", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Repuestos #<?= htmlspecialchars($repair['reference']) ?></title>

    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: white;
            color: #000;
            line-height: 1.4;
            font-size: 12px;
        }

        /* Configuración A4 Portrait */
        .invoice {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
            position: relative;
        }

        /* Header de la factura */
        .invoice-header {
            border-bottom: 3px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .company-info {
            flex: 2;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 2px solid #ddd;
            padding: 5px;
            background: white;
        }

        .company-details {
            margin-top: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .company-address {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }

        .invoice-title {
            flex: 1;
            text-align: right;
        }

        .invoice-type {
            font-size: 32px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .invoice-date {
            font-size: 12px;
            color: #666;
        }

        /* معلومات العميل والإصلاح */
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .customer-info, .repair-info {
            flex: 1;
            padding: 15px;
            border: 2px solid #eee;
            background: #f9f9f9;
            margin: 0 5px;
        }

        .info-title {
            font-size: 14px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #0066cc;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 100px;
            color: #333;
        }

        .info-value {
            flex: 1;
            color: #666;
        }

        /* جدول قطع الغيار */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .invoice-table th {
            background: #0066cc;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0066cc;
        }

        .invoice-table th.text-center {
            text-align: center;
        }

        .invoice-table th.text-right {
            text-align: right;
        }

        .invoice-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .invoice-table td.text-center {
            text-align: center;
        }

        .invoice-table td.text-right {
            text-align: right;
        }

        .invoice-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .invoice-table tbody tr:hover {
            background: #f0f8ff;
        }

        .part-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .part-code {
            font-size: 10px;
            color: #666;
            font-style: italic;
        }

        .part-category {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .warranty-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        /* إجماليات الفاتورة */
        .invoice-totals {
            margin-top: 20px;
        }

        .totals-table {
            width: 60%;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 15px;
            border: 1px solid #ddd;
        }

        .totals-table .label {
            background: #f8f9fa;
            font-weight: bold;
            text-align: right;
            width: 60%;
        }

        .totals-table .value {
            background: white;
            text-align: right;
            font-weight: bold;
        }

        .total-final {
            background: #0066cc !important;
            color: white !important;
            font-size: 16px;
        }

        /* قسم الضمانة */
        .warranty-section {
            margin-top: 25px;
            padding: 15px;
            border: 2px solid #28a745;
            background: #f8fff9;
        }

        .warranty-title {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .warranty-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .warranty-item {
            text-align: center;
            padding: 10px;
            border: 1px solid #28a745;
            background: white;
            border-radius: 5px;
        }

        .warranty-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .warranty-value {
            font-size: 14px;
            font-weight: bold;
            color: #28a745;
        }

        /* تحليل الأرباح (للإدارة فقط) */
        .profit-analysis {
            margin-top: 25px;
            padding: 15px;
            border: 2px solid #ffc107;
            background: #fffbf0;
        }

        .profit-title {
            font-size: 16px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .profit-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .profit-item {
            text-align: center;
            padding: 10px;
            border: 1px solid #ffc107;
            background: white;
            border-radius: 5px;
        }

        .profit-item.negative {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .profit-item.positive {
            border-color: #28a745;
            background: #f8fff9;
        }

        .profit-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .profit-value {
            font-size: 14px;
            font-weight: bold;
        }

        .profit-value.negative {
            color: #dc3545;
        }

        .profit-value.positive {
            color: #28a745;
        }

        .profit-value.neutral {
            color: #6c757d;
        }

        /* Footer de la factura */
        .invoice-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #0066cc;
        }

        .footer-notes {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #0066cc;
        }

        .footer-notes h4 {
            font-size: 14px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
        }

        .footer-notes ul {
            padding-left: 20px;
            margin: 0;
        }

        .footer-notes li {
            margin-bottom: 5px;
            font-size: 11px;
            color: #666;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #666;
        }

        .tech-signature {
            text-align: right;
        }

        .signature-line {
            width: 200px;
            border-bottom: 1px solid #333;
            margin: 20px 0 5px auto;
        }

        /* Controles de impresión */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 15px;
            border-radius: 8px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .print-controls button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 8px 15px;
            margin: 3px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            transition: background 0.3s;
        }

        .print-controls button:hover {
            background: #0052a3;
        }

        .print-controls button.secondary {
            background: #6c757d;
        }

        .print-controls button.secondary:hover {
            background: #545b62;
        }

        /* Print styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .print-controls {
                display: none;
            }

            .invoice {
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            /* فرض الألوان في الطباعة */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .invoice-table th {
                background: #0066cc !important;
                color: white !important;
            }

            .total-final {
                background: #0066cc !important;
                color: white !important;
            }

            .warranty-section {
                border: 2px solid #28a745 !important;
                background: #f8fff9 !important;
            }

            .profit-analysis {
                border: 2px solid #ffc107 !important;
                background: #fffbf0 !important;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .invoice {
                width: 100%;
                padding: 10px;
            }

            .header-top,
            .invoice-details {
                flex-direction: column;
            }

            .customer-info,
            .repair-info {
                margin: 5px 0;
            }

            .totals-table {
                width: 100%;
            }

            .warranty-grid,
            .profit-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .invoice-table {
                font-size: 10px;
            }

            .invoice-table th,
            .invoice-table td {
                padding: 6px 4px;
            }
        }
    </style>
</head>
<body>
<!-- Controles de impresión -->
<div class="print-controls">
    <div style="margin-bottom: 10px; font-weight: bold;">Factura de Repuestos</div>
    <button onclick="window.print()" title="Imprimir factura">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()" class="secondary" title="Cerrar ventana">
        ❌ Cerrar
    </button>
    <button onclick="downloadPDF()" class="secondary" title="Descargar PDF">
        📄 PDF
    </button>
</div>

<div class="invoice">
    <!-- Header de la factura -->
    <div class="invoice-header">
        <div class="header-top">
            <div class="company-info">
                <?php if (!empty($repair['logo'])): ?>
                    <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" alt="Logo" class="company-logo">
                <?php endif; ?>

                <div class="company-details">
                    <div class="company-name"><?= htmlspecialchars($repair['name']) ?></div>
                    <div class="company-address">
                        <?php if (!empty($repair['address'])): ?>
                            📍 <?= htmlspecialchars($repair['address']) ?><br>
                        <?php endif; ?>
                        📞 <?= htmlspecialchars($repair['phone1']) ?>
                        <?php if (!empty($repair['phone2'])): ?>
                            / <?= htmlspecialchars($repair['phone2']) ?>
                        <?php endif; ?>
                        <?php if (!empty($repair['email'])): ?>
                            <br>✉️ <?= htmlspecialchars($repair['email']) ?>
                        <?php endif; ?>
                        <?php if (!empty($repair['website'])): ?>
                            <br>🌐 <?= htmlspecialchars($repair['website']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="invoice-title">
                <div class="invoice-type">Factura Repuestos</div>
                <div class="invoice-number">N° <?= htmlspecialchars($repair['reference']) ?></div>
                <div class="invoice-date">
                    Fecha: <?= formatDate(getCurrentDateTime(), 'd/m/Y H:i') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del cliente y reparación -->
    <div class="invoice-details">
        <div class="customer-info">
            <div class="info-title">Datos del Cliente</div>
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value"><?= htmlspecialchars($repair['customer_name']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value"><?= htmlspecialchars($repair['customer_phone']) ?></div>
            </div>
            <?php if (!empty($repair['customer_email'])): ?>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?= htmlspecialchars($repair['customer_email']) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="repair-info">
            <div class="info-title">Datos de la Reparación</div>
            <div class="info-row">
                <div class="info-label">Dispositivo:</div>
                <div class="info-value"><?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value"><?= getStatusName($repair['status']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Recibido:</div>
                <div class="info-value"><?= formatDate($repair['received_at'], 'd/m/Y') ?></div>
            </div>
            <?php if ($repair['delivered_at']): ?>
                <div class="info-row">
                    <div class="info-label">Entregado:</div>
                    <div class="info-value"><?= formatDate($repair['delivered_at'], 'd/m/Y') ?></div>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <div class="info-label">Técnico:</div>
                <div class="info-value"><?= htmlspecialchars($repair['created_by_name']) ?></div>
            </div>
        </div>
    </div>

    <!-- Tabla de repuestos -->
    <table class="invoice-table">
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Descripción del Repuesto</th>
            <th style="width: 15%;">Categoría</th>
            <th class="text-center" style="width: 10%;">Cantidad</th>
            <th class="text-right" style="width: 15%;">Precio Unit.</th>
            <th class="text-right" style="width: 15%;">Total</th>
            <th class="text-center" style="width: 5%;">Garantía</th>
        </tr>
        </thead>
        <tbody>
        <?php $item_number = 1; ?>
        <?php foreach ($used_spare_parts as $part): ?>
            <tr>
                <td class="text-center"><?= $item_number++ ?></td>
                <td>
                    <div class="part-name"><?= htmlspecialchars($part['part_name']) ?></div>
                    <?php if (!empty($part['part_code'])): ?>
                        <div class="part-code">Código: <?= htmlspecialchars($part['part_code']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="part-category"><?= formatSparePartCategory($part['category']) ?></span>
                </td>
                <td class="text-center">
                    <strong><?= $part['quantity'] ?></strong>
                </td>
                <td class="text-right">€<?= number_format($part['unit_price'], 2) ?></td>
                <td class="text-right">
                    <strong>€<?= number_format($part['total_price'], 2) ?></strong>
                </td>
                <td class="text-center">
                    <span class="warranty-badge"><?= $part['warranty_days'] ?>d</span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totales de la factura -->
    <div class="invoice-totals">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal Repuestos:</td>
                <td class="value">€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?></td>
            </tr>
            <?php
            $labor_cost = 0;
            if ($repair['actual_cost'] > 0) {
                $labor_cost = $repair['actual_cost'] - $spare_parts_cost['total_customer_price'];
            }
            ?>
            <?php if ($labor_cost > 0): ?>
                <tr>
                    <td class="label">Mano de Obra:</td>
                    <td class="value">€<?= number_format($labor_cost, 2) ?></td>
                </tr>
                <tr>
                    <td class="label total-final">TOTAL FACTURADO:</td>
                    <td class="value total-final">€<?= number_format($repair['actual_cost'], 2) ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td class="label total-final">TOTAL REPUESTOS:</td>
                    <td class="value total-final">€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Sección de garantía -->
    <div class="warranty-section">
        <div class="warranty-title">🛡️ Información de Garantía</div>
        <div class="warranty-grid">
            <div class="warranty-item">
                <div class="warranty-label">Período</div>
                <div class="warranty-value"><?= $warranty_days ?> días</div>
            </div>
            <div class="warranty-item">
                <div class="warranty-label">Estado</div>
                <div class="warranty-value">
                    <?php if ($repair['delivered_at']): ?>
                        <?= $is_under_warranty ? 'VÁLIDA' : 'EXPIRADA' ?>
                    <?php else: ?>
                        PENDIENTE
                    <?php endif; ?>
                </div>
            </div>
            <div class="warranty-item">
                <div class="warranty-label">Días Restantes</div>
                <div class="warranty-value">
                    <?= $repair['delivered_at'] ? ($is_under_warranty ? $warranty_days_left : '0') : '-' ?>
                </div>
            </div>
            <div class="warranty-item">
                <div class="warranty-label">Expira</div>
                <div class="warranty-value">
                    <?php if ($repair['delivered_at']): ?>
                        <?= date('d/m/Y', strtotime($repair['delivered_at'] . " +{$warranty_days} days")) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis de rentabilidad (solo para admin) -->
    <?php if ($spare_parts_permissions['view_detailed_costs']): ?>
        <div class="profit-analysis">
            <div class="profit-title">📊 Análisis de Rentabilidad (Solo Administración)</div>
            <div class="profit-grid">
                <div class="profit-item">
                    <div class="profit-label">Coste de Compra</div>
                    <div class="profit-value neutral">€<?= number_format($spare_parts_cost['total_cost_price'], 2) ?></div>
                </div>
                <div class="profit-item">
                    <div class="profit-label">Coste de Mano de Obra</div>
                    <div class="profit-value neutral">€<?= number_format($spare_parts_cost['total_labor_cost'], 2) ?></div>
                </div>
                <div class="profit-item <?= $spare_parts_cost['total_profit'] > 0 ? 'positive' : 'negative' ?>">
                    <div class="profit-label">Beneficio Neto</div>
                    <div class="profit-value <?= $spare_parts_cost['total_profit'] > 0 ? 'positive' : 'negative' ?>">
                        €<?= number_format($spare_parts_cost['total_profit'], 2) ?>
                    </div>
                </div>
            </div>

            <?php if (($spare_parts_cost['total_cost_price'] + $spare_parts_cost['total_labor_cost']) > 0): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <strong>Margen de Beneficio:
                        <span class="<?= $spare_parts_cost['total_profit'] > 0 ? 'profit-value positive' : 'profit-value negative' ?>">
                        <?= number_format(($spare_parts_cost['total_profit'] / ($spare_parts_cost['total_cost_price'] + $spare_parts_cost['total_labor_cost'])) * 100, 1) ?>%
                    </span>
                    </strong>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Footer de la factura -->
    <div class="invoice-footer">
        <div class="footer-notes">
            <h4>📋 Condiciones de Garantía y Servicio</h4>
            <ul>
                <li>La garantía de los repuestos es de <?= $warranty_days ?> días desde la fecha de entrega del dispositivo reparado.</li>
                <li>La garantía cubre defectos de fabricación de los repuestos, no incluye daños por mal uso o accidentes.</li>
                <li>Para hacer efectiva la garantía, es necesario presentar esta factura.</li>
                <li>Los repuestos sustituidos en garantía tendrán la garantía restante del repuesto original.</li>
                <li>La garantía se anula en caso de manipulación por terceros no autorizados.</li>
                <li>El cliente tiene derecho a solicitar la devolución de los repuestos sustituidos.</li>
            </ul>
        </div>

        <div class="footer-info">
            <div class="invoice-details-footer">
                <div><strong>Fecha de impresión:</strong> <?= formatDate(getCurrentDateTime(), 'd/m/Y H:i') ?></div>
                <div><strong>Usuario:</strong> <?= htmlspecialchars($current_user['name']) ?></div>
                <div><strong>Total de ítems:</strong> <?= count($used_spare_parts) ?> repuesto(s)</div>
            </div>

            <div class="tech-signature">
                <div><strong>Firma del Técnico</strong></div>
                <div class="signature-line"></div>
                <div><?= htmlspecialchars($repair['created_by_name']) ?></div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-focus en la ventana para facilitar la impresión
        window.focus();

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+P para imprimir
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }

            // Escape para cerrar
            if (e.key === 'Escape') {
                window.close();
            }

            // Ctrl+S para descargar PDF
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                downloadPDF();
            }
        });

        // Log de información
        console.log('Factura de repuestos cargada exitosamente');
        console.log('Reparación ID:', <?= $repair['id'] ?>);
        console.log('Reference:', '<?= $repair['reference'] ?>');
        console.log('Total repuestos:', <?= count($used_spare_parts) ?>);
        console.log('Total facturado:', '€<?= number_format($spare_parts_cost['total_customer_price'], 2) ?>');
    });

    // Función para descargar como PDF (básica)
    function downloadPDF() {
        // Esta función podría expandirse para usar una librería de PDF
        // Por ahora, simplemente abre el diálogo de impresión
        alert('Usa Ctrl+P e selecciona "Guardar como PDF" en tu navegador');
        window.print();
    }

    // Función para imprimir con configuraciones específicas
    function printInvoice() {
        // Configurar la página para impresión
        const printButton = document.querySelector('.print-controls');
        if (printButton) {
            printButton.style.display = 'none';
        }

        // Esperar un momento y luego imprimir
        setTimeout(() => {
            window.print();

            // Restaurar controles después de la impresión
            setTimeout(() => {
                if (printButton) {
                    printButton.style.display = 'block';
                }
            }, 1000);
        }, 100);
    }

    // Manejar el evento después de imprimir
    window.addEventListener('afterprint', function() {
        const shouldClose = confirm('¿Desea cerrar la ventana de la factura?');
        if (shouldClose) {
            window.close();
        }
    });

    // Función para mostrar/ocultar análisis de rentabilidad
    function toggleProfitAnalysis() {
        const profitSection = document.querySelector('.profit-analysis');
        if (profitSection) {
            profitSection.style.display = profitSection.style.display === 'none' ? 'block' : 'none';
        }
    }

    // Validación antes de cerrar
    window.addEventListener('beforeunload', function(e) {
        // Solo mostrar confirmación si no se ha impreso
        if (!window.printExecuted) {
            e.preventDefault();
            e.returnValue = '¿Estás seguro de que quieres cerrar sin imprimir?';
            return e.returnValue;
        }
    });

    // Marcar como impreso cuando se usa la función de imprimir
    window.addEventListener('beforeprint', function() {
        window.printExecuted = true;
    });

    // Funciones de utilidad para mejorar la experiencia
    function highlightSection(sectionClass) {
        const section = document.querySelector('.' + sectionClass);
        if (section) {
            section.style.backgroundColor = '#fff3cd';
            section.style.border = '2px solid #ffc107';

            setTimeout(() => {
                section.style.backgroundColor = '';
                section.style.border = '';
            }, 2000);
        }
    }

    // Función para copiar información al portapapeles
    function copyInvoiceInfo() {
        const invoiceText = `
Factura de Repuestos #<?= $repair['reference'] ?>
Cliente: <?= htmlspecialchars($repair['customer_name']) ?>
Dispositivo: <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
Total: €<?= number_format($spare_parts_cost['total_customer_price'], 2) ?>
Fecha: <?= formatDate(getCurrentDateTime(), 'd/m/Y') ?>
            `.trim();

        if (navigator.clipboard) {
            navigator.clipboard.writeText(invoiceText).then(() => {
                alert('Información de la factura copiada al portapapeles');
            });
        } else {
            // Fallback para navegadores sin soporte para Clipboard API
            const textArea = document.createElement('textarea');
            textArea.value = invoiceText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Información de la factura copiada al portapapeles');
        }
    }

    // Función para enviar por email (placeholder)
    function emailInvoice() {
        const subject = encodeURIComponent(`Factura de Repuestos #<?= $repair['reference'] ?>`);
        const body = encodeURIComponent(`
Estimado cliente,

Adjunto encontrará la factura de repuestos correspondiente a la reparación de su dispositivo:

- Dispositivo: <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
- Referencia: #<?= $repair['reference'] ?>
- Total: €<?= number_format($spare_parts_cost['total_customer_price'], 2) ?>

Saludos cordiales,
<?= htmlspecialchars($repair['name']) ?>
            `);

        const mailtoLink = `mailto:<?= htmlspecialchars($repair['customer_email'] ?? '') ?>?subject=${subject}&body=${body}`;
        window.open(mailtoLink);
    }

    // Función para mostrar detalles técnicos (debug)
    function showTechnicalDetails() {
        if (confirm('¿Mostrar información técnica de debug?')) {
            const debugInfo = {
                repair_id: <?= $repair['id'] ?>,
                reference: '<?= $repair['reference'] ?>',
                spare_parts_count: <?= count($used_spare_parts) ?>,
                total_customer_price: <?= $spare_parts_cost['total_customer_price'] ?>,
                total_cost_price: <?= $spare_parts_cost['total_cost_price'] ?>,
                total_labor_cost: <?= $spare_parts_cost['total_labor_cost'] ?>,
                total_profit: <?= $spare_parts_cost['total_profit'] ?>,
                warranty_days: <?= $warranty_days ?>,
                is_under_warranty: <?= $is_under_warranty ? 'true' : 'false' ?>,
                user_permissions: <?= json_encode($spare_parts_permissions) ?>
            };

            console.table(debugInfo);
            alert('Información técnica mostrada en la consola del navegador (F12)');
        }
    }

    // Detectar si es una pantalla pequeña y ajustar
    function checkScreenSize() {
        if (window.innerWidth < 768) {
            document.body.classList.add('mobile-view');

            // Ajustar controles para móvil
            const controls = document.querySelector('.print-controls');
            if (controls) {
                controls.style.position = 'relative';
                controls.style.width = '100%';
                controls.style.marginBottom = '20px';
                controls.style.textAlign = 'center';
            }
        }
    }

    // Ejecutar al cargar la página
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);

    // Agregar controles adicionales si es necesario
    function addExtraControls() {
        const controls = document.querySelector('.print-controls');
        if (controls && <?= json_encode($spare_parts_permissions['view_detailed_costs']) ?>) {
            const extraBtn = document.createElement('button');
            extraBtn.textContent = '📊 Debug';
            extraBtn.onclick = showTechnicalDetails;
            extraBtn.className = 'secondary';
            extraBtn.style.fontSize = '10px';
            controls.appendChild(extraBtn);
        }

        <?php if (!empty($repair['customer_email'])): ?>
        if (controls) {
            const emailBtn = document.createElement('button');
            emailBtn.textContent = '✉️ Email';
            emailBtn.onclick = emailInvoice;
            emailBtn.className = 'secondary';
            controls.appendChild(emailBtn);
        }
        <?php endif; ?>
    }

    // Ejecutar funciones adicionales
    setTimeout(addExtraControls, 100);

    // Prevenir zoom accidental en móviles
    document.addEventListener('touchstart', function(e) {
        if (e.touches.length > 1) {
            e.preventDefault();
        }
    });

    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(e) {
        const now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, false);

    // Funciones de accesibilidad
    function increaseFontSize() {
        document.body.style.fontSize = '14px';
    }

    function decreaseFontSize() {
        document.body.style.fontSize = '10px';
    }

    function resetFontSize() {
        document.body.style.fontSize = '12px';
    }

    // Shortcuts de accesibilidad
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            switch(e.key) {
                case '+':
                case '=':
                    e.preventDefault();
                    increaseFontSize();
                    break;
                case '-':
                    e.preventDefault();
                    decreaseFontSize();
                    break;
                case '0':
                    e.preventDefault();
                    resetFontSize();
                    break;
            }
        }
    });
</script>
</body>
</html>