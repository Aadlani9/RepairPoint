// في logout.php - الحل المباشر والأكثر أماناً
<?php
/**
 * RepairPoint - Cerrar Sesión
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once 'config/config.php';
require_once INCLUDES_PATH . 'functions.php';

// Verificar que hay sesión activa
if (!isLoggedIn()) {
    header('Location: ' . url('pages/login.php'));
    exit;
}

// تسجيل النشاط قبل الخروج
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    logActivity('logout', "Cierre de sesión", $user_id);
}

// Limpiar sesión
session_destroy();

// Limpiar cookie de "recordarme"
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');

    // إزالة token من قاعدة البيانات
    if ($user_id) {
        $db = getDB();
        $db->update(
            "UPDATE users SET remember_token = NULL WHERE id = ?",
            [$user_id]
        );
    }
}

// Establecer mensaje de despedida
setMessage('Sesión cerrada correctamente', MSG_SUCCESS);

// Redirigir a login
header('Location: ' . url('pages/login.php'));
exit;
?>