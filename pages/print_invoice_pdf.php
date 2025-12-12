<?php
/**
 * RepairPoint - Generador PDF de Facturas
 * Generador profesional de facturas en PDF con logo y diseño español
 */

define('SECURE_ACCESS', true);
require_once '../config/config.php';

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

// Obtener información de la factura con JOIN
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

// Configuración de caracteres
header('Content-Type: text/html; charset=UTF-8');

// Generar HTML para el PDF
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura ' . htmlspecialchars($invoice['invoice_number']) . '</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 3px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .header-right {
            text-align: right;
        }

        .logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #0066cc;
            margin: 0;
        }

        .company-details {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }

        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #0066cc;
            margin: 0;
        }

        .invoice-number {
            font-size: 14pt;
            color: #666;
            margin: 5px 0;
        }

        .invoice-dates {
            font-size: 9pt;
            color: #666;
        }

        .info-section {
            display: table;
            width: 100%;
            margin: 20px 0;
        }

        .info-box {
            display: table-cell;
            vertical-align: top;
            width: 48%;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .info-box + .info-box {
            margin-left: 4%;
        }

        .info-title {
            font-size: 12pt;
            font-weight: bold;
            color: #0066cc;
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #0066cc;
        }

        .info-line {
            margin: 5px 0;
            font-size: 10pt;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table.items thead {
            background-color: #0066cc;
            color: white;
        }

        table.items th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
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

        table.items td {
            padding: 10px;
            font-size: 10pt;
        }

        table.items td.center {
            text-align: center;
        }

        table.items td.right {
            text-align: right;
        }

        .item-description {
            font-weight: 500;
        }

        .item-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
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

        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 8px;
            font-size: 11pt;
        }

        .totals .label {
            text-align: right;
            font-weight: bold;
            color: #666;
        }

        .totals .amount {
            text-align: right;
            width: 120px;
        }

        .totals .total-row {
            background-color: #0066cc;
            color: white;
            font-size: 14pt;
            font-weight: bold;
        }

        .totals .subtotal-row {
            border-top: 1px solid #dee2e6;
        }

        .notes {
            clear: both;
            margin-top: 30px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .notes-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }

        .notes-content {
            color: #856404;
            font-size: 10pt;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 20mm;
            border-top: 2px solid #0066cc;
            background-color: #f8f9fa;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }

        .payment-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 10px;
        }

        .status-pending {
            background-color: #ffc107;
            color: #333;
        }

        .status-partial {
            background-color: #17a2b8;
            color: white;
        }

        .status-paid {
            background-color: #28a745;
            color: white;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>';

// Header con logo y datos de la empresa
$html .= '
    <div class="header">
        <div class="header-content">
            <div class="header-left">';

if (!empty($invoice['shop_logo']) && file_exists(ROOT_PATH . $invoice['shop_logo'])) {
    $html .= '<img src="' . ROOT_PATH . $invoice['shop_logo'] . '" alt="Logo" class="logo">';
}

$html .= '
                <h1 class="company-name">' . htmlspecialchars($invoice['shop_name']) . '</h1>
                <div class="company-details">';

if (!empty($invoice['shop_address'])) {
    $html .= htmlspecialchars($invoice['shop_address']) . '<br>';
}
if (!empty($invoice['shop_phone'])) {
    $html .= 'Tel: ' . htmlspecialchars($invoice['shop_phone']) . '<br>';
}
if (!empty($invoice['shop_email'])) {
    $html .= 'Email: ' . htmlspecialchars($invoice['shop_email']);
}

$html .= '
                </div>
            </div>
            <div class="header-right">
                <h1 class="invoice-title">FACTURA</h1>
                <div class="invoice-number">' . htmlspecialchars($invoice['invoice_number']) . '</div>
                <div class="invoice-dates">
                    <strong>Fecha:</strong> ' . date('d/m/Y', strtotime($invoice['invoice_date'])) . '<br>';

if (!empty($invoice['due_date'])) {
    $html .= '<strong>Vencimiento:</strong> ' . date('d/m/Y', strtotime($invoice['due_date']));
}

$html .= '
                </div>
            </div>
        </div>
    </div>';

// Información del cliente
$html .= '
    <div class="info-section">
        <div class="info-box">
            <h3 class="info-title">DATOS DEL CLIENTE</h3>
            <div class="info-line"><span class="info-label">Nombre:</span> ' . htmlspecialchars($invoice['customer_name']) . '</div>
            <div class="info-line"><span class="info-label">Documento:</span> ' . strtoupper($invoice['id_type']) . ' ' . htmlspecialchars($invoice['id_number']) . '</div>
            <div class="info-line"><span class="info-label">Teléfono:</span> ' . htmlspecialchars($invoice['customer_phone']) . '</div>';

if (!empty($invoice['customer_email'])) {
    $html .= '<div class="info-line"><span class="info-label">Email:</span> ' . htmlspecialchars($invoice['customer_email']) . '</div>';
}

if (!empty($invoice['customer_address'])) {
    $html .= '<div class="info-line"><span class="info-label">Dirección:</span> ' . nl2br(htmlspecialchars($invoice['customer_address'])) . '</div>';
}

$html .= '
        </div>
        <div class="info-box">
            <h3 class="info-title">ESTADO DE PAGO</h3>';

// Estado de pago
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

$html .= '
            <div class="payment-status ' . $status_class[$invoice['payment_status']] . '">
                ' . $status_text[$invoice['payment_status']] . '
            </div>';

if ($invoice['payment_status'] === 'paid') {
    $html .= '
            <div class="info-line" style="margin-top: 10px;">
                <span class="info-label">Fecha de Pago:</span> ' . date('d/m/Y', strtotime($invoice['payment_date'])) . '
            </div>
            <div class="info-line">
                <span class="info-label">Método:</span> ' . ucfirst($invoice['payment_method']) . '
            </div>';
} elseif ($invoice['payment_status'] === 'partial') {
    $html .= '
            <div class="info-line" style="margin-top: 10px;">
                <span class="info-label">Pagado:</span> €' . number_format($invoice['paid_amount'], 2) . '
            </div>
            <div class="info-line">
                <span class="info-label">Pendiente:</span> <strong style="color: #dc3545;">€' . number_format($invoice['total'] - $invoice['paid_amount'], 2) . '</strong>
            </div>';
}

$html .= '
        </div>
    </div>';

// Tabla de items
$html .= '
    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th width="80">Tipo</th>
                <th width="100">IMEI</th>
                <th width="50" class="center">Cant.</th>
                <th width="80" class="right">Precio Unit.</th>
                <th width="80" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>';

foreach ($items as $item) {
    $type_classes = [
        'service' => 'type-service',
        'product' => 'type-product',
        'spare_part' => 'type-spare_part'
    ];

    $type_labels = [
        'service' => 'Servicio',
        'product' => 'Producto',
        'spare_part' => 'Repuesto'
    ];

    $html .= '
            <tr>
                <td><div class="item-description">' . nl2br(htmlspecialchars($item['description'])) . '</div></td>
                <td><span class="item-type ' . $type_classes[$item['item_type']] . '">' . $type_labels[$item['item_type']] . '</span></td>
                <td>' . (!empty($item['imei']) ? htmlspecialchars($item['imei']) : '-') . '</td>
                <td class="center">' . $item['quantity'] . '</td>
                <td class="right">€' . number_format($item['unit_price'], 2) . '</td>
                <td class="right"><strong>€' . number_format($item['subtotal'], 2) . '</strong></td>
            </tr>';
}

$html .= '
        </tbody>
    </table>';

// Totales
$html .= '
    <div class="totals">
        <table>
            <tr class="subtotal-row">
                <td class="label">Subtotal:</td>
                <td class="amount">€' . number_format($invoice['subtotal'], 2) . '</td>
            </tr>
            <tr>
                <td class="label">IVA (' . $invoice['iva_rate'] . '%):</td>
                <td class="amount">€' . number_format($invoice['iva_amount'], 2) . '</td>
            </tr>
            <tr class="total-row">
                <td class="label">TOTAL:</td>
                <td class="amount">€' . number_format($invoice['total'], 2) . '</td>
            </tr>
        </table>
    </div>';

// Notas adicionales
if (!empty($invoice['notes'])) {
    $html .= '
    <div class="notes">
        <div class="notes-title">Notas:</div>
        <div class="notes-content">' . nl2br(htmlspecialchars($invoice['notes'])) . '</div>
    </div>';
}

$html .= '
    <div class="footer">
        Gracias por su confianza • ' . htmlspecialchars($invoice['shop_name']) . ' • Factura generada el ' . date('d/m/Y H:i') . '
    </div>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14pt;">
            📄 Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14pt; margin-left: 10px;">
            ✕ Cerrar
        </button>
    </div>
</body>
</html>';

// Mostrar el HTML
echo $html;
?>
