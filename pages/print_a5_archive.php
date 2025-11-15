<?php
/**
 * RepairPoint - Ticket A5 para Archivo - DISEÑO OPTIMIZADO
 * UNA SOLA PÁGINA - 148mm × 210mm
 * Diseño ultra-compacto con toda la información necesaria
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
    <title>Archivo A5 - #<?= htmlspecialchars($repair['reference']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.1;
            color: #000;
            background: white;
        }

        /* PÁGINA A5 - UNA SOLA PÁGINA */
        .page {
            width: 148mm;
            height: 210mm;
            margin: 0 auto;
            padding: 4mm;
            background: white;
            position: relative;
        }

        /* HEADER COMPACTO - 15mm */
        .header {
            display: grid;
            grid-template-columns: 1fr 35mm;
            gap: 2mm;
            border: 2px solid #000;
            padding: 1.5mm;
            margin-bottom: 2mm;
            height: 15mm;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2mm;
        }

        .logo {
            width: 10mm;
            height: 10mm;
            object-fit: contain;
        }

        .shop-info {
            font-size: 6pt;
            line-height: 1.2;
        }

        .shop-name {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .header-right {
            text-align: center;
            border: 2px solid #000;
            padding: 1mm;
            background: #f5f5f5;
        }

        .doc-title {
            font-size: 7pt;
            font-weight: bold;
        }

        .doc-ref {
            font-size: 11pt;
            font-weight: bold;
            margin: 0.5mm 0;
            letter-spacing: 0.5px;
        }

        .doc-date {
            font-size: 5pt;
        }

        /* INFORMACIÓN DE REPARACIÓN - 35mm */
        .repair-info {
            border: 1px solid #000;
            margin-bottom: 2mm;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .info-cell {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1mm;
            min-height: 7mm;
        }

        .info-cell:nth-child(2n) {
            border-right: none;
        }

        .info-cell:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .info-label {
            font-size: 5pt;
            color: #666;
            font-weight: bold;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0.5mm;
        }

        .info-value {
            font-size: 7pt;
            font-weight: bold;
        }

        .problem-cell {
            grid-column: 1 / -1;
            min-height: 12mm;
            max-height: 12mm;
            overflow: hidden;
        }

        .problem-text {
            font-size: 6pt;
            line-height: 1.2;
        }

        /* BARCODE COMPACTO - 25mm */
        .barcode-box {
            text-align: center;
            border: 1px dashed #000;
            padding: 1mm;
            margin-bottom: 2mm;
            height: 25mm;
        }

        .barcode-svg {
            width: 50mm;
            height: 18mm;
        }

        /* TÉRMINOS Y CONDICIONES - 90mm */
        .terms {
            border: 2px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
            background: #fafafa;
        }

        .terms-title {
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1.5mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
        }

        .term-item {
            margin-bottom: 1.5mm;
            font-size: 6pt;
            line-height: 1.3;
        }

        .term-main {
            background: #000;
            color: white;
            padding: 1.5mm;
            margin-bottom: 1mm;
            font-weight: bold;
            border-radius: 1mm;
        }

        .term-number {
            display: inline-block;
            background: #000;
            color: white;
            padding: 0.3mm 1.5mm;
            margin-right: 1mm;
            font-weight: bold;
            border-radius: 0.5mm;
        }

        .term-title {
            font-weight: bold;
        }

        .term-desc {
            margin-left: 6mm;
            margin-top: 0.3mm;
        }

        .warning-box {
            background: #000;
            color: white;
            text-align: center;
            padding: 1mm;
            margin: 1.5mm 0;
            font-weight: bold;
            font-size: 6pt;
        }

        /* FIRMA - 35mm */
        .signature {
            border: 2px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
        }

        .sig-title {
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1mm;
        }

        .sig-declaration {
            font-size: 5.5pt;
            line-height: 1.2;
            padding: 1mm;
            background: #fffbcc;
            border: 1px solid #000;
            margin-bottom: 2mm;
            text-align: justify;
        }

        .sig-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3mm;
            margin-bottom: 1.5mm;
        }

        .sig-box {
            border: 1px solid #000;
            padding: 1.5mm;
            height: 14mm;
            position: relative;
        }

        .sig-label {
            font-size: 5pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .sig-line {
            position: absolute;
            bottom: 3mm;
            left: 2mm;
            right: 2mm;
            border-bottom: 1px solid #000;
        }

        .sig-sublabel {
            position: absolute;
            bottom: 1mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 4.5pt;
            color: #666;
        }

        .employee-bar {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 1mm;
            font-size: 5.5pt;
            text-align: center;
        }

        /* FOOTER - 5mm */
        .footer {
            position: absolute;
            bottom: 4mm;
            left: 4mm;
            right: 4mm;
            text-align: center;
            font-size: 4.5pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 0.5mm;
        }

        /* Controles */
        .controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 10px;
            border-radius: 5px;
            z-index: 9999;
        }

        .controls button {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 15px;
            margin: 2px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }

        .controls button:hover {
            background: #5568d3;
        }

        /* PRINT STYLES */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .controls {
                display: none !important;
            }

            .page {
                margin: 0;
                padding: 4mm;
                box-shadow: none;
            }

            @page {
                size: A5 portrait;
                margin: 0;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .header-right,
            .term-main,
            .warning-box,
            .term-number {
                background: #000 !important;
                color: white !important;
            }

            .sig-declaration {
                background: #fffbcc !important;
            }

            .terms {
                background: #fafafa !important;
            }

            /* Forzar una sola página */
            .page {
                page-break-after: avoid;
                page-break-inside: avoid;
            }
        }

        @media screen {
            body {
                background: #e0e0e0;
                padding: 20px;
            }

            .page {
                box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            }
        }
    </style>
</head>
<body>
    <!-- Controles -->
    <div class="controls">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.close()">❌ Cerrar</button>
    </div>

    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <?php if (!empty($repair['logo'])): ?>
                    <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" class="logo" alt="Logo">
                <?php endif; ?>
                <div class="shop-info">
                    <div class="shop-name"><?= htmlspecialchars($repair['name']) ?></div>
                    <?php if (!empty($repair['phone1'])): ?>
                        Tel: <?= htmlspecialchars($repair['phone1']) ?>
                    <?php endif; ?>
                    <?php if (!empty($repair['email'])): ?>
                        <br><?= htmlspecialchars($repair['email']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-right">
                <div class="doc-title">REPARACIÓN</div>
                <div class="doc-ref">#<?= htmlspecialchars($repair['reference']) ?></div>
                <div class="doc-date"><?= formatDate($repair['received_at'], 'd/m/Y') ?></div>
            </div>
        </div>

        <!-- INFORMACIÓN DE REPARACIÓN -->
        <div class="repair-info">
            <div class="info-grid">
                <div class="info-cell">
                    <span class="info-label">Cliente:</span>
                    <div class="info-value"><?= htmlspecialchars($repair['customer_name']) ?></div>
                </div>
                <div class="info-cell">
                    <span class="info-label">Teléfono:</span>
                    <div class="info-value"><?= htmlspecialchars($repair['customer_phone']) ?></div>
                </div>
                <div class="info-cell">
                    <span class="info-label">Dispositivo:</span>
                    <div class="info-value"><?= htmlspecialchars($repair['brand_name']) ?> <?= htmlspecialchars($repair['model_name']) ?></div>
                </div>
                <div class="info-cell">
                    <span class="info-label">Coste:</span>
                    <div class="info-value">€<?= number_format($repair['actual_cost'] ?? $repair['estimated_cost'] ?? 0, 2) ?></div>
                </div>
                <div class="info-cell problem-cell">
                    <span class="info-label">Problema:</span>
                    <div class="problem-text"><?= htmlspecialchars(substr($repair['issue_description'], 0, 180)) ?><?= strlen($repair['issue_description']) > 180 ? '...' : '' ?></div>
                </div>
            </div>
        </div>

        <!-- BARCODE -->
        <div class="barcode-box">
            <svg id="barcode" class="barcode-svg"></svg>
        </div>

        <!-- TÉRMINOS Y CONDICIONES -->
        <div class="terms">
            <div class="terms-title">⚖️ Términos y Condiciones</div>

            <!-- Término principal -->
            <div class="term-main">
                ★ 1. CUSTODIA Y RECOGIDA: Dispositivo debe recogerse en 30 días máximo tras completar reparación. Pasados 30 días, NO nos responsabilizamos por pérdida, daño o extravío. Pasados 60 días, podemos disponer del dispositivo sin previo aviso.
            </div>

            <div class="warning-box">
                ⚠️ IMPORTANTE: RECOGER EN 30 DÍAS TRAS REPARACIÓN ⚠️
            </div>

            <!-- Resto de términos -->
            <div class="term-item">
                <span class="term-number">2</span>
                <span class="term-title">TIEMPO REPARACIÓN:</span>
                <div class="term-desc">3-30 días según disponibilidad de piezas y complejidad.</div>
            </div>

            <div class="term-item">
                <span class="term-number">3</span>
                <span class="term-title">ACCESORIOS:</span>
                <div class="term-desc">Solo responsables de accesorios registrados en este documento.</div>
            </div>

            <div class="term-item">
                <span class="term-number">4</span>
                <span class="term-title">GARANTÍA:</span>
                <div class="term-desc"><?= $warranty_days ?> días. Apertura/manipulación externa ANULA garantía. Daños líquidos/golpes NO cubiertos.</div>
            </div>

            <div class="term-item">
                <span class="term-number">5</span>
                <span class="term-title">RECOGIDA:</span>
                <div class="term-desc">OBLIGATORIO presentar ticket. Sin ticket: DNI + verificación.</div>
            </div>

            <div class="term-item">
                <span class="term-number">6</span>
                <span class="term-title">PRESUPUESTO:</span>
                <div class="term-desc">Posible tarifa inspección si se rechaza presupuesto.</div>
            </div>
        </div>

        <!-- FIRMA -->
        <div class="signature">
            <div class="sig-title">✍️ Aceptación de Condiciones</div>

            <div class="sig-declaration">
                <strong>DECLARO</strong> haber leído y aceptar todos los términos, especialmente la recogida en 30 días máximo tras reparación, y que pasado este plazo el establecimiento NO se responsabiliza por pérdida o daños.
            </div>

            <div class="sig-grid">
                <div class="sig-box">
                    <div class="sig-label">FIRMA DEL CLIENTE:</div>
                    <div class="sig-line"></div>
                    <div class="sig-sublabel">Firma y DNI</div>
                </div>
                <div class="sig-box">
                    <div class="sig-label">FECHA:</div>
                    <div class="sig-line"></div>
                    <div class="sig-sublabel">DD/MM/AAAA</div>
                </div>
            </div>

            <div class="employee-bar">
                <strong>Empleado:</strong> <?= htmlspecialchars($repair['created_by_name']) ?> |
                <strong>Recepción:</strong> <?= formatDate($repair['received_at'], 'd/m/Y H:i') ?>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            Documento generado <?= date('d/m/Y H:i') ?> - Validez legal como prueba de entrega y aceptación de condiciones
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateBarcode();

            <?php if ($auto_print): ?>
            setTimeout(() => window.print(), 1500);
            <?php endif; ?>
        });

        function generateBarcode() {
            try {
                JsBarcode("#barcode", '<?= $repair['reference'] ?>', {
                    format: "CODE128",
                    width: 1.3,
                    height: 18,
                    displayValue: true,
                    background: "#ffffff",
                    lineColor: "#000000",
                    margin: 0,
                    fontSize: 9,
                    textMargin: 1
                });
            } catch (error) {
                console.error('Error barcode:', error);
            }
        }

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });

        window.addEventListener('afterprint', function() {
            setTimeout(() => {
                if (confirm('¿Cerrar ventana?')) {
                    window.close();
                }
            }, 1000);
        });
    </script>
</body>
</html>
