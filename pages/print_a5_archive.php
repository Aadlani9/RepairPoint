<?php
/**
 * RepairPoint - Ticket A5 para Archivo
 * Optimizado para papel A5 (148mm × 210mm)
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
            line-height: 1.4;
            font-size: 10pt;
        }

        /* Configuración A5 Portrait */
        .document {
            width: 148mm;
            min-height: 210mm;
            margin: 0 auto;
            padding: 8mm;
            background: white;
        }

        /* Header principal */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 3px solid #000;
            padding: 5mm;
            margin-bottom: 4mm;
        }

        .header-left {
            flex: 1;
        }

        .shop-logo {
            max-width: 25mm;
            max-height: 25mm;
            margin-bottom: 2mm;
        }

        .shop-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2mm;
        }

        .shop-contact {
            font-size: 8pt;
            line-height: 1.3;
        }

        .header-right {
            text-align: center;
            border: 2px solid #000;
            padding: 3mm;
            min-width: 40mm;
        }

        .doc-type {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2mm;
        }

        .doc-number {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2mm;
        }

        .doc-date {
            font-size: 8pt;
        }

        .archive-notice {
            background: #000;
            color: white;
            padding: 2mm;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            margin-top: 2mm;
        }

        /* Información del cliente */
        .client-section {
            border: 2px solid #000;
            padding: 4mm;
            margin-bottom: 4mm;
        }

        .section-title {
            background: #000;
            color: white;
            padding: 2mm;
            font-weight: bold;
            font-size: 10pt;
            margin: -4mm -4mm 3mm -4mm;
            text-transform: uppercase;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .info-value {
            font-size: 10pt;
            padding: 1mm;
            border-bottom: 1px solid #000;
        }

        /* Dispositivo */
        .device-section {
            border: 2px solid #000;
            padding: 4mm;
            margin-bottom: 4mm;
            background: #f8f8f8;
        }

        .device-box {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            padding: 3mm;
            border: 2px dashed #000;
            background: white;
        }

        /* Problema */
        .problem-section {
            border: 2px solid #000;
            padding: 4mm;
            margin-bottom: 4mm;
        }

        .problem-text {
            min-height: 15mm;
            padding: 3mm;
            border: 1px dashed #000;
            background: white;
            font-size: 9pt;
        }

        /* Costes */
        .costs-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
            margin-bottom: 4mm;
        }

        .cost-box {
            border: 2px solid #000;
            padding: 3mm;
            text-align: center;
        }

        .cost-label {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .cost-value {
            font-size: 16pt;
            font-weight: bold;
        }

        /* Código de barras */
        .barcode-section {
            text-align: center;
            border: 2px dashed #000;
            padding: 3mm;
            margin-bottom: 4mm;
        }

        .barcode-svg {
            width: 80mm;
            height: auto;
        }

        /* CONDICIONES - LA PARTE MÁS IMPORTANTE */
        .terms-section {
            border: 4px double #000;
            padding: 4mm;
            margin-bottom: 4mm;
            background: #f9f9f9;
            page-break-inside: avoid;
        }

        .terms-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
            border-bottom: 2px solid #000;
        }

        .term-item {
            margin-bottom: 3mm;
            padding: 2mm;
            border-left: 3px solid #000;
            padding-left: 3mm;
        }

        .term-number {
            display: inline-block;
            background: #000;
            color: white;
            padding: 1mm 3mm;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2mm;
        }

        .term-title-text {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2mm;
        }

        .term-content {
            font-size: 9pt;
            line-height: 1.5;
            margin-left: 3mm;
        }

        .term-content ul {
            margin-top: 1mm;
            margin-left: 5mm;
        }

        .term-content li {
            margin-bottom: 1mm;
        }

        .warning-box {
            background: #000;
            color: white;
            padding: 3mm;
            margin: 3mm 0;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }

        /* Firma del cliente */
        .signature-section {
            border: 3px solid #000;
            padding: 4mm;
            margin-bottom: 4mm;
            background: #fff;
            page-break-inside: avoid;
        }

        .signature-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 3mm;
            text-transform: uppercase;
        }

        .acceptance-text {
            font-size: 9pt;
            line-height: 1.5;
            margin-bottom: 4mm;
            text-align: justify;
            padding: 2mm;
            background: #fffbcc;
            border: 1px solid #000;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 5mm;
            margin-top: 4mm;
        }

        .signature-box {
            border: 2px solid #000;
            padding: 3mm;
            min-height: 25mm;
        }

        .signature-label {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .signature-line {
            border-bottom: 2px solid #000;
            margin: 15mm 3mm 2mm 3mm;
        }

        .signature-sublabel {
            font-size: 7pt;
            text-align: center;
            color: #666;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 7pt;
            padding-top: 3mm;
            border-top: 1px solid #000;
            color: #666;
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
                display: none;
            }

            .document {
                margin: 0;
                padding: 8mm;
            }

            @page {
                size: A5 portrait;
                margin: 0;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .section-title,
            .archive-notice,
            .warning-box,
            .term-number {
                background: #000 !important;
                color: white !important;
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
        <!-- Header -->
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
                        <br>
                    <?php endif; ?>
                    <?php if (!empty($repair['email'])): ?>
                        📧 <?= htmlspecialchars($repair['email']) ?>
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
        <div class="client-section">
            <div class="section-title">📋 Datos del Cliente</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nombre Completo:</div>
                    <div class="info-value"><?= htmlspecialchars($repair['customer_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teléfono:</div>
                    <div class="info-value"><?= htmlspecialchars($repair['customer_phone']) ?></div>
                </div>
            </div>
        </div>

        <!-- Dispositivo -->
        <div class="device-section">
            <div class="section-title">📱 Dispositivo</div>
            <div class="device-box">
                <?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?>
            </div>
        </div>

        <!-- Problema -->
        <div class="problem-section">
            <div class="section-title">⚠️ Problema Reportado</div>
            <div class="problem-text"><?= nl2br(htmlspecialchars($repair['issue_description'])) ?></div>
        </div>

        <!-- Costes -->
        <?php if (!empty($repair['estimated_cost']) || !empty($repair['actual_cost'])): ?>
            <div class="costs-section">
                <?php if (!empty($repair['estimated_cost'])): ?>
                    <div class="cost-box">
                        <div class="cost-label">Coste Estimado</div>
                        <div class="cost-value">€<?= number_format($repair['estimated_cost'], 2) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($repair['actual_cost'])): ?>
                    <div class="cost-box" style="background: #f0f0f0;">
                        <div class="cost-label">Coste Final</div>
                        <div class="cost-value">€<?= number_format($repair['actual_cost'], 2) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Código de barras -->
        <div class="barcode-section">
            <svg id="barcode" class="barcode-svg"></svg>
        </div>

        <!-- TÉRMINOS Y CONDICIONES - LA SECCIÓN MÁS IMPORTANTE -->
        <div class="terms-section">
            <div class="terms-title">⚖️ TÉRMINOS Y CONDICIONES DEL SERVICIO ⚖️</div>

            <!-- Término 1 - CUSTODIA Y RECOGIDA (EL MÁS IMPORTANTE) -->
            <div class="term-item">
                <div class="term-number">1</div>
                <div class="term-title-text">CUSTODIA Y RECOGIDA DEL DISPOSITIVO</div>
                <div class="term-content">
                    <ul>
                        <li><strong>El dispositivo debe recogerse en un plazo máximo de 30 días naturales tras COMPLETAR la reparación.</strong></li>
                        <li><strong style="text-decoration: underline;">IMPORTANTE:</strong> Pasados 30 días desde la finalización de la reparación, el establecimiento NO se responsabiliza por pérdida, daños, robo o extravío del dispositivo.</li>
                        <li>Pasados 60 días sin recoger el dispositivo, el establecimiento se reserva el derecho de disponer del mismo sin previo aviso ni compensación.</li>
                        <li>El establecimiento conservará el dispositivo en condiciones normales, pero no se responsabiliza de daños causados por factores externos (humedad, temperatura, etc.).</li>
                    </ul>
                </div>
            </div>

            <div class="warning-box">
                ⚠️ ATENCIÓN: RECOGER EL DISPOSITIVO DENTRO DE 30 DÍAS TRAS LA REPARACIÓN ⚠️
            </div>

            <!-- Término 2 - TIEMPO DE REPARACIÓN -->
            <div class="term-item">
                <div class="term-number">2</div>
                <div class="term-title-text">TIEMPO DE REPARACIÓN</div>
                <div class="term-content">
                    <ul>
                        <li>El tiempo estimado de reparación es de 3 a 30 días naturales, dependiendo de la disponibilidad de piezas y complejidad de la avería.</li>
                        <li>El establecimiento notificará al cliente cuando la reparación esté completada.</li>
                        <li>Los plazos son estimados y pueden variar sin constituir incumplimiento contractual.</li>
                    </ul>
                </div>
            </div>

            <!-- Término 3 - ACCESORIOS -->
            <div class="term-item">
                <div class="term-number">3</div>
                <div class="term-title-text">ACCESORIOS NO REGISTRADOS</div>
                <div class="term-content">
                    <ul>
                        <li>Solo nos responsabilizamos de los accesorios expresamente listados en este documento.</li>
                        <li>Se recomienda retirar fundas, tarjetas SIM, tarjetas de memoria y otros accesorios antes de entregar el dispositivo.</li>
                        <li>El establecimiento no se responsabiliza de accesorios no declarados.</li>
                    </ul>
                </div>
            </div>

            <!-- Término 4 - GARANTÍA -->
            <div class="term-item">
                <div class="term-number">4</div>
                <div class="term-title-text">GARANTÍA DE REPARACIÓN</div>
                <div class="term-content">
                    <ul>
                        <li>La reparación incluye <?= $warranty_days ?> días de garantía sobre el trabajo realizado.</li>
                        <li><strong>Cualquier apertura, manipulación o reparación externa ANULA automáticamente toda garantía.</strong></li>
                        <li>Daños causados por líquidos, golpes o caídas posteriores a la reparación NO están cubiertos por la garantía.</li>
                        <li>La garantía solo cubre la reparación específica realizada, no otras averías del dispositivo.</li>
                    </ul>
                </div>
            </div>

            <!-- Término 5 - RECOGIDA -->
            <div class="term-item">
                <div class="term-number">5</div>
                <div class="term-title-text">PROCEDIMIENTO DE RECOGIDA</div>
                <div class="term-content">
                    <ul>
                        <li><strong>Es OBLIGATORIO presentar el ticket de reparación para recoger el dispositivo.</strong></li>
                        <li>Sin ticket, se requerirá documento de identidad (DNI/NIE) y verificación adicional.</li>
                        <li>Solo el titular o persona autorizada expresamente puede recoger el dispositivo.</li>
                    </ul>
                </div>
            </div>

            <!-- Término 6 - PRESUPUESTO -->
            <div class="term-item">
                <div class="term-number">6</div>
                <div class="term-title-text">INSPECCIÓN Y PRESUPUESTO</div>
                <div class="term-content">
                    <ul>
                        <li>Puede aplicarse una tarifa de inspección si el cliente rechaza el presupuesto de reparación.</li>
                        <li>El presupuesto aceptado es vinculante, salvo imprevistos técnicos que se comunicarán.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Firma del cliente -->
        <div class="signature-section">
            <div class="signature-title">✍️ Aceptación de Condiciones</div>

            <div class="acceptance-text">
                <strong>DECLARO que he leído, entendido y acepto todos los términos y condiciones descritos anteriormente.</strong>
                En particular, acepto que el dispositivo debe recogerse en un plazo máximo de 30 días tras completar la reparación,
                y que pasado este plazo el establecimiento NO se responsabiliza por pérdida, daños o extravío del mismo.
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
                    <div class="signature-sublabel">DD / MM / AAAA</div>
                </div>
            </div>

            <div style="margin-top: 4mm; padding: 2mm; background: #f0f0f0; border: 1px solid #000; font-size: 8pt;">
                <strong>Empleado:</strong> <?= htmlspecialchars($repair['created_by_name']) ?> |
                <strong>Fecha recepción:</strong> <?= formatDate($repair['received_at'], 'd/m/Y H:i') ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Documento generado el <?= date('d/m/Y H:i') ?> - Este documento tiene validez legal como prueba de entrega y aceptación de condiciones</div>
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
                    width: 2,
                    height: 50,
                    displayValue: true,
                    background: "#ffffff",
                    lineColor: "#000000",
                    margin: 5,
                    fontSize: 14,
                    textMargin: 5
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
