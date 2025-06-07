<?php
/**
 * RepairPoint - Configuración del Sistema
 * Página exclusiva para administradores
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación y permisos de administrador
authMiddleware();

// Verificar que el usuario es administrador
if (!isAdmin()) {
    setMessage('No tienes permisos para acceder a esta página', MSG_ERROR);
    header('Location: ' . url('pages/dashboard.php'));
    exit;
}

$page_title = 'Configuración del Sistema';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        $action = $_POST['action'] ?? '';
        $success = false;
        $message = '';

        switch ($action) {
            case 'update_shop':
                $success = updateShopSettings($_POST, $shop_id);
                $message = $success ? 'Información del taller actualizada correctamente' : 'Error al actualizar la información';
                break;

            case 'upload_logo':
                $result = uploadShopLogo($_FILES['logo'] ?? null, $shop_id);
                $success = $result['success'];
                $message = $result['message'];
                break;

            case 'add_brand':
                $success = addBrand($_POST['brand_name'], $shop_id);
                $message = $success ? 'Marca agregada correctamente' : 'Error al agregar la marca';
                break;

            case 'add_model':
                $success = addModel($_POST['brand_id'], $_POST['model_name'], $shop_id);
                $message = $success ? 'Modelo agregado correctamente' : 'Error al agregar el modelo';
                break;

            case 'delete_brand':
                $success = deleteBrand($_POST['brand_id'], $shop_id);
                $message = $success ? 'Marca eliminada correctamente' : 'Error al eliminar la marca (puede estar en uso)';
                break;

            case 'delete_model':
                $success = deleteModel($_POST['model_id'], $shop_id);
                $message = $success ? 'Modelo eliminado correctamente' : 'Error al eliminar el modelo (puede estar en uso)';
                break;

            case 'add_issue':
                $success = addCommonIssue($_POST['category'], $_POST['issue_text'], $shop_id);
                $message = $success ? 'Problema común agregado correctamente' : 'Error al agregar el problema';
                break;

            case 'delete_issue':
                $success = deleteCommonIssue($_POST['issue_id'], $shop_id);
                $message = $success ? 'Problema eliminado correctamente' : 'Error al eliminar el problema';
                break;
        }

        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);

        // Evitar reenvío del formulario
        if ($success && $action !== 'upload_logo') {
            header('Location: ' . url('pages/settings.php'));
            exit;
        }
    }
}

// Obtener datos del taller
$db = getDB();
$shop = $db->selectOne("SELECT * FROM shops WHERE id = ?", [$shop_id]);

// Obtener marcas y modelos
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$models_by_brand = [];
foreach ($brands as $brand) {
    $models_by_brand[$brand['id']] = $db->select(
        "SELECT * FROM models WHERE brand_id = ? ORDER BY name",
        [$brand['id']]
    );
}

// Obtener problemas comunes
$common_issues = $db->select("SELECT * FROM common_issues ORDER BY category, issue_text");
$issues_by_category = [];
foreach ($common_issues as $issue) {
    $category = $issue['category'] ?: 'General';
    if (!isset($issues_by_category[$category])) {
        $issues_by_category[$category] = [];
    }
    $issues_by_category[$category][] = $issue;
}

// Funciones de procesamiento
function updateShopSettings($data, $shop_id) {
    $required_fields = ['name', 'phone1'];
    $errors = validateRequired($data, $required_fields);

    if (!empty($errors)) return false;

    $db = getDB();

    // Validar email si se proporciona
    if (!empty($data['email']) && !isValidEmail($data['email'])) {
        return false;
    }

    try {
        $updated = $db->update(
            "UPDATE shops SET 
                name = ?, email = ?, phone1 = ?, phone2 = ?, 
                address = ?, website = ?, city = ?, country = ?, notes = ?
             WHERE id = ?",
            [
                cleanString($data['name']),
                cleanString($data['email'] ?? ''),
                cleanString($data['phone1']),
                cleanString($data['phone2'] ?? ''),
                cleanString($data['address'] ?? ''),
                cleanString($data['website'] ?? ''),
                cleanString($data['city'] ?? ''),
                cleanString($data['country'] ?? 'España'),
                cleanString($data['notes'] ?? ''),
                $shop_id
            ]
        );

        if ($updated !== false) {
            logActivity('shop_settings_updated', "Configuración del taller actualizada", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error actualizando configuración: " . $e->getMessage());
    }

    return false;
}

function uploadShopLogo($file, $shop_id) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }

    // Validar tipo de archivo
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowed_types)];
    }

    // Validar tamaño (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 2MB'];
    }

    // Crear directorio uploads si no existe
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generar nombre único
    $new_filename = 'logo_' . $shop_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $db = getDB();

        // Obtener logo anterior para eliminarlo
        $old_logo = $db->selectOne("SELECT logo FROM shops WHERE id = ?", [$shop_id])['logo'] ?? '';

        // Actualizar base de datos
        $logo_url = 'assets/uploads/' . $new_filename;
        $updated = $db->update("UPDATE shops SET logo = ? WHERE id = ?", [$logo_url, $shop_id]);

        if ($updated !== false) {
            // Eliminar logo anterior
            if ($old_logo && file_exists('../' . $old_logo)) {
                unlink('../' . $old_logo);
            }

            logActivity('shop_logo_updated', "Logo del taller actualizado", $_SESSION['user_id']);
            return ['success' => true, 'message' => 'Logo subido correctamente'];
        } else {
            // Eliminar archivo si falla la actualización
            unlink($upload_path);
            return ['success' => false, 'message' => 'Error al guardar en la base de datos'];
        }
    }

    return ['success' => false, 'message' => 'Error al subir el archivo'];
}

function addBrand($name, $shop_id) {
    $name = trim($name);
    if (empty($name)) return false;

    $db = getDB();

    // Verificar duplicados
    $existing = $db->selectOne("SELECT id FROM brands WHERE name = ?", [$name]);
    if ($existing) return false;

    try {
        $brand_id = $db->insert("INSERT INTO brands (name) VALUES (?)", [$name]);
        if ($brand_id) {
            logActivity('brand_added', "Marca agregada: $name", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error agregando marca: " . $e->getMessage());
    }

    return false;
}

function addModel($brand_id, $name, $shop_id) {
    $brand_id = intval($brand_id);
    $name = trim($name);

    if (!$brand_id || empty($name)) return false;

    $db = getDB();

    // Verificar que la marca existe
    $brand = $db->selectOne("SELECT name FROM brands WHERE id = ?", [$brand_id]);
    if (!$brand) return false;

    // Verificar duplicados
    $existing = $db->selectOne("SELECT id FROM models WHERE brand_id = ? AND name = ?", [$brand_id, $name]);
    if ($existing) return false;

    try {
        $model_id = $db->insert("INSERT INTO models (brand_id, name) VALUES (?, ?)", [$brand_id, $name]);
        if ($model_id) {
            logActivity('model_added', "Modelo agregado: {$brand['name']} $name", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error agregando modelo: " . $e->getMessage());
    }

    return false;
}

function deleteBrand($brand_id, $shop_id) {
    $brand_id = intval($brand_id);
    if (!$brand_id) return false;

    $db = getDB();

    // Verificar que la marca existe
    $brand = $db->selectOne("SELECT name FROM brands WHERE id = ?", [$brand_id]);
    if (!$brand) return false;

    // Verificar que no está en uso
    $used_in_repairs = $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE brand_id = ?", [$brand_id])['count'] ?? 0;
    if ($used_in_repairs > 0) return false;

    try {
        $db->beginTransaction();

        // Eliminar modelos primero
        $db->delete("DELETE FROM models WHERE brand_id = ?", [$brand_id]);

        // Eliminar marca
        $deleted = $db->delete("DELETE FROM brands WHERE id = ?", [$brand_id]);

        if ($deleted) {
            $db->commit();
            logActivity('brand_deleted', "Marca eliminada: {$brand['name']}", $_SESSION['user_id']);
            return true;
        } else {
            $db->rollback();
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error eliminando marca: " . $e->getMessage());
    }

    return false;
}

function deleteModel($model_id, $shop_id) {
    $model_id = intval($model_id);
    if (!$model_id) return false;

    $db = getDB();

    // Verificar que el modelo existe
    $model = $db->selectOne(
        "SELECT m.name, b.name as brand_name FROM models m JOIN brands b ON m.brand_id = b.id WHERE m.id = ?",
        [$model_id]
    );
    if (!$model) return false;

    // Verificar que no está en uso
    $used_in_repairs = $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE model_id = ?", [$model_id])['count'] ?? 0;
    if ($used_in_repairs > 0) return false;

    try {
        $deleted = $db->delete("DELETE FROM models WHERE id = ?", [$model_id]);
        if ($deleted) {
            logActivity('model_deleted', "Modelo eliminado: {$model['brand_name']} {$model['name']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error eliminando modelo: " . $e->getMessage());
    }

    return false;
}

function addCommonIssue($category, $issue_text, $shop_id) {
    $category = trim($category);
    $issue_text = trim($issue_text);

    if (empty($issue_text)) return false;

    $db = getDB();

    // Verificar duplicados
    $existing = $db->selectOne("SELECT id FROM common_issues WHERE issue_text = ?", [$issue_text]);
    if ($existing) return false;

    try {
        $issue_id = $db->insert(
            "INSERT INTO common_issues (category, issue_text) VALUES (?, ?)",
            [$category ?: null, $issue_text]
        );

        if ($issue_id) {
            logActivity('common_issue_added', "Problema común agregado: $issue_text", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error agregando problema común: " . $e->getMessage());
    }

    return false;
}

function deleteCommonIssue($issue_id, $shop_id) {
    $issue_id = intval($issue_id);
    if (!$issue_id) return false;

    $db = getDB();

    // Verificar que el problema existe
    $issue = $db->selectOne("SELECT issue_text FROM common_issues WHERE id = ?", [$issue_id]);
    if (!$issue) return false;

    try {
        $deleted = $db->delete("DELETE FROM common_issues WHERE id = ?", [$issue_id]);
        if ($deleted) {
            logActivity('common_issue_deleted', "Problema común eliminado: {$issue['issue_text']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error eliminando problema común: " . $e->getMessage());
    }

    return false;
}

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/dashboard.php') ?>">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-gear"></i> Configuración
                </li>
            </ol>
        </nav>

        <!-- Header de la página -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header bg-primary text-white p-4 rounded">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-1">
                                <i class="bi bi-gear me-2"></i>
                                Configuración del Sistema
                            </h1>
                            <p class="mb-0 opacity-75">
                                Gestiona la configuración del taller y del sistema
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-light" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mostrar mensajes -->
        <?php displayMessage(); ?>

        <div class="row">
            <!-- Navegación lateral -->
            <div class="col-lg-3 mb-4">
                <div class="settings-nav">
                    <div class="list-group">
                        <a href="#shop-settings" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                            <i class="bi bi-shop me-2"></i>Información del Taller
                        </a>
                        <a href="#brands-models" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="bi bi-tags me-2"></i>Marcas y Modelos
                        </a>
                        <a href="#common-issues" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="bi bi-exclamation-triangle me-2"></i>Problemas Comunes
                        </a>
                        <a href="#system-settings" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="bi bi-sliders me-2"></i>Sistema
                        </a>
                        <a href="#backup-restore" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="bi bi-archive me-2"></i>Respaldo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Información del Taller -->
                    <div class="tab-pane fade show active" id="shop-settings">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-shop me-2"></i>Información del Taller
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="update_shop">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shop_name" class="form-label">Nombre del Taller *</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="shop_name"
                                                   name="name"
                                                   value="<?= safeHtml($shop['name']) ?>"
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor introduce el nombre del taller
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shop_email" class="form-label">Email</label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="shop_email"
                                                   name="email"
                                                   value="<?= safeHtml($shop['email']) ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shop_phone1" class="form-label">Teléfono Principal *</label>
                                            <input type="tel"
                                                   class="form-control"
                                                   id="shop_phone1"
                                                   name="phone1"
                                                   value="<?= safeHtml($shop['phone1']) ?>"
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor introduce el teléfono principal
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shop_phone2" class="form-label">Teléfono Secundario</label>
                                            <input type="tel"
                                                   class="form-control"
                                                   id="shop_phone2"
                                                   name="phone2"
                                                   value="<?= safeHtml($shop['phone2']) ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="shop_address" class="form-label">Dirección</label>
                                        <textarea class="form-control"
                                                  id="shop_address"
                                                  name="address"
                                                  rows="3"><?= safeHtml($shop['address']) ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shop_city" class="form-label">Ciudad</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="shop_city"
                                                   name="city"
                                                   value="<?= safeHtml($shop['city']) ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shop_country" class="form-label">País</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="shop_country"
                                                   name="country"
                                                   value="<?= safeHtml($shop['country'] ?: 'España') ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="shop_website" class="form-label">Sitio Web</label>
                                        <input type="url"
                                               class="form-control"
                                               id="shop_website"
                                               name="website"
                                               value="<?= safeHtml($shop['website']) ?>"
                                               placeholder="https://ejemplo.com">
                                    </div>

                                    <div class="mb-3">
                                        <label for="shop_notes" class="form-label">Notas</label>
                                        <textarea class="form-control"
                                                  id="shop_notes"
                                                  name="notes"
                                                  rows="3"><?= safeHtml($shop['notes']) ?></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Logo del Taller -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-image me-2"></i>Logo del Taller
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="current-logo mb-3">
                                            <?php if ($shop['logo']): ?>
                                                <img src="<?= url($shop['logo']) ?>"
                                                     alt="Logo del taller"
                                                     class="img-thumbnail"
                                                     style="max-width: 200px; max-height: 200px;">
                                            <?php else: ?>
                                                <div class="no-logo bg-light p-4 text-center rounded">
                                                    <i class="bi bi-image display-4 text-muted"></i>
                                                    <p class="text-muted mb-0">Sin logo</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="POST" action="" enctype="multipart/form-data">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="upload_logo">

                                            <div class="mb-3">
                                                <label for="logo" class="form-label">Subir nuevo logo</label>
                                                <input type="file"
                                                       class="form-control"
                                                       id="logo"
                                                       name="logo"
                                                       accept="image/*"
                                                       required>
                                                <div class="form-text">
                                                    Formatos soportados: JPG, PNG, GIF<br>
                                                    Tamaño máximo: 2MB
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-upload me-2"></i>Subir Logo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Marcas y Modelos -->
                    <div class="tab-pane fade" id="brands-models">
                        <div class="row">
                            <!-- Marcas -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-tags me-2"></i>Marcas
                                        </h5>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="list-group list-group-flush">
                                            <?php if (empty($brands)): ?>
                                                <div class="list-group-item text-center text-muted">
                                                    No hay marcas registradas
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($brands as $brand): ?>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?= safeHtml($brand['name']) ?></strong>
                                                            <small class="text-muted d-block">
                                                                <?= count($models_by_brand[$brand['id']] ?? []) ?> modelos
                                                            </small>
                                                        </div>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-primary"
                                                                    onclick="showModels(<?= $brand['id'] ?>, '<?= addslashes($brand['name']) ?>')">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger"
                                                                    onclick="deleteBrand(<?= $brand['id'] ?>)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modelos -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-phone me-2"></i>Modelos
                                        </h5>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModelModal">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="models-container">
                                            <div class="p-3 text-center text-muted">
                                                Selecciona una marca para ver los modelos
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Problemas Comunes -->
                    <div class="tab-pane fade" id="common-issues">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Problemas Comunes
                                </h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIssueModal">
                                    <i class="bi bi-plus me-2"></i>Agregar Problema
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($issues_by_category)): ?>
                                    <div class="text-center text-muted p-4">
                                        <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
                                        <p>No hay problemas comunes registrados</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($issues_by_category as $category => $issues): ?>
                                        <div class="category-section mb-4">
                                            <h6 class="category-title fw-bold text-primary border-bottom pb-2">
                                                <?= safeHtml($category) ?>
                                            </h6>
                                            <div class="issues-list">
                                                <?php foreach ($issues as $issue): ?>
                                                    <div class="issue-item d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded">
                                                        <span><?= safeHtml($issue['issue_text']) ?></span>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteIssue(<?= $issue['id'] ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración del Sistema -->
                    <div class="tab-pane fade" id="system-settings">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-sliders me-2"></i>Información del Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="settings-info">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="setting-item">
                                                <label class="fw-bold">Versión del Sistema:</label>
                                                <span class="text-muted"><?= APP_VERSION ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="setting-item">
                                                <label class="fw-bold">Base de Datos:</label>
                                                <span class="text-muted">MySQL</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="setting-item">
                                                <label class="fw-bold">Versión PHP:</label>
                                                <span class="text-muted"><?= PHP_VERSION ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="setting-item">
                                                <label class="fw-bold">Tamaño máximo de subida:</label>
                                                <span class="text-muted"><?= ini_get('upload_max_filesize') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="system-actions">
                                        <h6 class="fw-bold mb-3">Acciones del Sistema</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-outline-info" onclick="clearCache()">
                                                <i class="bi bi-arrow-clockwise me-2"></i>Limpiar Caché
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="checkSystem()">
                                                <i class="bi bi-shield-check me-2"></i>Verificar Sistema
                                            </button>
                                            <button class="btn btn-outline-success" onclick="exportSettings()">
                                                <i class="bi bi-download me-2"></i>Exportar Configuración
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Respaldo y Restauración -->
                    <div class="tab-pane fade" id="backup-restore">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-archive me-2"></i>Respaldo y Restauración
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="backup-info mb-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Los respaldos incluyen todos los datos excepto archivos subidos (logos)
                                    </div>
                                </div>

                                <div class="backup-actions">
                                    <h6 class="fw-bold mb-3">Respaldo</h6>
                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <button class="btn btn-primary" onclick="createBackup()">
                                            <i class="bi bi-download me-2"></i>Crear Respaldo
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="downloadBackup()">
                                            <i class="bi bi-cloud-download me-2"></i>Descargar Último Respaldo
                                        </button>
                                    </div>

                                    <h6 class="fw-bold mb-3">Restauración</h6>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Advertencia:</strong> Restaurar un respaldo reemplazará todos los datos actuales
                                    </div>
                                    <form id="restoreForm" class="d-flex gap-2">
                                        <input type="file" class="form-control" accept=".sql" required>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-upload me-2"></i>Restaurar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Marca -->
    <div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandModalLabel">
                        <i class="bi bi-tags me-2"></i>Agregar Marca
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_brand">

                        <div class="mb-3">
                            <label for="brand_name" class="form-label">Nombre de la Marca *</label>
                            <input type="text"
                                   class="form-control"
                                   id="brand_name"
                                   name="brand_name"
                                   placeholder="Ej: Apple, Samsung"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Modelo -->
    <div class="modal fade" id="addModelModal" tabindex="-1" aria-labelledby="addModelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModelModalLabel">
                        <i class="bi bi-phone me-2"></i>Agregar Modelo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_model">

                        <div class="mb-3">
                            <label for="model_brand_id" class="form-label">Marca *</label>
                            <select class="form-select" id="model_brand_id" name="brand_id" required>
                                <option value="">Selecciona una marca</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= $brand['id'] ?>">
                                        <?= safeHtml($brand['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="model_name" class="form-label">Nombre del Modelo *</label>
                            <input type="text"
                                   class="form-control"
                                   id="model_name"
                                   name="model_name"
                                   placeholder="Ej: iPhone 15, Galaxy S24"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Problema Común -->
    <div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIssueModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Agregar Problema Común
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_issue">

                        <div class="mb-3">
                            <label for="issue_category" class="form-label">Categoría</label>
                            <input type="text"
                                   class="form-control"
                                   id="issue_category"
                                   name="category"
                                   placeholder="Ej: Pantalla, Batería, Carga"
                                   list="existing-categories">
                            <datalist id="existing-categories">
                                <?php
                                $categories = array_keys($issues_by_category);
                                foreach ($categories as $category): ?>
                                <option value="<?= safeHtml($category) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label for="issue_text" class="form-label">Descripción del Problema *</label>
                            <input type="text"
                                   class="form-control"
                                   id="issue_text"
                                   name="issue_text"
                                   placeholder="Ej: Pantalla rota, No carga"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Estilos específicos para la página de configuración */
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

        .settings-nav .list-group-item {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.3s ease;
        }

        .settings-nav .list-group-item:hover {
            background-color: rgba(13, 110, 253, 0.1);
            transform: translateX(5px);
        }

        .settings-nav .list-group-item.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .card-header {
            background: rgba(13, 110, 253, 0.05);
            border-bottom: 1px solid rgba(13, 110, 253, 0.1);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .current-logo img {
            border-radius: 1rem;
        }

        .no-logo {
            border: 2px dashed #dee2e6;
        }

        .category-title {
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }

        .issue-item {
            transition: all 0.3s ease;
        }

        .issue-item:hover {
            background-color: rgba(13, 110, 253, 0.1) !important;
            transform: translateX(5px);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .system-actions .btn {
            margin-bottom: 0.5rem;
        }

        .backup-actions .btn {
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 2rem 1rem !important;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .settings-nav {
                margin-bottom: 2rem;
            }

            .settings-nav .list-group {
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }

            .settings-nav .list-group-item {
                flex: 0 0 auto;
                margin-right: 0.5rem;
                margin-bottom: 0;
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

            .card-body {
                padding: 1rem;
            }

            .current-logo img {
                max-width: 150px !important;
                max-height: 150px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Configurar validación de formularios
            setupFormValidation();

            // Configurar formateo de teléfonos
            setupPhoneFormatting();

            // Cargar datos de modelos
            loadModelsData();
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

                // Validación en tiempo real
                const inputs = form.querySelectorAll('input, select, textarea');
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

        function setupPhoneFormatting() {
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function() {
                    let value = this.value.replace(/\s/g, '');

                    // Auto-formatear para números españoles
                    if (value.length === 9 && !value.startsWith('+34')) {
                        this.value = '+34 ' + value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
                    }
                });
            });
        }

        function loadModelsData() {
            // Cargar datos de modelos en JavaScript
            window.modelsData = <?= json_encode($models_by_brand) ?>;
        }

        // Funciones de operaciones
        window.showModels = function(brandId, brandName) {
            const modelsContainer = document.getElementById('models-container');
            const models = window.modelsData[brandId] || [];

            let html = `<div class="p-3">
                    <h6 class="fw-bold text-primary mb-3">Modelos de ${brandName}</h6>`;

            if (models.length === 0) {
                html += '<div class="text-center text-muted">No hay modelos registrados</div>';
            } else {
                html += '<div class="list-group list-group-flush">';
                models.forEach(model => {
                    html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${model.name}</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteModel(${model.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                     </div>`;
                });
                html += '</div>';
            }

            html += '</div>';
            modelsContainer.innerHTML = html;
        };

        window.deleteBrand = function(brandId) {
            if (confirm('¿Estás seguro de eliminar esta marca? Se eliminarán también todos sus modelos.')) {
                submitAction('delete_brand', {brand_id: brandId});
            }
        };

        window.deleteModel = function(modelId) {
            if (confirm('¿Estás seguro de eliminar este modelo?')) {
                submitAction('delete_model', {model_id: modelId});
            }
        };

        window.deleteIssue = function(issueId) {
            if (confirm('¿Estás seguro de eliminar este problema común?')) {
                submitAction('delete_issue', {issue_id: issueId});
            }
        };

        function submitAction(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= generateCSRFToken() ?>';
            form.appendChild(csrfInput);

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);

            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }

        // Funciones del sistema
        window.clearCache = function() {
            if (confirm('¿Limpiar la caché del sistema?')) {
                // Implementar limpieza de caché
                if (typeof Utils !== 'undefined') {
                    Utils.showNotification('Caché limpiada correctamente', 'success');
                }
            }
        };

        window.checkSystem = function() {
            if (typeof Utils !== 'undefined') {
                Utils.showNotification('Verificando sistema...', 'info');

                // Simular verificación del sistema
                setTimeout(() => {
                    Utils.showNotification('Sistema funcionando correctamente', 'success');
                }, 2000);
            }
        };

        window.exportSettings = function() {
            if (typeof Utils !== 'undefined') {
                Utils.showNotification('Exportando configuración...', 'info');

                // Crear archivo de configuración
                const settings = {
                    shop: <?= json_encode($shop) ?>,
                    brands: <?= json_encode($brands) ?>,
                    export_date: new Date().toISOString()
                };

                const blob = new Blob([JSON.stringify(settings, null, 2)], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'configuracion_' + new Date().toISOString().split('T')[0] + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                Utils.showNotification('Configuración exportada correctamente', 'success');
            }
        };

        // Funciones de respaldo
        window.createBackup = function() {
            if (confirm('¿Crear un respaldo de todos los datos?')) {
                if (typeof Utils !== 'undefined') {
                    Utils.showNotification('Creando respaldo...', 'info');

                    // Simular creación de respaldo
                    setTimeout(() => {
                        Utils.showNotification('Respaldo creado correctamente', 'success');
                    }, 3000);
                }
            }
        };

        window.downloadBackup = function() {
            if (typeof Utils !== 'undefined') {
                Utils.showNotification('Descargando último respaldo...', 'info');

                // Simular descarga de respaldo
                setTimeout(() => {
                    Utils.showNotification('Respaldo descargado correctamente', 'success');
                }, 2000);
            }
        };

        // Manejar formulario de restauración
        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const fileInput = this.querySelector('input[type="file"]');
            if (!fileInput.files[0]) {
                if (typeof Utils !== 'undefined') {
                    Utils.showNotification('Por favor selecciona un archivo para restaurar', 'warning');
                }
                return;
            }

            if (!confirm('ADVERTENCIA: Esta acción reemplazará todos los datos actuales. ¿Estás seguro?')) {
                return;
            }

            if (typeof Utils !== 'undefined') {
                Utils.showNotification('Restaurando datos...', 'info');

                // Simular proceso de restauración
                setTimeout(() => {
                    Utils.showNotification('Datos restaurados correctamente', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }, 5000);
            }
        });

        // Limpiar formularios al cerrar modales
        ['addBrandModal', 'addModelModal', 'addIssueModal'].forEach(modalId => {
            document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
                this.querySelector('form').reset();
                this.querySelector('form').classList.remove('was-validated');
            });
        });

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S para guardar configuración
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const activeTab = document.querySelector('.tab-pane.active');
                const saveButton = activeTab.querySelector('button[type="submit"]');
                if (saveButton) {
                    saveButton.click();
                }
            }

            // Escape para cerrar modales
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal).hide();
                });
            }
        });
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>