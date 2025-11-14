<?php
/**
 * RepairPoint - Ticket 80mm (Estándar - POS Térmica)
 * Optimizado para impresoras térmicas de 80mm
 * Para el CLIENTE - Información estándar
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
$auto_print = intval($_GET['auto_print'] ?? 0);

if (!$repair_id) {
    die('ID de reparación no válido');
}

// Obtener datos de la reparación
$db = getDB();
$repair = $db->selectOne(
    "SELECT r.*, b.name as brand_name, m.name as model_name, s.*
     FROM repairs r
     JOIN brands b ON r.brand_id = b.id
     JOIN models m ON r.model_id = m.id
     JOIN shops s ON r.shop_id = s.id
     WHERE r.id = ? AND r.shop_id = ?",
    [$repair_id, $shop_id]
);

if (!$repair) {
    die('Reparación no encontrada');
}

// Log de actividad
logActivity('ticket_printed', "Ticket 80mm impreso para reparación #{$repair['reference']}", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket 80mm - #<?= htmlspecialchars($repair['reference']) ?></title>

    <!-- JsBarcode para códigos de barras -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: white;
            color: #000;
            line-height: 1.4;
            font-size: 10pt;
        }

        /* Configuración para ticket 80mm */
        .ticket {
            width: 80mm;
            margin: 0 auto;
            padding: 3mm;
            background: white;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }

        .shop-logo {
            max-width: 30mm;
            max-height: 30mm;
            margin-bottom: 2mm;
        }

        .shop-name {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2mm;
        }

        .shop-contact {
            font-size: 8pt;
            line-height: 1.3;
        }

        /* Título del ticket */
        .ticket-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 3mm 0;
            text-transform: uppercase;
            border: 2px solid #000;
            padding: 2mm;
        }

        .ticket-reference {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 3mm;
            letter-spacing: 2px;
        }

        /* Información del cliente */
        .info-section {
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .info-row {
            display: flex;
            margin-bottom: 2mm;
        }

        .info-label {
            font-weight: bold;
            min-width: 25mm;
        }

        .info-value {
            flex: 1;
            word-wrap: break-word;
        }

        /* Dispositivo */
        .device-section {
            margin: 3mm 0;
            border: 2px solid #000;
            padding: 3mm;
            background: repeating-linear-gradient(
                45deg,
                #f8f8f8,
                #f8f8f8 2mm,
                #ffffff 2mm,
                #ffffff 4mm
            );
        }

        .device-label {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .device-name {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
        }

        /* Problema */
        .problem-section {
            margin: 3mm 0;
            border: 2px dashed #000;
            padding: 3mm;
        }

        .problem-label {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .problem-text {
            font-size: 9pt;
            font-style: italic;
            word-wrap: break-word;
            line-height: 1.4;
        }

        /* Coste */
        .cost-section {
            text-align: center;
            border: 3px double #000;
            padding: 3mm;
            margin: 3mm 0;
            background: #000;
            color: white;
        }

        .cost-label {
            font-size: 9pt;
            margin-bottom: 2mm;
        }

        .cost-value {
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Estado */
        .status-section {
            text-align: center;
            margin: 3mm 0;
            padding: 2mm;
            border: 1px solid #000;
        }

        .status-label {
            font-size: 8pt;
            margin-bottom: 1mm;
        }

        .status-badge {
            display: inline-block;
            padding: 2mm 4mm;
            border: 2px solid #000;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }

        /* Separador */
        .separator {
            border-bottom: 1px dashed #000;
            margin: 4mm 0;
        }

        .separator-double {
            border-bottom: 2px solid #000;
            margin: 4mm 0;
        }

        /* Código de barras */
        .barcode-section {
            text-align: center;
            margin: 4mm 0;
            padding: 3mm;
            border: 2px dashed #000;
        }

        .barcode-title {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .barcode-svg {
            width: 70mm;
            height: auto;
        }

        /* Fechas */
        .dates-section {
            font-size: 8pt;
            margin: 3mm 0;
            padding: 2mm;
            background: #f8f8f8;
            border: 1px solid #000;
        }

        .date-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        /* Condiciones */
        .conditions {
            font-size: 7pt;
            line-height: 1.3;
            margin-top: 3mm;
            padding: 3mm;
            border: 2px solid #000;
            background: #f0f0f0;
        }

        .conditions-title {
            font-weight: bold;
            font-size: 8pt;
            text-align: center;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .conditions p {
            margin: 1.5mm 0;
        }

        .conditions strong {
            font-size: 8pt;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 8pt;
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 2px solid #000;
        }

        .footer-thanks {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2mm;
        }

        .footer-message {
            font-size: 8pt;
            margin-bottom: 2mm;
        }

        .footer-date {
            font-size: 7pt;
            color: #666;
            margin-top: 2mm;
        }

        /* Controles de impresión */
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 10px;
            border-radius: 5px;
            z-index: 1000;
        }

        .print-controls button {
            background: #333;
            color: white;
            border: none;
            padding: 8px 15px;
            margin: 2px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }

        .print-controls button:hover {
            background: #555;
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

            .ticket {
                margin: 0;
                padding: 3mm;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .cost-section {
                background: #000 !important;
                color: white !important;
            }
        }

        /* Vista previa en pantalla */
        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
            }

            .ticket {
                box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            }
        }
    </style>
</head>
<body>
    <!-- Controles de impresión -->
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.close()">❌ Cerrar</button>
    </div>

    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <?php if (!empty($repair['logo'])): ?>
                <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" alt="Logo" class="shop-logo">
            <?php endif; ?>
            <div class="shop-name"><?= htmlspecialchars($repair['name']) ?></div>
            <div class="shop-contact">
                <?php if (!empty($repair['address'])): ?>
                    <?= htmlspecialchars($repair['address']) ?><br>
                <?php endif; ?>
                <?php if (!empty($repair['phone1'])): ?>
                    Tel: <?= htmlspecialchars($repair['phone1']) ?>
                    <?php if (!empty($repair['phone2'])): ?>
                        / <?= htmlspecialchars($repair['phone2']) ?>
                    <?php endif; ?>
                    <br>
                <?php endif; ?>
                <?php if (!empty($repair['email'])): ?>
                    <?= htmlspecialchars($repair['email']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Título -->
        <div class="ticket-title">REPARACIÓN</div>
        <div class="ticket-reference">#<?= htmlspecialchars($repair['reference']) ?></div>

        <!-- Información del cliente -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Cliente:</div>
                <div class="info-value"><?= htmlspecialchars($repair['customer_name']) ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value"><?= htmlspecialchars($repair['customer_phone']) ?></div>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Dispositivo -->
        <div class="device-section">
            <div class="device-label">DISPOSITIVO:</div>
            <div class="device-name">
                <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
            </div>
        </div>

        <!-- Problema -->
        <div class="problem-section">
            <div class="problem-label">Problema:</div>
            <div class="problem-text"><?= nl2br(htmlspecialchars($repair['issue_description'])) ?></div>
        </div>

        <!-- Coste -->
        <?php if (!empty($repair['estimated_cost']) || !empty($repair['actual_cost'])): ?>
            <div class="cost-section">
                <div class="cost-label">COSTE:</div>
                <div class="cost-value">
                    €<?= number_format($repair['actual_cost'] ?? $repair['estimated_cost'], 2) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estado -->
        <div class="status-section">
            <div class="status-label">Estado actual:</div>
            <div class="status-badge"><?= getStatusName($repair['status']) ?></div>
        </div>

        <!-- Separador doble -->
        <div class="separator-double"></div>

        <!-- Código de barras -->
        <div class="barcode-section">
            <div class="barcode-title">CÓDIGO DE IDENTIFICACIÓN</div>
            <svg id="barcode" class="barcode-svg"></svg>
        </div>

        <!-- Separador doble -->
        <div class="separator-double"></div>

        <!-- Fechas -->
        <div class="dates-section">
            <div class="date-row">
                <span><strong>Recibido:</strong></span>
                <span><?= formatDate($repair['received_at'], 'd/m/Y H:i') ?></span>
            </div>
            <?php if (!empty($repair['estimated_completion'])): ?>
                <div class="date-row">
                    <span><strong>Est. finalización:</strong></span>
                    <span><?= formatDate($repair['estimated_completion'], 'd/m/Y') ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Condiciones -->
        <div class="conditions">
            <div class="conditions-title">⚠️ CONDICIONES IMPORTANTES ⚠️</div>
            <p><strong>• RECOGIDA:</strong> Dispositivo debe recogerse en máximo 30 días tras completar la reparación.</p>
            <p><strong>• RESPONSABILIDAD:</strong> Pasados 30 días, NO nos responsabilizamos por pérdida o daños.</p>
            <p><strong>• TICKET:</strong> Presentar este ticket es OBLIGATORIO para recoger el dispositivo.</p>
            <p><strong>• GARANTÍA:</strong> Apertura externa anula toda garantía.</p>
            <p style="text-align: center; margin-top: 2mm; font-style: italic;">
                Ver condiciones completas en recibo A5
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-thanks">*** GRACIAS POR SU CONFIANZA ***</div>
            <div class="footer-message">
                Conserve este ticket para recoger su dispositivo
            </div>
            <div class="footer-date">
                Impreso: <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateBarcode();

            <?php if ($auto_print): ?>
            // Auto-imprimir si está activado
            setTimeout(function() {
                window.print();
            }, 1000);
            <?php endif; ?>
        });

        function generateBarcode() {
            try {
                const barcodeData = '<?= $repair['reference'] ?>';

                JsBarcode("#barcode", barcodeData, {
                    format: "CODE128",
                    width: 2,
                    height: 40,
                    displayValue: false,
                    background: "#ffffff",
                    lineColor: "#000000",
                    margin: 2
                });
            } catch (error) {
                console.error('Error generando código de barras:', error);
            }
        }

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }

            if (e.key === 'Escape') {
                window.close();
            }
        });

        // Opcional: auto-cerrar después de imprimir
        window.addEventListener('afterprint', function() {
            setTimeout(() => {
                if (confirm('¿Desea cerrar la ventana?')) {
                    window.close();
                }
            }, 1000);
        });
    </script>
</body>
</html>
