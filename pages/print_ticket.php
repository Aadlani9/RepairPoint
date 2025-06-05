<?php
/**
 * RepairPoint - Imprimir Ticket de Reparación
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
logActivity('ticket_printed', "Ticket impreso para reparación #{$repair['reference']}", $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= htmlspecialchars($repair['reference']) ?> - <?= APP_NAME ?></title>
    
    <!-- Print CSS -->
    <link href="<?= asset('css/print.css') ?>" rel="stylesheet" media="print">
    
    <style>
        /* Estilos específicos para el ticket */
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.3;
            margin: 0;
            padding: 10px;
            background: white;
        }
        
        .ticket {
            max-width: 300px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 15px;
            background: white;
        }
        
        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .shop-logo {
            max-width: 100px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .shop-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .shop-contact {
            font-size: 10px;
            line-height: 1.2;
        }
        
        .ticket-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 15px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        
        .ticket-reference {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }
        
        .ticket-section {
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .ticket-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 2px 0;
        }
        
        .ticket-row .label {
            font-weight: bold;
            min-width: 40%;
        }
        
        .ticket-row .value {
            text-align: right;
            word-break: break-word;
            max-width: 55%;
        }
        
        .ticket-issue {
            background: #f0f0f0;
            padding: 8px;
            border: 1px solid #ccc;
            margin: 10px 0;
            word-wrap: break-word;
        }
        
        .ticket-footer {
            text-align: center;
            font-size: 10px;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        
        .print-button button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button button:hover {
            background: #0056b3;
        }
        
        /* Ocultar botón al imprimir */
        @media print {
            .print-button {
                display: none;
            }
            
            body {
                padding: 0;
            }
            
            .ticket {
                border: none;
                max-width: none;
                margin: 0;
                padding: 0;
            }
        }
        
        /* Para impresoras térmicas */
        @media print and (max-width: 80mm) {
            body {
                font-size: 10px;
            }
            
            .shop-name {
                font-size: 14px;
            }
            
            .ticket-reference {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header del taller -->
        <div class="ticket-header">
            <?php if ($repair['logo']): ?>
                <img src="<?= htmlspecialchars($repair['logo']) ?>" alt="Logo" class="shop-logo">
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
                    <br>Email: <?= htmlspecialchars($repair['email']) ?>
                <?php endif; ?>
                
                <?php if ($repair['website']): ?>
                    <br>Web: <?= htmlspecialchars($repair['website']) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Título del ticket -->
        <div class="ticket-title">Ticket de Reparación</div>
        
        <!-- Referencia -->
        <div class="ticket-reference">#<?= htmlspecialchars($repair['reference']) ?></div>
        
        <!-- Información del cliente -->
        <div class="ticket-section">
            <div class="ticket-row">
                <span class="label">Cliente:</span>
                <span class="value"><?= htmlspecialchars($repair['customer_name']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Teléfono:</span>
                <span class="value"><?= htmlspecialchars($repair['customer_phone']) ?></span>
            </div>
        </div>
        
        <!-- Información del dispositivo -->
        <div class="ticket-section">
            <div class="ticket-row">
                <span class="label">Marca:</span>
                <span class="value"><?= htmlspecialchars($repair['brand_name']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Modelo:</span>
                <span class="value"><?= htmlspecialchars($repair['model_name']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Estado:</span>
                <span class="value"><?= getStatusName($repair['status']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Prioridad:</span>
                <span class="value"><?= ucfirst($repair['priority']) ?></span>
            </div>
        </div>
        
        <!-- Problema reportado -->
        <div class="ticket-section">
            <div class="ticket-row">
                <span class="label">Problema:</span>
            </div>
            <div class="ticket-issue">
                <?= nl2br(htmlspecialchars($repair['issue_description'])) ?>
            </div>
        </div>
        
        <!-- Información de fechas -->
        <div class="ticket-section">
            <div class="ticket-row">
                <span class="label">Recibido:</span>
                <span class="value"><?= formatDateTime($repair['received_at']) ?></span>
            </div>
            
            <?php if ($repair['estimated_completion']): ?>
            <div class="ticket-row">
                <span class="label">Est. Entrega:</span>
                <span class="value"><?= formatDate($repair['estimated_completion'], 'd/m/Y') ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($repair['completed_at']): ?>
            <div class="ticket-row">
                <span class="label">Completado:</span>
                <span class="value"><?= formatDateTime($repair['completed_at']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($repair['delivered_at']): ?>
            <div class="ticket-row">
                <span class="label">Entregado:</span>
                <span class="value"><?= formatDateTime($repair['delivered_at']) ?></span>
            </div>
            
            <?php if ($repair['delivered_by']): ?>
            <div class="ticket-row">
                <span class="label">Entregado por:</span>
                <span class="value"><?= htmlspecialchars($repair['delivered_by']) ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Información de costes -->
        <?php if ($repair['estimated_cost'] || $repair['actual_cost']): ?>
        <div class="ticket-section">
            <?php if ($repair['estimated_cost']): ?>
            <div class="ticket-row">
                <span class="label">Coste Estimado:</span>
                <span class="value">€<?= number_format($repair['estimated_cost'], 2) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($repair['actual_cost']): ?>
            <div class="ticket-row">
                <span class="label">Coste Final:</span>
                <span class="value">€<?= number_format($repair['actual_cost'], 2) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Información del técnico -->
        <div class="ticket-section">
            <div class="ticket-row">
                <span class="label">Registrado por:</span>
                <span class="value"><?= htmlspecialchars($repair['created_by_name']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Fecha impresión:</span>
                <span class="value"><?= formatDateTime(date('Y-m-d H:i:s')) ?></span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="ticket-footer">
            <div style="margin-bottom: 10px; font-weight: bold;">
                ¡Gracias por confiar en nosotros!
            </div>
            
            <div style="font-size: 9px; line-height: 1.1;">
                Conserve este ticket para la recogida del dispositivo.<br>
                Para cualquier consulta, contacte con nosotros.<br>
                <?= APP_NAME ?> v<?= APP_VERSION ?>
            </div>
        </div>
    </div>
    
    <!-- Botón de impresión (solo visible en pantalla) -->
    <div class="print-button">
        <button onclick="window.print()" type="button">
            🖨️ Imprimir Ticket
        </button>
        <button onclick="window.close()" type="button" style="background: #6c757d; margin-left: 10px;">
            ❌ Cerrar
        </button>
    </div>
    
    <script>
        // Auto-imprimir al cargar (opcional)
        // window.addEventListener('load', function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 500);
        // });
        
        // Cerrar ventana después de imprimir
        window.addEventListener('afterprint', function() {
            // Preguntar si quiere cerrar la ventana
            setTimeout(() => {
                if (confirm('¿Cerrar la ventana?')) {
                    window.close();
                }
            }, 1000);
        });
        
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
    </script>
</body>
</html>