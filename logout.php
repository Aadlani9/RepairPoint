<?php
/**
 * RepairPoint - Cerrar Sesión
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once 'config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar que hay sesión activa
if (!isLoggedIn()) {
    header('Location: ' . url('pages/login.php'));
    exit;
}

// Realizar logout
$auth = getAuth();
$result = $auth->logout();

// Establecer mensaje de despedida
setMessage($result['message'], MSG_SUCCESS);

// Redirigir a login
header('Location: ' . $result['redirect']);
exit;
?>