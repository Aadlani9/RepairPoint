<?php
/**
 * RepairPoint - مولد PDF احترافي للفواتير
 * تصميم احترافي نظيف مع استغلال أمثل للمساحات
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
            margin: 20mm;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #2c3e50;
            line-height: 1.6;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-left {
            width: 50%;
        }

        .header-right {
            width: 50%;
            text-align: right;
        }

        .company-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .company-details {
            font-size: 9pt;
            color: #7f8c8d;
            line-height: 1.8;
        }

        .invoice-title {
            font-size: 28pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 14pt;
            color: #34495e;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .invoice-dates {
            font-size: 9pt;
            color: #7f8c8d;
            line-height: 1.8;
        }

        /* Info Section - Two Columns */
        .info-section {
            margin: 25px 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .info-table td:last-child {
            padding-right: 0;
            padding-left: 10px;
        }

        .info-box {
            background-color: #ecf0f1;
            border: 1px solid #bdc3c7;
            padding: 15px;
            height: 100%;
        }

        .info-title {
            font-size: 11pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-line {
            margin: 6px 0;
            font-size: 10pt;
            line-height: 1.7;
        }

        .info-label {
            font-weight: bold;
            color: #34495e;
            min-width: 90px;
            display: inline-block;
        }

        /* Payment Status Badge */
        .payment-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 2px solid #856404;
        }

        .status-partial {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 2px solid #0c5460;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #155724;
        }

        /* Items Table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            border: 1px solid #bdc3c7;
        }

        table.items thead {
            background-color: #2c3e50;
            color: white;
        }

        table.items th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
            border-bottom: 2px solid #34495e;
        }

        table.items th.center {
            text-align: center;
        }

        table.items th.right {
            text-align: right;
        }

        table.items tbody tr {
            border-bottom: 1px solid #ecf0f1;
        }

        table.items tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table.items td {
            padding: 10px 8px;
            font-size: 10pt;
        }

        table.items td.center {
            text-align: center;
        }

        table.items td.right {
            text-align: right;
        }

        .item-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .type-service {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .type-product {
            background-color: #d4edda;
            color: #155724;
        }

        .type-spare_part {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Totals */
        .totals-section {
            margin-top: 30px;
        }

        .totals-table {
            width: 320px;
            float: right;
            border: 2px solid #2c3e50;
        }

        .totals-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 10px 15px;
            font-size: 11pt;
        }

        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #34495e;
        }

        .totals-table .amount {
            text-align: right;
            font-weight: 600;
            width: 120px;
        }

        .totals-table .total-row {
            background-color: #2c3e50;
            color: white;
            font-size: 14pt;
            font-weight: bold;
        }

        .totals-table .subtotal-row {
            background-color: #ecf0f1;
        }

        .totals-table .iva-row {
            background-color: #f8f9fa;
        }

        /* Notes */
        .notes {
            clear: both;
            margin-top: 40px;
            padding: 15px;
            background-color: #fffbeb;
            border: 1px solid #ffd93d;
            border-left: 4px solid #ffd93d;
        }

        .notes-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            font-size: 10pt;
            text-transform: uppercase;
        }

        .notes-content {
            color: #856404;
            font-size: 9.5pt;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px 20mm;
            border-top: 2px solid #ecf0f1;
            background-color: white;
            font-size: 8pt;
            color: #7f8c8d;
        }

        .footer-content {
            text-align: center;
        }

        .page-number:after {
            content: "Página " counter(page) " de " counter(pages);
        }

        .clearfix {
            clear: both;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">
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
            </td>
            <td class="header-right">
                <div class="invoice-title">FACTURA</div>
                <div class="invoice-number"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
                <div class="invoice-dates">
                    <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?><br>
                    <?php if (!empty($invoice['due_date'])): ?>
                        <strong>Vencimiento:</strong> <?= date('d/m/Y', strtotime($invoice['due_date'])) ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Info Section - Cliente y Estado de Pago en una fila -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-title">Datos del Cliente</div>
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
            </td>
            <td>
                <div class="info-box">
                    <div class="info-title">Estado de Pago</div>
                    <?php
                    $status_text = [
                        'pending' => 'Pendiente de Pago',
                        'partial' => 'Pago Parcial',
                        'paid' => 'Pagado'
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
                        <div class="info-line">
                            <span class="info-label">Fecha de Pago:</span> <?= date('d/m/Y', strtotime($invoice['payment_date'])) ?>
                        </div>
                        <div class="info-line">
                            <span class="info-label">Método:</span> <?= ucfirst($invoice['payment_method']) ?>
                        </div>
                    <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                        <div class="info-line">
                            <span class="info-label">Pagado:</span> €<?= number_format($invoice['paid_amount'], 2) ?>
                        </div>
                        <div class="info-line">
                            <span class="info-label">Pendiente:</span> <strong>€<?= number_format($invoice['total'] - $invoice['paid_amount'], 2) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Items Table -->
<table class="items">
    <thead>
        <tr>
            <th style="width: 35%;">Descripción</th>
            <th style="width: 15%;">Tipo</th>
            <th style="width: 15%;">IMEI</th>
            <th style="width: 10%;" class="center">Cant.</th>
            <th style="width: 12%;" class="right">P. Unit.</th>
            <th style="width: 13%;" class="right">Subtotal</th>
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
<div class="totals-section">
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
    <div class="footer-content">
        <div style="margin-bottom: 5px;">
            Gracias por su confianza • <?= htmlspecialchars($invoice['shop_name']) ?> • Factura generada el <?= date('d/m/Y H:i') ?>
        </div>
        <div class="page-number"></div>
    </div>
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

// إضافة ترقيم الصفحات
$canvas = $dompdf->getCanvas();
$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
    $text = "Página $pageNumber de $pageCount";
    $font = $fontMetrics->getFont('DejaVu Sans');
    $size = 8;
    $width = $fontMetrics->getTextWidth($text, $font, $size);
    $canvas->text(297 - $width - 20, 820, $text, $font, $size, [0.5, 0.5, 0.5]);
});

// اسم الملف
$filename = 'Factura_' . $invoice['invoice_number'] . '_' . date('Y-m-d') . '.pdf';

// إرسال PDF للمتصفح
$dompdf->stream($filename, [
    'Attachment' => true
]);
?>
