<?php
/**
 * RepairPoint - Ticket 57mm (Pequeño - POS Térmica)
 * Optimizado para impresoras térmicas de 57mm
 * Para el CLIENTE - Información compacta
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

// Obtener datos de la reparación con información de reapertura
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

// Calcular información de garantía
$warranty_days = $repair['warranty_days'] ?? 30;
$warranty_days_left = 0;
$is_under_warranty = false;

if ($repair['delivered_at']) {
    $warranty_days_left = calculateWarrantyDaysLeft($repair['delivered_at'], $warranty_days);
    $is_under_warranty = isUnderWarranty($repair['delivered_at'], $warranty_days);
}

// Log de actividad
logActivity('ticket_printed', "Ticket 57mm impreso para reparación #{$repair['reference']}", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket 57mm - #<?= htmlspecialchars($repair['reference']) ?></title>

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
            font-family: 'Courier New', 'Arial', monospace;
            background: white;
            color: #000;
            line-height: 1.3;
            font-size: 9pt;
            -webkit-font-smoothing: antialiased;
        }

        /* Configuración para ticket 57mm - Optimizado para impresoras térmicas */
        .ticket {
            width: 57mm;
            max-width: 57mm;
            margin: 0 auto;
            padding: 2mm;
            background: white;
            color: #000;
        }

        /* Header compacto */
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }

        .shop-logo {
            max-width: 20mm;
            max-height: 20mm;
            margin-bottom: 1mm;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .shop-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
            color: #000;
        }

        .shop-contact {
            font-size: 7pt;
            line-height: 1.3;
            color: #000;
        }

        /* Título del ticket */
        .ticket-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin: 2mm 0;
            text-transform: uppercase;
            color: #000;
        }

        .ticket-reference {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2mm;
            letter-spacing: 1px;
            color: #000;
        }

        /* Información del cliente */
        .info-section {
            margin-bottom: 2mm;
            font-size: 8pt;
        }

        .info-label {
            font-weight: bold;
            margin-top: 1mm;
            color: #000;
        }

        .info-value {
            margin-left: 1mm;
            word-wrap: break-word;
            color: #000;
        }

        /* Dispositivo */
        .device-box {
            text-align: center;
            border: 2px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            background: white;
        }

        .device-name {
            font-size: 9pt;
            font-weight: bold;
            color: #000;
        }

        /* Problema */
        .problem-box {
            border: 1px dashed #000;
            padding: 2mm;
            margin: 2mm 0;
            font-size: 8pt;
        }

        .problem-label {
            font-weight: bold;
            margin-bottom: 1mm;
            color: #000;
        }

        .problem-text {
            font-style: italic;
            word-wrap: break-word;
            color: #000;
        }

        /* Coste - Simplificado para impresoras térmicas */
        .cost-box {
            text-align: center;
            border: 3px double #000;
            padding: 3mm;
            margin: 2mm 0;
            background: white;
            color: #000;
        }

        .cost-label {
            font-size: 8pt;
            margin-bottom: 1mm;
            color: #000;
        }

        .cost-value {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
            text-decoration: underline;
        }

        /* Sección de garantía compacta - Sin gradientes */
        .warranty-box {
            border: 3px double #000;
            padding: 2mm;
            margin: 2mm 0;
            background: white;
        }

        .warranty-header {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            padding: 2mm;
            border: 2px solid #000;
            color: #000;
            margin-bottom: 2mm;
            text-decoration: underline;
        }

        .warranty-alert {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 1mm;
            padding: 1mm;
            border: 1px solid #000;
            background: white;
            color: #000;
        }

        .warranty-info {
            font-size: 7pt;
            margin: 1mm 0;
            padding: 1mm;
            background: white;
            border: 1px solid #000;
            color: #000;
        }

        .warranty-info strong {
            display: block;
            margin-bottom: 0.5mm;
        }

        /* Separador */
        .separator {
            border-bottom: 1px dashed #000;
            margin: 3mm 0;
        }

        /* Código de barras */
        .barcode-section {
            text-align: center;
            margin: 3mm 0;
        }

        .barcode-svg {
            width: 50mm;
            height: auto;
            max-width: 100%;
        }

        /* Condiciones compactas */
        .conditions {
            font-size: 7pt;
            line-height: 1.3;
            margin-top: 2mm;
            padding-top: 2mm;
            border-top: 1px dashed #000;
            color: #000;
        }

        .conditions p {
            margin: 1mm 0;
        }

        .conditions strong {
            font-size: 8pt;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 8pt;
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px solid #000;
            color: #000;
        }

        .footer-date {
            font-size: 7pt;
            margin-top: 1mm;
            color: #000;
        }

        /* Controles de impresión */
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 15px;
            border-radius: 8px;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .print-controls h4 {
            margin-bottom: 10px;
            font-size: 13px;
            border-bottom: 1px solid #555;
            padding-bottom: 5px;
        }

        .print-controls button {
            background: #444;
            color: white;
            border: 1px solid #666;
            padding: 10px 15px;
            margin: 3px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            display: block;
            width: 100%;
            text-align: left;
        }

        .print-controls button:hover {
            background: #555;
        }

        .print-controls button:active {
            background: #333;
        }

        /* Print styles - Optimizado para impresoras térmicas */
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .print-controls {
                display: none !important;
            }

            .ticket {
                margin: 0;
                padding: 2mm;
                width: 57mm;
                max-width: 57mm;
            }

            @page {
                size: 57mm auto;
                margin: 0;
                padding: 0;
            }

            /* Forzar colores para impresión */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Asegurar que todo sea negro sobre blanco */
            .cost-box,
            .warranty-header,
            .device-box,
            .problem-box,
            .warranty-box,
            .warranty-alert,
            .warranty-info {
                background: white !important;
                color: #000 !important;
            }

            /* Asegurar bordes visibles */
            .separator {
                border-bottom: 1px dashed #000 !important;
            }

            .header {
                border-bottom: 1px dashed #000 !important;
            }

            .footer {
                border-top: 1px solid #000 !important;
            }

            .conditions {
                border-top: 1px dashed #000 !important;
            }
        }

        /* Vista previa en pantalla */
        @media screen {
            body {
                background: #e0e0e0;
                padding: 20px;
            }

            .ticket {
                box-shadow: 0 5px 25px rgba(0,0,0,0.3);
                border: 1px solid #ccc;
            }
        }
    </style>
</head>
<body>
    <!-- Controles de impresión mejorados -->
    <div class="print-controls">
        <h4>⚙️ خيارات الطباعة</h4>
        <button onclick="printPDF()" style="background: #2ecc71; font-weight: bold;">📑 طباعة PDF (موصى به!)</button>
        <button onclick="downloadPDF()">💾 تحميل PDF</button>
        <hr style="border: 1px solid #555; margin: 8px 0;">
        <button onclick="window.print()">🖨️ طباعة عادية</button>
        <button onclick="printSimplified()">📄 طباعة مبسطة (نص فقط)</button>
        <button onclick="adjustScale(0.9)">🔍 تصغير 10%</button>
        <button onclick="adjustScale(1.1)">🔎 تكبير 10%</button>
        <button onclick="adjustScale(1.0)">↺ إعادة تعيين</button>
        <button onclick="window.close()">❌ إغلاق</button>
    </div>

    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <?php if (!empty($repair['logo'])): ?>
                <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" alt="Logo" class="shop-logo">
            <?php endif; ?>
            <div class="shop-name"><?= htmlspecialchars($repair['name']) ?></div>
            <div class="shop-contact">
                <?php if (!empty($repair['phone1'])): ?>
                    Tel: <?= htmlspecialchars($repair['phone1']) ?><br>
                <?php endif; ?>
                <?php if (!empty($repair['address'])): ?>
                    <?= htmlspecialchars(substr($repair['address'], 0, 30)) ?><?= strlen($repair['address']) > 30 ? '...' : '' ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Título -->
        <div class="ticket-title">REPARACIÓN</div>
        <div class="ticket-reference">#<?= htmlspecialchars($repair['reference']) ?></div>

        <!-- Información del cliente -->
        <div class="info-section">
            <div class="info-label">Cliente:</div>
            <div class="info-value"><?= htmlspecialchars($repair['customer_name']) ?></div>

            <div class="info-label">Tel:</div>
            <div class="info-value"><?= htmlspecialchars($repair['customer_phone']) ?></div>
        </div>

        <!-- Dispositivo -->
        <div class="device-box">
            <div style="font-size: 7pt; margin-bottom: 1mm;">Dispositivo:</div>
            <div class="device-name">
                <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
            </div>
        </div>

        <!-- Problema -->
        <div class="problem-box">
            <div class="problem-label">Problema:</div>
            <div class="problem-text"><?= nl2br(htmlspecialchars($repair['issue_description'])) ?></div>
        </div>

        <!-- Coste -->
        <?php if (!empty($repair['estimated_cost']) || !empty($repair['actual_cost'])): ?>
            <div class="cost-box">
                <div class="cost-label">Coste:</div>
                <div class="cost-value">
                    €<?= number_format($repair['actual_cost'] ?? $repair['estimated_cost'], 2) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Información de garantía - Solo si está reabierto -->
        <?php if (!empty($repair['is_reopened'])): ?>
            <div class="warranty-box">
                <div class="warranty-header">*** BAJO GARANTÍA ***</div>

                <div class="warranty-alert">
                    REAPERTURA BAJO GARANTÍA
                </div>

                <?php if (!empty($repair['delivered_at']) && !empty($warranty_days)): ?>
                    <div class="warranty-info">
                        <strong>VÁLIDA HASTA:</strong>
                        <?= date('d/m/Y', strtotime($repair['delivered_at'] . " +{$warranty_days} days")) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($repair['reopen_reason'])): ?>
                    <div class="warranty-info">
                        <strong>MOTIVO:</strong>
                        <?= htmlspecialchars(substr($repair['reopen_reason'], 0, 50)) ?><?= strlen($repair['reopen_reason']) > 50 ? '...' : '' ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Separador -->
        <div class="separator"></div>

        <!-- Código de barras -->
        <div class="barcode-section">
            <svg id="barcode" class="barcode-svg"></svg>
        </div>

        <!-- Separador -->
        <div class="separator"></div>

        <!-- Condiciones compactas -->
        <div class="conditions">
            <p><strong>CONDICIONES:</strong></p>
            <p>• Recoger en 30 días tras reparación.</p>
            <p>• Presentar este ticket obligatorio.</p>
            <p>• Ver condiciones completas en recibo A5.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>*** GRACIAS ***</div>
            <div class="footer-date">
                <?= formatDate($repair['received_at'], 'd/m/Y H:i') ?>
            </div>
        </div>
    </div>

    <script>
        let currentScale = 1.0;

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
                    width: 1.5,
                    height: 25,
                    displayValue: false,
                    background: "#ffffff",
                    lineColor: "#000000",
                    margin: 0
                });
            } catch (error) {
                console.error('Error generando código de barras:', error);
            }
        }

        // تعديل حجم التذكرة
        function adjustScale(scale) {
            currentScale = scale;
            const ticket = document.querySelector('.ticket');
            ticket.style.transform = `scale(${scale})`;
            ticket.style.transformOrigin = 'top center';

            // تحديث رسالة للمستخدم
            if (scale !== 1.0) {
                ticket.style.marginBottom = '20px';
            } else {
                ticket.style.marginBottom = '0';
            }
        }

        // طباعة PDF (موصى به)
        function printPDF() {
            const repairId = <?= $repair_id ?>;
            window.open(`print_ticket_57mm_pdf.php?id=${repairId}&action=view`, '_blank');
        }

        // تحميل PDF
        function downloadPDF() {
            const repairId = <?= $repair_id ?>;
            window.location.href = `print_ticket_57mm_pdf.php?id=${repairId}&action=download`;
        }

        // طباعة مبسطة (نص فقط)
        function printSimplified() {
            // إخفاء العناصر المعقدة مؤقتاً
            const elementsToHide = [
                '.shop-logo',
                '.barcode-section',
                '.cost-box',
                '.warranty-box'
            ];

            elementsToHide.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.dataset.originalDisplay = el.style.display;
                    el.style.display = 'none';
                });
            });

            // إزالة الحدود والخلفيات
            const ticket = document.querySelector('.ticket');
            ticket.dataset.originalStyle = ticket.getAttribute('style');

            const allBoxes = document.querySelectorAll('.device-box, .problem-box');
            allBoxes.forEach(box => {
                box.style.border = '1px solid #000';
                box.style.background = 'white';
            });

            // طباعة
            window.print();

            // إعادة العناصر
            setTimeout(() => {
                elementsToHide.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => {
                        el.style.display = el.dataset.originalDisplay || '';
                    });
                });
                allBoxes.forEach(box => {
                    box.style.border = '';
                    box.style.background = '';
                });
            }, 1000);
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

            // تكبير/تصغير بـ +/-
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                adjustScale(currentScale + 0.1);
            }
            if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                adjustScale(Math.max(0.5, currentScale - 0.1));
            }
            // إعادة تعيين بـ 0
            if (e.key === '0') {
                e.preventDefault();
                adjustScale(1.0);
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
