<?php
/**
 * RepairPoint - Print Selector
 * صفحة اختيار حجم الطباعة للتذاكر
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

// Verificar que la reparación pertenece al shop
$db = getDB();
$repair = $db->selectOne(
    "SELECT id, reference FROM repairs WHERE id = ? AND shop_id = ?",
    [$repair_id, $shop_id]
);

if (!$repair) {
    die('Reparación no encontrada');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Formato de Impresión</title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .selector-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 90%;
        }

        .selector-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .selector-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .selector-header .repair-ref {
            color: #667eea;
            font-size: 20px;
            font-weight: bold;
        }

        .format-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .format-card {
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .format-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .format-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .format-card input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .format-card label {
            cursor: pointer;
            display: block;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin: -25px;
        }

        .format-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .format-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .format-desc {
            font-size: 13px;
            opacity: 0.8;
            line-height: 1.4;
        }

        .format-size {
            font-size: 12px;
            margin-top: 8px;
            font-weight: bold;
            opacity: 0.6;
        }

        .print-options {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .print-options h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }

        .checkbox-option {
            display: flex;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .checkbox-option:hover {
            background: #e8eaf6;
        }

        .checkbox-option input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
        }

        .checkbox-option label {
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .checkbox-option .option-desc {
            font-size: 12px;
            color: #666;
            margin-left: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-print:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .preview-info {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 10px;
            border: 2px solid #ffc107;
        }

        .preview-info p {
            margin: 5px 0;
            font-size: 13px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <div class="selector-header">
            <h1>🖨️ Seleccionar Formato de Impresión</h1>
            <div class="repair-ref">Reparación #<?= htmlspecialchars($repair['reference']) ?></div>
        </div>

        <form id="printForm" method="GET" target="_blank">
            <input type="hidden" name="id" value="<?= $repair_id ?>">

            <div class="format-options">
                <!-- Ticket 57mm -->
                <div class="format-card" onclick="selectFormat('57mm')">
                    <input type="radio" name="format" id="format_57mm" value="57mm">
                    <label for="format_57mm">
                        <div class="format-icon">🎫</div>
                        <div class="format-title">Ticket 57mm</div>
                        <div class="format-desc">Pequeño - POS térmica</div>
                        <div class="format-size">57mm × Auto</div>
                    </label>
                </div>

                <!-- Ticket 80mm -->
                <div class="format-card" onclick="selectFormat('80mm')">
                    <input type="radio" name="format" id="format_80mm" value="80mm" checked>
                    <label for="format_80mm">
                        <div class="format-icon">📋</div>
                        <div class="format-title">Ticket 80mm</div>
                        <div class="format-desc">Estándar - POS térmica</div>
                        <div class="format-size">80mm × Auto</div>
                    </label>
                </div>

                <!-- A5 Archive -->
                <div class="format-card" onclick="selectFormat('a5')">
                    <input type="radio" name="format" id="format_a5" value="a5">
                    <label for="format_a5">
                        <div class="format-icon">📄</div>
                        <div class="format-title">A5 Completo</div>
                        <div class="format-desc">Solo archivo (con firma)</div>
                        <div class="format-size">148mm × 210mm</div>
                    </label>
                </div>
            </div>

            <div class="print-options">
                <h3>⚙️ Opciones Adicionales</h3>

                <div class="checkbox-option" onclick="toggleCheckbox('print_a5')">
                    <input type="checkbox" name="print_a5" id="print_a5" value="1" checked>
                    <label for="print_a5">
                        <span>Imprimir también A5 para archivo</span>
                        <span class="option-desc">(Recomendado - incluye firma del cliente)</span>
                    </label>
                </div>

                <div class="checkbox-option" onclick="toggleCheckbox('auto_print')">
                    <input type="checkbox" name="auto_print" id="auto_print" value="1">
                    <label for="auto_print">
                        <span>Imprimir automáticamente</span>
                        <span class="option-desc">(Sin vista previa)</span>
                    </label>
                </div>
            </div>

            <div class="preview-info">
                <p><strong>💡 Recomendación:</strong></p>
                <p>• Ticket (57/80mm) → Para el cliente</p>
                <p>• A5 con firma → Para archivo del establecimiento</p>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-print">
                    🖨️ Imprimir
                </button>
                <a href="?page=repair_details&id=<?= $repair_id ?>" class="btn btn-cancel">
                    ❌ Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
        function selectFormat(format) {
            document.getElementById('format_' + format).checked = true;

            // Si selecciona A5, desactivar la opción de "imprimir también A5"
            if (format === 'a5') {
                document.getElementById('print_a5').checked = false;
                document.getElementById('print_a5').disabled = true;
                document.getElementById('print_a5').parentElement.style.opacity = '0.5';
            } else {
                document.getElementById('print_a5').disabled = false;
                document.getElementById('print_a5').parentElement.style.opacity = '1';
            }
        }

        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            if (!checkbox.disabled) {
                checkbox.checked = !checkbox.checked;
            }
        }

        // Manejar el submit del formulario
        document.getElementById('printForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const format = document.querySelector('input[name="format"]:checked').value;
            const printA5 = document.getElementById('print_a5').checked;
            const autoPrint = document.getElementById('auto_print').checked;
            const repairId = <?= $repair_id ?>;

            // Determinar qué páginas abrir
            let pages = [];

            if (format === 'a5') {
                // Solo A5
                pages.push(`print_a5_archive.php?id=${repairId}&auto_print=${autoPrint ? 1 : 0}`);
            } else {
                // Ticket principal
                const ticketPage = format === '57mm' ? 'print_ticket_57mm.php' : 'print_ticket_80mm.php';
                pages.push(`${ticketPage}?id=${repairId}&auto_print=${autoPrint ? 1 : 0}`);

                // A5 adicional si está marcado
                if (printA5) {
                    pages.push(`print_a5_archive.php?id=${repairId}&auto_print=${autoPrint ? 1 : 0}`);
                }
            }

            // Abrir las páginas
            pages.forEach((page, index) => {
                setTimeout(() => {
                    window.open(page, '_blank');
                }, index * 500); // Delay para evitar bloqueo de popups
            });

            // Opcional: cerrar esta ventana después de un tiempo
            if (pages.length > 0) {
                setTimeout(() => {
                    if (confirm('¿Desea cerrar esta ventana?')) {
                        window.location.href = '?page=repair_details&id=' + repairId;
                    }
                }, 2000);
            }
        });

        // Prevenir que se cierre accidentalmente
        window.addEventListener('beforeunload', function(e) {
            // Solo si el usuario no ha impreso aún
            const hasInteracted = sessionStorage.getItem('print_initiated');
            if (!hasInteracted) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Marcar cuando se inicia la impresión
        document.getElementById('printForm').addEventListener('submit', function() {
            sessionStorage.setItem('print_initiated', 'true');
        });
    </script>
</body>
</html>
