<?php
/**
 * RepairPoint - Ticket A5 Paper Single Page
 * مُحسَّن للطباعة على ورق A5 في صفحة واحدة - أبيض وأسود
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

// Obtener datos de la reparación con información de reapertura
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

// Calcular información de duración y garantía
$repair_duration = calculateRepairDuration($repair['received_at'], $repair['delivered_at']);
$warranty_days = $repair['warranty_days'] ?? 30;
$warranty_days_left = 0;
$is_under_warranty = false;

if ($repair['delivered_at']) {
    $warranty_days_left = calculateWarrantyDaysLeft($repair['delivered_at'], $warranty_days);
    $is_under_warranty = isUnderWarranty($repair['delivered_at'], $warranty_days);
}

// Log de actividad
logActivity('ticket_printed', "Ticket A5 impreso para reparación #{$repair['reference']}", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= htmlspecialchars($repair['reference']) ?></title>

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
            font-family: 'Arial', sans-serif;
            background: white;
            color: #000;
            line-height: 1.3;
            font-size: 11px;
        }

        /* Configuración A5 Paper - Landscape */
        .ticket {
            width: 210mm;
            max-height: 148mm;
            margin: 0 auto;
            padding: 4mm;
            background: white;
            overflow: hidden;
        }

        /* Header compacto */
        .header {
            display: flex;
            align-items: center;
            border: 2px solid #000;
            padding: 3mm;
            margin-bottom: 3mm;
            background: repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 2px,
                    #f0f0f0 2px,
                    #f0f0f0 4px
            );
        }

        .header-logo {
            flex: 0 0 15mm;
            text-align: center;
        }

        .shop-logo {
            max-width: 12mm;
            max-height: 12mm;
            border: 1px solid #333;
        }

        .header-info {
            flex: 1;
            margin-left: 3mm;
        }

        .shop-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .shop-contact {
            font-size: 9px;
            line-height: 1.2;
        }

        .header-ticket {
            flex: 0 0 25mm;
            text-align: center;
            border: 2px solid #000;
            padding: 2mm;
            background: white;
        }

        .ticket-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .ticket-reference {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .ticket-date {
            font-size: 7px;
            margin-top: 1mm;
        }

        /* Layout principal - optimizado para landscape */
        .main-content {
            display: flex;
            gap: 4mm;
            margin-bottom: 3mm;
            height: auto;
        }

        .content-left {
            flex: 3;
        }

        .content-right {
            flex: 2;
        }

        /* Secciones compactas */
        .section {
            margin-bottom: 2mm;
            border: 1px solid #000;
            overflow: hidden;
        }

        .section-header {
            background: #000;
            color: white;
            padding: 1mm 2mm;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .section-content {
            padding: 2mm;
            background: white;
        }

        /* Customer info compacto */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1mm;
            font-size: 9px;
        }

        .info-item {
            border-bottom: 1px dotted #999;
            padding-bottom: 0.5mm;
        }

        .info-label {
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 9px;
            margin-top: 0.5mm;
        }

        /* Device info */
        .device-box {
            background: repeating-linear-gradient(
                    90deg,
                    white,
                    white 5mm,
                    #f8f8f8 5mm,
                    #f8f8f8 10mm
            );
            border: 1px solid #333;
            padding: 2mm;
            text-align: center;
        }

        .device-brand {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .device-model {
            font-size: 10px;
        }

        /* Problem description compacto */
        .problem-box {
            background: white;
            border: 1px dashed #333;
            padding: 2mm;
            margin: 1mm 0;
        }

        .problem-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .problem-text {
            font-size: 9px;
            line-height: 1.3;
            font-style: italic;
        }

        /* Status badges para B&W */
        .status-box {
            text-align: center;
            margin: 1mm 0;
        }

        .status-badge {
            display: inline-block;
            padding: 1mm 2mm;
            border: 1px solid #333;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0.5mm;
        }

        .status-pending { background: repeating-linear-gradient(45deg, white, white 1px, #f0f0f0 1px, #f0f0f0 2px); }
        .status-in_progress { background: white; border-style: double; }
        .status-completed { background: #f0f0f0; }
        .status-delivered { background: #000; color: white; }
        .status-reopened { background: repeating-linear-gradient(45deg, white, white 2px, #000 2px, #000 3px); color: white; }

        .priority-high { border: 2px solid #000; }
        .priority-medium { border: 1px solid #000; }
        .priority-low { border: 1px dotted #000; }

        /* Costs compacto */
        .costs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1mm;
        }

        .cost-item {
            text-align: center;
            padding: 1mm;
            border: 1px solid #333;
            background: #f8f8f8;
        }

        .cost-label {
            font-size: 7px;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
        }

        .cost-value {
            font-size: 10px;
            font-weight: bold;
        }

        /* Warranty section compacto */
        .warranty-section {
            background: repeating-linear-gradient(
                    0deg,
                    white,
                    white 1mm,
                    #f0f0f0 1mm,
                    #f0f0f0 2mm
            );
            border: 2px solid #000;
            padding: 2mm;
            margin: 1mm 0;
        }

        .warranty-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 1mm;
            font-size: 9px;
            text-transform: uppercase;
        }

        .warranty-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1mm;
            font-size: 8px;
        }

        .warranty-item {
            text-align: center;
            border: 1px solid #333;
            padding: 1mm;
            background: white;
        }

        .warranty-label {
            font-size: 6px;
            text-transform: uppercase;
        }

        .warranty-value {
            font-weight: bold;
            margin-top: 0.5mm;
        }

        /* Reopen section para B&W */
        .reopen-section {
            background: repeating-linear-gradient(
                    45deg,
                    white,
                    white 3px,
                    #000 3px,
                    #000 6px
            );
            border: 3px solid #000;
            padding: 2mm;
            margin: 2mm 0;
        }

        .reopen-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 1mm;
            font-size: 10px;
            text-transform: uppercase;
            background: white;
            padding: 1mm;
        }

        .reopen-alert {
            background: #000;
            color: white;
            padding: 1mm;
            margin-bottom: 1mm;
            text-align: center;
        }

        .reopen-alert-text {
            font-size: 9px;
            font-weight: bold;
        }

        .reopen-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1mm;
            font-size: 8px;
        }

        .reopen-item {
            border: 1px solid #000;
            padding: 1mm;
            background: white;
        }

        .reopen-label {
            font-weight: bold;
            font-size: 7px;
            text-transform: uppercase;
        }

        .reopen-value {
            margin-top: 0.5mm;
        }

        .reopen-reason {
            grid-column: 1 / -1;
            background: white;
            border: 1px dashed #000;
            padding: 1mm;
            margin-top: 1mm;
        }

        .reopen-reason-title {
            font-weight: bold;
            font-size: 7px;
            margin-bottom: 0.5mm;
            text-transform: uppercase;
        }

        .reopen-reason-text {
            font-size: 8px;
            font-style: italic;
        }

        /* Barcode section compacto */
        .barcode-section {
            text-align: center;
            border: 2px dashed #000;
            padding: 2mm;
            margin: 2mm 0;
            background: white;
        }

        .barcode-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .barcode-svg {
            max-width: 60mm;
            height: auto;
            margin: 1mm 0;
        }

        .barcode-number {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 1mm;
        }

        /* Timeline compacto */
        .timeline {
            position: relative;
            padding-left: 5mm;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 1mm;
            top: 0;
            bottom: 0;
            width: 1px;
            background: #333;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2mm;
            padding-left: 2mm;
            font-size: 8px;
        }

        .timeline-marker {
            position: absolute;
            left: -3mm;
            top: 1mm;
            width: 4px;
            height: 4px;
            border: 1px solid #000;
            background: white;
        }

        .timeline-marker.delivered,
        .timeline-marker.completed { background: #000; }
        .timeline-marker.reopened { background: repeating-linear-gradient(45deg, white, white 1px, #000 1px, #000 2px); }

        .timeline-content {
            background: white;
            border: 1px solid #ddd;
            padding: 1mm;
        }

        .timeline-title {
            font-size: 8px;
            font-weight: bold;
        }

        .timeline-date {
            font-size: 7px;
            color: #666;
        }

        /* Footer compacto */
        .footer {
            margin-top: 3mm;
            border-top: 2px solid #000;
            padding-top: 2mm;
            text-align: center;
        }

        .footer-thanks {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .footer-message {
            font-size: 8px;
            margin-bottom: 1mm;
        }

        .footer-info {
            font-size: 6px;
            border-top: 1px solid #333;
            padding-top: 1mm;
        }

        /* Controls simplificados */
        .controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 10px;
            border-radius: 6px;
            font-size: 11px;
            z-index: 1000;
        }

        .controls button {
            background: #333;
            color: white;
            border: none;
            padding: 6px 10px;
            margin: 2px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 10px;
        }

        .controls button:hover {
            background: #555;
        }

        .controls button.print {
            background: #000;
            font-weight: bold;
        }

        .controls button.close {
            background: #666;
        }

        /* Print styles optimizados */
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
                padding: 3mm;
                max-height: none;
            }

            @page {
                size: A5 portrait;
                margin: 3mm;
            }

            /* Asegurar que todo esté en B&W */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                color: #000 !important;
            }

            .section-header {
                background: #000 !important;
                color: white !important;
            }

            .reopen-alert {
                background: #000 !important;
                color: white !important;
            }

            .status-delivered {
                background: #000 !important;
                color: white !important;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ticket {
                width: 100%;
                padding: 3mm;
            }

            .header {
                flex-direction: column;
                text-align: center;
            }

            .main-content {
                flex-direction: column;
            }

            .info-grid,
            .costs-grid,
            .warranty-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<!-- Controls simplificados -->
<div class="controls">
    <button onclick="window.print()" class="print">🖨️ Imprimir</button>
    <button onclick="window.close()" class="close">❌ Cerrar</button>
</div>

<div class="ticket" id="ticket">
    <!-- Header compacto -->
    <div class="header">
        <div class="header-logo">
            <?php if (!empty($repair['logo'])): ?>
                <img src="<?= url(htmlspecialchars($repair['logo'])) ?>" alt="Logo" class="shop-logo">
            <?php else: ?>
                <div style="width: 12mm; height: 12mm; border: 1px solid #333; display: flex; align-items: center; justify-content: center; font-size: 7px;">LOGO</div>
            <?php endif; ?>
        </div>

        <div class="header-info">
            <div class="shop-name"><?= htmlspecialchars($repair['name']) ?></div>
            <div class="shop-contact">
                <?php if (!empty($repair['address'])): ?>
                    <?= htmlspecialchars($repair['address']) ?><br>
                <?php endif; ?>
                Tel: <?= htmlspecialchars($repair['phone1']) ?>
                <?php if (!empty($repair['phone2'])): ?>
                    / <?= htmlspecialchars($repair['phone2']) ?>
                <?php endif; ?>
                <?php if (!empty($repair['email'])): ?>
                    <br><?= htmlspecialchars($repair['email']) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="header-ticket">
            <div class="ticket-title">REPARACION</div>
            <div class="ticket-reference">#<?= htmlspecialchars($repair['reference']) ?></div>
            <div class="ticket-date"><?= formatDate($repair['received_at'], 'd/m/Y') ?></div>
        </div>
    </div>

    <!-- Contenido principal compacto -->
    <div class="main-content">
        <!-- Columna izquierda -->
        <div class="content-left">
            <!-- Cliente y dispositivo en una sección -->
            <div class="section">
                <div class="section-header">CLIENTE Y DISPOSITIVO</div>
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
                        <div class="info-item">
                            <div class="info-label">Marca:</div>
                            <div class="info-value"><?= htmlspecialchars($repair['brand_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Modelo:</div>
                            <div class="info-value"><?= htmlspecialchars($repair['model_name']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Problema -->
            <div class="section">
                <div class="section-header">PROBLEMA REPORTADO</div>
                <div class="section-content">
                    <div class="problem-box">
                        <div class="problem-text"><?= nl2br(htmlspecialchars($repair['issue_description'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Estado y costes -->
            <div class="section">
                <div class="section-header">ESTADO Y COSTES</div>
                <div class="section-content">
                    <div class="status-box">
                            <span class="status-badge status-<?= $repair['status'] ?>">
                                <?= getStatusName($repair['status']) ?>
                            </span>
                        <span class="status-badge priority-<?= $repair['priority'] ?>">
                                <?= ucfirst($repair['priority']) ?>
                            </span>
                    </div>

                    <?php if (!empty($repair['estimated_cost']) || !empty($repair['actual_cost'])): ?>
                        <div class="costs-grid" style="margin-top: 2mm;">
                            <?php if (!empty($repair['estimated_cost'])): ?>
                                <div class="cost-item">
                                    <div class="cost-label">Estimado</div>
                                    <div class="cost-value">€<?= number_format($repair['estimated_cost'], 2) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($repair['actual_cost'])): ?>
                                <div class="cost-item">
                                    <div class="cost-label">Final</div>
                                    <div class="cost-value">€<?= number_format($repair['actual_cost'], 2) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información de reapertura - Solo si está reabierto -->
            <?php if (!empty($repair['is_reopened'])): ?>
                <div class="reopen-section">
                    <div class="reopen-header">*** DISPOSITIVO BAJO GARANTIA ***</div>

                    <div class="reopen-alert">
                        <div class="reopen-alert-text">REPARACION REABIERTA BAJO GARANTIA</div>
                    </div>

                    <div class="reopen-details">
                        <?php if (!empty($repair['reopen_type'])): ?>
                            <div class="reopen-item">
                                <div class="reopen-label">Tipo:</div>
                                <div class="reopen-value">
                                    <?php
                                    $reopen_config = getConfig('reopen_types');
                                    echo $reopen_config[$repair['reopen_type']]['name'];
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($repair['reopen_date'])): ?>
                            <div class="reopen-item">
                                <div class="reopen-label">Fecha Reapertura:</div>
                                <div class="reopen-value"><?= formatDate($repair['reopen_date'], 'd/m/Y') ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- إضافة تاريخ انتهاء الضمان -->
                        <?php if (!empty($repair['delivered_at']) && !empty($warranty_days)): ?>
                            <div class="reopen-item">
                                <div class="reopen-label">Garantía Válida Hasta:</div>
                                <div class="reopen-value">
                                    <?= date('d/m/Y', strtotime($repair['delivered_at'] . " +{$warranty_days} days")) ?>
                                    (<?= $warranty_days_left ?> días restantes)
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($repair['reopen_reason'])): ?>
                            <div class="reopen-reason">
                                <div class="reopen-reason-title">Motivo Reapertura:</div>
                                <div class="reopen-reason-text"><?= htmlspecialchars($repair['reopen_reason']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Columna derecha -->
        <div class="content-right">
            <!-- Código de barras -->
            <div class="barcode-section">
                <div class="barcode-title">CODIGO IDENTIFICACION</div>
                <svg id="barcode" class="barcode-svg"></svg>
                <div class="barcode-number"><?= $repair['reference'] ?></div>
            </div>

            <!-- Timeline compacto -->
            <div class="section">
                <div class="section-header">CRONOLOGIA</div>
                <div class="section-content">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker pending"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Recibido</div>
                                <div class="timeline-date"><?= formatDate($repair['received_at'], 'd/m/Y H:i') ?></div>
                            </div>
                        </div>

                        <?php if (!empty($repair['completed_at'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker completed"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Completado</div>
                                    <div class="timeline-date"><?= formatDate($repair['completed_at'], 'd/m/Y H:i') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($repair['delivered_at'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker delivered"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Entregado</div>
                                    <div class="timeline-date"><?= formatDate($repair['delivered_at'], 'd/m/Y H:i') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($repair['reopen_date'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker reopened"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Reabierto</div>
                                    <div class="timeline-date"><?= formatDate($repair['reopen_date'], 'd/m/Y H:i') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Garantía compacta -->
            <div class="warranty-section">
                <div class="warranty-header">GARANTIA</div>
                <div class="warranty-grid">
                    <div class="warranty-item">
                        <div class="warranty-label">Dias</div>
                        <div class="warranty-value"><?= $warranty_days ?></div>
                    </div>

                    <div class="warranty-item">
                        <div class="warranty-label">Estado</div>
                        <div class="warranty-value">
                            <?php if (!empty($repair['delivered_at'])): ?>
                                <?= $is_under_warranty ? 'VALIDA' : 'EXPIRADA' ?>
                            <?php else: ?>
                                PENDIENTE
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="warranty-item">
                        <div class="warranty-label">Restante</div>
                        <div class="warranty-value">
                            <?= !empty($repair['delivered_at']) ? ($is_under_warranty ? $warranty_days_left : '0') : '-' ?>
                        </div>
                    </div>

                    <div class="warranty-item">
                        <div class="warranty-label">Expira</div>
                        <div class="warranty-value">
                            <?php if (!empty($repair['delivered_at'])): ?>
                                <?= date('d/m/y', strtotime($repair['delivered_at'] . " +{$warranty_days} days")) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer compacto -->
    <div class="footer">
        <div class="footer-thanks">*** GRACIAS POR CONFIAR EN NUESTRO SERVICIO ***</div>
        <div class="footer-message">
            Conserve este ticket para recoger su dispositivo reparado
        </div>
        <div class="footer-info">
            Tecnico: <?= htmlspecialchars($repair['created_by_name']) ?> | Duracion: <?= formatDurationSpanish($repair_duration) ?> | <?= date('d/m/Y H:i') ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        generateBarcode();
    });

    function generateBarcode() {
        try {
            const barcodeData = '<?= $repair['reference'] ?>';

            JsBarcode("#barcode", barcodeData, {
                format: "CODE128",
                width: 1.2,
                height: 30,
                displayValue: false,
                background: "#ffffff",
                lineColor: "#000000",
                margin: 1
            });

            console.log('Código de barras generado:', barcodeData);
        } catch (error) {
            console.error('Error generando código de barras:', error);
            document.querySelector('.barcode-section').innerHTML =
                '<div style="text-align: center; font-weight: bold;">Error en código</div>';
        }
    }

    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl+P para imprimir
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }

        // Escape para cerrar
        if (e.key === 'Escape') {
            window.close();
        }
    });

    // Auto-cerrar después de imprimir (opcional)
    window.addEventListener('afterprint', function() {
        setTimeout(() => {
            if (confirm('¿Desea cerrar la ventana de impresión?')) {
                window.close();
            }
        }, 1000);
    });

    // Log de información
    console.log('Ticket A5 Single Page cargado exitosamente');
    console.log('Reparación ID:', <?= $repair['id'] ?>);
    console.log('Reference:', '<?= $repair['reference'] ?>');
    <?php if (!empty($repair['is_reopened'])): ?>
    console.log('Reparación reabierta - Tipo:', '<?= $repair['reopen_type'] ?? 'N/A' ?>');
    <?php endif; ?>
</script>
</body>
</html>