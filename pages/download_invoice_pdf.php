<?php
/**
 * RepairPoint - Generador PDF Profesional de Facturas
 * Diseño moderno y profesional con preview antes de descargar
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

// Modo: 'preview' para ver en navegador, 'download' para descargar
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'preview';

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
            s.phone2 as shop_phone2,
            s.email as shop_email,
            s.address as shop_address,
            s.logo as shop_logo,
            s.nif as shop_nif,
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

// Generar nombre del archivo: NombreCliente_Fecha_NumeroFactura.pdf
$customer_name_clean = preg_replace('/[^a-zA-Z0-9]/', '_', $invoice['customer_name']);
$customer_name_clean = preg_replace('/_+/', '_', $customer_name_clean);
$customer_name_clean = trim($customer_name_clean, '_');
$invoice_date_formatted = date('d-m-Y', strtotime($invoice['invoice_date']));
$invoice_number_clean = str_replace(['/', '\\', ' '], '-', $invoice['invoice_number']);
$filename = $customer_name_clean . '_' . $invoice_date_formatted . '_' . $invoice_number_clean . '.pdf';

// Colores del tema
$primary_color = '#1a365d';      // Azul oscuro profesional
$secondary_color = '#2c5282';    // Azul medio
$accent_color = '#3182ce';       // Azul claro
$success_color = '#276749';      // Verde
$warning_color = '#c05621';      // Naranja
$light_bg = '#f7fafc';           // Gris muy claro
$border_color = '#e2e8f0';       // Gris borde

// Estado de pago
$status_config = [
    'pending' => ['text' => 'PENDIENTE', 'bg' => '#fef3c7', 'color' => '#92400e', 'border' => '#f59e0b'],
    'partial' => ['text' => 'PAGO PARCIAL', 'bg' => '#dbeafe', 'color' => '#1e40af', 'border' => '#3b82f6'],
    'paid' => ['text' => 'PAGADO', 'bg' => '#d1fae5', 'color' => '#065f46', 'border' => '#10b981']
];
$payment_status = $status_config[$invoice['payment_status']];

// Crear HTML para el PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        @page {
            margin: 15mm 15mm 25mm 15mm;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #2d3748;
            line-height: 1.5;
            background: white;
        }

        /* ============ HEADER ============ */
        .header {
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-left {
            width: 55%;
        }

        .header-right {
            width: 45%;
            text-align: right;
        }

        .company-logo {
            max-width: 120px;
            max-height: 60px;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: <?= $primary_color ?>;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 8pt;
            color: #718096;
            line-height: 1.6;
        }

        .invoice-badge {
            display: inline-block;
            background: <?= $primary_color ?>;
            color: white;
            font-size: 20pt;
            font-weight: bold;
            padding: 8px 25px;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }

        .invoice-number-box {
            background: <?= $light_bg ?>;
            border: 2px solid <?= $primary_color ?>;
            padding: 10px 15px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .invoice-number-label {
            font-size: 7pt;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .invoice-number-value {
            font-size: 14pt;
            font-weight: bold;
            color: <?= $primary_color ?>;
        }

        .invoice-dates {
            font-size: 8pt;
            color: #4a5568;
            line-height: 1.8;
        }

        .invoice-dates strong {
            color: <?= $primary_color ?>;
        }

        /* ============ DIVIDER ============ */
        .divider {
            height: 3px;
            background: linear-gradient(to right, <?= $primary_color ?>, <?= $accent_color ?>);
            margin: 15px 0;
        }

        /* ============ INFO BOXES ============ */
        .info-section {
            margin: 15px 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table > tbody > tr > td {
            width: 48%;
            vertical-align: top;
            padding: 0 8px;
        }

        .info-table > tbody > tr > td:first-child {
            padding-left: 0;
        }

        .info-table > tbody > tr > td:last-child {
            padding-right: 0;
        }

        .info-box {
            border: 1px solid <?= $border_color ?>;
            border-radius: 6px;
            overflow: hidden;
        }

        .info-box-header {
            background: <?= $primary_color ?>;
            color: white;
            padding: 8px 12px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-box-content {
            padding: 12px;
            background: white;
        }

        .info-line {
            margin: 4px 0;
            font-size: 8.5pt;
        }

        .info-label {
            font-weight: bold;
            color: #4a5568;
            display: inline-block;
            min-width: 70px;
        }

        .info-value {
            color: #2d3748;
        }

        .customer-name {
            font-size: 11pt;
            font-weight: bold;
            color: <?= $primary_color ?>;
            margin-bottom: 6px;
        }

        /* Payment Status */
        .payment-status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
            margin: 8px 0;
            background: <?= $payment_status['bg'] ?>;
            color: <?= $payment_status['color'] ?>;
            border: 2px solid <?= $payment_status['border'] ?>;
        }

        .payment-detail {
            font-size: 8.5pt;
            margin: 4px 0;
        }

        .amount-pending {
            color: #c53030;
            font-weight: bold;
        }

        /* ============ ITEMS TABLE ============ */
        .items-section {
            margin: 20px 0;
        }

        .items-title {
            background: <?= $secondary_color ?>;
            color: white;
            padding: 8px 12px;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid <?= $border_color ?>;
            border-top: none;
        }

        table.items thead {
            background: <?= $light_bg ?>;
        }

        table.items th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
            color: #4a5568;
            border-bottom: 2px solid <?= $border_color ?>;
            text-transform: uppercase;
        }

        table.items th.center { text-align: center; }
        table.items th.right { text-align: right; }

        table.items tbody tr {
            border-bottom: 1px solid <?= $border_color ?>;
        }

        table.items tbody tr:nth-child(even) {
            background: #fafafa;
        }

        table.items tbody tr:hover {
            background: #f0f4f8;
        }

        table.items td {
            padding: 10px 8px;
            font-size: 8.5pt;
            vertical-align: middle;
        }

        table.items td.center { text-align: center; }
        table.items td.right { text-align: right; }

        .item-desc {
            font-weight: 500;
            color: #2d3748;
        }

        .item-type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .type-service {
            background: #e0f2fe;
            color: #0369a1;
        }

        .type-product {
            background: #dcfce7;
            color: #166534;
        }

        .type-spare_part {
            background: #fef3c7;
            color: #92400e;
        }

        .item-imei {
            font-family: monospace;
            font-size: 7.5pt;
            color: #718096;
        }

        /* ============ TOTALS ============ */
        .totals-section {
            margin-top: 15px;
        }

        .totals-wrapper {
            width: 280px;
            float: right;
            border: 2px solid <?= $primary_color ?>;
            border-radius: 6px;
            overflow: hidden;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            font-size: 9pt;
        }

        .totals-table .label-cell {
            text-align: right;
            font-weight: 600;
            color: #4a5568;
            background: <?= $light_bg ?>;
        }

        .totals-table .amount-cell {
            text-align: right;
            font-weight: bold;
            width: 100px;
            background: white;
        }

        .totals-table .subtotal-row td {
            border-bottom: 1px solid <?= $border_color ?>;
        }

        .totals-table .iva-row td {
            border-bottom: 1px solid <?= $border_color ?>;
        }

        .totals-table .total-row td {
            background: <?= $primary_color ?>;
            color: white;
            font-size: 12pt;
            padding: 10px 12px;
        }

        .totals-table .total-row .label-cell {
            background: <?= $primary_color ?>;
            color: white;
        }

        /* ============ NOTES ============ */
        .notes-section {
            clear: both;
            margin-top: 25px;
            padding: 12px;
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
        }

        .notes-title {
            font-weight: bold;
            color: #92400e;
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .notes-content {
            color: #78350f;
            font-size: 8.5pt;
            line-height: 1.5;
        }

        /* ============ FOOTER ============ */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 15mm;
            background: <?= $light_bg ?>;
            border-top: 2px solid <?= $primary_color ?>;
            font-size: 7.5pt;
            color: #718096;
        }

        .footer-content {
            text-align: center;
        }

        .footer-company {
            font-weight: bold;
            color: <?= $primary_color ?>;
        }

        .clearfix {
            clear: both;
        }

        /* ============ LEGAL INFO ============ */
        .legal-info {
            clear: both;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed <?= $border_color ?>;
            font-size: 7.5pt;
            color: #a0aec0;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">
                <?php if ($logo_base64): ?>
                    <img src="<?= $logo_base64 ?>" alt="Logo" class="company-logo"><br>
                <?php endif; ?>
                <div class="company-name"><?= htmlspecialchars($invoice['shop_name']) ?></div>
                <div class="company-info">
                    <?php if (!empty($invoice['shop_nif'])): ?>
                        <strong>NIF:</strong> <?= htmlspecialchars($invoice['shop_nif']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($invoice['shop_address'])): ?>
                        <?= nl2br(htmlspecialchars($invoice['shop_address'])) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($invoice['shop_phone'])): ?>
                        Tel: <?= htmlspecialchars($invoice['shop_phone']) ?>
                        <?php if (!empty($invoice['shop_phone2'])): ?>
                            / <?= htmlspecialchars($invoice['shop_phone2']) ?>
                        <?php endif; ?>
                        <br>
                    <?php endif; ?>
                    <?php if (!empty($invoice['shop_email'])): ?>
                        <?= htmlspecialchars($invoice['shop_email']) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td class="header-right">
                <div class="invoice-badge">FACTURA</div><br>
                <div class="invoice-number-box">
                    <div class="invoice-number-label">Número de Factura</div>
                    <div class="invoice-number-value"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
                </div>
                <div class="invoice-dates">
                    <strong>Fecha de emisión:</strong> <?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?><br>
                    <?php if (!empty($invoice['due_date'])): ?>
                        <strong>Fecha de vencimiento:</strong> <?= date('d/m/Y', strtotime($invoice['due_date'])) ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="divider"></div>

<!-- INFO SECTION -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-box-header">Datos del Cliente</div>
                    <div class="info-box-content">
                        <div class="customer-name"><?= htmlspecialchars($invoice['customer_name']) ?></div>
                        <div class="info-line">
                            <span class="info-label">Documento:</span>
                            <span class="info-value"><?= strtoupper($invoice['id_type']) ?> <?= htmlspecialchars($invoice['id_number']) ?></span>
                        </div>
                        <div class="info-line">
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value"><?= htmlspecialchars($invoice['customer_phone']) ?></span>
                        </div>
                        <?php if (!empty($invoice['customer_email'])): ?>
                            <div class="info-line">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($invoice['customer_email']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($invoice['customer_address'])): ?>
                            <div class="info-line">
                                <span class="info-label">Dirección:</span>
                                <span class="info-value"><?= nl2br(htmlspecialchars($invoice['customer_address'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <div class="info-box">
                    <div class="info-box-header">Estado de Pago</div>
                    <div class="info-box-content">
                        <div class="payment-status-badge">
                            <?= $payment_status['text'] ?>
                        </div>

                        <?php if ($invoice['payment_status'] === 'paid'): ?>
                            <div class="payment-detail">
                                <span class="info-label">Fecha pago:</span>
                                <?= date('d/m/Y', strtotime($invoice['payment_date'])) ?>
                            </div>
                            <div class="payment-detail">
                                <span class="info-label">Método:</span>
                                <?= ucfirst($invoice['payment_method']) ?>
                            </div>
                        <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                            <div class="payment-detail">
                                <span class="info-label">Pagado:</span>
                                <strong style="color: #276749;"><?= number_format($invoice['paid_amount'], 2) ?></strong>
                            </div>
                            <div class="payment-detail">
                                <span class="info-label">Pendiente:</span>
                                <span class="amount-pending"><?= number_format($invoice['total'] - $invoice['paid_amount'], 2) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="payment-detail">
                                <span class="info-label">Total a pagar:</span>
                                <span class="amount-pending" style="font-size: 11pt;"><?= number_format($invoice['total'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS SECTION -->
<div class="items-section">
    <div class="items-title">Detalle de la Factura</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 40%;">Descripción</th>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 15%;">IMEI/Serie</th>
                <th style="width: 8%;" class="center">Cant.</th>
                <th style="width: 12%;" class="right">P. Unit.</th>
                <th style="width: 13%;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $type_config = [
                'service' => ['label' => 'Servicio', 'class' => 'type-service'],
                'product' => ['label' => 'Producto', 'class' => 'type-product'],
                'spare_part' => ['label' => 'Repuesto', 'class' => 'type-spare_part']
            ];

            foreach ($items as $item):
                $type = $type_config[$item['item_type']];
            ?>
                <tr>
                    <td><span class="item-desc"><?= nl2br(htmlspecialchars($item['description'])) ?></span></td>
                    <td>
                        <span class="item-type-badge <?= $type['class'] ?>">
                            <?= $type['label'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($item['imei'])): ?>
                            <span class="item-imei"><?= htmlspecialchars($item['imei']) ?></span>
                        <?php else: ?>
                            <span style="color: #cbd5e0;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="center"><?= $item['quantity'] ?></td>
                    <td class="right"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="right"><strong><?= number_format($item['subtotal'], 2) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- TOTALS -->
<div class="totals-section">
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr class="subtotal-row">
                <td class="label-cell">Base Imponible:</td>
                <td class="amount-cell"><?= number_format($invoice['subtotal'], 2) ?></td>
            </tr>
            <tr class="iva-row">
                <td class="label-cell">IVA (<?= number_format($invoice['iva_rate'], 0) ?>%):</td>
                <td class="amount-cell"><?= number_format($invoice['iva_amount'], 2) ?></td>
            </tr>
            <tr class="total-row">
                <td class="label-cell">TOTAL:</td>
                <td class="amount-cell"><?= number_format($invoice['total'], 2) ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="clearfix"></div>

<!-- NOTES -->
<?php if (!empty($invoice['notes'])): ?>
    <div class="notes-section">
        <div class="notes-title">Observaciones:</div>
        <div class="notes-content"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
    </div>
<?php endif; ?>

<!-- LEGAL INFO -->
<div class="legal-info">
    Factura emitida conforme a la normativa fiscal vigente.
    <?php if (!empty($invoice['shop_nif'])): ?>
        • NIF: <?= htmlspecialchars($invoice['shop_nif']) ?>
    <?php endif; ?>
</div>

<!-- FOOTER -->
<div class="footer">
    <div class="footer-content">
        <span class="footer-company"><?= htmlspecialchars($invoice['shop_name']) ?></span>
        • Documento generado el <?= date('d/m/Y H:i') ?>
        • Factura <?= htmlspecialchars($invoice['invoice_number']) ?>
    </div>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// Configurar Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('dpi', 150);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Agregar numeración de páginas
$canvas = $dompdf->getCanvas();
$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
    $text = "Página $pageNumber de $pageCount";
    $font = $fontMetrics->getFont('DejaVu Sans');
    $size = 7;
    $width = $fontMetrics->getTextWidth($text, $font, $size);
    $canvas->text(560 - $width, 820, $text, $font, $size, [0.5, 0.5, 0.5]);
});

// Modo preview (mostrar en navegador) o download (descargar directamente)
$attachment = ($mode === 'download') ? true : false;

$dompdf->stream($filename, [
    'Attachment' => $attachment
]);
?>
