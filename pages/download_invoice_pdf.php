<?php
/**
 * RepairPoint - مولد PDF احترافي للفواتير
 * باستخدام Dompdf - يحمّل الفاتورة كملف PDF حقيقي
 */

define('SECURE_ACCESS', true);
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die('Acceso denegado');
}

// Verificar permisos
if ($_SESSION['user_role'] !== 'admin') {
    die('Acceso denegado');
}

$db = getDB();
$shop_id = $_SESSION['shop_id'];

// Obtener ID de la factura
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    die('ID de factura inválido');
}

// Obtener información de la factura
$invoice = $db->selectOne(
    "SELECT i.*,
            c.full_name as customer_name,
            c.phone as customer_phone,
            c.email as customer_email,
            c.id_type,
            c.id_number,
            c.address as customer_address,
            s.name as shop_name,
            s.phone1 as shop_phone,
            s.email as shop_email,
            s.address as shop_address,
            s.logo as shop_logo,
            u.name as created_by_name
     FROM invoices i
     JOIN customers c ON i.customer_id = c.id
     JOIN shops s ON i.shop_id = s.id
     JOIN users u ON i.created_by = u.id
     WHERE i.id = ? AND i.shop_id = ?",
    [$invoice_id, $shop_id]
);

if (!$invoice) {
    die('Factura no encontrada');
}

// Obtener items de la factura
$items = $db->select(
    "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id",
    [$invoice_id]
);

// Preparar logo en base64 si existe
$logo_base64 = '';
if (!empty($invoice['shop_logo'])) {
    $logo_path = '../' . $invoice['shop_logo'];
    if (file_exists($logo_path)) {
        $logo_data = file_get_contents($logo_path);
        $logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
        $logo_base64 = 'data:image/' . $logo_type . ';base64,' . base64_encode($logo_data);
    }
}

// إنشاء HTML للفاتورة
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            border-bottom: 4px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 25px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            padding: 15px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left, .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-right {
            text-align: right;
        }

        .company-logo {
            max-width: 120px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 8pt;
            color: #666;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #0066cc;
        }

        .invoice-number {
            font-size: 12pt;
            color: #666;
            margin: 5px 0;
        }

        .invoice-dates {
            font-size: 8pt;
            color: #666;
        }

        .info-section {
            margin: 20px 0;
        }

        .info-box {
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .info-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #0066cc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-line {
            margin: 5px 0;
            font-size: 9.5pt;
            line-height: 1.6;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            min-width: 80px;
            display: inline-block;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 2px solid #0066cc;
            border-radius: 5px;
        }

        table.items thead {
            background: linear-gradient(to bottom, #0066cc 0%, #0052a3 100%);
            color: white;
        }

        table.items th {
            padding: 10px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9.5pt;
            border-bottom: 2px solid #004080;
        }

        table.items th.center {
            text-align: center;
        }

        table.items th.right {
            text-align: right;
        }

        table.items tbody tr {
            border-bottom: 1px solid #dee2e6;
        }

        table.items tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table.items tbody tr:hover {
            background-color: #e9ecef;
        }

        table.items td {
            padding: 9px 6px;
            font-size: 9.5pt;
        }

        table.items td.center {
            text-align: center;
        }

        table.items td.right {
            text-align: right;
        }

        .item-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }

        .type-service {
            background-color: #17a2b8;
            color: white;
        }

        .type-product {
            background-color: #28a745;
            color: white;
        }

        .type-spare_part {
            background-color: #ffc107;
            color: #333;
        }

        .totals-table {
            width: 280px;
            float: right;
            margin-top: 20px;
            border: 2px solid #0066cc;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .totals-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 10px;
            font-size: 10.5pt;
        }

        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #555;
        }

        .totals-table .amount {
            text-align: right;
            font-weight: 600;
        }

        .totals-table .total-row {
            background: linear-gradient(to bottom, #0066cc 0%, #0052a3 100%);
            color: white;
            font-size: 13pt;
            font-weight: bold;
        }

        .totals-table .subtotal-row {
            border-top: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .totals-table .iva-row {
            background-color: #fff3cd;
        }

        .notes {
            clear: both;
            margin-top: 30px;
            padding: 12px 15px;
            background: linear-gradient(to right, #fff3cd 0%, #fffbeb 100%);
            border-left: 5px solid #ffc107;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .notes-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notes-content {
            color: #856404;
            font-size: 9.5pt;
            line-height: 1.5;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            border-top: 3px solid #0066cc;
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
            font-size: 8.5pt;
            color: #666;
            text-align: center;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }

        .payment-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .status-pending {
            background: linear-gradient(to bottom, #ffc107 0%, #e0a800 100%);
            color: #333;
            border: 2px solid #d39e00;
        }

        .status-partial {
            background: linear-gradient(to bottom, #17a2b8 0%, #117a8b 100%);
            color: white;
            border: 2px solid #0c6674;
        }

        .status-paid {
            background: linear-gradient(to bottom, #28a745 0%, #1e7e34 100%);
            color: white;
            border: 2px solid #155724;
        }

        .clearfix {
            clear: both;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <?php if ($logo_base64): ?>
                <img src="<?= $logo_base64 ?>" alt="Logo" class="company-logo">
            <?php endif; ?>
            <div class="company-name"><?= htmlspecialchars($invoice['shop_name']) ?></div>
            <div class="company-details">
                <?php if (!empty($invoice['shop_address'])): ?>
                    <?= nl2br(htmlspecialchars($invoice['shop_address'])) ?><br>
                <?php endif; ?>
                <?php if (!empty($invoice['shop_phone'])): ?>
                    Tel: <?= htmlspecialchars($invoice['shop_phone']) ?><br>
                <?php endif; ?>
                <?php if (!empty($invoice['shop_email'])): ?>
                    Email: <?= htmlspecialchars($invoice['shop_email']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">FACTURA</div>
            <div class="invoice-number"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
            <div class="invoice-dates">
                <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?><br>
                <?php if (!empty($invoice['due_date'])): ?>
                    <strong>Vencimiento:</strong> <?= date('d/m/Y', strtotime($invoice['due_date'])) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Info Section -->
<div class="info-section">
    <div class="info-box">
        <div class="info-title">DATOS DEL CLIENTE</div>
        <div class="info-line"><span class="info-label">Nombre:</span> <?= htmlspecialchars($invoice['customer_name']) ?></div>
        <div class="info-line"><span class="info-label">Documento:</span> <?= strtoupper($invoice['id_type']) ?> <?= htmlspecialchars($invoice['id_number']) ?></div>
        <div class="info-line"><span class="info-label">Teléfono:</span> <?= htmlspecialchars($invoice['customer_phone']) ?></div>
        <?php if (!empty($invoice['customer_email'])): ?>
            <div class="info-line"><span class="info-label">Email:</span> <?= htmlspecialchars($invoice['customer_email']) ?></div>
        <?php endif; ?>
        <?php if (!empty($invoice['customer_address'])): ?>
            <div class="info-line"><span class="info-label">Dirección:</span> <?= nl2br(htmlspecialchars($invoice['customer_address'])) ?></div>
        <?php endif; ?>
    </div>

    <div class="info-box">
        <div class="info-title">ESTADO DE PAGO</div>
        <?php
        $status_text = [
            'pending' => 'PENDIENTE DE PAGO',
            'partial' => 'PAGO PARCIAL',
            'paid' => 'PAGADO'
        ];
        $status_class = [
            'pending' => 'status-pending',
            'partial' => 'status-partial',
            'paid' => 'status-paid'
        ];
        ?>
        <div class="payment-badge <?= $status_class[$invoice['payment_status']] ?>">
            <?= $status_text[$invoice['payment_status']] ?>
        </div>

        <?php if ($invoice['payment_status'] === 'paid'): ?>
            <div class="info-line" style="margin-top: 8px;">
                <span class="info-label">Fecha de Pago:</span> <?= date('d/m/Y', strtotime($invoice['payment_date'])) ?>
            </div>
            <div class="info-line">
                <span class="info-label">Método:</span> <?= ucfirst($invoice['payment_method']) ?>
            </div>
        <?php elseif ($invoice['payment_status'] === 'partial'): ?>
            <div class="info-line" style="margin-top: 8px;">
                <span class="info-label">Pagado:</span> €<?= number_format($invoice['paid_amount'], 2) ?>
            </div>
            <div class="info-line">
                <span class="info-label">Pendiente:</span> <strong>€<?= number_format($invoice['total'] - $invoice['paid_amount'], 2) ?></strong>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Items Table -->
<table class="items">
    <thead>
        <tr>
            <th>Descripción</th>
            <th width="60">Tipo</th>
            <th width="80">IMEI</th>
            <th width="40" class="center">Cant.</th>
            <th width="60" class="right">P. Unit.</th>
            <th width="60" class="right">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $type_labels = [
            'service' => ['label' => 'Servicio', 'class' => 'type-service'],
            'product' => ['label' => 'Producto', 'class' => 'type-product'],
            'spare_part' => ['label' => 'Repuesto', 'class' => 'type-spare_part']
        ];

        foreach ($items as $item):
            $type_info = $type_labels[$item['item_type']];
        ?>
            <tr>
                <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                <td>
                    <span class="item-type <?= $type_info['class'] ?>">
                        <?= $type_info['label'] ?>
                    </span>
                </td>
                <td><?= !empty($item['imei']) ? htmlspecialchars($item['imei']) : '-' ?></td>
                <td class="center"><?= $item['quantity'] ?></td>
                <td class="right">€<?= number_format($item['unit_price'], 2) ?></td>
                <td class="right"><strong>€<?= number_format($item['subtotal'], 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Totals -->
<div class="totals-table">
    <table>
        <tr class="subtotal-row">
            <td class="label">Subtotal:</td>
            <td class="amount">€<?= number_format($invoice['subtotal'], 2) ?></td>
        </tr>
        <tr class="iva-row">
            <td class="label">IVA (<?= $invoice['iva_rate'] ?>%):</td>
            <td class="amount">€<?= number_format($invoice['iva_amount'], 2) ?></td>
        </tr>
        <tr class="total-row">
            <td class="label">TOTAL:</td>
            <td class="amount">€<?= number_format($invoice['total'], 2) ?></td>
        </tr>
    </table>
</div>

<div class="clearfix"></div>

<!-- Notes -->
<?php if (!empty($invoice['notes'])): ?>
    <div class="notes">
        <div class="notes-title">Notas:</div>
        <div class="notes-content"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
    </div>
<?php endif; ?>

<!-- Footer -->
<div class="footer">
    Gracias por su confianza • <?= htmlspecialchars($invoice['shop_name']) ?> • Factura generada el <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// إعداد Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

// تحميل HTML
$dompdf->loadHtml($html);

// إعداد الصفحة
$dompdf->setPaper('A4', 'portrait');

// تحويل HTML إلى PDF
$dompdf->render();

// اسم الملف
$filename = 'Factura_' . $invoice['invoice_number'] . '_' . date('Y-m-d') . '.pdf';

// إرسال PDF للمتصفح
$dompdf->stream($filename, [
    'Attachment' => true // true = تحميل، false = عرض في المتصفح
]);
?>
