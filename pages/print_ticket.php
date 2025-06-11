<?php
/**
 * RepairPoint - Ticket POS Térmico Optimizado
 * Compatible con impresoras 58mm y 80mm
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

if (!$repair_id) {
    die('ID de reparación no válido');
}

// Obtener tamaño de papel (58mm o 80mm)
$paper_size = $_GET['size'] ?? '80mm';
if (!in_array($paper_size, ['58mm', '80mm'])) {
    $paper_size = '80mm';
}

// Obtener datos de la reparación
$db = getDB();
$repair = $db->selectOne(
    "SELECT r.*, b.name as brand_name, m.name as model_name, 
            u.name as created_by_name, s.*
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

// Log de actividad
logActivity('ticket_printed', "Ticket POS impreso para reparación #{$repair['reference']} ({$paper_size})", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket POS #<?= htmlspecialchars($repair['reference']) ?></title>

    <!-- JsBarcode para códigos de barras -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: white;
            color: #000;
            padding: 0;
            margin: 0;
        }

        /* Configuración para 80mm (por defecto) */
        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 11px;
            line-height: 1.3;
        }

        /* Configuración para 58mm */
        .ticket.size-58mm {
            width: 58mm;
            max-width: 58mm;
            padding: 3mm;
            font-size: 9px;
            line-height: 1.2;
        }

        /* Header del taller */
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 5mm;
            margin-bottom: 5mm;
        }

        .shop-logo {
            max-width: 20mm;
            max-height: 20mm;
            margin-bottom: 2mm;
        }

        .size-58mm .shop-logo {
            max-width: 15mm;
            max-height: 15mm;
        }

        .shop-name {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 2mm;
        }

        .size-58mm .shop-name {
            font-size: 12px;
        }

        .shop-contact {
            font-size: 9px;
            line-height: 1.2;
        }

        .size-58mm .shop-contact {
            font-size: 8px;
        }

        /* Título del ticket */
        .ticket-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin: 5mm 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2mm 0;
        }

        .size-58mm .ticket-title {
            font-size: 10px;
            margin: 3mm 0;
        }

        /* Referencia destacada */
        .reference {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 3mm 0;
            letter-spacing: 2px;
        }

        .size-58mm .reference {
            font-size: 14px;
            letter-spacing: 1px;
        }

        /* Código de barras */
        .barcode-section {
            text-align: center;
            margin: 5mm 0;
            border: 1px dashed #000;
            padding: 3mm;
        }

        .size-58mm .barcode-section {
            margin: 3mm 0;
            padding: 2mm;
        }

        .barcode-svg {
            max-width: 100%;
            height: auto;
        }

        .barcode-text {
            font-size: 8px;
            margin-top: 1mm;
            font-weight: bold;
        }

        .size-58mm .barcode-text {
            font-size: 7px;
        }

        /* Secciones de información */
        .section {
            margin: 4mm 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
        }

        .section:last-of-type {
            border-bottom: none;
        }

        .size-58mm .section {
            margin: 3mm 0;
            padding-bottom: 2mm;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2mm;
            text-decoration: underline;
        }

        .size-58mm .section-title {
            font-size: 9px;
            margin-bottom: 1mm;
        }

        /* Filas de información */
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            word-wrap: break-word;
        }

        .label {
            font-weight: bold;
            flex: 0 0 45%;
            text-align: left;
        }

        .value {
            flex: 0 0 50%;
            text-align: right;
            word-break: break-word;
        }

        /* Para 58mm, usar layout vertical en vez de horizontal */
        .size-58mm .row {
            display: block;
            margin-bottom: 2mm;
        }

        .size-58mm .label {
            display: block;
            margin-bottom: 0.5mm;
        }

        .size-58mm .value {
            display: block;
            text-align: left;
            margin-left: 5mm;
        }

        /* Estados con símbolos */
        .status-pending::before { content: "⏳ "; }
        .status-in_progress::before { content: "🔧 "; }
        .status-completed::before { content: "✅ "; }
        .status-delivered::before { content: "📦 "; }

        /* Prioridades con símbolos */
        .priority-high::before { content: "🔴 "; }
        .priority-medium::before { content: "🟡 "; }
        .priority-low::before { content: "🟢 "; }

        /* Problema destacado */
        .issue-box {
            border: 1px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            background: #f0f0f0;
            word-wrap: break-word;
            font-style: italic;
        }

        .size-58mm .issue-box {
            padding: 1mm;
            margin: 1mm 0;
        }

        /* Área de notas */
        .notes-area {
            border: 1px dashed #000;
            height: 15mm;
            margin: 3mm 0;
            position: relative;
        }

        .size-58mm .notes-area {
            height: 10mm;
            margin: 2mm 0;
        }

        .notes-title {
            position: absolute;
            top: 1mm;
            left: 1mm;
            font-size: 8px;
            font-weight: bold;
        }

        .notes-lines {
            position: absolute;
            top: 4mm;
            left: 1mm;
            right: 1mm;
            bottom: 1mm;
            background-image: repeating-linear-gradient(
                    transparent,
                    transparent 2.5mm,
                    #ccc 2.5mm,
                    #ccc 2.6mm
            );
        }

        /* Footer */
        .footer {
            text-align: center;
            border-top: 2px solid #000;
            padding-top: 3mm;
            margin-top: 5mm;
            font-size: 9px;
        }

        .size-58mm .footer {
            padding-top: 2mm;
            margin-top: 3mm;
            font-size: 8px;
        }

        .footer-thanks {
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .footer-info {
            font-size: 7px;
            margin-top: 2mm;
        }

        .size-58mm .footer-info {
            font-size: 6px;
        }

        /* Línea de corte */
        .cut-line {
            text-align: center;
            margin: 5mm 0;
            padding: 2mm 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-size: 10px;
            font-weight: bold;
        }

        .size-58mm .cut-line {
            margin: 3mm 0;
            font-size: 8px;
        }

        /* Sección del cliente (separable) */
        .customer-section {
            border: 2px dashed #000;
            margin-top: 3mm;
            padding: 3mm;
            background: repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 2px,
                    rgba(0,0,0,0.1) 2px,
                    rgba(0,0,0,0.1) 4px
            );
        }

        .size-58mm .customer-section {
            margin-top: 2mm;
            padding: 2mm;
        }

        .customer-header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }

        .size-58mm .customer-header {
            padding-bottom: 1mm;
            margin-bottom: 2mm;
        }

        .customer-title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        .size-58mm .customer-title {
            font-size: 9px;
        }

        .customer-ref {
            font-weight: bold;
            font-size: 14px;
            margin-top: 1mm;
            letter-spacing: 1px;
        }

        .size-58mm .customer-ref {
            font-size: 12px;
        }

        .customer-info {
            margin-bottom: 3mm;
        }

        .size-58mm .customer-info {
            margin-bottom: 2mm;
        }

        .customer-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            font-size: 9px;
        }

        .size-58mm .customer-row {
            display: block;
            margin-bottom: 1.5mm;
            font-size: 8px;
        }

        .customer-label {
            font-weight: bold;
            flex: 0 0 40%;
        }

        .customer-value {
            flex: 0 0 55%;
            text-align: right;
            word-break: break-word;
        }

        .size-58mm .customer-label {
            display: block;
            margin-bottom: 0.5mm;
        }

        .size-58mm .customer-value {
            display: block;
            text-align: left;
            margin-left: 3mm;
        }

        .customer-problem {
            border: 1px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            background: white;
        }

        .size-58mm .customer-problem {
            padding: 1mm;
            margin: 1mm 0;
        }

        .customer-problem-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .customer-problem-text {
            font-size: 8px;
            line-height: 1.2;
            font-style: italic;
        }

        .size-58mm .customer-problem-text {
            font-size: 7px;
        }

        .customer-barcode {
            text-align: center;
            margin: 3mm 0;
            border: 1px solid #000;
            padding: 2mm;
            background: white;
        }

        .size-58mm .customer-barcode {
            margin: 2mm 0;
            padding: 1mm;
        }

        .customer-barcode-svg {
            max-width: 100%;
            height: auto;
        }

        .customer-barcode-text {
            font-size: 8px;
            font-weight: bold;
            margin-top: 1mm;
            letter-spacing: 1px;
        }

        .size-58mm .customer-barcode-text {
            font-size: 7px;
        }

        .customer-contact {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 2mm;
            font-size: 8px;
        }

        .size-58mm .customer-contact {
            padding-top: 1mm;
            font-size: 7px;
        }

        .customer-shop-name {
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .customer-shop-phone {
            margin-bottom: 1mm;
        }

        .customer-note {
            font-style: italic;
            font-size: 7px;
            margin-top: 1mm;
        }

        .size-58mm .customer-note {
            font-size: 6px;
        }

        /* Botones de control (solo pantalla) */
        .controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            z-index: 1000;
        }

        .controls button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            margin: 2px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }

        .controls button:hover {
            background: #0056b3;
        }

        .controls button.active {
            background: #28a745;
        }

        /* Estilos de impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .controls {
                display: none;
            }

            .ticket {
                margin: 0;
                padding: 2mm;
            }

            /* Forzar tamaños específicos para impresión */
            @page {
                margin: 0;
                size: 80mm auto;
            }

            .size-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
            }
        }

        /* Media queries específicas para impresoras térmicas */
        @media print and (max-width: 58mm) {
            .ticket {
                width: 58mm !important;
                font-size: 9px !important;
            }
        }

        @media print and (max-width: 80mm) {
            .ticket {
                width: 80mm !important;
                font-size: 11px !important;
            }
        }

        /* Optimización para impresión térmica */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .barcode-section, .issue-box {
                background: white !important;
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>
<!-- Controles de tamaño (solo en pantalla) -->
<div class="controls">
    <div style="margin-bottom: 8px; font-weight: bold;">Tamaño de Papel:</div>
    <button onclick="changePaperSize('58mm')" id="btn-58mm">58mm</button>
    <button onclick="changePaperSize('80mm')" id="btn-80mm" class="active">80mm</button>
    <br><br>
    <button onclick="window.print()" style="background: #28a745;">🖨️ Imprimir</button>
    <button onclick="window.close()" style="background: #dc3545;">❌ Cerrar</button>
</div>

<div class="ticket size-<?= $paper_size ?>" id="ticket">
    <!-- Header del taller -->
    <div class="header">
        <?php if ($repair['logo']): ?>
            <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" alt="Logo" class="shop-logo">
        <?php endif; ?>

        <div class="shop-name"><?= htmlspecialchars($repair['name']) ?></div>

        <div class="shop-contact">
            <?php if ($repair['address']): ?>
                <?= htmlspecialchars($repair['address']) ?><br>
            <?php endif; ?>

            Tel: <?= htmlspecialchars($repair['phone1']) ?>

            <?php if ($repair['phone2']): ?>
                / <?= htmlspecialchars($repair['phone2']) ?>
            <?php endif; ?>

            <?php if ($repair['email']): ?>
                <br><?= htmlspecialchars($repair['email']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Título -->
    <div class="ticket-title">*** TICKET REPARACION ***</div>

    <!-- Referencia -->
    <div class="reference">#<?= htmlspecialchars($repair['reference']) ?></div>

    <!-- Código de barras -->
    <div class="barcode-section">
        <svg id="barcode" class="barcode-svg"></svg>
        <div class="barcode-text">ID: <?= $repair['id'] ?> | <?= htmlspecialchars($repair['reference']) ?></div>
    </div>

    <!-- Cliente -->
    <div class="section">
        <div class="section-title">CLIENTE</div>
        <div class="row">
            <span class="label">Nombre:</span>
            <span class="value" style="color: black; font-weight: bold;">
        <?= htmlspecialchars($repair['customer_name']) ?>
    </span>
        </div>
        <div class="row">
            <span class="label">Telefono:</span>
            <span class="value" style="color: black; font-weight: bold;"><?= htmlspecialchars($repair['customer_phone']) ?></span>
        </div>
    </div>

    <!-- Dispositivo -->
    <div class="section">
        <div class="section-title">DISPOSITIVO</div>
        <div class="row">
            <span class="label">Marca:</span>
            <span class="value"><?= htmlspecialchars($repair['brand_name']) ?></span>
        </div>
        <div class="row">
            <span class="label">Modelo:</span>
            <span class="value"><?= htmlspecialchars($repair['model_name']) ?></span>
        </div>
    </div>

    <!-- Problema -->
    <div class="section">
        <div class="section-title">PROBLEMA REPORTADO</div>
        <div class="issue-box">
            <?= nl2br(htmlspecialchars($repair['issue_description'])) ?>
        </div>
    </div>

    <!-- Fechas -->
    <div class="section">
        <div class="section-title">FECHAS</div>
        <div class="row">
            <span class="label">Recibido:</span>
            <span class="value"><?= formatDate($repair['received_at'], 'd/m/Y H:i') ?></span>
        </div>

        <?php if ($repair['estimated_completion']): ?>
            <div class="row">
                <span class="label">Est. Entrega:</span>
                <span class="value"><?= formatDate($repair['estimated_completion'], 'd/m/Y') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($repair['completed_at']): ?>
            <div class="row">
                <span class="label">Completado:</span>
                <span class="value"><?= formatDate($repair['completed_at'], 'd/m/Y H:i') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($repair['delivered_at']): ?>
            <div class="row">
                <span class="label">Entregado:</span>
                <span class="value"><?= formatDate($repair['delivered_at'], 'd/m/Y H:i') ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Costes -->
    <?php if ($repair['estimated_cost'] || $repair['actual_cost']): ?>
        <div class="section">
            <div class="section-title">COSTES</div>

            <?php if ($repair['estimated_cost']): ?>
                <div class="row">
                    <span class="label">Estimado:</span>
                    <span class="value">€<?= number_format($repair['estimated_cost'], 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($repair['actual_cost']): ?>
                <div class="row">
                    <span class="label">FINAL:</span>
                    <span class="value"><strong>€<?= number_format($repair['actual_cost'], 2) ?></strong></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Área de notas -->
    <div class="section">
        <div class="section-title">NOTAS ADICIONALES</div>
        <div class="notes-area">
            <div class="notes-title">Escribir aqui:</div>
            <div class="notes-lines"></div>
        </div>
    </div>

    <!-- Info técnica -->
    <div class="section">
        <div class="section-title">INFO TECNICA</div>
        <div class="row">
            <span class="label">Tecnico:</span>
            <span class="value"><?= htmlspecialchars($repair['created_by_name']) ?></span>
        </div>
        <div class="row">
            <span class="label">Impreso:</span>
            <span class="value"><?= date('d/m/Y H:i') ?></span>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-thanks">
            *** GRACIAS POR SU CONFIANZA ***
        </div>
        <div>
            Conserve este ticket para recoger<br>
            el dispositivo reparado.
        </div>
        <div class="footer-info">
            <?= APP_NAME ?> | <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <!-- Línea de corte -->
    <div class="cut-line">
        <span>✂️ ✂️ ✂️ ✂️ ✂️ ✂️ ✂️ ✂️ ✂️ ✂️ ✂️</span>
    </div>

    <!-- Sección para el cliente (separable) -->
    <div class="customer-section">
        <div class="customer-header">
            <div class="customer-title">*** COMPROBANTE CLIENTE ***</div>
            <div class="customer-ref">#<?= htmlspecialchars($repair['reference']) ?></div>
        </div>

        <!-- Info básica del cliente -->
        <div class="customer-info">
            <div class="customer-row">
                <span class="customer-label">Cliente:</span>
                <span class="customer-value"><?= htmlspecialchars($repair['customer_name']) ?></span>
            </div>
            <div class="customer-row">
                <span class="customer-label">Telefono:</span>
                <span class="customer-value"><?= htmlspecialchars($repair['customer_phone']) ?></span>
            </div>
            <div class="customer-row">
                <span class="customer-label">Fecha:</span>
                <span class="customer-value"><?= formatDate($repair['received_at'], 'd/m/Y') ?></span>
            </div>
            <div class="customer-row">
                <span class="customer-label">Dispositivo:</span>
                <span class="customer-value"><?= htmlspecialchars($repair['brand_name'] . ' ' . $repair['model_name']) ?></span>
            </div>
        </div>

        <!-- Problema resumido -->
        <div class="customer-problem">
            <div class="customer-problem-title">Problema:</div>
            <div class="customer-problem-text">
                <?= htmlspecialchars(substr($repair['issue_description'], 0, 100)) ?><?= strlen($repair['issue_description']) > 100 ? '...' : '' ?>
            </div>
        </div>

        <!-- Código de barras para el cliente -->
        <div class="customer-barcode">
            <svg id="customer-barcode" class="customer-barcode-svg"></svg>
            <div class="customer-barcode-text"><?= htmlspecialchars($repair['reference']) ?></div>
        </div>

        <!-- Info de contacto -->
        <div class="customer-contact">
            <div class="customer-shop-name"><?= htmlspecialchars($repair['name']) ?></div>
            <div class="customer-shop-phone">Tel: <?= htmlspecialchars($repair['phone1']) ?></div>
            <div class="customer-note">Presente este comprobante para recoger</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer tamaño inicial
        const urlParams = new URLSearchParams(window.location.search);
        const size = urlParams.get('size') || '80mm';
        changePaperSize(size);

        // Generar código de barras
        generateBarcode();
    });

    function changePaperSize(size) {
        const ticket = document.getElementById('ticket');
        const buttons = document.querySelectorAll('.controls button');

        // Remover clases de tamaño
        ticket.classList.remove('size-58mm', 'size-80mm');

        // Agregar nueva clase
        ticket.classList.add('size-' + size);

        // Actualizar botones activos
        buttons.forEach(btn => btn.classList.remove('active'));
        document.getElementById('btn-' + size).classList.add('active');

        // Regenerar código de barras con nuevo tamaño
        setTimeout(() => {
            generateBarcode();
        }, 100);

        console.log('Cambiado a tamaño:', size);
    }

    function generateBarcode() {
        try {
            const barcodeData = '<?= $repair['id'] . $repair['reference'] ?>';
            const isSmall = document.getElementById('ticket').classList.contains('size-58mm');

            // Código de barras principal
            JsBarcode("#barcode", barcodeData, {
                format: "CODE128",
                width: isSmall ? 1 : 1.5,
                height: isSmall ? 25 : 35,
                displayValue: false,
                background: "#ffffff",
                lineColor: "#000000",
                margin: 1
            });

            // Código de barras del cliente (más pequeño)
            JsBarcode("#customer-barcode", barcodeData, {
                format: "CODE128",
                width: isSmall ? 0.8 : 1,
                height: isSmall ? 20 : 25,
                displayValue: false,
                background: "#ffffff",
                lineColor: "#000000",
                margin: 1
            });

            console.log('Códigos de barras generados:', barcodeData);
        } catch (error) {
            console.error('Error generando códigos de barras:', error);
            document.querySelector('.barcode-section').innerHTML =
                '<div style="text-align: center; font-weight: bold;">Error en código</div>';
            document.querySelector('.customer-barcode').innerHTML =
                '<div style="text-align: center; font-weight: bold;">Error en código</div>';
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

        // Cambiar tamaños con teclas
        if (e.key === '1') {
            changePaperSize('58mm');
        }
        if (e.key === '2') {
            changePaperSize('80mm');
        }
    });

    // Auto-cerrar después de imprimir
    window.addEventListener('afterprint', function() {
        setTimeout(() => {
            if (confirm('¿Cerrar ventana?')) {
                window.close();
            }
        }, 1000);
    });

    console.log('Ticket POS cargado - Tamaño: <?= $paper_size ?>');
</script>
</body>
</html>