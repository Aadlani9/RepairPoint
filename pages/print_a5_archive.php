<?php
/**
 * RepairPoint - Ticket A5 para Archivo
 * Optimizado para papel A5 (148mm × 210mm) - PÁGINA ÚNICA
 * Para ARCHIVO DEL ESTABLECIMIENTO - Con condiciones completas y firma
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

// Calcular garantía
$warranty_days = $repair['warranty_days'] ?? 30;

// Log de actividad
logActivity('ticket_printed', "Ticket A5 archivo impreso para reparación #{$repair['reference']}", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo Archivo A5 - #<?= htmlspecialchars($repair['reference']) ?></title>

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
            font-family: Arial, sans-serif;
            background: white;
            color: #000;
            line-height: 1.2;
            font-size: 8pt;
        }

        /* Configuración A5 Portrait - OPTIMIZADO PARA UNA PÁGINA */
        .document {
            width: 148mm;
            height: 210mm;
            margin: 0 auto;
            padding: 5mm;
            background: white;
            overflow: hidden;
        }

        /* Header compacto */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
        }

        .header-left {
            flex: 1;
        }

        .shop-logo {
            max-width: 15mm;
            max-height: 15mm;
            margin-bottom: 1mm;
        }

        .shop-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .shop-contact {
            font-size: 6pt;
            line-height: 1.2;
        }

        .header-right {
            text-align: center;
            border: 2px solid #000;
            padding: 2mm;
            min-width: 35mm;
        }

        .doc-type {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .doc-number {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 1mm;
        }

        .doc-date {
            font-size: 6pt;
        }

        .archive-notice {
            background: #000;
            color: white;
            padding: 1mm;
            text-align: center;
            font-weight: bold;
            font-size: 6pt;
            margin-top: 1mm;
        }

        /* Secciones compactas */
        .section {
            border: 1px solid #000;
            margin-bottom: 2mm;
            page-break-inside: avoid;
        }

        .section-title {
            background: #000;
            color: white;
            padding: 1mm 2mm;
            font-weight: bold;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .section-content {
            padding: 2mm;
        }

        /* Grid de información */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            font-size: 6pt;
            color: #666;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 7pt;
            padding: 0.5mm;
            border-bottom: 1px solid #000;
        }

        /* Dispositivo y problema en línea */
        .compact-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
            margin-bottom: 2mm;
        }

        .device-box {
            border: 1px solid #000;
            padding: 2mm;
            text-align: center;
        }

        .device-label {
            font-size: 6pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .device-name {
            font-size: 8pt;
            font-weight: bold;
        }

        .cost-box {
            border: 2px solid #000;
            padding: 2mm;
            text-align: center;
            background: #f0f0f0;
        }

        .cost-label {
            font-size: 6pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .cost-value {
            font-size: 12pt;
            font-weight: bold;
        }

        /* Problema */
        .problem-box {
            border: 1px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
        }

        .problem-label {
            font-size: 6pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .problem-text {
            font-size: 7pt;
            max-height: 12mm;
            overflow: hidden;
        }

        /* Código de barras compacto */
        .barcode-section {
            text-align: center;
            border: 1px dashed #000;
            padding: 1mm;
            margin-bottom: 2mm;
        }

        .barcode-svg {
            width: 60mm;
            height: auto;
        }

        /* CONDICIONES - Muy compactas */
        .terms-section {
            border: 2px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
            background: #f9f9f9;
            page-break-inside: avoid;
        }

        .terms-title {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #000;
        }

        .terms-compact {
            font-size: 6pt;
            line-height: 1.3;
        }

        .term-item {
            margin-bottom: 1.5mm;
            padding-left: 2mm;
        }

        .term-number {
            display: inline-block;
            background: #000;
            color: white;
            padding: 0.5mm 1.5mm;
            font-weight: bold;
            font-size: 6pt;
            margin-right: 1mm;
        }

        .term-title-text {
            font-weight: bold;
            font-size: 6pt;
        }

        .term-content {
            font-size: 6pt;
            line-height: 1.3;
            margin-left: 8mm;
            margin-top: 0.5mm;
        }

        .warning-box {
            background: #000;
            color: white;
            padding: 1mm;
            margin: 1mm 0;
            text-align: center;
            font-weight: bold;
            font-size: 6pt;
        }

        /* Firma compacta */
        .signature-section {
            border: 2px solid #000;
            padding: 2mm;
            background: #fff;
            page-break-inside: avoid;
        }

        .signature-title {
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .acceptance-text {
            font-size: 6pt;
            line-height: 1.3;
            margin-bottom: 2mm;
            text-align: justify;
            padding: 1mm;
            background: #fffbcc;
            border: 1px solid #000;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3mm;
            margin-top: 2mm;
        }

        .signature-box {
            border: 1px solid #000;
            padding: 2mm;
            min-height: 15mm;
        }

        .signature-label {
            font-size: 6pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin: 10mm 2mm 1mm 2mm;
        }

        .signature-sublabel {
            font-size: 5pt;
            text-align: center;
            color: #666;
        }

        .employee-info {
            margin-top: 2mm;
            padding: 1mm;
            background: #f0f0f0;
            border: 1px solid #000;
            font-size: 6pt;
        }

        /* Footer mínimo */
        .footer {
            text-align: center;
            font-size: 5pt;
            padding-top: 1mm;
            border-top: 1px solid #000;
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
            padding: 12px;
            border-radius: 6px;
            z-index: 1000;
        }

        .print-controls button {
            background: #333;
            color: white;
            border: none;
            padding: 10px 18px;
            margin: 3px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .print-controls button:hover {
            background: #555;
        }

        .print-controls button.primary {
            background: #667eea;
        }

        /* Print styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .print-controls {
                display: none !important;
            }

            .document {
                margin: 0;
                padding: 5mm;
                page-break-after: avoid;
            }

            @page {
                size: A5 portrait;
                margin: 0;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .section-title,
            .archive-notice,
            .warning-box,
            .term-number {
                background: #000 !important;
                color: white !important;
            }

            /* Forzar una sola página */
            .document {
                page-break-inside: avoid;
                page-break-after: avoid;
            }
        }

        /* Vista previa en pantalla */
        @media screen {
            body {
                background: #e0e0e0;
                padding: 20px;
            }

            .document {
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            }
        }
    </style>
</head>
<body>
    <!-- Controles de impresión -->
    <div class="print-controls">
        <button onclick="window.print()" class="primary">🖨️ Imprimir</button>
        <button onclick="window.close()">❌ Cerrar</button>
    </div>

    <div class="document">
        <!-- Header compacto -->
        <div class="header">
            <div class="header-left">
                <?php if (!empty($repair['logo'])): ?>
                    <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" alt="Logo" class="shop-logo">
                <?php endif; ?>
                <div class="shop-name"><?= htmlspecialchars($repair['name']) ?></div>
                <div class="shop-contact">
                    <?php if (!empty($repair['address'])): ?>
                        📍 <?= htmlspecialchars($repair['address']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($repair['phone1'])): ?>
                        📞 <?= htmlspecialchars($repair['phone1']) ?>
                        <?php if (!empty($repair['phone2'])): ?>
                            / <?= htmlspecialchars($repair['phone2']) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($repair['email'])): ?>
                        <br>📧 <?= htmlspecialchars($repair['email']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="header-right">
                <div class="doc-type">REPARACIÓN</div>
                <div class="doc-number">#<?= htmlspecialchars($repair['reference']) ?></div>
                <div class="doc-date"><?= formatDate($repair['received_at'], 'd/m/Y') ?></div>
                <div class="archive-notice">COPIA ARCHIVO</div>
            </div>
        </div>

        <!-- Información del cliente -->
        <div class="section">
            <div class="section-title">📋 Cliente</div>
            <div class="section-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nombre:</div>
                        <div class="info-value"><?= htmlspecialchars($repair['customer_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Teléfono:</div>
                        <div class="info-value"><?= htmlspecialchars($repair['customer_phone']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dispositivo y Coste en una fila -->
        <div class="compact-row">
            <div class="device-box">
                <div class="device-label">📱 DISPOSITIVO:</div>
                <div class="device-name">
                    <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
                </div>
            </div>

            <?php if (!empty($repair['estimated_cost']) || !empty($repair['actual_cost'])): ?>
                <div class="cost-box">
                    <div class="cost-label">💰 COSTE:</div>
                    <div class="cost-value">
                        €<?= number_format($repair['actual_cost'] ?? $repair['estimated_cost'], 2) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Problema -->
        <div class="problem-box">
            <div class="problem-label">⚠️ PROBLEMA:</div>
            <div class="problem-text"><?= nl2br(htmlspecialchars($repair['issue_description'])) ?></div>
        </div>

        <!-- Código de barras -->
        <div class="barcode-section">
            <svg id="barcode" class="barcode-svg"></svg>
        </div>

        <!-- TÉRMINOS Y CONDICIONES - MUY COMPACTOS -->
        <div class="terms-section">
            <div class="terms-title">⚖️ TÉRMINOS Y CONDICIONES</div>

            <div class="terms-compact">
                <!-- Término 1 - El más importante -->
                <div class="term-item">
                    <span class="term-number">1</span>
                    <span class="term-title-text">CUSTODIA Y RECOGIDA</span>
                    <div class="term-content">
                        • Recoger en <strong>30 días máx tras completar reparación</strong>. Pasados 30 días, NO nos responsabilizamos por pérdida/daños. Pasados 60 días, el establecimiento puede disponer del dispositivo.
                    </div>
                </div>

                <div class="warning-box">
                    ⚠️ RECOGER EN 30 DÍAS TRAS REPARACIÓN - DESPUÉS NO HAY RESPONSABILIDAD
                </div>

                <!-- Término 2 -->
                <div class="term-item">
                    <span class="term-number">2</span>
                    <span class="term-title-text">TIEMPO REPARACIÓN</span>
                    <div class="term-content">
                        • 3-30 días según disponibilidad de piezas y complejidad.
                    </div>
                </div>

                <!-- Término 3 -->
                <div class="term-item">
                    <span class="term-number">3</span>
                    <span class="term-title-text">ACCESORIOS</span>
                    <div class="term-content">
                        • Solo responsables de accesorios registrados en este documento.
                    </div>
                </div>

                <!-- Término 4 -->
                <div class="term-item">
                    <span class="term-number">4</span>
                    <span class="term-title-text">GARANTÍA</span>
                    <div class="term-content">
                        • <?= $warranty_days ?> días. Apertura externa ANULA garantía. Daños líquidos/golpes NO cubiertos.
                    </div>
                </div>

                <!-- Término 5 -->
                <div class="term-item">
                    <span class="term-number">5</span>
                    <span class="term-title-text">RECOGIDA</span>
                    <div class="term-content">
                        • OBLIGATORIO presentar ticket. Sin ticket: DNI + verificación.
                    </div>
                </div>

                <!-- Término 6 -->
                <div class="term-item">
                    <span class="term-number">6</span>
                    <span class="term-title-text">PRESUPUESTO</span>
                    <div class="term-content">
                        • Posible tarifa inspección si se rechaza presupuesto.
                    </div>
                </div>
            </div>
        </div>

        <!-- Firma -->
        <div class="signature-section">
            <div class="signature-title">✍️ Aceptación de Condiciones</div>

            <div class="acceptance-text">
                <strong>DECLARO</strong> que he leído y acepto todos los términos, especialmente que el dispositivo debe recogerse en 30 días tras la reparación, y que pasado este plazo el establecimiento NO se responsabiliza por pérdida o daños.
            </div>

            <div class="signature-grid">
                <div class="signature-box">
                    <div class="signature-label">FIRMA DEL CLIENTE:</div>
                    <div class="signature-line"></div>
                    <div class="signature-sublabel">Firma y DNI</div>
                </div>

                <div class="signature-box">
                    <div class="signature-label">FECHA:</div>
                    <div class="signature-line"></div>
                    <div class="signature-sublabel">DD/MM/AAAA</div>
                </div>
            </div>

            <div class="employee-info">
                <strong>Empleado:</strong> <?= htmlspecialchars($repair['created_by_name']) ?> |
                <strong>Recepción:</strong> <?= formatDate($repair['received_at'], 'd/m/Y H:i') ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Documento generado el <?= date('d/m/Y H:i') ?> - Validez legal como prueba de entrega y aceptación
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateBarcode();

            <?php if ($auto_print): ?>
            // Auto-imprimir si está activado
            setTimeout(function() {
                window.print();
            }, 1500);
            <?php endif; ?>
        });

        function generateBarcode() {
            try {
                const barcodeData = '<?= $repair['reference'] ?>';

                JsBarcode("#barcode", barcodeData, {
                    format: "CODE128",
                    width: 1.5,
                    height: 30,
                    displayValue: true,
                    background: "#ffffff",
                    lineColor: "#000000",
                    margin: 2,
                    fontSize: 10,
                    textMargin: 2
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
