<?php
define('SECURE_ACCESS', true);
header('Content-Type: application/json');
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$db       = getDB();
$shop_id  = $_SESSION['shop_id'];
$term     = trim($_GET['term'] ?? '');

if (strlen($term) < 2) {
    echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
    exit;
}

$like = '%' . $term . '%';

$customers = $db->select(
    "SELECT id, full_name, phone, email, id_type, id_number, address
     FROM customers
     WHERE shop_id = ? AND status = 'active'
       AND (phone LIKE ? OR id_number LIKE ? OR full_name LIKE ?)
     ORDER BY full_name
     LIMIT 10",
    [$shop_id, $like, $like, $like]
);

if ($customers === false) {
    $customers = [];
}

echo json_encode(['success' => true, 'data' => $customers]);
