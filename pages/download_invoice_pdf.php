<?php
/**
 * RepairPoint - Generador PDF Profesional de Facturas
 * Usando mPDF con QR Code
 */

define('SECURE_ACCESS', true);
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die('Acceso denegado - Sesión no iniciada');
}

// Verificar permisos
if ($_SESSION['user_role'] !== 'admin') {
    die('Acceso denegado - Se requiere rol de administrador');
}

$db = getDB();
$shop_id = $_SESSION['shop_id'] ?? 0;

// Debug: mostrar información si hay problemas
if ($shop_id <= 0) {
    die('Error: shop_id no válido en la sesión');
}

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
    // Debug: intentar encontrar la factura sin JOINs
    $debug_invoice = $db->selectOne("SELECT * FROM invoices WHERE id = ? AND shop_id = ?", [$invoice_id, $shop_id]);
    if ($debug_invoice) {
        // Verificar qué JOIN está fallando
        $customer = $db->selectOne("SELECT id FROM customers WHERE id = ?", [$debug_invoice['customer_id']]);
        $shop = $db->selectOne("SELECT id FROM shops WHERE id = ?", [$debug_invoice['shop_id']]);
        $user = $db->selectOne("SELECT id FROM users WHERE id = ?", [$debug_invoice['created_by']]);

        $errors = [];
        if (!$customer) $errors[] = "Cliente ID {$debug_invoice['customer_id']} no existe";
        if (!$shop) $errors[] = "Shop ID {$debug_invoice['shop_id']} no existe";
        if (!$user) $errors[] = "Usuario ID {$debug_invoice['created_by']} no existe";

        if ($errors) {
            die("Error en datos relacionados: " . implode(", ", $errors));
        }

        // Si llegamos aquí, usar LEFT JOIN
        $invoice = $db->selectOne(
            "SELECT i.*,
                    COALESCE(c.full_name, 'Cliente desconocido') as customer_name,
                    COALESCE(c.phone, '') as customer_phone,
                    c.email as customer_email,
                    COALESCE(c.id_type, 'dni') as id_type,
                    COALESCE(c.id_number, '') as id_number,
                    c.address as customer_address,
                    COALESCE(s.name, 'Tienda') as shop_name,
                    s.phone1 as shop_phone,
                    s.phone2 as shop_phone2,
                    s.email as shop_email,
                    s.address as shop_address,
                    s.logo as shop_logo,
                    s.nif as shop_nif,
                    COALESCE(u.name, 'Usuario') as created_by_name
             FROM invoices i
             LEFT JOIN customers c ON i.customer_id = c.id
             LEFT JOIN shops s ON i.shop_id = s.id
             LEFT JOIN users u ON i.created_by = u.id
             WHERE i.id = ? AND i.shop_id = ?",
            [$invoice_id, $shop_id]
        );
    } else {
        die("Factura con ID $invoice_id no existe o no pertenece a tu tienda");
    }
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

// Generar QR Code con toda la información
$qr_data = [
    'Factura' => $invoice['invoice_number'],
    'Fecha' => date('d/m/Y', strtotime($invoice['invoice_date'])),
    'Cliente' => $invoice['customer_name'],
    'NIF/DNI' => strtoupper($invoice['id_type']) . ' ' . $invoice['id_number'],
    'Total' => number_format($invoice['total'], 2) . ' EUR',
    'Estado' => $invoice['payment_status'] === 'paid' ? 'PAGADO' : ($invoice['payment_status'] === 'partial' ? 'PARCIAL' : 'PENDIENTE'),
    'Empresa' => $invoice['shop_name'],
];

if (!empty($invoice['shop_nif'])) {
    $qr_data['NIF Empresa'] = $invoice['shop_nif'];
}

// Convertir a texto para QR
$qr_text = "";
foreach ($qr_data as $key => $value) {
    $qr_text .= "$key: $value\n";
}

// Generar QR Code con chillerlan/php-qrcode
$qrOptions = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel' => QRCode::ECC_M,
    'scale' => 5,
    'imageBase64' => true,
]);

$qrcode = new QRCode($qrOptions);
$qr_base64 = $qrcode->render($qr_text);

// Generar nombre del archivo
$customer_name_clean = preg_replace('/[^a-zA-Z0-9]/', '_', $invoice['customer_name']);
$customer_name_clean = preg_replace('/_+/', '_', $customer_name_clean);
$customer_name_clean = trim($customer_name_clean, '_');
$invoice_date_formatted = date('d-m-Y', strtotime($invoice['invoice_date']));
$invoice_number_clean = str_replace(['/', '\\', ' '], '-', $invoice['invoice_number']);
$filename = $customer_name_clean . '_' . $invoice_date_formatted . '_' . $invoice_number_clean . '.pdf';

// Estado de pago configuración
$status_config = [
    'pending' => ['text' => 'PENDIENTE', 'bg' => '#fef3c7', 'color' => '#92400e', 'border' => '#f59e0b'],
    'partial' => ['text' => 'PAGO PARCIAL', 'bg' => '#dbeafe', 'color' => '#1e40af', 'border' => '#3b82f6'],
    'paid' => ['text' => 'PAGADO', 'bg' => '#d1fae5', 'color' => '#065f46', 'border' => '#10b981']
];
$payment_status = $status_config[$invoice['payment_status']];

// Colores del tema
$primary = '#1a365d';
$secondary = '#2c5282';
$accent = '#3182ce';
$light_bg = '#f7fafc';
$border = '#e2e8f0';

// Crear HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10pt;
            color: #2d3748;
            line-height: 1.4;
        }

        /* Header */
        .header {
            width: 100%;
            margin-bottom: 15px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left {
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: ' . $primary . ';
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            color: #718096;
            line-height: 1.6;
        }
        .invoice-badge {
            display: inline-block;
            background: ' . $primary . ';
            color: white;
            font-size: 18pt;
            font-weight: bold;
            padding: 8px 20px;
            letter-spacing: 2px;
        }
        .invoice-number {
            font-size: 14pt;
            font-weight: bold;
            color: ' . $primary . ';
            margin: 8px 0;
        }
        .invoice-dates {
            font-size: 9pt;
            color: #4a5568;
        }

        /* Divider */
        .divider {
            height: 3px;
            background: linear-gradient(to right, ' . $primary . ', ' . $accent . ');
            margin: 12px 0;
        }

        /* Info boxes */
        .info-section {
            width: 100%;
            margin: 15px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-cell {
            width: 48%;
            vertical-align: top;
            padding: 0 5px;
        }
        .info-box {
            border: 1px solid ' . $border . ';
            border-radius: 5px;
            overflow: hidden;
        }
        .info-header {
            background: ' . $primary . ';
            color: white;
            padding: 8px 10px;
            font-size: 10pt;
            font-weight: bold;
        }
        .info-content {
            padding: 10px;
            background: white;
        }
        .info-line {
            margin: 4px 0;
            font-size: 9pt;
        }
        .info-label {
            font-weight: bold;
            color: #4a5568;
            display: inline-block;
            min-width: 65px;
        }
        .customer-name {
            font-size: 12pt;
            font-weight: bold;
            color: ' . $primary . ';
            margin-bottom: 5px;
        }
        .payment-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10pt;
            margin: 5px 0;
            background: ' . $payment_status['bg'] . ';
            color: ' . $payment_status['color'] . ';
            border: 2px solid ' . $payment_status['border'] . ';
        }
        .amount-pending {
            color: #c53030;
            font-weight: bold;
        }

        /* Items table */
        .items-section {
            margin: 15px 0;
        }
        .items-title {
            background: ' . $secondary . ';
            color: white;
            padding: 8px 10px;
            font-size: 11pt;
            font-weight: bold;
            border-radius: 4px 4px 0 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid ' . $border . ';
            border-top: none;
        }
        table.items th {
            background: ' . $light_bg . ';
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            color: #4a5568;
            border-bottom: 2px solid ' . $border . ';
        }
        table.items th.center { text-align: center; }
        table.items th.right { text-align: right; }
        table.items td {
            padding: 8px 6px;
            font-size: 9pt;
            border-bottom: 1px solid ' . $border . ';
        }
        table.items td.center { text-align: center; }
        table.items td.right { text-align: right; }
        table.items tr:nth-child(even) {
            background: #fafafa;
        }
        .type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .type-service { background: #e0f2fe; color: #0369a1; }
        .type-product { background: #dcfce7; color: #166534; }
        .type-spare_part { background: #fef3c7; color: #92400e; }

        /* Totals */
        .totals-section {
            margin-top: 15px;
            width: 100%;
        }
        .totals-table {
            width: 250px;
            float: right;
            border: 2px solid ' . $primary . ';
            border-radius: 5px;
            overflow: hidden;
        }
        .totals-table td {
            padding: 8px 10px;
            font-size: 10pt;
        }
        .totals-table .label {
            text-align: right;
            font-weight: 600;
            color: #4a5568;
            background: ' . $light_bg . ';
        }
        .totals-table .amount {
            text-align: right;
            font-weight: bold;
            width: 90px;
            background: white;
        }
        .totals-table .total-row td {
            background: ' . $primary . ' !important;
            color: white;
            font-size: 12pt;
        }

        /* QR Section */
        .qr-section {
            float: left;
            width: 180px;
            text-align: center;
            padding: 10px;
            border: 1px solid ' . $border . ';
            border-radius: 5px;
            background: white;
        }
        .qr-title {
            font-size: 8pt;
            color: #718096;
            margin-bottom: 5px;
        }
        .qr-code img {
            width: 120px;
            height: 120px;
        }
        .qr-info {
            font-size: 7pt;
            color: #a0aec0;
            margin-top: 5px;
        }

        /* Notes */
        .notes-section {
            clear: both;
            margin-top: 20px;
            padding: 10px;
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
        }
        .notes-title {
            font-weight: bold;
            color: #92400e;
            font-size: 10pt;
            margin-bottom: 4px;
        }
        .notes-content {
            color: #78350f;
            font-size: 9pt;
        }

        /* Legal */
        .legal-info {
            clear: both;
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px dashed ' . $border . ';
            font-size: 8pt;
            color: #a0aec0;
            text-align: center;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 0;
            background: ' . $light_bg . ';
            border-top: 2px solid ' . $primary . ';
            font-size: 8pt;
            color: #718096;
            text-align: center;
        }
        .footer-company {
            font-weight: bold;
            color: ' . $primary . ';
        }

        .clearfix { clear: both; }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">';

// Logo
if ($logo_base64) {
    $html .= '<img src="' . $logo_base64 . '" style="max-width: 100px; max-height: 50px; margin-bottom: 8px;"><br>';
}

$html .= '
                <div class="company-name">' . htmlspecialchars($invoice['shop_name']) . '</div>
                <div class="company-info">';

if (!empty($invoice['shop_nif'])) {
    $html .= '<strong>NIF:</strong> ' . htmlspecialchars($invoice['shop_nif']) . '<br>';
}
if (!empty($invoice['shop_address'])) {
    $html .= nl2br(htmlspecialchars($invoice['shop_address'])) . '<br>';
}
if (!empty($invoice['shop_phone'])) {
    $html .= 'Tel: ' . htmlspecialchars($invoice['shop_phone']);
    if (!empty($invoice['shop_phone2'])) {
        $html .= ' / ' . htmlspecialchars($invoice['shop_phone2']);
    }
    $html .= '<br>';
}
if (!empty($invoice['shop_email'])) {
    $html .= htmlspecialchars($invoice['shop_email']);
}

$html .= '
                </div>
            </td>
            <td class="header-right">
                <div class="invoice-badge">FACTURA</div><br>
                <div class="invoice-number">' . htmlspecialchars($invoice['invoice_number']) . '</div>
                <div class="invoice-dates">
                    <strong>Fecha:</strong> ' . date('d/m/Y', strtotime($invoice['invoice_date'])) . '<br>';

if (!empty($invoice['due_date'])) {
    $html .= '<strong>Vencimiento:</strong> ' . date('d/m/Y', strtotime($invoice['due_date']));
}

$html .= '
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
            <td class="info-cell">
                <div class="info-box">
                    <div class="info-header">DATOS DEL CLIENTE</div>
                    <div class="info-content">
                        <div class="customer-name">' . htmlspecialchars($invoice['customer_name']) . '</div>
                        <div class="info-line">
                            <span class="info-label">Documento:</span>
                            ' . strtoupper($invoice['id_type']) . ' ' . htmlspecialchars($invoice['id_number']) . '
                        </div>
                        <div class="info-line">
                            <span class="info-label">Teléfono:</span>
                            ' . htmlspecialchars($invoice['customer_phone']) . '
                        </div>';

if (!empty($invoice['customer_email'])) {
    $html .= '<div class="info-line">
                            <span class="info-label">Email:</span>
                            ' . htmlspecialchars($invoice['customer_email']) . '
                        </div>';
}

if (!empty($invoice['customer_address'])) {
    $html .= '<div class="info-line">
                            <span class="info-label">Dirección:</span>
                            ' . nl2br(htmlspecialchars($invoice['customer_address'])) . '
                        </div>';
}

$html .= '
                    </div>
                </div>
            </td>
            <td class="info-cell">
                <div class="info-box">
                    <div class="info-header">ESTADO DE PAGO</div>
                    <div class="info-content">
                        <div class="payment-badge">' . $payment_status['text'] . '</div>';

if ($invoice['payment_status'] === 'paid') {
    $html .= '<div class="info-line">
                            <span class="info-label">Fecha pago:</span>
                            ' . date('d/m/Y', strtotime($invoice['payment_date'])) . '
                        </div>
                        <div class="info-line">
                            <span class="info-label">Método:</span>
                            ' . ucfirst($invoice['payment_method']) . '
                        </div>';
} elseif ($invoice['payment_status'] === 'partial') {
    $html .= '<div class="info-line">
                            <span class="info-label">Pagado:</span>
                            <strong style="color: #276749;">€' . number_format($invoice['paid_amount'], 2) . '</strong>
                        </div>
                        <div class="info-line">
                            <span class="info-label">Pendiente:</span>
                            <span class="amount-pending">€' . number_format($invoice['total'] - $invoice['paid_amount'], 2) . '</span>
                        </div>';
} else {
    $html .= '<div class="info-line">
                            <span class="info-label">A pagar:</span>
                            <span class="amount-pending" style="font-size: 12pt;">€' . number_format($invoice['total'], 2) . '</span>
                        </div>';
}

$html .= '
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS -->
<div class="items-section">
    <div class="items-title">DETALLE DE LA FACTURA</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 40%;">Descripción</th>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 15%;">IMEI/Serie</th>
                <th style="width: 8%;" class="center">Cant.</th>
                <th style="width: 12%;" class="right">P.Unit.</th>
                <th style="width: 13%;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>';

$type_config = [
    'service' => ['label' => 'Servicio', 'class' => 'type-service'],
    'product' => ['label' => 'Producto', 'class' => 'type-product'],
    'spare_part' => ['label' => 'Repuesto', 'class' => 'type-spare_part']
];

foreach ($items as $item) {
    $type = $type_config[$item['item_type']] ?? $type_config['service'];
    $html .= '<tr>
                <td>' . nl2br(htmlspecialchars($item['description'])) . '</td>
                <td><span class="type-badge ' . $type['class'] . '">' . $type['label'] . '</span></td>
                <td>' . (!empty($item['imei']) ? '<code style="font-size: 8pt;">' . htmlspecialchars($item['imei']) . '</code>' : '-') . '</td>
                <td class="center">' . $item['quantity'] . '</td>
                <td class="right">€' . number_format($item['unit_price'], 2) . '</td>
                <td class="right"><strong>€' . number_format($item['subtotal'], 2) . '</strong></td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
</div>

<!-- TOTALS & QR -->
<div class="totals-section">
    <!-- QR Code -->
    <div class="qr-section">
        <div class="qr-title">CÓDIGO QR DE VERIFICACIÓN</div>
        <div class="qr-code">
            <img src="' . $qr_base64 . '" alt="QR Code">
        </div>
        <div class="qr-info">Escanea para ver información</div>
    </div>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td class="label">Base Imponible:</td>
            <td class="amount">€' . number_format($invoice['subtotal'], 2) . '</td>
        </tr>
        <tr>
            <td class="label">IVA (' . number_format($invoice['iva_rate'], 0) . '%):</td>
            <td class="amount">€' . number_format($invoice['iva_amount'], 2) . '</td>
        </tr>
        <tr class="total-row">
            <td class="label">TOTAL:</td>
            <td class="amount">€' . number_format($invoice['total'], 2) . '</td>
        </tr>
    </table>
</div>

<div class="clearfix"></div>';

// Notes
if (!empty($invoice['notes'])) {
    $html .= '
<div class="notes-section">
    <div class="notes-title">Observaciones:</div>
    <div class="notes-content">' . nl2br(htmlspecialchars($invoice['notes'])) . '</div>
</div>';
}

$html .= '
<!-- Legal -->
<div class="legal-info">
    Factura emitida conforme a la normativa fiscal vigente.';

if (!empty($invoice['shop_nif'])) {
    $html .= ' • NIF: ' . htmlspecialchars($invoice['shop_nif']);
}

$html .= '
</div>

</body>
</html>';

// Crear PDF con mPDF
try {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 25,
        'margin_header' => 10,
        'margin_footer' => 10,
        'default_font' => 'dejavusans',
        'default_font_size' => 10,
    ]);

    // Metadata
    $mpdf->SetTitle('Factura ' . $invoice['invoice_number']);
    $mpdf->SetAuthor($invoice['shop_name']);
    $mpdf->SetCreator('RepairPoint');
    $mpdf->SetSubject('Factura para ' . $invoice['customer_name']);

    // Footer
    $mpdf->SetHTMLFooter('
        <div style="text-align: center; font-size: 8pt; color: #718096; border-top: 2px solid ' . $primary . '; padding-top: 8px;">
            <span style="font-weight: bold; color: ' . $primary . ';">' . htmlspecialchars($invoice['shop_name']) . '</span>
            • Documento generado el ' . date('d/m/Y H:i') . '
            • Factura ' . htmlspecialchars($invoice['invoice_number']) . '
            • Página {PAGENO} de {nbpg}
        </div>
    ');

    // Escribir HTML
    $mpdf->WriteHTML($html);

    // Output
    if ($mode === 'download') {
        $mpdf->Output($filename, Destination::DOWNLOAD);
    } else {
        $mpdf->Output($filename, Destination::INLINE);
    }

} catch (Exception $e) {
    die('Error al generar PDF: ' . $e->getMessage());
}
