<?php
/**
 * RepairPoint - Ticket 57mm PDF Generator
 * Generador PDF optimizado para impresoras térmicas de 57mm
 * Solución profesional sin dependencias del navegador
 */

// Iniciar output buffering para evitar errores de TCPDF
ob_start();

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
$action = $_GET['action'] ?? 'view'; // view o download

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

// Calcular información de garantía
$warranty_days = $repair['warranty_days'] ?? 30;
$warranty_days_left = 0;
$is_under_warranty = false;

if ($repair['delivered_at']) {
    $warranty_days_left = calculateWarrantyDaysLeft($repair['delivered_at'], $warranty_days);
    $is_under_warranty = isUnderWarranty($repair['delivered_at'], $warranty_days);
}

// Log de actividad
logActivity('ticket_pdf_generated', "Ticket PDF 57mm generado para reparación #{$repair['reference']}", $_SESSION['user_id']);

// Limpiar cualquier output previo
if (ob_get_length()) {
    ob_clean();
}

// Cargar TCPDF
require_once '../vendor/autoload.php';

/**
 * Clase personalizada para tickets 57mm
 */
class TicketPDF extends TCPDF {
    public function __construct() {
        // Configurar PDF para 57mm de ancho
        // Alto: auto (depende del contenido)
        parent::__construct('P', 'mm', array(57, 297), true, 'UTF-8', false);

        // Configuración
        $this->SetCreator('RepairPoint');
        $this->SetAuthor('RepairPoint System');
        $this->SetTitle('Ticket de Reparación');

        // Márgenes mínimos
        $this->SetMargins(2, 2, 2);
        $this->SetAutoPageBreak(true, 2);

        // Sin header/footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
    }
}

// Crear instancia PDF
$pdf = new TicketPDF();
$pdf->AddPage();

// Configurar fuente (DejaVu Sans soporta árabe perfectamente)
$pdf->SetFont('dejavusans', '', 8);

// ============================================
// HEADER - Logo y nombre del negocio
// ============================================
if (!empty($repair['logo']) && file_exists('../' . $repair['logo'])) {
    $logo_path = '../' . $repair['logo'];
    // Centrar logo
    $pdf->Image($logo_path, '', '', 20, 20, '', '', 'T', false, 300, 'C');
    $pdf->Ln(22);
}

// Nombre del negocio
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 5, strtoupper($repair['name']), 0, 1, 'C');

// Contacto del negocio
$pdf->SetFont('dejavusans', '', 7);
if (!empty($repair['phone1'])) {
    $pdf->Cell(0, 3, 'Tel: ' . $repair['phone1'], 0, 1, 'C');
}
if (!empty($repair['address'])) {
    $address_short = mb_strlen($repair['address']) > 35 ? mb_substr($repair['address'], 0, 35) . '...' : $repair['address'];
    $pdf->Cell(0, 3, $address_short, 0, 1, 'C');
}

// Línea separadora
$pdf->Ln(1);
$pdf->Cell(0, 0, '', 'T', 1, 'C', false, '', 1, false, 'T', 'M');
$pdf->Ln(2);

// ============================================
// TÍTULO
// ============================================
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 5, 'REPARACIÓN', 0, 1, 'C');

// Número de referencia
$pdf->SetFont('dejavusans', 'B', 13);
$pdf->Cell(0, 6, '#' . $repair['reference'], 0, 1, 'C');
$pdf->Ln(2);

// ============================================
// INFORMACIÓN DEL CLIENTE
// ============================================
$pdf->SetFont('dejavusans', 'B', 8);
$pdf->Cell(0, 4, 'Cliente:', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 8);
$pdf->Cell(0, 4, $repair['customer_name'], 0, 1, 'L');

$pdf->SetFont('dejavusans', 'B', 8);
$pdf->Cell(0, 4, 'Tel:', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 8);
$pdf->Cell(0, 4, $repair['customer_phone'], 0, 1, 'L');
$pdf->Ln(2);

// ============================================
// DISPOSITIVO - con borde
// ============================================
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetFillColor(255, 255, 255);

$y_before = $pdf->GetY();
$pdf->MultiCell(0, 4, '', 'LRTB', 'C', true, 1, '', '', true, 0, false, true, 0);
$y_after = $pdf->GetY();
$pdf->SetY($y_before);

$pdf->SetFont('dejavusans', '', 7);
$pdf->Cell(0, 3, 'Dispositivo:', 0, 1, 'C');
$pdf->SetFont('dejavusans', 'B', 9);
$device_name = $repair['brand_name'] . ' ' . $repair['model_name'];
$pdf->Cell(0, 4, $device_name, 0, 1, 'C');

$pdf->Rect($pdf->GetX(), $y_before, 53, 9);
$pdf->Ln(3);

// ============================================
// PROBLEMA - con borde punteado
// ============================================
$pdf->SetLineStyle(array('width' => 0.3, 'dash' => 1));
$y_problem_start = $pdf->GetY();

$pdf->SetFont('dejavusans', 'B', 8);
$pdf->Cell(0, 4, 'Problema:', 0, 1, 'L');

$pdf->SetFont('dejavusans', 'I', 8);
$pdf->MultiCell(0, 4, $repair['issue_description'], 0, 'L', false, 1, '', '', true, 0, false, true, 0);

$y_problem_end = $pdf->GetY();
$problem_height = $y_problem_end - $y_problem_start;
$pdf->Rect(2, $y_problem_start, 53, $problem_height);
$pdf->Ln(2);

// Volver a línea sólida
$pdf->SetLineStyle(array('width' => 0.5));

// ============================================
// COSTE - con borde doble
// ============================================
if (!empty($repair['estimated_cost']) || !empty($repair['actual_cost'])) {
    $cost = $repair['actual_cost'] ?? $repair['estimated_cost'];

    $y_cost_start = $pdf->GetY();

    // Borde doble (dos rectángulos)
    $pdf->Rect(2, $y_cost_start, 53, 12);
    $pdf->Rect(2.5, $y_cost_start + 0.5, 52, 11);

    $pdf->SetFont('dejavusans', '', 8);
    $pdf->Cell(0, 4, 'Coste:', 0, 1, 'C');

    $pdf->SetFont('dejavusans', 'BU', 16);
    $pdf->Cell(0, 8, '€' . number_format($cost, 2), 0, 1, 'C');

    $pdf->Ln(2);
}

// ============================================
// GARANTÍA - Solo si está reabierto
// ============================================
if (!empty($repair['is_reopened'])) {
    $y_warranty_start = $pdf->GetY();

    // Borde doble
    $pdf->Rect(2, $y_warranty_start, 53, 18);
    $pdf->Rect(2.5, $y_warranty_start + 0.5, 52, 17);

    // Header de garantía
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell(0, 5, '*** BAJO GARANTÍA ***', 0, 1, 'C', true);

    // Volver a negro
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);

    $pdf->SetFont('dejavusans', 'B', 8);
    $pdf->Cell(0, 4, 'REAPERTURA BAJO GARANTÍA', 0, 1, 'C');

    if (!empty($repair['delivered_at']) && !empty($warranty_days)) {
        $pdf->SetFont('dejavusans', 'B', 7);
        $pdf->Cell(0, 3, 'VÁLIDA HASTA:', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 7);
        $warranty_end = date('d/m/Y', strtotime($repair['delivered_at'] . " +{$warranty_days} days"));
        $pdf->Cell(0, 3, $warranty_end, 0, 1, 'C');
    }

    if (!empty($repair['reopen_reason'])) {
        $pdf->SetFont('dejavusans', 'B', 7);
        $pdf->Cell(0, 3, 'MOTIVO:', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 7);
        $reason_short = mb_strlen($repair['reopen_reason']) > 50 ? mb_substr($repair['reopen_reason'], 0, 50) . '...' : $repair['reopen_reason'];
        $pdf->MultiCell(0, 3, $reason_short, 0, 'L');
    }

    $pdf->Ln(2);
}

// ============================================
// LÍNEA SEPARADORA
// ============================================
$pdf->SetLineStyle(array('width' => 0.3, 'dash' => 1));
$pdf->Cell(0, 0, '', 'T', 1, 'C');
$pdf->Ln(2);

// ============================================
// CÓDIGO DE BARRAS
// ============================================
$pdf->SetFont('dejavusans', '', 7);
$pdf->write1DBarcode($repair['reference'], 'C128', '', '', 50, 10, 0.4, array('position' => 'C', 'border' => false, 'padding' => 0, 'fgcolor' => array(0,0,0), 'bgcolor' => array(255,255,255), 'text' => false), 'N');
$pdf->Ln(2);

// ============================================
// LÍNEA SEPARADORA
// ============================================
$pdf->Cell(0, 0, '', 'T', 1, 'C');
$pdf->Ln(2);

// ============================================
// CONDICIONES
// ============================================
$pdf->SetFont('dejavusans', 'B', 7);
$pdf->Cell(0, 3, 'CONDICIONES:', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 7);
$pdf->MultiCell(0, 3, "• Recoger en 30 días tras reparación.\n• Presentar este ticket obligatorio.\n• Ver condiciones completas en recibo A5.", 0, 'L');

// ============================================
// LÍNEA SEPARADORA
// ============================================
$pdf->Ln(1);
$pdf->SetLineStyle(array('width' => 0.5));
$pdf->Cell(0, 0, '', 'T', 1, 'C');
$pdf->Ln(2);

// ============================================
// FOOTER
// ============================================
$pdf->SetFont('dejavusans', 'B', 8);
$pdf->Cell(0, 4, '*** GRACIAS ***', 0, 1, 'C');
$pdf->SetFont('dejavusans', '', 7);
$pdf->Cell(0, 3, formatDate($repair['received_at'], 'd/m/Y H:i'), 0, 1, 'C');

// ============================================
// SALIDA DEL PDF
// ============================================

// Limpiar cualquier output buffering antes de enviar PDF
ob_end_clean();

$filename = 'ticket_' . $repair['reference'] . '.pdf';

if ($action === 'download') {
    // Descargar
    $pdf->Output($filename, 'D');
} else {
    // Ver en navegador (para imprimir)
    $pdf->Output($filename, 'I');
}

exit;
