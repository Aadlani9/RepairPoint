<?php
/**
 * RepairPoint - Gestión de Usuarios
 * Página exclusiva para administradores
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación y permisos de administrador
authMiddleware(true); // فقط للمديرين

$page_title = 'Gestión de Usuarios';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// معالجة العمليات POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('El token de seguridad no es válido', MSG_ERROR);
    } else {
        $action = $_POST['action'] ?? '';
        $success = false;
        $message = '';

        switch ($action) {
            case 'add_user':
                $success = addNewUser($_POST, $shop_id);
                $message = $success ? 'Usuario añadido correctamente' : 'Error al añadir el usuario';
                break;

            case 'edit_user':
                $success = editUser($_POST, $shop_id);
                $message = $success ? 'Usuario actualizado correctamente' : 'Error al actualizar el usuario';
                break;

            case 'toggle_status':
                $success = toggleUserStatus($_POST['user_id'], $shop_id);
                $message = $success ? 'Estado del usuario actualizado' : 'Error al actualizar el estado';
                break;

            case 'delete_user':
                $success = deleteUser($_POST['user_id'], $shop_id, $current_user['id']);
                $message = $success ? 'Usuario eliminado correctamente' : 'Error al eliminar el usuario';
                break;
        }

        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);

        // إعادة توجيه لتجنب إعادة الإرسال
        if ($success) {
            header('Location: ' . url('pages/users.php'));
            exit;
        }
    }
}

// فلاتر البحث
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

// بناء استعلام البحث
$where_conditions = ["u.shop_id = ?"];
$params = [$shop_id];

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if ($role_filter) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

if ($status_filter) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// جلب المستخدمين
$db = getDB();
$users = $db->select(
    "SELECT u.*, 
            (SELECT COUNT(*) FROM repairs WHERE created_by = u.id) as repairs_count,
            (SELECT MAX(created_at) FROM repairs WHERE created_by = u.id) as last_repair_date
     FROM users u 
     WHERE $where_clause
     ORDER BY u.created_at DESC",
    $params
);

// إحصائيات سريعة
$stats = [
    'total' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE shop_id = ?", [$shop_id])['count'] ?? 0,
    'active' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE shop_id = ? AND status = 'active'", [$shop_id])['count'] ?? 0,
    'admins' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE shop_id = ? AND role = 'admin'", [$shop_id])['count'] ?? 0,
    'staff' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE shop_id = ? AND role = 'staff'", [$shop_id])['count'] ?? 0,
];

// دوال المعالجة
function addNewUser($data, $shop_id) {
    $required_fields = ['name', 'email', 'password', 'role'];
    $errors = validateRequired($data, $required_fields);

    if (!empty($errors)) return false;

    $db = getDB();

    // التحقق من عدم تكرار الإيميل
    $existing = $db->selectOne("SELECT id FROM users WHERE email = ?", [trim($data['email'])]);
    if ($existing) return false;

    // التحقق من صحة الإيميل
    if (!isValidEmail($data['email'])) return false;

    // التحقق من طول كلمة المرور
    if (strlen($data['password']) < 6) return false;

    try {
        $user_id = $db->insert(
            "INSERT INTO users (name, email, phone, password, role, shop_id, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
            [
                cleanString($data['name']),
                cleanString($data['email']),
                cleanString($data['phone'] ?? ''),
                hashPassword($data['password']),
                $data['role'],
                $shop_id
            ]
        );

        if ($user_id) {
            logActivity('user_created', "Nuevo usuario: {$data['name']} ({$data['email']})", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error al añadir usuario: " . $e->getMessage());
    }

    return false;
}

function editUser($data, $shop_id) {
    $user_id = intval($data['user_id'] ?? 0);
    if (!$user_id) return false;

    $required_fields = ['name', 'email', 'role'];
    $errors = validateRequired($data, $required_fields);

    if (!empty($errors)) return false;

    $db = getDB();

    // التحقق من وجود المستخدم
    $user = $db->selectOne("SELECT * FROM users WHERE id = ? AND shop_id = ?", [$user_id, $shop_id]);
    if (!$user) return false;

    // التحقق من عدم تكرار الإيميل
    $existing = $db->selectOne("SELECT id FROM users WHERE email = ? AND id != ?", [trim($data['email']), $user_id]);
    if ($existing) return false;

    try {
        $updated = $db->update(
            "UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ? AND shop_id = ?",
            [
                cleanString($data['name']),
                cleanString($data['email']),
                cleanString($data['phone'] ?? ''),
                $data['role'],
                $user_id,
                $shop_id
            ]
        );

        if ($updated !== false) {
            logActivity('user_updated', "Actualización de usuario: {$data['name']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error al actualizar usuario: " . $e->getMessage());
    }

    return false;
}

function toggleUserStatus($user_id, $shop_id) {
    $user_id = intval($user_id);
    if (!$user_id) return false;

    $db = getDB();

    // التحقق من وجود المستخدم
    $user = $db->selectOne("SELECT * FROM users WHERE id = ? AND shop_id = ?", [$user_id, $shop_id]);
    if (!$user) return false;

    // منع المستخدم من إلغاء تفعيل نفسه
    if ($user_id == $_SESSION['user_id']) return false;

    $new_status = $user['status'] === 'active' ? 'inactive' : 'active';

    try {
        $updated = $db->update(
            "UPDATE users SET status = ? WHERE id = ? AND shop_id = ?",
            [$new_status, $user_id, $shop_id]
        );

        if ($updated !== false) {
            logActivity('user_status_changed', "Cambio de estado del usuario {$user['name']} a $new_status", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error al cambiar el estado del usuario: " . $e->getMessage());
    }

    return false;
}

function deleteUser($user_id, $shop_id, $current_user_id) {
    $user_id = intval($user_id);
    if (!$user_id) return false;

    // منع حذف المستخدم لنفسه
    if ($user_id == $current_user_id) return false;

    $db = getDB();

    // التحقق من وجود المستخدم
    $user = $db->selectOne("SELECT * FROM users WHERE id = ? AND shop_id = ?", [$user_id, $shop_id]);
    if (!$user) return false;

    try {
        $db->beginTransaction();

        // تحديث الإصلاحات لتشير لمستخدم محذوف
        $db->update("UPDATE repairs SET created_by = NULL WHERE created_by = ?", [$user_id]);

        // حذف المستخدم
        $deleted = $db->delete("DELETE FROM users WHERE id = ? AND shop_id = ?", [$user_id, $shop_id]);

        if ($deleted) {
            $db->commit();
            logActivity('user_deleted', "Eliminación del usuario: {$user['name']}", $current_user_id);
            return true;
        } else {
            $db->rollback();
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error al eliminar el usuario: " . $e->getMessage());
    }

    return false;
}

// تضمين الهيدر
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/dashboard.php') ?>">
                        <i class="bi bi-house"></i> Inicio
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-people"></i> Gestión de Usuarios
                </li>
            </ol>
        </nav>

        <!-- عنوان الصفحة -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header bg-primary text-white p-4 rounded">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-1">
                                <i class="bi bi-people me-2"></i>
                                Gestión de Usuarios
                            </h1>
                            <p class="mb-0 opacity-75">
                                Gestión del equipo de trabajo y permisos de usuarios
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="bi bi-person-plus me-2"></i>Añadir Usuario
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stat-number h4 mb-1"><?= $stats['total'] ?></div>
                                <div class="stat-label">Total de Usuarios</div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stat-number h4 mb-1"><?= $stats['active'] ?></div>
                                <div class="stat-label">Usuarios Activos</div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-person-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stat-number h4 mb-1"><?= $stats['admins'] ?></div>
                                <div class="stat-label">Administradores</div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-shield"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="stat-number h4 mb-1"><?= $stats['staff'] ?></div>
                                <div class="stat-label">Empleados</div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-person-workspace"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php displayMessage(); ?>

        <!-- فلاتر البحث -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Buscar</label>
                        <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                            <input type="text"
                                   class="form-control"
                                   id="search"
                                   name="search"
                                   placeholder="Nombre, correo o teléfono..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">Todos los roles</option>
                            <option value="admin" <?= ($role_filter === 'admin') ? 'selected' : '' ?>>
                                Administrador
                            </option>
                            <option value="staff" <?= ($role_filter === 'staff') ? 'selected' : '' ?>>
                                Empleado
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>
                                Activo
                            </option>
                            <option value="inactive" <?= ($status_filter === 'inactive') ? 'selected' : '' ?>>
                                Inactivo
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($search || $role_filter || $status_filter): ?>
                    <div class="mt-3">
                        <span class="text-muted">Filtros activos:</span>
                        <?php if ($search): ?>
                            <span class="badge bg-primary me-2">Buscar: <?= htmlspecialchars($search) ?></span>
                        <?php endif; ?>
                        <?php if ($role_filter): ?>
                            <span class="badge bg-secondary me-2">Rol: <?= $role_filter === 'admin' ? 'Administrador' : 'Empleado' ?></span>
                        <?php endif; ?>
                        <?php if ($status_filter): ?>
                            <span class="badge bg-secondary me-2">Estado: <?= $status_filter === 'active' ? 'Activo' : 'Inactivo' ?></span>
                        <?php endif; ?>
                        <a href="<?= url('pages/users.php') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Borrar filtros
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- جدول المستخدمين -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Lista de Usuarios
                    <span class="badge bg-primary ms-2"><?= count($users) ?></span>
                </h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($users)): ?>
                    <div class="text-center p-5">
                        <i class="bi bi-people display-4 text-muted mb-3"></i>
                        <h5 class="text-muted">No hay usuarios</h5>
                        <p class="text-muted mb-3">
                            No se encontraron usuarios que coincidan con la búsqueda
                        </p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus me-2"></i>Añadir primer usuario
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Reparaciones</th>
                                <th>Última actividad</th>
                                <th>Fecha de creación</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="user-row <?= $user['status'] === 'inactive' ? 'table-secondary' : '' ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <div class="avatar-circle <?= $user['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($user['name']) ?>
                                                        <?php if ($user['id'] == $current_user['id']): ?>
                                                            <span class="badge bg-primary ms-1">Tú</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <?= htmlspecialchars($user['email']) ?>
                                                    </div>
                                                    <?php if ($user['phone']): ?>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-telephone me-1"></i>
                                                            <?= htmlspecialchars($user['phone']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-warning text-dark">
                                            <i class="bi bi-shield me-1"></i>Administrador
                                        </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                            <i class="bi bi-person-workspace me-1"></i>Empleado
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="repairs-stats">
                                            <span class="fw-bold text-primary"><?= $user['repairs_count'] ?></span>
                                            <small class="text-muted">Reparación</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="last-activity">
                                            <?php if ($user['last_login']): ?>
                                                <div class="small">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?= formatDateTime($user['last_login']) ?>
                                                </div>
                                            <?php elseif ($user['last_repair_date']): ?>
                                                <div class="small text-muted">
                                                    <i class="bi bi-tools me-1"></i>
                                                    <?= formatDate($user['last_repair_date'], 'd/m/Y') ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= formatDate($user['created_at'], 'd/m/Y') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary"
                                                    onclick="editUser(<?= $user['id'] ?>)"
                                                    title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <?php if ($user['id'] != $current_user['id']): ?>
                                                <button class="btn btn-outline-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>"
                                                        onclick="toggleUserStatus(<?= $user['id'] ?>)"
                                                        title="<?= $user['status'] === 'active' ? 'Desactivar' : 'Activar' ?>">
                                                    <i class="bi bi-<?= $user['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                                </button>

                                                <button class="btn btn-outline-danger"
                                                        onclick="deleteUser(<?= $user['id'] ?>)"
                                                        title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal إضافة مستخدم -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <i class="bi bi-person-plus me-2"></i>Añadir nuevo usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_user">

                        <div class="mb-3">
                            <label for="add_name" class="form-label">Nombre completo *</label>
                            <input type="text"
                                   class="form-control"
                                   id="add_name"
                                   name="name"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, introduce el nombre completo
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="add_email" class="form-label">Correo electrónico *</label>
                            <input type="email"
                                   class="form-control"
                                   id="add_email"
                                   name="email"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, introduce un correo electrónico válido
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="add_phone" class="form-label">Número de teléfono</label>
                            <input type="tel"
                                   class="form-control"
                                   id="add_phone"
                                   name="phone"
                                   placeholder="+34 666 123 456">
                        </div>

                        <div class="mb-3">
                            <label for="add_password" class="form-label">Contraseña *</label>
                            <input type="password"
                                   class="form-control"
                                   id="add_password"
                                   name="password"
                                   minlength="6"
                                   required>
                            <div class="form-text">
                                La contraseña debe tener al menos 6 caracteres
                            </div>
                            <div class="invalid-feedback">
                                La contraseña debe tener al menos 6 caracteres
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="add_role" class="form-label">Rol *</label>
                            <select class="form-select" id="add_role" name="role" required>
                                <option value="">Seleccionar rol</option>
                                <option value="staff">Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecciona el rol del usuario
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Añadir usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal تعديل مستخدم -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="bi bi-pencil me-2"></i>Editar usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="" class="needs-validation" novalidate id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nombre completo *</label>
                            <input type="text"
                                   class="form-control"
                                   id="edit_name"
                                   name="name"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, introduce el nombre completo
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Correo electrónico *</label>
                            <input type="email"
                                   class="form-control"
                                   id="edit_email"
                                   name="email"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, introduce un correo electrónico válido
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Número de teléfono</label>
                            <input type="tel"
                                   class="form-control"
                                   id="edit_phone"
                                   name="phone"
                                   placeholder="+34 666 123 456">
                        </div>

                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Rol *</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="staff">Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecciona el rol del usuario
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Para cambiar la contraseña, el usuario debe hacerlo desde la página de perfil
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* أنماط خاصة بصفحة المستخدمين */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
        }

        .user-row {
            transition: background-color 0.2s ease;
        }

        .user-row:hover {
            background-color: rgba(13, 110, 253, 0.04);
        }

        .user-info {
            min-width: 200px;
        }

        .repairs-stats {
            text-align: center;
            min-width: 80px;
        }

        .last-activity {
            min-width: 120px;
            font-size: 0.875rem;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .table th {
            background-color: rgba(13, 110, 253, 0.1);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
        }

        /* أنماط النموذج */
        .needs-validation .form-control:invalid {
            border-color: var(--danger-color);
        }

        .needs-validation .form-control:valid {
            border-color: var(--success-color);
        }

        /* أنماط متجاوبة */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 2rem 1rem !important;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .user-info {
                min-width: auto;
            }

            .avatar-circle {
                width: 32px;
                height: 32px;
                font-size: 0.75rem;
            }

            .btn-group-sm .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }

            .stat-card .card-body {
                padding: 1rem;
                text-align: center;
            }

            .stat-icon {
                position: static;
                display: block;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .page-header {
                margin-left: -0.75rem;
                margin-right: -0.75rem;
                border-radius: 0;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }

            .user-info .fw-semibold {
                font-size: 0.875rem;
            }

            .user-info .small {
                font-size: 0.75rem;
            }
        }

        /* أنماط الرسوم المتحركة */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.5s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* أنماط الحالة */
        .table-secondary {
            opacity: 0.7;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تفعيل tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // إعداد التحقق من النماذج
            setupFormValidation();

            // إعداد البحث الفوري
            setupLiveSearch();
        });

        function setupFormValidation() {
            const forms = document.querySelectorAll('.needs-validation');

            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });

                // التحقق الفوري
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        if (this.checkValidity()) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    });
                });
            });
        }

        function setupLiveSearch() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;

                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);

                    searchTimeout = setTimeout(() => {
                        if (this.value.length >= 2 || this.value.length === 0) {
                            this.form.submit();
                        }
                    }, 500);
                });
            }
        }

        // دوال العمليات
        window.editUser = function(userId) {
            // البحث عن بيانات المستخدم في الجدول
            const userRows = document.querySelectorAll('.user-row');
            let userData = null;

            <?php foreach ($users as $user): ?>
            if (<?= $user['id'] ?> === userId) {
                userData = {
                    id: <?= $user['id'] ?>,
                    name: '<?= addslashes($user['name']) ?>',
                    email: '<?= addslashes($user['email']) ?>',
                    phone: '<?= addslashes($user['phone'] ?? '') ?>',
                    role: '<?= $user['role'] ?>'
                };
            }
            <?php endforeach; ?>

            if (userData) {
                // ملء النموذج بالبيانات
                document.getElementById('edit_user_id').value = userData.id;
                document.getElementById('edit_name').value = userData.name;
                document.getElementById('edit_email').value = userData.email;
                document.getElementById('edit_phone').value = userData.phone;
                document.getElementById('edit_role').value = userData.role;

                // عرض النموذج
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            }
        };

        window.toggleUserStatus = function(userId) {
            if (confirm('¿Estás seguro de cambiar el estado de este usuario?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= generateCSRFToken() ?>';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';

                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;

                form.appendChild(csrfInput);
                form.appendChild(actionInput);
                form.appendChild(userIdInput);

                document.body.appendChild(form);
                form.submit();
            }
        };

        window.deleteUser = function(userId) {
            if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= generateCSRFToken() ?>';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_user';

                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;

                form.appendChild(csrfInput);
                form.appendChild(actionInput);
                form.appendChild(userIdInput);

                document.body.appendChild(form);
                form.submit();
            }
        };

        // اختصارات لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // Ctrl+N لإضافة مستخدم جديد
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
                modal.show();
            }

            // Ctrl+F للبحث
            if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('search').focus();
            }

            // Escape لإغلاق النماذج
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal).hide();
                });
            }
        });

        // تنسيق رقم الهاتف تلقائياً
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\s/g, '');

                // تنسيق تلقائي للأرقام الإسبانية
                if (value.length === 9 && !value.startsWith('+34')) {
                    this.value = '+34 ' + value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
                }
            });
        });

        // إعادة تعيين النماذج عند الإغلاق
        document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            this.querySelector('form').classList.remove('was-validated');
        });

        document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            this.querySelector('form').classList.remove('was-validated');
        });
    </script>

<?php
// تضمين الفوتر
require_once INCLUDES_PATH . 'footer.php';
?>